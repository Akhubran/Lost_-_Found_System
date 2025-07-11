<?php
session_start();
require 'connection.php'; 

$username = trim($_POST['username']);
$password = $_POST['password'];
$role = $_POST['role'];

if (empty($username) || empty($password) || empty($role)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: login.html");
    exit();
}

if ($role === 'admin') {
    $stmt = $conn->prepare("SELECT * FROM admin WHERE Contact = ?");
    $stmt->bind_param("s", $username);
} elseif ($role === 'user') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE Gmail = ?");
    $stmt->bind_param("s", $username);
} else {
    $_SESSION['error'] = "Invalid role.";
    header("Location: login.html");
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['Password'])) {
        if ($role === 'admin') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['Admin_ID'];
            $_SESSION['fname'] = $user['Fname'];
            header("Location: admin.php");
        } else {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $user['User_ID'];
            $_SESSION['fname'] = $user['Fname'];
            header("Location: home.html");
        }
        exit();
    } else {
        $_SESSION['error'] = "Incorrect password.";
        header("Location: login.html");
        exit();
    }
} else {
    $_SESSION['error'] = "Account not found.";
    header("Location: login.html");
    exit();
}
?>
