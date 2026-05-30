<?php
require_once 'init.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

if ((int)($_SESSION['ruolo'] ?? 0) !== 1) {
    header('Location: home.php');
    exit();
}

$id_evento = (int)($_GET['id'] ?? 0);
if ($id_evento <= 0) {
    die('Evento non valido.');
}
 // salva l'immagine e restituisce il percorso da salvare in DB, stessa funzione di upload usata in admin_dashboard.php
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

    return $percorsoDb; // percorso da salvare in DB
}
 // Funzione per ottenere i dettagli di un evento, restituisce null se non trovato
function getEvento(PDO $pdo, int $id_evento): ?array {
    $stmt = $pdo->prepare("SELECT * FROM evento WHERE id = ? LIMIT 1");
    $stmt->execute([$id_evento]);
    return $stmt->fetch() ?: null;
}
// Funzione per ottenere tutte le repliche di un evento, restituisce un array vuoto se non ci sono repliche
function getRepliche(PDO $pdo, int $id_evento): array {
    $stmt = $pdo->prepare("
        SELECT id, data_ora_inizio, data_ora_fine, stato
        FROM replica_evento
        WHERE id_evento = ?
        ORDER BY data_ora_inizio ASC
    ");
    $stmt->execute([$id_evento]);
    return $stmt->fetchAll();
}
// Funzione per ottenere i settori di un luogo, restituisce un array vuoto se non ci sono settori
function getSettoriLuogo(PDO $pdo, int $idLuogo): array {
    $stmt = $pdo->prepare("
        SELECT id, nome, descrizione, prezzo_base, posti_totali
        FROM settore
        WHERE id_luogo = ?
        ORDER BY nome ASC
    ");
    $stmt->execute([$idLuogo]);
    return $stmt->fetchAll();
}
// prende la data e la formatta in forma italiana, se vuoto restituisce null
function formatDataOra(?string $value): string {
    if (!$value) return '';
    return date('d/m/Y H:i', strtotime($value));
}
//errore eevento non trovato
$evento = getEvento($pdo, $id_evento);
if (!$evento) {
    die('Evento non trovato.');
}
// ottiene i settori del luogo associato all'evento, se presente
$settoriLuogo = [];
if (isset($evento['id_luogo']) && (int)$evento['id_luogo'] > 0) {
    $settoriLuogo = getSettoriLuogo($pdo, (int)$evento['id_luogo']);
}

$errore = '';
$messaggio = '';

if (isset($_GET['success']) && $_GET['success'] === '1') { //messaggio di successo
    $messaggio = 'Operazione completata con successo.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {// verifica quale azione è stata richiesta tramite il campo azione del form
    $azione = trim($_POST['azione'] ?? '');

    if ($azione === 'modifica_dettagli_evento') {
        $titolo = trim($_POST['titolo'] ?? '');
        $descrizione = trim($_POST['descrizione'] ?? '');

        try {
            if ($titolo === '') {
                throw new Exception('Il titolo è obbligatorio.');
            }
            // Gestione immagine: se viene caricata una nuova immagine, salva e usa quella, altrimenti mantieni quella esistente (se presente)
            $nuovaImmagine = salvaImmagine($_FILES['immagine'] ?? null);
            $immagineDaSalvare = $nuovaImmagine ?? ($evento['immagine'] ?? null);
            // Aggiorna i dettagli dell'evento nel database
            $stmt = $pdo->prepare("
                UPDATE evento
                SET titolo = ?, descrizione = ?, immagine = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $titolo,
                $descrizione !== '' ? $descrizione : null,
                $immagineDaSalvare,
                $id_evento
            ]);

            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id_evento . '&success=1');
            exit();
        } catch (Throwable $e) {
            $errore = 'Errore durante il salvataggio dei dettagli evento: ' . $e->getMessage();
        }
    }
        // Aggiunta di una nuova replica per l'evento
        if ($azione === 'aggiungi_replica') {
        $dataOraInizio = trim((string)($_POST['data_ora_inizio'] ?? ''));

        if ($dataOraInizio === '') {
            $errore = 'Inserisci la data e ora di inizio.';
        } elseif (empty($settoriLuogo)) {
            $errore = 'Il luogo associato all evento non ha settori configurati.';
        } else {
            try {
                $pdo->beginTransaction();

                $dataOraInizioSql = str_replace('T', ' ', $dataOraInizio);
                if (strlen($dataOraInizioSql) === 16) {
                    $dataOraInizioSql .= ':00';
                }
                // Inserisce la nuova replica
                $pdo->prepare("
                    INSERT INTO replica_evento (id_evento, data_ora_inizio, data_ora_fine, stato)
                    VALUES (?, ?, ?, 'programmata')
                ")->execute([$id_evento, $dataOraInizioSql, $dataOraFineSql]);

                $idReplica = (int)$pdo->lastInsertId();
                // Prepara la query di inserimento per evento_settore
                $stmtInsertES = $pdo->prepare("
                    INSERT INTO evento_settore (
                        id_replica_evento,
                        id_evento,
                        id_settore,
                        prezzo,
                        posti_totali,
                        posti_disponibili
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                // Per ogni settore del luogo, crea una nuova riga in evento_settore con i dati della replica appena creata
                foreach ($settoriLuogo as $settore) {
                    $idSettore = (int)$settore['id'];
                    $postiTotali = (int)$settore['posti_totali'];
                    $prezzo = (float)$settore['prezzo_base'];

                    if ($prezzo <= 0 || $postiTotali <= 0) {
                        continue;
                    }

                    $stmtInsertES->execute([
                        $idReplica,
                        $id_evento,
                        $idSettore,
                        $prezzo,
                        $postiTotali,
                        $postiTotali
                    ]);
                }

                $pdo->commit();
                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id_evento . '&success=1');
                exit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errore = 'Errore durante l aggiunta della replica: ' . $e->getMessage();
            }
        }
    }
    // Annullamento di una replica esistente e rimborso dei biglietti associati
    if ($azione === 'annulla_replica') {
        
    $idReplica = (int)($_POST['id_replica'] ?? 0);

    if ($idReplica <= 0) {
        $errore = 'ID replica non valido.';
    } else {
        try {
            $pdo->beginTransaction();

            //Annulla la replica
            $stmt = $pdo->prepare("
                UPDATE replica_evento
                SET stato = 'annullata'
                WHERE id = ? AND id_evento = ?
            ");
            $stmt->execute([$idReplica, $id_evento]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Nessuna replica trovata con questi criteri.');
            }

            // Recupera tutti i biglietti da rimborsare per quella replica
            $stmtRimborsi = $pdo->prepare("
                SELECT b.id AS id_biglietto, b.id_utente, b.prezzo
                FROM biglietto b
                INNER JOIN evento_settore es ON b.id_evento_settore = es.id
                WHERE es.id_replica_evento = ?
                  AND b.stato_rimborso = 'nessuno'
                  AND b.disponibilita = 1
            ");
            $stmtRimborsi->execute([$idReplica]);
            $bigliettiDaRimborsare = $stmtRimborsi->fetchAll(PDO::FETCH_ASSOC);

            $stmtAggiornaSaldo = $pdo->prepare("
                UPDATE utente
                SET saldo = saldo + ?
                WHERE id = ?
            ");
            // 
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

            $stmtInserisciRimborso = $pdo->prepare("
                INSERT INTO rimborsi (id_biglietto, id_utente, importo, stato, motivo, data_elaborazione)
                VALUES (?, ?, ?, 'completato', 'Replica annullata', NOW())
            ");

            foreach ($bigliettiDaRimborsare as $b) {
                $idBiglietto = (int)$b['id_biglietto'];
                $idUtente = (int)$b['id_utente'];
                $importo = (float)$b['prezzo'];

                $stmtAggiornaSaldo->execute([$importo, $idUtente]);
                $stmtAggiornaBiglietto->execute([$idBiglietto]);
                $stmtInserisciRimborso->execute([$idBiglietto, $idUtente, $importo]);

                $stmtNotifica->execute([
                    $idUtente,
                    'Rimborso automatico effettuato',
                    'La replica annullata ha generato un rimborso automatico di € ' .
                    number_format($importo, 2, ',', '.') .
                    ' sul tuo wallet.'
                ]);
            }

            $pdo->commit();
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id_evento . '&success=1');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errore = 'Errore durante l\\annullamento della replica: ' . $e->getMessage();
        }
    }
}

    if ($azione === 'riattiva_replica') {
        $idReplica = (int)($_POST['id_replica'] ?? 0);

        if ($idReplica <= 0) {
            $errore = 'ID replica non valido.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE replica_evento
                    SET stato = 'programmata'
                    WHERE id = ? AND id_evento = ?
                ");
                $stmt->execute([$idReplica, $id_evento]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception('Nessuna replica trovata con questi criteri.');
                }

                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id_evento . '&success=1');
                exit();
            } catch (Throwable $e) {
                $errore = 'Errore durante la riattivazione: ' . $e->getMessage();
            }
        }
    }
}

$repliche = getRepliche($pdo, $id_evento);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica evento - EasyTicket</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="home.php" class="brand"><img src="img/logo_sito.png" alt="Logo EasyTicket"></a>
        <nav class="user-nav">
            <a href="admin_dashboard.php" class="user-pill primary-pill">Dashboard</a>
            <a href="logout.php" class="user-pill secondary-pill">Logout</a>
        </nav>
    </div>
</header>

<main class="page-shell">

        <?php if ($errore !== ''): ?>
            <div class="admin-card msg-ko" style="margin-bottom:20px; border-left: 4px solid #dc3545;">
                <strong>Errore:</strong> <?php echo esc($errore); ?>
            </div>
        <?php endif; ?>

        <?php if ($messaggio !== ''): ?>
            <div class="admin-card msg-ok" style="margin-bottom:20px; border-left: 4px solid #28a745;">
                <strong>Successo:</strong> <?php echo esc($messaggio); ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Dettagli evento</h2>
            <p>Modifica titolo, descrizione e immagine.</p>
        </div>

        <div class="admin-card">
            <form method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?id=<?php echo $id_evento; ?>">
                <input type="hidden" name="azione" value="modifica_dettagli_evento">

                <div class="admin-form-group">
                    <label for="titolo">Titolo evento</label>
                    <input type="text" id="titolo" name="titolo" required value="<?php echo esc($evento['titolo'] ?? ''); ?>">
                </div>

                <div class="admin-form-group">
                    <label for="descrizione">Descrizione</label>
                    <textarea id="descrizione" name="descrizione" rows="5"><?php echo esc($evento['descrizione'] ?? ''); ?></textarea>
                </div>

                <div class="admin-form-group">
                    <label for="immagine">Immagine evento</label>
                    <?php if (!empty($evento['immagine'])): ?> <!-- Se c'è un'immagine già salvata -->
                        <div style="margin-bottom:10px;"> 
                            <img src="<?php echo esc($evento['immagine']); ?>" alt="<?php echo esc($evento['titolo'] ?? 'Evento'); ?>" style="max-width:220px; border-radius:8px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="immagine" name="immagine" accept=".jpg,.jpeg,.png,.webp">
                    <small>Lascia vuoto se non vuoi cambiare immagine.</small>
                </div>

                <button type="submit" class="admin-submit">Salva modifiche</button>
            </form>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Aggiungi replica</h2>
            <p>Inserisci una nuova data per l'evento. Prezzi e posti vengono letti dal luogo associato.</p>
        </div>

        <div class="admin-card">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?id=<?php echo $id_evento; ?>">
                <input type="hidden" name="azione" value="aggiungi_replica">

                <div class="admin-form-group">
                    <label for="data_ora_inizio">Data e ora inizio</label>
                    <input type="datetime-local" id="data_ora_inizio" name="data_ora_inizio" required>
                </div>

                <?php if (empty($settoriLuogo)): ?>
                    <div class="admin-card" style="margin-top:20px; color:#c13d2a;">
                        Nessun settore disponibile per il luogo associato.
                    </div>
                <?php endif; ?>

                <button type="submit" class="admin-submit">Aggiungi replica</button>
            </form>
        </div>
    </section>

    <section class="section-block">
        <div class="section-heading">
            <h2>Repliche esistenti</h2>
            <p>Puoi annullare, riattivare o eliminare una replica.</p>
        </div>

        <?php if (!empty($repliche)): ?>
            <div class="admin-list">
                <?php foreach ($repliche as $replica): ?>
                    <div class="admin-list-item">
                        <div>
                            <strong><?php echo esc(formatDataOra($replica['data_ora_inizio'])); ?></strong>
                            <?php if (!empty($replica['data_ora_fine'])): ?>
                            <?php endif; ?>
                            <div>Stato: <strong><?php echo esc($replica['stato']); ?></strong></div>
                        </div>

                        <div class="dashboard-actions">
                            <?php if (($replica['stato'] ?? '') !== 'annullata'): ?>
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?id=<?php echo $id_evento; ?>">
                                    <input type="hidden" name="azione" value="annulla_replica">
                                    <input type="hidden" name="id_replica" value="<?php echo (int)$replica['id']; ?>">
                                    <button type="submit" class="admin-delete">Annulla</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?id=<?php echo $id_evento; ?>">
                                    <input type="hidden" name="azione" value="riattiva_replica">
                                    <input type="hidden" name="id_replica" value="<?php echo (int)$replica['id']; ?>">
                                    <button type="submit" class="admin-submit">Riattiva</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>Nessuna replica presente</h3>
                <p>Aggiungi almeno una replica.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="site-footer">
    <p>&copy; 2026 EasyTicket</p>
</footer>

<script>
function verificaBiglietti(idReplica) {
    return confirm('⚠️ ATTENZIONE: stai per eliminare questa replica.\n\nTutti i biglietti acquistati verranno rimborsati automaticamente.\n\nSei sicuro di voler procedere?');
}
</script>
</body>
</html>