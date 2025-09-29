<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coach') {
    header("Location: ../login/login.php");
    exit();
}
?>

<div class="dashboard-grid">
    <!-- Appointments Card -->
    <div class="card">
        <h3>📅 Afspraken</h3>
        <div class="meta mb-2">Beheer je coachingsafspraken</div>
        <div class="actions">
            <a href="afspraken.php?action=new" class="btn primary">Nieuwe afspraak</a>
            <a href="afspraken.php" class="btn secondary">Mijn agenda</a>
        </div>
    </div>

    <!-- Student Progress Card -->
    <div class="card">
        <h3>📈 Voortgang</h3>
        <div class="meta mb-2">Bekijk studentenvoortgang</div>
        <div class="actions">
            <a href="studenten.php" class="btn primary">Studentenoverzicht</a>
            <a href="voortgang.php" class="btn secondary">Risicoanalyse</a>
        </div>
    </div>

    <!-- Notes and Goals Card -->
    <div class="card">
        <h3>📝 Notities & Doelen</h3>
        <div class="meta mb-2">Beheer notities en stel doelen</div>
        <div class="actions">
            <a href="notities.php" class="btn primary">Notities</a>
            <a href="doelen.php" class="btn secondary">Beheer doelen</a>
        </div>
    </div>

    <!-- Absence Alerts Card -->
    <div class="card">
        <h3>⚠️ Verzuim</h3>
        <div class="meta mb-2">Houd verzuim bij en ontvang meldingen</div>
        <div class="actions">
            <a href="verzuim.php" class="btn primary">Verzuimoverzicht</a>
            <a href="meldingen.php" class="btn secondary">Meldingen</a>
        </div>
    </div>
</div>

<!-- Reuse the same styles as teacher dashboard -->
<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-5px);
}

.card h3 {
    margin-top: 0;
    color: #2c3e50;
}

.meta {
    color: #7f8c8d;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.btn.primary {
    background: #2ecc71;
    color: white;
}

.btn.primary:hover {
    background: #27ae60;
}

.btn.secondary {
    background: #ecf0f1;
    color: #2c3e50;
}

.btn.secondary:hover {
    background: #bdc3c7;
}

.mb-2 {
    margin-bottom: 0.5rem;
}
</style>
