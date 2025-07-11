<?php
session_start();
require 'connection.php';

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Invalid form submission";
    header("Location: login.php");
    exit();
}

// Get and sanitize inputs
$username = trim($_POST['username']);
$password = $_POST['password'];

// Validate credentials
$stmt = $conn->prepare("SELECT User_ID, username, Password, role, is_active FROM users_new WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Check if account is active
    if (!$user['is_active']) {
        $_SESSION['error'] = "Your account is disabled";
        header("Location: login.php");
        exit();
    }
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['User_ID'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Update last login
        $conn->query("UPDATE users_new SET last_login = NOW() WHERE id = {$user['id']}");
        
        // Redirect based on role
        header("Location: " . ($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
        exit();
    }
}

// If authentication fails
$_SESSION['error'] = "Invalid username or password";
header("Location: login.php");
exit();
?>