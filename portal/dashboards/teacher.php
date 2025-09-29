<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'docent') {
    header("Location: ../login/login.php");
    exit();
}
?>

<div class="dashboard-grid">
    <!-- Lesson Planning Card -->
    <div class="card">
        <h3>📅 Lesbeheer</h3>
        <div class="meta mb-2">Beheer je lessen en roosters</div>
        <div class="actions">
            <a href="lessenbeheer.php" class="btn primary">Lessen beheren</a>
            <a href="rooster.php" class="btn secondary">Bekijk rooster</a>
        </div>
    </div>

    <!-- Assignment Management Card -->
    <div class="card">
        <h3>📝 Opdrachten</h3>
        <div class="meta mb-2">Beheer opdrachten en deadlines</div>
        <div class="actions">
            <a href="opdrachten.php?action=new" class="btn primary">Nieuwe opdracht</a>
            <a href="opdrachten.php" class="btn secondary">Bekijk inzendingen</a>
        </div>
    </div>

    <!-- Grading Card -->
    <div class="card">
        <h3>📊 Beoordelingen</h3>
        <div class="meta mb-2">Beoordeel ingeleverde opdrachten</div>
        <div class="actions">
            <a href="beoordelingen.php" class="btn primary">Te beoordelen</a>
            <a href="cijferoverzicht.php" class="btn secondary">Cijferoverzicht</a>
        </div>
    </div>

    <!-- Class Communication Card -->
    <div class="card">
        <h3>💬 Communicatie</h3>
        <div class="meta mb-2">Stuur berichten naar je klas</div>
        <div class="actions">
            <a href="berichten.php?new=1" class="btn primary">Nieuw bericht</a>
            <a href="berichten.php" class="btn secondary">Bekijk berichten</a>
        </div>
    </div>
</div>

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
    background: #3498db;
    color: white;
}

.btn.primary:hover {
    background: #2980b9;
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
