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

$hasProjects = table_exists($conn, $db, 'projects');
$hasMembers  = table_exists($conn, $db, 'project_members');
$hasUsers    = table_exists($conn, $db, 'users');
$message = null; $error = null;

$project = null; $members = null;
if ($hasProjects && $hasMembers) {
  // pick first project of the user for demo
  $stmt = $conn->prepare("SELECT p.id, p.name FROM projects p JOIN project_members pm ON pm.project_id=p.id WHERE pm.user_id=? LIMIT 1");
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $project = $stmt->get_result()->fetch_assoc();

  if ($project) {
    $pid = (int)$project['id'];
    $members = $conn->query("SELECT u.name, u.email FROM project_members pm JOIN users u ON u.id=pm.user_id WHERE pm.project_id=$pid ORDER BY u.name");
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $project && $hasMembers && $hasUsers) {
  $email = trim($_POST['email'] ?? '');
  $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $u = $stmt->get_result()->fetch_assoc();
  if ($u) {
    $pid = (int)$project['id']; $uid = (int)$u['id'];
    $ins = $conn->prepare("INSERT INTO project_members (project_id, user_id) VALUES (?, ?)");
    $ins->bind_param('ii', $pid, $uid);
    if ($ins->execute()) { $message = 'Groepsgenoot toegevoegd!'; } else { $error = 'Kon niet toevoegen: ' . $conn->error; }
  } else {
    $error = 'Gebruiker niet gevonden.';
  }
}
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CampusLife - Projectgroep</title>
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
        <div class="subtext">Samenwerken</div>
        <h1 class="headline">Projectgroep</h1>
      </section>
      <section class="section">
        <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if (!$hasProjects || !$hasMembers): ?>
          <div class="alert warning">De benodigde tabellen <strong>projects</strong> en/of <strong>project_members</strong> ontbreken nog.</div>
        <?php elseif (!$project): ?>
          <div class="alert warning">Je bent nog niet gekoppeld aan een project.</div>
        <?php else: ?>
          <div class="cards">
            <div class="card">
              <h3>Project</h3>
              <div class="meta">Naam: <?php echo htmlspecialchars($project['name']); ?></div>
            </div>
            <div class="card">
              <h3>Groepsleden</h3>
              <?php if ($members): while($m = $members->fetch_assoc()): ?>
                <div class="meta"><?php echo htmlspecialchars($m['name']); ?> — <?php echo htmlspecialchars($m['email']); ?></div>
              <?php endwhile; endif; ?>
            </div>
            <div class="card">
              <h3>Voeg groepsgenoot toe</h3>
              <form method="post" class="mt-2">
                <label for="email">E-mail van student</label>
                <input id="email" name="email" type="email" required placeholder="student@example.com" />
                <div class="actions mt-2">
                  <button class="btn primary" type="submit">Toevoegen</button>
                </div>
              </form>
            </div>
          </div>
        <?php endif; ?>
        <div class="mt-3"><a class="btn" href="dashboard.php">⬅ Terug naar Dashboard</a></div>
      </section>
    </main>
  </body>
</html>
