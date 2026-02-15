<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Get user details
$user_query = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

// Get user stats
$stats = [
    'total_plans' => 0,
    'active_plans' => 0,
    'plans_generated' => 0,
    'pantry_items' => 0,
    'budget_used' => 0
];

$plans_query = "SELECT COUNT(*) as total FROM meal_plans WHERE user_id = $user_id";
$result = mysqli_query($conn, $plans_query);
if ($result && mysqli_num_rows($result) > 0) {
    $stats['total_plans'] = mysqli_fetch_assoc($result)['total'];
}

$active_query = "SELECT COUNT(*) as active FROM meal_plans 
                 WHERE user_id = $user_id 
                 AND start_date <= CURDATE() 
                 AND end_date >= CURDATE()";
$result = mysqli_query($conn, $active_query);
if ($result && mysqli_num_rows($result) > 0) {
    $stats['active_plans'] = mysqli_fetch_assoc($result)['active'];
}

$generated_query = "SELECT COUNT(*) as generated FROM user_activity 
                    WHERE user_id = $user_id 
                    AND activity_type = 'plan_generated'";
$result = mysqli_query($conn, $generated_query);
if ($result && mysqli_num_rows($result) > 0) {
    $stats['plans_generated'] = mysqli_fetch_assoc($result)['generated'];
}

$pantry_query = "SELECT COUNT(*) as items FROM pantry WHERE user_id = $user_id";
$result = mysqli_query($conn, $pantry_query);
if ($result && mysqli_num_rows($result) > 0) {
    $stats['pantry_items'] = mysqli_fetch_assoc($result)['items'];
}

$budget_query = "SELECT COALESCE(SUM(amount), 0) as budget FROM budgets 
                 WHERE user_id = $user_id 
                 AND start_date <= CURDATE() 
                 AND end_date >= CURDATE()";
$result = mysqli_query($conn, $budget_query);
if ($result && mysqli_num_rows($result) > 0) {
    $stats['budget_used'] = mysqli_fetch_assoc($result)['budget'];
}

// Handle profile update
$message = '';
$message_type = '';

if (isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    $update_query = "UPDATE users SET full_name = '$full_name', email = '$email', phone_number = '$phone' WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['full_name'] = $full_name;
        $user_name = $full_name;
        $message = "Profile updated successfully!";
        $message_type = 'success';
        
        // Update user data
        $user_data['full_name'] = $full_name;
        $user_data['email'] = $email;
        $user_data['phone_number'] = $phone;
    } else {
        $message = "Error updating profile: " . mysqli_error($conn);
        $message_type = 'error';
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $check_query = "SELECT password FROM users WHERE user_id = $user_id";
    $check_result = mysqli_query($conn, $check_query);
    $user = mysqli_fetch_assoc($check_result);
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = '$hashed_password' WHERE user_id = $user_id";
            
            if (mysqli_query($conn, $update_query)) {
                $message = "Password changed successfully!";
                $message_type = 'success';
            } else {
                $message = "Error changing password: " . mysqli_error($conn);
                $message_type = 'error';
            }
        } else {
            $message = "New passwords do not match!";
            $message_type = 'error';
        }
    } else {
        $message = "Current password is incorrect!";
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Meal Plan System</title>
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
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        
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
            gap: 15px;
        }
        
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
        
        .btn-warning {
            background: var(--accent-orange);
            color: white;
        }
        
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
        
        /* Message Styles */
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
        
        /* Profile Grid */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 992px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            border: 2px solid var(--border-color);
            transition: all 0.3s;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            gap: 20px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            font-weight: bold;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .profile-info h3 {
            color: var(--dark-green);
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .profile-info p {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .profile-details {
            margin-bottom: 30px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: var(--text-light);
            font-weight: 500;
        }
        
        .detail-value {
            color: var(--text-dark);
            font-weight: 600;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-value {
            color: var(--primary-green);
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 12px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        /* Activity Log */
        .activity-log {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .activity-icon {
            color: var(--primary-green);
        }
        
        .activity-time {
            color: var(--text-light);
            font-size: 13px;
        }
        
        /* Danger Zone */
        .danger-zone {
            background: #fff5f5;
            border: 2px solid var(--accent-red);
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .danger-zone h4 {
            color: var(--accent-red);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
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
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
                <p>Manage your profile</p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="meal_plan.php"><i class="fas fa-calendar-alt"></i> My Meal Plans</a></li>
                <li><a href="pantry.php"><i class="fas fa-utensils"></i> My Pantry</a></li>
                <li><a href="recipes.php"><i class="fas fa-book"></i> Kenyan Recipes</a></li>
                <li><a href="shopping-list.php"><i class="fas fa-shopping-cart"></i> Shopping List</a></li>
                <li><a href="budget.php"><i class="fas fa-wallet"></i> Budget Tracker</a></li>
                <li><a href="preferences.php"><i class="fas fa-sliders-h"></i> My Preferences</a></li>
                <li><a href="create-plan.php"><i class="fas fa-magic"></i> Generate Plan</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <div class="welcome-message">
                    <h1>My Profile</h1>
                    <p>Manage your account and settings</p>
                </div>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Profile Grid -->
            <div class="profile-grid">
                <!-- Left Column: Profile Info -->
                <div>
                    <!-- Profile Card -->
                    <div class="content-section">
                        <div class="section-header">
                            <h2><i class="fas fa-user-circle"></i> Profile Information</h2>
                        </div>
                        
                        <div class="profile-card">
                            <div class="profile-header">
                                <div class="profile-avatar">
                                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                                </div>
                                <div class="profile-info">
                                    <h3><?php echo htmlspecialchars($user_name); ?></h3>
                                    <p>Member since <?php echo date('F Y', strtotime($user_data['date_registered'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="profile-details">
                                <div class="detail-item">
                                    <span class="detail-label">Full Name:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($user_data['full_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Email Address:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($user_data['email']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Phone Number:</span>
                                    <span class="detail-value"><?php echo !empty($user_data['phone_number']) ? htmlspecialchars($user_data['phone_number']) : 'Not set'; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Account Type:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($user_data['user_type'] ?? 'Standard'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Your Stats -->
                    <div class="content-section">
                        <h3 style="color: var(--dark-green); margin-bottom: 20px;">
                            <i class="fas fa-chart-line"></i> Your Statistics
                        </h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $stats['total_plans']; ?></div>
                                <div class="stat-label">Total Plans</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $stats['plans_generated']; ?></div>
                                <div class="stat-label">Generated</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $stats['pantry_items']; ?></div>
                                <div class="stat-label">Pantry Items</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">KES <?php echo number_format($stats['budget_used'], 0); ?></div>
                                <div class="stat-label">Budget Used</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Forms -->
                <div>
                    <!-- Update Profile Form -->
                    <div class="content-section">
                        <div class="section-header">
                            <h2><i class="fas fa-edit"></i> Update Profile</h2>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                    
                    <!-- Change Password Form -->
                    <div class="content-section">
                        <div class="section-header">
                            <h2><i class="fas fa-key"></i> Change Password</h2>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="content-section">
                        <h3 style="color: var(--dark-green); margin-bottom: 20px;">
                            <i class="fas fa-history"></i> Recent Activity
                        </h3>
                        <div class="activity-log">
                            <?php 
                            $activity_query = "SELECT * FROM user_activity 
                                              WHERE user_id = $user_id 
                                              ORDER BY created_at DESC 
                                              LIMIT 5";
                            $activity_result = mysqli_query($conn, $activity_query);
                            
                            if (mysqli_num_rows($activity_result) > 0):
                                while ($activity = mysqli_fetch_assoc($activity_result)):
                            ?>
                                <div class="activity-item">
                                    <div class="activity-content">
                                        <i class="fas fa-check-circle activity-icon"></i>
                                        <span><?php echo htmlspecialchars($activity['activity_details']); ?></span>
                                    </div>
                                    <span class="activity-time">
                                        <?php echo date('d M, h:i A', strtotime($activity['created_at'])); ?>
                                    </span>
                                </div>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                                <p style="color: var(--text-light); text-align: center; padding: 20px;">
                                    No recent activity
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Danger Zone -->
            <div class="danger-zone">
                <h4><i class="fas fa-exclamation-triangle"></i> Danger Zone</h4>
                <p style="color: var(--text-light); margin-bottom: 20px;">
                    Once you delete your account, there is no going back. Please be certain.
                </p>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash"></i> Delete My Account
                </button>
            </div>
        </main>
    </div>
    
    <script>
        function confirmDelete() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                alert('Account deletion feature coming soon!');
                // window.location.href = 'delete-account.php';
            }
        }
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const passwordForm = document.querySelector('form[action=""][method="POST"]:nth-of-type(2)');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    const newPass = document.getElementById('new_password').value;
                    const confirmPass = document.getElementById('confirm_password').value;
                    
                    if (newPass !== confirmPass) {
                        e.preventDefault();
                        alert('New passwords do not match!');
                        return false;
                    }
                    
                    if (newPass.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters long!');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>