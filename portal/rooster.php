<?php
 session_start();
 include '../database/db.php';

 if (!isset($_SESSION['user_id'])) {
   header("Location: ../login/login.php");
   exit();
 }

 $sql = "SELECT * FROM schedule ORDER BY FIELD(day, 'Maandag','Dinsdag','Woensdag','Donderdag','Vrijdag'), start_time";
 $result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CampusLife - Rooster</title>
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
      <section class="hero">
        <div class="subtext">Weekoverzicht</div>
        <h1 class="headline">Rooster van <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
      </section>

      <section class="section">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Dag</th>
                <th>Vak</th>
                <th>Tijd</th>
                <th>Locatie</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <?php while($result && ($row = $result->fetch_assoc())): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['day']); ?></td>
                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                <td><?php echo htmlspecialchars(substr($row['start_time'],0,5) . " - " . substr($row['end_time'],0,5)); ?></td>
                <td><?php echo htmlspecialchars($row['location']); ?></td>
                <td>
                  <a class="btn" href="les.php?id=<?php echo (int)$row['id']; ?>">Bekijk</a>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          <a class="btn" href="dashboard.php">⬅ Terug naar Dashboard</a>
        </div>
      </section>
    </main>
  </body>
</html>