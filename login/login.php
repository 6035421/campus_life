<?php
session_start();
include '../database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        header("Location: ../portal/dashboard.php");
        exit();
    } else {
        $error = "Ongeldige login gegevens!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>CampusLife - Login</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <h2>Inloggen</h2>
    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="post">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Wachtwoord" required><br>
        <button type="submit">Login</button>
    </form>
    <p><a href="forgot.php">Wachtwoord vergeten?</a></p>
    <p>Nog geen account? <a href="register.php">Registreren</a></p>
</body>
</html>