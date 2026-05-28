<?php
require_once 'init.php';
//controllo login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? '';
if ($username === '') { //reindirizza al login se non c'è username
    header("Location: login.php");
    exit();
}
//acquisisce dati del biglietto
$ticketDetails = $_SESSION['ticket_info'] ?? [];
//se non ci sono dati allora vado ad user_dashboard
if (empty($ticketDetails) || !is_array($ticketDetails)) {
    header("Location: User_dashboard.php");
    exit();
}
//dati singoli
$utente = $ticketDetails['utente'] ?? [];
$nomeEvento = $ticketDetails['evento'] ?? 'Acquisto confermato';
$settore = $ticketDetails['settore'] ?? 'N/D';
$luogo = $ticketDetails['luogo'] ?? 'N/D';
$citta = $ticketDetails['citta'] ?? 'N/D';
$dataReplica = $ticketDetails['data_replica'] ?? 'N/D';
$oraReplica = $ticketDetails['ora_replica'] ?? 'N/D';
$totale = $ticketDetails['totale'] ?? '0,00';
$numeroBiglietti = (int)($ticketDetails['quantita'] ?? 0);

$posti = []; //array numeri posti
$sigilli = []; // array sigilli fiscali

if (!empty($ticketDetails['biglietti']) && is_array($ticketDetails['biglietti'])) {
    foreach ($ticketDetails['biglietti'] as $b) {
        if (isset($b['posto'])) { //scrive P davanti al numero del posto
            $posti[] = 'P' . (int)$b['posto'];
        }
        if (!empty($b['sigillo_fiscale'])) { 
            $sigilli[] = $b['sigillo_fiscale'];
        }
    }
}
// converto gli array in stringhe leggibili
$postoStr = !empty($posti) ? implode(', ', $posti) : 'N/D';
$sigilloStr = !empty($sigilli) ? implode(', ', $sigilli) : 'N/D';
// pulizia della sessione
unset($_SESSION['ticket_info']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferma Acquisto - EasyTicket</title>
    <link rel="stylesheet" href="css/base.css?v=2">
    <link rel="stylesheet" href="css/confirmation.css?v=2">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
<main class="confirmation-shell">
    <section class="confirmation-hero card"> <!--messaggio dinamico-->
        <div class="confirmation-badge">Acquisto completato</div>
        <h1>Grazie per il tuo acquisto, <?php echo esc($utente['username'] ?? $username); ?>!</h1>
        <p>
            La transazione è stata registrata correttamente.
            Qui sotto trovi il riepilogo del tuo ordine e i dettagli dei biglietti acquistati.
        </p>
        <div class="confirmation-actions">
            <a href="home.php" class="dash-btn dash-btn-primary">Torna alla Home</a>
            <a href="User_dashboard.php" class="dash-btn dash-btn-secondary">Vai ai miei biglietti</a>
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
                    <strong><?php echo esc($utente['nome'] ?? 'N/D'); ?></strong>
                </div>
                <div class="info-row">
                    <span>Cognome</span>
                    <strong><?php echo esc($utente['cognome'] ?? 'N/D'); ?></strong>
                </div>
                <div class="info-row">
                    <span>Data di nascita</span>
                    <strong><?php echo esc($utente['data_nascita'] ?? 'N/D'); ?></strong>
                </div>
                <div class="info-row">
                    <span>Username</span>
                    <strong><?php echo esc($utente['username'] ?? $username); ?></strong>
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
                <strong><?php echo esc($nomeEvento); ?></strong>
            </div>

            <div class="info-grid">
                <div class="mini-box">
                    <span>Settore</span>
                    <strong><?php echo esc($settore); ?></strong>
                </div>
                <div class="mini-box">
                    <span>Biglietti</span>
                    <strong><?php echo esc((string)$numeroBiglietti); ?></strong>
                </div>
                <div class="mini-box">
                    <span>Data</span>
                    <strong><?php echo esc($dataReplica); ?></strong>
                </div>
                <div class="mini-box">
                    <span>Ora</span>
                    <strong><?php echo esc($oraReplica); ?></strong>
                </div>
                <div class="mini-box">
                    <span>Luogo</span>
                    <strong><?php echo esc($luogo . ($citta !== 'N/D' ? ' - ' . $citta : '')); ?></strong>
                </div>
                <div class="mini-box">
                <span>Posti assegnati</span>
                <strong><?php echo esc($postoStr); ?></strong>
            </div>
        </div>

            <div class="confirmation-ticket-code-box">
                <span>QR Code</span>
                <img
                    src="https://quickchart.io/qr?text=<?php echo urlencode($sigilloStr); ?>&size=220"
                    alt="QR code del biglietto"
                    width="220"
                    height="220">
            </div>
        </article>
    </section>
</main>
</body>
</html>