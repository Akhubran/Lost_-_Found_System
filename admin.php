<?php 
session_start();
require 'connection.php';
require 'session.php';

//verify admin
if($_SESSION['role'] !=='admin'){
    $_SESSION['error'] = "Access denied - Admin priviledges required";
    header("Location: login.php");
    exit();
}

//Regenerate session ID periodically for security 
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800){
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Get all users for management
$users = [];
$query = "SELECT User_ID, Fname, Lname, Gmail, Contact, created_at, is_active 
          FROM users_new
          ORDER BY created_at DESC";

if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    error_log("Database error: " . $conn->error);
    $_SESSION['error'] = "Error loading user data";
}


// Display messages
$success_message = '';
if (isset($_SESSION['success'])) {
    $success_message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' . 
                      htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}

$error_message = '';
if (isset($_SESSION['error'])) {
    $error_message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' . 
                    htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}

// Get stats for dashboard
$stats = [];
$query = "SELECT 
    (SELECT COUNT(*) FROM users_new) as total_users,
    (SELECT COUNT(*) FROM users_new) WHERE is_active = 1) as active_users,
    (SELECT COUNT(*) FROM items WHERE Item_status = 'lost') as lost_items,
    (SELECT COUNT(*) FROM items WHERE Item_status = 'found') as found_items,
    (SELECT COUNT(*) FROM items WHERE Item_status = 'claimed') as claimed_items,
    (SELECT COUNT(*) FROM users_new) as users";

$result = $conn->query($query);
if ($result) {
    $stats = $result->fetch_assoc();
    $result->free();
} else {
    error_log("Database error: " . $conn->error);
    $_SESSION['error'] = "Error loading statistics";
}


// Get recent items
$recent_items = [];
$query = "SELECT i.*, c.name as Category_Name 
          FROM items i
          LEFT JOIN categories c ON i.Category_ID = c.id
          ORDER BY i.Date_reported DESC
          LIMIT 5";

if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $recent_items[] = $row;
    }
    
    $stmt->close();
} else {
    error_log("Prepare failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <style>
    :root {
        --primary: #4a6bff;
        --primary-dark: #3a5bef;
        --secondary: #6c757d;
        --light: #f5f7fa;
        --dark: #343a40;
        --white: #ffffff;
        --sidebar-width: 280px;
        --topbar-height: 70px;
        --success: #28a745;
        --danger: #dc3545;
        --warning: #ffc107;
        --info: #17a2b8;
        --gray: #e9ecef;
        --transition: all 0.3s ease;
    }

    body {
        font-family: 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
        background: var(--light);
        color: var(--dark);
    }

    .admin-container {
        display: flex;
        min-height: 100vh;
    }

    /* Sidebar Styles */
    .sidebar {
        width: var(--sidebar-width);
        background: var(--dark);
        color: var(--white);
        padding: 1.5rem;
        position: relative;
        transition: var(--transition);
    }

    .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .sidebar-header h2 {
        font-size: 1.3rem;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sidebar nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar nav ul li {
        margin-bottom: 0.5rem;
    }

    .sidebar nav ul li a {
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0.8rem;
        border-radius: 4px;
        transition: var(--transition);
    }

    .sidebar nav ul li a:hover,
    .sidebar nav ul li.active a {
        background: rgba(255,255,255,0.1);
        color: var(--white);
    }

    .sidebar nav ul li a i {
        width: 20px;
        text-align: center;
    }

    .sidebar-footer {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(0,0,0,0.2);
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-profile img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .user-profile div {
        line-height: 1.3;
    }

    .username {
        font-weight: 600;
        display: block;
    }

    .user-role {
        font-size: 0.8rem;
        opacity: 0.8;
    }

    .logout-btn {
        color: rgba(255,255,255,0.8);
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        transition: var(--transition);
    }

    .logout-btn:hover {
        color: var(--white);
        transform: scale(1.1);
    }

    /* Main Content Styles */
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .top-bar {
        background: var(--white);
        padding: 1rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
        z-index: 100;
    }

    .toggle-sidebar {
        background: none;
        border: none;
        font-size: 1.2rem;
        color: var(--secondary);
        cursor: pointer;
        display: none;
    }

    .search-bar {
        flex: 1;
        max-width: 500px;
        display: flex;
        position: relative;
    }

    .search-bar input {
        width: 100%;
        padding: 0.5rem 1rem;
        padding-right: 2.5rem;
        border: 1px solid var(--gray);
        border-radius: 4px;
        font-family: inherit;
    }

    .search-bar button {
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 2.5rem;
        background: none;
        border: none;
        color: var(--secondary);
        cursor: pointer;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .notification-btn {
        position: relative;
        background: none;
        border: none;
        font-size: 1.2rem;
        color: var(--secondary);
        cursor: pointer;
    }

    .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: var(--danger);
        color: var(--white);
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .add-item-btn {
        background: var(--primary);
        color: var(--white);
        border: none;
        padding: 8px 15px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
    }

    .add-item-btn:hover {
        background: var(--primary-dark);
    }

    /* Content Section */
    .content-section {
        flex: 1;
        padding: 1.5rem;
        overflow-y: auto;
    }

    .section-title {
        margin-bottom: 1.5rem;
        color: var(--dark);
        font-size: 1.5rem;
        font-weight: 600;
    }

    /* Stats Cards */
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--white);
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: var(--transition);
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 1.2rem;
    }

    .stat-info h3 {
        font-size: 0.9rem;
        color: var(--secondary);
        margin-bottom: 5px;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark);
    }

    /* Recent Activity & Items */
    .recent-activity, 
    .recent-items {
        background: var(--white);
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        padding: 20px;
        margin-bottom: 30px;
    }

    .activity-header, 
    .items-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .view-all {
        background: none;
        border: none;
        color: var(--primary);
        font-weight: 500;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 4px;
    }

    .view-all:hover {
        background: rgba(74, 107, 255, 0.1);
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 10px 0;
        border-bottom: 1px solid var(--gray);
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--gray);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        flex-shrink: 0;
    }

    .activity-info {
        flex: 1;
    }

    .activity-info h4 {
        font-size: 0.95rem;
        margin-bottom: 3px;
        color: var(--dark);
    }

    .activity-info p {
        font-size: 0.85rem;
        color: var(--secondary);
        margin: 0;
    }

    .activity-time {
        font-size: 0.8rem;
        color: var(--secondary);
        white-space: nowrap;
    }

    /* Items Table */
    .items-table {
        overflow-x: auto;
        margin-top: 15px;
    }

    .items-table table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px;
    }

    .items-table th, 
    .items-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--gray);
    }

    .items-table th {
        background: var(--gray);
        font-weight: 600;
        color: var(--dark);
    }

    .items-table tr:hover {
        background: rgba(0,0,0,0.02);
    }

    .status-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        display: inline-block;
    }

    .status-badge.lost {
        background: #fff3bf;
        color: #e67700;
    }

    .status-badge.found {
        background: #d3f9d8;
        color: #2b8a3e;
    }

    .status-badge.claimed {
        background: #d0ebff;
        color: #1971c2;
    }

    .action-btn {
        background: none;
        border: none;
        color: var(--secondary);
        cursor: pointer;
        margin-right: 10px;
        font-size: 1rem;
        padding: 5px;
    }

    .action-btn.edit {
        color: var(--info);
    }

    .action-btn.delete {
        color: var(--danger);
    }

    /* Modal Styles */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: var(--transition);
    }

    .modal.active {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: var(--white);
        border-radius: 8px;
        width: 100%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        transform: translateY(-20px);
        transition: var(--transition);
    }

    .modal.active .modal-content {
        transform: translateY(0);
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid var(--gray);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .close-modal {
        background: none;
        border: none;
        font-size: 1.2rem;
        color: var(--secondary);
        cursor: pointer;
    }

    .modal-body {
        padding: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--dark);
    }

    .form-group input, 
    .form-group select, 
    .form-group textarea {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid var(--gray);
        border-radius: 6px;
        font-family: inherit;
        font-size: 0.95rem;
        transition: var(--transition);
    }

    .form-group input:focus, 
    .form-group select:focus, 
    .form-group textarea:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.2);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-top: 30px;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-outline {
        background: var(--white);
        border: 1px solid var(--gray);
        color: var(--dark);
    }

    .btn-outline:hover {
        background: var(--gray);
    }

    .btn-primary {
        background: var(--primary);
        color: var(--white);
        border: none;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
    }

    /* Alerts */
    .alert {
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* Responsive Styles */
    @media (max-width: 1200px) {
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            transform: translateX(-100%);
            z-index: 1000;
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .close-sidebar {
            display: block;
        }
        
        .toggle-sidebar {
            display: block;
        }
        
        .main-content {
            margin-left: 0;
        }
    }

    @media (max-width: 768px) {
        .stats-cards {
            grid-template-columns: 1fr 1fr;
        }
        
        .top-bar {
            padding: 15px;
        }
        
        .header-actions {
            gap: 10px;
        }
        
        .add-item-btn span {
            display: none;
        }
        
        .content-section {
            padding: 15px;
        }
    }
        .user-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .user-table th, 
    .user-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--gray);
    }
    
    .user-table th {
        background-color: var(--gray);
        font-weight: 600;
        color: var(--dark);
    }
    
    .user-table tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
    
    .status-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        display: inline-block;
    }
    
    .status-badge.active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-badge.inactive {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .actions {
        display: flex;
        gap: 8px;
    }
    
    .action-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        border-radius: 4px;
        transition: var(--transition);
    }
    
    .action-btn.edit {
        color: var(--info);
    }
    
    .action-btn.edit:hover {
        background-color: rgba(23, 162, 184, 0.1);
    }
    
    .action-btn.delete {
        color: var(--danger);
    }
    
    .action-btn.delete:hover {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .action-btn.activate {
        color: var(--success);
    }
    
    .action-btn.activate:hover {
        background-color: rgba(40, 167, 69, 0.1);
    }
    
    .action-btn.reset-pwd {
        color: var(--warning);
    }
    
    .action-btn.reset-pwd:hover {
        background-color: rgba(255, 193, 7, 0.1);
    }


    @media (max-width: 576px) {
        .stats-cards {
            grid-template-columns: 1fr;
        }
        
        .modal-content {
            width: 95%;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
        }
    }
    .shake-animation {
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-5px); }
    40%, 80% { transform: translateX(5px); }
}

.modal-bounce {
    animation: bounceIn 0.3s;
}

@keyframes bounceIn {
    0% { transform: scale(0.9); opacity: 0; }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); opacity: 1; }
}

.modal-content {
    transition: all 0.3s ease-out;
}
</style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-search"></i>Admin Panel</h2>
                <button class="close-sidebar"><i class="fas fa-times"></i></button>
            </div>
            <nav>
                <ul>
                    <li class="active"><a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="#"><i class="fas fa-question-circle"></i> Lost Items</a></li>
                    <li><a href="#"><i class="fas fa-check-circle"></i> Found Items</a></li>
                    <li><a href="#"><i class="fas fa-hand-holding"></i> Claims</a></li>
                    <li><a href="#"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="#"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div>
                        <span class="username"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Admin'); ?></span>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </aside>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <header class="top-bar">
                <div class="search-bar">
                    <input type="text" placeholder="Search items, users...">
                    <button><i class="fas fa-search"></i></button>
                </div>
                <div class="header-actions">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="badge">
                            <?php 
                                $unread = 0;
                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0 AND user_id = ?");
                                $stmt->bind_param("i", $_SESSION['user_id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result) {
                                    $unread = $result->fetch_assoc()['count'];
                                    $result->free();
                                }
                                $stmt->close();
                                echo $unread > 0 ? $unread : '';
                            ?>
                        </span>
                    </button>
                    <button class="add-item-btn" id="openAddItemModal">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
            </header>
            
            <section id="dashboard" class="content-section">
                <h2 class="section-title">Dashboard Overview</h2>
                
                <?php echo $error_message ?? ''; ?>
                <?php echo $success_message ?? ''; ?>
                
                <div class="stats-cards">
                    <!-- Stat cards here (similar structure for each) -->
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: var(--danger);">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Lost Items</h3>
                            <p class="stat-value"><?php echo $stats['lost_items'] ?? 0; ?></p>
                        </div>
                    </div>
                    <!-- Add other stat cards similarly -->
                </div>
                <div class="user-management">
                    <div class="section-header">
                        <h3><i class="fas fa-users"></i> User Management</h3>
                        <button class="btn btn-primary" id="addUserBtn">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                    </div>
                         <table class="user-table">
                             <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                  </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['User_ID']) ?></td>
            <td><?= htmlspecialchars($user['Fname'] . ' ' . $user['Lname']) ?></td>
            <td><?= htmlspecialchars($user['Gmail']) ?></td>
            <td><?= htmlspecialchars($user['Contact']) ?></td>
            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
            <td>
                <span class="status-badge <?= $user['is_active'] ? 'active' : 'inactive' ?>">
                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
            </td>
            <td class="actions">
                <button class="action-btn edit" onclick="editUser(<?= $user['User_ID'] ?>)"
                        title="Edit User">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="action-btn <?= $user['is_active'] ? 'delete' : 'activate' ?>" 
                        onclick="toggleUserStatus(<?= $user['User_ID'] ?>, <?= $user['is_active'] ?>)"
                        title="<?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>">
                    <i class="fas <?= $user['is_active'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                </button>
                <?php if ($user['User_ID'] != $_SESSION['user_id']): ?>
                <button class="action-btn reset-pwd" 
                        onclick="resetPassword(<?= $user['User_ID'] ?>)"
                        title="Reset Password">
                    <i class="fas fa-key"></i>
                </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
                        </table>
                    </div>
                 
                </div>
                
                <div class="recent-items">
                    <!-- Recent items table -->
                </div>
            </section>
        </main>
    </div>
    
    <!-- Add Item Modal -->
    <div class="modal" id="addItemModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Item</h3>
                <button class="close-modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="itemForm" action="add_item.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Form fields here -->
                    <div class="form-group">
                        <label for="itemType">Item Type</label>
                        <select id="itemType" name="itemType" class="form-control" required>
                            <option value="">Select type</option>
                            <option value="lost">Lost Item</option>
                            <option value="found">Found Item</option>
                        </select>
                    </div>

                    <div class="user-management">
                         <div class="section-header">
                                 <h3><i class="fas fa-users"></i> User Management</h3>
                                    <button class="btn btn-primary" id="addUserBtn">
                                          <i class="fas fa-plus"></i> Add User
                                       </button>
                     </div>

    <div class="table-responsive">
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['User_ID']) ?></td>
                    <td><?= htmlspecialchars($user['Fname'] . ' ' . $user['Lname']) ?></td>
                    <td><?= htmlspecialchars($user['Gmail']) ?></td>
                    <td><?= htmlspecialchars($user['Contact']) ?></td>
                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <span class="status-badge <?= $user['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="actions">
                        <button class="action-btn edit" onclick="editUser(<?= $user['User_ID'] ?>)"
                                title="Edit User">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn <?= $user['is_active'] ? 'delete' : 'activate' ?>" 
                                onclick="toggleUserStatus(<?= $user['User_ID'] ?>, <?= $user['is_active'] ?>)"
                                title="<?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>">
                            <i class="fas <?= $user['is_active'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                        </button>
                        <?php if ($user['User_ID'] != $_SESSION['user_id']): ?>
                        <button class="action-btn reset-pwd" 
                                onclick="resetPassword(<?= $user['User_ID'] ?>)"
                                title="Reset Password">
                            <i class="fas fa-key"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal" id="addUserModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New User</h3>
            <button class="close-modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="addUserForm" action="add_user.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="firstName" required>
                </div>
                
                <div class="form-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="lastName" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input type="text" id="contact" name="contact" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline cancel-btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal" id="editUserModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit User</h3>
            <button class="close-modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="editUserForm" action="update_user.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" id="editUserId" name="userId">
                
                <div class="form-group">
                    <label for="editFirstName">First Name</label>
                    <input type="text" id="editFirstName" name="firstName" required>
                </div>
                
                <div class="form-group">
                    <label for="editLastName">Last Name</label>
                    <input type="text" id="editLastName" name="lastName" required>
                </div>
                
                <div class="form-group">
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="editContact">Contact Number</label>
                    <input type="text" id="editContact" name="contact" required>
                </div>
                
                <div class="form-group">
                    <label for="editRole">Role</label>
                    <select id="editRole" name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline cancel-btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
                    
                    <!-- Other form fields -->
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-primary submit-btn">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal Elements
    const modal = document.getElementById('addItemModal');
    const openModalBtn = document.getElementById('openAddItemModal');
    const closeModalBtn = document.querySelector('.close-modal');
    const cancelBtn = document.querySelector('.cancel-btn');
    const form = document.getElementById('itemForm');
    
    // Form Elements
    const dateField = document.getElementById('itemDate');
    const requiredFields = form.querySelectorAll('[required]');
    
    // Modal State Management
    let isModalOpen = false;
    
    // Open Modal Function
    const openModal = () => {
        modal.classList.add('active');
        isModalOpen = true;
        // Set focus to first form element when modal opens
        const firstInput = form.querySelector('input, select, textarea');
        if (firstInput) firstInput.focus();
    };
    
    // Close Modal Function
    const closeModal = () => {
        modal.classList.remove('active');
        isModalOpen = false;
        resetFormValidation();
    };
    
    // Reset Form Validation
    const resetFormValidation = () => {
        requiredFields.forEach(field => {
            field.style.borderColor = '';
            field.classList.remove('shake-animation');
        });
    };
    
    // Form Validation
    const validateForm = () => {
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.style.borderColor = 'var(--danger)';
                isValid = false;
                
                // Add shake animation to empty required fields
                field.classList.add('shake-animation');
                setTimeout(() => {
                    field.classList.remove('shake-animation');
                }, 500);
            } else {
                field.style.borderColor = '';
            }
        });
        
        return isValid;
    };
    
    // Event Listeners
    openModalBtn.addEventListener('click', openModal);
    closeModalBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    
    // Close modal when clicking outside content
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isModalOpen) {
            closeModal();
        }
    });
    
    // Set today's date as default
    if (dateField) {
        dateField.valueAsDate = new Date();
    }
    
    // Form submission handler
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            // Scroll to first invalid field
            const firstInvalidField = form.querySelector('[required][style*="border-color"]');
            if (firstInvalidField) {
                firstInvalidField.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            }
        }
    });
    
    // Real-time validation for fields
    requiredFields.forEach(field => {
        field.addEventListener('input', () => {
            if (field.value.trim()) {
                field.style.borderColor = '';
            }
        });
    });

    // Add animation for modal appearance
    modal.addEventListener('transitionend', function(e) {
        if (modal.classList.contains('active')) {
            modal.querySelector('.modal-content').classList.add('modal-bounce');
        }
    });
});
</script>
</body>
</html>