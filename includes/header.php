<?php
// Start de sessie als deze nog niet is gestart
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Controleer of de gebruiker is ingelogd
$isIngelogd = isset($_SESSION['gebruiker_id']);
$gebruikersnaam = $isIngelogd ? $_SESSION['gebruiker_naam'] : '';
$rol = $isIngelogd ? $_SESSION['gebruiker_rol'] : '';

// Haal eventuele flash-berichten op
$flashBericht = null;
if (isset($_SESSION['flash'])) {
    $flashBericht = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $paginaTitel ?? 'CampusLife' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="logo">
                <a href="index.php">CampusLife</a>
            </div>
            
            <nav class="main-nav">
                <ul>
                    <li><a href="rooster.php"><i class="fas fa-calendar-alt"></i> Rooster</a></li>
                    <li><a href="vakken.php"><i class="fas fa-book"></i> Vakken</a></li>
                    <li><a href="opdrachten.php"><i class="fas fa-tasks"></i> Opdrachten</a></li>
                    <li><a href="cijfers.php"><i class="fas fa-chart-bar"></i> Cijfers</a></li>
                    <li><a href="berichten.php"><i class="fas fa-envelope"></i> Berichten</a></li>
                    
                    <?php if ($isIngelogd && ($rol === 'docent' || $rol === 'beheerder')): ?>
                        <li><a href="beheer/"><i class="fas fa-cog"></i> Beheer</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="user-menu">
                <?php if ($isIngelogd): ?>
                    <div class="dropdown">
                        <button class="dropdown-toggle">
                            <span class="user-avatar">
                                <?= strtoupper(substr($gebruikersnaam, 0, 1)) ?>
                            </span>
                            <span class="user-name"><?= htmlspecialchars($gebruikersnaam) ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-content">
                            <a href="profiel.php"><i class="fas fa-user"></i> Mijn profiel</a>
                            <a href="instellingen.php"><i class="fas fa-cog"></i> Instellingen</a>
                            <div class="dropdown-divider"></div>
                            <a href="uitloggen.php"><i class="fas fa-sign-out-alt"></i> Uitloggen</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="inloggen.php" class="btn btn-outline">Inloggen</a>
                    <a href="registreren.php" class="btn">Registreren</a>
                <?php endif; ?>
            </div>
            
            <button class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>
    
    <?php if ($flashBericht): ?>
        <div class="flash-message flash-<?= $flashBericht['type'] ?>">
            <div class="container">
                <p><?= $flashBericht['bericht'] ?></p>
                <button class="close-flash">&times;</button>
            </div>
        </div>
    <?php endif; ?>
    
    <main class="main-content">
