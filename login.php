<?php
session_start();
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            header("Location: edit_profile.php");
            exit;
        } else {
            echo "❌ Wrong password.";
        }
    } else {
        echo "❌ No user found.";
    }
}
?>

<form method="POST">
    <h2>Login</h2>
    Email: <input type="email" name="email" required><br><br>
    Password: <input type="password" name="password" required><br><br>
    <input type="submit" value="Login">
</form>
