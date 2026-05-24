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
$idEvento = (int)($_POST['id_evento'] ?? 0);
$idEventoSettore = (int)($_POST['id_evento_settore'] ?? 0);
$prezzoPost = (float)($_POST['prezzo'] ?? 0);
$postiRaw = trim($_POST['posti'] ?? '');

$posti = array_filter(array_map('intval', explode(',', $postiRaw)), fn($p) => $p > 0);
$posti = array_values(array_unique($posti));

if ($idUtente <= 0 || $idEvento <= 0 || $idEventoSettore <= 0 || empty($posti)) {
    die("Dati acquisto non validi.");
}

try {
    $pdo->beginTransaction();

    $stmtES = $pdo->prepare("
        SELECT es.*, e.titolo
        FROM evento_settore es
        INNER JOIN evento e ON es.id_evento = e.id
        WHERE es.id = ? AND es.id_evento = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmtES->execute([$idEventoSettore, $idEvento]);
    $eventoSettore = $stmtES->fetch();

    if (!$eventoSettore) {
        throw new Exception("Evento o settore non valido.");
    }

    $prezzoUnitario = (float)$eventoSettore['prezzo'];
    $quantita = count($posti);
    $totale = $prezzoUnitario * $quantita;

    if ($eventoSettore['posti_disponibili'] < $quantita) {
        throw new Exception("Posti disponibili insufficienti.");
    }

    $stmtOccupied = $pdo->prepare("
        SELECT posto
        FROM biglietto
        WHERE id_evento_settore = ? AND posto IN (" . implode(',', array_fill(0, count($posti), '?')) . ")
        FOR UPDATE
    ");
    $stmtOccupied->execute(array_merge([$idEventoSettore], $posti));
    $occupiedRows = $stmtOccupied->fetchAll();

    if (!empty($occupiedRows)) {
        $occupiedSeats = array_column($occupiedRows, 'posto');
        throw new Exception("Alcuni posti sono già stati acquistati: " . implode(', ', $occupiedSeats));
    }

    $stmtUser = $pdo->prepare("SELECT saldo FROM utente WHERE id = ? LIMIT 1 FOR UPDATE");
    $stmtUser->execute([$idUtente]);
    $utente = $stmtUser->fetch();

    if (!$utente) {
        throw new Exception("Utente non trovato.");
    }

    $saldo = (float)$utente['saldo'];

    if ($saldo < $totale) {
        throw new Exception("Saldo insufficiente.");
    }

    $stmtInsert = $pdo->prepare("
        INSERT INTO biglietto (sigillo_fiscale, disponibilita, id_utente, id_evento_settore, posto, prezzo)
        VALUES (?, 1, ?, ?, ?, ?)
    ");

    $createdTicketIds = [];

    foreach ($posti as $posto) {
        $sigillo = bin2hex(random_bytes(8));
        $stmtInsert->execute([
            $sigillo,
            $idUtente,
            $idEventoSettore,
            $posto,
            $prezzoUnitario
        ]);
        $createdTicketIds[] = $pdo->lastInsertId();
    }

    $stmtUpdateSaldo = $pdo->prepare("
        UPDATE utente
        SET saldo = saldo - ?
        WHERE id = ?
    ");
    $stmtUpdateSaldo->execute([$totale, $idUtente]);

    $stmtUpdatePosti = $pdo->prepare("
        UPDATE evento_settore
        SET posti_disponibili = posti_disponibili - ?
        WHERE id = ?
    ");
    $stmtUpdatePosti->execute([$quantita, $idEventoSettore]);

    $_SESSION['saldo'] = $saldo - $totale;

    $pdo->commit();

    $firstTicketId = (int)$createdTicketIds[0];
    header("Location: confirmation.php?ticket_id=" . $firstTicketId);
    exit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Errore acquisto: " . $e->getMessage());
}
