<?php
session_start();

//Force HTTPS in production
if (empty($_SERVER['HTTPS']) && $_SERVER['HTTP_HOST']!= 'localhost'){
    header("Location: https://".$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Enable if using HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode',1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to access this page";
    header("Location: login.php");
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Optional: Implement session timeout (30 minutes)
$inactive = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    session_unset();
    session_destroy();
    $_SESSION['error'] = "Your session has timed out";
    header("Location: login.php");
    exit();
}
$_SESSION['last_activity'] = time();
?>