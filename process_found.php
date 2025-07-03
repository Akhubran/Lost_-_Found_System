<?php
// Include database connection
require_once 'connection.php';

// Start session for user authentication (assuming user is logged in)
session_start();

// Check if form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if user is logged in (you'll need to implement login system)
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Initialize variables
    $item_name = "";
    $location = "";
    $date = "";
    $description = "";
    $photo_data = null;
    $errors = array();
    
    // Validate and sanitize input data
    if (empty($_POST["item"])) {
        $errors[] = "Item name is required";
    } else {
        $item_name = trim($_POST["item"]);
        $item_name = htmlspecialchars($item_name);
        // Check length constraint (30 characters max based on your DB)
        if (strlen($item_name) > 30) {
            $errors[] = "Item name must be 30 characters or less";
        }
    }
    
    if (empty($_POST["location"])) {
        $errors[] = "Location is required";
    } else {
        $location = $_POST["location"];
    }
    
    if (empty($_POST["date"])) {
        $errors[] = "Date is required";
    } else {
        $date = $_POST["date"];
        // Validate date format and convert to datetime for database
        if (!DateTime::createFromFormat('Y-m-d', $date)) {
            $errors[] = "Invalid date format";
        } else {
            // Convert to datetime format for database
            $date = $date . ' ' . date('H:i:s');
        }
    }
    
    if (empty($_POST["description"])) {
        $errors[] = "Item description is required";
    } else {
        $description = trim($_POST["description"]);
        $description = htmlspecialchars($description);
    }
    
    // Handle photo upload (store as BLOB in database)
    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {
        $file_extension = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = array("jpg", "jpeg", "png", "gif");
        
        // Validate file extension
        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed";
        }
        
        // Validate file size (5MB max)
        if ($_FILES["photo"]["size"] > 5000000) {
            $errors[] = "File size should not exceed 5MB";
        }
        
        if (empty($errors)) {
            // Read file content for BLOB storage
            $photo_data = file_get_contents($_FILES["photo"]["tmp_name"]);
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $sql = "INSERT INTO items (User_ID, Item_name, Item_status, Item_image, Item_description, Date_reported, Location) 
                VALUES (?, ?, 'Found', ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("isssss", $user_id, $item_name, $photo_data, $description, $date, $location);
            
            if ($stmt->execute()) {
                // Success - redirect with success message
                $_SESSION['success_message'] = "Found item reported successfully!";
                header("Location: report_found.php?success=1");
                exit();
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
            
            $stmt->close();
        } else {
            $errors[] = "Database preparation error: " . $conn->error;
        }
    }
    
    // If there are errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST; // Preserve form data
        header("Location: report_found.php?error=1");
        exit();
    }
    
} else {
    // If not POST request, redirect to form page
    header("Location: report_found.php");
    exit();
}

$conn->close();
?>