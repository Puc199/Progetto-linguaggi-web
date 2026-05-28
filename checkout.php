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

$username = $_SESSION['username'] ?? '';
if ($username === '') {
    header("Location: login.php");
    exit();
}

$idEvento = (int)($_POST['id_evento'] ?? 0);
$idEventoSettore = (int)($_POST['id_evento_settore'] ?? 0);
$postiInput = $_POST['posti'] ?? '';

// Trasforma la stringa "1,2,3,4" inviata dal JS in un vero array PHP [1, 2, 3, 4]
if (is_string($postiInput)) {
    $postiInput = $postiInput !== '' ? explode(',', $postiInput) : [];
}

$posti = array_map('intval', $postiInput);
$posti = array_filter($posti, fn($p) => $p > 0);
$posti = array_values(array_unique($posti));
sort($posti);

if ($idEvento <= 0 || $idEventoSettore <= 0 || empty($posti)) {
    die("Dati checkout non validi.");
}

try {
    $stmtUser = $pdo->prepare("
        SELECT id, nome, cognome, username, saldo
        FROM utente
        WHERE username = ?
        LIMIT 1
    ");
    $stmtUser->execute([$username]);
    $utente = $stmtUser->fetch();

    if (!$utente) {
        throw new Exception("Utente non trovato.");
    }

    $stmt = $pdo->prepare("
        SELECT 
            es.id,
            es.id_evento,
            es.id_replica_evento,
            es.id_settore,
            es.prezzo,
            es.posti_totali,
            es.posti_disponibili,
            e.titolo,
            e.immagine,
            s.nome AS settore_nome,
            r.data_ora_inizio,
            l.nome AS luogo_nome,
            l.citta,
            l.indirizzo
        FROM evento_settore es
        INNER JOIN evento e ON es.id_evento = e.id
        INNER JOIN settore s ON es.id_settore = s.id
        INNER JOIN replica_evento r ON es.id_replica_evento = r.id
        INNER JOIN luogo l ON e.id_luogo = l.id
        WHERE es.id = ? AND es.id_evento = ?
        LIMIT 1
    ");
    $stmt->execute([$idEventoSettore, $idEvento]);
    $checkout = $stmt->fetch();

    if (!$checkout) {
        throw new Exception("Evento o settore non valido.");
    }

    foreach ($posti as $posto) {
        if ($posto > (int)$checkout['posti_totali']) {
            throw new Exception("Uno o più posti selezionati non sono validi.");
        }
    }

    $placeholders = implode(',', array_fill(0, count($posti), '?'));
    $stmtOccupati = $pdo->prepare("
        SELECT posto
        FROM biglietto
        WHERE id_evento_settore = ? AND posto IN ($placeholders)
    ");
    $stmtOccupati->execute(array_merge([$idEventoSettore], $posti));
    $occupati = $stmtOccupati->fetchAll();

    if (!empty($occupati)) {
        $postiOccupati = array_column($occupati, 'posto');
        throw new Exception("Alcuni posti non sono più disponibili: " . implode(', ', $postiOccupati));
    }

    $quantita = count($posti);
    $prezzoUnitario = (float)$checkout['prezzo'];
    $totale = $prezzoUnitario * $quantita;
    $saldoUtente = (float)$utente['saldo'];
    $saldoSufficiente = $saldoUtente >= $totale;

    $postiStringa = implode(',', $posti);
    $dataEvento = !empty($checkout['data_ora_inizio']) ? date('d/m/Y', strtotime($checkout['data_ora_inizio'])) : 'N/D';
    $oraEvento = !empty($checkout['data_ora_inizio']) ? date('H:i', strtotime($checkout['data_ora_inizio'])) : 'N/D';

} catch (Throwable $e) {
    die("Errore checkout: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - EasyTicket</title>
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/checkout.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand">
            <img src="img/logo_sito.png" alt="Logo EasyTicket">
        </a>
        <nav class="user-nav">
            <a href="User_dashboard.php" class="user-pill primary-pill"><?php echo esc($utente['username']); ?></a>
            <a href="logout.php" class="user-pill secondary-pill">Logout</a>
        </nav>
    </div>
</header>

<main class="checkout-page-shell">
    <section class="checkout-page-card">
        <div class="checkout-page-grid">
            <div class="checkout-main">
                <article class="checkout-event-card">
                    <div class="checkout-event-image">
                        <?php if (!empty($checkout['immagine'])): ?>
                            <img src="<?php echo esc($checkout['immagine']); ?>" alt="<?php echo esc($checkout['titolo']); ?>">
                        <?php else: ?>
                            <img src="img/evento-default.png" alt="Evento">
                        <?php endif; ?>
                    </div>

                    <div class="checkout-event-content">
                        <span class="checkout-chip">Ordine in corso</span>
                        <h1><?php echo esc($checkout['titolo']); ?></h1>
                        <p><?php echo esc($checkout['luogo_nome'] . ' - ' . $checkout['citta']); ?></p>
                        <p>Settore selezionato: <strong><?php echo esc($checkout['settore_nome']); ?></strong></p>
                        <p>Data evento: <strong><?php echo esc($dataEvento . ' alle ' . $oraEvento); ?></strong></p>
                    </div>
                </article>

                <article class="checkout-seats-card">
                    <div class="checkout-section-header">
                        <h2>Posti selezionati</h2>
                        <span><?php echo (int)$quantita; ?> biglietto/i</span>
                    </div>

                    <div class="checkout-seat-list">
                        <?php foreach ($posti as $posto): ?>
                            <div class="checkout-seat-item">
                                <div>
                                    <strong>Posto P<?php echo (int)$posto; ?></strong>
                                    <span><?php echo esc($checkout['settore_nome']); ?></span>
                                </div>
                                <div class="checkout-seat-price">
                                    € <?php echo number_format($prezzoUnitario, 2, ',', '.'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!$saldoSufficiente): ?>
                        <div class="checkout-alert error">
                            Saldo insufficiente per completare l'acquisto.
                        </div>
                    <?php endif; ?>
                </article>
            </div>

            <aside class="checkout-sidebar">
                <article class="checkout-summary-card">
                    <div class="checkout-timer-box" id="checkout-timer-box">
                        <span>Tempo rimanente</span>
                        <strong id="checkout-timer">05:00</strong>
                    </div>

                    <div class="checkout-summary-line">
                        <span>Prezzo unitario</span>
                        <strong>€ <?php echo number_format($prezzoUnitario, 2, ',', '.'); ?></strong>
                    </div>

                    <div class="checkout-summary-line">
                        <span>Quantità</span>
                        <strong><?php echo (int)$quantita; ?></strong>
                    </div>

                    <div class="checkout-summary-line">
                        <span>Saldo disponibile</span>
                        <strong>€ <?php echo number_format($saldoUtente, 2, ',', '.'); ?></strong>
                    </div>

                    <div class="checkout-summary-divider"></div>

                    <div class="checkout-summary-total">
                        <span>Totale</span>
                        <strong>€ <?php echo number_format($totale, 2, ',', '.'); ?></strong>
                    </div>

                    <form id="checkout-final-form" action="purchase.php" method="post">
                        <input type="hidden" name="id_evento" value="<?php echo (int)$idEvento; ?>">
                        <input type="hidden" name="id_evento_settore" value="<?php echo (int)$idEventoSettore; ?>">
                        <input type="hidden" name="posti" value="<?php echo esc($postiStringa); ?>">

                        <button
                            type="submit"
                            class="checkout-confirm-btn"
                            id="checkout-confirm-btn"
                            <?php echo !$saldoSufficiente ? 'disabled' : ''; ?>
                        >
                            Conferma acquisto
                        </button>
                    </form>

                    <a href="javascript:history.back()" class="checkout-back-btn">Torna indietro</a>
                </article>
            </aside>
        </div>
    </section>
</main>

<script src="js/checkout.js" defer></script>
</body>
</html>