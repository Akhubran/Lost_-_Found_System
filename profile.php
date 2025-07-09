<?php
// Include database connection
include 'connection.php';

// Get selected user ID from URL parameter or default to first user
$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// Fetch all users for dropdown
$users_sql = "SELECT User_ID, Fname, Lname FROM users ORDER BY Fname, Lname";
$users_result = $conn->query($users_sql);

// If no user selected, get the first user
if ($selected_user_id === null && $users_result->num_rows > 0) {
    $first_user = $users_result->fetch_assoc();
    $selected_user_id = $first_user['User_ID'];
    $users_result->data_seek(0); // Reset pointer
}

// Fetch selected user details
$user_details = null;
if ($selected_user_id) {
    $user_sql = "SELECT * FROM users WHERE User_ID = ?";
    $stmt = $conn->prepare($user_sql);
    $stmt->bind_param("i", $selected_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_details = $result->fetch_assoc();
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Viewer - Back To You</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #000;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            background: linear-gradient(to right, #ffebee, #e8f5e9);
            min-height: 100vh;
            border-radius: 10px;
            overflow: hidden;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #fff;
            border-bottom: 1px solid #e0e0e0;
        }

        .logo {
            height: 50px;
            margin-right: 10px;
        }

        .logo img {
            height: 100%;
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin: 0 10px;
        }

        nav ul li a {
            text-decoration: none;
            color: #333;
            padding: 5px 10px;
        }

        nav ul li a.active {
            background-color: #ddd;
            border-radius: 5px;
        }

        .sign-out {
            padding: 6px 15px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
        }

        main {
            padding: 20px;
        }

        h2 {
            text-align: center;
            font-size: 42px;
            color: #4C2B07;
            margin-bottom: 30px;
        }

        .user-selector {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
            gap: 15px;
        }

        .user-selector label {
            font-size: 18px;
            font-weight: bold;
            color: #4C2B07;
        }

        .user-selector select {
            padding: 10px 20px;
            border: 1px solid #ccc;
            border-radius: 30px;
            font-size: 16px;
            background-color: #fff;
            color: #333;
            cursor: pointer;
            min-width: 300px;
        }

        .user-selector select:focus {
            outline: none;
            border-color: #673AB7;
        }

        .profile-container {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .profile-header {
            background-color: #673AB7;
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            margin: 0 auto 20px;
            overflow: hidden;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .profile-id {
            font-size: 16px;
            opacity: 0.9;
        }

        .profile-content {
            padding: 30px;
        }

        .profile-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 24px;
            font-weight: bold;
            color: #4C2B07;
            margin-bottom: 20px;
            border-bottom: 2px solid #673AB7;
            padding-bottom: 10px;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .profile-field {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #673AB7;
        }

        .field-label {
            font-size: 14px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .field-value {
            font-size: 18px;
            color: #333;
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #4CAF50;
            color: white;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #dc3545;
            margin: 20px 0;
            font-weight: bold;
        }

        .no-user-message {
            text-align: center;
            padding: 50px;
            color: #666;
            font-size: 18px;
        }

        .avatar-initial {
            color: white;
            font-size: 48px;
            font-weight: bold;
        }

        footer {
            background-color: white;
            padding: 8px 20px;
            border-top: 1px solid #ddd;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-left {
            display: flex;
            align-items: center;
        }

        .footer-logo {
            height: 100px;
            margin-right: 15px;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
        }

        .footer-links-title,
        .footer-contact-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .footer-links a {
            color: #333;
            text-decoration: none;
            font-size: 13px;
            margin-bottom: 1px;
            line-height: 1.3;
        }

        .footer-right {
            text-align: right;
            font-size: 15px;
            line-height: 1.3;
        }

        .copyright {
            text-align: center;
            font-size: 10px;
            padding: 8px 0 3px;
            color: #333;
        }

        @media (max-width: 768px) {
            .container {
                width: 95%;
            }
            
            header {
                flex-direction: column;
                gap: 15px;
            }
            
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            h2 {
                font-size: 32px;
            }
            
            .user-selector {
                flex-direction: column;
                gap: 10px;
            }
            
            .user-selector select {
                min-width: auto;
                width: 100%;
            }
            
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }
            
            .footer-right {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <img src="back to you logo.png" alt="Back To You Logo">
            </div>
            <nav>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Lost</a></li>
                    <li><a href="found.php">Found</a></li>
                    <li><a href="#">Report Lost</a></li>
                    <li><a href="#">Report Found</a></li>
                    <li><a href="#" class="active">Profile</a></li>
                </ul>
            </nav>
            <button class="sign-out">Sign Out</button>
        </header>

        <main>
            <h2>User Profile</h2>
            
            <!-- User Selection Dropdown -->
            <div class="user-selector">
                <label for="user_select">Select User:</label>
                <form method="GET" action="" style="display: inline;">
                    <select name="user_id" id="user_select" onchange="this.form.submit()">
                        <option value="">Choose a user...</option>
                        <?php
                        if ($users_result->num_rows > 0) {
                            while($user = $users_result->fetch_assoc()) {
                                $selected = ($user['User_ID'] == $selected_user_id) ? 'selected' : '';
                                echo "<option value='" . $user['User_ID'] . "' $selected>" . 
                                     htmlspecialchars($user['Fname'] . ' ' . $user['Lname']) . 
                                     " (ID: " . $user['User_ID'] . ")</option>";
                            }
                        }
                        ?>
                    </select>
                </form>
            </div>

            <?php if ($user_details): ?>
            <!-- Profile Display -->
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-image">
                        <?php if (!empty($user_details['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($user_details['profile_image']); ?>" 
                                 alt="Profile Picture">
                        <?php else: ?>
                            <div class="avatar-initial">
                                <?php echo strtoupper(substr($user_details['Fname'], 0, 1) . substr($user_details['Lname'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-name">
                        <?php echo htmlspecialchars($user_details['Fname'] . ' ' . $user_details['Lname']); ?>
                    </div>
                    <div class="profile-id">
                        User ID: <?php echo htmlspecialchars($user_details['User_ID']); ?>
                    </div>
                </div>

                <div class="profile-content">
                    <div class="profile-section">
                        <div class="section-title">Personal Information</div>
                        <div class="profile-grid">
                            <div class="profile-field">
                                <div class="field-label">First Name</div>
                                <div class="field-value"><?php echo htmlspecialchars($user_details['Fname']); ?></div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">Last Name</div>
                                <div class="field-value"><?php echo htmlspecialchars($user_details['Lname']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <div class="section-title">Contact Information</div>
                        <div class="profile-grid">
                            <div class="profile-field">
                                <div class="field-label">Email Address</div>
                                <div class="field-value"><?php echo htmlspecialchars($user_details['Gmail']); ?></div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">Phone Number</div>
                                <div class="field-value"><?php echo htmlspecialchars($user_details['Contact']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <div class="section-title">Account Details</div>
                        <div class="profile-grid">
                            <div class="profile-field">
                                <div class="field-label">User ID</div>
                                <div class="field-value"><?php echo htmlspecialchars($user_details['User_ID']); ?></div>
                            </div>
                            <div class="profile-field">
                                <div class="field-label">Account Status</div>
                                <div class="field-value">
                                    <span class="status-badge">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php elseif ($selected_user_id): ?>
            <div class="profile-container">
                <div class="error-message">
                    <strong>Error:</strong> User not found with ID <?php echo htmlspecialchars($selected_user_id); ?>
                </div>
            </div>
            <?php else: ?>
            <div class="profile-container">
                <div class="no-user-message">
                    <h3>No users available</h3>
                    <p>Please add users to the database to view profiles.</p>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-left">
                <img src="Logo 1.png" alt="Back To You Logo" class="footer-logo">
                <div class="footer-links">
                    <div class="footer-links-title">Site</div>
                    <a href="#">Lost</a>
                    <a href="#">Report Lost</a>
                    <a href="#">Found</a>
                    <a href="#">Report Found</a>
                    <a href="#">About Us</a>
                </div>
            </div>
            
            <div class="footer-right">
                <div class="footer-contact-title">Contact</div>
                <div>Tel: ‪+254 723456890‬</div>
                <div>Email: backtoyou@gmail.com</div>
            </div>
        </div>
        
        <div class="copyright">
            © Copyright 2025 Lost and Found
            All Right Reserved
        </div>
    </footer>
</body>
</html>