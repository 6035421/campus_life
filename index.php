<?php
require_once 'config.php';

// Check of gebruiker is ingelogd
if (!isIngelogd()) {
    zetFlashBericht('waarschuwing', 'Je moet ingelogd zijn om het dashboard te bekijken.');
    stuurDoor('/inloggen.php');
}

// Haal gebruikersgegevens op
$gebruiker_id = $_SESSION['gebruiker_id'];
$rol = $_SESSION['gebruiker_rol'];

try {
    $db = maakDBVerbinding();
    
    // Haal gebruikersgegevens op
    $stmt = $db->prepare('SELECT * FROM gebruikers WHERE id = ?');
    $stmt->execute([$gebruiker_id]);
    $gebruiker = $stmt->fetch();
    
    // Haal aankomende lessen/afspraken op (voor vandaag en morgen)
    $vandaag = date('Y-m-d');
    $morgen = date('Y-m-d', strtotime('+1 day'));
    
    if ($rol === 'student') {
        $rooster_stmt = $db->prepare("
            SELECT r.*, v.naam as vak_naam, v.code as vak_code 
            FROM rooster r
            JOIN vakken v ON r.vak_id = v.id
            JOIN course_enrollments ce ON v.id = ce.course_id
            WHERE ce.student_id = ? 
            AND (DATE(r.start_tijd) = ? OR DATE(r.start_tijd) = ?)
            AND r.is_verplaatst = 0
            ORDER BY r.start_tijd ASC
        ");
        $rooster_stmt->execute([$gebruiker_id, $vandaag, $morgen]);
    } else if ($rol === 'docent') {
        $rooster_stmt = $db->prepare("
            SELECT r.*, v.naam as vak_naam, v.code as vak_code 
            FROM rooster r
            JOIN vakken v ON r.vak_id = v.id
            WHERE v.docent_id = ? 
            AND (DATE(r.start_tijd) = ? OR DATE(r.start_tijd) = ?)
            ORDER BY r.start_tijd ASC
        ");
        $rooster_stmt->execute([$gebruiker_id, $vandaag, $morgen]);
    } else if ($rol === 'coach') {
        $rooster_stmt = $db->prepare("
            SELECT a.*, 
                   g.voornaam as student_voornaam, 
                   g.achternaam as student_achternaam
            FROM afspraken a
            JOIN gebruikers g ON a.student_id = g.id
            WHERE a.coach_id = ? 
            AND (DATE(a.start_tijd) = ? OR DATE(a.start_tijd) = ?)
            ORDER BY a.start_tijd ASC
        ");
        $rooster_stmt->execute([$gebruiker_id, $vandaag, $morgen]);
    }
    
    $rooster_items = $rooster_stmt->fetchAll();
    
    // Haal aankomende deadlines op (voor de komende 7 dagen)
    $deadline_stmt = $db->prepare("
        SELECT o.*, v.naam as vak_naam, v.code as vak_code
        FROM opdrachten o
        JOIN vakken v ON o.vak_id = v.id
        JOIN course_enrollments ce ON v.id = ce.course_id
        WHERE ce.student_id = ? 
        AND o.deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
        ORDER BY o.deadline ASC
        LIMIT 5
    ");
    $deadline_stmt->execute([$gebruiker_id]);
    $deadlines = $deadline_stmt->fetchAll();
    
    // Haal recente cijfers op
    $cijfers_stmt = $db->prepare("
        SELECT c.*, v.naam as vak_naam, o.titel as opdracht_titel
        FROM cijfers c
        JOIN vakken v ON c.vak_id = v.id
        LEFT JOIN opdrachten o ON c.opdracht_id = o.id
        WHERE c.student_id = ?
        ORDER BY c.toegekend_op DESC
        LIMIT 5
    ");
    $cijfers_stmt->execute([$gebruiker_id]);
    $cijfers = $cijfers_stmt->fetchAll();
    
    // Haal recente mededelingen op
    $mededelingen_stmt = $db->prepare("
        SELECT b.*, g.voornaam, g.achternaam
        FROM berichten b
        JOIN gebruikers g ON b.afzender_id = g.id
        WHERE (b.ontvanger_id = ? OR b.ontvanger_id IS NULL)
        ORDER BY b.verzonden_op DESC
        LIMIT 5
    ");
    $mededelingen_stmt->execute([$gebruiker_id]);
    $mededelingen = $mededelingen_stmt->fetchAll();
    
} catch (PDOException $e) {
    $foutmelding = 'Er is een fout opgetreden bij het ophalen van de gegevens: ' . $e->getMessage();
}

// Functie om de juiste begroeting te tonen
function getBegroeting() {
    $uur = date('H');
    if ($uur < 12) {
        return 'Goedemorgen';
    } elseif ($uur < 18) {
        return 'Goedemiddag';
    } else {
        return 'Goedenavond';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAAM ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Basisstijlen */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --gray-color: #95a5a6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
        }
        
        /* Navigatiebalk */
        .navbar {
            background-color: var(--dark-color);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .nav-links {
            display: flex;
            gap: 1.5rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--primary-color);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-menu img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Hoofdinhoud */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .welcome-section {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .welcome-section h1 {
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .welcome-section p {
            color: var(--gray-color);
            margin-bottom: 1rem;
        }
        
        /* Dashboard grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h2 {
            font-size: 1.25rem;
            color: var(--dark-color);
        }
        
        .card-header a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .card-header a:hover {
            text-decoration: underline;
        }
        
        /* Rooster items */
        .rooster-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .rooster-item:last-child {
            border-bottom: none;
        }
        
        .rooster-tijd {
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }
        
        .rooster-vak {
            font-weight: 500;
        }
        
        .rooster-lokaal {
            color: var(--gray-color);
            font-size: 0.9rem;
        }
        
        /* Deadlines */
        .deadline-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .deadline-item:last-child {
            border-bottom: none;
        }
        
        .deadline-vak {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .deadline-titel {
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }
        
        .deadline-datum {
            color: var(--gray-color);
            font-size: 0.9rem;
        }
        
        .deadline-snel-vervalt {
            color: var(--danger-color);
            font-weight: 500;
        }
        
        /* Cijfers */
        .cijfer-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .cijfer-item:last-child {
            border-bottom: none;
        }
        
        .cijfer-info {
            flex: 1;
        }
        
        .cijfer-vak {
            font-weight: 500;
        }
        
        .cijfer-opdracht {
            color: var(--gray-color);
            font-size: 0.9rem;
        }
        
        .cijfer-waarde {
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--success-color);
        }
        
        /* Mededelingen */
        .mededeling-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .mededeling-item:last-child {
            border-bottom: none;
        }
        
        .mededeling-afzender {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .mededeling-onderwerp {
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }
        
        .mededeling-datum {
            color: var(--gray-color);
            font-size: 0.8rem;
        }
        
        /* Knoppen */
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Responsief */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Rol-specifieke kleuren */
        .role-student { color: #3498db; }
        .role-docent { color: #e74c3c; }
        .role-coach { color: #9b59b6; }
        .role-beheerder { color: #f39c12; }
    </style>
</head>
<body>
    <!-- Navigatiebalk -->
    <nav class="navbar">
        <div class="logo">CampusLife</div>
        
        <div class="nav-links">
            <a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="rooster.php"><i class="fas fa-calendar-alt"></i> Rooster</a>
            <a href="vakken.php"><i class="fas fa-book"></i> Vakken</a>
            <a href="opdrachten.php"><i class="fas fa-tasks"></i> Opdrachten</a>
            <a href="cijfers.php"><i class="fas fa-chart-bar"></i> Cijfers</a>
            <a href="berichten.php"><i class="fas fa-envelope"></i> Berichten</a>
            <?php if ($rol === 'docent' || $rol === 'beheerder'): ?>
                <a href="beheer/"><i class="fas fa-cog"></i> Beheer</a>
            <?php endif; ?>
        </div>
        
        <div class="user-menu">
            <span class="role-<?= $rol ?>"><?= ucfirst($rol) ?></span>
            <a href="profiel.php" title="Mijn profiel">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($gebruiker['voornaam'] . ' ' . $gebruiker['achternaam']) ?>" alt="Profiel">
            </a>
            <a href="uitloggen.php" title="Uitloggen"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>
    
    <div class="container">
        <!-- Welkomstbericht -->
        <section class="welcome-section">
            <h1><?= getBegroeting() ?>, <?= htmlspecialchars($gebruiker['voornaam']) ?>!</h1>
            <p>Welkom terug op je persoonlijke dashboard. Hier vind je een overzicht van je rooster, aankomende deadlines en meer.</p>
            
            <?php if ($rol === 'student'): ?>
                <p>Je hebt vandaag <?= count($rooster_items) ?> afspraken en <?= count($deadlines) ?> aankomende deadlines.</p>
            <?php elseif ($rol === 'docent'): ?>
                <p>Je hebt vandaag <?= count($rooster_items) ?> lessen gepland en <?= count($deadlines) ?> nakijkopdrachten.</p>
            <?php elseif ($rol === 'coach'): ?>
                <p>Je hebt vandaag <?= count($rooster_items) ?> afspraken met studenten.</p>
            <?php endif; ?>
        </section>
        
        <!-- Dashboard grid -->
        <div class="dashboard-grid">
            <!-- Rooster voor vandaag -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="far fa-calendar-alt"></i> Mijn rooster</h2>
                    <a href="rooster.php">Bekijk alles</a>
                </div>
                
                <?php if (!empty($rooster_items)): ?>
                    <?php foreach ($rooster_items as $item): ?>
                        <div class="rooster-item">
                            <div class="rooster-tijd">
                                <?= date('H:i', strtotime($item['start_tijd'])) ?> - <?= date('H:i', strtotime($item['eind_tijd'])) ?>
                            </div>
                            <div class="rooster-vak">
                                <?= htmlspecialchars($item['vak_naam'] ?? $item['titel']) ?>
                                <?php if (isset($item['student_voornaam'])): ?>
                                    <br><small>met <?= htmlspecialchars($item['student_voornaam'] . ' ' . $item['student_achternaam']) ?></small>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($item['vak_code']) || isset($item['lokaal'])): ?>
                                <div class="rooster-lokaal">
                                    <?= isset($item['vak_code']) ? htmlspecialchars($item['vak_code']) : '' ?>
                                    <?= isset($item['lokaal']) ? '· ' . htmlspecialchars($item['lokaal']) : '' ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Geen afspraken gepland voor vandaag of morgen.</p>
                <?php endif; ?>
                
                <div style="margin-top: 1rem;">
                    <a href="rooster.php?toevoegen" class="btn btn-outline"><i class="fas fa-plus"></i> Afspraak toevoegen</a>
                </div>
            </div>
            
            <!-- Aankomende deadlines -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="far fa-clock"></i> Aankomende deadlines</h2>
                    <a href="opdrachten.php">Bekijk alles</a>
                </div>
                
                <?php if (!empty($deadlines)): ?>
                    <?php foreach ($deadlines as $deadline): 
                        $deadline_datum = new DateTime($deadline['deadline']);
                        $nu = new DateTime();
                        $verschil = $deadline_datum->diff($nu);
                        $dagen_over = $verschil->days;
                        $is_vandaag = $deadline_datum->format('Y-m-d') === $nu->format('Y-m-d');
                        $is_morgen = $deadline_datum->format('Y-m-d') === $nu->modify('+1 day')->format('Y-m-d');
                    ?>
                        <div class="deadline-item">
                            <div class="deadline-vak"><?= htmlspecialchars($deadline['vak_naam']) ?></div>
                            <div class="deadline-titel"><?= htmlspecialchars($deadline['titel']) ?></div>
                            <div class="deadline-datum <?= $is_vandaaj ? 'deadline-snel-vervalt' : '' ?>">
                                <i class="far fa-calendar"></i> 
                                <?php 
                                    if ($is_vandaag) {
                                        echo 'Vandaag ' . $deadline_datum->format('H:i');
                                    } elseif ($is_morgen) {
                                        echo 'Morgen ' . $deadline_datum->format('H:i');
                                    } else {
                                        echo $deadline_datum->format('d-m-Y H:i');
                                    }
                                ?>
                                <?php if ($dagen_over <= 1): ?>
                                    <span class="deadline-snel-vervalt">(Nog <?= $dagen_over === 0 ? 'vandaag' : 'morgen' ?>)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Geen aankomende deadlines in de komende 7 dagen.</p>
                <?php endif; ?>
                
                <div style="margin-top: 1rem;">
                    <a href="opdrachten.php?toevoegen" class="btn btn-outline"><i class="fas fa-plus"></i> Nieuwe opdracht</a>
                </div>
            </div>
            
            <!-- Recente cijfers -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-bar"></i> Recente cijfers</h2>
                    <a href="cijfers.php">Bekijk alles</a>
                </div>
                
                <?php if (!empty($cijfers)): ?>
                    <?php foreach ($cijfers as $cijfer): ?>
                        <div class="cijfer-item">
                            <div class="cijfer-info">
                                <div class="cijfer-vak"><?= htmlspecialchars($cijfer['vak_naam']) ?></div>
                                <div class="cijfer-opdracht"><?= htmlspecialchars($cijfer['opdracht_titel'] ?? 'Overige beoordeling') ?></div>
                            </div>
                            <div class="cijfer-waarde"><?= number_format($cijfer['cijfer'], 1, ',', '') ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nog geen cijfers beschikbaar.</p>
                <?php endif; ?>
                
                <div style="margin-top: 1rem;">
                    <a href="cijfers.php" class="btn btn-outline"><i class="fas fa-chart-line"></i> Bekijk voortgang</a>
                </div>
            </div>
            
            <!-- Mededelingen -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="far fa-bell"></i> Mededelingen</h2>
                    <a href="berichten.php">Bekijk alles</a>
                </div>
                
                <?php if (!empty($mededelingen)): ?>
                    <?php foreach ($mededelingen as $mededeling): 
                        $verzonden_op = new DateTime($mededeling['verzonden_op']);
                    ?>
                        <div class="mededeling-item">
                            <div class="mededeling-afzender">
                                <?= htmlspecialchars($mededeling['voornaam'] . ' ' . $mededeling['achternaam']) ?>
                            </div>
                            <div class="mededeling-onderwerp">
                                <?= !empty($mededeling['onderwerp']) ? htmlspecialchars($mededeling['onderwerp']) : '(Geen onderwerp)' ?>
                            </div>
                            <div class="mededeling-datum">
                                <?= $verzonden_op->format('d-m-Y H:i') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Geen nieuwe mededelingen.</p>
                <?php endif; ?>
                
                <div style="margin-top: 1rem;">
                    <a href="berichten.php?nieuw" class="btn btn-outline"><i class="fas fa-pen"></i> Nieuwe mededeling</a>
                </div>
            </div>
        </div>
        
        <!-- Snelle acties -->
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h2><i class="fas fa-bolt"></i> Snelle acties</h2>
            </div>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; padding: 1rem 0;">
                <a href="rooster.php?toevoegen" class="btn"><i class="fas fa-plus"></i> Nieuwe afspraak</a>
                <a href="opdrachten.php?toevoegen" class="btn"><i class="fas fa-tasks"></i> Nieuwe opdracht</a>
                <a href="berichten.php?nieuw" class="btn"><i class="fas fa-envelope"></i> Bericht sturen</a>
                <?php if ($rol === 'docent'): ?>
                    <a href="cijfers.php?toevoegen" class="btn"><i class="fas fa-chart-bar"></i> Cijfer invoeren</a>
                <?php endif; ?>
                <?php if ($rol === 'coach'): ?>
                    <a href="afspraken.php?toevoegen" class="btn"><i class="fas fa-user-graduate"></i> Coachgesprek inplannen</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer style="text-align: center; padding: 2rem; color: #7f8c8d; font-size: 0.9rem;">
        <p>CampusLife &copy; <?= date('Y') ?> - Een simpele maar krachtige studentenportal</p>
    </footer>
</body>
</html>
