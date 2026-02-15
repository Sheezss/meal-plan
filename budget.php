<?php
// budget.php - Budget Tracker
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Handle form submission
$message = '';
$message_type = '';

// Check if budget table exists, if not create it
$check_table = "SHOW TABLES LIKE 'user_budgets'";
$table_exists = mysqli_query($conn, $check_table);

if (mysqli_num_rows($table_exists) == 0) {
    // Create user_budgets table
    $create_table = "CREATE TABLE user_budgets (
        budget_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        budget_type VARCHAR(20) DEFAULT 'Weekly',
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'KES',
        start_date DATE,
        end_date DATE,
        current_spending DECIMAL(10,2) DEFAULT 0.00,
        status VARCHAR(20) DEFAULT 'Active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    
    mysqli_query($conn, $create_table);
}

// Check if pantry table exists
$pantry_count = 0;
$check_pantry = "SHOW TABLES LIKE 'pantry'";
$pantry_exists = mysqli_query($conn, $check_pantry);

if (mysqli_num_rows($pantry_exists) > 0) {
    $pantry_count_query = "SELECT COUNT(*) as count FROM pantry WHERE user_id = $user_id";
    $pantry_count_result = mysqli_query($conn, $pantry_count_query);
    $pantry_count = mysqli_fetch_assoc($pantry_count_result)['count'] ?? 0;
}

// Get existing budget
$current_budget = [];
$budget_query = "SELECT * FROM user_budgets WHERE user_id = $user_id AND status = 'Active' ORDER BY created_at DESC LIMIT 1";
$budget_result = mysqli_query($conn, $budget_query);
if (mysqli_num_rows($budget_result) > 0) {
    $current_budget = mysqli_fetch_assoc($budget_result);
}

// Get budget history
$budget_history = [];
$history_query = "SELECT * FROM user_budgets WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10";
$history_result = mysqli_query($conn, $history_query);
while ($row = mysqli_fetch_assoc($history_result)) {
    $budget_history[] = $row;
}

// Calculate spending statistics
$spending_stats = [
    'total_budgets' => 0,
    'total_allocated' => 0,
    'total_spent' => 0,
    'average_budget' => 0
];

$stats_query = "SELECT 
    COUNT(*) as total_budgets,
    SUM(amount) as total_allocated,
    SUM(current_spending) as total_spent,
    AVG(amount) as average_budget
    FROM user_budgets WHERE user_id = $user_id";
    
$stats_result = mysqli_query($conn, $stats_query);
if ($stats_result) {
    $stats = mysqli_fetch_assoc($stats_result);
    if ($stats) {
        $spending_stats = $stats;
    }
}

// Save new budget
if (isset($_POST['save_budget'])) {
    $budget_type = mysqli_real_escape_string($conn, $_POST['budget_type']);
    $amount = floatval($_POST['amount']);
    $currency = mysqli_real_escape_string($conn, $_POST['currency']);
    
    // Calculate dates based on budget type
    $start_date = date('Y-m-d');
    if ($budget_type == 'Weekly') {
        $end_date = date('Y-m-d', strtotime('+6 days'));
    } elseif ($budget_type == 'Monthly') {
        $end_date = date('Y-m-d', strtotime('+30 days'));
    } else {
        $end_date = date('Y-m-d', strtotime('+6 days'));
    }
    
    // Deactivate any existing active budget
    $deactivate_sql = "UPDATE user_budgets SET status = 'Inactive' WHERE user_id = $user_id AND status = 'Active'";
    mysqli_query($conn, $deactivate_sql);
    
    // Insert new budget
    $sql = "INSERT INTO user_budgets (user_id, budget_type, amount, currency, start_date, end_date, status) 
            VALUES ($user_id, '$budget_type', $amount, '$currency', '$start_date', '$end_date', 'Active')";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Budget set successfully!";
        $message_type = 'success';
        
        // Refresh budget data
        $budget_result = mysqli_query($conn, $budget_query);
        $current_budget = mysqli_fetch_assoc($budget_result);
        
        // Refresh history
        $history_result = mysqli_query($conn, $history_query);
        $budget_history = [];
        while ($row = mysqli_fetch_assoc($history_result)) {
            $budget_history[] = $row;
        }
    } else {
        $message = "Error setting budget: " . mysqli_error($conn);
        $message_type = 'error';
    }
}

// Update spending
if (isset($_POST['update_spending'])) {
    $spending = floatval($_POST['spending']);
    $budget_id = intval($_POST['budget_id']);
    
    $sql = "UPDATE user_budgets SET current_spending = $spending WHERE budget_id = $budget_id AND user_id = $user_id";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Spending updated successfully!";
        $message_type = 'success';
        
        // Refresh budget data
        $budget_result = mysqli_query($conn, $budget_query);
        $current_budget = mysqli_fetch_assoc($budget_result);
    } else {
        $message = "Error updating spending: " . mysqli_error($conn);
        $message_type = 'error';
    }
}

// Delete budget
if (isset($_GET['delete'])) {
    $budget_id = intval($_GET['delete']);
    $sql = "DELETE FROM user_budgets WHERE budget_id = $budget_id AND user_id = $user_id";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Budget deleted successfully!";
        $message_type = 'success';
        
        // Refresh history
        $history_result = mysqli_query($conn, $history_query);
        $budget_history = [];
        while ($row = mysqli_fetch_assoc($history_result)) {
            $budget_history[] = $row;
        }
    } else {
        $message = "Error deleting budget: " . mysqli_error($conn);
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Tracker - Meal Plan System</title>
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
            --warning-yellow: #f39c12;
            --success-blue: #3498db;
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
        
        /* Sidebar - Same as other pages */
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
        
        .btn-warning {
            background: var(--warning-yellow);
            color: white;
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        .btn-danger {
            background: var(--accent-red);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-lg {
            padding: 15px 40px;
            font-size: 16px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
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
        
        /* Forms */
        .form-group {
            margin-bottom: 25px;
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        /* Budget Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
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
        
        .stat-icon.budget {
            background: linear-gradient(135deg, var(--primary-green), #2980b9);
        }
        
        .stat-icon.spending {
            background: linear-gradient(135deg, var(--secondary-green), #27ae60);
        }
        
        .stat-icon.saved {
            background: linear-gradient(135deg, var(--accent-blue), #3498db);
        }
        
        .stat-icon.history {
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
        
        /* Budget Overview */
        .budget-overview {
            background: linear-gradient(135deg, var(--light-green), #e8f8f1);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        
        .budget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .budget-amount {
            font-size: 36px;
            font-weight: bold;
            color: var(--dark-green);
        }
        
        .budget-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .detail-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }
        
        .detail-label {
            color: var(--text-light);
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        /* Progress Bar */
        .progress-container {
            margin: 25px 0;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .progress-bar {
            height: 20px;
            background: var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-green), var(--secondary-green));
            border-radius: 10px;
            transition: width 0.5s ease-in-out;
            position: relative;
        }
        
        .progress-danger {
            background: linear-gradient(90deg, var(--accent-red), #c0392b);
        }
        
        .progress-warning {
            background: linear-gradient(90deg, var(--warning-yellow), #e67e22);
        }
        
        .progress-text {
            position: absolute;
            right: 10px;
            top: 0;
            height: 100%;
            display: flex;
            align-items: center;
            font-size: 12px;
            font-weight: 600;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        /* Budget Suggestions */
        .suggestions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .suggestion-card {
            background: var(--light-bg);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .suggestion-card:hover {
            border-color: var(--primary-green);
            transform: translateY(-2px);
        }
        
        .suggestion-card h4 {
            color: var(--dark-green);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Budget History Table */
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            margin-top: 20px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
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
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8f9fa;
            color: #6c757d;
        }
        
        .status-exceeded {
            background: #f8d7da;
            color: #721c24;
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
        
        /* Workflow Steps */
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
        
        /* Progress Container */
        .progress-container-large {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
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
            
            .budget-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
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
                <p>Track your food budget</p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="meal-plans.php"><i class="fas fa-calendar-alt"></i> My Meal Plans</a></li>
                <li><a href="pantry.php"><i class="fas fa-utensils"></i> My Pantry</a></li>
                <li><a href="recipes.php"><i class="fas fa-book"></i> Kenyan Recipes</a></li>
                <li><a href="shopping-list.php"><i class="fas fa-shopping-cart"></i> Shopping List</a></li>
                <li><a href="budget.php" class="active"><i class="fas fa-wallet"></i> Budget Tracker</a></li>
                <li><a href="preferences.php"><i class="fas fa-sliders-h"></i> My Preferences</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <div class="welcome-message">
                    <h1>Budget Tracker</h1>
                    <p>Set and track your food spending</p>
                </div>
                <div class="header-actions">
                    <a href="create-plan.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Continue to Step 4
                    </a>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Progress Bar -->
            <div class="progress-container-large">
                <div class="progress-header">
                    <span><strong>Setup Progress</strong></span>
                    <span>Step 3 of 4</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 75%"></div>
                </div>
                <p style="color: var(--text-light); font-size: 14px; margin-top: 10px;">
                    Pantry Setup ✓ → Preferences ✓ → Budget → Generate Plan
                </p>
            </div>
            
            <!-- Workflow Steps -->
            <div class="content-section">
                <h3>Your Setup Progress</h3>
                <div class="workflow-steps">
                    <div class="step">
                        <div class="step-icon" style="background: var(--primary-green);">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="step-content">
                            <h4>Step 1: Pantry Setup</h4>
                            <p>Add items to your pantry</p>
                            <div class="step-status">
                                <span class="badge badge-success">✓ Completed</span>
                                <p style="font-size: 12px; color: var(--primary-green);">
                                    <?php echo $pantry_count; ?> items added
                                </p>
                            </div>
                            <a href="pantry.php" class="btn btn-sm btn-outline" style="margin-top: 10px;">
                                View Pantry
                            </a>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-icon" style="background: var(--primary-green);">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="step-content">
                            <h4>Step 2: Set Preferences</h4>
                            <p>Choose dietary preferences & meal types</p>
                            <div class="step-status">
                                <span class="badge badge-success">✓ Completed</span>
                            </div>
                            <a href="preferences.php" class="btn btn-sm btn-outline" style="margin-top: 10px;">
                                View Preferences
                            </a>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-icon" style="background: var(--accent-orange);">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="step-content">
                            <h4>Step 3: Set Budget</h4>
                            <p>Define your weekly food budget</p>
                            <div class="step-status">
                                <span class="badge badge-warning" style="background: var(--accent-orange); color: white;">
                                    Current Step
                                </span>
                            </div>
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
            </div>
            
            <!-- Budget Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon budget">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-info">
                        <h3>KES <?php echo number_format($spending_stats['average_budget'] ?? 0, 0); ?></h3>
                        <p>Average Budget</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon spending">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>KES <?php echo number_format($spending_stats['total_spent'] ?? 0, 0); ?></h3>
                        <p>Total Spent</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon saved">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <div class="stat-info">
                        <h3>KES <?php echo number_format(($spending_stats['total_allocated'] ?? 0) - ($spending_stats['total_spent'] ?? 0), 0); ?></h3>
                        <p>Total Saved</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon history">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $spending_stats['total_budgets'] ?? 0; ?></h3>
                        <p>Budget Periods</p>
                    </div>
                </div>
            </div>
            
            <!-- Current Budget Overview -->
            <?php if (!empty($current_budget)): 
                $remaining = $current_budget['amount'] - $current_budget['current_spending'];
                $percentage = $current_budget['amount'] > 0 ? ($current_budget['current_spending'] / $current_budget['amount']) * 100 : 0;
                $progress_class = 'progress-fill';
                if ($percentage > 100) {
                    $progress_class = 'progress-fill progress-danger';
                } elseif ($percentage > 80) {
                    $progress_class = 'progress-fill progress-warning';
                }
            ?>
                <div class="budget-overview">
                    <div class="budget-header">
                        <div>
                            <h2 style="color: var(--dark-green); margin-bottom: 5px;">Current Budget</h2>
                            <p style="color: var(--text-light);">Active until <?php echo date('d M Y', strtotime($current_budget['end_date'])); ?></p>
                        </div>
                        <div class="budget-amount">
                            KES <?php echo number_format($current_budget['amount'], 0); ?>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-header">
                            <span style="font-weight: 600;">Budget Usage</span>
                            <span><?php echo number_format($percentage, 1); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="<?php echo $progress_class; ?>" style="width: <?php echo min($percentage, 100); ?>%">
                                <span class="progress-text">KES <?php echo number_format($current_budget['current_spending'], 0); ?> / KES <?php echo number_format($current_budget['amount'], 0); ?></span>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 14px;">
                            <span style="color: var(--primary-green); font-weight: 600;">Remaining: KES <?php echo number_format($remaining, 0); ?></span>
                            <span style="color: var(--text-light);">Spent: KES <?php echo number_format($current_budget['current_spending'], 0); ?></span>
                        </div>
                    </div>
                    
                    <div class="budget-details">
                        <div class="detail-item">
                            <div class="detail-label">Budget Type</div>
                            <div class="detail-value"><?php echo htmlspecialchars($current_budget['budget_type']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Start Date</div>
                            <div class="detail-value"><?php echo date('d M Y', strtotime($current_budget['start_date'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">End Date</div>
                            <div class="detail-value"><?php echo date('d M Y', strtotime($current_budget['end_date'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <span class="status-badge status-<?php echo strtolower($current_budget['status']); ?>">
                                    <?php echo htmlspecialchars($current_budget['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Update Spending Form -->
                    <form method="POST" action="" style="margin-top: 25px; background: white; padding: 20px; border-radius: 10px;">
                        <input type="hidden" name="budget_id" value="<?php echo $current_budget['budget_id']; ?>">
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: end;">
                            <div>
                                <label for="spending" style="display: block; margin-bottom: 8px; color: var(--text-dark); font-weight: 500;">
                                    Update Current Spending
                                </label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-weight: bold;">KES</span>
                                    <input type="number" 
                                           id="spending" 
                                           name="spending" 
                                           value="<?php echo $current_budget['current_spending']; ?>" 
                                           step="0.01"
                                           min="0"
                                           style="flex: 1; padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;"
                                           required>
                                </div>
                            </div>
                            <button type="submit" name="update_spending" class="btn btn-warning">
                                <i class="fas fa-sync-alt"></i> Update
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="content-section" style="text-align: center; background: var(--light-bg);">
                    <i class="fas fa-wallet" style="font-size: 60px; color: var(--text-light); margin-bottom: 20px;"></i>
                    <h3 style="color: var(--text-dark); margin-bottom: 10px;">No Active Budget</h3>
                    <p style="color: var(--text-light); margin-bottom: 20px;">
                        Set your first budget to start tracking your food expenses
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Set Budget Form -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Set New Budget</h2>
                </div>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="budget_type">Budget Period</label>
                            <select id="budget_type" name="budget_type" class="form-control" required>
                                <option value="Weekly">Weekly Budget</option>
                                <option value="Monthly">Monthly Budget</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="currency">Currency</label>
                            <select id="currency" name="currency" class="form-control" required>
                                <option value="KES" selected>Kenyan Shilling (KES)</option>
                                <option value="USD">US Dollar (USD)</option>
                                <option value="EUR">Euro (EUR)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Budget Amount</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); font-weight: bold; color: var(--text-dark);">
                                KES
                            </span>
                            <input type="number" 
                                   id="amount" 
                                   name="amount" 
                                   class="form-control" 
                                   style="padding-left: 50px;"
                                   placeholder="Enter your budget amount"
                                   step="0.01"
                                   min="100"
                                   required>
                        </div>
                        <small style="color: var(--text-light); font-size: 13px; margin-top: 5px; display: block;">
                            Suggested: KES 2,000 - 5,000 per week for individuals
                        </small>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" name="save_budget" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Set Budget
                        </button>
                        <a href="create-plan.php" class="btn btn-outline btn-lg" style="margin-left: 15px;">
                            <i class="fas fa-arrow-right"></i> Skip to Generate Plan
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Budget Suggestions -->
            <div class="content-section">
                <h3>Budgeting Tips</h3>
                <p style="color: var(--text-light); margin-bottom: 20px;">
                    Smart budgeting strategies for Kenyan households
                </p>
                
                <div class="suggestions-grid">
                    <div class="suggestion-card">
                        <h4><i class="fas fa-shopping-basket"></i> Market Days</h4>
                        <p>Shop on market days (Wednesday/Saturday) for fresh produce at lower prices.</p>
                    </div>
                    
                    <div class="suggestion-card">
                        <h4><i class="fas fa-utensils"></i> Bulk Cooking</h4>
                        <p>Cook in bulk and freeze portions to save on fuel and time.</p>
                    </div>
                    
                    <div class="suggestion-card">
                        <h4><i class="fas fa-seedling"></i> Seasonal Foods</h4>
                        <p>Buy seasonal fruits and vegetables for better prices and freshness.</p>
                    </div>
                    
                    <div class="suggestion-card">
                        <h4><i class="fas fa-list-alt"></i> Shopping List</h4>
                        <p>Always use a shopping list to avoid impulse buys.</p>
                    </div>
                </div>
            </div>
            
            <!-- Budget History -->
            <?php if (!empty($budget_history)): ?>
                <div class="content-section">
                    <div class="section-header">
                        <h2>Budget History</h2>
                    </div>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Amount</th>
                                    <th>Spent</th>
                                    <th>Remaining</th>
                                    <th>Status</th>
                                    <th>Dates</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($budget_history as $budget): 
                                    $remaining = $budget['amount'] - $budget['current_spending'];
                                    $percentage = $budget['amount'] > 0 ? ($budget['current_spending'] / $budget['amount']) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($budget['budget_type']); ?></td>
                                        <td><strong>KES <?php echo number_format($budget['amount'], 0); ?></strong></td>
                                        <td>KES <?php echo number_format($budget['current_spending'], 0); ?></td>
                                        <td>
                                            <span style="color: <?php echo $remaining >= 0 ? 'var(--primary-green)' : 'var(--accent-red)'; ?>; font-weight: 600;">
                                                KES <?php echo number_format($remaining, 0); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($budget['status'] == 'Active'): ?>
                                                <span class="status-badge status-active">Active</span>
                                            <?php elseif ($percentage > 100): ?>
                                                <span class="status-badge status-exceeded">Exceeded</span>
                                            <?php else: ?>
                                                <span class="status-badge status-inactive">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('d M', strtotime($budget['start_date'])); ?> - 
                                            <?php echo date('d M Y', strtotime($budget['end_date'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($budget['status'] != 'Active'): ?>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="if(confirm('Delete this budget record?')) window.location.href='budget.php?delete=<?php echo $budget['budget_id']; ?>'">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Final Call to Action -->
            <div class="content-section" style="text-align: center; background: linear-gradient(135deg, var(--light-green), #e8f8f1);">
                <?php if (!empty($current_budget)): ?>
                    <h3 style="color: var(--dark-green); margin-bottom: 15px;">Ready to Generate Your Meal Plan!</h3>
                    <p style="color: var(--text-dark); margin-bottom: 25px; font-size: 16px;">
                        You have set a budget of <strong>KES <?php echo number_format($current_budget['amount'], 0); ?></strong> 
                        for your <?php echo strtolower($current_budget['budget_type']); ?> meals.
                        Let's create your personalized meal plan based on your pantry, preferences, and budget.
                    </p>
                    <a href="create-plan.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-magic"></i> Generate My Meal Plan
                    </a>
                    <p style="color: var(--text-light); font-size: 14px; margin-top: 15px;">
                        Your plan will be optimized for cost, nutrition, and your preferences
                    </p>
                <?php else: ?>
                    <h3 style="color: var(--dark-green); margin-bottom: 15px;">Almost There!</h3>
                    <p style="color: var(--text-dark); margin-bottom: 25px; font-size: 16px;">
                        Set your budget above to complete Step 3, then generate your personalized meal plan.
                    </p>
                    <a href="#budget-form" class="btn btn-primary btn-lg">
                        <i class="fas fa-wallet"></i> Set Budget First
                    </a>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
    // Format currency input
    document.addEventListener('DOMContentLoaded', function() {
        const amountInput = document.getElementById('amount');
        const currencySelect = document.getElementById('currency');
        
        function updateCurrencySymbol() {
            const currency = currencySelect.value;
            const symbol = currency === 'KES' ? 'KES' : currency === 'USD' ? '$' : '€';
            amountInput.previousElementSibling.textContent = symbol;
        }
        
        currencySelect.addEventListener('change', updateCurrencySymbol);
        updateCurrencySymbol();
        
        // Add budget suggestions based on selection
        const budgetType = document.getElementById('budget_type');
        budgetType.addEventListener('change', function() {
            const type = this.value;
            const suggestion = document.querySelector('small');
            if (type === 'Weekly') {
                suggestion.textContent = 'Suggested: KES 2,000 - 5,000 per week for individuals';
            } else {
                suggestion.textContent = 'Suggested: KES 8,000 - 15,000 per month for individuals';
            }
        });
    });
    
    // Add anchor for budget form
    document.addEventListener('DOMContentLoaded', function() {
        const setBudgetLinks = document.querySelectorAll('a[href="#budget-form"]');
        setBudgetLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector('.content-section:last-of-type').scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    });
    </script>
</body>
</html>