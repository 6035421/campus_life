<?php
session_start();
include '../database/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../login/login.php'); exit(); }
$userId = (int)$_SESSION['user_id'];

function table_exists($conn, $db, $table) {
  $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema=? AND table_name=?");
  $stmt->bind_param('ss', $db, $table);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_assoc();
  return $res && (int)$res['c'] > 0;
}

$hasNotif = table_exists($conn, $db, 'notifications');
$notifs = null;
if ($hasNotif) {
  $stmt = $conn->prepare("SELECT title, body, created_at FROM notifications WHERE user_id IS NULL OR user_id=? ORDER BY created_at DESC");
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $notifs = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CampusLife - Meldingen</title>
    <link rel="stylesheet" href="../style.css" />
  </head>
  <body>
    <header class="appbar">
      <div class="nav container">
        <div class="brand"><div class="logo"></div><div>CampusLife</div></div>
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
      <section class="hero">
        <div class="subtext">Altijd op de hoogte</div>
        <h1 class="headline">Meldingen</h1>
      </section>
      <section class="section">
        <?php if (!$hasNotif): ?>
          <div class="alert warning">De tabel <strong>notifications</strong> ontbreekt nog.</div>
        <?php else: ?>
          <div class="cards">
            <?php while($notifs && ($n = $notifs->fetch_assoc())): ?>
              <div class="card">
                <h3><?php echo htmlspecialchars($n['title']); ?></h3>
                <div class="meta mb-2"><?php echo htmlspecialchars($n['body']); ?></div>
                <div class="meta">Ontvangen: <?php echo htmlspecialchars($n['created_at']); ?></div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
        <div class="mt-3"><a class="btn" href="dashboard.php">⬅ Terug naar Dashboard</a></div>
      </section>
    </main>
  </body>
</html>
