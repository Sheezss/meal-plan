<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Get plan ID from URL
$plan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($plan_id == 0) {
    header("Location: meal_plan.php");
    exit();
}

// Get meal plan details
$plan_query = "SELECT * FROM meal_plans WHERE mealplan_id = $plan_id AND user_id = $user_id";
$plan_result = mysqli_query($conn, $plan_query);

if (mysqli_num_rows($plan_result) == 0) {
    header("Location: meal_plan.php");
    exit();
}

$plan = mysqli_fetch_assoc($plan_result);

// Get meals for this plan grouped by day
$meals_query = "SELECT 
                    m.*, 
                    r.recipe_name, 
                    r.description,
                    r.calories,
                    r.protein,
                    r.carbs,
                    r.fats,
                    r.estimated_cost,
                    r.meal_type,
                    DAYNAME(m.scheduled_date) as day_name,
                    DATE_FORMAT(m.scheduled_date, '%Y-%m-%d') as formatted_date
                FROM meals m
                JOIN recipes r ON m.recipe_id = r.recipe_id
                WHERE m.mealplan_id = $plan_id
                ORDER BY m.scheduled_date, FIELD(r.meal_type, 'Breakfast', 'Lunch', 'Dinner', 'Snack')";

$meals_result = mysqli_query($conn, $meals_query);

// Group meals by day
$meals_by_day = [];
$total_calories = 0;
$total_protein = 0;
$total_carbs = 0;
$total_fats = 0;

while ($meal = mysqli_fetch_assoc($meals_result)) {
    $date = $meal['formatted_date'];
    if (!isset($meals_by_day[$date])) {
        $meals_by_day[$date] = [
            'date' => $date,
            'day_name' => $meal['day_name'],
            'meals' => []
        ];
    }
    $meals_by_day[$date]['meals'][] = $meal;
    
    $total_calories += $meal['calories'] ?? 0;
    $total_protein += $meal['protein'] ?? 0;
    $total_carbs += $meal['carbs'] ?? 0;
    $total_fats += $meal['fats'] ?? 0;
}

// Get shopping list for this plan
$shopping_query = "SELECT 
                    sl.*,
                    fi.name as item_name,
                    fi.category,
                    fi.unit as default_unit
                   FROM shopping_lists sl
                   LEFT JOIN food_items fi ON sl.fooditem_id = fi.fooditem_id
                   WHERE sl.mealplan_id = $plan_id
                   ORDER BY sl.status, fi.category, fi.name";
$shopping_result = mysqli_query($conn, $shopping_query);

// Determine plan status
$start_date = strtotime($plan['start_date']);
$end_date = strtotime($plan['end_date']);
$current_date = time();

$status = '';
$status_class = '';
$status_text = '';

if ($current_date >= $start_date && $current_date <= $end_date) {
    $status = 'Active';
    $status_class = 'status-active';
    $status_text = 'This plan is currently active';
} elseif ($current_date < $start_date) {
    $status = 'Upcoming';
    $status_class = 'status-upcoming';
    $status_text = 'This plan starts on ' . date('F d, Y', $start_date);
} else {
    $status = 'Completed';
    $status_class = 'status-completed';
    $status_text = 'This plan ended on ' . date('F d, Y', $end_date);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($plan['name']); ?> - Meal Plan Details</title>
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
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 15px;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-upcoming {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background: #e2e3e5;
            color: #383d41;
        }
        
        /* Plan Info */
        .plan-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
            padding: 20px;
            background: var(--light-bg);
            border-radius: 10px;
        }
        
        .info-item {
            text-align: center;
        }
        
        .info-label {
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-green);
        }
        
        .info-value.small {
            font-size: 18px;
        }
        
        /* Days Grid */
        .days-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .day-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            border: 2px solid var(--border-color);
            transition: all 0.3s;
        }
        
        .day-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: var(--primary-green);
        }
        
        .day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .day-name {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark-green);
        }
        
        .day-date {
            background: var(--light-green);
            color: var(--primary-green);
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .day-meals {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .meal-item {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 15px;
            border-left: 4px solid;
        }
        
        .meal-item.breakfast { border-left-color: #f39c12; }
        .meal-item.lunch { border-left-color: #2ecc71; }
        .meal-item.dinner { border-left-color: #3498db; }
        .meal-item.snack { border-left-color: #9b59b6; }
        
        .meal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .meal-name {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 16px;
        }
        
        .meal-type {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 3px;
        }
        
        .meal-cost {
            color: var(--primary-green);
            font-weight: 600;
        }
        
        .meal-nutrition {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            font-size: 13px;
            color: var(--text-light);
        }
        
        .meal-nutrition span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .meal-nutrition i {
            color: var(--accent-orange);
            font-size: 12px;
        }
        
        /* Shopping List */
        .shopping-items {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .shopping-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .shopping-item:last-child {
            border-bottom: none;
        }
        
        .item-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .item-category {
            background: var(--light-green);
            color: var(--primary-green);
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .item-quantity {
            background: var(--light-bg);
            color: var(--text-dark);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .item-status {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 15px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-purchased {
            background: #d4edda;
            color: #155724;
        }
        
        /* Nutrition Summary */
        .nutrition-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .nutrition-card {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        
        .nutrition-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .nutrition-label {
            font-size: 13px;
            color: var(--text-light);
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
            
            .days-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media print {
            .sidebar, .top-header .btn, .btn, .header-actions {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .content-section {
                box-shadow: none;
                border: 1px solid #ddd;
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
                <p>Viewing meal plan details</p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="meal_plan.php" class="active"><i class="fas fa-calendar-alt"></i> My Meal Plans</a></li>
                <li><a href="pantry.php"><i class="fas fa-utensils"></i> My Pantry</a></li>
                <li><a href="recipes.php"><i class="fas fa-book"></i> Kenyan Recipes</a></li>
                <li><a href="shopping-list.php"><i class="fas fa-shopping-cart"></i> Shopping List</a></li>
                <li><a href="budget.php"><i class="fas fa-wallet"></i> Budget Tracker</a></li>
                <li><a href="preferences.php"><i class="fas fa-sliders-h"></i> My Preferences</a></li>
                <li><a href="create-plan.php"><i class="fas fa-magic"></i> Generate Plan</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-header">
                <div class="welcome-message">
                    <h1><?php echo htmlspecialchars($plan['name']); ?></h1>
                    <p>
                        <?php echo date('F d, Y', strtotime($plan['start_date'])); ?> - 
                        <?php echo date('F d, Y', strtotime($plan['end_date'])); ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo $status; ?>
                        </span>
                    </p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-outline" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Plan
                    </button>
                    <a href="meal_plan.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Plans
                    </a>
                </div>
            </div>
            
            <!-- Plan Overview -->
            <div class="content-section">
                <h2 style="color: var(--dark-green); margin-bottom: 20px;">
                    <i class="fas fa-chart-pie"></i> Plan Overview
                </h2>
                
                <div class="plan-info-grid">
                    <div class="info-item">
                        <div class="info-label">Total Meals</div>
                        <div class="info-value"><?php echo count($meals_by_day) > 0 ? array_sum(array_map(function($day) { return count($day['meals']); }, $meals_by_day)) : 0; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Total Cost</div>
                        <div class="info-value">KES <?php echo number_format($plan['total_cost'], 0); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Total Calories</div>
                        <div class="info-value"><?php echo number_format($total_calories); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value small"><?php echo $status_text; ?></div>
                    </div>
                </div>
                
                <!-- Nutrition Summary -->
                <h3 style="color: var(--dark-green); margin: 30px 0 20px;">
                    <i class="fas fa-chart-bar"></i> Nutrition Summary
                </h3>
                
                <div class="nutrition-grid">
                    <div class="nutrition-card">
                        <div class="nutrition-value" style="color: #e74c3c;"><?php echo number_format($total_calories); ?></div>
                        <div class="nutrition-label">Total Calories</div>
                    </div>
                    <div class="nutrition-card">
                        <div class="nutrition-value" style="color: #3498db;"><?php echo number_format($total_protein, 1); ?>g</div>
                        <div class="nutrition-label">Total Protein</div>
                    </div>
                    <div class="nutrition-card">
                        <div class="nutrition-value" style="color: #f39c12;"><?php echo number_format($total_carbs, 1); ?>g</div>
                        <div class="nutrition-label">Total Carbs</div>
                    </div>
                    <div class="nutrition-card">
                        <div class="nutrition-value" style="color: #9b59b6;"><?php echo number_format($total_fats, 1); ?>g</div>
                        <div class="nutrition-label">Total Fats</div>
                    </div>
                </div>
            </div>
            
            <!-- Daily Meal Plan -->
            <div class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-calendar-week"></i> Daily Meal Schedule</h2>
                </div>
                
                <?php if (!empty($meals_by_day)): ?>
                    <div class="days-grid">
                        <?php foreach ($meals_by_day as $day): ?>
                            <div class="day-card">
                                <div class="day-header">
                                    <span class="day-name"><?php echo $day['day_name']; ?></span>
                                    <span class="day-date"><?php echo date('M d', strtotime($day['date'])); ?></span>
                                </div>
                                
                                <div class="day-meals">
                                    <?php foreach ($day['meals'] as $meal): ?>
                                        <div class="meal-item <?php echo strtolower($meal['meal_type']); ?>">
                                            <div class="meal-header">
                                                <div>
                                                    <div class="meal-name">
                                                        <?php echo htmlspecialchars($meal['recipe_name']); ?>
                                                    </div>
                                                    <div class="meal-type">
                                                        <i class="fas fa-<?php 
                                                            echo $meal['meal_type'] == 'Breakfast' ? 'sun' : 
                                                                 ($meal['meal_type'] == 'Lunch' ? 'sun' : 
                                                                 ($meal['meal_type'] == 'Dinner' ? 'moon' : 'apple-alt')); 
                                                        ?>"></i>
                                                        <?php echo $meal['meal_type']; ?>
                                                    </div>
                                                </div>
                                                <div class="meal-cost">KES <?php echo $meal['estimated_cost']; ?></div>
                                            </div>
                                            
                                            <?php if (!empty($meal['description'])): ?>
                                                <p style="font-size: 13px; color: var(--text-light); margin: 10px 0;">
                                                    <?php echo htmlspecialchars($meal['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="meal-nutrition">
                                                <span><i class="fas fa-fire" style="color: #e74c3c;"></i> <?php echo $meal['calories'] ?? 0; ?> cal</span>
                                                <span><i class="fas fa-drumstick-bite" style="color: #3498db;"></i> <?php echo $meal['protein'] ?? 0; ?>g</span>
                                                <span><i class="fas fa-bread-slice" style="color: #f39c12;"></i> <?php echo $meal['carbs'] ?? 0; ?>g</span>
                                                <span><i class="fas fa-oil-can" style="color: #9b59b6;"></i> <?php echo $meal['fats'] ?? 0; ?>g</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <p>No meals found for this plan.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Shopping List -->
            <?php if (mysqli_num_rows($shopping_result) > 0): ?>
                <div class="content-section">
                    <div class="section-header">
                        <h2><i class="fas fa-shopping-cart"></i> Shopping List for this Plan</h2>
                        <a href="shopping-list.php?plan=<?php echo $plan_id; ?>" class="btn btn-sm btn-primary">
                            View Full Shopping List
                        </a>
                    </div>
                    
                    <div class="shopping-items">
                        <?php 
                        $pending_count = 0;
                        $purchased_count = 0;
                        
                        while ($item = mysqli_fetch_assoc($shopping_result)): 
                            if ($item['status'] == 'Pending') $pending_count++;
                            else $purchased_count++;
                        ?>
                            <div class="shopping-item">
                                <div class="item-info">
                                    <div>
                                        <span style="font-weight: 500;"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                        <span class="item-category"><?php echo htmlspecialchars($item['category'] ?? 'Other'); ?></span>
                                    </div>
                                    <span class="item-quantity">
                                        <?php echo $item['quantity_needed']; ?> <?php echo $item['unit']; ?>
                                    </span>
                                </div>
                                <span class="item-status status-<?php echo strtolower($item['status']); ?>">
                                    <?php echo $item['status']; ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                        <span>Items to buy: <strong style="color: var(--accent-orange);"><?php echo $pending_count; ?></strong></span>
                        <span>Items purchased: <strong style="color: var(--primary-green);"><?php echo $purchased_count; ?></strong></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 20px;">
                <a href="shopping-list.php?plan=<?php echo $plan_id; ?>" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Go to Shopping List
                </a>
                <a href="create-plan.php?edit=<?php echo $plan_id; ?>" class="btn btn-outline">
                    <i class="fas fa-edit"></i> Edit Plan
                </a>
            </div>
        </main>
    </div>
    
    <script>
        function deletePlan(planId) {
            if (confirm('Are you sure you want to delete this meal plan? This action cannot be undone.')) {
                window.location.href = 'delete-plan.php?id=' + planId;
            }
        }
        
        // Print functionality
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>