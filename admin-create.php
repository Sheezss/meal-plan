<?php
// admin-create.php - Create new admin accounts (Admins only)
session_start();
require_once 'config.php';

// Check if user is logged in AND is admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';

// Get current admin info for audit trail
$current_admin_id = $_SESSION['user_id'];
$current_admin_query = "SELECT full_name, email FROM users WHERE user_id = $current_admin_id";
$current_admin_result = mysqli_query($conn, $current_admin_query);
$current_admin = mysqli_fetch_assoc($current_admin_result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        $message = "All fields are required!";
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $message_type = 'error';
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters!";
        $message_type = 'error';
    } else {
        // Check if email already exists
        $check_query = "SELECT user_id, is_admin FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $existing_user = mysqli_fetch_assoc($check_result);
            
            if ($existing_user['is_admin'] == 1) {
                $message = "This user is already an admin!";
                $message_type = 'error';
            } else {
                // Promote existing user to admin
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET 
                              is_admin = 1, 
                              user_type = 'Administrator',
                              password = '$hashed_password'
                              WHERE email = '$email'";
                
                if (mysqli_query($conn, $update_sql)) {
                    // Log the action
                    $log_sql = "INSERT INTO user_activity (user_id, activity_type, activity_details) 
                               VALUES ($current_admin_id, 'admin_created', 
                               'Promoted $email to administrator')";
                    mysqli_query($conn, $log_sql);
                    
                    $message = "User promoted to Administrator successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error: " . mysqli_error($conn);
                    $message_type = 'error';
                }
            }
        } else {
            // Create new admin
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO users (full_name, email, password, user_type, phone_number, date_registered, is_admin) 
                          VALUES ('$full_name', '$email', '$hashed_password', 'Administrator', '$phone', CURDATE(), 1)";
            
            if (mysqli_query($conn, $insert_sql)) {
                $new_admin_id = mysqli_insert_id($conn);
                
                // Log the action
                $log_sql = "INSERT INTO user_activity (user_id, activity_type, activity_details) 
                           VALUES ($current_admin_id, 'admin_created', 
                           'Created new administrator: $email')";
                mysqli_query($conn, $log_sql);
                
                // Also log for the new admin
                $log_new_admin = "INSERT INTO user_activity (user_id, activity_type, activity_details) 
                                 VALUES ($new_admin_id, 'account_created', 
                                 'Administrator account created by {$current_admin['full_name']}')";
                mysqli_query($conn, $log_new_admin);
                
                $message = "New administrator created successfully!";
                $message_type = 'success';
            } else {
                $message = "Error: " . mysqli_error($conn);
                $message_type = 'error';
            }
        }
    }
}

// Get all current admins
$admins_query = "SELECT user_id, full_name, email, user_type, date_registered 
                 FROM users 
                 WHERE is_admin = 1 
                 ORDER BY date_registered DESC";
$admins_result = mysqli_query($conn, $admins_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Administrator - Meal Plan System</title>
    <style>
        :root {
            /* Nutrition-based color palette - matching admin dashboard */
            --calories-red: #e74c3c;
            --calories-light: #f1948a;
            --protein-blue: #3498db;
            --protein-light: #85c1e9;
            --carbs-yellow: #f39c12;
            --carbs-light: #f8c471;
            --fats-purple: #9b59b6;
            --fats-light: #d7bde2;
            --vitamins-green: #27ae60;
            --vitamins-light: #a9dfbf;
            --minerals-teal: #1abc9c;
            --minerals-light: #76d7c4;
            
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --border-color: #e5e8e8;
            --light-bg: #f8f9f9;
            --card-bg: #ffffff;
            --shadow: rgba(0, 0, 0, 0.1);
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
        
        /* Sidebar with nutrition gradient - matching admin dashboard */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--text-dark), #1a2632);
            color: white;
            padding: 25px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px var(--shadow);
        }
        
        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--vitamins-green), var(--minerals-teal));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-right: 15px;
        }
        
        .logo-text h2 {
            color: white;
            font-size: 22px;
        }
        
        .logo-text p {
            color: rgba(255,255,255,0.7);
            font-size: 12px;
        }
        
        .admin-welcome {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .admin-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--vitamins-green), var(--minerals-teal));
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
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .nav-menu a.active {
            background: var(--vitamins-green);
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
            color: var(--text-dark);
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .welcome-message h1 i {
            color: var(--vitamins-green);
            margin-right: 10px;
        }
        
        .welcome-message p {
            color: var(--text-light);
            font-size: 16px;
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
            background: var(--vitamins-green);
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e8449;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-outline {
            background: white;
            color: var(--vitamins-green);
            border: 2px solid var(--vitamins-green);
        }
        
        .btn-outline:hover {
            background: var(--vitamins-light);
        }
        
        .btn-warning {
            background: var(--carbs-yellow);
            color: white;
        }
        
        .btn-danger {
            background: var(--calories-red);
            color: white;
        }
        
        .btn-info {
            background: var(--protein-blue);
            color: white;
        }
        
        .content-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px var(--shadow);
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
            color: var(--text-dark);
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-header h2 i {
            color: var(--vitamins-green);
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left-color: var(--vitamins-green);
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: var(--calories-red);
        }
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border-left-color: var(--protein-blue);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--vitamins-green);
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .admin-table th {
            background: var(--vitamins-light);
            color: var(--text-dark);
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid var(--border-color);
        }
        
        .admin-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .admin-table tr:hover {
            background: var(--light-bg);
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: var(--vitamins-green);
            color: white;
        }
        
        .badge-super {
            background: var(--calories-red);
            color: white;
        }
        
        .badge-info {
            background: var(--protein-light);
            color: var(--text-dark);
        }
        
        .security-note {
            background: #fff3cd;
            border-left: 4px solid var(--carbs-yellow);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #856404;
        }
        
        .security-note i {
            margin-right: 8px;
            color: #856404;
        }
        
        .strength-indicator {
            margin-top: 5px;
            font-size: 11px;
        }
        
        .guidelines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .guideline-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }
        
        .guideline-card h4 {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .guideline-card ul {
            list-style: none;
            padding-left: 0;
        }
        
        .guideline-card li {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .activity-item {
            font-size: 12px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--vitamins-green);
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
            
            .form-grid {
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
                    <i class="fas fa-leaf"></i>
                </div>
                <div class="logo-text">
                    <h2>NutriPlan KE</h2>
                    <p>Admin Panel</p>
                </div>
            </div>
            
            <div class="admin-welcome">
                <div class="admin-avatar">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3><?php echo htmlspecialchars($current_admin['full_name']); ?></h3>
                <p style="color: var(--vitamins-green); font-size: 13px;">Nutrition Administrator</p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="admin-dashboard.php"><i class="fas fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="admin-create.php" class="active"><i class="fas fa-user-plus"></i> Create Admin</a></li>
                <li><a href="admin-dashboard.php#users"><i class="fas fa-users-cog"></i> Manage Users</a></li>
                <li><a href="admin-dashboard.php#recipes"><i class="fas fa-utensils"></i> Manage Recipes</a></li>
                <li><a href="dashboard.php"><i class="fas fa-exchange-alt"></i> Switch to User</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-header">
                <div class="welcome-message">
                    <h1><i class="fas fa-user-shield"></i> Create Administrator</h1>
                    <p>Add new administrators to the nutrition system</p>
                </div>
                <div class="header-actions">
                    <span class="badge badge-admin">
                        <i class="fas fa-shield-alt"></i> Admin Access
                    </span>
                </div>
            </div>
            
            <!-- Security Notice -->
            <div class="security-note">
                <i class="fas fa-shield-alt"></i>
                <strong>Security Notice:</strong> Only existing administrators can create new admin accounts. 
                All admin creation actions are logged and monitored. Regular users cannot become admins through signup.
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Create Admin Form -->
            <div class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-user-plus"></i> New Administrator</h2>
                </div>
                
                <form method="POST" action="" onsubmit="return validateForm()">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                   placeholder="Enter full name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="admin@example.com" required>
                            <small style="color: var(--text-light); font-size: 11px;">
                                <i class="fas fa-info-circle"></i> If email exists, user will be promoted to admin
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Min. 8 characters" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   placeholder="Re-enter password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" class="form-control" 
                                   placeholder="0712 345 678">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Create Administrator
                        </button>
                        <button type="reset" class="btn btn-outline">
                            <i class="fas fa-undo"></i> Clear Form
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Current Administrators List -->
            <div class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-users-cog"></i> Current Administrators</h2>
                    <span class="badge badge-admin">
                        <?php echo mysqli_num_rows($admins_result); ?> Total
                    </span>
                </div>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Date Registered</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($admin = mysqli_fetch_assoc($admins_result)): ?>
                            <tr>
                                <td><strong>#<?php echo $admin['user_id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($admin['full_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td>
                                    <span class="badge badge-admin">
                                        <i class="fas fa-shield-alt"></i> Administrator
                                    </span>
                                    <?php if ($admin['user_id'] == $current_admin_id): ?>
                                        <span class="badge badge-super" style="margin-left: 5px;">
                                            <i class="fas fa-crown"></i> You
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($admin['date_registered'])); ?>
                                    <div style="font-size: 11px; color: var(--text-light);">
                                        <?php 
                                        $days_ago = floor((time() - strtotime($admin['date_registered'])) / (60 * 60 * 24));
                                        echo $days_ago . ' days ago';
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="color: var(--vitamins-green);">
                                        <i class="fas fa-circle" style="font-size: 10px;"></i> Active
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Admin Creation Guidelines -->
            <div class="content-section" style="background: var(--vitamins-light);">
                <h3 style="color: var(--text-dark); margin-bottom: 15px;">
                    <i class="fas fa-clipboard-list" style="color: var(--vitamins-green);"></i> Admin Creation Guidelines
                </h3>
                
                <div class="guidelines-grid">
                    <div class="guideline-card">
                        <h4 style="color: var(--vitamins-green);">
                            <i class="fas fa-check-circle"></i> DO:
                        </h4>
                        <ul>
                            <li><i class="fas fa-check" style="color: var(--vitamins-green);"></i> Use corporate email addresses</li>
                            <li><i class="fas fa-check" style="color: var(--vitamins-green);"></i> Set strong passwords (8+ chars)</li>
                            <li><i class="fas fa-check" style="color: var(--vitamins-green);"></i> Verify identity before granting access</li>
                            <li><i class="fas fa-check" style="color: var(--vitamins-green);"></i> Log all admin creation actions</li>
                        </ul>
                    </div>
                    
                    <div class="guideline-card">
                        <h4 style="color: var(--calories-red);">
                            <i class="fas fa-times-circle"></i> DON'T:
                        </h4>
                        <ul>
                            <li><i class="fas fa-times" style="color: var(--calories-red);"></i> Create admin accounts for regular users</li>
                            <li><i class="fas fa-times" style="color: var(--calories-red);"></i> Use weak passwords</li>
                            <li><i class="fas fa-times" style="color: var(--calories-red);"></i> Share admin credentials</li>
                            <li><i class="fas fa-times" style="color: var(--calories-red);"></i> Create unnecessary admin accounts</li>
                        </ul>
                    </div>
                    
                    <div class="guideline-card">
                        <h4 style="color: var(--protein-blue);">
                            <i class="fas fa-history"></i> Recent Activity:
                        </h4>
                        <?php
                        $audit_query = "SELECT activity_details, created_at FROM user_activity 
                                       WHERE activity_type = 'admin_created' 
                                       ORDER BY created_at DESC LIMIT 5";
                        $audit_result = mysqli_query($conn, $audit_query);
                        if ($audit_result && mysqli_num_rows($audit_result) > 0):
                        ?>
                            <div style="max-height: 150px; overflow-y: auto;">
                                <?php while ($audit = mysqli_fetch_assoc($audit_result)): ?>
                                    <div class="activity-item">
                                        <span class="activity-dot"></span>
                                        <span>
                                            <?php echo date('M d, H:i', strtotime($audit['created_at'])); ?>
                                            - <?php echo htmlspecialchars($audit['activity_details']); ?>
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: var(--text-light); font-size: 13px;">No recent admin creation activity</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Form validation
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long!');
                return false;
            }
            
            return true;
        }
        
        // Auto-format phone number
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.match(/.{1,4}/g).join(' ');
            }
            e.target.value = value;
        });
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            const strengthText = document.createElement('small');
            strengthText.style.display = 'block';
            strengthText.style.marginTop = '5px';
            strengthText.style.fontSize = '11px';
            
            const strengths = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['#e74c3c', '#e67e22', '#f39c12', '#3498db', '#27ae60'];
            
            let existing = this.parentElement.querySelector('.strength-indicator');
            if (existing) existing.remove();
            
            strengthText.className = 'strength-indicator';
            strengthText.innerHTML = `Password Strength: <span style="color: ${colors[strength]};">${strengths[strength]}</span>`;
            
            this.parentElement.appendChild(strengthText);
        });
    </script>
</body>
</html>