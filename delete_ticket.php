<?php
require_once 'init.php';
header('Content-Type: application/json'); // comunica che la risposta sarà in json 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Non autorizzato'
    ]);
    exit;
}

$username = $_SESSION['username'] ?? '';
if ($username === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Sessione non valida'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$ticketId = isset($data['id']) ? (int)$data['id'] : 0; // estrae l'id del biglietto da eliminare

//caso di errore
if ($ticketId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID biglietto mancante o non valido'
    ]);
    exit;
}

try { // cosi facendo non si rischia di lasciare a metà l'operazione in caso di errore, se qualcosa va storto si fa il rollback e si annullano tutte le modifiche al database
    $pdo->beginTransaction();
    // ottiene l'id dell'utente loggato
    $stmt = $pdo->prepare("SELECT id FROM utente WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $utente = $stmt->fetch(PDO::FETCH_ASSOC);
    //errore
    if (!$utente) {
        throw new Exception('Utente non trovato');
    }
    //query per selezionare il biglietto da eliminare e bloccarlo per evitare race condition
    $stmt = $pdo->prepare("
        SELECT id, id_utente, id_evento_settore, posto, prezzo
        FROM biglietto
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$ticketId]);
    $biglietto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$biglietto) {
        throw new Exception('Biglietto non trovato');
    }

    if ((int)$biglietto['id_utente'] !== (int)$utente['id']) {
        throw new Exception('Non hai i permessi per eliminare questo biglietto');
    }
    //query per selezionare il settore collegato al biglietto e bloccarlo per evitare race condition
    $stmt = $pdo->prepare("
        SELECT id, posti_disponibili, posti_totali
        FROM evento_settore
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([(int)$biglietto['id_evento_settore']]);
    $eventoSettore = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$eventoSettore) {
        throw new Exception('Settore collegato al biglietto non trovato');
    }
    //elimina effettivamente il biglietto
    $stmt = $pdo->prepare("DELETE FROM biglietto WHERE id = ?");
    $stmt->execute([$ticketId]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception('Eliminazione del biglietto non riuscita');
    }
    //rimborsa l'utente
    $stmt = $pdo->prepare("UPDATE utente SET saldo = saldo + ? WHERE id = ?");
    $stmt->execute([(float)$biglietto['prezzo'], (int)$utente['id']]);
    //aggiorna i posti disponibili del settore, assicurandosi di non superare il totale dei posti
    $stmt = $pdo->prepare("
        UPDATE evento_settore
        SET posti_disponibili = LEAST(posti_disponibili + 1, posti_totali)
        WHERE id = ?
    ");
    $stmt->execute([(int)$biglietto['id_evento_settore']]);
    //ottiene il nuovo saldo dell'utente dopo il rimborso
    $stmt = $pdo->prepare("SELECT saldo FROM utente WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$utente['id']]); //
    $nuovoSaldo = $stmt->fetchColumn(); // conferma tutte le modifiche al database se tutto è andato a buon fine

    $pdo->commit(); // se tutto è andato bene
    // risposta al client biglietto eliminato
    echo json_encode([ 
        'success' => true,
        'message' => 'Biglietto eliminato, rimborso effettuato e posto reso di nuovo disponibile.',
        'nuovo_saldo' => number_format((float)$nuovoSaldo, 2, ',', '.'),
        'rimborso' => number_format((float)$biglietto['prezzo'], 2, ',', '.'),
        'posto_liberato' => (int)$biglietto['posto'],
        'id_evento_settore' => (int)$biglietto['id_evento_settore']
    ]);
} catch (Throwable $e) { 
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // annulla tutte le modifiche al database se c'è stato un errore
    }
    // messaaggio di errore al client
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}