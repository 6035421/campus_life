<?php
// Configuratiebestand voor CampusLife

// Database instellingen
define('DB_HOST', 'localhost');
define('DB_NAAM', 'campus_life');
define('DB_GEBRUIKER', 'codex');  // Pas dit aan naar je database gebruiker
define('DB_WACHTWOORD', 'root');      // Pas dit aan naar je database wachtwoord

// Applicatie instellingen
define('SITE_NAAM', 'CampusLife');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '');

// Bestandsuploads
define('UPLOAD_PAD', __DIR__ . '/uploads');

// E-mail instellingen
define('MAIL_VAN', 'noreply@campuslife.nl');
define('MAIL_NAAM', 'CampusLife');

// Sessie starten
session_start();

// Foutmeldingen
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Tijdzone
ini_set('date.timezone', 'Europe/Amsterdam');

// Database verbinding maken
function maakDBVerbinding() {
    $db = null;
    
    try {
        $db = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAAM . ';charset=utf8',
            DB_GEBRUIKER,
            DB_WACHTWOORD
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die('Fout bij verbinden met de database: ' . $e->getMessage());
    }
    
    return $db;
}

// Controleren of gebruiker is ingelogd
function isIngelogd() {
    return isset($_SESSION['gebruiker_id']);
}

// Doorsturen naar een andere pagina
function stuurDoor($pagina) {
    header('Location: ' . SITE_URL . $pagina);
    exit();
}

// Wachtwoord hashen
function hashWachtwoord($wachtwoord) {
    return password_hash($wachtwoord, PASSWORD_BCRYPT);
}

// Bestand uploaden
function uploadBestand($bestand, $doelmap, $toegestane_types = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']) {
    if (!file_exists($doelmap)) {
        mkdir($doelmap, 0755, true);
    }
    
    $bestandsnaam = uniqid() . '_' . basename($bestand['name']);
    $doelpad = $doelmap . '/' . $bestandsnaam;
    
    // Bestandstype controleren
    $bestandstype = mime_content_type($bestand['tmp_name']);
    if (!in_array($bestandstype, $toegestane_types)) {
        throw new Exception('Alleen JPG, PNG, PDF, DOC en DOCX bestanden zijn toegestaan.');
    }
    
    // Bestandsgrootte controleren (max 5MB)
    if ($bestand['size'] > 5 * 1024 * 1024) {
        throw new Exception('Bestand is te groot. Maximale grootte is 5MB.');
    }
    
    if (move_uploaded_file($bestand['tmp_name'], $doelpad)) {
        return $bestandsnaam;
    } else {
        throw new Exception('Er is een fout opgetreden bij het uploaden van het bestand.');
    }
}

// Flashberichten
function zetFlashBericht($type, $bericht) {
    $_SESSION['flash'] = [
        'type' => $type,
        'bericht' => $bericht
    ];
}

function krijgFlashBericht() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Controleren of gebruiker een bepaalde rol heeft
function heeftRol($rol) {
    return isset($_SESSION['gebruiker_rol']) && $_SESSION['gebruiker_rol'] === $rol;
}

// Controleren of gebruiker is ingelogd, anders doorsturen
function vereisInloggen() {
    if (!isIngelogd()) {
        zetFlashBericht('waarschuwing', 'Je moet ingelogd zijn om deze pagina te bekijken.');
        stuurDoor('/inloggen.php');
    }
}

// Controleren of gebruiker een specifieke rol heeft, anders doorsturen
function vereisRol($rol) {
    vereisInloggen();
    
    if (!heeftRol($rol)) {
        zetFlashBericht('fout', 'Je hebt geen toegang tot deze pagina.');
        stuurDoor('/dashboard.php');
    }
}

// Willekeurige token genereren voor wachtwoord reset
function genereerResetToken() {
    return bin2hex(random_bytes(32));
}

// E-mail verzenden
function verstuurEmail($naar, $onderwerp, $bericht, $van = null, $vanNaam = null) {
    $van = $van ?: MAIL_VAN;
    $vanNaam = $vanNaam ?: MAIL_NAAM;
    
    $headers = "From: $vanNaam <$van>\r\n";
    $headers .= "Reply-To: $van\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($naar, $onderwerp, $bericht, $headers);
}

// Datum/tijd opmaken
function formatDatum($datum, $formaat = 'd-m-Y H:i') {
    $datum = new DateTime($datum);
    return $datum->format($formaat);
}

// Beveilig invoer
function schoonInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>
