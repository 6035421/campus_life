<?php
session_start();
include '../database/db.php';

$message = null; $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Voer een geldig e-mailadres in.';
  } else {
    // Placeholder: hier zou je een reset-token genereren en e-mail versturen
    // Bewaar token in password_resets tabel (indien aanwezig)
    $message = 'Als dit e-mailadres bekend is, zijn de instructies verzonden.';
  }
}
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CampusLife - Wachtwoord vergeten</title>
    <link rel="stylesheet" href="../style.css" />
  </head>
  <body>
    <main class="container" style="padding-top:56px;">
      <section class="hero">
        <div class="subtext">Toegang herstellen</div>
        <h1 class="headline">Wachtwoord vergeten</h1>
      </section>
      <section class="section">
        <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="post">
          <label for="email">E-mailadres</label>
          <input id="email" type="email" name="email" placeholder="student1@example.com" required />
          <div class="actions mt-2">
            <button class="btn primary" type="submit">Stuur instructies</button>
            <a class="btn" href="login.php">Terug naar inloggen</a>
          </div>
        </form>
      </section>
    </main>
  </body>
</html>
