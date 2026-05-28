<?php
require_once 'init.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'] ?? 'Utente';
$ruolo = (int)($_SESSION['ruolo'] ?? 0);

$id_evento = (int)($_GET['id'] ?? 0);
if ($id_evento <= 0) {
    die('Evento non valido.');
}

function getUserData(PDO $pdo, string $username): ?array
{
    $stmt = $pdo->prepare('SELECT id, username, saldo FROM utente WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function getEventoDettaglio(PDO $pdo, int $id_evento): ?array
{
    $sql = "
        SELECT 
            e.id,
            e.titolo,
            e.descrizione,
            e.immagine,
            e.stato,
            c.nome AS categoria,
            l.nome AS luogo,
            l.citta,
            MIN(r.data_ora_inizio) AS data_evento
        FROM evento e
        JOIN categoria c ON e.id_categoria = c.id
        JOIN luogo l ON e.id_luogo = l.id
        LEFT JOIN replica_evento r ON r.id_evento = e.id
        WHERE e.id = ?
        GROUP BY e.id, e.titolo, e.descrizione, e.immagine, e.stato, c.nome, l.nome, l.citta
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_evento]);
    $evento = $stmt->fetch();
    return $evento ?: null;
}

function getReplicheEvento(PDO $pdo, int $id_evento): array
{
    $sql = "
        SELECT id, data_ora_inizio, data_ora_fine, stato
        FROM replica_evento
        WHERE id_evento = ?
          AND stato <> 'annullata'
        ORDER BY data_ora_inizio ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_evento]);
    return $stmt->fetchAll();
}

function getSettoriReplica(PDO $pdo, int $id_replica): array
{
    $sql = "
        SELECT 
            es.id,
            es.id_evento,
            es.id_replica_evento,
            es.id_settore,
            es.prezzo,
            es.posti_totali,
            es.posti_disponibili,
            s.nome AS nome_settore
        FROM evento_settore es
        JOIN settore s ON es.id_settore = s.id
        WHERE es.id_replica_evento = ?
        ORDER BY es.prezzo ASC, s.nome ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_replica]);
    return $stmt->fetchAll();
}

function getPostiOccupati(PDO $pdo, int $id_evento_settore): array
{
    $sql = 'SELECT posto FROM biglietto WHERE id_evento_settore = ? ORDER BY posto ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_evento_settore]);

    $posti = [];
    foreach ($stmt->fetchAll() as $row) {
        $posti[] = (int)$row['posto'];
    }

    return $posti;
}

function formatDataReplica(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }

    return date('d/m/Y H:i', strtotime($datetime));
}

$user = getUserData($pdo, $username);
if (!$user) {
    die('Utente non trovato.');
}

$evento = getEventoDettaglio($pdo, $id_evento);
if (!$evento) {
    die('Evento non trovato.');
}

if (($evento['stato'] ?? '') === 'annullato') {
    die('Questo evento è stato annullato.');
}

$repliche = getReplicheEvento($pdo, $id_evento);

$id_replica = (int)($_GET['replica'] ?? 0);
if ($id_replica === 0 && !empty($repliche)) {
    $id_replica = (int)$repliche[0]['id'];
}

$replicaSelezionata = null;
foreach ($repliche as $replica) {
    if ((int)$replica['id'] === $id_replica) {
        $replicaSelezionata = $replica;
        break;
    }
}

$settori = [];
if ($id_replica > 0) {
    $settori = getSettoriReplica($pdo, $id_replica);
}

$selected_settore_id = (int)($_GET['settore'] ?? 0);
$selectedSettore = null;
$postiOccupati = [];

foreach ($settori as $settore) {
    if ((int)$settore['id'] === $selected_settore_id) {
        $selectedSettore = $settore;
        break;
    }
}

if ($selectedSettore) {
    $postiOccupati = getPostiOccupati($pdo, $selected_settore_id);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($evento['titolo']); ?> - EasyTicket</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/public.css">
    <link rel="stylesheet" href="css/style1.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand">
            <img src="img/logo_sito.png" alt="Logo EasyTicket">
        </a>
        <nav class="user-nav">
            <?php if ($ruolo === 1): ?>
                <a href="admin_dashboard.php" class="user-pill primary-pill">Admin</a>
            <?php else: ?>
                <a href="User_dashboard.php" class="user-pill primary-pill"><?php echo esc($username); ?></a>
            <?php endif; ?>
            <a href="logout.php" class="user-pill secondary-pill">Logout</a>
        </nav>
    </div>
</header>

<main class="page-shell">
    <section class="section-block">
        <div class="section-heading">
            <h2><?php echo esc($evento['titolo']); ?></h2>
            <p>
                <?php echo esc($evento['categoria']); ?>
                ·
                <?php echo esc($evento['luogo']); ?>
                <?php if (!empty($evento['citta'])): ?>
                    - <?php echo esc($evento['citta']); ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="admin-grid" style="margin-top: 24px;">
            <div class="admin-preview-card">
                <div class="admin-preview-image" style="height: 300px;">
                    <?php if (!empty($evento['immagine'])): ?>
                        <img src="<?php echo esc($evento['immagine']); ?>" alt="<?php echo esc($evento['titolo']); ?>">
                    <?php else: ?>
                        <img src="img/evento-default.png" alt="Evento">
                    <?php endif; ?>
                </div>

                <div class="admin-preview-body">
                    <div class="admin-preview-top">
                        <span class="admin-preview-badge"><?php echo esc($evento['categoria']); ?></span>
                        <span class="admin-preview-date"><?php echo count($repliche); ?> repliche</span>
                    </div>
                    <h4><?php echo esc($evento['titolo']); ?></h4>
                    <p><?php echo nl2br(esc($evento['descrizione'] ?? 'Nessuna descrizione disponibile.')); ?></p>
                </div>
            </div>

            <div class="admin-card">
                <h3>Dettagli utente</h3>

                <div class="admin-form-group">
                    <label>Username</label>
                    <input type="text" value="<?php echo esc($user['username']); ?>" readonly>
                </div>

                <div class="admin-form-group">
                    <label>Saldo disponibile</label>
                    <input type="text" value="€ <?php echo number_format((float)$user['saldo'], 2, ',', '.'); ?>" readonly>
                </div>

                <div class="admin-form-group">
                    <label>Luogo evento</label>
                    <input
                        type="text"
                        value="<?php echo esc($evento['luogo'] . (!empty($evento['citta']) ? ' - ' . $evento['citta'] : '')); ?>"
                        readonly
                    >
                </div>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Scegli la replica</h2>
            <p>Seleziona giorno e orario dello spettacolo che preferisci.</p>
        </div>

        <?php if (empty($repliche)): ?>
            <div class="empty-state">
                <h3>Nessuna replica disponibile</h3>
                <p>Questo evento non ha ancora date prenotabili.</p>
            </div>
        <?php else: ?>
            <div class="admin-list">
                <?php foreach ($repliche as $replica): ?>
                <div class="admin-list-item">
                    <div>
                        <strong><?php echo esc(formatDataReplica($replica['data_ora_inizio'])); ?></strong>
                        <span>
                            <?php if (!empty($replica['data_ora_fine'])): ?>
                                · Fine <?php echo esc(formatDataReplica($replica['data_ora_fine'])); ?>
                            <?php else: ?>
                                · Stato <?php echo esc($replica['stato']); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div>
                        <button type="button" class="hero-cta replica-button"
                            data-replica-id="<?php echo (int)$replica['id']; ?>"
                            data-replica-label="<?php echo esc(formatDataReplica($replica['data_ora_inizio'])); ?>">
                            Seleziona
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Scegli il settore</h2>
            <p>
                Replica selezionata:
                <span id="replica-riepilogo">
                    <?php echo $replicaSelezionata ? esc(formatDataReplica($replicaSelezionata['data_ora_inizio'])) : 'Nessuna replica selezionata'; ?>
                </span>
            </p>
        </div>

        <div class="event-grid" id="sector-list">
    <div class="empty-state">
        <h3>Seleziona una replica</h3>
        <p>I settori disponibili appariranno qui.</p>
    </div>
</div>
    </section>

    <section class="section-block" id="purchase-section" style="display:none;">
    <div class="section-heading">
        <h2>Completa acquisto</h2>
        <p>
            Settore: <span id="purchase-settore"></span>
            · Prezzo: <span id="purchase-prezzo"></span>
        </p>
    </div>

    <form action="checkout.php" method="post" class="admin-card" id="purchase-form">
        <input type="hidden" name="id_evento" value="<?php echo (int)$evento['id']; ?>">
        <input type="hidden" name="id_evento_settore" id="selected-evento-settore" value="0">
        <input type="hidden" name="posti" id="purchase-posti-hidden">

        <div class="admin-form-group">
            <label>Seleziona i posti</label>
            <div class="seat-grid" id="seat-grid"></div>
            <small class="seat-legend">Grigio = disponibile · Arancione = selezionato · Rosso = occupato</small>
        </div>

        <div class="admin-form-group">
            <label>Totale preventivo</label>
            <input type="text" id="purchase-prezzo-input" value="€ 0,00" readonly>
        </div>

        <div class="admin-form-group">
            <label>Posti selezionati</label>
            <input type="text" id="purchase-posti-display" value="Nessuno" readonly>
        </div>

        <button type="submit" class="admin-submit">Vai al checkout</button>
    </form>
</section>
</main>

<footer class="site-footer">
    <p>&copy; 2026 EasyTicket</p>
</footer>
<script src="js/evento.js"></script>
</body>
</html>