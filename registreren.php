<?php
require_once 'config.php';

// Initialiseer variabelen
$foutmeldingen = [];
$succesmelding = '';
$gebruikersnaam = '';
$email = '';
$voornaam = '';
$achternaam = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gebruikersnaam = trim($_POST['gebruikersnaam'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $voornaam = trim($_POST['voornaam'] ?? '');
    $achternaam = trim($_POST['achternaam'] ?? '');
    $wachtwoord = $_POST['wachtwoord'] ?? '';
    $wachtwoord_bevestigen = $_POST['wachtwoord_bevestigen'] ?? '';
    $rol = 'student'; // Standaardrol is student
    
    // Validatie
    if (empty($gebruikersnaam)) {
        $foutmeldingen[] = 'Gebruikersnaam is verplicht';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $foutmeldingen[] = 'Vul een geldig e-mailadres in';
    }
    
    if (empty($voornaam)) {
        $foutmeldingen[] = 'Voornaam is verplicht';
    }
    
    if (empty($achternaam)) {
        $foutmeldingen[] = 'Achternaam is verplicht';
    }
    
    if (strlen($wachtwoord) < 8) {
        $foutmeldingen[] = 'Wachtwoord moet minimaal 8 tekens lang zijn';
    }
    
    if ($wachtwoord !== $wachtwoord_bevestigen) {
        $foutmeldingen[] = 'Wachtwoorden komen niet overeen';
    }
    
    // Controleer of gebruikersnaam of e-mail al bestaat
    if (empty($foutmeldingen)) {
        try {
            $db = maakDBVerbinding();
            
            // Controleer gebruikersnaam
            $stmt = $db->prepare('SELECT id FROM gebruikers WHERE gebruikersnaam = ?');
            $stmt->execute([$gebruikersnaam]);
            if ($stmt->fetch()) {
                $foutmeldingen[] = 'Deze gebruikersnaam is al in gebruik';
            }
            
            // Controleer e-mail
            $stmt = $db->prepare('SELECT id FROM gebruikers WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $foutmeldingen[] = 'Dit e-mailadres is al geregistreerd';
            }
            
            // Als er geen fouten zijn, voeg gebruiker toe
            if (empty($foutmeldingen)) {
                $wachtwoord_hash = hashWachtwoord($wachtwoord);
                
                $stmt = $db->prepare('INSERT INTO gebruikers (gebruikersnaam, wachtwoord, email, voornaam, achternaam, rol) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$gebruikersnaam, $wachtwoord_hash, $email, $voornaam, $achternaam, $rol]);
                
                $succesmelding = 'Account succesvol aangemaakt! Je kunt nu inloggen.';
                
                // Leeg de velden na succesvolle registratie
                $gebruikersnaam = $email = $voornaam = $achternaam = '';
            }
            
        } catch (PDOException $e) {
            $foutmeldingen[] = 'Er is een fout opgetreden bij het registreren: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registreren - <?= SITE_NAAM ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-top: 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #2ecc71;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #27ae60;
        }
        .foutmelding {
            background-color: #ffdddd;
            color: #d32f2f;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border-left: 4px solid #d32f2f;
        }
        .succesmelding {
            background-color: #ddffdd;
            color: #2e7d32;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border-left: 4px solid #2e7d32;
        }
        .terugknop {
            display: inline-block;
            margin-top: 15px;
            color: #3498db;
            text-decoration: none;
        }
        .terugknop:hover {
            text-decoration: underline;
        }
        .wachtwoord-hint {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registreren</h1>
        
        <?php if (!empty($foutmeldingen)): ?>
            <div class="foutmelding">
                <strong>Er zijn een aantal fouten opgetreden:</strong>
                <ul style="margin: 5px 0 0 20px; padding: 0;">
                    <?php foreach ($foutmeldingen as $fout): ?>
                        <li><?= $fout ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($succesmelding): ?>
            <div class="succesmelding">
                <?= $succesmelding ?>
                <a href="inloggen.php" class="terugknop">← Terug naar inloggen</a>
            </div>
        <?php else: ?>
            <form method="POST" action="registreren.php">
                <div class="form-group">
                    <label for="gebruikersnaam">Gebruikersnaam: *</label>
                    <input type="text" id="gebruikersnaam" name="gebruikersnaam" value="<?= htmlspecialchars($gebruikersnaam) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">E-mailadres: *</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="voornaam">Voornaam: *</label>
                    <input type="text" id="voornaam" name="voornaam" value="<?= htmlspecialchars($voornaam) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="achternaam">Achternaam: *</label>
                    <input type="text" id="achternaam" name="achternaam" value="<?= htmlspecialchars($achternaam) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="wachtwoord">Wachtwoord: *</label>
                    <input type="password" id="wachtwoord" name="wachtwoord" required>
                    <div class="wachtwoord-hint">Minimaal 8 tekens</div>
                </div>
                
                <div class="form-group">
                    <label for="wachtwoord_bevestigen">Bevestig wachtwoord: *</label>
                    <input type="password" id="wachtwoord_bevestigen" name="wachtwoord_bevestigen" required>
                </div>
                
                <button type="submit">Registreren</button>
                
                <div style="text-align: center; margin-top: 15px;">
                    <a href="inloggen.php" class="terugknop">← Terug naar inloggen</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
