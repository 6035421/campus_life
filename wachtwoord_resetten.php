<?php
require_once 'config.php';

$foutmelding = '';
$succesmelding = '';
$token = $_GET['token'] ?? '';
$tokenGeldig = false;

// Valideer het token
if (!empty($token)) {
    try {
        $db = maakDBVerbinding();
        $nu = date('Y-m-d H:i:s');
        
        $stmt = $db->prepare('SELECT id FROM gebruikers WHERE reset_token = ? AND reset_vervalt > ?');
        $stmt->execute([$token, $nu]);
        $gebruiker = $stmt->fetch();
        
        $tokenGeldig = (bool)$gebruiker;
    } catch (PDOException $e) {
        $foutmelding = 'Er is een fout opgetreden. Probeer het later opnieuw.';
        error_log('Wachtwoord reset validatie fout: ' . $e->getMessage());
    }
}

// Verwerk het formulier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenGeldig) {
    $wachtwoord = $_POST['wachtwoord'] ?? '';
    $wachtwoord_bevestigen = $_POST['wachtwoord_bevestigen'] ?? '';
    
    if (strlen($wachtwoord) < 8) {
        $foutmelding = 'Het wachtwoord moet minimaal 8 tekens lang zijn.';
    } elseif ($wachtwoord !== $wachtwoord_bevestigen) {
        $foutmelding = 'De wachtwoorden komen niet overeen.';
    } else {
        try {
            $wachtwoord_hash = hashWachtwoord($wachtwoord);
            
            $updateStmt = $db->prepare('UPDATE gebruikers SET wachtwoord = ?, reset_token = NULL, reset_vervalt = NULL WHERE reset_token = ?');
            $updateStmt->execute([$wachtwoord_hash, $token]);
            
            $succesmelding = 'Je wachtwoord is succesvol gewijzigd. Je kunt nu inloggen met je nieuwe wachtwoord.';
            $tokenGeldig = false; // Toon het formulier niet meer
        } catch (PDOException $e) {
            $foutmelding = 'Er is een fout opgetreden bij het opslaan van je nieuwe wachtwoord.';
            error_log('Wachtwoord opslaan fout: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wachtwoord opnieuw instellen - <?= SITE_NAAM ?></title>
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
        
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .wachtwoord-hint {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
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
        
        .ongeldig-token {
            text-align: center;
            padding: 2rem;
        }
        
        .ongeldig-token i {
            font-size: 3rem;
            color: #d32f2f;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo"><?= SITE_NAAM ?></div>
        
        <?php if (!$tokenGeldig && empty($succesmelding)): ?>
            <div class="ongeldig-token">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Ongeldige of verlopen link</h2>
                <p>Deze wachtwoordresetlink is ongeldig of verlopen. Vraag een nieuwe link aan.</p>
                <a href="wachtwoord_vergeten.php" class="terug-link">
                    <i class="fas fa-arrow-left"></i> Naar wachtwoord vergeten
                </a>
            </div>
        <?php elseif (!empty($succesmelding)): ?>
            <div class="succesmelding">
                <i class="fas fa-check-circle"></i> <?= $succesmelding ?>
            </div>
            
            <a href="inloggen.php" class="terug-link">
                <i class="fas fa-sign-in-alt"></i> Naar inloggen
            </a>
        <?php else: ?>
            <h1>Nieuw wachtwoord instellen</h1>
            
            <?php if ($foutmelding): ?>
                <div class="foutmelding">
                    <i class="fas fa-exclamation-circle"></i> <?= $foutmelding ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="wachtwoord">Nieuw wachtwoord</label>
                    <input type="password" id="wachtwoord" name="wachtwoord" required>
                    <div class="wachtwoord-hint">Minimaal 8 tekens</div>
                </div>
                
                <div class="form-group">
                    <label for="wachtwoord_bevestigen">Bevestig nieuw wachtwoord</label>
                    <input type="password" id="wachtwoord_bevestigen" name="wachtwoord_bevestigen" required>
                </div>
                
                <button type="submit">
                    <i class="fas fa-save"></i> Wachtwoord opslaan
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
