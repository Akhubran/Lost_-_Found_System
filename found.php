<?php 
// Include database connection
// If connection.php is in a parent directory:
include '../connection.php';

// If it's in a subdirectory called 'config':
include 'config/connection.php';

// Or use absolute path:
include $_SERVER['DOCUMENT_ROOT'] . '/Lost_and_Found_System/connection.php';// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search)) {
    $stmt = $conn->prepare("SELECT i.*, u.Fname, u.Lname 
                            FROM items i 
                            LEFT JOIN users u ON i.User_ID = u.User_ID 
                            WHERE i.Item_status = 'found' AND i.Item_name LIKE ? 
                            ORDER BY i.Date_reported DESC");
    $likeSearch = "%" . $search . "%";
    $stmt->bind_param("s", $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT i.*, u.Fname, u.Lname 
            FROM items i 
            LEFT JOIN users u ON i.User_ID = u.User_ID 
            WHERE i.Item_status = 'found' 
            ORDER BY i.Date_reported DESC";

    $result = $conn->query($sql);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Found Items - Back To You</title>
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

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo {
            height: 50px;
            margin-right: 10px;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            flex-direction: column;
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

        .search-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .search-bar {
            flex-grow: 0.7;
            display: flex;
            align-items: center;
            border: 1px solid #ccc;
            border-radius: 30px;
            padding: 8px 15px;
            background-color: #fff;
        }

        .search-icon {
            margin-right: 10px;
            color: #666;
        }

        .search-bar input {
            flex-grow: 1;
            border: none;
            outline: none;
            padding: 5px;
        }

        .search-magnify {
            margin-left: 10px;
            color: #666;
        }

        .report-button {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            background-color: #4CAF50;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
            width: 110px;
            height: 60px;
        }

        .report-icon {
            margin-bottom: 2px;
            font-size: 18px;
        }

        .items-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .item-card {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .item-header {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
            position: relative;
        }

        .avatar {
            width: 30px;
            height: 30px;
            background-color: #673AB7;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }

        .item-info {
            flex-grow: 1;
        }

        .item-name {
            font-weight: bold;
            color: #333;
        }

        .date {
            font-size: 12px;
            color: #777;
        }

        .menu-dots {
            font-size: 18px;
            cursor: pointer;
            color: #555;
        }

        .item-image {
            height: 150px;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-image.no-image::before {
            content: '';
            position: absolute;
            width: 50px;
            height: 50px;
            background-color: #bbb;
            transform: rotate(45deg);
            top: 30px;
        }

        .item-image.no-image::after {
            content: '';
            position: absolute;
            width: 30px;
            height: 30px;
            background-color: #999;
            border-radius: 50%;
            bottom: 40px;
            right: 80px;
        }

        .item-details {
            padding: 15px;
        }

        .title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .location {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .description {
            font-size: 12px;
            color: #777;
            line-height: 1.4;
            margin-bottom: 15px;
        }

        .contact-button {
            display: block;
            width: 100px;
            margin: 0 auto 15px;
            padding: 8px 0;
            background-color: #673AB7;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
        }

        .contact-button:hover {
            background-color: #5e35b1;
        }

        .report-button:hover {
            background-color: #45a049;
        }

        .no-items {
            text-align: center;
            color: #666;
            font-size: 18px;
            margin-top: 50px;
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
    .content-container {
        flex-direction: column;
        align-items: center;
    }
    
    .form-card {
        width: 100%;
        max-width: 400px;
    }
    
    .image-container {
        margin-left: 0;
        margin-top: 20px;
        max-width: 400px;
    }
    
    .footer-content {
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }
    
    .footer-right {
        text-align: center;
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
                    <li><a href="lost.php">Lost</a></li>
                    <li><a href="#">Report Lost</a></li>
                    <li><a href="#" class="active">Found</a></li>
                    <li><a href="#">Report Found</a></li>
                    <li><a href="#">Profile</a></li>
                </ul>
            </nav>
            <button class="sign-out">Sign Out</button>
        </header>
        
        <main>
            <h2>Found Items</h2>
            <?php if (!empty($search)): ?>
    <p style="text-align:center; color:#555;">Showing results for "<strong><?php echo htmlspecialchars($search); ?></strong>"</p>
<?php endif; ?>

            
           <div class="search-section">
    <form method="GET" action="found.php" class="search-bar">
        <span class="search-icon">≡</span>
        <input type="text" name="search" placeholder="Item Name" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit" class="search-magnify">🔍</button>
    </form>

    <button class="report-button">
        <div class="report-icon">✓</div>
        Report<br>Found
    </button>
</div>

            
            <div class="items-container">
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Get user's first initial for avatar
                        $initial = !empty($row['Fname']) ? strtoupper(substr($row['Fname'], 0, 1)) : 'U';
                        
                        // Format date
                        $date = date('M j, Y', strtotime($row['Date_reported']));
                        
                        // Get user's full name
                        $fullName = !empty($row['Fname']) && !empty($row['Lname']) 
                                   ? $row['Fname'] . ' ' . $row['Lname'] 
                                   : 'Unknown User';
                        
                        // Truncate description if too long
                        $description = strlen($row['Item_description']) > 100 
                                     ? substr($row['Item_description'], 0, 100) . '...' 
                                     : $row['Item_description'];
                        
                        echo '<div class="item-card">';
                        echo '<div class="item-header">';
                        echo '<div class="avatar">' . $initial . '</div>';
                        echo '<div class="item-info">';
                        echo '<div class="item-name">' . htmlspecialchars($fullName) . '</div>';
                        echo '<div class="date">' . $date . '</div>';
                        echo '</div>';
                        
                        // Add menu dots for some items (randomly for variety)
                        if (rand(0, 1)) {
                            echo '<div class="menu-dots">⋮</div>';
                        }
                        echo '</div>';
                        
                        // Display image if available
                        echo '<div class="item-image';
                        if (empty($row['Item_image'])) {
                            echo ' no-image';
                        }
                        echo '">';
                        
                        if (!empty($row['Item_image'])) {
                            echo '<img src="data:image/jpeg;base64,' . base64_encode($row['Item_image']) . '" alt="Item Image">';
                        }
                        echo '</div>';
                        
                        echo '<div class="item-details">';
                        echo '<div class="title">' . htmlspecialchars($row['Item_name']) . '</div>';
                        echo '<div class="location">' . htmlspecialchars($row['Location']) . '</div>';
                        echo '<p class="description">' . htmlspecialchars($description) . '</p>';
                        echo '</div>';
                        $contact = !empty($row['User_contact']) ? $row['User_contact'] : 'Not provided';

echo '<button class="contact-button" onclick="showPopup(\'' .
     htmlspecialchars($fullName, ENT_QUOTES) . '\', \'' .
     htmlspecialchars($contact, ENT_QUOTES) . '\')">Contact</button>';

                       
                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-items">No found items available.</div>';
                }
                ?>
            </div>
        </main>
    </div>

    <script>
    function showPopup(name, contact) {
        document.getElementById('popupName').innerText = name;
        document.getElementById('popupContact').innerText = contact;
        document.getElementById('contactPopup').style.display = 'block';
    }

    function closePopup() {
        document.getElementById('contactPopup').style.display = 'none';
    }
</script>
<footer>
        <div class="footer-content">
            <div class="footer-left">
                <img src="Logo 1.png" alt="Back To You Logo">
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

<!-- Contact Popup -->
<div id="contactPopup" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color: rgba(0,0,0,0.6); z-index:1000;">
    <div style="position:relative; width:300px; margin:100px auto; background:#fff; padding:20px; border-radius:10px; text-align:center;">
        <h3>Contact Info</h3>
        <p><strong>Name:</strong> <span id="popupName"></span></p>
        <p><strong>Contact:</strong> <span id="popupContact"></span></p>
        <button onclick="closePopup()" style="margin-top:15px; padding:8px 16px; background:#673AB7; color:#fff; border:none; border-radius:5px; cursor:pointer;">Close</button>
    </div>
</div>

</body>
</html>

<?php
// Close database connection
$conn->close();
?>