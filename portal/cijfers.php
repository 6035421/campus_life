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

$grades = [];
$hasTable = table_exists($conn, $db, 'grades');
if ($hasTable) {
  $stmt = $conn->prepare("SELECT course_name, grade, feedback, updated_at FROM grades WHERE student_id=? ORDER BY updated_at DESC");
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $grades = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CampusLife - Mijn cijfers</title>
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
        <div class="subtext">Voortgangsoverzicht</div>
        <h1 class="headline">Mijn cijfers</h1>
      </section>
      <section class="section">
        <?php if (!$hasTable): ?>
          <div class="alert warning">De tabel <strong>grades</strong> is nog niet aangemaakt. Vraag de beheerder om de database te initialiseren.</div>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Vak</th>
                  <th>Cijfer</th>
                  <th>Feedback</th>
                  <th>Laatst gewijzigd</th>
                </tr>
              </thead>
              <tbody>
                <?php while($grades && ($row = $grades->fetch_assoc())): ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                  <td><?php echo htmlspecialchars($row['grade']); ?></td>
                  <td><?php echo htmlspecialchars($row['feedback']); ?></td>
                  <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
        <div class="mt-3"><a class="btn" href="dashboard.php">⬅ Terug naar Dashboard</a></div>
      </section>
    </main>
  </body>
</html>
