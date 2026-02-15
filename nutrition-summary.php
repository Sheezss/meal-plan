<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Get user's nutrition goals
$goals_query = "SELECT * FROM nutrition_goals WHERE user_id = $user_id";
$goals_result = mysqli_query($conn, $goals_query);
$nutrition_goals = mysqli_fetch_assoc($goals_result);

if (!$nutrition_goals) {
    // Create default goals
    $default_goals = "INSERT INTO nutrition_goals (user_id, daily_calories, daily_protein, daily_carbs, daily_fats, goal_type) 
                      VALUES ($user_id, 2000, 50.00, 250.00, 65.00, 'Maintenance')";
    mysqli_query($conn, $default_goals);
    $nutrition_goals = [
        'daily_calories' => 2000,
        'daily_protein' => 50.00,
        'daily_carbs' => 250.00,
        'daily_fats' => 65.00,
        'goal_type' => 'Maintenance',
        'active_level' => 'Moderate',
        'weight_kg' => null,
        'height_cm' => null,
        'age' => null,
        'gender' => 'Other'
    ];
}

// Calculate period for summary (default: last 7 days)
$period = isset($_GET['period']) ? $_GET['period'] : 'week';
$end_date = date('Y-m-d');
$start_date = '';

switch ($period) {
    case 'today':
        $start_date = $end_date;
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'all':
        $start_date = '1970-01-01';
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-7 days'));
}

// Get meal plans with nutrition data for the period
$mealplans_query = "SELECT mp.*, 
                   COALESCE(SUM(r.calories), 0) as total_calories,
                   COALESCE(SUM(r.protein), 0) as total_protein,
                   COALESCE(SUM(r.carbs), 0) as total_carbs,
                   COALESCE(SUM(r.fats), 0) as total_fats,
                   COUNT(DISTINCT m.meal_id) as total_meals
                   FROM meal_plans mp
                   LEFT JOIN meals m ON mp.mealplan_id = m.mealplan_id
                   LEFT JOIN recipes r ON m.recipe_id = r.recipe_id
                   WHERE mp.user_id = $user_id 
                   AND DATE(mp.created_at) BETWEEN '$start_date' AND '$end_date'
                   GROUP BY mp.mealplan_id
                   ORDER BY mp.created_at DESC";

$mealplans_result = mysqli_query($conn, $mealplans_query);

// Calculate totals
$total_mealplans = 0;
$total_meals = 0;
$total_calories = 0;
$total_protein = 0;
$total_carbs = 0;
$total_fats = 0;
$total_cost = 0;

$mealplan_data = [];
while ($plan = mysqli_fetch_assoc($mealplans_result)) {
    $mealplan_data[] = $plan;
    $total_mealplans++;
    $total_meals += $plan['total_meals'];
    $total_calories += $plan['total_calories'];
    $total_protein += $plan['total_protein'];
    $total_carbs += $plan['total_carbs'];
    $total_fats += $plan['total_fats'];
    $total_cost += $plan['total_cost'];
}

// Calculate averages if we have data - FIXED DIVISION BY ZERO
$avg_calories = $total_mealplans > 0 ? $total_calories / $total_mealplans : 0;
$avg_protein = $total_mealplans > 0 ? $total_protein / $total_mealplans : 0;
$avg_carbs = $total_mealplans > 0 ? $total_carbs / $total_mealplans : 0;
$avg_fats = $total_mealplans > 0 ? $total_fats / $total_mealplans : 0;

// Calculate percentages of goals - FIXED DIVISION BY ZERO
$calories_percent = 0;
$protein_percent = 0;
$carbs_percent = 0;
$fats_percent = 0;

if ($total_mealplans > 0) {
    $calories_percent = $nutrition_goals['daily_calories'] > 0 ? 
        min(100, ($total_calories / ($nutrition_goals['daily_calories'] * $total_mealplans)) * 100) : 0;
    $protein_percent = $nutrition_goals['daily_protein'] > 0 ? 
        min(100, ($total_protein / ($nutrition_goals['daily_protein'] * $total_mealplans)) * 100) : 0;
    $carbs_percent = $nutrition_goals['daily_carbs'] > 0 ? 
        min(100, ($total_carbs / ($nutrition_goals['daily_carbs'] * $total_mealplans)) * 100) : 0;
    $fats_percent = $nutrition_goals['daily_fats'] > 0 ? 
        min(100, ($total_fats / ($nutrition_goals['daily_fats'] * $total_mealplans)) * 100) : 0;
}

// Get nutritional insights
$insights = [];
if ($total_mealplans > 0 && $total_calories > 0) {
    if ($calories_percent < 70) {
        $insights[] = "You're consuming fewer calories than recommended. Consider adding more nutrient-dense foods.";
    } elseif ($calories_percent > 130) {
        $insights[] = "You're consuming more calories than recommended. Consider portion control.";
    } else {
        $insights[] = "Your calorie intake is within a healthy range. Keep it up!";
    }
    
    if ($protein_percent < 70) {
        $insights[] = "Consider increasing protein intake with beans, lentils, or lean meats.";
    }
    
    if ($carbs_percent > 130) {
        $insights[] = "Your carbohydrate intake is high. Balance with more vegetables and protein.";
    }
    
    if ($fats_percent > 130) {
        $insights[] = "Monitor your fat intake. Choose healthier fats like avocados and nuts.";
    }
} else {
    $insights[] = "No meal plan data available for the selected period. Create a meal plan to see your nutrition summary!";
}

// Handle goal update
if (isset($_POST['update_goals']) || isset($_POST['calculate_tdee'])) {
    $daily_calories = intval($_POST['daily_calories'] ?? 2000);
    $daily_protein = floatval($_POST['daily_protein'] ?? 50);
    $daily_carbs = floatval($_POST['daily_carbs'] ?? 250);
    $daily_fats = floatval($_POST['daily_fats'] ?? 65);
    $goal_type = mysqli_real_escape_string($conn, $_POST['goal_type'] ?? 'Maintenance');
    $active_level = mysqli_real_escape_string($conn, $_POST['active_level'] ?? 'Moderate');
    $weight = floatval($_POST['weight'] ?? 0);
    $height = intval($_POST['height'] ?? 0);
    $age = intval($_POST['age'] ?? 0);
    $gender = mysqli_real_escape_string($conn, $_POST['gender'] ?? 'Other');
    
    // Auto-calculate goals if using TDEE calculator
    if (isset($_POST['calculate_tdee'])) {
        if ($weight > 0 && $height > 0 && $age > 0) {
            // Calculate BMR (Basal Metabolic Rate)
            if ($gender == 'Male') {
                $bmr = 88.362 + (13.397 * $weight) + (4.799 * $height) - (5.677 * $age);
            } elseif ($gender == 'Female') {
                $bmr = 447.593 + (9.247 * $weight) + (3.098 * $height) - (4.330 * $age);
            } else {
                $bmr = 500 + (10 * $weight) + (6.25 * $height) - (5 * $age);
            }
            
            // Apply activity multiplier
            $activity_multipliers = [
                'Sedentary' => 1.2,
                'Light' => 1.375,
                'Moderate' => 1.55,
                'Active' => 1.725,
                'Very Active' => 1.9
            ];
            
            $multiplier = $activity_multipliers[$active_level] ?? 1.55;
            $tdee = $bmr * $multiplier;
            
            // Adjust based on goal
            switch ($goal_type) {
                case 'Weight Loss':
                    $daily_calories = round($tdee * 0.8);
                    $daily_protein = round($weight * 2.2, 1);
                    break;
                case 'Muscle Gain':
                    $daily_calories = round($tdee * 1.1);
                    $daily_protein = round($weight * 2.5, 1);
                    break;
                case 'Maintenance':
                default:
                    $daily_calories = round($tdee);
                    $daily_protein = round($weight * 1.8, 1);
                    break;
            }
            
            // Calculate carbs and fats (40% carbs, 30% fats)
            $carbs_calories = $daily_calories * 0.4;
            $fats_calories = $daily_calories * 0.3;
            $daily_carbs = round($carbs_calories / 4, 1);
            $daily_fats = round($fats_calories / 9, 1);
        }
    }
    
    // Check if goals exist
    $check_goals = "SELECT * FROM nutrition_goals WHERE user_id = $user_id";
    $check_result = mysqli_query($conn, $check_goals);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing goals
        $update_sql = "UPDATE nutrition_goals SET 
                       daily_calories = $daily_calories,
                       daily_protein = $daily_protein,
                       daily_carbs = $daily_carbs,
                       daily_fats = $daily_fats,
                       goal_type = '$goal_type',
                       active_level = '$active_level',
                       weight_kg = " . ($weight > 0 ? $weight : "NULL") . ",
                       height_cm = " . ($height > 0 ? $height : "NULL") . ",
                       age = " . ($age > 0 ? $age : "NULL") . ",
                       gender = '$gender',
                       updated_at = NOW()
                       WHERE user_id = $user_id";
    } else {
        // Insert new goals
        $update_sql = "INSERT INTO nutrition_goals 
                       (user_id, daily_calories, daily_protein, daily_carbs, daily_fats, goal_type, active_level, weight_kg, height_cm, age, gender, updated_at) 
                       VALUES 
                       ($user_id, $daily_calories, $daily_protein, $daily_carbs, $daily_fats, '$goal_type', '$active_level', " . 
                       ($weight > 0 ? $weight : "NULL") . ", " . ($height > 0 ? $height : "NULL") . ", " . ($age > 0 ? $age : "NULL") . ", '$gender', NOW())";
    }
    
    if (mysqli_query($conn, $update_sql)) {
        $success_message = "Nutrition goals updated successfully!";
        // Refresh goals
        $goals_result = mysqli_query($conn, $goals_query);
        $nutrition_goals = mysqli_fetch_assoc($goals_result);
    } else {
        $error_message = "Error updating goals: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrition Summary - Meal Plan System</title>
    <style>
        :root {
            --primary-green: #27ae60;
            --secondary-green: #2ecc71;
            --accent-orange: #e67e22;
            --accent-red: #e74c3c;
            --accent-blue: #3498db;
            --accent-purple: #9b59b6;
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
        
        /* Sidebar styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--dark-green), var(--primary-green));
            color: white;
            padding: 25px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.2);
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
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-green);
            font-size: 20px;
            margin-right: 15px;
        }
        
        .logo-text h2 {
            color: white;
            font-size: 22px;
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
            color: rgba(255,255,255,0.9);
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
            background: rgba(255,255,255,0.2);
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
            margin-left: 250px;
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
            background: var(--primary-green);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--dark-green);
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: white;
            color: var(--primary-green);
            border: 2px solid var(--primary-green);
        }
        
        .btn-outline:hover {
            background: var(--light-green);
        }
        
        .period-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            flex-wrap: wrap;
        }
        
        .period-btn {
            padding: 8px 20px;
            border: 2px solid var(--border-color);
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            color: var(--text-dark);
        }
        
        .period-btn.active {
            background: var(--primary-green);
            color: white;
            border-color: var(--primary-green);
        }
        
        .period-btn:hover:not(.active) {
            background: var(--light-green);
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
        
        /* Stats Grid */
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
        
        .stat-icon.calories { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-icon.protein { background: linear-gradient(135deg, #3498db, #2980b9); }
        .stat-icon.carbs { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-icon.fats { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .stat-icon.cost { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .stat-icon.meals { background: linear-gradient(135deg, #1abc9c, #16a085); }
        
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
        
        /* Progress Bars */
        .progress-container {
            margin-bottom: 25px;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .progress-bar {
            height: 12px;
            background: #ecf0f1;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 6px;
            transition: width 0.5s ease;
        }
        
        .progress-fill.calories { background: linear-gradient(90deg, #e74c3c, #f1948a); }
        .progress-fill.protein { background: linear-gradient(90deg, #3498db, #85c1e9); }
        .progress-fill.carbs { background: linear-gradient(90deg, #f39c12, #f8c471); }
        .progress-fill.fats { background: linear-gradient(90deg, #9b59b6, #d7bde2); }
        
        /* Insights Section */
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .insight-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--primary-green);
        }
        
        .insight-card.warning {
            border-left-color: var(--accent-orange);
        }
        
        .insight-card.info {
            border-left-color: var(--accent-blue);
        }
        
        .insight-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .insight-card.warning .insight-icon {
            color: var(--accent-orange);
        }
        
        .insight-card.info .insight-icon {
            color: var(--accent-blue);
        }
        
        /* Meal Plans Table */
        .nutrition-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .nutrition-table th {
            background: var(--light-bg);
            color: var(--dark-green);
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid var(--border-color);
        }
        
        .nutrition-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .nutrition-table tr:hover {
            background: var(--light-bg);
        }
        
        /* Modal for goals */
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
            border-radius: 15px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
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
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
            border-left-color: #28a745;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        
        /* Print Button */
        .print-btn {
            background: var(--accent-blue);
            color: white;
        }
        
        .print-btn:hover {
            background: #2980b9;
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
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .period-selector {
                flex-direction: column;
            }
            
            .period-btn {
                text-align: center;
            }
        }
        
        @media print {
            .sidebar, .top-header .btn, .period-selector, .btn, .modal {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 20px !important;
            }
            
            .content-section {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                page-break-inside: avoid;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <p>Healthy Living</p>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="pantry.php"><i class="fas fa-archive"></i> My Pantry</a></li>
                <li><a href="meal_plan.php"><i class="fas fa-calendar-alt"></i> Meal Plans</a></li>
                <li><a href="nutrition-summary.php" class="active"><i class="fas fa-chart-pie"></i> Nutrition Summary</a></li>
                <li><a href="shopping-list.php"><i class="fas fa-shopping-cart"></i> Shopping List</a></li>
                <li><a href="budget.php"><i class="fas fa-wallet"></i> Budget</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <div class="welcome-message">
                    <h1>Nutrition Summary</h1>
                    <p>Track your nutritional intake and progress</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openGoalsModal()">
                        <i class="fas fa-bullseye"></i> Set Goals
                    </button>
                    <button class="btn print-btn" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Summary
                    </button>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Period Selector -->
            <div class="period-selector">
                <a href="?period=today" class="period-btn <?php echo $period == 'today' ? 'active' : ''; ?>">
                    Today
                </a>
                <a href="?period=week" class="period-btn <?php echo $period == 'week' ? 'active' : ''; ?>">
                    Last 7 Days
                </a>
                <a href="?period=month" class="period-btn <?php echo $period == 'month' ? 'active' : ''; ?>">
                    Last 30 Days
                </a>
                <a href="?period=all" class="period-btn <?php echo $period == 'all' ? 'active' : ''; ?>">
                    All Time
                </a>
            </div>
            
            <!-- Nutrition Overview -->
            <div class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-chart-line"></i> Nutrition Overview</h2>
                    <span>Period: <?php 
                        if ($period == 'today') echo 'Today';
                        elseif ($period == 'week') echo 'Last 7 Days';
                        elseif ($period == 'month') echo 'Last 30 Days';
                        else echo 'All Time';
                    ?> (<?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>)</span>
                </div>
                
                <?php if ($total_mealplans > 0): ?>
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon calories">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_calories); ?></div>
                        <div class="stat-label">Total Calories</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon protein">
                            <i class="fas fa-drumstick-bite"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_protein, 1); ?>g</div>
                        <div class="stat-label">Total Protein</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon carbs">
                            <i class="fas fa-bread-slice"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_carbs, 1); ?>g</div>
                        <div class="stat-label">Total Carbs</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon fats">
                            <i class="fas fa-oil-can"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($total_fats, 1); ?>g</div>
                        <div class="stat-label">Total Fats</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon cost">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-value">KES <?php echo number_format($total_cost); ?></div>
                        <div class="stat-label">Total Cost</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon meals">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_mealplans; ?></div>
                        <div class="stat-label">Meal Plans</div>
                    </div>
                </div>
                
                <!-- Progress vs Goals -->
                <div style="margin-top: 30px;">
                    <h3 style="color: var(--dark-green); margin-bottom: 20px;">Progress vs Daily Goals</h3>
                    
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Calories (<?php echo number_format($total_calories); ?> / <?php echo number_format($nutrition_goals['daily_calories'] * $total_mealplans); ?>)</span>
                            <span><?php echo number_format($calories_percent, 1); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill calories" style="width: <?php echo $calories_percent; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Protein (<?php echo number_format($total_protein, 1); ?>g / <?php echo number_format($nutrition_goals['daily_protein'] * $total_mealplans, 1); ?>g)</span>
                            <span><?php echo number_format($protein_percent, 1); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill protein" style="width: <?php echo $protein_percent; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Carbs (<?php echo number_format($total_carbs, 1); ?>g / <?php echo number_format($nutrition_goals['daily_carbs'] * $total_mealplans, 1); ?>g)</span>
                            <span><?php echo number_format($carbs_percent, 1); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill carbs" style="width: <?php echo $carbs_percent; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-label">
                            <span>Fats (<?php echo number_format($total_fats, 1); ?>g / <?php echo number_format($nutrition_goals['daily_fats'] * $total_mealplans, 1); ?>g)</span>
                            <span><?php echo number_format($fats_percent, 1); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill fats" style="width: <?php echo $fats_percent; ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-light);">
                        <i class="fas fa-chart-pie" style="font-size: 48px; margin-bottom: 20px; display: block; color: var(--border-color);"></i>
                        <h3 style="color: var(--text-dark); margin-bottom: 10px;">No Data Available</h3>
                        <p style="margin-bottom: 20px;">You haven't created any meal plans for this period.</p>
                        <a href="create-plan.php" class="btn btn-primary">
                            <i class="fas fa-magic"></i> Generate Meal Plan
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Nutritional Insights -->
            <?php if (!empty($insights)): ?>
            <div class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-lightbulb"></i> Nutritional Insights</h2>
                </div>
                
                <div class="insights-grid">
                    <?php foreach ($insights as $insight): ?>
                        <?php 
                        $type = (strpos(strtolower($insight), 'consider') !== false || 
                                strpos(strtolower($insight), 'monitor') !== false) ? 'warning' : 'info';
                        ?>
                        <div class="insight-card <?php echo $type; ?>">
                            <div class="insight-icon">
                                <i class="fas fa-<?php echo $type == 'warning' ? 'exclamation-triangle' : 'info-circle'; ?>"></i>
                            </div>
                            <p><?php echo htmlspecialchars($insight); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Meal Plans with Nutrition -->
            <?php if (!empty($mealplan_data)): ?>
            <div class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Meal Plan Details</h2>
                    <span>Showing <?php echo count($mealplan_data); ?> meal plan<?php echo count($mealplan_data) > 1 ? 's' : ''; ?></span>
                </div>
                
                <div style="overflow-x: auto;">
                    <table class="nutrition-table">
                        <thead>
                            <tr>
                                <th>Plan Name</th>
                                <th>Date</th>
                                <th>Meals</th>
                                <th>Calories</th>
                                <th>Protein</th>
                                <th>Carbs</th>
                                <th>Fats</th>
                                <th>Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mealplan_data as $plan): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($plan['name']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($plan['created_at'])); ?></td>
                                    <td><?php echo $plan['total_meals']; ?></td>
                                    <td><?php echo number_format($plan['total_calories']); ?></td>
                                    <td><?php echo number_format($plan['total_protein'], 1); ?>g</td>
                                    <td><?php echo number_format($plan['total_carbs'], 1); ?>g</td>
                                    <td><?php echo number_format($plan['total_fats'], 1); ?>g</td>
                                    <td>KES <?php echo number_format($plan['total_cost']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Nutrition Goals Modal -->
            <div id="goalsModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><i class="fas fa-bullseye"></i> Set Nutrition Goals</h3>
                        <button class="close-modal" onclick="closeGoalsModal()">&times;</button>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Goal Type</label>
                            <select name="goal_type" class="form-control" required>
                                <option value="Weight Loss" <?php echo ($nutrition_goals['goal_type'] ?? '') == 'Weight Loss' ? 'selected' : ''; ?>>Weight Loss</option>
                                <option value="Muscle Gain" <?php echo ($nutrition_goals['goal_type'] ?? '') == 'Muscle Gain' ? 'selected' : ''; ?>>Muscle Gain</option>
                                <option value="Maintenance" <?php echo ($nutrition_goals['goal_type'] ?? '') == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Activity Level</label>
                            <select name="active_level" class="form-control" required>
                                <option value="Sedentary" <?php echo ($nutrition_goals['active_level'] ?? '') == 'Sedentary' ? 'selected' : ''; ?>>Sedentary (little or no exercise)</option>
                                <option value="Light" <?php echo ($nutrition_goals['active_level'] ?? '') == 'Light' ? 'selected' : ''; ?>>Light (exercise 1-3 days/week)</option>
                                <option value="Moderate" <?php echo ($nutrition_goals['active_level'] ?? '') == 'Moderate' ? 'selected' : ''; ?>>Moderate (exercise 3-5 days/week)</option>
                                <option value="Active" <?php echo ($nutrition_goals['active_level'] ?? '') == 'Active' ? 'selected' : ''; ?>>Active (exercise 6-7 days/week)</option>
                                <option value="Very Active" <?php echo ($nutrition_goals['active_level'] ?? '') == 'Very Active' ? 'selected' : ''; ?>>Very Active (hard exercise daily)</option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Weight (kg)</label>
                                <input type="number" name="weight" class="form-control" step="0.1" 
                                       value="<?php echo $nutrition_goals['weight_kg'] ?? ''; ?>" placeholder="e.g., 70">
                            </div>
                            <div class="form-group">
                                <label>Height (cm)</label>
                                <input type="number" name="height" class="form-control" 
                                       value="<?php echo $nutrition_goals['height_cm'] ?? ''; ?>" placeholder="e.g., 170">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Age</label>
                                <input type="number" name="age" class="form-control" 
                                       value="<?php echo $nutrition_goals['age'] ?? ''; ?>" placeholder="e.g., 30">
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" class="form-control">
                                    <option value="Male" <?php echo ($nutrition_goals['gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($nutrition_goals['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($nutrition_goals['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="margin: 20px 0;">
                            <button type="submit" name="calculate_tdee" class="btn btn-outline" style="width: 100%;">
                                <i class="fas fa-calculator"></i> Calculate Recommended Goals
                            </button>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Daily Calories</label>
                                <input type="number" name="daily_calories" class="form-control" 
                                       value="<?php echo $nutrition_goals['daily_calories']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Daily Protein (g)</label>
                                <input type="number" step="0.1" name="daily_protein" class="form-control" 
                                       value="<?php echo $nutrition_goals['daily_protein']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Daily Carbs (g)</label>
                                <input type="number" step="0.1" name="daily_carbs" class="form-control" 
                                       value="<?php echo $nutrition_goals['daily_carbs']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Daily Fats (g)</label>
                                <input type="number" step="0.1" name="daily_fats" class="form-control" 
                                       value="<?php echo $nutrition_goals['daily_fats']; ?>" required>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; margin-top: 30px;">
                            <button type="submit" name="update_goals" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Goals
                            </button>
                            <button type="button" class="btn btn-outline" onclick="closeGoalsModal()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Modal functions
        function openGoalsModal() {
            document.getElementById('goalsModal').style.display = 'flex';
        }
        
        function closeGoalsModal() {
            document.getElementById('goalsModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('goalsModal');
            if (event.target == modal) {
                closeGoalsModal();
            }
        }
        
        // Nutrition chart (only create if there's data)
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($total_mealplans > 0 && $total_calories > 0): ?>
            const chartContainer = document.querySelector('.content-section');
            if (chartContainer && !document.getElementById('nutritionChart')) {
                const canvas = document.createElement('canvas');
                canvas.id = 'nutritionChart';
                canvas.style.marginTop = '30px';
                canvas.style.maxHeight = '300px';
                
                // Find the progress section or append to container
                const progressSection = document.querySelector('[style*="margin-top: 30px;"]');
                if (progressSection) {
                    progressSection.appendChild(canvas);
                } else {
                    chartContainer.appendChild(canvas);
                }
                
                const ctx = canvas.getContext('2d');
                const nutritionChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Calories', 'Protein', 'Carbs', 'Fats'],
                        datasets: [{
                            label: 'Your Intake',
                            data: [<?php echo $total_calories; ?>, <?php echo $total_protein; ?>, <?php echo $total_carbs; ?>, <?php echo $total_fats; ?>],
                            backgroundColor: [
                                'rgba(231, 76, 60, 0.7)',
                                'rgba(52, 152, 219, 0.7)',
                                'rgba(243, 156, 18, 0.7)',
                                'rgba(155, 89, 182, 0.7)'
                            ],
                            borderColor: [
                                '#e74c3c',
                                '#3498db',
                                '#f39c12',
                                '#9b59b6'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Nutrition Breakdown'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>