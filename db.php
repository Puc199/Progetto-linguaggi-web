<?php
$host = 'localhost';
$dbname = 'Prova';
$user = 'root';
$pass = 'root';
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try { 
    $pdo = new PDO($dsn, $user, $pass, [ // stabiliamo la connessione
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Gestione sicura degli errori: cattura eccezioni PDO e previene terminazioni improvvise.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // restituisce risultati delle query
    ]);
} catch (PDOException $e) { //gestione errore 
    die("Connessione fallita: " . $e->getMessage());
}
?>
