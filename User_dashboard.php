<?php
require_once 'init.php';
// Controllo accesso
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'] ?? '';
$ruolo = (int)($_SESSION['ruolo'] ?? 0);
if ($username === '') {
    die("Utente non valido.");
}

// Dati utente
$stmt = $pdo->prepare("SELECT id, username, saldo FROM utente WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$utente = $stmt->fetch();
if (!$utente) {
    die("Utente non trovato.");
}

// Dati biglietti CON IMMAGINE EVENTO
$stmt = $pdo->prepare("
    SELECT 
        b.id,
        e.titolo AS evento_nome,
        e.immagine AS evento_immagine,
        e.stato AS stato_evento,
        r.data_ora_inizio AS data_evento,
        r.stato AS stato_replica,
        s.nome AS settore_nome,
        b.posto,
        b.prezzo,
        b.sigillo_fiscale
    FROM biglietto b
    JOIN evento_settore es ON b.id_evento_settore = es.id
    JOIN replica_evento r ON es.id_replica_evento = r.id
    JOIN evento e ON es.id_evento = e.id
    JOIN settore s ON es.id_settore = s.id
    WHERE b.id_utente = ?
    ORDER BY r.data_ora_inizio DESC, e.titolo ASC
");
$stmt->execute([$utente['id']]);
$biglietti = $stmt->fetchAll();

$numeroBiglietti = count($biglietti);
$totaleSpeso = 0;
foreach ($biglietti as $biglietto) {
    $totaleSpeso += (float)$biglietto['prezzo'];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - EasyTicket</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/user.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand">
            <img src="img/logo_sito.png" alt="Logo EasyTicket">
        </a>
        <nav class="user-nav">
            <a href="home.php" class="user-pill secondary-pill">Home</a>
            <a href="logout.php" class="user-pill secondary-pill">Logout</a>
        </nav>
    </div>
</header>

<main class="page-shell">
    <!-- Ricarica Wallet (Solo per utenti standard, ruolo 2) -->
    <?php if ($ruolo === 2): ?>
    <section class="section-block wallet-section">
        <div class="section-heading">
            <h2>Ciao <?php echo htmlspecialchars($utente['username']); ?>, Ricarica Wallet</h2> <br>
        </div>
            <div class="wallet-balance">
                <span>Il tuo credito attuale</span>
                <strong id="wallet-display-balance">€ <?php echo number_format((float)$utente['saldo'], 2, ',', '.'); ?></strong>
            </div>
            <div class="wallet-form">
                <div id="wallet-feedback"></div>
                <form id="wallet-recharge-form">
                    <div class="admin-form-group">
                        <label for="wallet-importo">Importo da ricaricare</label>
                        <input
                            type="number"
                            id="wallet-importo"
                            name="importo"
                            min="1"
                            max="1000"
                            step="0.01"
                            placeholder="Es. 20.00"
                            required
                        >
                    </div>
                    <button type="submit" id="wallet-submit-btn" class="admin-submit">
                        Ricarica saldo
                    </button>
                </form>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sezione Biglietti -->
<section class="section-block tickets-section">
    <h2>I tuoi biglietti</h2>
    <?php if (empty($biglietti)): ?>
        <div class="empty-card">
            <h3>Nessun biglietto trovato</h3>
            <p>Non risultano ancora biglietti associati al tuo account.</p>
            <a href="home.php" class="admin-submit" style="display: inline-block; text-decoration: none;">
                Vai agli eventi
            </a>
        </div>
    <?php else: ?>
        <div class="tickets-grid">
            <?php
$numeroBiglietto = 1;
foreach ($biglietti as $biglietto):
$immagineEvento = !empty($biglietto['evento_immagine'])
? htmlspecialchars($biglietto['evento_immagine'])
: 'img/evento-default.png';
$eventoAnnullato = (($biglietto['stato_evento'] ?? '') === 'annullato') || (($biglietto['stato_replica'] ?? '') === 'annullata');
$testoStato = $eventoAnnullato ? 'Evento annullato' : 'Biglietto valido';
$classeStato = $eventoAnnullato ? 'ticket-status cancelled' : 'ticket-status active';
$classeCard = $eventoAnnullato ? 'ticket-card cancelled-event' : 'ticket-card';
?>
<div class="<?php echo $classeCard; ?>" id="ticket-card-<?php echo $biglietto['id']; ?>">
<!-- 1. Immagine a Sinistra -->
<div class="ticket-image">
<img src="<?php echo $immagineEvento; ?>"
alt="<?php echo htmlspecialchars($biglietto['evento_nome']); ?>">
<div class="ticket-number-badge"><?php echo $numeroBiglietto; ?></div>
<?php if ($eventoAnnullato): ?>
<div class="ticket-cancelled-overlay">ANNULLATO</div>
<?php endif; ?>
</div>
<!-- 2. Contenuto Centrale -->
<div class="ticket-content">
<div class="ticket-info">
<h3><?php echo htmlspecialchars($biglietto['evento_nome']); ?></h3>
<div class="ticket-meta">
<span>📍 <?php echo htmlspecialchars($biglietto['settore_nome']); ?></span>
<span>📅 <?php echo date('d/m/Y', strtotime($biglietto['data_evento'])); ?></span>
<span>🎫 Posto <?php echo htmlspecialchars($biglietto['posto']); ?></span>
</div>
<div class="ticket-price-new">€ <?php echo number_format($biglietto['prezzo'], 2, ',', '.'); ?></div>
<?php if ($eventoAnnullato): ?>
<div class="ticket-refund-info">
✅ Rimborso già effettuato sul tuo wallet
</div>
<?php endif; ?>
</div>
<!-- Bottone Elimina - Visibile SOLO se l'evento NON è annullato -->
<?php if (!$eventoAnnullato): ?>
<!-- Bottone Elimina - Visibile SOLO in Hover -->
<div class="ticket-actions-hover" onclick="event.stopPropagation();">
<button type="button" class="btn-delete-hover"
onclick="event.stopPropagation(); deleteTicket(<?php echo (int)$biglietto['id']; ?>, <?php echo (float)$biglietto['prezzo']; ?>, this);">
Elimina biglietto e ottieni rimborso
</button>
</div>
<?php endif; ?>
</div>
<!-- 3. QR Code a Destra -->
<div class="ticket-qr">
<?php if ($eventoAnnullato): ?>
<div class="qr-cancelled">
<img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($biglietto['sigillo_fiscale']); ?>"
alt="QR Code" style="opacity: 0.3;">
<div class="qr-invalid-badge">NON VALIDO</div>
</div>
<?php else: ?>
<img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($biglietto['sigillo_fiscale']); ?>"
alt="QR Code">
<?php endif; ?>
</div>
</div>
<?php
$numeroBiglietto++;
endforeach; ?>
        </div>
    <?php endif; ?>
</section>
</main>

<footer class="site-footer">
    <p>&copy; 2026 EasyTicket</p>
</footer>

<!-- Script JS -->
<script src="js/user_dashboard.js"></script>
</body>
</html>