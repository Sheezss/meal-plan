<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

$current_admin_id = $_SESSION['user_id'];
$view_user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get user details
$user_query = "SELECT * FROM users WHERE user_id = $view_user_id";
$user_result = mysqli_query($conn, $user_query);

if (!$user_result || mysqli_num_rows($user_result) == 0) {
    header("Location: admin-dashboard.php#users");
    exit();
}

$user = mysqli_fetch_assoc($user_result);

// Get user statistics
$stats_query = "SELECT 
                COUNT(DISTINCT mp.mealplan_id) as total_plans,
                COUNT(DISTINCT m.meal_id) as total_meals,
                COUNT(DISTINCT p.pantry_id) as pantry_items,
                COUNT(DISTINCT ua.activity_id) as total_activities
                FROM users u
                LEFT JOIN meal_plans mp ON u.user_id = mp.user_id
                LEFT JOIN meals m ON mp.mealplan_id = m.mealplan_id
                LEFT JOIN pantry p ON u.user_id = p.user_id
                LEFT JOIN user_activity ua ON u.user_id = ua.user_id
                WHERE u.user_id = $view_user_id
                GROUP BY u.user_id";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get user's meal plans
$mealplans_query = "SELECT * FROM meal_plans 
                    WHERE user_id = $view_user_id 
                    ORDER BY created_at DESC 
                    LIMIT 10";
$mealplans_result = mysqli_query($conn, $mealplans_query);

// Get user's recent activity
$activity_query = "SELECT * FROM user_activity 
                   WHERE user_id = $view_user_id 
                   ORDER BY created_at DESC 
                   LIMIT 20";
$activity_result = mysqli_query($conn, $activity_query);

// Get user's budget
$budget_query = "SELECT * FROM user_budgets 
                 WHERE user_id = $view_user_id AND status = 'Active'
                 ORDER BY created_at DESC 
                 LIMIT 1";
$budget_result = mysqli_query($conn, $budget_query);
$budget = mysqli_fetch_assoc($budget_result);

// Get user's preferences
$preferences_query = "SELECT * FROM user_preferences WHERE user_id = $view_user_id";
$preferences_result = mysqli_query($conn, $preferences_query);
$preferences = mysqli_fetch_assoc($preferences_result);

// Get user's pantry items
$pantry_query = "SELECT * FROM pantry 
                 WHERE user_id = $view_user_id 
                 ORDER BY added_date DESC 
                 LIMIT 15";
$pantry_result = mysqli_query($conn, $pantry_query);

// Calculate account age
$date_registered = new DateTime($user['date_registered']);
$now = new DateTime();
$account_age = $date_registered->diff($now);

// Check if user is active today
$active_today_query = "SELECT activity_id FROM user_activity 
                       WHERE user_id = $view_user_id 
                       AND DATE(created_at) = CURDATE() 
                       LIMIT 1";
$active_today_result = mysqli_query($conn, $active_today_query);
$active_today = mysqli_num_rows($active_today_result) > 0;

// Get last login
$last_login_query = "SELECT created_at FROM user_activity 
                     WHERE user_id = $view_user_id AND activity_type = 'login' 
                     ORDER BY created_at DESC LIMIT 1";
$last_login_result = mysqli_query($conn, $last_login_query);
$last_login = mysqli_fetch_assoc($last_login_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - <?php echo htmlspecialchars($user['full_name']); ?></title>
    <style>
        :root {
            --admin-purple: #8e44ad;
            --admin-dark: #7d3c98;
            --primary-green: #27ae60;
            --accent-blue: #3498db;
            --accent-orange: #e67e22;
            --accent-red: #e74c3c;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --border-color: #e1f5e1;
            --light-bg: #f9fdf7;
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
            padding: 30px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .header h1 {
            color: var(--admin-purple);
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
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
            background: var(--admin-purple);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--admin-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(142, 68, 173, 0.3);
        }
        
        .btn-outline {
            background: white;
            color: var(--admin-purple);
            border: 2px solid var(--admin-purple);
        }
        
        .btn-outline:hover {
            background: #f8f0ff;
        }
        
        .btn-warning {
            background: var(--accent-orange);
            color: white;
        }
        
        .btn-danger {
            background: var(--accent-red);
            color: white;
        }
        
        .profile-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--admin-purple), #9b59b6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: bold;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-name {
            font-size: 32px;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .profile-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: var(--admin-purple);
            color: white;
        }
        
        .badge-user {
            background: #7f8c8d;
            color: white;
        }
        
        .badge-active {
            background: #27ae60;
            color: white;
        }
        
        .badge-inactive {
            background: #95a5a6;
            color: white;
        }
        
        .profile-meta {
            display: flex;
            gap: 30px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-light);
        }
        
        .meta-item i {
            width: 20px;
            color: var(--admin-purple);
        }
        
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
            border: 2px solid var(--border-color);
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 24px;
        }
        
        .stat-icon.plans { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .stat-icon.meals { background: linear-gradient(135deg, #3498db, #2980b9); }
        .stat-icon.pantry { background: linear-gradient(135deg, #e67e22, #f39c12); }
        .stat-icon.activities { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .content-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .section-header h2 {
            color: var(--admin-purple);
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: var(--text-light);
            font-weight: 500;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .preference-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin: 2px;
        }
        
        .preference-yes {
            background: #d4edda;
            color: #155724;
            border: 1px solid #28a745;
        }
        
        .preference-no {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #dc3545;
        }
        
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--admin-purple);
            margin-right: 12px;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-text {
            color: var(--text-dark);
            margin-bottom: 3px;
            font-size: 14px;
        }
        
        .activity-time {
            font-size: 11px;
            color: var(--text-light);
        }
        
        .mealplan-card {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
            border-left: 4px solid var(--primary-green);
        }
        
        .mealplan-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .mealplan-name {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .mealplan-date {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .mealplan-cost {
            color: var(--primary-green);
            font-weight: 600;
            font-size: 14px;
        }
        
        .pantry-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
        }
        
        .pantry-item {
            background: var(--light-bg);
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .pantry-item-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .pantry-item-details {
            font-size: 11px;
            color: var(--text-light);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            body { padding: 15px; }
            .content-grid { grid-template-columns: 1fr; }
            .profile-header { flex-direction: column; text-align: center; }
            .profile-meta { justify-content: center; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-user-circle"></i> 
                User Profile: <?php echo htmlspecialchars($user['full_name']); ?>
            </h1>
            <div>
                <a href="admin-dashboard.php#users" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </div>
        </div>
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <div class="profile-name">
                    <?php echo htmlspecialchars($user['full_name']); ?>
                    <?php if ($user['is_admin'] == 1): ?>
                        <span class="profile-badge badge-admin">
                            <i class="fas fa-shield-alt"></i> Administrator
                        </span>
                    <?php else: ?>
                        <span class="profile-badge badge-user">
                            <i class="fas fa-user"></i> Regular User
                        </span>
                    <?php endif; ?>
                    <?php if ($active_today): ?>
                        <span class="profile-badge badge-active">
                            <i class="fas fa-circle"></i> Active Today
                        </span>
                    <?php else: ?>
                        <span class="profile-badge badge-inactive">
                            <i class="fas fa-circle"></i> Inactive
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="profile-meta">
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-phone"></i>
                        <?php echo $user['phone_number'] ? htmlspecialchars($user['phone_number']) : 'Not provided'; ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-tag"></i>
                        <?php echo htmlspecialchars($user['user_type']); ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        Member for <?php echo $account_age->y; ?> years, <?php echo $account_age->m; ?> months
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon plans">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_plans'] ?? 0; ?></div>
                <div class="stat-label">Meal Plans</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon meals">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_meals'] ?? 0; ?></div>
                <div class="stat-label">Meals Planned</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pantry">
                    <i class="fas fa-archive"></i>
                </div>
                <div class="stat-value"><?php echo $stats['pantry_items'] ?? 0; ?></div>
                <div class="stat-label">Pantry Items</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon activities">
                    <i class="fas fa-history"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_activities'] ?? 0; ?></div>
                <div class="stat-label">Activities</div>
            </div>
        </div>
        
        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Left Column -->
            <div>
                <!-- Account Information -->
                <div class="content-section">
                    <div class="section-header">
                        <h2><i class="fas fa-id-card"></i> Account Information</h2>
                    </div>
                    
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">User ID</span>
                            <span class="info-value">#<?php echo $user['user_id']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email Address</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone Number</span>
                            <span class="info-value"><?php echo $user['phone_number'] ? htmlspecialchars($user['phone_number']) : '<span style="color: var(--text-light);">Not provided</span>'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Account Type</span>
                            <span class="info-value">
                                <span class="badge" style="background: <?php echo $user['user_type'] == 'Nutritionist' ? '#27ae60' : ($user['user_type'] == 'Family' ? '#f39c12' : '#3498db'); ?>; color: white; padding: 5px 12px;">
                                    <?php echo htmlspecialchars($user['user_type']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Account Status</span>
                            <span class="info-value">
                                <?php if ($active_today): ?>
                                    <span style="color: #27ae60;"><i class="fas fa-circle"></i> Active Today</span>
                                <?php else: ?>
                                    <span style="color: #95a5a6;"><i class="fas fa-circle"></i> Inactive</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date Registered</span>
                            <span class="info-value"><?php echo date('F j, Y', strtotime($user['date_registered'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Login</span>
                            <span class="info-value">
                                <?php if ($last_login): ?>
                                    <?php echo date('F j, Y h:i A', strtotime($last_login['created_at'])); ?>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">Never logged in</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Admin Actions -->
                    <div class="action-buttons">
                        <?php if ($user['is_admin'] == 0): ?>
                            <a href="admin-create.php?promote=<?php echo $user['user_id']; ?>" class="btn btn-warning" onclick="return confirm('Are you sure you want to make this user an administrator?')">
                                <i class="fas fa-user-shield"></i> Make Administrator
                            </a>
                        <?php else: ?>
                            <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                <a href="admin-remove.php?id=<?php echo $user['user_id']; ?>" class="btn btn-outline" onclick="return confirm('Are you sure you want to remove admin privileges?')">
                                    <i class="fas fa-user"></i> Remove Administrator
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                            <a href="admin-delete-user.php?id=<?php echo $user['user_id']; ?>&confirm=1" class="btn btn-danger" onclick="return confirm('⚠️ WARNING: This will permanently delete this user and all their data!\n\nAre you absolutely sure?')">
                                <i class="fas fa-trash"></i> Delete User
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Meal Plans -->
                <div class="content-section">
                    <div class="section-header">
                        <h2><i class="fas fa-calendar-alt"></i> Recent Meal Plans</h2>
                        <span style="color: var(--text-light); font-size: 13px;">
                            Total: <?php echo $stats['total_plans'] ?? 0; ?>
                        </span>
                    </div>
                    
                    <?php if (mysqli_num_rows($mealplans_result) > 0): ?>
                        <?php while ($plan = mysqli_fetch_assoc($mealplans_result)): ?>
                            <div class="mealplan-card">
                                <div class="mealplan-header">
                                    <span class="mealplan-name">
                                        <i class="fas fa-utensils" style="color: var(--primary-green);"></i>
                                        <?php echo htmlspecialchars($plan['name']); ?>
                                    </span>
                                    <span class="mealplan-date">
                                        <?php echo date('M d, Y', strtotime($plan['created_at'])); ?>
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 13px; color: var(--text-light);">
                                        <?php echo date('M d', strtotime($plan['start_date'])); ?> - 
                                        <?php echo date('M d', strtotime($plan['end_date'])); ?>
                                    </span>
                                    <span class="mealplan-cost">
                                        KES <?php echo number_format($plan['total_cost'], 0); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: var(--text-light);">
                            <i class="fas fa-calendar-times" style="font-size: 40px; margin-bottom: 15px;"></i>
                            <p>No meal plans created yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Dietary Preferences -->
                <div class="content-section">
                    <div class="section-header">
                        <h2><i class="fas fa-sliders-h"></i> Dietary Preferences</h2>
                    </div>
                    
                    <?php if ($preferences): ?>
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                                <span style="font-weight: 600;">Diet Type:</span>
                                <span style="color: var(--admin-purple); font-weight: 600;"><?php echo htmlspecialchars($preferences['diet_type'] ?? 'Balanced'); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <span style="font-weight: 600;">Meals per day:</span>
                                <span style="color: var(--primary-green); font-weight: 600;"><?php echo $preferences['meals_per_day'] ?? 3; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <span style="font-weight: 600;">Cuisine Preference:</span>
                                <span><?php echo htmlspecialchars($preferences['cuisine_pref'] ?? 'Kenyan'); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <span style="font-weight: 600;">Spicy Level:</span>
                                <span><?php echo htmlspecialchars($preferences['spicy_level'] ?? 'Medium'); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="font-weight: 600;">Cooking Time:</span>
                                <span><?php echo htmlspecialchars($preferences['cooking_time'] ?? '30-45 minutes'); ?></span>
                            </div>
                        </div>
                        
                        <h4 style="color: var(--text-dark); margin-bottom: 15px; font-size: 16px;">Restrictions & Preferences</h4>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <?php
                            $restrictions = [
                                'vegetarian' => 'Vegetarian',
                                'vegan' => 'Vegan',
                                'gluten_free' => 'Gluten Free',
                                'low_carb' => 'Low Carb',
                                'low_fat' => 'Low Fat',
                                'high_protein' => 'High Protein',
                                'avoid_pork' => 'No Pork',
                                'avoid_beef' => 'No Beef',
                                'avoid_fish' => 'No Fish',
                                'avoid_dairy' => 'No Dairy',
                                'avoid_eggs' => 'No Eggs',
                                'avoid_nuts' => 'No Nuts',
                                'diabetic' => 'Diabetic',
                                'hypertension' => 'Low Sodium',
                                'pregnancy' => 'Pregnancy'
                            ];
                            
                            foreach ($restrictions as $key => $label):
                                if (!empty($preferences[$key]) && $preferences[$key] == 1):
                            ?>
                                <span class="preference-tag preference-yes">
                                    <i class="fas fa-check-circle"></i> <?php echo $label; ?>
                                </span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                            
                            <?php if (empty(array_filter($preferences, function($key) use ($restrictions) { 
                                return in_array($key, array_keys($restrictions)) && $preferences[$key] == 1; 
                            }, ARRAY_FILTER_USE_KEY))): ?>
                                <span style="color: var(--text-light);">No dietary restrictions set</span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: var(--text-light);">
                            <i class="fas fa-sliders-h" style="font-size: 40px; margin-bottom: 15px;"></i>
                            <p>No dietary preferences set.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Budget Information -->
                <div class="content-section">
                    <div class="section-header">
                        <h2><i class="fas fa-wallet"></i> Budget Information</h2>
                    </div>
                    
                    <?php if ($budget): ?>
                        <div class="info-list">
                            <div class="info-item">
                                <span class="info-label">Weekly Budget</span>
                                <span class="info-value" style="color: var(--primary-green); font-size: 20px;">
                                    KES <?php echo number_format($budget['amount'], 0); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Current Spending</span>
                                <span class="info-value" style="color: var(--accent-orange);">
                                    KES <?php echo number_format($budget['current_spending'] ?? 0, 0); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Budget Period</span>
                                <span class="info-value">
                                    <?php echo date('M d', strtotime($budget['start_date'])); ?> - 
                                    <?php echo date('M d', strtotime($budget['end_date'])); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status</span>
                                <span class="info-value">
                                    <span style="color: #27ae60;">
                                        <i class="fas fa-circle"></i> <?php echo $budget['status']; ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Budget Progress Bar -->
                        <?php 
                        $spent_percent = $budget['amount'] > 0 ? min(100, ($budget['current_spending'] / $budget['amount']) * 100) : 0;
                        ?>
                        <div style="margin-top: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span>Budget Used</span>
                                <span><?php echo round($spent_percent, 1); ?>%</span>
                            </div>
                            <div style="height: 8px; background: var(--border-color); border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; width: <?php echo $spent_percent; ?>%; 
                                            background: <?php echo $spent_percent > 80 ? '#e74c3c' : ($spent_percent > 60 ? '#f39c12' : '#27ae60'); ?>;">
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: var(--text-light);">
                            <i class="fas fa-coins" style="font-size: 40px; margin-bottom: 15px;"></i>
                            <p>No active budget set.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pantry Items -->
                <div class="content-section">
                    <div class="section-header">
                        <h2><i class="fas fa-archive"></i> Pantry Items</h2>
                        <span style="color: var(--text-light); font-size: 13px;">
                            Total: <?php echo $stats['pantry_items'] ?? 0; ?>
                        </span>
                    </div>
                    
                    <?php if (mysqli_num_rows($pantry_result) > 0): ?>
                        <div class="pantry-grid">
                            <?php while ($item = mysqli_fetch_assoc($pantry_result)): ?>
                                <div class="pantry-item">
                                    <div class="pantry-item-name">
                                        <i class="fas fa-<?php echo $item['category'] == 'Vegetable' ? 'carrot' : ($item['category'] == 'Fruit' ? 'apple-alt' : 'box'); ?>" 
                                           style="color: var(--primary-green); margin-right: 5px;"></i>
                                        <?php echo htmlspecialchars($item['item_name']); ?>
                                    </div>
                                    <div class="pantry-item-details">
                                        <?php echo number_format($item['quantity'], 1); ?> <?php echo $item['unit']; ?>
                                        <?php if ($item['expiry_date']): ?>
                                            <br>Expires: <?php echo date('M d, Y', strtotime($item['expiry_date'])); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <?php if ($stats['pantry_items'] > 15): ?>
                            <div style="text-align: center; margin-top: 15px;">
                                <a href="admin-pantry.php?user_id=<?php echo $user['user_id']; ?>" style="color: var(--admin-purple); font-size: 13px;">
                                    View all <?php echo $stats['pantry_items']; ?> items →
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: var(--text-light);">
                            <i class="fas fa-box-open" style="font-size: 40px; margin-bottom: 15px;"></i>
                            <p>No pantry items added.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="content-section">
            <div class="section-header">
                <h2><i class="fas fa-history"></i> Recent Activity</h2>
                <span style="color: var(--text-light); font-size: 13px;">
                    Last 20 activities
                </span>
            </div>
            
            <div class="activity-feed">
                <?php if (mysqli_num_rows($activity_result) > 0): ?>
                    <?php while ($activity = mysqli_fetch_assoc($activity_result)): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php 
                                    echo $activity['activity_type'] == 'plan_generated' ? 'magic' : 
                                         ($activity['activity_type'] == 'plan_saved' ? 'save' : 
                                         ($activity['activity_type'] == 'login' ? 'sign-in-alt' : 
                                         ($activity['activity_type'] == 'signup' ? 'user-plus' : 
                                         ($activity['activity_type'] == 'preferences_updated' ? 'sliders-h' : 'circle')))); 
                                ?>"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-text">
                                    <?php 
                                    $action = str_replace('_', ' ', $activity['activity_type']);
                                    echo ucwords($action); 
                                    ?>
                                    <?php if ($activity['activity_details']): ?>
                                        - <?php echo htmlspecialchars($activity['activity_details']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-time">
                                    <i class="far fa-clock"></i> 
                                    <?php echo date('F j, Y \a\t h:i A', strtotime($activity['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-light);">
                        <i class="fas fa-history" style="font-size: 50px; margin-bottom: 20px;"></i>
                        <p>No activity recorded for this user.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>