<?php
require_once 'config.php';

// Controleer of de gebruiker is ingelogd
if (!isIngelogd()) {
    zetFlashBericht('waarschuwing', 'Je moet ingelogd zijn om het rooster te bekijken.');
    stuurDoor('/inloggen.php');
}

$gebruiker_id = $_SESSION['gebruiker_id'];
$rol = $_SESSION['gebruiker_rol'];

// Haal de geselecteerde week op (standaard huidige week)
$huidige_week = date('W');
$huidig_jaar = date('Y');
$week = isset($_GET['week']) ? (int)$_GET['week'] : $huidige_week;
$jaar = isset($_GET['jaar']) ? (int)$_GET['jaar'] : $huidig_jaar;

// Bereken de datum van maandag van de geselecteerde week
$maandag = new DateTime();
$maandag->setISODate($jaar, $week, 1);

// Genereer een array met de dagen van de week
$dagen = [];
for ($i = 0; $i < 7; $i++) {
    $dag = clone $maandag;
    $dag->modify("+$i days");
    $dagen[] = $dag;
}

try {
    $db = maakDBVerbinding();
    
    // Haal het rooster op voor de geselecteerde week
    $start_datum = $maandag->format('Y-m-d');
    $eind_datum = clone $maandag;
    $eind_datum->modify('+6 days');
    $eind_datum = $eind_datum->format('Y-m-d');    
    
    if ($rol === 'student') {
        $stmt = $db->prepare("
            SELECT r.*, v.naam as vak_naam, v.code as vak_code, g.voornaam as docent_voornaam, g.achternaam as docent_achternaam
            FROM rooster r
            JOIN vakken v ON r.vak_id = v.id
            JOIN evenement_inschrijvingen ce ON v.id = ce.evenement_id
            JOIN gebruikers g ON v.docent_id = g.id
            WHERE ce.gebruiker_id = ?
            AND DATE(r.start_tijd) BETWEEN ? AND ?
            ORDER BY r.start_tijd ASC
        ");
        $stmt->execute([$gebruiker_id, $start_datum, $eind_datum]);
    } elseif ($rol === 'docent') {
        $stmt = $db->prepare("
            SELECT r.*, v.naam as vak_naam, v.code as vak_code, 
                   g.voornaam as student_voornaam, g.achternaam as student_achternaam
            FROM rooster r
            JOIN vakken v ON r.vak_id = v.id
            JOIN evenement_inschrijvingen ce ON v.id = ce.evenement_id
            JOIN gebruikers g ON ce.gebruiker_id = g.id
            WHERE v.docent_id = ?
            AND DATE(r.start_tijd) BETWEEN ? AND ?
            ORDER BY r.start_tijd ASC
        ");
        $stmt->execute([$gebruiker_id, $start_datum, $eind_datum]);
    } else {
        // Voor coaches en andere rollen
        $stmt = $db->prepare("
            SELECT a.*, g.voornaam, g.achternaam
            FROM afspraken a
            JOIN gebruikers g ON a.student_id = g.id
            WHERE a.coach_id = ?
            AND DATE(a.start_tijd) BETWEEN ? AND ?
            ORDER BY a.start_tijd ASC
        
```php
        ");
        $stmt->execute([$gebruiker_id, $start_datum, $eind_datum]);
    }
    
    $rooster_items = $stmt->fetchAll();
    
    // Groepeer de roosteritems per dag
    $rooster_per_dag = [];
    foreach ($rooster_items as $item) {
        $datum = date('Y-m-d', strtotime($item['start_tijd']));
        if (!isset($rooster_per_dag[$datum])) {
            $rooster_per_dag[$datum] = [];
        }
        $rooster_per_dag[$datum][] = $item;
    }
    
} catch (PDOException $e) {
    $foutmelding = 'Er is een fout opgetreden bij het ophalen van het rooster: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn Rooster - <?= SITE_NAAM ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Basisstijlen */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
            background-color: #f5f7fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-title {
            margin: 0;
            color: #2c3e50;
        }
        
        /* Weeknavigatie */
        .week-navigatie {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .week-navigatie button {
            background: none;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        
        .week-navigatie button:hover {
            background-color: #f0f0f0;
        }
        
        .huidige-week {
            font-weight: 500;
            min-width: 200px;
            text-align: center;
        }
        
        /* Rooster */
        .rooster-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .dagen-header {
            display: grid;
            grid-template-columns: 100px repeat(7, 1fr);
            background-color: #2c3e50;
            color: white;
        }
        
        .tijd-kolom {
            background-color: #f8f9fa;
            border-right: 1px solid #eee;
            padding: 0.5rem;
            text-align: center;
            font-weight: 500;
        }
        
        .dag-header {
            padding: 0.75rem;
            text-align: center;
            border-right: 1px solid rgba(255,255,255,0.1);
        }
        
        .dag-header:last-child {
            border-right: none;
        }
        
        .dag-datum {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .dag-naam {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .vandaag .dag-datum {
            color: #3498db;
        }
        
        .rooster-body {
            display: grid;
            grid-template-columns: 100px repeat(7, 1fr);
            min-height: 600px;
        }
        
        .tijden-kolom {
            display: grid;
            grid-template-rows: repeat(12, 1fr);
            border-right: 1px solid #eee;
            background-color: #f8f9fa;
        }
        
        .tijd-vak {
            border-bottom: 1px solid #eee;
            padding: 0.5rem;
            font-size: 0.8rem;
            color: #666;
            text-align: right;
            padding-right: 0.75rem;
        }
        
        .dagen-kolommen {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            grid-column: 2 / 9;
        }
        
        .dag-kolom {
            border-right: 1px solid #eee;
            position: relative;
        }
        
        .dag-kolom:last-child {
            border-right: none;
        }
        
        .tijd-vakje {
            border-bottom: 1px solid #f0f0f0;
            height: 50px;
            position: relative;
        }
        
        .afspraak {
            position: absolute;
            left: 0.25rem;
            right: 0.25rem;
            border-radius: 4px;
            padding: 0.5rem;
            font-size: 0.8rem;
            overflow: hidden;
            color: white;
            z-index: 1;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .afspraak:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .afspraak-tijd {
            font-weight: 500;
            margin-bottom: 0.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .afspraak-titel {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .afspraak-locatie {
            font-size: 0.75rem;
            opacity: 0.9;
        }
        
        .afspraak-type-1 { background-color: #3498db; }
        .afspraak-type-2 { background-color: #2ecc71; }
        .afspraak-type-3 { background-color: #9b59b6; }
        .afspraak-type-4 { background-color: #e67e22; }
        .afspraak-type-5 { background-color: #e74c3c; }
        
        /* Responsief */
        @media (max-width: 992px) {
            .dagen-header, .rooster-body {
                grid-template-columns: 80px repeat(7, 1fr);
            }
            
            .dagen-kolommen {
                grid-column: 2 / 9;
            }
            
            .tijden-kolom {
                grid-template-rows: repeat(24, 1fr);
            }
            
            .tijd-vak {
                height: 25px;
                font-size: 0.7rem;
                padding-right: 0.5rem;
            }
            
            .dag-datum {
                font-size: 1.2rem;
            }
            
            .dag-naam {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0.5rem;
            }
            
            .dagen-header, .rooster-body {
                grid-template-columns: 60px repeat(7, 1fr);
            }
            
            .dag-header {
                padding: 0.5rem 0.25rem;
            }
            
            .dag-datum {
                font-size: 1rem;
            }
            
            .dag-naam {
                font-size: 0.7rem;
            }
            
            .afspraak {
                font-size: 0.7rem;
                padding: 0.25rem;
            }
            
            .afspraak-titel {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .week-navigatie {
                width: 100%;
                justify-content: space-between;
            }
            
            .dagen-header, .rooster-body {
                grid-template-columns: 40px repeat(7, 1fr);
            }
            
            .tijd-vak {
                font-size: 0.6rem;
                padding: 0.25rem 0.25rem 0.25rem 0;
            }
            
            .dag-datum {
                font-size: 0.9rem;
            }
            
            .dag-naam {
                display: none;
            }
        }
        
        /* Lege staat */
        .lege-staat {
            grid-column: 1 / -1;
            padding: 3rem 1rem;
            text-align: center;
            color: #666;
        }
        
        .lege-staat i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
            display: block;
        }
        
        /* Actieknoppen */
        .actie-knoppen {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }
        
        .btn:hover {
            background-color: #2980b9;
            color: white;
        }
        
        .btn-outline {
            background: none;
            border: 1px solid #3498db;
            color: #3498db;
        }
        
        .btn-outline:hover {
            background-color: #f0f8ff;
        }
        
        .btn i {
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Mijn Rooster</h1>
            
            <div class="week-navigatie">
                <a href="?week=<?= $week - 1 ?>&jaar=<?= $week == 1 ? $jaar - 1 : $jaar ?>" class="btn btn-outline">
                    <i class="fas fa-chevron-left"></i> Vorige week
                </a>
                
                <div class="huidige-week">
                    Week <?= $week ?>, <?= $maandag->format('d M') ?> - <?= $maandag->modify('+6 days')->format('d M Y') ?>
                </div>
                
                <a href="?week=<?= $week + 1 ?>&jaar=<?= $week == 52 ? $jaar + 1 : $jaar ?>" class="btn btn-outline">
                    Volgende week <i class="fas fa-chevron-right"></i>
                </a>
                
                <a href="?week=<?= $huidige_week ?>&jaar=<?= $huidig_jaar ?>" class="btn btn-outline">
                    <i class="fas fa-calendar-day"></i> Huidige week
                </a>
            </div>
            
            <div class="actie-knoppen">
                <?php if ($rol === 'docent' || $rol === 'beheerder'): ?>
                    <a href="rooster_toevoegen.php" class="btn">
                        <i class="fas fa-plus"></i> Les toevoegen
                    </a>
                <?php endif; ?>
                
                <a href="rooster_export.php?week=<?= $week ?>" class="btn btn-outline">
                    <i class="fas fa-download"></i> Exporteren
                </a>
                
                <a href="rooster_print.php?week=<?= $week ?>" class="btn btn-outline" target="_blank">
                    <i class="fas fa-print"></i> Afdrukken
                </a>
            </div>
        </div>
        
        <?php if (isset($foutmelding)): ?>
            <div class="foutmelding">
                <i class="fas fa-exclamation-circle"></i> <?= $foutmelding ?>
            </div>
        <?php endif; ?>
        
        <div class="rooster-container">
            <div class="dagen-header">
                <div class="tijd-kolom">Tijd</div>
                <?php foreach ($dagen as $index => $dag): ?>
                    <?php 
                    $isVandaag = $dag->format('Y-m-d') === date('Y-m-d');
                    $dagKlasse = $isVandaag ? 'vandaag' : '';
                    ?>
                    <div class="dag-header <?= $dagKlasse ?>">
                        <div class="dag-datum"><?= $dag->format('j') ?></div>
                        <div class="dag-naam"><?= ucfirst(@strftime('%A', $dag->getTimestamp())) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="rooster-body">
                <div class="tijden-kolom">
                    <?php for ($uur = 8; $uur < 20; $uur++): ?>
                        <div class="tijd-vak"><?= sprintf('%02d:00', $uur) ?></div>
                    <?php endfor; ?>
                </div>
                
                <div class="dagen-kolommen">
                    <?php foreach ($dagen as $dag): ?>
                        <?php 
                        $datum = $dag->format('Y-m-d');
                        $dagItems = $rooster_per_dag[$datum] ?? [];
                        $isVandaag = $datum === date('Y-m-d');
                        $dagKlasse = $isVandaag ? 'vandaag' : '';
                        ?>
                        <div class="dag-kolom <?= $dagKlasse ?>">
                            <?php if (empty($dagItems)): ?>
                                <div style="height: 100%; display: flex; align-items: center; justify-content: center; color: #999;">
                                    Geen afspraken
                                </div>
                            <?php else: ?>
                                <?php foreach ($dagItems as $item): ?>
                                    <?php
                                    $startTijd = strtotime($item['start_tijd']);
                                    $eindTijd = strtotime($item['eind_tijd']);
                                    
                                    // Bereken positie en hoogte in pixels (elke rij is 50px = 1 uur)
                                    $startUur = (int)date('H', $startTijd);
                                    $startMinuut = (int)date('i', $startTijd);
                                    $eindUur = (int)date('H', $eindTijd);
                                    $eindMinuut = (int)date('i', $eindTijd);
                                    
                                    $startPos = (($startUur - 8) * 60 + $startMinuut) * (50 / 60);
                                    $eindPos = (($eindUur - 8) * 60 + $eindMinuut) * (50 / 60);
                                    $hoogte = $eindPos - $startPos;
                                    
                                    // Bepaal het type afspraak voor kleurcodering
                                    $type = isset($item['vak_id']) ? (($item['vak_id'] % 5) + 1) : 1;
                                    
                                    // Toon de afspraak
                                    ?>
                                    <div class="afspraak afspraak-type-<?= $type ?>" 
                                         style="top: <?= $startPos ?>px; height: <?= $hoogte ?>px;"
                                         title="<?= htmlspecialchars($item['vak_naam'] ?? $item['titel'] ?? 'Afspraak') ?>">
                                        <div class="afspraak-tijd">
                                            <span><?= date('H:i', $startTijd) ?> - <?= date('H:i', $eindTijd) ?></span>
                                        </div>
                                        <div class="afspraak-titel">
                                            <?= htmlspecialchars($item['vak_naam'] ?? $item['titel'] ?? 'Afspraak') ?>
                                        </div>
                                        <?php if (isset($item['vak_code']) || isset($item['lokaal'])): ?>
                                            <div class="afspraak-locatie">
                                                <?= isset($item['vak_code']) ? htmlspecialchars($item['vak_code']) : '' ?>
                                                <?= (isset($item['vak_code']) && isset($item['lokaal'])) ? '·' : '' ?>
                                                <?= isset($item['lokaal']) ? htmlspecialchars($item['lokaal']) : '' ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($item['docent_voornaam'])): ?>
                                            <div class="afspraak-docent" style="font-size: 0.7rem; opacity: 0.9; margin-top: 0.25rem;">
                                                <?= htmlspecialchars($item['docent_voornaam'] . ' ' . $item['docent_achternaam']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <?php if (empty($rooster_items)): ?>
            <div class="lege-staat">
                <i class="far fa-calendar-alt"></i>
                <h3>Geen afspraken deze week</h3>
                <p>Er zijn geen afspraken gevonden voor de geselecteerde week.</p>
                <?php if ($rol === 'docent' || $rol === 'beheerder'): ?>
                    <a href="rooster_toevoegen.php" class="btn">
                        <i class="fas fa-plus"></i> Voeg je eerste les toe
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Voeg hier eventuele JavaScript toe voor interactiviteit
        document.addEventListener('DOMContentLoaded', function() {
            // Maak afspraken klikbaar
            document.querySelectorAll('.afspraak').forEach(afspraak => {
                afspraak.addEventListener('click', function() {
                    // Hier kun je een modal of pagina openen met details
                    console.log('Afspraak geklikt');
                });
            });
            
            // Toon een bevestiging bij het verwijderen van een afspraak
            document.querySelectorAll('.verwijder-knop').forEach(knop => {
                knop.addEventListener('click', function(e) {
                    if (!confirm('Weet je zeker dat je deze afspraak wilt verwijderen?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
