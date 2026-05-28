<?php
require_once 'init.php';

// Query per la visualizzazione dell'evento nella home
$sql = "
    SELECT
        e.id,
        e.titolo,
        MIN(r.data_ora_inizio) AS data_evento,
        e.immagine,
        c.nome AS categoria,
        l.nome AS luogo
    FROM evento e
    JOIN categoria c ON e.id_categoria = c.id
    JOIN luogo l ON e.id_luogo = l.id
    INNER JOIN replica_evento r ON r.id_evento = e.id
    WHERE e.stato != 'annullato'
      AND r.stato != 'annullata'
    GROUP BY e.id, e.titolo, e.immagine, c.nome, l.nome
    ORDER BY data_evento ASC
";

$stmt = $pdo->query($sql);
$events = $stmt->fetchAll();

$role = isset($_SESSION['ruolo']) ? (int) $_SESSION['ruolo'] : null; //acquisisce il ruolo
$username = isset($_SESSION['username']) ? esc($_SESSION['username']) : null; //acquisisce l'username
$isLogged = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true; //flag per controllo login

//sezione messsaggio dinamico 
if ($role === 1) {
    // Admin
    $heroTitle = "Pannello Amministratore";
    $heroSubtitle = "";
} elseif ($role === 2) {
    // Cliente loggato
    $heroTitle = "Bentornato, " . ($username ?? "Utente");
    $heroSubtitle = "Scopri i prossimi eventi, gestisci i tuoi biglietti e prenota in pochi click.";
} else {
    // Ospite
    $heroTitle = "Benvenuto su EasyTicket";
    $heroSubtitle = "Prenota i tuoi eventi in modo semplice, veloce e sicuro.";
}
$heroBackground = "img/image.png";
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyTicket</title>
    <link rel="icon" type="image/x-icon" href="img/icn_sito_sf.png">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/public.css">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="home.php" class="brand">
                <img src="img/logo_sito.png" alt="Logo EasyTicket">
            </a>

            <nav class="user-nav">
                <?php if ($isLogged): ?> <!--visualizzazione bottone login/logount in base alla funzione-->
                    <a href="<?php echo $role === 1 ? 'admin_dashboard.php' : 'User_dashboard.php'; ?>" class="user-pill primary-pill">
                        <?php echo $username; ?>
                    </a>
                    <a href="logout.php" class="user-pill secondary-pill">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="user-pill primary-pill">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="page-shell"> <!--visualizzazione sfondo e herotitle e herosubtitle-->
        <section
            class="hero-section"
            style="background: url('<?php echo htmlspecialchars($heroBackground, ENT_QUOTES, 'UTF-8'); ?>') center/cover no-repeat;">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1><?php echo esc($heroTitle); ?></h1>
                <p><?php echo esc($heroSubtitle); ?></p>
            </div>
        </section>

        <section class="category-bar"> <!--barra delle categorie (il resto in js)-->
            <button class="category-item" data-category="all">Tutti gli Eventi</button>
            <button class="category-item" data-category="concerto">Concerti</button>
            <button class="category-item" data-category="teatro">Teatro</button>
            <button class="category-item" data-category="festival">Festival</button>
        </section>

        <section class="section-block" id="eventi">
            <div class="section-heading">
                <h2>Eventi Disponibili</h2>
                <p>Seleziona un evento per vedere i dettagli e procedere con la prenotazione.</p>
            </div>

            <?php if (!empty($events)): ?>
                <div class="event-grid">
                    <?php foreach ($events as $event): ?>
                        <article
                            class="event-card"
                            data-category="<?php echo strtolower(htmlspecialchars($event['categoria'])); ?>"
                            onclick="handleEventClick(<?php echo (int) $event['id']; ?>)">
                            <div class="event-card-top">
                                <span class="event-badge">
                                    <?php echo htmlspecialchars($event['categoria']); ?>
                                </span>
                                <span class="event-date">
                                    <?php
                                    $data = new DateTime($event['data_evento']);
                                    echo $data->format('d/m/Y H:i'); // formato di data corretta
                                    ?>
                                </span>
                            </div>

                            <div class="event-logos">
                                <?php if (!empty($event['immagine'])): ?>
                                    <img
                                        src="<?php echo htmlspecialchars($event['immagine']); ?>"
                                        alt="<?php echo htmlspecialchars($event['titolo']); ?>"
                                    >
                                <?php else: ?>
                                    <img src="img/evento-default.png" alt="Evento">
                                <?php endif; ?>
                            </div>

                            <div class="event-details">
                                <h3><?php echo htmlspecialchars($event['titolo']); ?></h3>
                                <p><?php echo htmlspecialchars($event['luogo']); ?></p>
                            </div>

                            <div class="event-card-bottom">
                                <span class="event-action">Vai all'evento</span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Nessun evento disponibile</h3>
                    <p>Al momento non ci sono eventi caricati nel sistema.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 EasyTicket</p>
    </footer>
<!--reindirizzamento nei tre casi di login-->
    <script> 
        function handleEventClick(eventId) {
            <?php if ($isLogged): ?>
                <?php if ($role === 1): ?>
                    window.location.href = "modifica_evento.php?id=" + eventId;
                <?php elseif ($role === 2): ?>
                    window.location.href = "evento.php?id=" + eventId;
                <?php else: ?>
                    window.location.href = "login.php";
                <?php endif; ?>
            <?php else: ?>
                window.location.href = "login.php";
            <?php endif; ?>
        }
    </script>

    <script src="js/home.js"></script>
</body>
</html>