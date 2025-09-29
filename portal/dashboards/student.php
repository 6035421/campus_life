<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}
?>

<div class="dashboard-grid">
    <!-- Schedule Card -->
    <div class="card">
        <h3>📅 Mijn Rooster</h3>
        <div class="meta mb-2">Bekijk je dagelijkse lessen</div>
        <div class="upcoming-classes">
            <?php
            // Get today's classes
            $today = date('Y-m-d');
            $stmt = $conn->prepare("
                SELECT * FROM lessons 
                WHERE class_id IN (SELECT class_id FROM class_students WHERE student_id = ?)
                AND DATE(lesson_date) = ? 
                ORDER BY start_time
                LIMIT 3
            ") or die($conn->error);
            $stmt->bind_param('is', $_SESSION['user_id'], $today);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while ($class = $result->fetch_assoc()) {
                    echo '<div class="class-item">';
                    echo '<div class="class-time">' . date('H:i', strtotime($class['start_time'])) . ' - ' . date('H:i', strtotime($class['end_time'])) . '</div>';
                    echo '<div class="class-name">' . htmlspecialchars($class['name']) . '</div>';
                    echo '<div class="class-location">' . htmlspecialchars($class['location']) . '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p>Geen lessen vandaag</p>';
            }
            ?>
        </div>
        <div class="actions mt-2">
            <a href="rooster.php" class="btn primary">Bekijk volledig rooster</a>
        </div>
    </div>

    <!-- Assignments Card -->
    <div class="card">
        <h3>📝 Opdrachten</h3>
        <div class="meta mb-2">Binnenkort in te leveren</div>
        <div class="assignments-list">
            <?php
            // Get upcoming assignments
            $stmt = $conn->prepare("
                SELECT * FROM assignments 
                WHERE deadline >= CURDATE() 
                AND class_id IN (SELECT class_id FROM class_students WHERE student_id = ?)
                ORDER BY deadline ASC 
                LIMIT 3
            ") or die($conn->error);
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $assignments = $stmt->get_result();
            
            if ($assignments->num_rows > 0) {
                while ($assignment = $assignments->fetch_assoc()) {
                    $dueDate = new DateTime($assignment['deadline']);
                    $now = new DateTime();
                    $interval = $now->diff($dueDate);
                    $daysLeft = $interval->format('%a');
                    
                    echo '<div class="assignment-item">';
                    echo '<div class="assignment-title">' . htmlspecialchars($assignment['title']) . '</div>';
                    echo '<div class="assignment-due">Deadline: ' . date('d-m-Y', strtotime($assignment['deadline'])) . ' (' . $daysLeft . ' dagen)</div>';
                    echo '<div class="assignment-class">' . htmlspecialchars($assignment['class_name'] ?? '') . '</div>';
                    echo '<a href="opdracht.php?id=' . $assignment['id'] . '" class="btn small">Bekijk</a>';
                    echo '</div>';
                }
            } else {
                echo '<p>Geen aankomende opdrachten</p>';
            }
            ?>
        </div>
        <div class="actions mt-2">
            <a href="opdrachten.php" class="btn primary">Bekijk alle opdrachten</a>
        </div>
    </div>

    <!-- Grades Card -->
    <div class="card">
        <h3>📊 Mijn Cijfers</h3>
        <div class="meta mb-2">Laatste beoordelingen</div>
        <div class="grades-list">
            <?php
            // Get recent grades
            $stmt = $conn->prepare("
                SELECT g.*, a.title as assignment_title, c.name as class_name 
                FROM grades g
                JOIN assignments a ON g.assignment_id = a.id
                JOIN classes c ON a.class_id = c.id
                WHERE g.student_id = ? 
                ORDER BY g.updated_at DESC 
                LIMIT 3
            ") or die($conn->error);
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $grades = $stmt->get_result();
            
            if ($grades->num_rows > 0) {
                while ($grade = $grades->fetch_assoc()) {
                    echo '<div class="grade-item">';
                    echo '<div class="grade-header">';
                    echo '<span class="grade-value">' . htmlspecialchars($grade['grade']) . '</span>';
                    echo '<span class="grade-title">' . htmlspecialchars($grade['assignment_title']) . '</span>';
                    echo '</div>';
                    echo '<div class="grade-meta">' . htmlspecialchars($grade['class_name']) . ' • ' . date('d-m-Y', strtotime($grade['updated_at'])) . '</div>';
                    if (!empty($grade['feedback'])) {
                        echo '<div class="grade-feedback">' . nl2br(htmlspecialchars($grade['feedback'])) . '</div>';
                    }
                    echo '</div>';
                }
            } else {
                echo '<p>Nog geen cijfers beschikbaar</p>';
            }
            ?>
        </div>
        <div class="actions mt-2">
            <a href="cijfers.php" class="btn primary">Bekijk alle cijfers</a>
        </div>
    </div>

    <!-- Announcements Card -->
    <div class="card">
        <h3>📢 Mededelingen</h3>
        <div class="meta mb-2">Recente aankondigingen</div>
        <div class="announcements-list">
            <?php
            // Get recent announcements
            $stmt = $conn->prepare("
                SELECT a.*, u.name as author_name 
                FROM announcements a
                JOIN users u ON a.author_id = u.id
                WHERE a.class_id IN (SELECT class_id FROM class_students WHERE student_id = ?)
                OR a.class_id IS NULL
                ORDER BY a.created_at DESC 
                LIMIT 3
            ") or die($conn->error);
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $announcements = $stmt->get_result();
            
            if ($announcements->num_rows > 0) {
                while ($announcement = $announcements->fetch_assoc()) {
                    echo '<div class="announcement-item">';
                    echo '<div class="announcement-title">' . htmlspecialchars($announcement['title']) . '</div>';
                    echo '<div class="announcement-meta">' . htmlspecialchars($announcement['author_name']) . ' • ' . date('d-m-Y H:i', strtotime($announcement['created_at'])) . '</div>';
                    echo '<div class="announcement-content">' . nl2br(htmlspecialchars(substr($announcement['content'], 0, 100))) . (strlen($announcement['content']) > 100 ? '...' : '') . '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p>Geen recente mededelingen</p>';
            }
            ?>
        </div>
        <div class="actions mt-2">
            <a href="mededelingen.php" class="btn primary">Bekijk alle mededelingen</a>
        </div>
    </div>
</div>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    display: flex;
    flex-direction: column;
}

.card:hover {
    transform: translateY(-5px);
}

.card h3 {
    margin-top: 0;
    margin-bottom: 0.5rem;
    color: #2c3e50;
    font-size: 1.25rem;
}

.meta {
    color: #7f8c8d;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.actions {
    margin-top: auto;
    padding-top: 1rem;
    display: flex;
    gap: 0.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.2s;
    text-align: center;
}

.btn.primary {
    background: #3498db;
    color: white;
    border: 1px solid #2980b9;
}

.btn.primary:hover {
    background: #2980b9;
}

.btn.small {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    background: #f8f9fa;
    color: #2c3e50;
    border: 1px solid #dee2e6;
}

/* Upcoming Classes */
.upcoming-classes {
    margin-bottom: 1rem;
}

.class-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
}

.class-item:last-child {
    border-bottom: none;
}

.class-time {
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
}

.class-name {
    font-weight: 500;
    margin: 0.25rem 0;
}

.class-location {
    font-size: 0.85rem;
    color: #7f8c8d;
}

/* Assignments */
.assignments-list {
    margin-bottom: 1rem;
}

.assignment-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
}

.assignment-item:last-child {
    border-bottom: none;
}

.assignment-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.assignment-due {
    font-size: 0.85rem;
    color: #e74c3c;
    margin-bottom: 0.25rem;
}

.assignment-class {
    font-size: 0.85rem;
    color: #7f8c8d;
}

/* Grades */
.grades-list {
    margin-bottom: 1rem;
}

.grade-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
}

.grade-item:last-child {
    border-bottom: none;
}

.grade-header {
    display: flex;
    align-items: center;
    margin-bottom: 0.25rem;
}

.grade-value {
    font-weight: 700;
    font-size: 1.1rem;
    color: #2ecc71;
    margin-right: 0.75rem;
    min-width: 2rem;
    text-align: center;
}

.grade-title {
    font-weight: 600;
    color: #2c3e50;
}

.grade-meta {
    font-size: 0.8rem;
    color: #7f8c8d;
    margin-left: 2.75rem;
}

.grade-feedback {
    margin-top: 0.5rem;
    font-size: 0.9rem;
    color: #555;
    background: #f8f9fa;
    padding: 0.5rem;
    border-radius: 4px;
    border-right: 3px solid #3498db;
}

/* Announcements */
.announcements-list {
    margin-bottom: 1rem;
}

.announcement-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
}

.announcement-item:last-child {
    border-bottom: none;
}

.announcement-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.announcement-meta {
    font-size: 0.8rem;
    color: #7f8c8d;
    margin-bottom: 0.5rem;
}

.announcement-content {
    font-size: 0.9rem;
    color: #555;
    line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>
