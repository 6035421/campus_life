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

$hasEvents = table_exists($conn, $db, 'events');
$hasRegs   = table_exists($conn, $db, 'event_registrations');
$message = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasRegs) {
  $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
  $stmt = $conn->prepare("INSERT INTO event_registrations (event_id, user_id, registered_at) VALUES (?,?,NOW())");
  $stmt->bind_param('ii', $eventId, $userId);
  if ($stmt->execute()) { $message = 'Je bent aangemeld voor het event!'; } else { $error = 'Kon niet aanmelden: ' . $conn->error; }
}

$events = null;
if ($hasEvents) {
  $events = $conn->query("SELECT id, title, event_date, location, description FROM events ORDER BY event_date ASC");
}
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CampusLife - Events</title>
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
        <div class="subtext">Doe mee</div>
        <h1 class="headline">Events</h1>
      </section>
      <section class="section">
        <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if (!$hasEvents || !$hasRegs): ?>
          <div class="alert warning">De benodigde tabellen <strong>events</strong> en/of <strong>event_registrations</strong> ontbreken nog.</div>
        <?php else: ?>
          <div class="cards">
            <?php while($events && ($e = $events->fetch_assoc())): ?>
              <div class="card">
                <h3><?php echo htmlspecialchars($e['title']); ?></h3>
                <div class="meta mb-2"><?php echo htmlspecialchars($e['description']); ?></div>
                <div class="meta">Tijd: <?php echo date('d-m-Y H:i', strtotime($e['event_date'])); ?></div>
                <div class="meta">Locatie: <?php echo htmlspecialchars($e['location']); ?></div>
                <form class="mt-3" method="post">
                  <input type="hidden" name="event_id" value="<?php echo (int)$e['id']; ?>" />
                  <button class="btn primary" type="submit">Aanmelden</button>
                </form>
              </div>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
        <div class="mt-3"><a class="btn" href="dashboard.php">⬅ Terug naar Dashboard</a></div>
      </section>
    </main>
  </body>
</html>
