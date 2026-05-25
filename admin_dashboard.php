<?php
require_once 'init.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)($_SESSION['ruolo'] ?? 0) !== 1) {
    header('Location: login.php');
    exit();
}

function salvaImmagine(?array $file): ?string
{
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Errore upload immagine.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new Exception('Formato immagine non consentito.');
    }

    $dir = __DIR__ . '/img/eventi/';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new Exception('Impossibile creare la cartella img/eventi.');
    }

    $nome = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
    $percorsoFisico = $dir . $nome;
    $percorsoDb = 'img/eventi/' . $nome;

    if (!move_uploaded_file($file['tmp_name'], $percorsoFisico)) {
        throw new Exception("Errore durante il salvataggio dell'immagine.");
    }

    return $percorsoDb;
}

function getCategorie(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, nome FROM categoria ORDER BY nome ASC");
    return $stmt->fetchAll();
}

function getLuoghi(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, nome, citta FROM luogo ORDER BY nome ASC");
    return $stmt->fetchAll();
}

function getSettoriByLuogo(PDO $pdo, int $idLuogo): array
{
    $stmt = $pdo->prepare("
        SELECT id, nome, descrizione, prezzo_base, posti_totali
        FROM settore
        WHERE id_luogo = ?
        ORDER BY nome ASC
    ");
    $stmt->execute([$idLuogo]);
    return $stmt->fetchAll();
}

function getEventiAdmin(PDO $pdo): array
{
    $sql = "
        SELECT
            e.id,
            e.titolo,
            e.immagine,
            e.stato,
            c.nome AS categoria,
            l.nome AS luogo,
            l.citta,
            MIN(r.data_ora_inizio) AS data_evento,
            COUNT(r.id) AS numero_repliche
        FROM evento e
        INNER JOIN categoria c ON e.id_categoria = c.id
        INNER JOIN luogo l ON e.id_luogo = l.id
        LEFT JOIN replica_evento r ON r.id_evento = e.id
        GROUP BY e.id, e.titolo, e.immagine, e.stato, c.nome, l.nome, l.citta
        ORDER BY
            CASE WHEN e.stato = 'annullato' THEN 1 ELSE 0 END ASC,
            data_evento ASC,
            e.id DESC
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

$categorie = getCategorie($pdo);
$luoghi = getLuoghi($pdo);

$messaggio = '';
$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    if ($azione === 'aggiungi_evento') {
        $titolo = trim($_POST['titolo'] ?? '');
        $descrizione = trim($_POST['descrizione'] ?? '');
        $id_categoria = (int)($_POST['id_categoria'] ?? 0);
        $id_luogo = (int)($_POST['id_luogo'] ?? 0);
        $data_inizio = trim($_POST['data_inizio'] ?? '');
        $data_fine = trim($_POST['data_fine'] ?? '');
        $orario_spettacolo = trim($_POST['orario_spettacolo'] ?? '');

        try {
            if ($titolo === '' || $id_categoria <= 0 || $id_luogo <= 0 || $data_inizio === '' || $data_fine === '' || $orario_spettacolo === '') {
                throw new Exception('Compila tutti i campi obbligatori.');
            }

            if ($data_fine < $data_inizio) {
                throw new Exception('La data fine non può essere precedente alla data inizio.');
            }

            $settoriLuogo = getSettoriByLuogo($pdo, $id_luogo);
            if (empty($settoriLuogo)) {
                throw new Exception('Il luogo selezionato non ha settori associati.');
            }

            $immagine = salvaImmagine($_FILES['immagine'] ?? null);

            $pdo->beginTransaction();

            $stmtEvento = $pdo->prepare("
                INSERT INTO evento (titolo, descrizione, id_categoria, id_luogo, immagine, stato)
                VALUES (?, ?, ?, ?, ?, 'programmato')
            ");
            $stmtEvento->execute([
                $titolo,
                $descrizione !== '' ? $descrizione : null,
                $id_categoria,
                $id_luogo,
                $immagine
            ]);

            $id_evento = (int)$pdo->lastInsertId();

            $dataStart = new DateTime($data_inizio);
            $dataEnd = new DateTime($data_fine);

            $stmtReplica = $pdo->prepare("
                INSERT INTO replica_evento (id_evento, data_ora_inizio, data_ora_fine, stato)
                VALUES (?, ?, ?, 'programmata')
            ");

            $stmtEventoSettore = $pdo->prepare("
                INSERT INTO evento_settore (
                    id_replica_evento,
                    id_evento,
                    id_settore,
                    prezzo,
                    posti_totali,
                    posti_disponibili
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");

            $current = clone $dataStart;
            while ($current <= $dataEnd) {
                $dataOraInizio = $current->format('Y-m-d') . ' ' . $orario_spettacolo . ':00';

                $stmtReplica->execute([
                    $id_evento,
                    $dataOraInizio,
                    null
                ]);

                $id_replica = (int)$pdo->lastInsertId();

                foreach ($settoriLuogo as $settore) {
                    $prezzo = (float)$settore['prezzo_base'];
                    $postiTotali = (int)$settore['posti_totali'];

                    if ($prezzo <= 0 || $postiTotali <= 0) {
                        continue;
                    }

                    $stmtEventoSettore->execute([
                        $id_replica,
                        $id_evento,
                        (int)$settore['id'],
                        $prezzo,
                        $postiTotali,
                        $postiTotali
                    ]);
                }

                $current->modify('+1 day');
            }

            $pdo->commit();
            $messaggio = 'Evento creato con successo.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errore = $e->getMessage();
        }
    }

    if ($azione === 'elimina_evento') {
    $idEvento = (int)($_POST['id_evento'] ?? 0);

    if ($idEvento <= 0) {
        $errore = 'Evento non valido.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmtEvento = $pdo->prepare("
                UPDATE evento
                SET stato = 'annullato'
                WHERE id = ?
                  AND stato <> 'annullato'
            ");
            $stmtEvento->execute([$idEvento]);

            if ($stmtEvento->rowCount() === 0) {
                throw new Exception('Evento non trovato o già annullato.');
            }

            $stmtRepliche = $pdo->prepare("
                UPDATE replica_evento
                SET stato = 'annullata'
                WHERE id_evento = ?
                  AND stato = 'programmata'
            ");
            $stmtRepliche->execute([$idEvento]);

            $stmtRimborsi = $pdo->prepare("
                SELECT
                    b.id AS id_biglietto,
                    b.id_utente,
                    b.prezzo
                FROM biglietto b
                INNER JOIN evento_settore es ON b.id_evento_settore = es.id
                INNER JOIN replica_evento r ON es.id_replica_evento = r.id
                WHERE es.id_evento = ?
                AND r.stato = 'annullata'
                AND b.stato_rimborso = 'nessuno'
                AND b.disponibilita = 1
            ");
            $stmtRimborsi->execute([$idEvento]);
            $bigliettiDaRimborsare = $stmtRimborsi->fetchAll();

            $stmtAggiornaSaldo = $pdo->prepare("
                UPDATE utente
                SET saldo = saldo + ?
                WHERE id = ?
            ");

            $stmtAggiornaBiglietto = $pdo->prepare("
                UPDATE biglietto
                SET disponibilita = 0,
                    stato_rimborso = 'rimborsato'
                WHERE id = ?
            ");

            $stmtNotifica = $pdo->prepare("
                INSERT INTO notifica (id_utente, titolo, messaggio)
                VALUES (?, ?, ?)
            ");

            foreach ($bigliettiDaRimborsare as $b) {
                $importo = (float)$b['prezzo'];
                $idUtente = (int)$b['id_utente'];
                $idBiglietto = (int)$b['id_biglietto'];

                $stmtAggiornaSaldo->execute([$importo, $idUtente]);
                $stmtAggiornaBiglietto->execute([$idBiglietto]);

                $stmtNotifica->execute([
                    $idUtente,
                    'Rimborso automatico effettuato',
                    'L\'evento annullato ha generato un rimborso automatico di € ' .
                    number_format($importo, 2, ',', '.') .
                    ' sul tuo wallet.'
                ]);
            }

            $pdo->commit();
            $messaggio = 'Evento annullato con successo. Repliche annullate e rimborsi automatici effettuati.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errore = 'Errore durante l’annullamento dell’evento: ' . $e->getMessage();
        }
    }
}
}

$eventi = getEventiAdmin($pdo);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EasyTicket</title>
    <link rel="icon" type="image/x-icon" href="img/icn_sito_sf.png">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand">
            <img src="img/logo_sito.png" alt="Logo EasyTicket">
        </a>
        <nav class="user-nav">
            <a href="admin_dashboard.php" class="user-pill primary-pill">
                <?php echo esc($_SESSION['username'] ?? 'admin'); ?>
            </a>
            <a href="logout.php" class="user-pill secondary-pill">Logout</a>
        </nav>
    </div>
</header>

<main class="page-shell">
    <section class="section-block">
        <div class="section-heading">
            <h2>Crea Nuovo Evento</h2>
            <p>Inserisci i dati dell’evento. Le repliche giornaliere e i settori saranno generati automaticamente in base al luogo selezionato.</p>
        </div>

        <?php if ($messaggio !== ''): ?>
            <div class="admin-card" style="margin-bottom:20px; border-color:#cfe8d4; color:#1f7a3d;">
                <?php echo esc($messaggio); ?>
            </div>
        <?php endif; ?>

        <?php if ($errore !== ''): ?>
            <div class="admin-card" style="margin-bottom:20px; border-color:#f1d1ca; color:#c13d2a;">
                <?php echo esc($errore); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="admin-card">
            <input type="hidden" name="azione" value="aggiungi_evento">

            <div class="admin-grid">
                <div class="admin-form-group">
                    <label>Titolo</label>
                    <input type="text" name="titolo" required>
                </div>

                <div class="admin-form-group">
                    <label>Categoria</label>
                    <select name="id_categoria" required>
                        <option value="">Seleziona...</option>
                        <?php foreach ($categorie as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>">
                                <?php echo esc($c['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label>Luogo</label>
                    <select name="id_luogo" required>
                        <option value="">Seleziona...</option>
                        <?php foreach ($luoghi as $l): ?>
                            <option value="<?php echo (int)$l['id']; ?>">
                                <?php echo esc($l['nome'] . ' - ' . $l['citta']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label>Immagine copertina</label>
                    <input type="file" name="immagine" accept=".jpg,.jpeg,.png,.webp">
                </div>
            </div>

            <div class="admin-form-group">
                <label>Descrizione</label>
                <textarea name="descrizione" rows="3" placeholder="Descrizione evento..."></textarea>
            </div>

            <div class="range-grid">
                <div class="admin-form-group">
                    <label>Data inizio</label>
                    <input type="date" name="data_inizio" required>
                </div>

                <div class="admin-form-group">
                    <label>Data fine</label>
                    <input type="date" name="data_fine" required>
                </div>

                <div class="admin-form-group">
                    <label>Orario spettacolo</label>
                    <input type="time" name="orario_spettacolo" required>
                </div>
            </div>

            <div class="dashboard-actions dashboard-actions-spaced">
                <button type="submit" class="admin-submit">Crea evento</button>
            </div>
        </form>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Eventi Esistenti</h2>
        </div>

        <?php if (empty($eventi)): ?>
            <div class="empty-state">
                <h3>Nessun evento presente</h3>
            </div>
        <?php else: ?>
            <div class="admin-card table-wrap">
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>Img</th>
                        <th>Titolo</th>
                        <th>Categoria</th>
                        <th>Luogo</th>
                        <th>Prima data</th>
                        <th>Repliche</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($eventi as $ev): ?>
                        <tr>
                            <td>
                                <img
                                    class="thumb-evento"
                                    src="<?php echo !empty($ev['immagine']) ? esc($ev['immagine']) : 'img/evento-default.png'; ?>"
                                    alt=""
                                >
                            </td>
                            <td><strong><?php echo esc($ev['titolo']); ?></strong></td>
                            <td><?php echo esc($ev['categoria']); ?></td>
                            <td><?php echo esc($ev['luogo'] . ' - ' . $ev['citta']); ?></td>
                            <td>
                                <?php echo !empty($ev['data_evento']) ? date('d/m/Y H:i', strtotime($ev['data_evento'])) : 'N/D'; ?>
                            </td>
                            <td><?php echo (int)$ev['numero_repliche']; ?></td>
                            <td><?php echo esc($ev['stato']); ?></td>
                            <td>
                                <?php if (($ev['stato'] ?? '') !== 'annullato'): ?>
                                    <div class="dashboard-actions">
                                        <a href="evento.php?id=<?php echo (int)$ev['id']; ?>" class="hero-cta" style="text-decoration:none">
                                            Apri
                                        </a>

                                        <a href="modifica_evento.php?id=<?php echo (int)$ev['id']; ?>" class="secondary-btn" style="text-decoration:none;display:inline-flex;align-items:center;">
                                            Modifica
                                        </a>

                                        <form method="post" onsubmit="return confirm('Vuoi davvero annullare questo evento e tutte le sue repliche?')" style="display:inline">
                                            <input type="hidden" name="azione" value="elimina_evento">
                                            <input type="hidden" name="id_evento" value="<?php echo (int)$ev['id']; ?>">
                                            <button type="submit" class="row-remove-btn">Annulla evento</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="site-footer">
    <p>&copy; 2026 EasyTicket</p>
</footer>
</body>
</html>
