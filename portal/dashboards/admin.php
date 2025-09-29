<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}
?>

<div class="dashboard-grid">
    <!-- User Management Card -->
    <div class="card">
        <h3>👥 Gebruikersbeheer</h3>
        <div class="meta mb-2">Beheer gebruikers en rechten</div>
        <div class="actions">
            <a href="gebruikers.php?action=new" class="btn primary">Nieuwe gebruiker</a>
            <a href="gebruikers.php" class="btn secondary">Gebruikersoverzicht</a>
        </div>
    </div>

    <!-- System Settings Card -->
    <div class="card">
        <h3>⚙️ Systeeminstellingen</h3>
        <div class="meta mb-2">Configureer het systeem</div>
        <div class="actions">
            <a href="instellingen.php" class="btn primary">Algemene instellingen</a>
            <a href="backup.php" class="btn secondary">Backup & Herstel</a>
        </div>
    </div>

    <!-- Security Card -->
    <div class="card">
        <h3>🔐 Beveiliging</h3>
        <div class="meta mb-2">Beheer beveiligingsinstellingen</div>
        <div class="actions">
            <a href="tweestapsverificatie.php" class="btn primary">2FA-instellingen</a>
            <a href="beveiligingslog.php" class="btn secondary">Beveiligingslog</a>
        </div>
    </div>

    <!-- System Health Card -->
    <div class="card">
        <h3>💻 Systeemstatus</h3>
        <div class="meta mb-2">Bekijk de systeemstatus</div>
        <div class="system-stats">
            <div class="stat">
                <span class="stat-label">Gebruikers</span>
                <span class="stat-value"><?php echo get_user_count(); ?></span>
            </div>
            <div class="stat">
                <span class="stat-label">Laatste backup</span>
                <span class="stat-value"><?php echo get_last_backup_date(); ?></span>
            </div>
        </div>
        <div class="actions mt-2">
            <a href="systeemstatus.php" class="btn primary">Details bekijken</a>
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
    margin-top: 1rem;
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
    background: #9b59b6;
    color: white;
}

.btn.primary:hover {
    background: #8e44ad;
}

.btn.secondary {
    background: #ecf0f1;
    color: #2c3e50;
}

.btn.secondary:hover {
    background: #bdc3c7;
}

.system-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin: 1rem 0;
}

.stat {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 6px;
}

.stat-label {
    display: block;
    font-size: 0.8rem;
    color: #7f8c8d;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-weight: 600;
    color: #2c3e50;
}

.mb-2 {
    margin-bottom: 0.5rem;
}

.mt-2 {
    margin-top: 0.5rem;
}
</style>
