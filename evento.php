<?php
require_once 'init.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

function getUserData(PDO $pdo, string $username): ?array {
    $stmt = $pdo->prepare('SELECT id, username, saldo FROM utente WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function getEventoDettaglio(PDO $pdo, int $id_evento): ?array {
    $sql = "SELECT e.id, e.titolo, e.descrizione, e.immagine, e.stato,
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
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_evento]);
    $evento = $stmt->fetch();
    return $evento ?: null;
}

function getReplicheEvento(PDO $pdo, int $id_evento): array {
    $sql = 'SELECT id, data_ora_inizio, data_ora_fine, stato FROM replica_evento WHERE id_evento = ? ORDER BY data_ora_inizio ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_evento]);
    return $stmt->fetchAll();
}

function getSettoriReplica(PDO $pdo, int $id_replica): array {
    $sql = "SELECT es.id, es.id_evento, es.id_replica_evento, es.id_settore,
                   es.prezzo, es.posti_totali, es.posti_disponibili,
                   s.nome AS nome_settore
            FROM evento_settore es
            JOIN settore s ON es.id_settore = s.id
            WHERE es.id_replica_evento = ?
            ORDER BY es.prezzo ASC, s.nome ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_replica]);
    return $stmt->fetchAll();
}

function getPostiOccupati(PDO $pdo, int $id_evento_settore): array {
    $sql = 'SELECT posto FROM biglietto WHERE id_evento_settore = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_evento_settore]);
    $posti = [];
    foreach ($stmt->fetchAll() as $row) {
        $posti[] = (int)$row['posto'];
    }
    return $posti;
}

function formatDataReplica(?string $datetime): string {
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

$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['azione'] ?? '') === 'acquista') {
    if ($ruolo !== 2) {
        $errore = 'Solo gli utenti cliente possono acquistare biglietti.';
    } else {
        $id_evento_settore = (int)($_POST['id_evento_settore'] ?? 0);
        $postiSelezionati = $_POST['posti'] ?? [];

        if (!is_array($postiSelezionati)) {
            $postiSelezionati = [];
        }

        $postiSelezionati = array_values(array_unique(array_map('intval', $postiSelezionati)));
        $postiSelezionati = array_values(array_filter($postiSelezionati, fn($p) => $p > 0));

        if ($id_evento_settore <= 0 || empty($postiSelezionati)) {
            $errore = 'Seleziona almeno un posto valido.';
        } else {
            try {
                $pdo->beginTransaction();

                $sqlSettore = "SELECT es.id, es.prezzo, es.posti_disponibili, es.posti_totali, s.nome AS nome_settore
                               FROM evento_settore es
                               JOIN settore s ON es.id_settore = s.id
                               WHERE es.id = ?
                               LIMIT 1 FOR UPDATE";
                $stmt = $pdo->prepare($sqlSettore);
                $stmt->execute([$id_evento_settore]);
                $settoreAcquisto = $stmt->fetch();

                if (!$settoreAcquisto) {
                    throw new Exception('Settore non trovato.');
                }

                $quantita = count($postiSelezionati);

                if ((int)$settoreAcquisto['posti_disponibili'] < $quantita) {
                    throw new Exception('Non ci sono abbastanza posti disponibili per questa selezione.');
                }

                $stmt = $pdo->prepare('SELECT posto FROM biglietto WHERE id_evento_settore = ?');
                $stmt->execute([$id_evento_settore]);
                $occupati = [];
                foreach ($stmt->fetchAll() as $row) {
                    $occupati[] = (int)$row['posto'];
                }

                foreach ($postiSelezionati as $posto) {
                    if ($posto > (int)$settoreAcquisto['posti_totali']) {
                        throw new Exception('Uno dei posti selezionati non è valido.');
                    }
                    if (in_array($posto, $occupati, true)) {
                        throw new Exception('Uno dei posti selezionati è già stato prenotato.');
                    }
                }

                $prezzoUnitario = (float)$settoreAcquisto['prezzo'];
                $prezzoTotale = $prezzoUnitario * $quantita;

                $stmt = $pdo->prepare('SELECT saldo FROM utente WHERE id = ? FOR UPDATE');
                $stmt->execute([$user['id']]);
                $saldoCorrente = (float)(($stmt->fetch()['saldo']) ?? 0);

                if ($saldoCorrente < $prezzoTotale) {
                    $pdo->rollBack();
                    header('Location: User_dashboard.php');
                    exit();
                }

                $stmt = $pdo->prepare('UPDATE utente SET saldo = saldo - ? WHERE id = ?');
                $stmt->execute([$prezzoTotale, $user['id']]);

                $stmt = $pdo->prepare('UPDATE evento_settore SET posti_disponibili = posti_disponibili - ? WHERE id = ?');
                $stmt->execute([$quantita, $id_evento_settore]);

                $disponibilita = 1;
                $ticketCreati = [];
                $stmt = $pdo->prepare('INSERT INTO biglietto (sigillo_fiscale, disponibilita, id_utente, id_evento_settore, posto, prezzo) VALUES (?, ?, ?, ?, ?, ?)');

                foreach ($postiSelezionati as $posto) {
                    $sigilloFiscale = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 15);
                    $stmt->execute([$sigilloFiscale, $disponibilita, $user['id'], $id_evento_settore, $posto, $prezzoUnitario]);
                    $ticketCreati[] = [
                        'sigillo_fiscale' => $sigilloFiscale,
                        'posto' => $posto,
                        'prezzo' => $prezzoUnitario,
                    ];
                }

                $_SESSION['ticket_info'] = [
                    'settore' => $settoreAcquisto['nome_settore'],
                    'evento' => $evento['titolo'],
                    'quantita' => $quantita,
                    'totale' => $prezzoTotale,
                    'biglietti' => $ticketCreati,
                ];

                $pdo->commit();
                header('Location: confirmation.php');
                exit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errore = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($evento['titolo']); ?> - EasyTicket</title>
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
                <?php if (isset($_SESSION['ruolo']) && (int)$_SESSION['ruolo'] === 1): ?>
                    <a href="admin_dashboard.php" class="user-pill primary-pill">admin</a>
                <?php else: ?>
                    <a href="User_dashboard.php" class="user-pill primary-pill"><?php echo htmlspecialchars($username); ?></a>
                <?php endif; ?>
                <a href="logout.php" class="user-pill secondary-pill">Logout</a>
            </nav>
        </div>
    </header>

    <main class="page-shell">
        <section class="section-block">
            <div class="section-heading">
                <h2><?php echo htmlspecialchars($evento['titolo']); ?></h2>
                <p>
                    <?php echo htmlspecialchars($evento['categoria']); ?>
                    •
                    <?php echo htmlspecialchars($evento['luogo']); ?>
                    <?php if (!empty($evento['citta'])): ?>
                        - <?php echo htmlspecialchars($evento['citta']); ?>
                    <?php endif; ?>
                </p>
            </div>

            <div class="admin-grid" style="margin-top: 24px;">
                <div class="admin-preview-card">
                    <div class="admin-preview-image" style="height: 300px;">
                        <?php if (!empty($evento['immagine'])): ?>
                            <img src="<?php echo htmlspecialchars($evento['immagine']); ?>" alt="<?php echo htmlspecialchars($evento['titolo']); ?>">
                        <?php else: ?>
                            <img src="img/evento-default.png" alt="Evento">
                        <?php endif; ?>
                    </div>

                    <div class="admin-preview-body">
                        <div class="admin-preview-top">
                            <span class="admin-preview-badge"><?php echo htmlspecialchars($evento['categoria']); ?></span>
                            <span class="admin-preview-date"><?php echo count($repliche); ?> repliche</span>
                        </div>

                        <h4><?php echo htmlspecialchars($evento['titolo']); ?></h4>
                        <p><?php echo nl2br(htmlspecialchars($evento['descrizione'] ?? 'Nessuna descrizione disponibile.')); ?></p>
                    </div>
                </div>

                <div class="admin-card">
                    <h3>Dettagli utente</h3>

                    <div class="admin-form-group">
                        <label>Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                    </div>

                    <div class="admin-form-group">
                        <label>Saldo disponibile</label>
                        <input type="text" value="€ <?php echo number_format((float)$user['saldo'], 2, ',', '.'); ?>" readonly>
                    </div>

                    <div class="admin-form-group">
                        <label>Luogo evento</label>
                        <input type="text" value="<?php echo htmlspecialchars($evento['luogo'] . (!empty($evento['citta']) ? ' - ' . $evento['citta'] : '')); ?>" readonly>
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
                                <strong><?php echo formatDataReplica($replica['data_ora_inizio']); ?></strong>
                                <span>
                                    <?php if (!empty($replica['data_ora_fine'])): ?>
                                        • Fine <?php echo formatDataReplica($replica['data_ora_fine']); ?>
                                    <?php else: ?>
                                        • Stato <?php echo htmlspecialchars($replica['stato']); ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div>
                                <button
                                        type="button"
                                        class="hero-cta replica-button"
                                        onclick="window.location.href='evento.php?id=<?php echo $id_evento; ?>&replica=<?php echo (int)$replica['id']; ?>#sector-list'">
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
                <?php echo $replicaSelezionata ? htmlspecialchars(formatDataReplica($replicaSelezionata['data_ora_inizio'])) : 'Nessuna replica selezionata'; ?>
            </span>
        </p>
    </div>

    <div class="matches-grid" id="sector-list">
        <?php if (!empty($settori)): ?>
            <?php foreach ($settori as $settore): ?>
                <div class="match-card">
                    <div class="match-card-top">
                        <span class="match-badge"><?php echo htmlspecialchars($settore['nome_settore']); ?></span>
                        <span class="match-date">€ <?php echo number_format((float)$settore['prezzo'], 2, ',', '.'); ?></span>
                    </div>

                    <div class="match-details" style="padding-top: 18px;">
                        <h3><?php echo htmlspecialchars($settore['nome_settore']); ?></h3>
                        <p>Posti disponibili: <?php echo (int)$settore['posti_disponibili']; ?> / <?php echo (int)$settore['posti_totali']; ?></p>
                    </div>

                    <div class="match-card-bottom">
                        <button
                            type="button"
                            class="match-action sector-button"
                            onclick="window.location.href='evento.php?id=<?php echo $id_evento; ?>&replica=<?php echo $id_replica; ?>&settore=<?php echo (int)$settore['id']; ?>#ticket-app'">
                            Scegli questo settore
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3>Nessun settore selezionato</h3>
                <p>Scegli prima una replica per vedere i settori disponibili.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

        <?php if ($selectedSettore): ?>
            <section class="section-block">
                <div class="section-heading">
                    <h2>Completa acquisto</h2>
                    <p>
                        Settore selezionato: <?php echo htmlspecialchars($selectedSettore['nome_settore']); ?>
                        · Prezzo per biglietto: € <?php echo number_format((float)$selectedSettore['prezzo'], 2, ',', '.'); ?>
                    </p>
                </div>

                <?php if (!empty($errore)): ?>
                    <div class="admin-card" style="border-color:#f1d1ca; color:#c13d2a; margin-bottom:20px;">
                        <?php echo htmlspecialchars($errore); ?>
                    </div>
                <?php endif; ?>

                <?php if ((int)$selectedSettore['posti_disponibili'] <= 0): ?>
                    <div class="empty-state">
                        <h3>Posti finiti</h3>
                        <p>Non ci sono più posti disponibili per questo settore.</p>
                    </div>
                <?php else: ?>
                    <form action="checkout.php" method="post" class="admin-card" id="ticket-app">
    <input type="hidden" name="id_evento" value="<?php echo (int)$evento['id']; ?>">
    <input type="hidden" name="id_evento_settore" value="<?php echo (int)$selectedSettore['id']; ?>">
    <input type="hidden" name="prezzo" value="<?php echo (float)$selectedSettore['prezzo']; ?>">

    <div class="admin-form-group">
        <label>Seleziona i posti</label>
        <div class="seat-grid">
            <?php for ($i = 1; $i <= (int)$selectedSettore['posti_totali']; $i++): ?>
                <?php $occupato = in_array($i, $postiOccupati, true); ?>
                <?php if ($occupato): ?>
                    <span class="seat-pill seat-occupied">P<?php echo $i; ?></span>
                <?php else: ?>
                    <label class="seat-pill seat-available">
                        <input type="checkbox" name="posti[]" value="<?php echo $i; ?>" v-model="postiSelezionati">
                        <span>P<?php echo $i; ?></span>
                    </label>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <small class="seat-legend">Grigio = disponibile · Arancione = selezionato · Rosso = occupato</small>
    </div>

    <div class="admin-form-group">
        <label>Prezzo per biglietto</label>
        <input type="text" value="€ <?php echo number_format((float)$selectedSettore['prezzo'], 2, ',', '.'); ?>" readonly>
    </div>

    <div class="admin-form-group" v-if="postiSelezionati.length > 0">
        <label style="color: #f39a05;">Totale Preventivo ({{ postiSelezionati.length }} biglietti)</label>
        <input 
            type="text"
            :value="'€ ' + (postiSelezionati.length * <?php echo (float)$selectedSettore['prezzo']; ?>).toFixed(2)"
            readonly
            style="background: #fff8eb; border: 2px solid #f39a05; font-weight: bold; font-size: 1.1em; color: #13293d;"
        >
    </div>

    <div class="admin-form-group">
        <label>Posti disponibili</label>
        <input type="text" value="<?php echo (int)$selectedSettore['posti_disponibili']; ?>" readonly>
    </div>

    <button type="submit" class="admin-submit">Vai al checkout</button>
</form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 EasyTicket</p>
    </footer>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const startVue = () => {
                const ticketApp = document.getElementById('ticket-app');
                // Se il form esiste nel DOM e Vue non è ancora stato montato
                if (ticketApp && !ticketApp.hasAttribute('data-v-app')) {
                    Vue.createApp({
                        data() {
                            return {
                                postiSelezionati: [] 
                            }
                        }
                    }).mount('#ticket-app');
                }
            };

            // Tenta l'avvio immediato al caricamento
            startVue();

            // MutationObserver: Spia il DOM e avvia Vue dinamicamente appena il form viene generato
            const observer = new MutationObserver(startVue);
            observer.observe(document.body, { childList: true, subtree: true });
        });
    </script>
    
    
</body>
</html>
