<?php
require_once 'init.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

if ((int)($_SESSION['ruolo'] ?? 0) !== 2) {
    header("Location: home.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: home.php");
    exit();
}

$idUtente = (int)($_SESSION['user_id'] ?? 0);
$username = $_SESSION['username'] ?? '';
$idEvento = (int)($_POST['id_evento'] ?? 0);
$idEventoSettore = (int)($_POST['id_evento_settore'] ?? 0);
$postiRaw = trim((string)($_POST['posti'] ?? ''));

$posti = array_filter(array_map('intval', explode(',', $postiRaw)), fn($p) => $p > 0);
$posti = array_values(array_unique($posti));
//casi in cui da errore 
if ($idUtente <= 0 || $username === '' || $idEvento <= 0 || $idEventoSettore <= 0 || empty($posti)) {
    die("Dati acquisto non validi.");
}

try {
    $pdo->beginTransaction(); 
    // Seleziona e blocca la riga di evento_settore per evitare race condition
    $stmtES = $pdo->prepare(" 
        SELECT 
            es.id,
            es.id_evento,
            es.id_replica_evento,
            es.id_settore,
            es.prezzo,
            es.posti_totali,
            es.posti_disponibili,
            e.titolo,
            s.nome AS settore_nome,
            r.data_ora_inizio,
            l.nome AS luogo_nome,
            l.citta
        FROM evento_settore es
        INNER JOIN evento e ON es.id_evento = e.id
        INNER JOIN settore s ON es.id_settore = s.id
        INNER JOIN replica_evento r ON es.id_replica_evento = r.id
        INNER JOIN luogo l ON e.id_luogo = l.id
        WHERE es.id = ? AND es.id_evento = ?
        LIMIT 1
        FOR UPDATE
    ");
    //verifica di correttezza di evento e settore
    $stmtES->execute([$idEventoSettore, $idEvento]);
    $eventoSettore = $stmtES->fetch();

    if (!$eventoSettore) { 
        throw new Exception("Evento o settore non valido."); //se non trova evento o settore, errore
    }

    $prezzoUnitario = (float)$eventoSettore['prezzo']; //prende il prezzo unitario del settore
    $quantita = count($posti); // prende la quantita di posti selezionati
    $totale = $prezzoUnitario * $quantita; //calcola il totale da pagare che verrà passato a confirmation.php

    if ((int)$eventoSettore['posti_disponibili'] < $quantita) {
        throw new Exception("Posti disponibili insufficienti."); //caso di errore
    }
    //altro caso di errore
    foreach ($posti as $posto) {
        if ($posto > (int)$eventoSettore['posti_totali']) {
            throw new Exception("Uno o più posti selezionati non sono validi.");
        }
    }
    //verifica dei posti occupati
    $placeholders = implode(',', array_fill(0, count($posti), '?'));
    $stmtOccupied = $pdo->prepare("
        SELECT posto
        FROM biglietto
        WHERE id_evento_settore = ? AND posto IN ($placeholders)
        FOR UPDATE
    ");
    $stmtOccupied->execute(array_merge([$idEventoSettore], $posti));
    $occupiedRows = $stmtOccupied->fetchAll(); //se ci sono posti occupati, errore con la lista dei posti occupati

    if (!empty($occupiedRows)) {
        $occupiedSeats = array_column($occupiedRows, 'posto');
        throw new Exception("Alcuni posti sono già stati acquistati: " . implode(', ', $occupiedSeats));
    }

    $stmtUser = $pdo->prepare("
        SELECT id, nome, cognome, data_nascita, username, saldo
        FROM utente
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmtUser->execute([$idUtente]);
    $utente = $stmtUser->fetch();

    if (!$utente) {
        throw new Exception("Utente non trovato.");
    }

    $saldo = (float)$utente['saldo'];

    if ($saldo < $totale) {
        throw new Exception("Saldo insufficiente.");
    }
        //query per creazione biglietto, inserimento dei dati
    $stmtInsert = $pdo->prepare("
        INSERT INTO biglietto (
            sigillo_fiscale,
            disponibilita,
            id_utente,
            id_evento_settore,
            posto,
            prezzo,
            stato_rimborso
        ) VALUES (?, 1, ?, ?, ?, ?, 'nessuno')
    ");

    $bigliettiCreati = [];

    foreach ($posti as $posto) { //per ogni posto selezionato, crea un biglietto con sigillo fiscale univoco
        $sigillo = strtoupper(bin2hex(random_bytes(8))); //funzione per la generazione del sigillo fiscale

        $stmtInsert->execute([ //esegue la query di inserimento del biglietto
            $sigillo,
            $idUtente,
            $idEventoSettore,
            $posto,
            $prezzoUnitario
        ]);

        $bigliettiCreati[] = [
            'id' => (int)$pdo->lastInsertId(),
            'posto' => (int)$posto,
            'prezzo' => number_format($prezzoUnitario, 2, ',', '.'),
            'sigillo_fiscale' => $sigillo
        ];
    }

    $stmtUpdateSaldo = $pdo->prepare(" 
        UPDATE utente
        SET saldo = saldo - ?
        WHERE id = ?
    "); //aggiorna il saldo dell'utente sottraendo il totale dell'acquisto
    $stmtUpdateSaldo->execute([$totale, $idUtente]);
    //aggiorna i posti disponibili del settore
    $stmtUpdatePosti = $pdo->prepare("
        UPDATE evento_settore
        SET posti_disponibili = posti_disponibili - ?
        WHERE id = ?
    ");
    $stmtUpdatePosti->execute([$quantita, $idEventoSettore]);

    $_SESSION['saldo'] = $saldo - $totale; //aggiorna il saldo in sessione
    //prepara le informazioni da mostrare nella pagina di conferma acquisto
    $dataOra = $eventoSettore['data_ora_inizio'] ?? null;
    $dataReplica = $dataOra ? date('d/m/Y', strtotime($dataOra)) : 'N/D';
    $oraReplica = $dataOra ? date('H:i', strtotime($dataOra)) : 'N/D';
    //salva in sessione tutte le informazioni necessarie per la pagina di conferma acquisto
    $_SESSION['ticket_info'] = [
        'evento' => $eventoSettore['titolo'],
        'settore' => $eventoSettore['settore_nome'],
        'luogo' => $eventoSettore['luogo_nome'],
        'citta' => $eventoSettore['citta'],
        'data_replica' => $dataReplica,
        'ora_replica' => $oraReplica,
        'quantita' => $quantita,
        'totale' => number_format($totale, 2, ',', '.'),
        'biglietti' => $bigliettiCreati,
        'utente' => [
            'nome' => $utente['nome'] ?? '',
            'cognome' => $utente['cognome'] ?? '',
            'data_nascita' => $utente['data_nascita'] ?? '',
            'username' => $utente['username'] ?? $username
        ]
    ];
    //
    $pdo->commit();// se è tutto ok 
 //reindirizza alla pagina di conferma acquisto
    header("Location: confirmation.php");
    exit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die("Errore acquisto: " . $e->getMessage());
}