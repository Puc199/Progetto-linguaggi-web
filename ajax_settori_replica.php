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

$id_replica = (int)($_GET['id_replica'] ?? 0);

if ($id_replica <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Replica non valida.'
    ]);
    exit();
}

try {
    $sql = "SELECT es.id, es.id_evento, es.id_replica_evento, es.id_settore,
                   es.prezzo, es.posti_totali, es.posti_disponibili,
                   s.nome AS nome_settore
            FROM evento_settore es
            JOIN settore s ON es.id_settore = s.id
            WHERE es.id_replica_evento = ?
            ORDER BY es.prezzo ASC, s.nome ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_replica]);
    $settori = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'settori' => $settori
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Errore nel caricamento dei settori.'
    ]);
}