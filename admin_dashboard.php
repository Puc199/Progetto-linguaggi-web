<?php
require_once 'init.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

if ((int)($_SESSION['ruolo'] ?? 0) !== 1) {
    header("Location: home.php");
    exit();
}

$messaggio = "";
$errore    = "";
$azione    = "";

function generaDate(string $a, string $b): array {
    $out = [];
    $s = new DateTime($a);
    $e = new DateTime($b);
    $s->setTime(0, 0, 0);
    $e->setTime(0, 0, 0);

    if ($s > $e) return [];

    $c = clone $s;
    while ($c <= $e) {
        $out[] = $c->format('Y-m-d');
        $c->modify('+1 day');
    }

    return $out;
}

function salvaImmagine(?array $file): ?string {
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Errore upload immagine.");
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new Exception("Formato non consentito.");
    }

    $dir = __DIR__ . '/img/eventi/';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $nome = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $dir . $nome)) {
        throw new Exception("Errore salvataggio file.");
    }

    return 'img/eventi/' . $nome;
}

function getSettoriByLuogo(int $idLuogo): array {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT id, nome, descrizione, prezzo_base, posti_totali
        FROM settore
        WHERE id_luogo = ?
        ORDER BY nome ASC
    ");
    $stmt->execute([$idLuogo]);

    return $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    if ($azione === 'aggiungi_evento') {
        $titolo      = trim($_POST['titolo'] ?? '');
        $descrizione = trim($_POST['descrizione'] ?? '');
        $id_cat      = (int)($_POST['id_categoria'] ?? 0);
        $id_luogo    = (int)($_POST['id_luogo'] ?? 0);
        $d_inizio    = trim($_POST['data_inizio'] ?? '');
        $d_fine      = trim($_POST['data_fine'] ?? '');
        $orario      = trim($_POST['orario_spettacolo'] ?? '');

        try {
            if (!$titolo || !$id_cat || !$id_luogo || !$d_inizio || !$d_fine || !$orario) {
                throw new Exception("Compila tutti i campi obbligatori.");
            }

            $date = generaDate($d_inizio, $d_fine);
            if (empty($date)) {
                throw new Exception("Intervallo date non valido.");
            }

            $settoriLuogo = getSettoriByLuogo($id_luogo);
            if (empty($settoriLuogo)) {
                throw new Exception("Il luogo selezionato non ha settori associati.");
            }

            $settoriValidi = [];
            foreach ($settoriLuogo as $s) {
                $settoriValidi[] = [
                    'id'     => (int)$s['id'],
                    'prezzo' => (float)$s['prezzo_base'],
                    'posti'  => (int)$s['posti_totali']
                ];
            }

            $immagine = salvaImmagine($_FILES['immagine'] ?? null);

            global $pdo;
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO evento (titolo, descrizione, id_categoria, id_luogo, immagine, stato)
                VALUES (?, ?, ?, ?, ?, 'programmato')
            ");
            $stmt->execute([$titolo, $descrizione, $id_cat, $id_luogo, $immagine]);
            $id_evento = (int)$pdo->lastInsertId();

            $nRep = 0;

            foreach ($date as $d) {
                $dataOra = $d . ' ' . $orario . ':00';

                $stmt = $pdo->prepare("
                    INSERT INTO replica_evento (id_evento, data_ora_inizio, stato)
                    VALUES (?, ?, 'programmata')
                ");
                $stmt->execute([$id_evento, $dataOra]);
                $id_replica = (int)$pdo->lastInsertId();
                $nRep++;

                foreach ($settoriValidi as $sv) {
                    $stmt = $pdo->prepare("
                        INSERT INTO evento_settore
                        (id_replica_evento, id_evento, id_settore, prezzo, posti_totali, posti_disponibili)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $id_replica,
                        $id_evento,
                        $sv['id'],
                        $sv['prezzo'],
                        $sv['posti'],
                        $sv['posti']
                    ]);
                }
            }

            $pdo->commit();
            $messaggio = "Evento creato! Repliche generate: $nRep.";
        } catch (Throwable $e) {
            try {
                $pdo->rollBack();
            } catch (Throwable $ignored) {}

            $errore = $e->getMessage();
        }
    }

    if ($azione === 'elimina_evento') {
        $id_el = (int)($_POST['id_evento'] ?? 0);

        try {
    if ($id_el <= 0) {
        throw new Exception("Evento non valido.");
    }

    global $pdo;
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id, immagine, titolo, stato
        FROM evento
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$id_el]);
    $ev = $stmt->fetch();

    if (!$ev) {
        throw new Exception("Evento non trovato.");
    }

    if (($ev['stato'] ?? '') === 'annullato') {
        throw new Exception("L'evento è già annullato.");
    }

    $stmt = $pdo->prepare("
        SELECT b.id_utente, SUM(b.prezzo) AS totale_rimborso
        FROM biglietto b
        JOIN evento_settore es ON b.id_evento_settore = es.id
        WHERE es.id_evento = ?
        GROUP BY b.id_utente
    ");
    $stmt->execute([$id_el]);
    $rimborsi = $stmt->fetchAll();

    if (!empty($rimborsi)) {
        $stmtUpdateSaldo = $pdo->prepare("
            UPDATE utente
            SET saldo = saldo + ?
            WHERE id = ?
        ");

        foreach ($rimborsi as $rimborso) {
            $totale = (float)$rimborso['totale_rimborso'];
            $idUtente = (int)$rimborso['id_utente'];

            if ($totale > 0 && $idUtente > 0) {
                $stmtUpdateSaldo->execute([$totale, $idUtente]);
            }
        }
    }

    $pdo->prepare("
        UPDATE evento
        SET stato = 'annullato'
        WHERE id = ?
    ")->execute([$id_el]);

    $pdo->prepare("
        UPDATE replica_evento
        SET stato = 'annullata'
        WHERE id_evento = ?
    ")->execute([$id_el]);

    $pdo->commit();

    $numUtentiRimborsati = count($rimborsi);
    $messaggio = "Evento annullato correttamente. Utenti rimborsati: $numUtentiRimborsati.";
} catch (Throwable $e) {
    try {
        $pdo->rollBack();
    } catch (Throwable $ignored) {}

    $errore = $e->getMessage();
}
    }
}

$categorie = $pdo->query("SELECT id, nome FROM categoria ORDER BY nome ASC")->fetchAll();
$luoghi = $pdo->query("SELECT id, nome, citta FROM luogo ORDER BY nome ASC")->fetchAll();

$eventi = $pdo->query("
    SELECT e.id, e.titolo, e.descrizione, e.immagine, e.stato,
           c.nome AS categoria, l.nome AS luogo, l.citta,
           MIN(r.data_ora_inizio) AS data_evento,
           COUNT(DISTINCT r.id) AS numero_repliche
    FROM evento e
    JOIN categoria c ON e.id_categoria = c.id
    JOIN luogo l ON e.id_luogo = l.id
    LEFT JOIN replica_evento r ON r.id_evento = e.id
    GROUP BY e.id, e.titolo, e.descrizione, e.immagine, e.stato, c.nome, l.nome, l.citta
    ORDER BY data_evento DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Admin Dashboard - EasyTicket</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel= "stylesheet" href="css/admin.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand"><img src="img/logo_sito.png" alt="EasyTicket"></a>
        <nav class="user-nav">
            <a href="admin_dashboard.php" class="user-pill primary-pill">Admin</a>
            <a href="logout.php" class="user-pill secondary-pill">Logout</a>
        </nav>
    </div>
</header>
<main class="page-shell">

    <section class="section-block">
        <div class="section-heading"><h2>Area Amministrazione</h2></div>
        <?php if ($messaggio): ?><div class="admin-card msg-ok" style="margin-top:16px"><?php echo esc($messaggio); ?></div><?php endif; ?>
        <?php if ($errore):    ?><div class="admin-card msg-ko" style="margin-top:16px"><?php echo esc($errore); ?></div><?php endif; ?>
    </section>

    <section class="section-block">
        <div class="section-heading"><h2>Aggiungi Nuovo Evento</h2></div>
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
                            <option value="<?php echo (int)$c['id']; ?>"><?php echo esc($c['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label>Luogo</label>
                    <select name="id_luogo" required>
                        <option value="">Seleziona...</option>
                        <?php foreach ($luoghi as $l): ?>
                            <option value="<?php echo (int)$l['id']; ?>"><?php echo esc($l['nome'] . ' — ' . $l['citta']); ?></option>
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
                <div class="admin-form-group"><label>Data inizio</label><input type="date" name="data_inizio" required></div>
                <div class="admin-form-group"><label>Data fine</label><input type="date" name="data_fine" required></div>
                <div class="admin-form-group"><label>Orario spettacolo</label><input type="time" name="orario_spettacolo" required></div>
            </div>

            <div class="admin-card" style="margin-top:20px;">
    <p><strong>Nota:</strong> i settori, i prezzi e i posti disponibili verranno caricati automaticamente in base al luogo selezionato.</p>
</div>

<div class="dashboard-actions dashboard-actions-spaced">
    <button type="submit" class="admin-submit">Crea evento</button>
</div>
        </form>
    </section>

    <section class="section-block">
        <div class="section-heading"><h2>Eventi Esistenti</h2></div>
        <?php if (empty($eventi)): ?>
            <div class="empty-state"><h3>Nessun evento presente</h3></div>
        <?php else: ?>
            <div class="admin-card table-wrap">
                <table class="admin-table">
                    <thead>
                    <tr><th>Img</th><th>Titolo</th><th>Categoria</th><th>Luogo</th><th>Prima data</th><th>Repliche</th><th>Stato</th><th>Azioni</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($eventi as $ev): ?>
                        <tr>
                            <td><img class="thumb-evento" src="<?php echo !empty($ev['immagine']) ? esc($ev['immagine']) : 'img/evento-default.png'; ?>" alt=""></td>
                            <td><strong><?php echo esc($ev['titolo']); ?></strong></td>
                            <td><?php echo esc($ev['categoria']); ?></td>
                            <td><?php echo esc($ev['luogo'] . ' - ' . $ev['citta']); ?></td>
                            <td><?php echo !empty($ev['data_evento']) ? date('d/m/Y H:i', strtotime($ev['data_evento'])) : 'N/D'; ?></td>
                            <td><?php echo (int)$ev['numero_repliche']; ?></td>
                            <td><?php echo esc($ev['stato']); ?></td>
                            <td>
                                <div class="dashboard-actions">
                                    <a href="evento.php?id=<?php echo (int)$ev['id']; ?>" class="hero-cta" style="text-decoration:none">Apri</a>
                                    <a href="modifica_evento.php?id=<?php echo (int)$ev['id']; ?>" class="secondary-btn" style="text-decoration:none;display:inline-flex;align-items:center;">Modifica</a>
                                    <form method="post" onsubmit="return confirm('Eliminare questo evento?')" style="display:inline">
                                        <input type="hidden" name="azione" value="elimina_evento">
                                        <input type="hidden" name="id_evento" value="<?php echo (int)$ev['id']; ?>">
                                        <button type="submit" class="row-remove-btn">Elimina</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<footer class="site-footer"><p>&copy; 2026 EasyTicket</p></footer>
</body>
</html>
