<?php
require_once 'config.php';

$foutmelding = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gebruikersnaam = $_POST['gebruikersnaam'] ?? '';
    $wachtwoord = $_POST['wachtwoord'] ?? '';
    
    if (empty($gebruikersnaam) || empty($wachtwoord)) {
        $foutmelding = 'Vul zowel gebruikersnaam als wachtwoord in.';
    } else {
        try {
            $db = maakDBVerbinding();
            $stmt = $db->prepare('SELECT * FROM gebruikers WHERE gebruikersnaam = :gebruikersnaam LIMIT 1');
            $stmt->execute([':gebruikersnaam' => $gebruikersnaam]);
            $gebruiker = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($gebruiker && password_verify($wachtwoord, $gebruiker['wachtwoord'])) {
                // Inloggen gelukt
                $_SESSION['gebruiker_id'] = $gebruiker['id'];
                $_SESSION['gebruiker_naam'] = $gebruiker['voornaam'] . ' ' . $gebruiker['achternaam'];
                $_SESSION['gebruiker_rol'] = $gebruiker['rol'];
                
                // Update laatste login
                $updateStmt = $db->prepare('UPDATE gebruikers SET laatste_login = NOW() WHERE id = :id');
                $updateStmt->execute([':id' => $gebruiker['id']]);
                
                stuurDoor('/index.php');
            } else {
                $foutmelding = 'Ongeldige inloggegevens.';
            }
        } catch (PDOException $e) {
            $foutmelding = 'Er is een fout opgetreden bij het inloggen: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen - <?= SITE_NAAM ?></title>
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
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
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
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #2980b9;
        }
        .foutmelding {
            background-color: #ffdddd;
            color: #d32f2f;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border-left: 4px solid #d32f2f;
        }
        .registreren {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Inloggen</h1>
        
        <?php if ($foutmelding): ?>
            <div class="foutmelding"><?= $foutmelding ?></div>
        <?php endif; ?>
        
        <form method="POST" action="inloggen.php">
            <div class="form-group">
                <label for="gebruikersnaam">Gebruikersnaam:</label>
                <input type="text" id="gebruikersnaam" name="gebruikersnaam" required>
            </div>
            
            <div class="form-group">
                <label for="wachtwoord">Wachtwoord:</label>
                <input type="password" id="wachtwoord" name="wachtwoord" required>
            </div>
            
            <button type="submit">Inloggen</button>
        </form>
        
        <div class="registreren">
            <p>Nog geen account? <a href="registreren.php">Registreer hier</a></p>
            <p><a href="wachtwoord_vergeten.php">Wachtwoord vergeten?</a></p>
        </div>
    </div>
</body>
</html>
