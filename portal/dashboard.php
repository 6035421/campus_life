<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}

// Set default role if not set
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'student'; // Default role
}

// Include role-specific dashboard if it exists
$role = strtolower($_SESSION['role']);
$dashboardFile = __DIR__ . "/dashboards/{$role}.php";

// Default to student dashboard if role-specific dashboard doesn't exist
if (!file_exists($dashboardFile)) {
    $dashboardFile = __DIR__ . "/dashboards/student.php";
}
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CampusLife - Dashboard</title>
    <link rel="stylesheet" href="../style.css" />
  </head>
  <body>
    <header class="appbar">
      <div class="nav container">
        <div class="brand">
          <div class="logo"></div>
          <div>CampusLife</div>
        </div>
        <nav class="nav-links">
          <a href="rooster.php">📅 Rooster</a>
          <a href="cijfers.php">📊 Mijn cijfers</a>
          <a href="opdrachten.php">📝 Opdrachten</a>
          <a href="events.php">🎉 Events</a>
          <a href="meldingen.php">🔔 Meldingen</a>
          <a href="projectgroep.php">👥 Projectgroep</a>
          <a class="btn danger" href="../login/logout.php">Uitloggen</a>
        </nav>
      </div>
    </header>

    <main class="container">
      <section class="hero">
        <div class="subtext">Welkom terug</div>
        <h1 class="headline">Hallo, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Gebruiker'); ?> 👋</h1>
        <div class="subtext mt-2">Rol: <?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'student')); ?></div>
      </section>

      <section class="section">
        <?php include $dashboardFile; ?>

          <div class="card">
            <h3>Mijn cijfers</h3>
            <div class="meta">Blijf op de hoogte van je voortgang</div>
            <div class="actions mt-3">
              <a class="btn" href="cijfers.php">Bekijk cijfers</a>
            </div>
          </div>

          <div class="card">
            <h3>Opdrachten</h3>
            <div class="meta">Lever opdrachten digitaal in</div>
            <div class="actions mt-3">
              <a class="btn" href="opdrachten.php">Naar opdrachten</a>
            </div>
          </div>

          <div class="card">
            <h3>Events</h3>
            <div class="meta">Meld je aan voor campus-events</div>
            <div class="actions mt-3">
              <a class="btn" href="events.php">Bekijk events</a>
            </div>
          </div>

          <div class="card">
            <h3>Meldingen</h3>
            <div class="meta">Roosterwijzigingen en deadlines</div>
            <div class="actions mt-3">
              <a class="btn" href="meldingen.php">Open meldingen</a>
            </div>
          </div>

          <div class="card">
            <h3>Projectgroep</h3>
            <div class="meta">Beheer je groepsleden</div>
            <div class="actions mt-3">
              <a class="btn" href="projectgroep.php">Open projectgroep</a>
            </div>
          </div>
        </div>
      </section>
    </main>
  </body>
</html>