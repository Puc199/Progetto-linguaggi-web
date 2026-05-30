<?php
require_once 'init.php'; // Include sessione, $pdo e funzione esc()
$reg_error = '';
$reg_success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $data_nascita = trim($_POST['data_nascita'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nome === '' || $cognome === '' || $data_nascita === '' || $username === '' || $password === '') {
        $reg_error = "Compila tutti i campi.";
    } else {
        try {
            // 1. Verifica se l'username esiste già (PDO)
            $stmt = $pdo->prepare("SELECT id FROM utente WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $exists = $stmt->fetch() !== false;

            if ($exists) {
                $reg_error = "Username già in uso. Scegline un altro.";
            } else {
                // 2. Inserimento nuovo utente (PDO)
                $hash = password_hash($password, PASSWORD_DEFAULT); //in questo modo la password è sicura PASSWORD_DEFAULT serve px
                $id_ruolo = 2;
                $saldo = 0.00;

                $stmt = $pdo->prepare("
                    INSERT INTO utente (nome, cognome, data_nascita, username, password, id_ruolo, saldo)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nome, $cognome, $data_nascita, $username, $hash, $id_ruolo, $saldo]);

                $reg_success = "Registrazione completata! Reindirizzamento...";
                header("Refresh: 2; URL=login.php");
                exit; //ferma l'esecuzione dopo il redirect
            }
        } catch (PDOException $e) {
            // con PDO::ERRMODE_EXCEPTION in db.php, gli errori del DB finiscono qui
            $reg_error = "Errore durante la registrazione: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrati - EasyTicket</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">

</head>
<body class="auth-page">

    <main class="login-shell">
        <div class="login-card register-card">
            <div class="login-brand">
                <img src="img/logo_sito.png" alt="Logo EasyTicket">
                <h1>Crea account</h1>
            </div>

            <?php if (!empty($reg_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($reg_error); ?></div>
            <?php endif; ?>

            <?php if (!empty($reg_success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($reg_success); ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">

                <label for="cognome">Cognome</label>
                <input type="text" id="cognome" name="cognome" required value="<?php echo htmlspecialchars($_POST['cognome'] ?? ''); ?>">

                <label for="data_nascita">Data di nascita</label>
                <input type="date" id="data_nascita" name="data_nascita" required value="<?php echo htmlspecialchars($_POST['data_nascita'] ?? ''); ?>">

                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <div class="wrap">
                    <button type="submit" class="auth-submit">Registrati</button>
                </div>
            </form>

            <div class="login-links">
                <a href="login.php">Hai già un account? Accedi</a>
            </div>
        </div>
    </main>

</body>
</html>

