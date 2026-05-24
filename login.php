<?php
require_once 'init.php';

// --- PARTE 1: BACKEND (PHP) ---
// Se la richiesta è di tipo POST, significa che l'utente ha cliccato "Accedi"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Diciamo al browser che la nostra risposta sarà un pacchetto dati JSON
    header('Content-Type: application/json');
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id, nome, cognome, username, password, id_ruolo FROM utente WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $user['username'];
        $_SESSION['ruolo']    = (int)$user['id_ruolo'];
        $_SESSION['user_id']  = (int)$user['id'];
        
        // Invia risposta di SUCCESSO
        echo json_encode(['success' => true]);
        exit();
    } else {
        // Invia risposta di ERRORE
        echo json_encode(['success' => false, 'message' => 'Username o password non validi.']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EasyTicket</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="icon" type="image/png" href="img/icn_sito_sf.png">
</head>
<body>
    <div class="login-shell">
        <div class="login-card">
            <div class="login-brand">
                <img src="img/logo_sito.png" alt="Logo EasyTicket">
                <h1>Accedi</h1>
            </div>

            <div id="error-message" class="alert alert-danger" style="display: none;"></div>

            <form id="form-login" action="login.php" method="post" class="login-form">
                <label for="first">Username</label>
                <input type="text" id="first" name="username" placeholder="Inserisci nome utente" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Inserisci password" required>

                <div class="wrap">
                    <button type="submit">Accedi</button>
                </div>
            </form>

            <div class="login-links">
                <a href="registra.php">Crea account</a>
                <a href="recuperaPassword.php">Hai dimenticato la password?</a>
            </div>
        </div>
    </div>

    <script>
        // Ascoltiamo l'evento 'submit' (l'invio) del modulo
        document.getElementById('form-login').addEventListener('submit', function(event) {
            
            // 1. Blocchiamo il ricaricamento fisico della pagina
            event.preventDefault(); 
            
            // 2. Raccogliamo i dati scritti dall'utente nel form
            const formData = new FormData(this);
            
            // 3. Facciamo la chiamata asincrona al server (AJAX)
            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Decodifichiamo il JSON
            .then(data => {
                if (data.success) {
                    // Login corretto: navighiamo verso la home
                    window.location.href = 'home.php';
                } else {
                    // Errore: mostriamo il messaggio rosso senza muoverci dalla pagina!
                    const errorDiv = document.getElementById('error-message');
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Errore di comunicazione:', error);
            });
        });
    </script>
</body>
</html>
