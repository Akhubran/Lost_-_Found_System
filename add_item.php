<?php
session_start();//starts a session to track user login
include 'connection.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $item_type = filter_input(INPUT_POST, 'itemType', FILTER_SANITIZE_STRING);
    $item_name = filter_input(INPUT_POST, 'itemName', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'itemDescription', FILTER_SANITIZE_STRING);
    $location = filter_input(INPUT_POST, 'itemLocation', FILTER_SANITIZE_STRING);
    $date_reported = filter_input(INPUT_POST, 'itemDate', FILTER_SANITIZE_STRING);
    $category_id = filter_input(INPUT_POST, 'itemCategory', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    // Validate required fields
    if (empty($item_type) || empty($item_name) || empty($location) || empty($date_reported)) {
        $_SESSION['error'] = "Please fill in all required fields";
        header("Location: admin.php");
        exit();
    }

    // Handle file upload
    $image_name = '';
    if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        
        // Create uploads directory if it doesn't exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        // Generate unique filename
        $file_ext = pathinfo($_FILES['itemImage']['name'], PATHINFO_EXTENSION);
        $image_name = uniqid('item_', true) . '.' . $file_ext;
        $target_file = $target_dir . $image_name;

        // Validate image file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($file_ext), $allowed_types)) {
            $_SESSION['error'] = "Only JPG, JPEG, PNG & GIF files are allowed";
            header("Location: admin.php");
            exit();
        }

        if (!move_uploaded_file($_FILES['itemImage']['tmp_name'], $target_file)) {
            $_SESSION['error'] = "Failed to upload image";
            header("Location: admin.php");
            exit();
        }
    }

    // Prepare SQL with prepared statement
    $stmt = $conn->prepare("INSERT INTO items (Item_name, Item_status, Item_image, Item_description, Location, Date_reported, User_ID, category_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("ssssssii", $item_name, $item_type, $image_name, $description, $location, $date_reported, $user_id, $category_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Item successfully added!";
        
        // Log activity
        $activity_stmt = $conn->prepare("INSERT INTO activities (user_id, action, details) VALUES (?, ?, ?)");
        $action = "Added new item";
        $details = "Item: " . $item_name;
        $activity_stmt->bind_param("iss", $user_id, $action, $details);
        $activity_stmt->execute();
        $activity_stmt->close();
    } else {
        $_SESSION['error'] = "Failed to add item: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    
    header("Location: admin.php?status=success");
    exit();
} else {
    $_SESSION['error'] = "Invalid request method";
    header("Location: admin.php");
    exit();
}
?>