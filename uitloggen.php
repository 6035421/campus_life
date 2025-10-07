<?php
require_once 'config.php';

// Vernietig de huidige sessie
$_SESSION = [];
session_destroy();

// Stuur door naar de inlogpagina met een uitlogbericht
zetFlashBericht('succes', 'Je bent succesvol uitgelogd.');
stuurDoor('/inloggen.php');
?>
