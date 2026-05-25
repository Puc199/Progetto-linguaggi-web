<?php
session_start();
require_once 'db.php';

function aggiornaEventiScaduti(PDO $pdo): void
{
    try {
        $stmt = $pdo->prepare("
            UPDATE evento e
            INNER JOIN (
                SELECT id_evento, MAX(data_ora_inizio) AS ultima_replica
                FROM replica_evento
                GROUP BY id_evento
            ) r ON r.id_evento = e.id
            SET e.stato = 'completato'
            WHERE e.stato = 'programmato'
              AND r.ultima_replica < NOW()
        ");
        $stmt->execute();
    } catch (Throwable $e) {
    }
}

aggiornaEventiScaduti($pdo);

// Funzione di sicurezza per output
function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
