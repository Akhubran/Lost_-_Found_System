<?php
session_start();
require 'connection.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, role=? WHERE id=?");
    $stmt->bind_param("sssi", $full_name, $phone, $role, $user['id']);

    if ($stmt->execute()) {
        $_SESSION['user']['full_name'] = $full_name;
        $_SESSION['user']['phone'] = $phone;
        $_SESSION['user']['role'] = $role;
        echo "✅ Profile updated!";
    } else {
        echo "❌ Error updating: " . $conn->error;
    }
}
?>

<h2>Edit Profile</h2>
<form method="POST">
    Full Name: <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>"><br><br>
    Phone: <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>"><br><br>
    Role: <input type="text" name="role" value="<?= htmlspecialchars($user['role']) ?>"><br><br>
    <input type="submit" value="Update">
</form>
<p><a href="logout.php">Logout</a></p>
