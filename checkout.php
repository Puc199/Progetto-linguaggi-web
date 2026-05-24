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

$id_evento = (int)($_POST['id_evento'] ?? 0);
$id_evento_settore = (int)($_POST['id_evento_settore'] ?? 0);
$prezzo_post = (float)($_POST['prezzo'] ?? 0);

$posti_input = $_POST['posti'] ?? '';

if (is_array($posti_input)) {
    $posti = array_filter(array_map('intval', $posti_input), fn($p) => $p > 0);
    $posti = array_values(array_unique($posti));
    $posti_raw = implode(',', $posti);
} else {
    $posti_raw = trim((string)$posti_input);
    $posti = array_filter(array_map('intval', explode(',', $posti_raw)), fn($p) => $p > 0);
    $posti = array_values(array_unique($posti));
}

if ($id_evento <= 0 || $id_evento_settore <= 0 || empty($posti)) {
    header("Location: home.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT 
        e.id,
        e.titolo,
        e.immagine,
        l.nome AS luogo_nome,
        l.citta,
        s.nome AS settore_nome,
        es.prezzo,
        es.posti_disponibili,
        es.posti_totali,
        r.data_ora_inizio
    FROM evento_settore es
    INNER JOIN evento e ON es.id_evento = e.id
    INNER JOIN settore s ON es.id_settore = s.id
    INNER JOIN replica_evento r ON es.id_replica_evento = r.id
    INNER JOIN luogo l ON e.id_luogo = l.id
    WHERE es.id = ? AND e.id = ?
    LIMIT 1
");
$stmt->execute([$id_evento_settore, $id_evento]);
$ordine = $stmt->fetch();

if (!$ordine) {
    die("Ordine non valido.");
}

$prezzo_unitario = (float)$ordine['prezzo'];
$totale = $prezzo_unitario * count($posti);

$stmtSaldo = $pdo->prepare("SELECT saldo FROM utente WHERE id = ?");
$stmtSaldo->execute([$_SESSION['user_id']]);
$utente = $stmtSaldo->fetch();

if (!$utente) {
    die("Utente non trovato.");
}

$saldo = (float)$utente['saldo'];
$saldo_sufficiente = $saldo >= $totale;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - EasyTicket</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/checkout.css">
</head>
<body>
    <main class="checkout-page-shell">
        <section class="checkout-page-card">
            <div class="checkout-page-grid">

                <div class="checkout-main">
                    <div class="checkout-event-card">
                        <div class="checkout-event-image">
                            <img src="<?php echo esc($ordine['immagine'] ?: 'img/evento-default.png'); ?>" alt="<?php echo esc($ordine['titolo']); ?>">
                        </div>

                        <div class="checkout-event-content">
                            <span class="checkout-chip">Riepilogo ordine</span>
                            <h1><?php echo esc($ordine['titolo']); ?></h1>
                            <p>
                                <?php echo esc($ordine['luogo_nome']); ?> ·
                                <?php echo esc($ordine['citta']); ?> ·
                                <?php echo esc($ordine['data_ora_inizio']); ?>
                            </p>
                            <p>
                                Settore selezionato: <strong><?php echo esc($ordine['settore_nome']); ?></strong>
                            </p>
                        </div>
                    </div>

                    <div class="checkout-seats-card">
                        <div class="checkout-section-header">
                            <h2>Posti selezionati</h2>
                            <span><?php echo count($posti); ?> bigliett<?php echo count($posti) === 1 ? 'o' : 'i'; ?></span>
                        </div>

                        <div class="checkout-seat-list">
                            <?php foreach ($posti as $posto): ?>
                                <div class="checkout-seat-item">
                                    <div>
                                        <strong>Posto P<?php echo (int)$posto; ?></strong>
                                        <span><?php echo esc($ordine['settore_nome']); ?></span>
                                    </div>
                                    <div class="checkout-seat-price">
                                        € <?php echo number_format($prezzo_unitario, 2, ',', '.'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <aside class="checkout-sidebar">
                    <div class="checkout-summary-card">
                        <div class="checkout-timer-box" id="checkout-timer-box">
                            <span>Tempo rimasto</span>
                            <strong id="checkout-timer">05:00</strong>
                        </div>

                        <div class="checkout-summary-line">
                            <span>Settore</span>
                            <strong><?php echo esc($ordine['settore_nome']); ?></strong>
                        </div>

                        <div class="checkout-summary-line">
                            <span>Prezzo unitario</span>
                            <strong>€ <?php echo number_format($prezzo_unitario, 2, ',', '.'); ?></strong>
                        </div>

                        <div class="checkout-summary-line">
                            <span>Quantità</span>
                            <strong><?php echo count($posti); ?></strong>
                        </div>

                        <div class="checkout-summary-line">
                            <span>Saldo wallet</span>
                            <strong>€ <?php echo number_format($saldo, 2, ',', '.'); ?></strong>
                        </div>

                        <div class="checkout-summary-divider"></div>

                        <div class="checkout-summary-total">
                            <span>Totale</span>
                            <strong>€ <?php echo number_format($totale, 2, ',', '.'); ?></strong>
                        </div>

                        <?php if (!$saldo_sufficiente): ?>
                            <div class="checkout-alert error">
                                Saldo insufficiente per completare l'acquisto.
                            </div>
                        <?php endif; ?>

                        <form action="purchase.php" method="post" id="checkout-final-form">
                            <input type="hidden" name="id_evento" value="<?php echo (int)$id_evento; ?>">
                            <input type="hidden" name="id_evento_settore" value="<?php echo (int)$id_evento_settore; ?>">
                            <input type="hidden" name="prezzo" value="<?php echo htmlspecialchars((string)$prezzo_unitario, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="posti" value="<?php echo esc(implode(',', $posti)); ?>">

                            <button 
                                type="submit" 
                                class="checkout-confirm-btn"
                                id="checkout-confirm-btn"
                                <?php echo !$saldo_sufficiente ? 'disabled' : ''; ?>
                            >
                                Conferma acquisto
                            </button>
                        </form>

                        <a href="javascript:history.back()" class="checkout-back-btn">Torna indietro</a>
                    </div>
                </aside>

            </div>
        </section>
    </main>

    <script>
        let secondsLeft = 300;
        const timerEl = document.getElementById('checkout-timer');
        const timerBox = document.getElementById('checkout-timer-box');
        const confirmBtn = document.getElementById('checkout-confirm-btn');

        function updateTimer() {
            const min = Math.floor(secondsLeft / 60);
            const sec = secondsLeft % 60;
            timerEl.textContent = String(min).padStart(2, '0') + ':' + String(sec).padStart(2, '0');

            if (secondsLeft <= 0) {
                clearInterval(timerInterval);
                timerBox.classList.add('expired');
                if (confirmBtn) {
                    confirmBtn.disabled = true;
                    confirmBtn.textContent = 'Tempo scaduto';
                }
                return;
            }

            secondsLeft--;
        }

        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);
    </script>
</body>
</html>
