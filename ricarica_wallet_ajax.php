<?php
require_once 'init.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Utente non autenticato.'
    ]);
    exit();
}

$username = $_SESSION['username'] ?? '';
$ruolo = (int)($_SESSION['ruolo'] ?? 0);

if ($username === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Sessione non valida.'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metodo non consentito.'
    ]);
    exit();
}

$importo = isset($_POST['importo']) ? (float)$_POST['importo'] : 0;

if ($ruolo !== 2) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Solo i clienti possono ricaricare il wallet.'
    ]);
    exit();
}

if ($importo <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Inserisci un importo valido maggiore di 0.'
    ]);
    exit();
}

if ($importo > 1000) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'L’importo massimo per singola ricarica è € 1000,00.'
    ]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, saldo FROM utente WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Utente non trovato.'
        ]);
        exit();
    }

    $stmt = $pdo->prepare("UPDATE utente SET saldo = saldo + ? WHERE id = ?");
    $stmt->execute([$importo, $user['id']]);

    $stmt = $pdo->prepare("SELECT saldo FROM utente WHERE id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $updatedUser = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Ricarica effettuata con successo.',
        'nuovo_saldo' => number_format((float)$updatedUser['saldo'], 2, ',', '.')
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante la ricarica del wallet.'
    ]);
}
