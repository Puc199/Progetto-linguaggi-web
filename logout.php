<?php
session_start(); // Avvia la sessione per accedere alle variabili di sessione
$_SESSION = array(); // Svuota tutte le variabili di sessione, rimuovendo eventuali dati memorizzati
session_destroy();//elimina la sessione e tutti i dati associati
header("location: login.php"); //reindirizza al login
exit;
?>

