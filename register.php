<?php
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, role, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $full_name, $email, $phone, $role, $password);

    if ($stmt->execute()) {
        echo "✅ Registered successfully. <a href='login.php'>Login</a>";
    } else {
        echo "❌ Registration failed: " . $conn->error;
    }
}
?>

<form method="POST">
    <h2>Register</h2>
    Full Name: <input type="text" name="full_name" required><br><br>
    Email: <input type="email" name="email" required><br><br>
    Phone: <input type="text" name="phone"><br><br>
    Role: <input type="text" name="role"><br><br>
    Password: <input type="password" name="password" required><br><br>
    <input type="submit" value="Register">
</form>
