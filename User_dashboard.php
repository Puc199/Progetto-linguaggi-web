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

$username = $_SESSION['username'] ?? '';
if ($username === '') {
    header("Location: login.php");
    exit();
}

$stmtUser = $pdo->prepare("
    SELECT id, nome, cognome, data_nascita, username, saldo
    FROM utente
    WHERE username = ?
    LIMIT 1
");
$stmtUser->execute([$username]);
$user = $stmtUser->fetch();

if (!$user) {
    die("Utente non trovato.");
}

$stmtTickets = $pdo->prepare("
    SELECT
        b.id,
        b.sigillo_fiscale,
        b.disponibilita,
        b.posto,
        b.prezzo,
        b.data_acquisto,
        b.stato_rimborso,
        e.id AS id_evento,
        e.titolo,
        e.immagine,
        e.stato AS stato_evento,
        s.nome AS settore_nome,
        r.data_ora_inizio,
        r.stato AS stato_replica,
        l.nome AS luogo_nome,
        l.citta,
        l.indirizzo          
    FROM biglietto b
    INNER JOIN evento_settore es ON b.id_evento_settore = es.id
    INNER JOIN evento e ON es.id_evento = e.id
    INNER JOIN settore s ON es.id_settore = s.id
    INNER JOIN replica_evento r ON es.id_replica_evento = r.id
    INNER JOIN luogo l ON e.id_luogo = l.id
    WHERE b.id_utente = ?
    ORDER BY r.data_ora_inizio ASC, b.id DESC
");
$stmtTickets->execute([(int)$user['id']]);
$tickets = $stmtTickets->fetchAll();

$numeroBiglietti = count($tickets);
$saldoFormattato = number_format((float)$user['saldo'], 2, ',', '.');

// filtro biglietti annullati da passare a JavaScript
$bigliettiAnnullati = [];
foreach ($tickets as $t) {
    if ($t['stato_evento'] === 'annullato' || ($t['stato_replica'] ?? '') === 'annullata') {
        $bigliettiAnnullati[] = [
            'ticket_id'   => (int)$t['id'],
            'nome_evento' => $t['titolo'],
            'data_evento' => $t['data_ora_inizio']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - EasyTicket</title>
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/User.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand">
            <img src="img/logo_sito.png" alt="Logo EasyTicket">
        </a>
        <nav class="user-nav">
            <a href="home.php" class="user-pill primary-pill">Home</a>
            <a href="logout.php" class="user-pill secondary-pill">Logout</a>
        </nav>
    </div>
</header>

<main class="page-shell">
    <section class="dashboard-welcome">
        <h1>Ciao, <?php echo esc($user['nome']); ?></h1>
        <p>Qui puoi controllare il tuo saldo e gestire i biglietti acquistati.</p>
    </section>

    <section class="wallet-section">
        <div class="wallet-card">
            <h2>Wallet</h2>

            <div class="wallet-balance">
                <span>Saldo disponibile</span>
                <strong id="wallet-saldo">€ <?php echo $saldoFormattato; ?> </strong>
            </div>

            <form id="wallet-form" class="wallet-form">
                <label for="wallet-importo">Importo da ricaricare</label>
                <input type="number" id="wallet-importo" name="importo" min="1" step="0.01" placeholder="Es. 20.00" required>
                <button type="submit" id="wallet-submit-btn">Ricarica wallet</button>
            </form>

            <div id="wallet-message" class="wallet-message" style="display:none;"></div>
        </div>
    </section>

    <section class="tickets-section">
        
        <h2>I miei biglietti</h2>
        <?php if (empty($tickets)): ?>
            <div class="empty-card">
                <h3>Nessun biglietto acquistato</h3>
                <p>Quando acquisterai un evento, i biglietti compariranno qui.</p>
                <a href="home.php" class="hero-cta">Vai agli eventi</a>
            </div>
        <?php else: ?>
            <div class="tickets-grid" id="tickets-grid">
                <?php foreach ($tickets as $index => $ticket): ?>
                    <?php
                    // LOGICA UNIFICATA: annullato O rimborsato = non valido (stesso stile)
                    $isCancelled = ($ticket['stato_evento'] === 'annullato');
                    $isReplicaCancelled = (($ticket['stato_replica'] ?? '') === 'annullata');
                    $isRefunded = ($ticket['stato_rimborso'] === 'rimborsato');
                    
                    // Unifico tutti gli stati non validi
                    $isNotValid = $isCancelled || $isReplicaCancelled || $isRefunded || ((int)$ticket['disponibilita'] === 0);
                    
                    // in questo modo se un biglietto si vede la grafica del biglietto cancellato 
                    $ticketClass = $isNotValid ? 'ticket-card cancelled' : 'ticket-card';

                    $dataEvento = !empty($ticket['data_ora_inizio']) ? date('d/m/Y', strtotime($ticket['data_ora_inizio'])) : 'N/D';
                    $oraEvento = !empty($ticket['data_ora_inizio']) ? date('H:i', strtotime($ticket['data_ora_inizio'])) : 'N/D';
                    $dataAcquisto = !empty($ticket['data_acquisto']) ? date('d/m/Y H:i', strtotime($ticket['data_acquisto'])) : 'N/D';
                    ?> 
                    <article class="<?php echo $ticketClass; ?>" id="ticket-card-<?php echo (int)$ticket['id']; ?>">
                        <div class="ticket-image">
                            <?php if (!empty($ticket['immagine'])): ?>
                                <img src="<?php echo esc($ticket['immagine']); ?>" alt="<?php echo esc($ticket['titolo']); ?>">
                            <?php else: ?>
                                <img src="img/evento-default.png" alt="Evento">
                            <?php endif; ?>

                            <div class="ticket-number-badge"><?php echo $index + 1; ?></div>

                            <?php if ($isNotValid): ?>
                                <div class="cancelled-stamp">Annullato</div> <!--patch annullato in caso di biglietto non valido-->
                            <?php endif; ?>
                        </div>

                        <div class="ticket-content">
                            <div>
                                <h3><?php echo esc($ticket['titolo']); ?></h3>

                                <div class="ticket-meta">
                                    <span>
                                        📍 <?php echo esc($ticket['luogo_nome'] . ' - ' . $ticket['citta']); ?>,
                                        <?php if (!empty($ticket['indirizzo'])): ?> 
                                                <?php echo esc($ticket['indirizzo']); ?>
                                        <?php endif; ?>
                                    </span>
                                    <span>🗓️ <?php echo esc($dataEvento . ' alle ' . $oraEvento); ?></span>
                                    <span>🎟️ Settore: <?php echo esc($ticket['settore_nome']); ?></span>
                                    <span>💺 Posto: P<?php echo (int)$ticket['posto']; ?></span>
                                    <span>🧾 Sigillo fiscale: <?php echo esc($ticket['sigillo_fiscale']); ?></span>
                                    <span>🕒 Acquistato il: <?php echo esc($dataAcquisto); ?></span>
                                    <?php if ($isNotValid): ?>
                                        <span>↩️ Rimborso: effettuato</span>
                                    <?php endif; ?>
                                </div>

                                <div class="ticket-price-new">
                                    € <?php echo number_format((float)$ticket['prezzo'], 2, ',', '.'); ?>
                                </div>

                            </div>

                            <?php if ($isNotValid): ?> <!--se è non valido visualizza messaggio di rimborso-->
                                <div class="ticket-refund-msg">
                                    ✅ Rimborso effettuato. L'importo è tornato sul wallet.
                                </div>
                                <button type="button" class="hide-ticket-btn" data-ticket-id="<?php echo (int)$ticket['id']; ?>" title="Nascondi questo biglietto">
                                    👁️ Nascondi
                                </button>
                            <?php else: ?>  <!--se è valido visualizza bottone per eliminare biglietto-->
                                <div class="ticket-actions-hover">
                                <button type="button" class="btn-delete-hover delete-ticket-btn" data-ticket-id="<?php echo (int)$ticket['id']; ?>">
                                    Elimina biglietto e ricevi rimborso
                                </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="ticket-qr">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo urlencode($ticket['sigillo_fiscale']); ?>" alt="QR Biglietto">

                            <?php if ($isNotValid): ?>
                                <div class="qr-overlay">NON VALIDO</div> <!--patch non valido sopra al qr-->
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    
</main>

<footer class="site-footer">
    <p>&copy; 2026 EasyTicket</p>
</footer>
                            <!--comunica al js che biglietti sono stati annullati-->
<script src="js/user_dashboard.js" defer></script>
</body>
</html>