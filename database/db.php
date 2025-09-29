<?php
$host = "localhost";
$user = "codex";  // pas aan naar jouw db-gebruiker
$pass = "root";      // pas aan naar jouw db-wachtwoord
$db   = "campuslife";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connectie mislukt: " . $conn->connect_error);
}
?>