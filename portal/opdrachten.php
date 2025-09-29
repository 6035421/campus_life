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

$hasAssignments = table_exists($conn, $db, 'assignments');
$hasSubmissions = table_exists($conn, $db, 'submissions');
$message = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasSubmissions) {
  $assignmentId = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;
  if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error = 'Upload mislukt. Probeer opnieuw.';
  } else {
    $uploads = __DIR__ . '/../uploads';
    if (!is_dir($uploads)) { @mkdir($uploads, 0775, true); }
    $safeName = preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($_FILES['file']['name']));
    $target = $uploads . '/' . time() . '_' . $safeName;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
      $relPath = '../uploads/' . basename($target);
      $stmt = $conn->prepare("INSERT INTO submissions (user_id, assignment_id, file_path, submitted_at) VALUES (?,?,?,NOW())");
      $stmt->bind_param('iis', $userId, $assignmentId, $relPath);
      if ($stmt->execute()) {
        $message = 'Opdracht is succesvol ingeleverd!';
      } else { $error = 'Opslaan mislukt: ' . $conn->error; }
    } else { $error = 'Bestand kon niet worden opgeslagen.'; }
  }
}

$assignments = null;
if ($hasAssignments) {
  $assignments = $conn->query("SELECT id, title, description, deadline FROM assignments ORDER BY deadline ASC");
}
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CampusLife - Opdrachten</title>
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
        <div class="subtext">Inleverportaal</div>
        <h1 class="headline">Opdrachten</h1>
      </section>
      <section class="section">
        <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if (!$hasAssignments || !$hasSubmissions): ?>
          <div class="alert warning">De benodigde tabellen <strong>assignments</strong> en/of <strong>submissions</strong> ontbreken nog.</div>
        <?php else: ?>
          <div class="cards">
            <?php while($assignments && ($a = $assignments->fetch_assoc())): ?>
              <div class="card">
                <h3><?php echo htmlspecialchars($a['title']); ?></h3>
                <div class="meta mb-2"><?php echo htmlspecialchars($a['description']); ?></div>
                <div class="meta">Deadline: <?php echo htmlspecialchars($a['deadline']); ?></div>
                <form class="mt-3" method="post" enctype="multipart/form-data">
                  <input type="hidden" name="assignment_id" value="<?php echo (int)$a['id']; ?>" />
                  <label for="file_<?php echo (int)$a['id']; ?>">Upload bestand</label>
                  <input id="file_<?php echo (int)$a['id']; ?>" type="file" name="file" required />
                  <div class="actions mt-2">
                    <button class="btn primary" type="submit">Inleveren</button>
                  </div>
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
