<?php
require 'connection.php';
require 'session.php';

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Forbidden");
}

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    http_response_code(400);
    die("Bad Request");
}

$userId = (int)$_GET['id'];
$status = (int)$_GET['status'];

$stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE User_ID = ?");
$stmt->bind_param("ii", $status, $userId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    http_response_code(200);
} else {
    http_response_code(500);
}
$stmt->close();
?>