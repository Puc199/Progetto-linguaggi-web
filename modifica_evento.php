<?php
require_once 'init.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (int)($_SESSION['ruolo'] ?? 0) !== 1) {
    header("Location: login.php");
    exit();
}

function salvaImmagine(?array $file): ?string {
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Errore upload immagine.");
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new Exception("Formato immagine non consentito.");
    }

    $dir = __DIR__ . '/img/eventi/';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new Exception("Impossibile creare la cartella img/eventi.");
    }

    $nome = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
    $percorsoFisico = $dir . $nome;
    $percorsoDb = 'img/eventi/' . $nome;

    if (!move_uploaded_file($file['tmp_name'], $percorsoFisico)) {
        throw new Exception("Errore durante il salvataggio dell'immagine.");
    }

    return $percorsoDb;
}

$id_evento = (int)($_GET['id'] ?? 0);
if ($id_evento <= 0) {
    die("Evento non valido.");
}

$messaggio = '';
$errore = '';

try {
    $stmt = $pdo->prepare("SELECT * FROM evento WHERE id = ? LIMIT 1");
    $stmt->execute([$id_evento]);
    $evento = $stmt->fetch();

    if (!$evento) {
        die("Evento non trovato.");
    }
} catch (Throwable $e) {
    die("Errore nel caricamento dell'evento.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    if ($azione === 'modifica_evento') {
        $titolo = trim($_POST['titolo'] ?? '');
        $descrizione = trim($_POST['descrizione'] ?? '');
        $id_categoria = (int)($_POST['id_categoria'] ?? 0);
        $id_luogo = (int)($_POST['id_luogo'] ?? 0);

        try {
            if ($titolo === '' || $id_categoria <= 0 || $id_luogo <= 0) {
                throw new Exception("Compila tutti i campi obbligatori dell'evento.");
            }

            $immagine = $evento['immagine'] ?? null;
            $nuovaImmagine = salvaImmagine($_FILES['immagine'] ?? null);

            if ($nuovaImmagine !== null) {
                if (!empty($immagine)) {
                    $vecchioPath = __DIR__ . '/' . ltrim($immagine, '/');
                    if (is_file($vecchioPath)) {
                        @unlink($vecchioPath);
                    }
                }
                $immagine = $nuovaImmagine;
            }

            $stmt = $pdo->prepare("
                UPDATE evento
                SET titolo = ?, descrizione = ?, id_categoria = ?, id_luogo = ?, immagine = ?
                WHERE id = ?
            ");
            $stmt->execute([$titolo, $descrizione, $id_categoria, $id_luogo, $immagine, $id_evento]);

            $stmt = $pdo->prepare("SELECT * FROM evento WHERE id = ? LIMIT 1");
            $stmt->execute([$id_evento]);
            $evento = $stmt->fetch();

            $messaggio = "Evento aggiornato con successo.";
        } catch (Throwable $e) {
            $errore = $e->getMessage();
        }
    }

    if ($azione === 'aggiungi_replica') {
        $data_ora_inizio = trim($_POST['data_ora_inizio'] ?? '');
        $data_ora_fine = trim($_POST['data_ora_fine'] ?? '');

        try {
            if ($data_ora_inizio === '') {
                throw new Exception("Inserisci almeno data e ora di inizio per la replica.");
            }

            $data_ora_inizio_sql = str_replace('T', ' ', $data_ora_inizio) . ':00';
            $data_ora_fine_sql = ($data_ora_fine !== '') ? str_replace('T', ' ', $data_ora_fine) . ':00' : null;

            $stmt = $pdo->prepare("
                INSERT INTO replica_evento (id_evento, data_ora_inizio, data_ora_fine, stato)
                VALUES (?, ?, ?, 'programmata')
            ");
            $stmt->execute([$id_evento, $data_ora_inizio_sql, $data_ora_fine_sql]);

            $messaggio = "Replica aggiunta con successo.";
        } catch (Throwable $e) {
            $errore = $e->getMessage();
        }
    }

    if ($azione === 'elimina_replica') {
        $id_replica = (int)($_POST['id_replica'] ?? 0);

        try {
            if ($id_replica <= 0) {
                throw new Exception("Replica non valida.");
            }

            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM biglietto b
                INNER JOIN evento_settore es ON b.id_evento_settore = es.id
                WHERE es.id_replica_evento = ?
            ");
            $stmt->execute([$id_replica]);
            $numeroBiglietti = (int)$stmt->fetchColumn();

            if ($numeroBiglietti > 0) {
                throw new Exception("Non puoi eliminare questa replica: ci sono già biglietti acquistati.");
            }

            $stmt = $pdo->prepare("DELETE FROM replica_evento WHERE id = ? AND id_evento = ?");
            $stmt->execute([$id_replica, $id_evento]);

            $messaggio = "Replica eliminata con successo.";
        } catch (Throwable $e) {
            $errore = $e->getMessage();
        }
    }
}

$categorie = $pdo->query("SELECT id, nome FROM categoria ORDER BY nome ASC")->fetchAll();
$luoghi = $pdo->query("SELECT id, nome, citta FROM luogo ORDER BY citta ASC, nome ASC")->fetchAll();

$stmt = $pdo->prepare("
    SELECT id, data_ora_inizio, data_ora_fine, stato
    FROM replica_evento
    WHERE id_evento = ?
    ORDER BY data_ora_inizio ASC
");
$stmt->execute([$id_evento]);
$repliche = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT MIN(data_ora_inizio)
    FROM replica_evento
    WHERE id_evento = ?
");
$stmt->execute([$id_evento]);
$primaData = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Evento - EasyTicket</title>
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
            <a href="admin_dashboard.php" class="user-pill primary-pill"><?php echo esc($_SESSION['username'] ?? 'admin'); ?></a>
            <a href="logout.php" class="user-pill secondary-pill">Logout</a>
        </nav>
    </div>
</header>

<main class="page-shell">
    <section class="section-block">
        <div class="section-heading">
            <h2>Modifica evento</h2>
            <p>Gestisci dati principali e repliche dell'evento selezionato.</p>
        </div>

        <?php if ($messaggio !== ""): ?>
            <div class="admin-card" style="margin-bottom:20px;">
                <?php echo esc($messaggio); ?>
            </div>
        <?php endif; ?>

        <?php if ($errore !== ""): ?>
            <div class="admin-card" style="margin-bottom:20px; border-color:#f1d1ca; color:#c13d2a;">
                <?php echo esc($errore); ?>
            </div>
        <?php endif; ?>

        <div class="admin-grid">
            <div class="admin-card">
                <h3>Dati evento</h3>

                <form action="modifica_evento.php?id=<?php echo $id_evento; ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="azione" value="modifica_evento">

                    <label for="titolo">Titolo evento</label>
                    <input type="text" id="titolo" name="titolo" value="<?php echo esc($evento['titolo']); ?>" required>

                    <label for="descrizione">Descrizione</label>
                    <textarea id="descrizione" name="descrizione"><?php echo esc($evento['descrizione'] ?? ''); ?></textarea>

                    <label for="id_categoria">Categoria</label>
                    <select id="id_categoria" name="id_categoria" required>
                        <?php foreach ($categorie as $cat): ?>
                            <option value="<?php echo (int)$cat['id']; ?>" <?php echo ((int)$evento['id_categoria'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                                <?php echo esc($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="id_luogo">Luogo</label>
                    <select id="id_luogo" name="id_luogo" required>
                        <?php foreach ($luoghi as $luogo): ?>
                            <option value="<?php echo (int)$luogo['id']; ?>" <?php echo ((int)$evento['id_luogo'] === (int)$luogo['id']) ? 'selected' : ''; ?>>
                                <?php echo esc($luogo['citta'] . " - " . $luogo['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="immagine">Nuova immagine evento</label>
                    <input type="file" id="immagine" name="immagine" accept="image/*">

                    <button type="submit" class="primary-btn">Salva modifiche evento</button>
                </form>
            </div>

            <div class="admin-card">
                <h3>Anteprima</h3>
                <?php if (!empty($evento['immagine'])): ?>
                    <img src="<?php echo esc($evento['immagine']); ?>" alt="<?php echo esc($evento['titolo']); ?>" width="220">
                <?php else: ?>
                    <img src="img/evento-default.png" alt="Evento" width="220">
                <?php endif; ?>

                <p><strong><?php echo esc($evento['titolo']); ?></strong></p>
                <p><?php echo esc($evento['descrizione'] ?? 'Nessuna descrizione'); ?></p>
                <p>Prima replica: <?php echo $primaData ? esc($primaData) : 'Nessuna replica'; ?></p>
            </div>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Aggiungi replica</h2>
            <p>Qui puoi inserire più date o più orari per lo stesso evento.</p>
        </div>

        <div class="admin-card">
            <form action="modifica_evento.php?id=<?php echo $id_evento; ?>" method="post">
                <input type="hidden" name="azione" value="aggiungi_replica">

                <label for="data_ora_inizio">Data e ora inizio</label>
                <input type="datetime-local" id="data_ora_inizio" name="data_ora_inizio" required>

                <label for="data_ora_fine">Data e ora fine (facoltativa)</label>
                <input type="datetime-local" id="data_ora_fine" name="data_ora_fine">

                <button type="submit" class="primary-btn">Aggiungi replica</button>
            </form>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Repliche esistenti</h2>
            <p>Puoi vedere e cancellare le repliche già associate a questo evento.</p>
        </div>

        <?php if (!empty($repliche)): ?>
            <div class="admin-list">
                <?php foreach ($repliche as $replica): ?>
                    <div class="admin-list-item">
                        <div>
                            <strong><?php echo esc($replica['data_ora_inizio']); ?></strong>
                            <?php if (!empty($replica['data_ora_fine'])): ?>
                                <span> - <?php echo esc($replica['data_ora_fine']); ?></span>
                            <?php endif; ?>
                        </div>

                        <form action="modifica_evento.php?id=<?php echo $id_evento; ?>" method="post" onsubmit="return confirm('Vuoi davvero eliminare questa replica?');">
                            <input type="hidden" name="azione" value="elimina_replica">
                            <input type="hidden" name="id_replica" value="<?php echo (int)$replica['id']; ?>">
                            <button type="submit" class="secondary-pill">Elimina</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>Nessuna replica presente</h3>
                <p>Aggiungi almeno una replica per permettere la scelta di data e orario.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="site-footer">
    <p>&copy; 2026 EasyTicket</p>
</footer>
</body>
</html>
