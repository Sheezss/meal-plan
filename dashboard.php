<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// 1. Get current week dates for proper calculations
$monday = date('Y-m-d', strtotime('monday this week'));
$sunday = date('Y-m-d', strtotime('sunday this week'));
$today = date('Y-m-d');

// 2. Get user's weekly budget - Check BOTH budget tables
$weekly_budget = 5000; // Default
$budget_amount = 0;

// First try user_budgets table (it has status field)
$budget_query = "SELECT amount, start_date, end_date FROM user_budgets 
                 WHERE user_id = $user_id 
                 AND status = 'Active'
                 AND start_date <= '$today' 
                 AND end_date >= '$today' 
                 LIMIT 1";
$budget_result = mysqli_query($conn, $budget_query);

if ($budget_result && mysqli_num_rows($budget_result) > 0) {
    $budget_row = mysqli_fetch_assoc($budget_result);
    $budget_amount = $budget_row['amount'];
} else {
    // If no active budget in user_budgets, try budgets table
    $budget_query2 = "SELECT amount, start_date, end_date FROM budgets 
                      WHERE user_id = $user_id 
                      AND start_date <= '$today' 
                      AND end_date >= '$today' 
                      LIMIT 1";
    $budget_result2 = mysqli_query($conn, $budget_query2);
    if ($budget_result2 && mysqli_num_rows($budget_result2) > 0) {
        $budget_row = mysqli_fetch_assoc($budget_result2);
        $budget_amount = $budget_row['amount'];
    }
}

$weekly_budget = $budget_amount > 0 ? $budget_amount : $weekly_budget;

// 3. Get budget used THIS WEEK (from meals)
$budget_used = 0;
$budget_used_query = "SELECT COALESCE(SUM(r.estimated_cost), 0) as total_cost
                      FROM meals m
                      JOIN recipes r ON m.recipe_id = r.recipe_id
                      JOIN meal_plans mp ON m.mealplan_id = mp.mealplan_id
                      WHERE mp.user_id = $user_id 
                      AND m.scheduled_date >= '$monday'
                      AND m.scheduled_date <= '$sunday'";
$budget_used_result = mysqli_query($conn, $budget_used_query);
if ($budget_used_result && mysqli_num_rows($budget_used_result) > 0) {
    $budget_row = mysqli_fetch_assoc($budget_used_result);
    $budget_used = $budget_row['total_cost'];
}

// 4. Get user's stats
$stats = [
    'total_plans' => 0,
    'active_plans' => 0,
    'budget_used' => $budget_used,
    'pantry_items' => 0,
    'plans_generated' => 0,
    'generated_this_week' => 0,
    'generated_this_month' => 0,
    'weekly_budget' => $weekly_budget
];

// Total meal plans
$plans_query = "SELECT COUNT(*) as total FROM meal_plans WHERE user_id = $user_id";
$result = mysqli_query($conn, $plans_query);
if ($result && mysqli_num_rows($result) > 0) {
    $stats['total_plans'] = mysqli_fetch_assoc($result)['total'];
}

// Active meal plans (this week)
$active_query = "SELECT COUNT(*) as active FROM meal_plans 
                 WHERE user_id = $user_id 
                 AND start_date <= '$today' 
                 AND end_date >= '$today'";
$result = mysqli_query($conn, $active_query);
if ($result && mysqli_num_rows($result) > 0) {
    $stats['active_plans'] = mysqli_fetch_assoc($result)['active'];
}

// Get plans generated (all time) - using created_at column
$generated_query = "SELECT COUNT(*) as generated_count FROM user_activity 
                    WHERE user_id = $user_id 
                    AND activity_type = 'plan_generated'";
$result = mysqli_query($conn, $generated_query);
if ($result && mysqli_num_rows($result) > 0) {
    $stats['plans_generated'] = mysqli_fetch_assoc($result)['generated_count'];
}

// Get plans generated this week - using created_at column
$week_generated_query = "SELECT COUNT(*) as week_count FROM user_activity 
                         WHERE user_id = $user_id 
                         AND activity_type = 'plan_generated' 
                         AND DATE(created_at) >= '$monday'
                         AND DATE(created_at) <= '$sunday'";
$result = mysqli_query($conn, $week_generated_query);
if ($result && mysqli_num_rows($result) > 0) {
    $stats['generated_this_week'] = mysqli_fetch_assoc($result)['week_count'];
}

// Get plans generated this month
$current_month = date('m');
$current_year = date('Y');
$month_generated_query = "SELECT COUNT(*) as month_count FROM user_activity 
                          WHERE user_id = $user_id 
                          AND activity_type = 'plan_generated' 
                          AND MONTH(created_at) = $current_month 
                          AND YEAR(created_at) = $current_year";
$result = mysqli_query($conn, $month_generated_query);
if ($result && mysqli_num_rows($result) > 0) {
    $stats['generated_this_month'] = mysqli_fetch_assoc($result)['month_count'];
}

// Get recent plan generation activity - using created_at column
$recent_activity_query = "SELECT activity_details, created_at FROM user_activity 
                          WHERE user_id = $user_id 
                          AND activity_type = 'plan_generated' 
                          ORDER BY created_at DESC 
                          LIMIT 5";
$recent_activity_result = mysqli_query($conn, $recent_activity_query);
$recent_activities = [];
if ($recent_activity_result) {
    while ($activity = mysqli_fetch_assoc($recent_activity_result)) {
        // Calculate time difference for display
        $activity_time = strtotime($activity['created_at']);
        $current_time = time();
        $time_diff = $current_time - $activity_time;
        
        if ($time_diff < 3600) { // Less than 1 hour
            $minutes = floor($time_diff / 60);
            $time_display = $minutes == 0 ? 'Just now' : $minutes . ' min ago';
        } elseif ($time_diff < 86400) { // Less than 1 day
            $hours = floor($time_diff / 3600);
            $time_display = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($time_diff < 172800) { // Less than 2 days
            $time_display = 'Yesterday at ' . date('h:i A', $activity_time);
        } else {
            $time_display = date('M d, h:i A', $activity_time);
        }
        
        $activity['display_time'] = $time_display;
        $recent_activities[] = $activity;
    }
}

// 5. Today's meals from database - FIXED: Changed mp.plan_name to mp.name
$today_query = "SELECT m.*, r.recipe_name, r.calories, r.estimated_cost, r.meal_type, mp.name as plan_name
                FROM meals m
                JOIN recipes r ON m.recipe_id = r.recipe_id
                JOIN meal_plans mp ON m.mealplan_id = mp.mealplan_id
                WHERE mp.user_id = $user_id 
                AND m.scheduled_date = CURDATE()
                ORDER BY FIELD(r.meal_type, 'Breakfast', 'Lunch', 'Dinner', 'Snack')";
$today_result = mysqli_query($conn, $today_query);

// Group today's meals by type
$todays_meals = [
    'Breakfast' => [],
    'Lunch' => [],
    'Dinner' => [],
    'Snack' => []
];

if ($today_result) {
    while ($meal = mysqli_fetch_assoc($today_result)) {
        $todays_meals[$meal['meal_type']][] = $meal;
    }
}

// 6. Get recommended recipes
$recommended_query = "SELECT * FROM recipes 
                      WHERE meal_type IN ('Breakfast', 'Lunch', 'Dinner') 
                      ORDER BY RAND() 
                      LIMIT 3";
$recommended_result = mysqli_query($conn, $recommended_query);

// 7. Get meal categories with recipes
$categories = [];
$meal_types = ['Breakfast', 'Lunch', 'Dinner', 'Snack'];

foreach ($meal_types as $type) {
    $cat_query = "SELECT * FROM recipes 
                  WHERE meal_type = '$type' 
                  ORDER BY RAND() 
                  LIMIT 2";
    $cat_result = mysqli_query($conn, $cat_query);
    $categories[$type] = [];
    if ($cat_result) {
        while ($recipe = mysqli_fetch_assoc($cat_result)) {
            $categories[$type][] = $recipe;
        }
    }
}

// 8. Calculate nutrition for today
$nutrition = [
    'total_calories' => 0,
    'total_protein' => 0,
    'total_carbs' => 0,
    'total_fats' => 0
];

$nutrition_query = "SELECT 
    COALESCE(SUM(r.calories), 0) as total_calories,
    COALESCE(SUM(r.protein), 0) as total_protein,
    COALESCE(SUM(r.carbs), 0) as total_carbs,
    COALESCE(SUM(r.fats), 0) as total_fats
    FROM meals m
    JOIN recipes r ON m.recipe_id = r.recipe_id
    JOIN meal_plans mp ON m.mealplan_id = mp.mealplan_id
    WHERE mp.user_id = $user_id 
    AND m.scheduled_date = CURDATE()";

$nutrition_result = mysqli_query($conn, $nutrition_query);
if ($nutrition_result && mysqli_num_rows($nutrition_result) > 0) {
    $nutrition = mysqli_fetch_assoc($nutrition_result);
}

// 9. Get pantry items count
$pantry_count = 0;
$pantry_query = "SELECT COUNT(*) as count FROM pantry WHERE user_id = $user_id";
$pantry_result = mysqli_query($conn, $pantry_query);
if ($pantry_result) {
    $pantry_data = mysqli_fetch_assoc($pantry_result);
    $pantry_count = $pantry_data['count'];
}
$stats['pantry_items'] = $pantry_count;

// 10. Get weekly meal statistics
$weekly_stats_query = "SELECT 
    DAYNAME(m.scheduled_date) as day_name,
    COUNT(m.meal_id) as meal_count,
    COALESCE(SUM(r.estimated_cost), 0) as daily_cost
    FROM meals m
    JOIN recipes r ON m.recipe_id = r.recipe_id
    JOIN meal_plans mp ON m.mealplan_id = mp.mealplan_id
    WHERE mp.user_id = $user_id 
    AND m.scheduled_date >= '$monday'
    AND m.scheduled_date <= '$sunday'
    GROUP BY m.scheduled_date
    ORDER BY m.scheduled_date";

$weekly_stats_result = mysqli_query($conn, $weekly_stats_query);
$weekly_stats = [];
if ($weekly_stats_result) {
    while ($week = mysqli_fetch_assoc($weekly_stats_result)) {
        $weekly_stats[] = $week;
    }
}

// 11. Get user's active budget from user_budgets table
$active_budget_query = "SELECT amount, start_date, end_date, current_spending FROM user_budgets 
                        WHERE user_id = $user_id 
                        AND status = 'Active' 
                        AND start_date <= '$today' 
                        AND end_date >= '$today' 
                        LIMIT 1";
$active_budget_result = mysqli_query($conn, $active_budget_query);
$active_budget = null;
if ($active_budget_result && mysqli_num_rows($active_budget_result) > 0) {
    $active_budget = mysqli_fetch_assoc($active_budget_result);
}

// 12. Get user's nutrition goals
$nutrition_goals = [
    'daily_calories' => 2000,
    'daily_protein' => 50.00,
    'daily_carbs' => 250.00,
    'daily_fats' => 65.00
];

$goals_query = "SELECT * FROM nutrition_goals WHERE user_id = $user_id";
$goals_result = mysqli_query($conn, $goals_query);
if ($goals_result && mysqli_num_rows($goals_result) > 0) {
    $nutrition_goals = mysqli_fetch_assoc($goals_result);
}

// 13. Calculate today's nutrition percentages
$nutrition_percentages = [
    'calories' => $nutrition_goals['daily_calories'] > 0 ? 
                 min(100, ($nutrition['total_calories'] / $nutrition_goals['daily_calories']) * 100) : 0,
    'protein' => $nutrition_goals['daily_protein'] > 0 ? 
                 min(100, ($nutrition['total_protein'] / $nutrition_goals['daily_protein']) * 100) : 0,
    'carbs' => $nutrition_goals['daily_carbs'] > 0 ? 
               min(100, ($nutrition['total_carbs'] / $nutrition_goals['daily_carbs']) * 100) : 0,
    'fats' => $nutrition_goals['daily_fats'] > 0 ? 
              min(100, ($nutrition['total_fats'] / $nutrition_goals['daily_fats']) * 100) : 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Meal Plan System</title>
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
        .todays-plan {
            background: white;
            border: 2px solid var(--primary-green);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        .todays-plan h4 {
            color: var(--dark-green);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .todays-plan h4 i {
            margin-right: 10px;
        }
        .meal-time {
            margin-bottom: 15px;
        }
        .meal-time:last-child {
            margin-bottom: 0;
        }
        .meal-time-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .meal-time-title {
            color: var(--dark-green);
            font-weight: 600;
        }
        .meal-time-time {
            color: var(--accent-orange);
            font-size: 14px;
            font-weight: 500;
        }
        .meal-item {
            background: var(--light-bg);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 5px;
            border-left: 3px solid var(--primary-green);
            cursor: pointer;
            transition: all 0.3s;
        }
        .meal-item:hover {
            transform: translateX(5px);
            background: #e8f8f1;
        }
        .meal-item:last-child {
            margin-bottom: 0;
        }
        .meal-item h5 {
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        .meal-item p {
            color: var(--text-light);
            font-size: 13px;
        }
        .meal-plan-name {
            color: var(--accent-blue);
            font-size: 11px;
            margin-top: 3px;
            font-style: italic;
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
        .search-bar {
            position: relative;
            width: 300px;
        }
        .search-bar input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            background-color: white;
            font-size: 14px;
            transition: all 0.3s;
        }
        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
        }
        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        .notification-btn, .profile-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.3s;
        }
        .notification-btn:hover, .profile-btn:hover {
            background: var(--light-green);
            color: var(--primary-green);
        }
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
        .stat-icon.plans {
            background: linear-gradient(135deg, var(--primary-green), #2980b9);
        }
        .stat-icon.pantry {
            background: linear-gradient(135deg, var(--secondary-green), #27ae60);
        }
        .stat-icon.budget {
            background: linear-gradient(135deg, var(--accent-orange), #e67e22);
        }
        .stat-icon.recipes {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        .stat-icon.generated {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        .stat-icon.week {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        .stat-icon.month {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        .stat-icon.budget-total {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }
        .stat-icon.nutrition {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        .stat-icon.goals {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        .stat-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .stat-info p {
            color: var(--text-light);
            font-size: 14px;
        }
        /* Weekly Overview */
        .weekly-overview {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        .weekly-overview h3 {
            color: var(--dark-green);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .week-days {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 15px;
        }
        .day-card {
            flex: 1;
            text-align: center;
            padding: 15px 5px;
            border-radius: 10px;
            background: var(--light-bg);
            border: 1px solid var(--border-color);
            transition: all 0.3s;
        }
        .day-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .day-card.today {
            background: linear-gradient(135deg, var(--light-green), #e8f8f1);
            border: 2px solid var(--primary-green);
        }
        .day-name {
            font-weight: bold;
            color: var(--text-dark);
            font-size: 14px;
            margin-bottom: 8px;
        }
        .day-meals {
            font-size: 12px;
            color: var(--text-light);
            margin: 5px 0;
        }
        .day-cost {
            font-size: 14px;
            font-weight: bold;
            color: var(--primary-green);
        }
        /* Recent Activity Section */
        .recent-activity {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
        }
        .recent-activity h4 {
            color: var(--dark-green);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .activity-list {
            max-height: 200px;
            overflow-y: auto;
        }
        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
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
            font-size: 12px;
            background: var(--light-bg);
            padding: 3px 8px;
            border-radius: 10px;
        }
        /* Budget Progress Bar */
        .budget-progress {
            margin: 15px 0;
        }
        .progress-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .progress-bar {
            height: 12px;
            background: var(--border-color);
            border-radius: 6px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-green), var(--secondary-green));
            border-radius: 6px;
            transition: width 0.5s ease;
        }
        .progress-fill.warning {
            background: linear-gradient(90deg, #f39c12, #e67e22);
        }
        .progress-fill.danger {
            background: linear-gradient(90deg, #e74c3c, #c0392b);
        }
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
        }
        .empty-state-icon {
            font-size: 50px;
            margin-bottom: 20px;
            color: var(--border-color);
        }
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        .meal-categories {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .section-header h2 {
            color: var(--dark-green);
            font-size: 22px;
        }
        .view-all {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        .view-all:hover {
            text-decoration: underline;
        }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .category-card {
            background: linear-gradient(135deg, #f9fdf7, #ffffff);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(46, 204, 113, 0.15);
            border-color: var(--primary-green);
        }
        .category-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .category-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 18px;
        }
        .breakfast .category-icon { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .lunch .category-icon { background: linear-gradient(135deg, #3498db, #2980b9); }
        .dinner .category-icon { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .snack .category-icon { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        
        .category-header h3 {
            color: var(--text-dark);
            font-size: 18px;
        }
        .meal-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .meal-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            border: 1px solid var(--border-color);
        }
        .meal-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .meal-info h4 {
            color: var(--text-dark);
            font-size: 15px;
            font-weight: 600;
        }
        .meal-price {
            color: var(--primary-green);
            font-weight: bold;
            font-size: 16px;
        }
        .meal-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .meal-calories {
            color: var(--accent-orange);
            font-size: 13px;
            font-weight: 500;
        }
        .add-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .add-btn:hover {
            background: var(--dark-green);
            transform: scale(1.05);
        }
        /* Recommended Section */
        .recommended-section {
            margin-top: 30px;
        }
        .recommended-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .recommended-card {
            background: linear-gradient(135deg, #f9fdf7, #ffffff);
            border-radius: 15px;
            padding: 20px;
            border: 2px solid var(--primary-green);
            text-align: center;
        }
        .food-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        .recommended-card h4 {
            color: var(--dark-green);
            margin-bottom: 10px;
        }
        .price-comparison {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        .current-price {
            color: var(--primary-green);
            font-weight: bold;
            font-size: 18px;
        }
        .original-price {
            color: var(--text-light);
            text-decoration: line-through;
            font-size: 14px;
        }
        /* Right Sidebar - Your Meal Plan */
        .meal-plan-sidebar {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }
        .plan-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .plan-header h2 {
            color: var(--dark-green);
            margin-bottom: 10px;
        }
        .plan-date {
            color: var(--accent-orange);
            font-weight: 500;
        }
        .nutrition-summary {
            background: linear-gradient(135deg, var(--light-green), #e8f8f1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }
        .nutrition-summary h4 {
            color: var(--dark-green);
            margin-bottom: 15px;
        }
        .nutrition-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .stat-item {
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
        .plan-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 25px;
        }
        .plan-btn {
            padding: 12px;
            border-radius: 10px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            font-size: 14px;
        }
        .plan-btn.primary {
            background: var(--primary-green);
            color: white;
        }
        .plan-btn.secondary {
            background: white;
            color: var(--primary-green);
            border: 2px solid var(--primary-green);
        }
        .plan-btn:hover {
            transform: translateY(-2px);
        }
        /* Budget Summary */
        .budget-summary {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        .budget-summary h4 {
            margin-bottom: 15px;
            color: var(--dark-green);
        }
        /* Nutrition Summary Styles */
        .content-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }
        
        .progress-indicator {
            height: 8px;
            background: var(--border-color);
            border-radius: 4px;
            margin: 5px 0;
            overflow: hidden;
        }
        
        .progress-indicator .fill {
            height: 100%;
            border-radius: 4px;
        }
        
        .nutrition-tips {
            background: #fff3cd;
            border-left: 4px solid #f39c12;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .nutrition-tips ul {
            margin: 10px 0 0 20px;
            color: #856404;
        }
        
        .nutrition-tips li {
            margin-bottom: 5px;
        }
        /* Responsive */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
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
            .search-bar {
                width: 100%;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .week-days {
                flex-wrap: wrap;
            }
            .day-card {
                flex: 0 0 calc(50% - 10px);
                margin-bottom: 10px;
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
                <p>Your meal plan for today is ready! 🍽️</p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="meal_plan.php"><i class="fas fa-calendar-alt"></i> My Meal Plans</a></li>
                <li><a href="pantry.php"><i class="fas fa-utensils"></i> My Pantry</a></li>
                <li><a href="recipes.php"><i class="fas fa-book"></i> Kenyan Recipes</a></li>
                <li><a href="shopping-list.php"><i class="fas fa-shopping-cart"></i> Shopping List</a></li>
                <li><a href="budget.php"><i class="fas fa-wallet"></i> Budget Tracker</a></li>
                <li><a href="nutrition-summary.php"><i class="fas fa-chart-pie"></i> Nutrition Summary</a></li>
                <li><a href="preferences.php"><i class="fas fa-sliders-h"></i> My Preferences</a></li>
                <li><a href="create-plan.php"><i class="fas fa-magic"></i> Generate Plan</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            
            <div class="todays-plan">
                <h4><i class="fas fa-calendar-day"></i> Today's Plan</h4>
                
                <?php 
                $meal_times = [
                    'Breakfast' => '06:30 AM',
                    'Lunch' => '01:00 PM', 
                    'Dinner' => '07:30 PM'
                ];
                
                $has_today_meals = false;
                foreach ($todays_meals as $type => $meals) {
                    if (!empty($meals)) {
                        $has_today_meals = true;
                        break;
                    }
                }
                
                if ($has_today_meals): 
                    foreach ($meal_times as $type => $time):
                        if (!empty($todays_meals[$type])): ?>
                            <div class="meal-time">
                                <div class="meal-time-header">
                                    <span class="meal-time-title"><?php echo $type; ?></span>
                                    <span class="meal-time-time"><?php echo $time; ?></span>
                                </div>
                                <?php foreach ($todays_meals[$type] as $meal): ?>
                                    <div class="meal-item" onclick="viewMealDetails(<?php echo $meal['meal_id']; ?>)">
                                        <h5><?php echo htmlspecialchars($meal['recipe_name']); ?></h5>
                                        <p><?php echo $meal['calories']; ?> cal • KES <?php echo $meal['estimated_cost']; ?></p>
                                        <?php if (!empty($meal['plan_name'])): ?>
                                            <div class="meal-plan-name">From: <?php echo htmlspecialchars($meal['plan_name']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="meal-time" style="opacity: 0.7;">
                                <div class="meal-time-header">
                                    <span class="meal-time-title"><?php echo $type; ?></span>
                                    <span class="meal-time-time"><?php echo $time; ?></span>
                                </div>
                                <div class="meal-item" style="border-left-color: #ccc; background: #f9f9f9;">
                                    <h5 style="color: var(--text-light);">No meal planned</h5>
                                    <a href="create-plan.php" style="color: var(--primary-green); font-size: 12px; text-decoration: none;">
                                        <i class="fas fa-plus-circle"></i> Add meal
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; 
                else: ?>
                    <div class="empty-state">
                        <i class="fas fa-utensils empty-state-icon"></i>
                        <p>No meals planned for today</p>
                        <a href="create-plan.php" class="plan-btn primary" style="margin-top: 15px; display: inline-block;">
                            <i class="fas fa-magic"></i> Generate Plan
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Weekly Budget Summary in Sidebar -->
                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="color: var(--text-dark); font-weight: 500;">Weekly Budget:</span>
                        <span style="font-weight: bold; color: var(--primary-green);">
                            KES <?php echo number_format($weekly_budget, 0); ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px;">
                        <span style="color: var(--text-light);">Used:</span>
                        <span style="color: var(--accent-orange);">KES <?php echo number_format($budget_used, 0); ?></span>
                    </div>
                    <div style="background: #f0f0f0; height: 6px; border-radius: 3px; margin: 10px 0;">
                        <?php 
                        $percentage = $weekly_budget > 0 ? min(100, ($budget_used / $weekly_budget) * 100) : 0;
                        $color = $percentage > 80 ? '#e74c3c' : ($percentage > 60 ? '#e67e22' : '#27ae60');
                        ?>
                        <div style="height: 100%; width: <?php echo $percentage; ?>%; background: <?php echo $color; ?>; border-radius: 3px;"></div>
                    </div>
                    <div style="text-align: center; font-size: 12px; color: var(--text-light);">
                        <?php echo round($percentage, 1); ?>% used
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <div class="welcome-message">
                    <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?>!</h1>
                    <p>Today is <?php echo date('l, F j, Y'); ?></p>
                </div>
                <div class="header-actions">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search recipes, ingredients...">
                    </div>
                    <button class="notification-btn" onclick="showNotifications()">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="profile-btn" onclick="window.location.href='profile.php'">
                        <i class="fas fa-user"></i>
                    </button>
                </div>
            </div>
            
            <!-- Weekly Overview -->
            <?php if (!empty($weekly_stats)): ?>
            <div class="weekly-overview">
                <h3><i class="fas fa-chart-bar"></i> This Week Overview (<?php echo date('M d', strtotime($monday)); ?> - <?php echo date('M d', strtotime($sunday)); ?>)</h3>
                <div class="week-days">
                    <?php 
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    $today_name = date('l');
                    
                    foreach ($days as $day): 
                        $day_data = null;
                        foreach ($weekly_stats as $stat) {
                            if (strpos($stat['day_name'], substr($day, 0, 3)) !== false) {
                                $day_data = $stat;
                                break;
                            }
                        }
                    ?>
                        <div class="day-card <?php echo $day === $today_name ? 'today' : ''; ?>" onclick="viewDayMeals('<?php echo $day; ?>')">
                            <div class="day-name"><?php echo substr($day, 0, 3); ?></div>
                            <div class="day-meals">
                                <?php echo $day_data ? $day_data['meal_count'] . ' meals' : '0 meals'; ?>
                            </div>
                            <div class="day-cost">
                                <?php echo $day_data && $day_data['daily_cost'] > 0 ? 'KES ' . $day_data['daily_cost'] : '-'; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Nutrition Summary Section -->
            <?php if (!empty($nutrition_goals)): ?>
            <div class="content-section" style="margin-bottom: 30px;">
                <h3 style="color: var(--dark-green); margin-bottom: 20px;">
                    <i class="fas fa-chart-pie"></i> Today's Nutrition Summary
                </h3>
                
                <div class="stats-grid">
                    <!-- Calories Card -->
                    <div class="stat-card">
                        <div class="stat-icon nutrition" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo round($nutrition['total_calories']); ?></h3>
                            <p>Calories Today</p>
                            <?php if ($nutrition_goals['daily_calories'] > 0): ?>
                                <small style="color: <?php 
                                    echo ($nutrition['total_calories'] / $nutrition_goals['daily_calories'] * 100) > 80 ? '#e74c3c' : 
                                         (($nutrition['total_calories'] / $nutrition_goals['daily_calories'] * 100) > 60 ? '#f39c12' : '#27ae60'); 
                                ?>; font-size: 12px;">
                                    <?php echo round(($nutrition['total_calories'] / $nutrition_goals['daily_calories']) * 100, 1); ?>% of daily goal
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Protein Card -->
                    <div class="stat-card">
                        <div class="stat-icon nutrition" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                            <i class="fas fa-drumstick-bite"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo round($nutrition['total_protein']); ?>g</h3>
                            <p>Protein Today</p>
                            <?php if ($nutrition_goals['daily_protein'] > 0): ?>
                                <small style="color: #3498db; font-size: 12px;">
                                    <?php echo round(($nutrition['total_protein'] / $nutrition_goals['daily_protein']) * 100, 1); ?>% of daily goal
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Carbs Card -->
                    <div class="stat-card">
                        <div class="stat-icon nutrition" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                            <i class="fas fa-bread-slice"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo round($nutrition['total_carbs']); ?>g</h3>
                            <p>Carbs Today</p>
                            <?php if ($nutrition_goals['daily_carbs'] > 0): ?>
                                <small style="color: #f39c12; font-size: 12px;">
                                    <?php echo round(($nutrition['total_carbs'] / $nutrition_goals['daily_carbs']) * 100, 1); ?>% of daily goal
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Fats Card -->
                    <div class="stat-card">
                        <div class="stat-icon nutrition" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                            <i class="fas fa-oil-can"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo round($nutrition['total_fats']); ?>g</h3>
                            <p>Fats Today</p>
                            <?php if ($nutrition_goals['daily_fats'] > 0): ?>
                                <small style="color: #9b59b6; font-size: 12px;">
                                    <?php echo round(($nutrition['total_fats'] / $nutrition_goals['daily_fats']) * 100, 1); ?>% of daily goal
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Nutrition Progress Bars -->
                <div style="background: var(--light-bg); padding: 20px; border-radius: 10px; margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <span style="color: var(--text-dark); font-weight: 500;">Nutrition Progress vs Goals</span>
                        <a href="nutrition-summary.php" style="font-size: 13px; color: var(--primary-green); text-decoration: none;">
                            <i class="fas fa-chart-line"></i> View Detailed Report
                        </a>
                    </div>
                    
                    <!-- Calories Progress -->
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px;">
                            <span>Calories</span>
                            <span>
                                <?php 
                                $calorie_percent = $nutrition_goals['daily_calories'] > 0 ? 
                                                  min(100, ($nutrition['total_calories'] / $nutrition_goals['daily_calories']) * 100) : 0;
                                echo round($nutrition['total_calories']) . ' / ' . $nutrition_goals['daily_calories'] . ' (' . round($calorie_percent, 1) . '%)';
                                ?>
                            </span>
                        </div>
                        <div class="progress-indicator">
                            <div class="fill" style="width: <?php echo $calorie_percent; ?>%; 
                                                     background: <?php 
                                                         echo $calorie_percent > 80 ? '#e74c3c' : 
                                                              ($calorie_percent > 60 ? '#f39c12' : '#27ae60'); 
                                                     ?>;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Protein Progress -->
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px;">
                            <span>Protein</span>
                            <span>
                                <?php 
                                $protein_percent = $nutrition_goals['daily_protein'] > 0 ? 
                                                  min(100, ($nutrition['total_protein'] / $nutrition_goals['daily_protein']) * 100) : 0;
                                echo round($nutrition['total_protein'], 1) . 'g / ' . $nutrition_goals['daily_protein'] . 'g (' . round($protein_percent, 1) . '%)';
                                ?>
                            </span>
                        </div>
                        <div class="progress-indicator">
                            <div class="fill" style="width: <?php echo $protein_percent; ?>%; background: #3498db;"></div>
                        </div>
                    </div>
                    
                    <!-- Carbs Progress -->
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px;">
                            <span>Carbs</span>
                            <span>
                                <?php 
                                $carbs_percent = $nutrition_goals['daily_carbs'] > 0 ? 
                                                min(100, ($nutrition['total_carbs'] / $nutrition_goals['daily_carbs']) * 100) : 0;
                                echo round($nutrition['total_carbs'], 1) . 'g / ' . $nutrition_goals['daily_carbs'] . 'g (' . round($carbs_percent, 1) . '%)';
                                ?>
                            </span>
                        </div>
                        <div class="progress-indicator">
                            <div class="fill" style="width: <?php echo $carbs_percent; ?>%; background: #f39c12;"></div>
                        </div>
                    </div>
                    
                    <!-- Fats Progress -->
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px;">
                            <span>Fats</span>
                            <span>
                                <?php 
                                $fats_percent = $nutrition_goals['daily_fats'] > 0 ? 
                                               min(100, ($nutrition['total_fats'] / $nutrition_goals['daily_fats']) * 100) : 0;
                                echo round($nutrition['total_fats'], 1) . 'g / ' . $nutrition_goals['daily_fats'] . 'g (' . round($fats_percent, 1) . '%)';
                                ?>
                            </span>
                        </div>
                        <div class="progress-indicator">
                            <div class="fill" style="width: <?php echo $fats_percent; ?>%; background: #9b59b6;"></div>
                        </div>
                    </div>
                    
                    <!-- Nutrition Tips -->
                    <?php 
                    $tips = [];
                    if ($nutrition_percentages['protein'] < 70) {
                        $tips[] = "Consider adding more protein-rich foods like beans, eggs, or chicken";
                    }
                    if ($nutrition_percentages['calories'] > 120) {
                        $tips[] = "You've exceeded your calorie goal for today";
                    } elseif ($nutrition_percentages['calories'] < 50) {
                        $tips[] = "You're below your calorie target. Add a healthy snack!";
                    }
                    if ($nutrition_percentages['carbs'] > 120) {
                        $tips[] = "Consider balancing your carbs with more vegetables and protein";
                    }
                    if (empty($tips)) {
                        $tips[] = "Great job! Your nutrition is well-balanced today";
                    }
                    
                    if (!empty($tips)): ?>
                    <div class="nutrition-tips">
                        <strong><i class="fas fa-lightbulb"></i> Nutrition Tips:</strong>
                        <ul>
                            <?php foreach ($tips as $tip): ?>
                                <li><?php echo htmlspecialchars($tip); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Nutrition Goals -->
                <div style="background: white; padding: 20px; border-radius: 10px; border: 1px solid var(--border-color); margin-top: 20px;">
                    <h4 style="color: var(--dark-green); margin-bottom: 15px;">
                        <i class="fas fa-bullseye"></i> Your Daily Nutrition Goals
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: var(--primary-green);">
                                <?php echo $nutrition_goals['daily_calories']; ?>
                            </div>
                            <div style="font-size: 13px; color: var(--text-light);">Daily Calories</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #3498db;">
                                <?php echo $nutrition_goals['daily_protein']; ?>g
                            </div>
                            <div style="font-size: 13px; color: var(--text-light);">Daily Protein</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #f39c12;">
                                <?php echo $nutrition_goals['daily_carbs']; ?>g
                            </div>
                            <div style="font-size: 13px; color: var(--text-light);">Daily Carbs</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #9b59b6;">
                                <?php echo $nutrition_goals['daily_fats']; ?>g
                            </div>
                            <div style="font-size: 13px; color: var(--text-light);">Daily Fats</div>
                        </div>
                    </div>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="nutrition-summary.php?view=goals" class="btn btn-sm btn-outline" style="display: inline-flex; align-items: center; gap: 5px;">
                            <i class="fas fa-sliders-h"></i> Adjust Goals
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Plan Generation Stats Section -->
            <div class="content-section" style="margin-bottom: 30px;">
                <h3 style="color: var(--dark-green); margin-bottom: 20px;">
                    <i class="fas fa-chart-line"></i> Your Plan Generation Activity
                </h3>
                
                <div class="stats-grid">
                    <!-- Plans Generated Card -->
                    <div class="stat-card">
                        <div class="stat-icon generated">
                            <i class="fas fa-magic"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['plans_generated']; ?></h3>
                            <p>Total Plans Generated</p>
                            <small style="color: var(--text-light); font-size: 12px;">
                                <?php echo $stats['plans_generated'] == 0 ? 'Start generating!' : 'Great work!'; ?>
                            </small>
                        </div>
                    </div>
                    
                    <!-- This Week Card -->
                    <div class="stat-card">
                        <div class="stat-icon week">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['generated_this_week']; ?></h3>
                            <p>This Week</p>
                            <small style="color: var(--text-light); font-size: 12px;">
                                <?php echo date('M d', strtotime($monday)) . ' - ' . date('M d', strtotime($sunday)); ?>
                            </small>
                        </div>
                    </div>
                    
                    <!-- This Month Card -->
                    <div class="stat-card">
                        <div class="stat-icon month">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['generated_this_month']; ?></h3>
                            <p>This Month</p>
                            <small style="color: var(--text-light); font-size: 12px;">
                                <?php echo date('F Y'); ?>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Next Goal Card -->
                    <div class="stat-card">
                        <div class="stat-icon goals">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div class="stat-info">
                            <h3>
                                <?php 
                                $next_goal = 5;
                                if ($stats['plans_generated'] >= 20) {
                                    $next_goal = 25;
                                } elseif ($stats['plans_generated'] >= 10) {
                                    $next_goal = 15;
                                } elseif ($stats['plans_generated'] >= 5) {
                                    $next_goal = 10;
                                }
                                echo $next_goal;
                                ?>
                            </h3>
                            <p>Next Goal</p>
                            <small style="color: var(--text-light); font-size: 12px;">
                                <?php 
                                $remaining = $next_goal - $stats['plans_generated'];
                                echo $remaining > 0 ? $remaining . ' more to go!' : 'Goal achieved!';
                                ?>
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Bar for Goals -->
                <?php if ($stats['plans_generated'] > 0): ?>
                <div style="background: var(--light-bg); padding: 15px; border-radius: 10px; margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: var(--text-dark); font-weight: 500;">Plan Generation Progress</span>
                        <span style="color: var(--primary-green); font-weight: 600;">
                            <?php echo $stats['plans_generated']; ?> generated
                        </span>
                    </div>
                    <div style="height: 10px; background: var(--border-color); border-radius: 5px; overflow: hidden;">
                        <?php 
                        $progress = min(100, ($stats['plans_generated'] / 20) * 100);
                        ?>
                        <div style="height: 100%; width: <?php echo $progress; ?>%; 
                                    background: linear-gradient(90deg, var(--primary-green), var(--secondary-green));">
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 12px; color: var(--text-light);">
                        <span>Beginner</span>
                        <span>Intermediate (10)</span>
                        <span>Advanced (20)</span>
                        <span>Expert (30+)</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Main Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon plans">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_plans']; ?></h3>
                        <p>Saved Meal Plans</p>
                        <small style="color: var(--text-light); font-size: 12px;">
                            <?php echo $stats['active_plans']; ?> active this week
                        </small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pantry">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pantry_items']; ?></h3>
                        <p>Pantry Items</p>
                        <small style="color: var(--text-light); font-size: 12px;">
                            Available for cooking
                        </small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon budget">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-info">
                        <h3>KES <?php echo number_format($stats['budget_used'], 0); ?></h3>
                        <p>Budget Used</p>
                        <small style="color: var(--text-light); font-size: 12px;">
                            This week (<?php echo date('M d', strtotime($monday)); ?> - <?php echo date('M d', strtotime($sunday)); ?>)
                        </small>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon budget-total">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <div class="stat-info">
                        <h3>KES <?php echo number_format($stats['weekly_budget'], 0); ?></h3>
                        <p>Weekly Budget</p>
                        <small style="color: var(--text-light); font-size: 12px;">
                            <?php 
                            $remaining = $stats['weekly_budget'] - $stats['budget_used'];
                            echo 'KES ' . number_format($remaining, 0) . ' remaining';
                            ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Section -->
            <?php if (!empty($recent_activities)): ?>
            <div class="recent-activity">
                <h4><i class="fas fa-history"></i> Recent Plan Generations</h4>
                <div class="activity-list">
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-content">
                                <i class="fas fa-check-circle activity-icon"></i>
                                <span><?php echo htmlspecialchars($activity['activity_details']); ?></span>
                            </div>
                            <span class="activity-time">
                                <?php echo $activity['display_time']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Left Column: Meal Categories -->
                <div class="meal-categories">
                    <div class="section-header">
                        <h2>Meal Categories</h2>
                        <a href="recipes.php" class="view-all">View All →</a>
                    </div>
                    
                    <div class="category-grid">
                        <?php 
                        foreach ($categories as $category => $recipes): 
                            if (empty($recipes)) continue;
                            $icon_class = strtolower($category);
                        ?>
                            <div class="category-card <?php echo $icon_class; ?>">
                                <div class="category-header">
                                    <div class="category-icon">
                                        <i class="fas fa-<?php 
                                            echo $category == 'Breakfast' ? 'sun' : 
                                                 ($category == 'Lunch' ? 'sun' : 
                                                 ($category == 'Dinner' ? 'moon' : 'apple-alt')); 
                                        ?>"></i>
                                    </div>
                                    <h3><?php echo $category; ?></h3>
                                </div>
                                <div class="meal-items">
                                    <?php foreach ($recipes as $recipe): ?>
                                        <div class="meal-card">
                                            <div class="meal-info">
                                                <h4><?php echo htmlspecialchars($recipe['recipe_name']); ?></h4>
                                                <span class="meal-price">KES <?php echo $recipe['estimated_cost']; ?></span>
                                            </div>
                                            <div class="meal-meta">
                                                <span class="meal-calories">
                                                    <?php 
                                                    echo isset($recipe['calories']) ? $recipe['calories'] . ' cal' : 'N/A';
                                                    ?>
                                                </span>
                                                <button class="add-btn" onclick="addToPlan(<?php echo $recipe['recipe_id']; ?>)">
                                                    Add to Plan
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (array_sum(array_map('count', $categories)) == 0): ?>
                            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-light);">
                                <i class="fas fa-utensils" style="font-size: 50px; margin-bottom: 20px;"></i>
                                <h3>No recipes available</h3>
                                <p>Add some recipes to get started with meal planning</p>
                                <a href="recipes.php" class="plan-btn primary" style="margin-top: 15px; display: inline-block;">
                                    <i class="fas fa-plus"></i> Add Recipes
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Recommended Section -->
                    <?php if ($recommended_result && mysqli_num_rows($recommended_result) > 0): ?>
                    <div class="recommended-section">
                        <div class="section-header">
                            <h2>Recommended For You</h2>
                            <a href="recipes.php" class="view-all">View All →</a>
                        </div>
                        <div class="recommended-grid">
                            <?php 
                            mysqli_data_seek($recommended_result, 0);
                            while ($recipe = mysqli_fetch_assoc($recommended_result)): ?>
                                <div class="recommended-card">
                                    <div class="food-icon">
                                        <i class="fas fa-<?php 
                                            echo strpos(strtolower($recipe['recipe_name']), 'choma') !== false ? 'drumstick-bite' :
                                                 (strpos(strtolower($recipe['recipe_name']), 'fish') !== false ? 'fish' : 
                                                 (strpos(strtolower($recipe['recipe_name']), 'chapati') !== false ? 'bread-slice' : 'leaf'));
                                        ?>"></i>
                                    </div>
                                    <h4><?php echo htmlspecialchars($recipe['recipe_name']); ?></h4>
                                    <div class="price-comparison">
                                        <span class="current-price">KES <?php echo $recipe['estimated_cost']; ?></span>
                                    </div>
                                    <button class="add-btn" onclick="addToPlan(<?php echo $recipe['recipe_id']; ?>)">
                                        Add to Plan
                                    </button>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Right Column: Your Meal Plan -->
                <div class="meal-plan-sidebar">
                    <div class="plan-header">
                        <h2>Today's Nutrition</h2>
                        <p class="plan-date"><?php echo date('l, F j'); ?></p>
                    </div>
                    
                    <div class="nutrition-summary">
                        <h4>Nutrition Summary</h4>
                        <div class="nutrition-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo round($nutrition['total_calories']); ?></div>
                                <div class="stat-label">Calories</div>
                                <?php if (!empty($nutrition_goals) && $nutrition_goals['daily_calories'] > 0): ?>
                                    <div class="stat-label" style="font-size: 11px; color: <?php 
                                        echo ($nutrition['total_calories'] / $nutrition_goals['daily_calories'] * 100) > 80 ? '#e74c3c' : 
                                             (($nutrition['total_calories'] / $nutrition_goals['daily_calories'] * 100) > 60 ? '#f39c12' : '#27ae60'); 
                                    ?>;">
                                        <?php echo round(($nutrition['total_calories'] / $nutrition_goals['daily_calories']) * 100, 1); ?>% of goal
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo round($nutrition['total_protein']); ?>g</div>
                                <div class="stat-label">Protein</div>
                                <?php if (!empty($nutrition_goals) && $nutrition_goals['daily_protein'] > 0): ?>
                                    <div class="stat-label" style="font-size: 11px; color: #3498db;">
                                        <?php echo round(($nutrition['total_protein'] / $nutrition_goals['daily_protein']) * 100, 1); ?>% of goal
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo round($nutrition['total_carbs']); ?>g</div>
                                <div class="stat-label">Carbs</div>
                                <?php if (!empty($nutrition_goals) && $nutrition_goals['daily_carbs'] > 0): ?>
                                    <div class="stat-label" style="font-size: 11px; color: #f39c12;">
                                        <?php echo round(($nutrition['total_carbs'] / $nutrition_goals['daily_carbs']) * 100, 1); ?>% of goal
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo round($nutrition['total_fats']); ?>g</div>
                                <div class="stat-label">Fats</div>
                                <?php if (!empty($nutrition_goals) && $nutrition_goals['daily_fats'] > 0): ?>
                                    <div class="stat-label" style="font-size: 11px; color: #9b59b6;">
                                        <?php echo round(($nutrition['total_fats'] / $nutrition_goals['daily_fats']) * 100, 1); ?>% of goal
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Add a progress bar for calories -->
                        <?php if (!empty($nutrition_goals) && $nutrition_goals['daily_calories'] > 0): 
                            $calorie_percent = min(100, ($nutrition['total_calories'] / $nutrition_goals['daily_calories']) * 100);
                        ?>
                            <div style="margin-top: 15px;">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px;">
                                    <span>Daily Calories Progress</span>
                                    <span><?php echo round($calorie_percent, 1); ?>%</span>
                                </div>
                                <div style="height: 6px; background: #ecf0f1; border-radius: 3px; overflow: hidden;">
                                    <div style="height: 100%; width: <?php echo $calorie_percent; ?>%; 
                                                background: <?php 
                                                    echo $calorie_percent > 80 ? '#e74c3c' : 
                                                         ($calorie_percent > 60 ? '#f39c12' : '#27ae60'); 
                                                ?>; border-radius: 3px;">
                                    </div>
                                </div>
                                <div style="font-size: 11px; color: var(--text-light); margin-top: 5px; text-align: center;">
                                    <?php echo round($nutrition['total_calories']); ?> / <?php echo $nutrition_goals['daily_calories']; ?> calories
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Add link to detailed nutrition page -->
                        <a href="nutrition-summary.php" style="display: block; text-align: center; margin-top: 15px; 
                                                   font-size: 13px; color: var(--primary-green); text-decoration: none;">
                            <i class="fas fa-chart-line"></i> View Detailed Nutrition Report
                        </a>
                    </div>
                    
                    <div class="plan-actions">
                        <a href="create-plan.php" class="plan-btn primary">
                            <i class="fas fa-plus-circle"></i> Create New Plan
                        </a>
                        <a href="nutrition-summary.php" class="plan-btn secondary">
                            <i class="fas fa-chart-line"></i> Nutrition Report
                        </a>
                        <a href="shopping-list.php" class="plan-btn secondary">
                            <i class="fas fa-shopping-cart"></i> Shopping List
                        </a>
                        <a href="budget.php" class="plan-btn secondary">
                            <i class="fas fa-wallet"></i> Budget Tracker
                        </a>
                    </div>
                    
                    <!-- Budget Summary -->
                    <div class="budget-summary">
                        <h4><i class="fas fa-chart-pie"></i> Weekly Budget Progress</h4>
                        <div style="margin: 15px 0;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span>Total Budget:</span>
                                <span style="font-weight: bold;">KES <?php echo number_format($weekly_budget, 0); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span>Used This Week:</span>
                                <span style="color: var(--accent-orange); font-weight: bold;">
                                    KES <?php echo number_format($budget_used, 0); ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <span>Remaining:</span>
                                <span style="color: var(--primary-green); font-weight: bold;">
                                    KES <?php echo number_format($weekly_budget - $budget_used, 0); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="budget-progress">
                            <div class="progress-labels">
                                <span>KES 0</span>
                                <span>KES <?php echo number_format($weekly_budget, 0); ?></span>
                            </div>
                            <div class="progress-bar">
                                <?php 
                                $percentage = $weekly_budget > 0 ? min(100, ($budget_used / $weekly_budget) * 100) : 0;
                                $progress_class = '';
                                if ($percentage > 80) {
                                    $progress_class = 'danger';
                                } elseif ($percentage > 60) {
                                    $progress_class = 'warning';
                                }
                                ?>
                                <div class="progress-fill <?php echo $progress_class; ?>" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                            <div style="text-align: center; margin-top: 5px; font-size: 12px; color: var(--text-light);">
                                <?php echo round($percentage, 1); ?>% used
                                <?php if ($percentage > 80): ?>
                                    <span style="color: var(--accent-red); margin-left: 10px;">
                                        <i class="fas fa-exclamation-triangle"></i> Over budget soon!
                                    </span>
                                <?php elseif ($percentage > 60): ?>
                                    <span style="color: var(--accent-orange); margin-left: 10px;">
                                        <i class="fas fa-info-circle"></i> Moderate spending
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--primary-green); margin-left: 10px;">
                                        <i class="fas fa-check-circle"></i> On track
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                        <h4>Quick Stats</h4>
                        <div style="margin-top: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Meals Today:</span>
                                <span style="font-weight: bold; color: var(--primary-green);">
                                    <?php 
                                    $total_today_meals = 0;
                                    foreach ($todays_meals as $meals) {
                                        $total_today_meals += count($meals);
                                    }
                                    echo $total_today_meals;
                                    ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Meals This Week:</span>
                                <span style="color: var(--accent-orange); font-weight: bold;">
                                    <?php 
                                    $total_week_meals = 0;
                                    foreach ($weekly_stats as $day) {
                                        $total_week_meals += $day['meal_count'];
                                    }
                                    echo $total_week_meals;
                                    ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Avg. Meal Cost:</span>
                                <span style="color: var(--accent-blue); font-weight: bold;">
                                    <?php 
                                    if ($total_week_meals > 0) {
                                        echo 'KES ' . round($budget_used / $total_week_meals, 0);
                                    } else {
                                        echo 'KES 0';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // JavaScript for interactivity
        
        function viewMealDetails(mealId) {
            alert('Viewing meal details for ID: ' + mealId + '\n\nIn a complete project, this would open a meal details page.');
        }
        
        function addToPlan(recipeId) {
            window.location.href = 'create-plan.php?recipe_id=' + recipeId;
        }
        
        function viewDayMeals(dayName) {
            alert('Viewing meals for ' + dayName + '\n\nIn a complete project, this would filter or show day-specific meals.');
        }
        
        function showNotifications() {
            alert('No new notifications.\n\nYou have ' + <?php echo $stats['plans_generated']; ?> + ' generated plans and ' + <?php echo $stats['active_plans']; ?> + ' active plans.');
        }
        
        // Search functionality placeholder
        document.querySelector('.search-bar input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    alert('Searching for: "' + searchTerm + '"\n\nIn a complete project, this would search recipes and ingredients.');
                }
            }
        });
        
        // Auto-refresh page every 2 minutes to update stats
        setTimeout(function() {
            console.log('Auto-refreshing dashboard...');
            location.reload();
        }, 120000); // 2 minutes
        
        // Add hover effect to day cards
        document.querySelectorAll('.day-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
        
        // Update budget progress bar color based on percentage
        function updateBudgetBarColor() {
            const budgetBar = document.querySelector('.progress-fill');
            if (budgetBar) {
                const width = parseInt(budgetBar.style.width);
                if (width > 80) {
                    budgetBar.style.background = 'linear-gradient(90deg, #e74c3c, #c0392b)';
                } else if (width > 60) {
                    budgetBar.style.background = 'linear-gradient(90deg, #f39c12, #e67e22)';
                }
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateBudgetBarColor();
            
            // Add today's date to welcome message
            const today = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            console.log('Dashboard loaded on: ' + today.toLocaleDateString('en-US', options));
        });
    </script>
</body>
</html>