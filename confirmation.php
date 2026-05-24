<?php
require_once 'init.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? '';

if ($username === '') {
    header("Location: login.php");
    exit();
}

$ticketDetails = $_SESSION['ticket_info'] ?? [];

if (empty($ticketDetails) || !is_array($ticketDetails)) {
    header("Location: user_dashboard.php");
    exit();
}

function getUserDetailsFromDatabase($pdo, $username) {
    $stmt = $pdo->prepare("SELECT nome, cognome, data_nascita, username FROM utente WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$userDetails = getUserDetailsFromDatabase($pdo, $username);

$settore = $ticketDetails['settore'] ?? 'N/D';
$totale = $ticketDetails['totale'] ?? '0.00';
$nomeEvento = $ticketDetails['nome_evento'] ?? 'Acquisto confermato';
$dataReplica = $ticketDetails['data_replica'] ?? null;
$oraReplica = $ticketDetails['ora_replica'] ?? null;

$posti = [];
$sigilli = [];

if (!empty($ticketDetails['biglietti']) && is_array($ticketDetails['biglietti'])) {
    foreach ($ticketDetails['biglietti'] as $b) {
        if (isset($b['posto'])) {
            $posti[] = 'P' . $b['posto'];
        }
        if (!empty($b['sigillo_fiscale'])) {
            $sigilli[] = $b['sigillo_fiscale'];
        }
    }
}

$postoStr = !empty($posti) ? implode(', ', $posti) : 'N/D';
$sigilloStr = !empty($sigilli) ? implode(', ', $sigilli) : 'N/D';
$numeroBiglietti = count($posti);

unset($_SESSION['ticket_info']);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferma Acquisto - EasyTicket</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="stylesheet" href="css/confirmation.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
    <main class="confirmation-shell">
        <section class="confirmation-hero card">
            <div class="confirmation-badge">Acquisto completato</div>
            <h1>Grazie per il tuo acquisto, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>
                La transazione è stata registrata correttamente. Qui sotto trovi il riepilogo
                del tuo ordine e i dettagli dei biglietti acquistati.
            </p>

            <div class="confirmation-actions">
                <a href="home.php" class="dash-btn dash-btn-primary">Torna alla Home</a>
                <a href="user_dashboard.php" class="dash-btn dash-btn-secondary">Vai ai miei biglietti</a>
            </div>
        </section>

        <section class="confirmation-grid">
            <article class="confirmation-card card">
                <div class="section-head">
                    <span class="section-kicker">Profilo</span>
                    <h2>Dati utente</h2>
                </div>

                <div class="info-list">
                    <div class="info-row">
                        <span>Nome</span>
                        <strong><?php echo htmlspecialchars($userDetails['nome'] ?? 'N/D'); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Cognome</span>
                        <strong><?php echo htmlspecialchars($userDetails['cognome'] ?? 'N/D'); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Data di nascita</span>
                        <strong><?php echo htmlspecialchars($userDetails['data_nascita'] ?? 'N/D'); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Username</span>
                        <strong><?php echo htmlspecialchars($userDetails['username'] ?? 'N/D'); ?></strong>
                    </div>
                </div>
            </article>

            <article class="confirmation-card card">
                <div class="section-head">
                    <span class="section-kicker">Ordine</span>
                    <h2>Dettagli acquisto</h2>
                </div>

                <div class="order-highlight">
                    <span>Evento</span>
                    <strong><?php echo htmlspecialchars($nomeEvento); ?></strong>
                </div>

                <div class="info-grid">
                    <div class="mini-box">
                        <span>Settore</span>
                        <strong><?php echo htmlspecialchars($settore); ?></strong>
                    </div>
                    <div class="mini-box">
                        <span>Biglietti</span>
                        <strong><?php echo htmlspecialchars((string)$numeroBiglietti); ?></strong>
                    </div>
                    <div class="mini-box">
                        <span>Data</span>
                        <strong><?php echo htmlspecialchars($dataReplica ?: 'N/D'); ?></strong>
                    </div>
                    <div class="mini-box">
                        <span>Ora</span>
                        <strong><?php echo htmlspecialchars($oraReplica ?: 'N/D'); ?></strong>
                    </div>
                </div>

                <div class="ticket-code-box">
                    <span>Posti assegnati</span>
                    <strong><?php echo htmlspecialchars($postoStr); ?></strong>
                </div>

                <div class="ticket-code-box">
                    <span>Sigillo fiscale</span>
                    <strong><?php echo htmlspecialchars($sigilloStr); ?></strong>
                </div>

                <div class="total-box">
                    <span>Prezzo totale</span>
                    <strong><?php echo htmlspecialchars($totale); ?>€</strong>
                </div>
            </article>
        </section>
    </main>
</body>
</html>