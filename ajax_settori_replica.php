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
    $sql = "
    SELECT 
        es.id,
        es.id_evento,
        es.id_replica_evento,
        es.id_settore,
        es.prezzo,
        es.posti_totali,
        es.posti_disponibili,
        s.nome AS nome_settore,
        s.descrizione AS descrizione
    FROM evento_settore es
    JOIN settore s ON es.id_settore = s.id
    JOIN replica_evento r ON es.id_replica_evento = r.id
    WHERE es.id_replica_evento = ?
      AND r.stato <> 'annullata'
    ORDER BY es.prezzo ASC, s.nome ASC
";
    $stmt = $pdo->prepare($sql); // Prepara la query
    $stmt->execute([$id_replica]); // Esegue la query con il parametro id_replica
    $settori = $stmt->fetchAll(); // Recupera tutti i settori associati alla replica
 
    echo json_encode([ // Restituisce i dati in formato JSON
        'success' => true, // Indica che la richiesta è stata elaborata con successo
        'settori' => $settori  // Include i dati dei settori nella risposta
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Errore nel caricamento dei settori.'
    ]);
}