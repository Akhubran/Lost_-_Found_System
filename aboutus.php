<?php
require 'connection.php';

$sql = "SELECT * FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>About Us</title>
    <style>
        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #aaa;
        }
        img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
<h2 style="text-align:center;">Team Members</h2>
<table>
    <tr>
        <th>Full Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Role</th>
        <th>Image</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['full_name']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td><?= htmlspecialchars($row['phone']) ?></td>
        <td><?= htmlspecialchars($row['role']) ?></td>
        <td>
            <?php if ($row['profile_image']): ?>
                <img src="uploads/<?= htmlspecialchars($row['profile_image']) ?>" alt="Profile">
            <?php else: ?>
                No image
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
</body>
</html>
