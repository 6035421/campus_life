<?php
 session_start();
 include '../database/db.php';

 if (!isset($_SESSION['user_id'])) {
   header("Location: ../login/login.php");
   exit();
 }

 if (!isset($_GET['id'])) {
   header("Location: ../portal/rooster.php");
   exit();
 }

 $id = intval($_GET['id']);
 $stmt = $conn->prepare("SELECT * FROM schedule WHERE id = ?");
 $stmt->bind_param("i", $id);
 $stmt->execute();
 $result = $stmt->get_result();
 $lesson = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CampusLife - Lesdetails</title>
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
          <a href="dashboard.php">🏠 Dashboard</a>
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
      <?php if($lesson): ?>
        <section class="hero">
          <div class="subtext">Lesdetails</div>
          <h1 class="headline"><?php echo htmlspecialchars($lesson['course_name']); ?></h1>
        </section>
        <section class="section">
          <div class="cards">
            <div class="card">
              <h3>Algemeen</h3>
              <div class="meta">Dag: <?php echo htmlspecialchars($lesson['day']); ?></div>
              <div class="meta">Tijd: <?php echo htmlspecialchars(substr($lesson['start_time'],0,5) . " - " . substr($lesson['end_time'],0,5)); ?></div>
            </div>
            <div class="card">
              <h3>Locatie</h3>
              <div class="meta"><?php echo htmlspecialchars($lesson['location']); ?></div>
            </div>
            <div class="card">
              <h3>Docent</h3>
              <div class="meta"><?php echo htmlspecialchars($lesson['docent']); ?></div>
            </div>
          </div>
          <div class="mt-3">
            <a class="btn" href="rooster.php">⬅ Terug naar rooster</a>
          </div>
        </section>
      <?php else: ?>
        <div class="alert error mt-3">Les niet gevonden.</div>
        <div class="mt-3"><a class="btn" href="rooster.php">Terug</a></div>
      <?php endif; ?>
    </main>
  </body>
</html>