<?php
// pantry.php - Complete Pantry Management System
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Handle form submissions
$message = '';
$message_type = '';

// Add item to pantry
if (isset($_POST['add_item'])) {
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $quantity = floatval($_POST['quantity']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    
    // Check if pantry table exists, if not create it
    $check_table = "SHOW TABLES LIKE 'pantry'";
    $table_exists = mysqli_query($conn, $check_table);
    
    if (mysqli_num_rows($table_exists) == 0) {
        // Create pantry table
        $create_table = "CREATE TABLE pantry (
            pantry_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            item_name VARCHAR(100) NOT NULL,
            category VARCHAR(50),
            quantity DECIMAL(10,2) NOT NULL,
            unit VARCHAR(50),
            expiry_date DATE,
            added_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        
        if (mysqli_query($conn, $create_table)) {
            $message = "Pantry table created successfully!";
            $message_type = 'success';
        } else {
            $message = "Error creating pantry table: " . mysqli_error($conn);
            $message_type = 'error';
        }
    }
    
    // Insert the item
    if ($expiry_date) {
        $sql = "INSERT INTO pantry (user_id, item_name, category, quantity, unit, expiry_date) 
                VALUES ('$user_id', '$item_name', '$category', '$quantity', '$unit', '$expiry_date')";
    } else {
        $sql = "INSERT INTO pantry (user_id, item_name, category, quantity, unit) 
                VALUES ('$user_id', '$item_name', '$category', '$quantity', '$unit')";
    }
    
    if (mysqli_query($conn, $sql)) {
        $message = "Item added to pantry successfully!";
        $message_type = 'success';
    } else {
        $message = "Error adding item: " . mysqli_error($conn);
        $message_type = 'error';
    }
}

// Delete item from pantry
if (isset($_GET['delete'])) {
    $pantry_id = intval($_GET['delete']);
    $sql = "DELETE FROM pantry WHERE pantry_id = $pantry_id AND user_id = $user_id";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Item deleted successfully!";
        $message_type = 'success';
    } else {
        $message = "Error deleting item: " . mysqli_error($conn);
        $message_type = 'error';
    }
}

// Update item quantity
if (isset($_POST['update_quantity'])) {
    $pantry_id = intval($_POST['pantry_id']);
    $new_quantity = floatval($_POST['new_quantity']);
    
    $sql = "UPDATE pantry SET quantity = $new_quantity WHERE pantry_id = $pantry_id AND user_id = $user_id";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Quantity updated successfully!";
        $message_type = 'success';
    } else {
        $message = "Error updating quantity: " . mysqli_error($conn);
        $message_type = 'error';
    }
}

// Update item (edit functionality)
if (isset($_POST['edit_item'])) {
    $pantry_id = intval($_POST['edit_pantry_id']);
    $item_name = mysqli_real_escape_string($conn, $_POST['edit_item_name']);
    $category = mysqli_real_escape_string($conn, $_POST['edit_category']);
    $quantity = floatval($_POST['edit_quantity']);
    $unit = mysqli_real_escape_string($conn, $_POST['edit_unit']);
    $expiry_date = !empty($_POST['edit_expiry_date']) ? $_POST['edit_expiry_date'] : null;
    
    if ($expiry_date) {
        $sql = "UPDATE pantry SET 
                item_name = '$item_name',
                category = '$category',
                quantity = $quantity,
                unit = '$unit',
                expiry_date = '$expiry_date'
                WHERE pantry_id = $pantry_id AND user_id = $user_id";
    } else {
        $sql = "UPDATE pantry SET 
                item_name = '$item_name',
                category = '$category',
                quantity = $quantity,
                unit = '$unit',
                expiry_date = NULL
                WHERE pantry_id = $pantry_id AND user_id = $user_id";
    }
    
    if (mysqli_query($conn, $sql)) {
        $message = "Item updated successfully!";
        $message_type = 'success';
    } else {
        $message = "Error updating item: " . mysqli_error($conn);
        $message_type = 'error';
    }
}

// Get pantry items
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

$query = "SELECT * FROM pantry WHERE user_id = $user_id";
if (!empty($search)) {
    $query .= " AND item_name LIKE '%$search%'";
}
if (!empty($category_filter) && $category_filter != 'all') {
    $query .= " AND category = '$category_filter'";
}
$query .= " ORDER BY added_date DESC";

$result = mysqli_query($conn, $query);

// Get pantry items count for workflow section
$pantry_count_query = "SELECT COUNT(*) as count FROM pantry WHERE user_id = $user_id";
$pantry_count_result = mysqli_query($conn, $pantry_count_query);
$pantry_count = mysqli_fetch_assoc($pantry_count_result)['count'] ?? 0;

// Calculate statistics
$stats_query = "SELECT 
    COUNT(*) as total_items,
    SUM(quantity) as total_quantity,
    COUNT(CASE WHEN expiry_date < CURDATE() THEN 1 END) as expired_items,
    COUNT(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as expiring_soon
    FROM pantry WHERE user_id = $user_id";
    
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Store pantry items for workflow section
$pantry_items = [];
$result_for_workflow = mysqli_query($conn, "SELECT * FROM pantry WHERE user_id = $user_id");
while ($item = mysqli_fetch_assoc($result_for_workflow)) {
    $pantry_items[] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Pantry - Meal Plan System</title>
    <style>
        :root {
            --primary-green: #27ae60;
            --secondary-green: #2ecc71;
            --accent-orange: #e67e22;
            --accent-red: #e74c3c;
            --accent-blue: #3498db;
            --dark-green: #2e8b57;
            --light-green: #d5f4e6;
            --light-bg: #f9fdf7;
            --card-bg: #ffffff;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --border-color: #e1f5e1;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Roboto', sans-serif;
        }
        
        body {
            background-color: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid var(--border-color);
            padding: 25px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(46, 204, 113, 0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-right: 15px;
        }
        
        .logo-text h2 {
            color: var(--dark-green);
            font-size: 22px;
        }
        
        .logo-text p {
            color: var(--text-light);
            font-size: 12px;
        }
        
        .user-welcome {
            background: linear-gradient(135deg, var(--light-green), #e8f8f1);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        
        .user-welcome h3 {
            color: var(--dark-green);
            margin-bottom: 5px;
        }
        
        .user-welcome p {
            color: var(--text-light);
            font-size: 14px;
            font-style: italic;
        }
        
        .nav-menu {
            list-style: none;
            margin-bottom: 30px;
        }
        
        .nav-menu li {
            margin-bottom: 8px;
        }
        
        .nav-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-menu a:hover {
            background-color: var(--light-green);
            color: var(--primary-green);
            transform: translateX(5px);
        }
        
        .nav-menu a.active {
            background-color: var(--primary-green);
            color: white;
        }
        
        .nav-menu i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 18px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        
        /* Top Header */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .welcome-message h1 {
            color: var(--dark-green);
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .welcome-message p {
            color: var(--accent-orange);
            font-size: 16px;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 24px;
            color: white;
        }
        
        .stat-icon.items {
            background: linear-gradient(135deg, var(--primary-green), #2980b9);
        }
        
        .stat-icon.quantity {
            background: linear-gradient(135deg, var(--secondary-green), #27ae60);
        }
        
        .stat-icon.expired {
            background: linear-gradient(135deg, var(--accent-red), #c0392b);
        }
        
        .stat-icon.expiring {
            background: linear-gradient(135deg, var(--accent-orange), #e67e22);
        }
        
        .stat-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: var(--text-light);
            font-size: 14px;
        }
        
        /* Content Section */
        .content-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-header h2 {
            color: var(--dark-green);
            font-size: 22px;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-primary {
            background: var(--primary-green);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--dark-green);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-outline {
            background: white;
            color: var(--primary-green);
            border: 2px solid var(--primary-green);
        }
        
        .btn-outline:hover {
            background: var(--light-green);
        }
        
        .btn-danger {
            background: var(--accent-red);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        /* Filter Bar */
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .filter-select {
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: white;
            font-size: 16px;
            min-width: 150px;
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        .data-table th {
            background: var(--light-green);
            color: var(--dark-green);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }
        
        .data-table tr:hover {
            background: var(--light-bg);
        }
        
        .data-table tr.expired {
            background: #ffeaea;
        }
        
        .data-table tr.expiring-soon {
            background: #fff4e6;
        }
        
        /* Badges */
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-grain {
            background: #ffeaa7;
            color: #e17055;
        }
        
        .badge-vegetable {
            background: #55efc4;
            color: #00b894;
        }
        
        .badge-protein {
            background: #fd79a8;
            color: #e84393;
        }
        
        .badge-dairy {
            background: #74b9ff;
            color: #0984e3;
        }
        
        .badge-fruit {
            background: #a29bfe;
            color: #6c5ce7;
        }
        
        .badge-spice {
            background: #fab1a0;
            color: #e17055;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .modal-header h3 {
            color: var(--dark-green);
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
        }
        
        .close-modal:hover {
            color: var(--text-dark);
        }
        
        /* Messages */
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }
        
        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            color: var(--border-color);
        }
        
        .empty-state h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .dashboard-container {
                flex-direction: column;
            }
            
            .top-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        /* Quantity Update Form */
        .quantity-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .quantity-input {
            width: 80px;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            text-align: center;
        }
        
        /* Expiry Status */
        .expiry-status {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .expiry-good {
            background: #d4edda;
            color: #155724;
        }
        
        .expiry-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .expiry-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .workflow-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }
        
        .step {
            background: white;
            border-radius: 15px;
            padding: 25px;
            border: 2px solid var(--border-color);
            text-align: center;
            transition: all 0.3s;
        }
        
        .step:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .step-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 24px;
        }
        
        .step-content h4 {
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .step-content p {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .step-status {
            margin: 15px 0;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .btn-lg {
            padding: 15px 40px;
            font-size: 16px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="logo-text">
                    <h2>NutriPlan KE</h2>
                    <p>Smart • Healthy • Affordable</p>
                </div>
            </div>
            
            <div class="user-welcome">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <h3>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h3>
                <p>Manage your kitchen inventory</p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="meal-plans.php"><i class="fas fa-calendar-alt"></i> My Meal Plans</a></li>
                <li><a href="pantry.php" class="active"><i class="fas fa-utensils"></i> My Pantry</a></li>
                <li><a href="recipes.php"><i class="fas fa-book"></i> Kenyan Recipes</a></li>
                <li><a href="shopping-list.php"><i class="fas fa-shopping-cart"></i> Shopping List</a></li>
                <li><a href="budget.php"><i class="fas fa-wallet"></i> Budget Tracker</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <div class="welcome-message">
                    <h1>My Pantry</h1>
                    <p>Manage your kitchen inventory and reduce food waste</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon items">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_items'] ?? 0; ?></h3>
                        <p>Total Items</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon quantity">
                        <i class="fas fa-weight-hanging"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_quantity'] ?? 0; ?></h3>
                        <p>Total Quantity</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon expired">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['expired_items'] ?? 0; ?></h3>
                        <p>Expired Items</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon expiring">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['expiring_soon'] ?? 0; ?></h3>
                        <p>Expiring Soon</p>
                    </div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="content-section">
                <div class="filter-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search pantry items..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               onkeyup="if(event.key === 'Enter') searchItems()">
                    </div>
                    
                    <select class="filter-select" id="categoryFilter" onchange="filterItems()">
                        <option value="all" <?php echo empty($category_filter) ? 'selected' : ''; ?>>All Categories</option>
                        <option value="Grain" <?php echo $category_filter == 'Grain' ? 'selected' : ''; ?>>Grains</option>
                        <option value="Vegetable" <?php echo $category_filter == 'Vegetable' ? 'selected' : ''; ?>>Vegetables</option>
                        <option value="Protein" <?php echo $category_filter == 'Protein' ? 'selected' : ''; ?>>Protein</option>
                        <option value="Dairy" <?php echo $category_filter == 'Dairy' ? 'selected' : ''; ?>>Dairy</option>
                        <option value="Fruit" <?php echo $category_filter == 'Fruit' ? 'selected' : ''; ?>>Fruits</option>
                        <option value="Spice" <?php echo $category_filter == 'Spice' ? 'selected' : ''; ?>>Spices</option>
                    </select>
                    
                    <button class="btn btn-outline" onclick="resetFilters()">
                        <i class="fas fa-redo"></i> Reset Filters
                    </button>
                </div>
                
                <!-- Pantry Items Table -->
                <div class="table-container">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Expiry Date</th>
                                    <th>Added On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Reset result pointer
                                mysqli_data_seek($result, 0);
                                while ($item = mysqli_fetch_assoc($result)): 
                                    // Determine row class based on expiry
                                    $row_class = '';
                                    if ($item['expiry_date']) {
                                        $expiry_date = new DateTime($item['expiry_date']);
                                        $today = new DateTime();
                                        $interval = $today->diff($expiry_date);
                                        
                                        if ($expiry_date < $today) {
                                            $row_class = 'expired';
                                        } elseif ($interval->days <= 7) {
                                            $row_class = 'expiring-soon';
                                        }
                                    }
                                ?>
                                    <tr class="<?php echo $row_class; ?>" id="item-<?php echo $item['pantry_id']; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($item['category'] ?? 'other'); ?>">
                                                <?php echo htmlspecialchars($item['category'] ?? 'Uncategorized'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" class="quantity-form" onsubmit="updateQuantity(event, <?php echo $item['pantry_id']; ?>)">
                                                <input type="hidden" name="pantry_id" value="<?php echo $item['pantry_id']; ?>">
                                                <input type="number" 
                                                       name="new_quantity" 
                                                       value="<?php echo $item['quantity']; ?>" 
                                                       step="0.01"
                                                       min="0"
                                                       class="quantity-input"
                                                       onchange="this.form.submit()">
                                                <span><?php echo htmlspecialchars($item['unit']); ?></span>
                                            </form>
                                        </td>
                                        <td>
                                            <?php if ($item['expiry_date']): 
                                                $expiry_date = new DateTime($item['expiry_date']);
                                                $today = new DateTime();
                                                $interval = $today->diff($expiry_date);
                                                
                                                $status_class = 'expiry-good';
                                                $status_text = 'Good';
                                                
                                                if ($expiry_date < $today) {
                                                    $status_class = 'expiry-danger';
                                                    $status_text = 'Expired';
                                                } elseif ($interval->days <= 7) {
                                                    $status_class = 'expiry-warning';
                                                    $status_text = 'Soon';
                                                }
                                            ?>
                                                <span class="expiry-status <?php echo $status_class; ?>">
                                                    <?php echo date('d M Y', strtotime($item['expiry_date'])); ?>
                                                    <br>
                                                    <small>(<?php echo $status_text; ?>)</small>
                                                </span>
                                            <?php else: ?>
                                                <span class="expiry-status">No expiry</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('d M Y', strtotime($item['added_date'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline" 
                                                        onclick="editItem(<?php echo $item['pantry_id']; ?>, '<?php echo addslashes($item['item_name']); ?>', '<?php echo $item['category']; ?>', <?php echo $item['quantity']; ?>, '<?php echo $item['unit']; ?>', '<?php echo $item['expiry_date']; ?>')"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="deleteItem(<?php echo $item['pantry_id']; ?>)"
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-utensils"></i>
                            <h3>Your pantry is empty</h3>
                            <p>Add items to your pantry to get started with meal planning</p>
                            <button class="btn btn-primary" onclick="openAddModal()">
                                <i class="fas fa-plus"></i> Add Your First Item
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tips Section -->
            <div class="content-section">
                <h3>Pantry Management Tips</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <i class="fas fa-rotate-left" style="color: var(--primary-green); font-size: 24px; margin-bottom: 10px;"></i>
                        <h4>First In, First Out</h4>
                        <p>Use older items first to prevent waste</p>
                    </div>
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <i class="fas fa-calendar-check" style="color: var(--accent-orange); font-size: 24px; margin-bottom: 10px;"></i>
                        <h4>Check Expiry Dates</h4>
                        <p>Regularly check and update expiry dates</p>
                    </div>
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <i class="fas fa-list-check" style="color: var(--accent-blue); font-size: 24px; margin-bottom: 10px;"></i>
                        <h4>Plan Meals Around Pantry</h4>
                        <p>Create meal plans using items you already have</p>
                    </div>
                </div>
            </div>

            <!-- Workflow Navigation -->
            <div class="content-section">
                <h3>Ready to Plan Your Meals?</h3>
                <p style="color: var(--text-light); margin-bottom: 25px;">
                    Complete these steps to generate your personalized meal plan:
                </p>
                
                <div class="workflow-steps">
                    <div class="step">
                        <div class="step-icon" style="background: <?php echo $pantry_count > 0 ? 'var(--primary-green)' : 'var(--text-light)'; ?>">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="step-content">
                            <h4>Step 1: Pantry Setup</h4>
                            <p>Add items to your pantry</p>
                            <div class="step-status">
                                <?php if ($pantry_count > 0): ?>
                                    <span class="badge badge-success">✓ Completed</span>
                                    <p style="font-size: 12px; color: var(--primary-green);">
                                        <?php echo $pantry_count; ?> items added
                                    </p>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-icon" style="background: var(--text-light);">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                        <div class="step-content">
                            <h4>Step 2: Set Preferences</h4>
                            <p>Choose dietary preferences & meal types</p>
                            <div class="step-status">
                                <span class="badge badge-warning">Pending</span>
                            </div>
                            <a href="preferences.php" class="btn btn-sm btn-outline" style="margin-top: 10px;">
                                Set Preferences
                            </a>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-icon" style="background: var(--text-light);">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="step-content">
                            <h4>Step 3: Set Budget</h4>
                            <p>Define your weekly food budget</p>
                            <div class="step-status">
                                <span class="badge badge-warning">Pending</span>
                            </div>
                            <a href="budget.php" class="btn btn-sm btn-outline" style="margin-top: 10px;">
                                Set Budget
                            </a>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-icon" style="background: var(--text-light);">
                            <i class="fas fa-magic"></i>
                        </div>
                        <div class="step-content">
                            <h4>Step 4: Generate Plan</h4>
                            <p>Create your personalized meal plan</p>
                            <div class="step-status">
                                <span class="badge badge-warning">Pending</span>
                            </div>
                            <a href="create-plan.php" class="btn btn-sm btn-outline" style="margin-top: 10px;">
                                Generate Plan
                            </a>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 1px solid var(--border-color);">
                    <?php if ($pantry_count > 0): ?>
                        <a href="preferences.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-arrow-right"></i> Continue to Step 2: Set Preferences
                        </a>
                        <p style="color: var(--text-light); font-size: 14px; margin-top: 15px;">
                            Great! You have <?php echo $pantry_count; ?> items in your pantry. Let's continue to set your preferences.
                        </p>
                    <?php else: ?>
                        <p style="color: var(--accent-orange); font-style: italic;">
                            <i class="fas fa-info-circle"></i> Please add at least 3 items to your pantry to continue.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add Item Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Item to Pantry</h3>
                <button class="close-modal" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="item_name">Item Name *</label>
                    <input type="text" 
                           id="item_name" 
                           name="item_name" 
                           class="form-control" 
                           placeholder="e.g., Maize Flour, Sukuma Wiki" 
                           required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="">Select category</option>
                            <option value="Grain">Grains</option>
                            <option value="Vegetable">Vegetables</option>
                            <option value="Protein">Protein</option>
                            <option value="Dairy">Dairy</option>
                            <option value="Fruit">Fruits</option>
                            <option value="Spice">Spices</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="unit">Unit *</label>
                        <select id="unit" name="unit" class="form-control" required>
                            <option value="">Select unit</option>
                            <option value="kg">Kilograms (kg)</option>
                            <option value="g">Grams (g)</option>
                            <option value="litre">Litres (L)</option>
                            <option value="ml">Millilitres (ml)</option>
                            <option value="piece">Pieces</option>
                            <option value="bunch">Bunches</option>
                            <option value="packet">Packets</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" 
                               id="quantity" 
                               name="quantity" 
                               class="form-control" 
                               step="0.01"
                               min="0.01"
                               placeholder="0.00"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date (Optional)</label>
                        <input type="date" 
                               id="expiry_date" 
                               name="expiry_date" 
                               class="form-control"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeAddModal()">
                        Cancel
                    </button>
                    <button type="submit" name="add_item" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add to Pantry
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Item Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Pantry Item</h3>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" id="edit_pantry_id" name="edit_pantry_id">
                
                <div class="form-group">
                    <label for="edit_item_name">Item Name *</label>
                    <input type="text" 
                           id="edit_item_name" 
                           name="edit_item_name" 
                           class="form-control" 
                           required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_category">Category *</label>
                        <select id="edit_category" name="edit_category" class="form-control" required>
                            <option value="">Select category</option>
                            <option value="Grain">Grains</option>
                            <option value="Vegetable">Vegetables</option>
                            <option value="Protein">Protein</option>
                            <option value="Dairy">Dairy</option>
                            <option value="Fruit">Fruits</option>
                            <option value="Spice">Spices</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_unit">Unit *</label>
                        <select id="edit_unit" name="edit_unit" class="form-control" required>
                            <option value="">Select unit</option>
                            <option value="kg">Kilograms (kg)</option>
                            <option value="g">Grams (g)</option>
                            <option value="litre">Litres (L)</option>
                            <option value="ml">Millilitres (ml)</option>
                            <option value="piece">Pieces</option>
                            <option value="bunch">Bunches</option>
                            <option value="packet">Packets</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_quantity">Quantity *</label>
                        <input type="number" 
                               id="edit_quantity" 
                               name="edit_quantity" 
                               class="form-control" 
                               step="0.01"
                               min="0.01"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_expiry_date">Expiry Date (Optional)</label>
                        <input type="date" 
                               id="edit_expiry_date" 
                               name="edit_expiry_date" 
                               class="form-control"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()">
                        Cancel
                    </button>
                    <button type="submit" name="edit_item" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Item
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal Functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }
        
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        function openEditModal() {
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target === addModal) {
                closeAddModal();
            }
            if (event.target === editModal) {
                closeEditModal();
            }
        }
        
        // Edit Item Function
        function editItem(pantryId, itemName, category, quantity, unit, expiryDate) {
            // Populate the edit form
            document.getElementById('edit_pantry_id').value = pantryId;
            document.getElementById('edit_item_name').value = itemName;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_quantity').value = quantity;
            document.getElementById('edit_unit').value = unit;
            
            if (expiryDate) {
                document.getElementById('edit_expiry_date').value = expiryDate;
            } else {
                document.getElementById('edit_expiry_date').value = '';
            }
            
            // Open the edit modal
            openEditModal();
        }
        
        // Search and Filter Functions
        function searchItems() {
            const search = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            window.location.href = `pantry.php?search=${encodeURIComponent(search)}&category=${category}`;
        }
        
        function filterItems() {
            const search = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            window.location.href = `pantry.php?search=${encodeURIComponent(search)}&category=${category}`;
        }
        
        function resetFilters() {
            window.location.href = 'pantry.php';
        }
        
        // Item Management Functions
        function deleteItem(pantryId) {
            if (confirm('Are you sure you want to delete this item?')) {
                window.location.href = `pantry.php?delete=${pantryId}`;
            }
        }
        
        function updateQuantity(event, pantryId) {
            event.preventDefault();
            const form = event.target;
            const quantity = form.querySelector('input[name="new_quantity"]').value;
            
            if (confirm(`Update quantity to ${quantity}?`)) {
                // Create hidden form for submission
                const hiddenForm = document.createElement('form');
                hiddenForm.method = 'POST';
                hiddenForm.style.display = 'none';
                
                const pantryIdInput = document.createElement('input');
                pantryIdInput.type = 'hidden';
                pantryIdInput.name = 'pantry_id';
                pantryIdInput.value = pantryId;
                
                const quantityInput = document.createElement('input');
                quantityInput.type = 'hidden';
                quantityInput.name = 'new_quantity';
                quantityInput.value = quantity;
                
                const submitInput = document.createElement('input');
                submitInput.type = 'hidden';
                submitInput.name = 'update_quantity';
                submitInput.value = '1';
                
                hiddenForm.appendChild(pantryIdInput);
                hiddenForm.appendChild(quantityInput);
                hiddenForm.appendChild(submitInput);
                document.body.appendChild(hiddenForm);
                hiddenForm.submit();
            }
        }
        
        // Auto-focus search on page load if search exists
        window.onload = function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput.value) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + N to add new item
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                openAddModal();
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                closeAddModal();
                closeEditModal();
            }
        });
    </script>
</body>
</html>