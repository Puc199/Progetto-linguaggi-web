<?php
session_start();

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "sito";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$matches = [];
$sql = "SELECT p.id, p.Squadra_C, s.nome AS Squadra_T, p.Data_partita 
        FROM partita p 
        JOIN squadre s ON s.id = p.id_squadraOspite 
        ORDER BY p.Data_partita ASC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $matches[] = $row;
    }
}
$conn->close();

$role = isset($_SESSION['ruolo']) ? $_SESSION['ruolo'] : null;
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : null;
$isLogged = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

$heroTitle = $isLogged && $username 
    ? "Benvenuto su EasyTicket, $username" 
    : "Benvenuto su EasyTicket";

$heroSubtitle = $isLogged
    ? "Scopri le prossime partite, gestisci i tuoi biglietti e prenota in pochi click."
    : "Prenota i tuoi eventi in modo semplice, veloce e sicuro.";
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyTicket</title>
    <link rel="icon" type="image/x-icon" href="img/icn_sito_sf.png">
    <link rel="stylesheet" href="css/style1.css?v=20">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="home.php" class="brand">
                <img src="img/logo_sito.png" alt="Logo EasyTicket">
            </a>

            <nav class="user-nav">
                <?php if ($isLogged): ?>
                    <a href="<?php echo $role == 1 ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>" class="user-pill primary-pill">
                        <?php echo $username; ?>
                    </a>
                    <a href="logout.php" class="user-pill secondary-pill">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="user-pill primary-pill">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="page-shell">
        <section class="hero-section">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h1><?php echo $heroTitle; ?></h1>
                <p><?php echo $heroSubtitle; ?></p>
                <a href="#eventi" class="hero-cta">Vedi Spettacoli</a>
            </div>
        </section>

        <section class="category-bar">
            <div class="category-item">Tutti gli Eventi</div>
            <div class="category-item">Serie A</div>
            <div class="category-item">Champions</div>
            <div class="category-item">Coppa Italia</div>
            <div class="category-item">Eventi Speciali</div>
            <div class="category-search">Cerca</div>
        </section>

        <section class="section-block" id="eventi">
            <div class="section-heading">
                <h2>Partite in evidenza</h2>
                <p>Seleziona una partita per vedere i dettagli e procedere con la prenotazione.</p>
            </div>

            <?php if (!empty($matches)): ?>
                <div class="matches-grid">
                    <?php foreach ($matches as $match): ?>
                        <article class="match-card" onclick="handleMatchClick(<?php echo $match['id']; ?>)">
                            <div class="match-card-top">
                                <span class="match-badge">Match</span>
                                <span class="match-date"><?php echo htmlspecialchars($match['Data_partita']); ?></span>
                            </div>

                            <div class="match-logos">
                                <img src="img/<?php echo strtolower(htmlspecialchars($match['Squadra_C'])); ?>.png" alt="Logo <?php echo htmlspecialchars($match['Squadra_C']); ?>">
                                <span class="vs-text">VS</span>
                                <img src="img/<?php echo strtolower(htmlspecialchars($match['Squadra_T'])); ?>.png" alt="Logo <?php echo htmlspecialchars($match['Squadra_T']); ?>">
                            </div>

                            <div class="match-details">
                                <h3><?php echo htmlspecialchars($match['Squadra_C']); ?> - <?php echo htmlspecialchars($match['Squadra_T']); ?></h3>
                                <p>Acquista o consulta i dettagli della partita.</p>
                            </div>

                            <div class="match-card-bottom">
                                <span class="match-action">Vai alla partita</span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Nessuna partita disponibile</h3>
                    <p>Al momento non ci sono eventi caricati nel sistema.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 EasyTicket</p>
    </footer>

    <script>
        function handleMatchClick(matchId) {
            <?php if ($isLogged): ?>
                <?php if ($role == 1): ?>
                    window.location.href = 'storico_ordini.php?id=' + matchId;
                <?php elseif ($role == 2): ?>
                    window.location.href = 'match.php?id=' + matchId;
                <?php else: ?>
                    window.location.href = 'login.php';
                <?php endif; ?>
            <?php else: ?>
                window.location.href = 'login.php';
            <?php endif; ?>
        }
    </script>
</body>
</html>