<?php
require_once 'config.php';

// Controleer of de gebruiker is ingelogd
if (!isIngelogd()) {
    zetFlashBericht('waarschuwing', 'Je moet ingelogd zijn om opdrachten te bekijken.');
    stuurDoor('/inloggen.php');
}

$gebruiker_id = $_SESSION['gebruiker_id'];
$rol = $_SESSION['gebruiker_rol'];

// Haal de geselecteerde weergave op (actueel of afgerond)
$weergave = isset($_GET['weergave']) && $_GET['weergave'] === 'afgerond' ? 'afgerond' : 'actueel';

// Haal eventuele filters op
$vak_id = isset($_GET['vak']) ? (int)$_GET['vak'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Sorteeropties
$sorteer_op = isset($_GET['sorteer']) ? $_GET['sorteer'] : 'deadline_oplopend';

// Paginering
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$per_pagina = 10;
$offset = ($pagina - 1) * $per_pagina;

try {
    $db = maakDBVerbinding();
    
    // Basisquery voor het ophalen van opdrachten
    $query = "SELECT o.*, v.naam as vak_naam, v.code as vak_code, ";
    $query .= "(SELECT COUNT(*) FROM inleveringen i WHERE i.opdracht_id = o.id AND i.student_id = ?) as ingeleverd, ";
    $query .= "(SELECT i.cijfer FROM inleveringen i WHERE i.opdracht_id = o.id AND i.student_id = ? LIMIT 1) as cijfer ";
    $query .= "FROM opdrachten o ";
    $query .= "JOIN vakken v ON o.vak_id = v.id ";
    $query .= "JOIN evenement_inschrijvingen ce ON v.id = ce.evenement_id ";
    $query .= "WHERE ce.gebruiker_id = ? ";
    
    $params = [$gebruiker_id, $gebruiker_id, $gebruiker_id];
    
    // Voeg filters toe aan de query
    if ($weergave === 'actueel') {
        $query .= "AND (o.deadline >= CURDATE() OR (SELECT COUNT(*) FROM inleveringen i WHERE i.opdracht_id = o.id AND i.student_id = ?) = 0) ";
        $params[] = $gebruiker_id;
    } else {
        $query .= "AND o.deadline < CURDATE() AND (SELECT COUNT(*) FROM inleveringen i WHERE i.opdracht_id = o.id AND i.student_id = ?) > 0 ";
        $params[] = $gebruiker_id;
    }
    
    if ($vak_id) {
        $query .= "AND o.vak_id = ? ";
        $params[] = $vak_id;
    }
    
    if ($status === 'ingeleverd') {
        $query .= "AND (SELECT COUNT(*) FROM inleveringen i WHERE i.opdracht_id = o.id AND i.student_id = ?) > 0 ";
        $params[] = $gebruiker_id;
    } elseif ($status === 'niet_ingeleverd') {
        $query .= "AND (SELECT COUNT(*) FROM inleveringen i WHERE i.opdracht_id = o.id AND i.student_id = ?) = 0 ";
        $params[] = $gebruiker_id;
    }
    
    // Sortering toevoegen
    switch ($sorteer_op) {
        case 'deadline_oplopend':
            $query .= "ORDER BY o.deadline ASC ";
            break;
        case 'deadline_aflopend':
            $query .= "ORDER BY o.deadline DESC ";
            break;
        case 'vak_oplopend':
            $query .= "ORDER BY v.naam ASC ";
            break;
        case 'vak_aflopend':
            $query .= "ORDER BY v.naam DESC ";
            break;
        default:
            $query .= "ORDER BY o.deadline ASC ";
    }
    
    // Maak een kopie van de query voor het tellen (zonder ORDER BY en LIMIT)
    $count_query = "SELECT COUNT(*) as totaal FROM (" . preg_replace('/ORDER BY .*/', '', $query) . ") as count_table";
    $totaal_stmt = $db->prepare($count_query);
    
    // Bind parameters voor de count query
    foreach ($params as $key => $value) {
        $totaal_stmt->bindValue(is_int($key) ? $key + 1 : $key, $value);
    }
    
    $totaal_stmt->execute();
    $totaal = $totaal_stmt->fetch()['totaal'];
    $totaal_paginas = ceil($totaal / $per_pagina);
    
    // Voeg sortering en paginering toe aan de hoofdquery
    $query .= " LIMIT ? OFFSET ?";
    
    // Voer de uiteindelijke query uit met genummerde parameters
    $stmt = $db->prepare($query);
    
    // Voeg alle oorspronkelijke parameters toe
    foreach ($params as $key => $value) {
        $stmt->bindValue($key + 1, $value);
    }
    
    // Voeg de paginering parameters toe
    $stmt->bindValue(count($params) + 1, (int)$per_pagina, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);
    
    // Voer de query uit
    $stmt->execute();
    $opdrachten = $stmt->fetchAll();
    
    // Haal de vakken op voor het filter
    $vakken_stmt = $db->prepare("
        SELECT v.id, v.naam, v.code 
        FROM vakken v
        JOIN evenement_inschrijvingen ce ON v.id = ce.evenement_id
        WHERE ce.gebruiker_id = ?
        ORDER BY v.naam
    ");
    $vakken_stmt->execute([$gebruiker_id]);
    $vakken = $vakken_stmt->fetchAll();
    
} catch (PDOException $e) {
    $foutmelding = 'Er is een fout opgetreden bij het ophalen van de opdrachten: ' . $e->getMessage();
}

// Bepaal de paginakop
$pagina_titel = $weergave === 'actueel' ? 'Actuele opdrachten' : 'Afgeronde opdrachten';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pagina_titel ?> - <?= SITE_NAAM ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .opdracht-kaart {
            border-left: 4px solid var(--primary-color);
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .opdracht-kaart:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .opdracht-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .opdracht-titel {
            font-size: 1.25rem;
            margin: 0;
            color: var(--dark-color);
        }
        
        .opdracht-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 0.75rem;
        }
        
        .opdracht-vak {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #e9f5ff;
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .opdracht-deadline {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .opdracht-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-niet-ingeleverd {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-ingeleverd {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-te-laat {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-beoordeeld {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .opdracht-beschrijving {
            color: var(--text-light);
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .opdracht-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            margin-top: 1rem;
        }
        
        .opdracht-cijfer {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .cijfer-goed {
            color: var(--success-color);
        }
        
        .cijfer-voldoende {
            color: #ffc107;
        }
        
        .cijfer-onvoldoende {
            color: var(--danger-color);
        }
        
        .filter-balk {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
        }
        
        .filter-groep {
            display: flex;
            flex-direction: column;
            min-width: 200px;
        }
        
        .filter-label {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
            color: var(--text-light);
        }
        
        .weergave-schakelaar {
            display: flex;
            background-color: #f0f2f5;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .weergave-optie {
            padding: 0.5rem 1rem;
            cursor: pointer;
            text-align: center;
            flex: 1;
            transition: background-color 0.2s;
        }
        
        .weergave-optie.actief {
            background-color: var(--primary-color);
            color: white;
        }
        
        .weergave-optie:not(.actief):hover {
            background-color: #e0e6ed;
        }
        
        .lege-staat {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-light);
        }
        
        .lege-staat i {
            font-size: 3rem;
            color: #e0e6ed;
            margin-bottom: 1rem;
            display: block;
        }
        
        .paginering {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .paginering a, 
        .paginering span {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            text-decoration: none;
            font-weight: 500;
        }
        
        .paginering a {
            background-color: white;
            color: var(--dark-color);
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }
        
        .paginering a:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .paginering .huidige {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .paginering .puntjes {
            color: var(--text-light);
            pointer-events: none;
        }
        
        @media (max-width: 768px) {
            .opdracht-header {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .opdracht-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .filter-balk {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <?php 
    $paginaTitel = $pagina_titel;
    include 'includes/header.php'; 
    ?>
    
    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><?= $pagina_titel ?></h1>
            <a href="opdracht_toevoegen.php" class="btn">
                <i class="fas fa-plus"></i> Nieuwe opdracht
            </a>
        </div>
        
        <div class="weergave-schakelaar">
            <a href="?weergave=actueel<?= $vak_id ? '&vak=' . $vak_id : '' ?>" 
               class="weergave-optie <?= $weergave === 'actueel' ? 'actief' : '' ?>">
                Actuele opdrachten
            </a>
            <a href="?weergave=afgerond<?= $vak_id ? '&vak=' . $vak_id : '' ?>" 
               class="weergave-optie <?= $weergave === 'afgerond' ? 'actief' : '' ?>">
                Afgeronde opdrachten
            </a>
        </div>
        
        <div class="filter-balk">
            <div class="filter-groep">
                <label for="vak-filter" class="filter-label">Filter op vak</label>
                <select id="vak-filter" class="form-control" onchange="window.location.href=this.value">
                    <option value="?weergave=<?= $weergave ?>">Alle vakken</option>
                    <?php foreach ($vakken as $vak): ?>
                        <option value="?weergave=<?= $weergave ?>&vak=<?= $vak['id'] ?>" 
                                <?= $vak_id == $vak['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vak['naam']) ?> (<?= htmlspecialchars($vak['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-groep">
                <label for="status-filter" class="filter-label">Filter op status</label>
                <select id="status-filter" class="form-control" onchange="window.location.href=this.value">
                    <option value="?weergave=<?= $weergave ?><?= $vak_id ? '&vak=' . $vak_id : '' ?>">Alle statussen</option>
                    <option value="?weergave=<?= $weergave ?><?= $vak_id ? '&vak=' . $vak_id : '' ?>&status=ingeleverd" 
                            <?= $status === 'ingeleverd' ? 'selected' : '' ?>>
                        Ingeleverd
                    </option>
                    <option value="?weergave=<?= $weergave ?><?= $vak_id ? '&vak=' . $vak_id : '' ?>&status=niet_ingeleverd" 
                            <?= $status === 'niet_ingeleverd' ? 'selected' : '' ?>>
                        Niet ingeleverd
                    </option>
                </select>
            </div>
            
            <div class="filter-groep">
                <label for="sorteer-op" class="filter-label">Sorteren op</label>
                <select id="sorteer-op" class="form-control" onchange="window.location.href=this.value">
                    <option value="?weergave=<?= $weergave ?><?= $vak_id ? '&vak=' . $vak_id : '' ?><?= $status ? '&status=' . $status : '' ?>&sorteer=deadline_oplopend" 
                            <?= $sorteer_op === 'deadline_oplopend' ? 'selected' : '' ?>>
                        Deadline (oplopend)
                    </option>
                    <option value="?weergave=<?= $weergave ?><?= $vak_id ? '&vak=' . $vak_id : '' ?><?= $status ? '&status=' . $status : '' ?>&sorteer=deadline_aflopend" 
                            <?= $sorteer_op === 'deadline_aflopend' ? 'selected' : '' ?>>
                        Deadline (aflopend)
                    </option>
                    <option value="?weergave=<?= $weergave ?><?= $vak_id ? '&vak=' . $vak_id : '' ?><?= $status ? '&status=' . $status : '' ?>&sorteer=vak_oplopend" 
                            <?= $sorteer_op === 'vak_oplopend' ? 'selected' : '' ?>>
                        Vak (A-Z)
                    </option>
                    <option value="?weergave=<?= $weergave ?><?= $vak_id ? '&vak=' . $vak_id : '' ?><?= $status ? '&status=' . $status : '' ?>&sorteer=vak_aflopend" 
                            <?= $sorteer_op === 'vak_aflopend' ? 'selected' : '' ?>>
                        Vak (Z-A)
                    </option>
                </select>
            </div>
        </div>
        
        <?php if (isset($foutmelding)): ?>
            <div class="foutmelding">
                <i class="fas fa-exclamation-circle"></i> <?= $foutmelding ?>
            </div>
        <?php elseif (empty($opdrachten)): ?>
            <div class="lege-staat">
                <i class="far fa-clipboard"></i>
                <h3>Geen opdrachten gevonden</h3>
                <p>Er zijn momenteel geen opdrachten beschikbaar die aan je zoekcriteria voldoen.</p>
                <?php if ($weergave === 'actueel' && !$vak_id && !$status): ?>
                    <a href="opdracht_toevoegen.php" class="btn">
                        <i class="fas fa-plus"></i> Voeg je eerste opdracht toe
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($opdrachten as $opdracht): ?>
                <?php
                // Bepaal de status van de opdracht
                $deadline = new DateTime($opdracht['deadline']);
                $vandaag = new DateTime();
                $isTeLaat = $deadline < $vandaag && !$opdracht['ingeleverd'];
                $isIngeleverd = (bool)$opdracht['ingeleverd'];
                $isBeoordeeld = $opdracht['cijfer'] !== null;
                
                // Bepaal de statusklasse
                $statusKlasse = '';
                $statusTekst = '';
                
                if ($isBeoordeeld) {
                    $statusKlasse = 'status-beoordeeld';
                    $statusTekst = 'Beoordeeld';
                } elseif ($isTeLaat) {
                    $statusKlasse = 'status-te-laat';
                    $statusTekst = 'Te laat';
                } elseif ($isIngeleverd) {
                    $statusKlasse = 'status-ingeleverd';
                    $statusTekst = 'Ingeleverd';
                } else {
                    $statusKlasse = 'status-niet-ingeleverd';
                    $statusTekst = 'Nog in te leveren';
                }
                
                // Bepaal de cijferklasse
                $cijferKlasse = '';
                if ($opdracht['cijfer'] !== null) {
                    if ($opdracht['cijfer'] >= 5.5) {
                        $cijferKlasse = 'cijfer-goed';
                    } elseif ($opdracht['cijfer'] >= 5.0) {
                        $cijferKlasse = 'cijfer-voldoende';
                    } else {
                        $cijferKlasse = 'cijfer-onvoldoende';
                    }
                }
                ?>
                <div class="card opdracht-kaart">
                    <div class="opdracht-header">
                        <h2 class="opdracht-titel">
                            <a href="opdracht_bekijken.php?id=<?= $opdracht['id'] ?>">
                                <?= htmlspecialchars($opdracht['titel']) ?>
                            </a>
                        </h2>
                        <span class="opdracht-status <?= $statusKlasse ?>">
                            <i class="fas fa-<?= $isIngeleverd ? 'check-circle' : ($isTeLaat ? 'exclamation-circle' : 'clock') ?>"></i>
                            <?= $statusTekst ?>
                        </span>
                    </div>
                    
                    <div class="opdracht-meta">
                        <span class="opdracht-vak">
                            <i class="fas fa-book"></i>
                            <?= htmlspecialchars($opdracht['vak_naam']) ?> (<?= htmlspecialchars($opdracht['vak_code']) ?>)
                        </span>
                        
                        <span class="opdracht-deadline">
                            <i class="far fa-calendar-alt"></i>
                            Deadline: <?= strftime('%A %d %B %Y', strtotime($opdracht['deadline'])) ?> om <?= date('H:i', strtotime($opdracht['deadline'])) ?> uur
                            <?php if ($isTeLaat): ?>
                                <span style="color: var(--danger-color);">(Verstreken)</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($opdracht['beschrijving'])): ?>
                        <div class="opdracht-beschrijving">
                            <?= nl2br(htmlspecialchars(mb_strimwidth($opdracht['beschrijving'], 0, 250, '...'))) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="opdracht-footer">
                        <div>
                            <?php if ($opdracht['ingeleverd']): ?>
                                <span>Ingeleverd op <?= strftime('%d %B %Y', strtotime($opdracht['ingeleverd_op'])) ?></span>
                            <?php else: ?>
                                <span>Nog niet ingeleverd</span>
                            <?php endif; ?>
                            
                            <?php if ($opdracht['cijfer'] !== null): ?>
                                <span class="opdracht-cijfer <?= $cijferKlasse ?>">
                                    Cijfer: <?= number_format($opdracht['cijfer'], 1, ',', ' ') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <a href="opdracht_bekijken.php?id=<?= $opdracht['id'] ?>" class="btn btn-outline">
                                <i class="fas fa-eye"></i> Bekijken
                            </a>
                            
                            <?php if (!$opdracht['ingeleverd'] && $weergave === 'actueel'): ?>
                                <a href="opdracht_inleveren.php?id=<?= $opdracht['id'] ?>" class="btn">
                                    <i class="fas fa-upload"></i> Inleveren
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($totaal_paginas > 1): ?>
                <div class="paginering">
                    <?php if ($pagina > 1): ?>
                        <a href="?pagina=1<?= $weergave ? '&weergave=' . $weergave : '' ?><?= $vak_id ? '&vak=' . $vak_id : '' ?><?= $status ? '&status=' . $status : '' ?><?= $sorteer_op ? '&sorteer=' . $sorteer_op : '' ?>">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?pagina=<?= $pagina - 1 ?><?= $weergave ? '&weergave=' . $weergave : '' ?><?= $vak_id ? '&vak=' . $vak_id : '' ?><?= $status ? '&status=' . $status : '' ?><?= $sorteer_op ? '&sorteer=' . $sorteer_op : '' ?>">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $pagina - 2);
                    $eind = min($start + 4, $totaal_paginas);
                    
                    if ($eind - $start < 4) {
                        $start = max(1, $eind - 4);
                    }
                    
                    if ($start > 1): ?>
                        <span class="puntjes">...</span>
                    <?php endif; ?>
                    
                    <?php for ($i = $start; $i <= $eind; $i++): ?>
                        <?php if ($i == $pagina): ?>
                            <span class="huidige"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?pagina=<?= $i ?><?= $weergave ? '&weergave=' . $weergave : '' ?><?= $vak_id ? '&vak=' . $vak_id : '' ?><?= $status ? '&status=' . $status : '' ?><?= $sorteer_op ? '&sorteer=' . $sorteer_op : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($eind < $totaal_paginas): ?>
                        <span class="puntjes">...</span>
                    <?php endif; ?>
                    
                    <?php if ($pagina < $totaal_paginas): ?>
                        <a href="?pagina=<?= $pagina + 1 ?><?= $weergave ? '&weergave=' . $weergave : '' ?><?= $vak_id ? '&vak=' . $vak_id : '' ?><?= $status ? '&status=' . $status : '' ?><?= $sorteer_op ? '&sorteer=' . $sorteer_op : '' ?>">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?pagina=<?= $totaal_paginas ?><?= $weergave ? '&weergave=' . $weergave : '' ?><?= $vak_id ? '&vak=' . $vak_id : '' ?><?= $status ? '&status=' . $status : '' ?><?= $sorteer_op ? '&sorteer=' . $sorteer_op : '' ?>">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Voeg hier eventuele JavaScript toe voor interactiviteit
        document.addEventListener('DOMContentLoaded', function() {
            // Voeg hier eventuele JavaScript toe die moet worden uitgevoerd na het laden van de pagina
        });
    </script>
</body>
</html>
