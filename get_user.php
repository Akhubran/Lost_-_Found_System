<?php
require 'connection.php';
require 'session.php';

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Bad Request']));
}

$userId = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE User_ID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    header('Content-Type: application/json');
    echo json_encode($result->fetch_assoc());
} else {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
}
$stmt->close();
?>