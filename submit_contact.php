<?php
require_once 'connection.php';

$errors = [];
$success = false;

// Process form only if POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Basic validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($title)) $errors[] = "Title is required";
    if (empty($message)) $errors[] = "Message is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";

    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            // Check if user exists
            $user_id = null;
            $stmt = $conn->prepare("SELECT User_ID FROM users WHERE Gmail = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $user_id = $row['User_ID'];
            } else {
                // Create new user WITHOUT password
                $stmt = $conn->prepare("INSERT INTO users (Frame, Lname, Gmail) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, '', $email);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                } else {
                    throw new Exception("Failed to create user: " . $conn->error);
                }
            }
            
            // Process file upload
            $file_path = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = mime_content_type($_FILES['photo']['tmp_name']);
                
                if (in_array($file_type, $allowed_types)) {
                    $upload_dir = 'uploads/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $file_name = "contact_" . $user_id . "_" . bin2hex(random_bytes(4)) . "." . $file_ext;
                    $file_path = $upload_dir . $file_name;
                    
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $file_path)) {
                        throw new Exception("Failed to upload file");
                    }
                } else {
                    throw new Exception("Only JPG, PNG, and GIF files are allowed");
                }
            }
            
            // Insert contact data
            $stmt = $conn->prepare("INSERT INTO items 
                                   (User_ID, Frame, Lname, Contact, Gmail, item_image, status, message) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $contact_number = preg_replace('/[^0-9]/', '', $title);
            $contact_number = !empty($contact_number) ? $contact_number : 0;
            
            $status = "New Contact";
            
            $stmt->bind_param("isssssss", 
                $user_id,
                $name,
                '',
                $contact_number,
                $email,
                $file_path,
                $status,
                $message
            );

            if (!$stmt->execute()) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $conn->commit();
            $success = true;
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
    
    // Redirect with status
    if ($success) {
        header("Location: Contact.html?success=1");
    } else {
        header("Location: Contact.html?error=" . urlencode(implode(", ", $errors)));
    }
    exit();
}

// If not POST request, redirect to form
header("Location: Contact.html");
exit();
?>