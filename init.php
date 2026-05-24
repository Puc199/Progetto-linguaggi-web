<?php
session_start();
require_once 'db.php';

// Funzione di sicurezza per output (slide 51)
function esc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
