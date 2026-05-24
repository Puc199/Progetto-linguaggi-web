<?php
require_once 'init.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$messaggio = "";
$messaggioClasse = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $new_password = $_POST['password'] ?? '';

    if ($user === '' || $new_password === '') {
        $messaggio = "Compila tutti i campi.";
        $messaggioClasse = "alert alert-danger";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("SELECT id FROM utente WHERE username = ? LIMIT 1");
        $stmt->execute([$user]);
        $idutente = $stmt->fetchColumn();

        if ($idutente) {
            $update_stmt = $pdo->prepare("UPDATE utente SET password = ? WHERE id = ?");
            $update_stmt->execute([$hashed_password, $idutente]);

            header("Location: login.php");
            exit();
        } else {
            $messaggio = "Username non trovato.";
            $messaggioClasse = "alert alert-danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyTicket - Recupera Password</title>
    <link rel="stylesheet" href="css/base.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
</head>
<body class="auth-page">
    <main class="login-shell">
        <section class="login-card">
            <div class="login-brand">
                <h1>Recupera Password</h1>
                <p>Inserisci il tuo username e imposta una nuova password per accedere di nuovo al tuo account EasyTicket.</p>
            </div>

            <?php if (!empty($messaggio)): ?>
                <div class="<?php echo $messaggioClasse; ?>">
                    <?php echo htmlspecialchars($messaggio); ?>
                </div>
            <?php endif; ?>

            <form action="recuperaPassword.php" method="post" class="login-form">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Inserisci nome utente"
                    required
                >

                <label for="password">Nuova Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Inserisci nuova password"
                    required
                >

                <div class="wrap">
                    <button type="submit">Cambia Password</button>
                </div>

                <div class="login-links">
                    <a href="login.php">Torna al login</a>
                    <a href="register.php">Non hai un account? Registrati</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>