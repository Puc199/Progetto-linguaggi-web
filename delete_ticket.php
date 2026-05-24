<?php
require_once 'init.php';
header('Content-Type: application/json');

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
$ticketId = isset($data['id']) ? (int)$data['id'] : 0;

if ($ticketId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID biglietto mancante o non valido'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id FROM utente WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $utente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$utente) {
        throw new Exception('Utente non trovato');
    }

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

    $stmt = $pdo->prepare("DELETE FROM biglietto WHERE id = ?");
    $stmt->execute([$ticketId]);

    if ($stmt->rowCount() !== 1) {
        throw new Exception('Eliminazione del biglietto non riuscita');
    }

    $stmt = $pdo->prepare("UPDATE utente SET saldo = saldo + ? WHERE id = ?");
    $stmt->execute([(float)$biglietto['prezzo'], (int)$utente['id']]);

    $stmt = $pdo->prepare("
        UPDATE evento_settore
        SET posti_disponibili = LEAST(posti_disponibili + 1, posti_totali)
        WHERE id = ?
    ");
    $stmt->execute([(int)$biglietto['id_evento_settore']]);

    $stmt = $pdo->prepare("SELECT saldo FROM utente WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$utente['id']]);
    $nuovoSaldo = $stmt->fetchColumn();

    $pdo->commit();

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
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}