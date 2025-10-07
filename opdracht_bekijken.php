<?php
require_once 'config.php';

// Controleer of de gebruiker is ingelogd
if (!isIngelogd()) {
    zetFlashBericht('waarschuwing', 'Je moet ingelogd zijn om opdrachten te bekijken.');
    stuurDoor('/inloggen.php');
}

$gebruiker_id = $_SESSION['gebruiker_id'];
$rol = $_SESSION['gebruiker_rol'];

// Haal de opdracht-ID op uit de URL
$opdracht_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$opdracht_id) {
    zetFlashBericht('fout', 'Geen geldige opdracht opgegeven.');
    stuurDoor('/opdrachten.php');
}

try {
    $db = maakDBVerbinding();
    
    // Haal de opdrachtgegevens op
    $query = "SELECT o.*, v.naam as vak_naam, v.code as vak_code, ";
    $query .= "v.docent_id, g.voornaam as docent_voornaam, g.achternaam as docent_achternaam, ";
    $query .= "(SELECT COUNT(*) FROM inleveringen i WHERE i.opdracht_id = o.id AND i.student_id = ?) as ingeleverd, ";
    $query .= "(SELECT i.cijfer FROM inleveringen i WHERE i.opdracht_id = o.id AND i.student_id = ? LIMIT 1) as cijfer, ";
    $query .= "(SELECT i.inlever_datum FROM inleveringen i WHERE i.opdracht_id = o.id AND i.student_id = ? LIMIT 1) as inlever_datum ";
    $query .= "FROM opdrachten o ";
    $query .= "JOIN vakken v ON o.vak_id = v.id ";
    $query .= "JOIN gebruikers g ON v.docent_id = g.id ";
    $query .= "JOIN course_enrollments ce ON v.id = ce.course_id ";
    $query .= "WHERE o.id = ? AND ce.student_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$gebruiker_id, $gebruiker_id, $gebruiker_id, $opdracht_id, $gebruiker_id]);
    $opdracht = $stmt->fetch();
    
    if (!$opdracht) {
        zetFlashBericht('fout', 'Je hebt geen toegang tot deze opdracht of de opdracht bestaat niet.');
        stuurDoor('/opdrachten.php');
    }
    
    // Haal eventuele bijlagen op
    $bijlagen_stmt = $db->prepare("SELECT * FROM bijlagen WHERE opdracht_id = ?");
    $bijlagen_stmt->execute([$opdracht_id]);
    $bijlagen = $bijlagen_stmt->fetchAll();
    
    // Haal eventuele inzending op
    $inzending = null;
    $ingeleverde_bijlagen = [];
    $feedback = null;
    
    if ($opdracht['ingeleverd']) {
        $inzending_stmt = $db->prepare("
            SELECT i.*, g.voornaam, g.achternaam 
            FROM inleveringen i 
            JOIN gebruikers g ON i.student_id = g.id 
            WHERE i.opdracht_id = ? AND i.student_id = ?
        ");
        $inzending_stmt->execute([$opdracht_id, $gebruiker_id]);
        $inzending = $inzending_stmt->fetch();
        
        // Haal ingeleverde bijlagen op
        $ingeleverde_bijlagen_stmt = $db->prepare("SELECT * FROM bijlagen WHERE inlevering_id = ?");
        $ingeleverde_bijlagen_stmt->execute([$inzending['id']]);
        $ingeleverde_bijlagen = $ingeleverde_bijlagen_stmt->fetchAll();
        
        // Haal eventuele feedback op
        $feedback_stmt = $db->prepare("
            SELECT f.*, g.voornaam, g.achternaam 
            FROM feedback f 
            JOIN gebruikers g ON f.docent_id = g.id 
            WHERE f.inlevering_id = ?
            ORDER BY f.datum DESC
        ");
        $feedback_stmt->execute([$inzending['id']]);
        $feedback = $feedback_stmt->fetchAll();
    }
    
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
    
} catch (PDOException $e) {
    $foutmelding = 'Er is een fout opgetreden bij het ophalen van de opdracht: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($opdracht['titel']) ?> - <?= SITE_NAMAAM ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .opdracht-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .opdracht-titel {
            font-size: 1.75rem;
            margin: 0;
            color: var(--dark-color);
            line-height: 1.2;
        }
        
        .opdracht-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.9rem;
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
        
        .opdracht-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .meta-label {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        .meta-waarde {
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .opdracht-vak {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: #e9f5ff;
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
        }
        
        .opdracht-vak:hover {
            background-color: #d8ecff;
        }
        
        .opdracht-beschrijving {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
            line-height: 1.7;
        }
        
        .opdracht-beschrijving h2,
        .opdracht-beschrijving h3,
        .opdracht-beschrijving h4 {
            margin-top: 1.5em;
            margin-bottom: 0.75em;
            color: var(--dark-color);
        }
        
        .opdracht-beschrijving p {
            margin-bottom: 1em;
        }
        
        .opdracht-beschrijving ul,
        .opdracht-beschrijving ol {
            margin-bottom: 1em;
            padding-left: 1.5em;
        }
        
        .opdracht-beschrijving li {
            margin-bottom: 0.5em;
        }
        
        .opdracht-bijlagen {
            margin-bottom: 2rem;
        }
        
        .bijlagen-lijst {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .bijlage-kaart {
            display: flex;
            align-items: center;
            padding: 1rem;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-decoration: none;
            color: var(--text-color);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .bijlage-kaart:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            color: var(--primary-color);
        }
        
        .bijlage-icoon {
            font-size: 2rem;
            margin-right: 1rem;
            color: var(--primary-color);
        }
        
        .bijlage-info {
            flex: 1;
            min-width: 0;
        }
        
        .bijlage-naam {
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.25rem;
        }
        
        .bijlage-grootte {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        .sectie-titel {
            font-size: 1.25rem;
            margin: 2rem 0 1rem;
            color: var(--dark-color);
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .inlever-formulier {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }
        
        .bestand-upload {
            border: 2px dashed var(--border-color);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        
        .bestand-upload:hover {
            border-color: var(--primary-color);
        }
        
        .bestand-upload i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: block;
        }
        
        .bestand-upload p {
            margin: 0;
            color: var(--text-light);
        }
        
        #bestanden-lijst {
            margin-bottom: 1.5rem;
        }
        
        .bestand-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
        }
        
        .bestand-item i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }
        
        .bestand-item .bestand-naam {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .bestand-verwijderen {
            background: none;
            border: none;
            color: var(--danger-color);
            cursor: pointer;
            padding: 0.25rem;
            margin-left: 0.5rem;
            font-size: 1.1rem;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .ingeleverd-sectie {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }
        
        .ingeleverd-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .ingeleverd-titel {
            margin: 0;
            font-size: 1.25rem;
            color: var(--dark-color);
        }
        
        .ingeleverd-datum {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .ingeleverd-beschrijving {
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }
        
        .cijfer-sectie {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .cijfer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .cijfer-titel {
            margin: 0;
            font-size: 1.1rem;
            color: var(--dark-color);
        }
        
        .cijfer-waarde {
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            margin: 1.5rem 0;
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
        
        .feedback-lijst {
            margin-top: 2rem;
        }
        
        .feedback-item {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .feedback-afzender {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .feedback-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .feedback-naam {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .feedback-rol {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        .feedback-datum {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        
        .feedback-inhoud {
            line-height: 1.7;
        }
        
        .feedback-inhoud p:last-child {
            margin-bottom: 0;
        }
        
        .geen-feedback {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .actie-knoppen {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .opdracht-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .opdracht-meta {
                flex-direction: column;
                gap: 1rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
                text-align: center;
            }
            
            .actie-knoppen {
                flex-direction: column;
            }
            
            .actie-knoppen .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php 
    $paginaTitel = $opdracht['titel'];
    include 'includes/header.php'; 
    ?>
    
    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><?= htmlspecialchars($opdracht['titel']) ?></h1>
            <span class="opdracht-status <?= $statusKlasse ?>">
                <i class="fas fa-<?= $isIngeleverd ? 'check-circle' : ($isTeLaat ? 'exclamation-circle' : 'clock') ?>"></i>
                <?= $statusTekst ?>
            </span>
        </div>
        
        <div class="opdracht-meta">
            <div class="meta-item">
                <span class="meta-label">Vak</span>
                <a href="vakken.php?id=<?= $opdracht['vak_id'] ?>" class="opdracht-vak">
                    <i class="fas fa-book"></i>
                    <?= htmlspecialchars($opdracht['vak_naam']) ?> (<?= htmlspecialchars($opdracht['vak_code']) ?>)
                </a>
            </div>
            
            <div class="meta-item">
                <span class="meta-label">Deadline</span>
                <div class="meta-waarde">
                    <i class="far fa-calendar-alt"></i>
                    <span>
                        <?= strftime('%A %d %B %Y', strtotime($opdracht['deadline'])) ?> om <?= date('H:i', strtotime($opdracht['deadline'])) ?> uur
                        <?php if ($isTeLaat): ?>
                            <span style="color: var(--danger-color);">(Verstreken)</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <div class="meta-item">
                <span class="meta-label">Docent</span>
                <div class="meta-waarde">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <?= htmlspecialchars($opdracht['docent_voornaam'] . ' ' . $opdracht['docent_achternaam']) ?>
                </div>
            </div>
            
            <?php if ($isIngeleverd): ?>
                <div class="meta-item">
                    <span class="meta-label">Ingeleverd op</span>
                    <div class="meta-waarde">
                        <i class="fas fa-check-circle"></i>
                        <?= strftime('%A %d %B %Y', strtotime($opdracht['inlever_datum'])) ?> om <?= date('H:i', strtotime($opdracht['inlever_datum'])) ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($isBeoordeeld): ?>
                <div class="meta-item">
                    <span class="meta-label">Cijfer</span>
                    <div class="meta-waarde">
                        <i class="fas fa-chart-bar"></i>
                        <span class="cijfer-waarde <?= $cijferKlasse ?>">
                            <?= number_format($opdracht['cijfer'], 1, ',', ' ') ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="opdracht-beschrijving">
            <h2>Opdrachtbeschrijving</h2>
            <?= nl2br(htmlspecialchars($opdracht['beschrijving'])) ?>
        </div>
        
        <?php if (!empty($bijlagen)): ?>
            <div class="opdracht-bijlagen">
                <h3 class="sectie-titel">Bijlagen</h3>
                <div class="bijlagen-lijst">
                    <?php foreach ($bijlagen as $bijlage): ?>
                        <a href="bestand_downloaden.php?id=<?= $bijlage['id'] ?>" class="bijlage-kaart" download>
                            <div class="bijlage-icoon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="bijlage-info">
                                <div class="bijlage-naam"><?= htmlspecialchars($bijlage['bestandsnaam']) ?></div>
                                <div class="bijlage-grootte"><?= formaatBestandsgrootte($bijlage['bestandsgrootte']) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($isIngeleverd): ?>
            <!-- Ingeleverde opdracht weergeven -->
            <div class="ingeleverd-sectie">
                <div class="ingeleverd-header">
                    <h2 class="ingeleverd-titel">Jouw inzending</h2>
                    <div class="ingeleverd-datum">
                        Ingeleverd op <?= strftime('%d %B %Y', strtotime($opdracht['inlever_datum'])) ?> om <?= date('H:i', strtotime($opdracht['inlever_datum'])) ?>
                    </div>
                </div>
                
                <?php if (!empty($inzending['opmerking'])): ?>
                    <div class="ingeleverd-beschrijving">
                        <h4>Jouw opmerking:</h4>
                        <?= nl2br(htmlspecialchars($inzending['opmerking'])) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($ingeleverde_bijlagen)): ?>
                    <div class="opdracht-bijlagen">
                        <h4>Ingeleverde bestanden:</h4>
                        <div class="bijlagen-lijst">
                            <?php foreach ($ingeleverde_bijlagen as $bestand): ?>
                                <a href="bestand_downloaden.php?id=<?= $bestand['id'] ?>" class="bijlage-kaart" download>
                                    <div class="bijlage-icoon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="bijlage-info">
                                        <div class="bijlage-naam"><?= htmlspecialchars($bestand['bestandsnaam']) ?></div>
                                        <div class="bijlage-grootte"><?= formaatBestandsgrootte($bestand['bestandsgrootte']) ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($isBeoordeeld): ?>
                    <div class="cijfer-sectie">
                        <div class="cijfer-header">
                            <h3 class="cijfer-titel">Beoordeling</h3>
                            <span class="opdracht-status status-beoordeeld">
                                <i class="fas fa-check-circle"></i> Beoordeeld
                            </span>
                        </div>
                        
                        <div class="cijfer-waarde <?= $cijferKlasse ?>">
                            <?= number_format($opdracht['cijfer'], 1, ',', ' ') ?>
                        </div>
                        
                        <?php if (!empty($feedback)): ?>
                            <div class="feedback-lijst">
                                <h4>Feedback</h4>
                                <?php foreach ($feedback as $fb): ?>
                                    <div class="feedback-item">
                                        <div class="feedback-header">
                                            <div class="feedback-afzender">
                                                <div class="feedback-avatar">
                                                    <?= strtoupper(substr($fb['voornaam'], 0, 1) . substr($fb['achternaam'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="feedback-naam"><?= htmlspecialchars($fb['voornaam'] . ' ' . $fb['achternaam']) ?></div>
                                                    <div class="feedback-rol">Docent</div>
                                                </div>
                                            </div>
                                            <div class="feedback-datum">
                                                <?= strftime('%d %B %Y', strtotime($fb['datum'])) ?>
                                            </div>
                                        </div>
                                        <div class="feedback-inhoud">
                                            <?= nl2br(htmlspecialchars($fb['inhoud'])) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="geen-feedback">
                                <i class="far fa-comment-dots" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                <p>Er is nog geen feedback beschikbaar voor deze inzending.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="actie-knoppen">
                    <a href="opdracht_aanpassen.php?id=<?= $opdracht_id ?>" class="btn btn-outline">
                        <i class="fas fa-edit"></i> Inzending aanpassen
                    </a>
                    <a href="opdracht_intrekken.php?id=<?= $opdracht_id ?>" class="btn btn-outline" 
                       onclick="return confirm('Weet je zeker dat je je inzending wilt intrekken? Je kunt daarna opnieuw inleveren tot de deadline.');">
                        <i class="fas fa-undo"></i> Inzending intrekken
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Inleverformulier weergeven -->
            <div class="inlever-formulier">
                <h3 class="sectie-titel">Opdracht inleveren</h3>
                
                <?php if ($isTeLaat): ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> De deadline voor deze opdracht is verstreken. 
                        Je kunt nog steeds inleveren, maar dit kan gevolgen hebben voor je beoordeling.
                    </div>
                <?php endif; ?>
                
                <form action="opdracht_inleveren_verwerken.php" method="POST" enctype="multipart/form-data" id="inlever-form">
                    <input type="hidden" name="opdracht_id" value="<?= $opdracht_id ?>">
                    
                    <div class="form-group">
                        <label for="opmerking" class="form-label">Opmerking (optioneel)</label>
                        <textarea id="opmerking" name="opmerking" class="form-control" rows="3" 
                                  placeholder="Voeg eventueel een opmerking toe aan je inzending..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Bestanden toevoegen</label>
                        <div class="bestand-upload" id="bestand-dropzone">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Sleep bestanden hiernaartoe of klik om te bladeren</p>
                            <input type="file" id="bestanden" name="bestanden[]" multiple style="display: none;">
                        </div>
                        
                        <div id="bestanden-lijst">
                            <!-- Hier komen de geselecteerde bestanden te staan -->
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="opdrachten.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Terug naar overzicht
                        </a>
                        <button type="submit" class="btn">
                            <i class="fas fa-paper-plane"></i> Inleveren
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <div class="actie-knoppen">
            <a href="opdrachten.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Terug naar overzicht
            </a>
            
            <?php if ($rol === 'docent' && $opdracht['docent_id'] == $gebruiker_id): ?>
                <a href="beheer/opdracht_bewerken.php?id=<?= $opdracht_id ?>" class="btn">
                    <i class="fas fa-edit"></i> Opdracht bewerken
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropzone = document.getElementById('bestand-dropzone');
            const fileInput = document.getElementById('bestanden');
            const fileList = document.getElementById('bestanden-lijst');
            const form = document.getElementById('inlever-form');
            
            // Toon het bestandsdialoogvenster wanneer er op de dropzone wordt geklikt
            dropzone.addEventListener('click', function() {
                fileInput.click();
            });
            
            // Voeg visuele feedback toe bij het slepen van bestanden
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                dropzone.style.borderColor = 'var(--primary-color)';
                dropzone.style.backgroundColor = 'rgba(52, 152, 219, 0.1)';
            }
            
            function unhighlight() {
                dropzone.style.borderColor = 'var(--border-color)';
                dropzone.style.backgroundColor = 'transparent';
            }
            
            // Verwerk gedropte bestanden
            dropzone.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(files);
            }
            
            // Verwerk geselecteerde bestanden
            fileInput.addEventListener('change', function() {
                handleFiles(this.files);
            });
            
            // Toon geselecteerde bestanden
            function handleFiles(files) {
                [...files].forEach(file => {
                    if (file.size > 10 * 1024 * 1024) { // 10MB limiet
                        alert(`Bestand '${file.name}' is te groot. Maximaal 10MB toegestaan.`);
                        return;
                    }
                    
                    const fileItem = document.createElement('div');
                    fileItem.className = 'bestand-item';
                    fileItem.innerHTML = `
                        <i class="fas fa-file"></i>
                        <span class="bestand-naam">${file.name}</span>
                        <span class="bestand-grootte">(${formatFileSize(file.size)})</span>
                        <button type="button" class="bestand-verwijderen" data-name="${file.name}">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    fileList.appendChild(fileItem);
                });
                
                // Update de bestandsinput met de geselecteerde bestanden
                updateFileInput();
            }
            
            // Verwijder een bestand uit de lijst
            fileList.addEventListener('click', function(e) {
                if (e.target.closest('.bestand-verwijderen')) {
                    const fileItem = e.target.closest('.bestand-item');
                    fileItem.remove();
                    updateFileInput();
                }
            });
            
            // Update de bestandsinput met de geselecteerde bestanden
            function updateFileInput() {
                const dataTransfer = new DataTransfer();
                const fileItems = fileList.querySelectorAll('.bestand-item');
                
                fileItems.forEach(item => {
                    const fileName = item.querySelector('.bestand-naam').textContent;
                    const fileSize = item.querySelector('.bestand-grootte').textContent;
                    
                    // Zoek het bestand in de oorspronkelijke file input
                    for (let i = 0; i < fileInput.files.length; i++) {
                        if (fileInput.files[i].name === fileName) {
                            dataTransfer.items.add(fileInput.files[i]);
                            break;
                        }
                    }
                });
                
                // Vervang de bestanden in de input
                fileInput.files = dataTransfer.files;
                
                // Update de dropzone tekst op basis van het aantal bestanden
                updateDropzoneText();
            }
            
            // Update de tekst in de dropzone op basis van het aantal bestanden
            function updateDropzoneText() {
                const fileCount = fileList.children.length;
                const dropzoneText = dropzone.querySelector('p');
                
                if (fileCount === 0) {
                    dropzoneText.textContent = 'Sleep bestanden hiernaartoe of klik om te bladeren';
                } else {
                    dropzoneText.textContent = `${fileCount} bestand(en) geselecteerd. Klik om toe te voegen of te wijzigen.`;
                }
            }
            
            // Formatteer bestandsgrootte
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
            // Valideer het formulier voordat het wordt verzonden
            if (form) {
                form.addEventListener('submit', function(e) {
                    const fileCount = fileList.children.length;
                    
                    if (fileCount === 0) {
                        e.preventDefault();
                        alert('Selecteer ten minste één bestand om in te leveren.');
                        return false;
                    }
                    
                    if (fileCount > 5) {
                        e.preventDefault();
                        alert('Je kunt maximaal 5 bestanden tegelijk uploaden.');
                        return false;
                    }
                    
                    // Controleer of de totale bestandsgrootte niet te groot is (bijv. 50MB)
                    let totalSize = 0;
                    for (let i = 0; i < fileInput.files.length; i++) {
                        totalSize += fileInput.files[i].size;
                    }
                    
                    if (totalSize > 50 * 1024 * 1024) { // 50MB limiet
                        e.preventDefault();
                        alert('De totale bestandsgrootte mag niet groter zijn dan 50MB.');
                        return false;
                    }
                    
                    // Toon een laadindicator
                    const submitButton = form.querySelector('button[type="submit"]');
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Bezig met uploaden...';
                    
                    return true;
                });
            }
        });
    </script>
</body>
</html>
