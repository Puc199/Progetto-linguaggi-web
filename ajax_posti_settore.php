<?php
require_once 'init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Utente non autenticato.'
    ]);
    exit();
}

$id_evento_settore = (int)($_GET['id_evento_settore'] ?? 0);

if ($id_evento_settore <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Settore non valido.'
    ]);
    exit();
}

try {
    $stmt = $pdo->prepare("
    SELECT posto
    FROM biglietto
    WHERE id_evento_settore = ?
      AND disponibilita = 1
    ORDER BY posto ASC
    ");
    $stmt->execute([$id_evento_settore]);

    $postiOccupati = [];
    foreach ($stmt->fetchAll() as $row) {
        $postiOccupati[] = (int)$row['posto'];
    }

    echo json_encode([
        'success' => true,
        'posti_occupati' => $postiOccupati
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Errore nel caricamento dei posti occupati.'
    ]);
}