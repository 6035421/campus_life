<?php
require_once 'config.php';

$foutmelding = '';
$succesmelding = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $foutmelding = 'Vul een e-mailadres in';
    } else {
        try {
            $db = maakDBVerbinding();
            
            // Controleer of het e-mailadres bestaat
            $stmt = $db->prepare('SELECT id, voornaam, achternaam FROM gebruikers WHERE email = ?');
            $stmt->execute([$email]);
            $gebruiker = $stmt->fetch();
            
            if ($gebruiker) {
                // Genereer een reset token
                $token = bin2hex(random_bytes(32));
                $vervalt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Geldig voor 1 uur
                
                // Sla de token op in de database
                $updateStmt = $db->prepare('UPDATE gebruikers SET reset_token = ?, reset_vervalt = ? WHERE id = ?');
                $updateStmt->execute([$token, $vervalt, $gebruiker['id']]);
                
                // Stuur een e-mail met de resetlink
                $resetLink = SITE_URL . '/wachtwoord_resetten.php?token=' . $token;
                $onderwerp = 'Wachtwoord opnieuw instellen - ' . SITE_NAAM;
                
                $bericht = 'Beste ' . htmlspecialchars($gebruiker['voornaam']) . ",\n\n";
                $bericht .= "Je hebt aangegeven je wachtwoord te willen resetten. Klik op de onderstaande link om een nieuw wachtwoord in te stellen.\n\n";
                $bericht .= $resetLink . "\n\n";
                $bericht .= "Deze link is 1 uur geldig.\n\n";
                $bericht .= "Met vriendelijke groet,\nHet " . SITE_NAAM . " team";
                
                // Verzend de e-mail (in productie zou je hier een e-mail functie aanroepen)
                // mail($email, $onderwerp, $bericht, 'From: ' . MAIL_VAN);
                
                // Toon een bevestiging (in plaats van de e-mail te sturen in deze demo)
                $succesmelding = 'Er is een e-mail verzonden met instructies om je wachtwoord opnieuw in te stellen. Controleer je inbox (en spam-map).';
                $succesmelding .= "<div style='margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px;'>";
                $succesmelding .= "<strong>Demo:</strong> In een echte omgeving zou er nu een e-mail worden verzonden naar " . htmlspecialchars($email) . " met de volgende link:<br>";
                $succesmelding .= "<a href='$resetLink' target='_blank'>$resetLink</a>";
                $succesmelding .= "</div>";
            } else {
                // Toon een algemene melding om te voorkomen dat we bestaande e-mails onthullen
                $succesmelding = 'Als het e-mailadres bestaat in ons systeem, ontvang je een e-mail met instructies om je wachtwoord opnieuw in te stellen.';
            }
        } catch (PDOException $e) {
            $foutmelding = 'Er is een fout opgetreden. Probeer het later opnieuw.';
            // Log de fout voor de beheerder
            error_log('Wachtwoord reset fout: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wachtwoord vergeten - <?= SITE_NAAM ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            max-width: 500px;
            width: 100%;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        h1 {
            margin-top: 0;
            color: #2c3e50;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #2980b9;
        }
        
        .terug-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #3498db;
            text-decoration: none;
        }
        
        .terug-link:hover {
            text-decoration: underline;
        }
        
        .foutmelding {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 12px;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            border-left: 4px solid #d32f2f;
        }
        
        .succesmelding {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            border-left: 4px solid #2e7d32;
        }
        
        .demo-notice {
            margin-top: 2rem;
            padding: 1rem;
            background-color: #e3f2fd;
            border-left: 4px solid #1976d2;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo"><?= SITE_NAAM ?></div>
        
        <h1>Wachtwoord vergeten</h1>
        
        <?php if ($foutmelding): ?>
            <div class="foutmelding">
                <i class="fas fa-exclamation-circle"></i> <?= $foutmelding ?>
            </div>
        <?php endif; ?>
        
        <?php if ($succesmelding): ?>
            <div class="succesmelding">
                <i class="fas fa-check-circle"></i> <?= $succesmelding ?>
            </div>
            
            <a href="inloggen.php" class="terug-link">Terug naar inloggen</a>
        <?php else: ?>
            <p>Vul je e-mailadres in en we sturen je een link om je wachtwoord opnieuw in te stellen.</p>
            
            <form method="POST" action="wachtwoord_vergeten.php">
                <div class="form-group">
                    <label for="email">E-mailadres</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <button type="submit">
                    <i class="fas fa-paper-plane"></i> Verstuur resetlink
                </button>
            </form>
            
            <a href="inloggen.php" class="terug-link">
                <i class="fas fa-arrow-left"></i> Terug naar inloggen
            </a>
            
            <div class="demo-notice">
                <strong>Let op:</strong> Dit is een demo. In een echte omgeving zou er een e-mail worden verzonden met een resetlink.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
