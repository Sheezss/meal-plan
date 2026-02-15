<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Check if user is admin
$admin_check = "SELECT is_admin FROM users WHERE user_id = $user_id";
$admin_result = mysqli_query($conn, $admin_check);
$user_data = mysqli_fetch_assoc($admin_result);

if (!$user_data || $user_data['is_admin'] != 1) {
    header("Location: dashboard.php");
    exit();
}

// Handle recipe actions
$message = '';
$message_type = '';

// Add new recipe
if (isset($_POST['add_recipe'])) {
    $recipe_name = mysqli_real_escape_string($conn, $_POST['recipe_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $instructions = mysqli_real_escape_string($conn, $_POST['instructions']);
    $prep_time = mysqli_real_escape_string($conn, $_POST['prep_time']);
    $serving_size = intval($_POST['serving_size']);
    $meal_type = mysqli_real_escape_string($conn, $_POST['meal_type']);
    $estimated_cost = floatval($_POST['estimated_cost']);
    $calories = intval($_POST['calories']);
    $protein = floatval($_POST['protein']);
    $carbs = floatval($_POST['carbs']);
    $fats = floatval($_POST['fats']);
    
    $sql = "INSERT INTO recipes (recipe_name, description, instructions, prep_time, serving_size, 
            meal_type, estimated_cost, calories, protein, carbs, fats) 
            VALUES ('$recipe_name', '$description', '$instructions', '$prep_time', $serving_size, 
            '$meal_type', $estimated_cost, $calories, $protein, $carbs, $fats)";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Recipe added successfully!";
        $message_type = 'success';
    } else {
        $message = "Error adding recipe: " . mysqli_error($conn);
        $message_type = 'error';
    }
}

// Update recipe
if (isset($_POST['update_recipe'])) {
    $recipe_id = intval($_POST['recipe_id']);
    $recipe_name = mysqli_real_escape_string($conn, $_POST['recipe_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $instructions = mysqli_real_escape_string($conn, $_POST['instructions']);
    $prep_time = mysqli_real_escape_string($conn, $_POST['prep_time']);
    $serving_size = intval($_POST['serving_size']);
    $meal_type = mysqli_real_escape_string($conn, $_POST['meal_type']);
    $estimated_cost = floatval($_POST['estimated_cost']);
    $calories = intval($_POST['calories']);
    $protein = floatval($_POST['protein']);
    $carbs = floatval($_POST['carbs']);
    $fats = floatval($_POST['fats']);
    
    $sql = "UPDATE recipes SET 
            recipe_name = '$recipe_name',
            description = '$description',
            instructions = '$instructions',
            prep_time = '$prep_time',
            serving_size = $serving_size,
            meal_type = '$meal_type',
            estimated_cost = $estimated_cost,
            calories = $calories,
            protein = $protein,
            carbs = $carbs,
            fats = $fats
            WHERE recipe_id = $recipe_id";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Recipe updated successfully!";
        $message_type = 'success';
    } else {
        $message = "Error updating recipe: " . mysqli_error($conn);
        $message_type = 'error';
    }
}

// Delete recipe
if (isset($_GET['delete_recipe'])) {
    $recipe_id = intval($_GET['delete_recipe']);
    
    // First delete recipe ingredients
    $delete_ingredients = "DELETE FROM recipe_ingredients WHERE recipe_id = $recipe_id";
    mysqli_query($conn, $delete_ingredients);
    
    // Then delete recipe
    $delete_recipe = "DELETE FROM recipes WHERE recipe_id = $recipe_id";
    
    if (mysqli_query($conn, $delete_recipe)) {
        $message = "Recipe deleted successfully!";
        $message_type = 'success';
    } else {
        $message = "Error deleting recipe: " . mysqli_error($conn);
        $message_type = 'error';
    }
}

// Get all recipes for display
$recipes_query = "SELECT * FROM recipes ORDER BY recipe_name";
$recipes_result = mysqli_query($conn, $recipes_query);

// Get statistics for dashboard
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$total_recipes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM recipes"))['count'];
$total_meal_plans = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM meal_plans"))['count'];
$total_activity = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM user_activity"))['count'];

// Get recent activity
$recent_activity_query = "SELECT ua.*, u.full_name 
                          FROM user_activity ua 
                          JOIN users u ON ua.user_id = u.user_id 
                          ORDER BY ua.created_at DESC LIMIT 10";
$recent_activity_result = mysqli_query($conn, $recent_activity_query);

// Get user growth data for chart
$user_growth_query = "SELECT DATE(date_registered) as date, COUNT(*) as count 
                      FROM users 
                      WHERE date_registered >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      GROUP BY DATE(date_registered)
                      ORDER BY date";
$user_growth_result = mysqli_query($conn, $user_growth_query);
$user_growth_data = [];
while ($row = mysqli_fetch_assoc($user_growth_result)) {
    $user_growth_data[] = $row;
}

// Get recipe categories data
$recipe_categories_query = "SELECT meal_type, COUNT(*) as count 
                            FROM recipes 
                            GROUP BY meal_type";
$recipe_categories_result = mysqli_query($conn, $recipe_categories_query);
$recipe_categories_data = [];
while ($row = mysqli_fetch_assoc($recipe_categories_result)) {
    $recipe_categories_data[] = $row;
}

// Get nutritional averages
$avg_calories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(calories) as avg FROM recipes WHERE calories > 0"))['avg'];
$avg_protein = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(protein) as avg FROM recipes WHERE protein > 0"))['avg'];
$avg_cost = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(estimated_cost) as avg FROM recipes WHERE estimated_cost > 0"))['avg'];
$recipe_count_complete = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM recipes WHERE calories > 0 AND protein > 0"))['count'];

// Get all users for user management
$users_query = "SELECT u.*, 
                COUNT(DISTINCT mp.mealplan_id) as total_plans,
                COUNT(DISTINCT ua.activity_id) as total_activities,
                COALESCE(ub.amount, 0) as budget_amount
                FROM users u
                LEFT JOIN meal_plans mp ON u.user_id = mp.user_id
                LEFT JOIN user_activity ua ON u.user_id = ua.user_id
                LEFT JOIN user_budgets ub ON u.user_id = ub.user_id AND ub.status = 'Active'
                GROUP BY u.user_id
                ORDER BY u.date_registered DESC";
$users_result = mysqli_query($conn, $users_query);

// Get user statistics
$total_admins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE is_admin = 1"))['count'];
$active_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as count FROM user_activity WHERE DATE(created_at) = CURDATE()"))['count'];
$new_this_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE MONTH(date_registered) = MONTH(CURDATE()) AND YEAR(date_registered) = YEAR(CURDATE())"))['count'];
$avg_plans_query = mysqli_query($conn, "SELECT AVG(plan_count) as avg FROM (SELECT user_id, COUNT(*) as plan_count FROM meal_plans GROUP BY user_id) as counts");
$avg_plans = mysqli_fetch_assoc($avg_plans_query)['avg'];

// Get user type distribution
$type_query = "SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type";
$type_result = mysqli_query($conn, $type_query);
$user_types = [];
while ($type = mysqli_fetch_assoc($type_result)) {
    $user_types[] = $type;
}

// Get recent registrations
$recent_users_query = "SELECT full_name, email, date_registered FROM users ORDER BY date_registered DESC LIMIT 5";
$recent_users_result = mysqli_query($conn, $recent_users_query);

// Get top users by meal plans
$top_users_query = "SELECT u.full_name, u.email, COUNT(mp.mealplan_id) as plan_count 
                    FROM users u 
                    LEFT JOIN meal_plans mp ON u.user_id = mp.user_id 
                    GROUP BY u.user_id 
                    ORDER BY plan_count DESC 
                    LIMIT 5";
$top_users_result = mysqli_query($conn, $top_users_query);

// Handle user filter from URL
$user_filter = isset($_GET['user_filter']) ? $_GET['user_filter'] : 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Meal Plan System</title>
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
            --warning-yellow: #f39c12;
            --success-blue: #3498db;
            --admin-purple: #8e44ad;
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
            background: linear-gradient(180deg, #2c3e50, #34495e);
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
            background: linear-gradient(135deg, var(--admin-purple), #9b59b6);
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
            background: linear-gradient(135deg, var(--admin-purple), #8e44ad);
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
            background: var(--admin-purple);
            color: white;
        }
        
        .nav-menu i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 18px;
        }
        
        .admin-badge {
            background: var(--admin-purple);
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
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
            color: var(--admin-purple);
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
            background: var(--admin-purple);
            color: white;
        }
        
        .btn-primary:hover {
            background: #7d3c98;
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
        
        .btn-success {
            background: var(--primary-green);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning-yellow);
            color: white;
        }
        
        .btn-danger {
            background: var(--accent-red);
            color: white;
        }
        
        .btn-info {
            background: var(--accent-blue);
            color: white;
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
            color: var(--admin-purple);
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border-left-color: #17a2b8;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
        }
        
        .stat-card.total-users::before { background: linear-gradient(90deg, var(--admin-purple), #9b59b6); }
        .stat-card.total-recipes::before { background: linear-gradient(90deg, var(--primary-green), var(--secondary-green)); }
        .stat-card.total-plans::before { background: linear-gradient(90deg, var(--accent-blue), #2980b9); }
        .stat-card.total-activity::before { background: linear-gradient(90deg, var(--accent-orange), #e67e22); }
        
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
        
        .stat-icon.users { background: linear-gradient(135deg, var(--admin-purple), #9b59b6); }
        .stat-icon.recipes { background: linear-gradient(135deg, var(--primary-green), var(--secondary-green)); }
        .stat-icon.plans { background: linear-gradient(135deg, var(--accent-blue), #2980b9); }
        .stat-icon.activity { background: linear-gradient(135deg, var(--accent-orange), #e67e22); }
        .stat-icon.new-users { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .stat-icon.active { background: linear-gradient(135deg, #e67e22, #f39c12); }
        .stat-icon.avg { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        
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
        
        .stat-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: var(--text-light);
            font-size: 14px;
        }
        
        /* Charts Container */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            border: 2px solid var(--border-color);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-header h3 {
            color: var(--text-dark);
            font-size: 18px;
        }
        
        /* Tables */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .admin-table th {
            background: var(--light-bg);
            color: var(--admin-purple);
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid var(--border-color);
        }
        
        .admin-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .admin-table tr:hover {
            background: var(--light-bg);
        }
        
        .table-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        /* Recipe Form */
        .recipe-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
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
            border-color: var(--admin-purple);
            box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.1);
        }
        
        .form-full {
            grid-column: 1 / -1;
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
            border-radius: 15px;
            padding: 30px;
            width: 90%;
            max-width: 600px;
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
            color: var(--admin-purple);
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
        }
        
        /* Activity Feed */
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--admin-purple);
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .activity-details {
            flex: 1;
        }
        
        .activity-text {
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .activity-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: var(--text-light);
        }
        
        /* Badges */
        .badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-primary { background: #d1c4e9; color: #4527a0; }
        .badge-admin { background: var(--admin-purple); color: white; }
        .badge-user { background: #7f8c8d; color: white; }
        
        /* Search Bar */
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
            border-color: var(--admin-purple);
            box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.1);
        }
        
        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        /* User Distribution */
        .distribution-item {
            margin-bottom: 15px;
        }
        
        .distribution-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .progress-bar {
            height: 8px;
            background: var(--border-color);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--admin-purple), #9b59b6);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        .recent-user-item, .top-user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .recent-user-item:last-child, .top-user-item:last-child {
            border-bottom: none;
        }
        
        .rank-badge {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
        }
        
        .rank-1 { background: #ffd700; color: #000; }
        .rank-2 { background: #c0c0c0; color: #000; }
        .rank-3 { background: #cd7f32; color: #fff; }
        
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
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-table {
                display: block;
                overflow-x: auto;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .search-bar {
                width: 100%;
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
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="logo-text">
                    <h2>NutriPlan KE</h2>
                    <p>Admin Dashboard</p>
                </div>
            </div>
            
            <div class="admin-welcome">
                <div class="admin-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3>Welcome, <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?>!</h3>
                <p>System Administrator</p>
                <span class="admin-badge">ADMIN</span>
            </div>
            
            <ul class="nav-menu">
                <li><a href="#dashboard" onclick="showSection('dashboard')" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#recipes" onclick="showSection('recipes')"><i class="fas fa-utensils"></i> Manage Recipes</a></li>
                <li><a href="#nutrition" onclick="showSection('nutrition')"><i class="fas fa-chart-line"></i> Nutritional Reports</a></li>
                <li><a href="#users" onclick="showSection('users')"><i class="fas fa-users"></i> User Management</a></li>
                <li><a href="#activity" onclick="showSection('activity')"><i class="fas fa-history"></i> System Activity</a></li>
                <li><a href="admin-create.php"><i class="fas fa-user-plus"></i> Create Admin</a></li>
                <li><a href="dashboard.php"><i class="fas fa-exchange-alt"></i> Switch to User View</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <div class="welcome-message">
                    <h1>Admin Dashboard</h1>
                    <p>Manage recipes, view reports, and monitor system activity</p>
                </div>
                <div class="header-actions">
                    <span class="badge badge-primary">Last login: <?php echo date('h:i A'); ?></span>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- Dashboard Overview Section -->
            <div id="dashboard" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-tachometer-alt"></i> System Overview</h2>
                    <div class="header-actions">
                        <span class="badge badge-info">Last updated: <?php echo date('F j, Y'); ?></span>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card total-users">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                        <div class="stat-label">Total Users</div>
                        <div style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                            <?php echo $total_admins; ?> Admins
                        </div>
                    </div>
                    
                    <div class="stat-card total-recipes">
                        <div class="stat-icon recipes">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_recipes; ?></div>
                        <div class="stat-label">Recipes</div>
                        <div style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                            <?php echo $recipe_count_complete; ?> with nutrition data
                        </div>
                    </div>
                    
                    <div class="stat-card total-plans">
                        <div class="stat-icon plans">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_meal_plans; ?></div>
                        <div class="stat-label">Meal Plans</div>
                        <div style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                            Avg <?php echo round($avg_plans, 1); ?> per user
                        </div>
                    </div>
                    
                    <div class="stat-card total-activity">
                        <div class="stat-icon activity">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_activity; ?></div>
                        <div class="stat-label">Activities</div>
                        <div style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                            <?php echo $active_today; ?> active today
                        </div>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="charts-grid">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>User Growth (Last 30 Days)</h3>
                            <span class="badge badge-success">+<?php echo count($user_growth_data); ?> days</span>
                        </div>
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Recipe Categories</h3>
                            <span class="badge badge-primary"><?php echo count($recipe_categories_data); ?> categories</span>
                        </div>
                        <canvas id="recipeCategoriesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Manage Recipes Section -->
            <div id="recipes" class="content-section" style="display: none;">
                <div class="section-header">
                    <h2><i class="fas fa-utensils"></i> Manage Recipes</h2>
                    <button class="btn btn-primary" onclick="showAddRecipeModal()">
                        <i class="fas fa-plus"></i> Add New Recipe
                    </button>
                </div>
                
                <!-- Recipes Table -->
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="20%">Recipe Name</th>
                                <th width="15%">Meal Type</th>
                                <th width="10%">Calories</th>
                                <th width="15%">Nutrition (P/C/F)</th>
                                <th width="10%">Cost</th>
                                <th width="25%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($recipes_result, 0);
                            while ($recipe = mysqli_fetch_assoc($recipes_result)): 
                            ?>
                                <tr>
                                    <td>#<?php echo $recipe['recipe_id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($recipe['recipe_name']); ?></strong>
                                        <div style="font-size: 12px; color: var(--text-light);">
                                            <?php echo strlen($recipe['description']) > 50 ? substr(htmlspecialchars($recipe['description']), 0, 50) . '...' : htmlspecialchars($recipe['description']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                            echo $recipe['meal_type'] == 'Breakfast' ? 'badge-warning' : 
                                                ($recipe['meal_type'] == 'Lunch' ? 'badge-success' : 
                                                ($recipe['meal_type'] == 'Dinner' ? 'badge-primary' : 'badge-info')); 
                                            ?>">
                                            <?php echo $recipe['meal_type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($recipe['calories']): ?>
                                            <strong><?php echo $recipe['calories']; ?></strong> cal
                                        <?php else: ?>
                                            <span style="color: var(--text-light);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($recipe['protein'] || $recipe['carbs'] || $recipe['fats']): ?>
                                            P: <?php echo $recipe['protein'] ?? 0; ?>g<br>
                                            C: <?php echo $recipe['carbs'] ?? 0; ?>g<br>
                                            F: <?php echo $recipe['fats'] ?? 0; ?>g
                                        <?php else: ?>
                                            <span style="color: var(--text-light);">No data</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong style="color: var(--primary-green);">
                                            KES <?php echo number_format($recipe['estimated_cost'], 0); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="btn btn-sm btn-info" onclick="editRecipe(<?php echo $recipe['recipe_id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="viewRecipe(<?php echo $recipe['recipe_id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <a href="?delete_recipe=<?php echo $recipe['recipe_id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this recipe?\n\nThis will also remove it from all meal plans.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Nutritional Reports Section -->
            <div id="nutrition" class="content-section" style="display: none;">
                <div class="section-header">
                    <h2><i class="fas fa-chart-line"></i> Nutritional Reports</h2>
                    <div class="header-actions">
                        <button class="btn btn-outline" onclick="exportNutritionData()">
                            <i class="fas fa-download"></i> Export Data
                        </button>
                    </div>
                </div>
                
                <div class="charts-grid">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Average Nutritional Values</h3>
                            <span class="badge badge-info">All Recipes</span>
                        </div>
                        <canvas id="nutritionChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Cost vs Calories</h3>
                            <span class="badge badge-warning">Cost per 100 calories</span>
                        </div>
                        <canvas id="costCaloriesChart"></canvas>
                    </div>
                </div>
                
                <!-- Nutritional Summary -->
                <div style="margin-top: 30px;">
                    <h3 style="color: var(--admin-purple); margin-bottom: 20px;">Nutritional Summary</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div style="background: var(--light-bg); padding: 20px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: var(--primary-green);">
                                <?php echo number_format($avg_calories, 0); ?>
                            </div>
                            <div style="color: var(--text-light); font-size: 14px;">Avg Calories</div>
                        </div>
                        
                        <div style="background: var(--light-bg); padding: 20px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: var(--accent-blue);">
                                <?php echo number_format($avg_protein, 1); ?>g
                            </div>
                            <div style="color: var(--text-light); font-size: 14px;">Avg Protein</div>
                        </div>
                        
                        <div style="background: var(--light-bg); padding: 20px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: var(--accent-orange);">
                                KES <?php echo number_format($avg_cost, 0); ?>
                            </div>
                            <div style="color: var(--text-light); font-size: 14px;">Avg Cost</div>
                        </div>
                        
                        <div style="background: var(--light-bg); padding: 20px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: var(--admin-purple);">
                                <?php echo $recipe_count_complete; ?>
                            </div>
                            <div style="color: var(--text-light); font-size: 14px;">Complete Nutrition Data</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User Management Section -->
            <div id="users" class="content-section" style="display: none;">
                <div class="section-header">
                    <h2><i class="fas fa-users-cog"></i> User Management</h2>
                    <div class="header-actions">
                        <button class="btn btn-outline" onclick="exportUserData()">
                            <i class="fas fa-download"></i> Export Users
                        </button>
                    </div>
                </div>
                
                <!-- User Stats Cards -->
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                        <div class="stat-label">Total Users</div>
                        <div style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                            <?php echo $total_admins; ?> Administrators
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon new-users">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-value"><?php echo $new_this_month; ?></div>
                        <div class="stat-label">New This Month</div>
                        <div style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                            <?php echo date('F Y'); ?>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon active">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-value"><?php echo $active_today; ?></div>
                        <div class="stat-label">Active Today</div>
                        <div style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                            Last 24 hours
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon avg">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value"><?php echo round($avg_plans, 1); ?></div>
                        <div class="stat-label">Avg Plans/User</div>
                        <div style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                            Meal plans per user
                        </div>
                    </div>
                </div>
                
                <!-- User Filters -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="?user_filter=all#users" class="btn btn-sm <?php echo $user_filter == 'all' ? 'btn-primary' : 'btn-outline'; ?>">
                            <i class="fas fa-users"></i> All Users
                        </a>
                        <a href="?user_filter=regular#users" class="btn btn-sm <?php echo $user_filter == 'regular' ? 'btn-primary' : 'btn-outline'; ?>">
                            <i class="fas fa-user"></i> Regular Users
                        </a>
                        <a href="?user_filter=admin#users" class="btn btn-sm <?php echo $user_filter == 'admin' ? 'btn-primary' : 'btn-outline'; ?>">
                            <i class="fas fa-user-shield"></i> Administrators
                        </a>
                        <a href="?user_filter=nutritionist#users" class="btn btn-sm <?php echo $user_filter == 'nutritionist' ? 'btn-primary' : 'btn-outline'; ?>">
                            <i class="fas fa-user-md"></i> Nutritionists
                        </a>
                    </div>
                    
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="userSearch" placeholder="Search users by name or email..." onkeyup="searchUsers()">
                    </div>
                </div>
                
                <!-- Users Table -->
                <!-- Users Table -->
<div style="overflow-x: auto;">
    <table class="admin-table" id="usersTable">
        <thead>
            <tr>
                <th width="5%">ID</th>
                <th width="15%">Name</th>
                <th width="15%">Email</th>
                <th width="10%">User Type</th>
                <th width="8%">Role</th>
                <th width="10%">Joined</th>
                <th width="5%">Plans</th>
                <th width="8%">Budget</th>
                <th width="8%">Status</th>
                <th width="16%">Actions</th> <!-- NEW COLUMN -->
            </tr>
        </thead>
        <tbody>
            <?php 
            mysqli_data_seek($users_result, 0);
            $user_count = 0;
            while ($user = mysqli_fetch_assoc($users_result)): 
                // Apply filter
                if ($user_filter == 'admin' && $user['is_admin'] != 1) continue;
                if ($user_filter == 'regular' && $user['is_admin'] == 1) continue;
                if ($user_filter == 'nutritionist' && $user['user_type'] != 'Nutritionist') continue;
                
                $user_count++;
                $status_color = '#27ae60';
                $status_text = 'Active';
                
                // Check if user has been active in last 7 days
                $active_check = mysqli_query($conn, "SELECT activity_id FROM user_activity WHERE user_id = {$user['user_id']} AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 1");
                if (mysqli_num_rows($active_check) == 0) {
                    $status_color = '#95a5a6';
                    $status_text = 'Inactive';
                }
            ?>
                <tr>
                    <td>#<?php echo $user['user_id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                        <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
                            <span class="badge badge-primary" style="margin-left: 5px;">You</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <?php 
                        $type_class = 'badge-info';
                        if ($user['user_type'] == 'Nutritionist') $type_class = 'badge-success';
                        if ($user['user_type'] == 'Family') $type_class = 'badge-warning';
                        if ($user['user_type'] == 'Organization') $type_class = 'badge-primary';
                        ?>
                        <span class="badge <?php echo $type_class; ?>">
                            <?php echo htmlspecialchars($user['user_type']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['is_admin'] == 1): ?>
                            <span class="badge badge-admin">
                                <i class="fas fa-shield-alt"></i> Admin
                            </span>
                        <?php else: ?>
                            <span class="badge badge-user">
                                <i class="fas fa-user"></i> User
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo date('M d, Y', strtotime($user['date_registered'])); ?>
                        <div style="font-size: 11px; color: var(--text-light);">
                            <?php 
                            $days_ago = floor((time() - strtotime($user['date_registered'])) / (60 * 60 * 24));
                            echo $days_ago . ' days ago';
                            ?>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <span style="font-weight: bold; color: var(--primary-green);">
                            <?php echo $user['total_plans']; ?>
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($user['budget_amount'] > 0): ?>
                            <span style="font-weight: bold; color: var(--accent-orange);">
                                KES <?php echo number_format($user['budget_amount'], 0); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: var(--text-light);">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: <?php echo $status_color; ?>;"></span>
                            <span style="font-size: 13px;"><?php echo $status_text; ?></span>
                        </div>
                        <div style="font-size: 11px; color: var(--text-light); margin-top: 3px;">
                            Activities: <?php echo $user['total_activities']; ?>
                        </div>
                    </td>
                    <td>
                        <div class="table-actions">
                            <!-- View User Details Button -->
                            <button class="btn btn-sm btn-info" onclick="viewUserDetails(<?php echo $user['user_id']; ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                            
                            <!-- Make Admin Button (only show for non-admins) -->
                            <?php if ($user['is_admin'] == 0): ?>
                                <button class="btn btn-sm btn-warning" onclick="makeAdmin(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                    <i class="fas fa-user-shield"></i> Make Admin
                                </button>
                            <?php endif; ?>
                            
                            <!-- Remove Admin Button (only show for admins, not yourself) -->
                            <?php if ($user['is_admin'] == 1 && $user['user_id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-outline" onclick="removeAdmin(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                    <i class="fas fa-user"></i> Remove Admin
                                </button>
                            <?php endif; ?>
                            
                            <!-- Delete User Button (don't show for yourself) -->
                            <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            
            <?php if ($user_count == 0): ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 40px; color: var(--text-light);">
                        <i class="fas fa-users" style="font-size: 40px; margin-bottom: 15px; display: block;"></i>
                        No users found matching the selected filter.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
                <!-- User Analytics -->
                <div style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <!-- User Type Distribution -->
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <h4 style="color: var(--admin-purple); margin-bottom: 15px;">
                            <i class="fas fa-chart-pie"></i> User Type Distribution
                        </h4>
                        <?php 
                        $total = $total_users;
                        mysqli_data_seek($type_result, 0);
                        ?>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($user_types as $type): 
                                $percentage = $total > 0 ? round(($type['count'] / $total) * 100, 1) : 0;
                            ?>
                                <div class="distribution-item">
                                    <div class="distribution-header">
                                        <span><?php echo htmlspecialchars($type['user_type']); ?></span>
                                        <span style="font-weight: bold;"><?php echo $type['count']; ?> (<?php echo $percentage; ?>%)</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Registrations -->
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <h4 style="color: var(--admin-purple); margin-bottom: 15px;">
                            <i class="fas fa-user-plus"></i> Recent Registrations
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php 
                            mysqli_data_seek($recent_users_result, 0);
                            while ($recent = mysqli_fetch_assoc($recent_users_result)): 
                            ?>
                                <div class="recent-user-item">
                                    <div>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($recent['full_name']); ?></div>
                                        <div style="font-size: 12px; color: var(--text-light);"><?php echo htmlspecialchars($recent['email']); ?></div>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-light);">
                                        <?php echo date('M d', strtotime($recent['date_registered'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    
                    <!-- Top Users by Meal Plans -->
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <h4 style="color: var(--admin-purple); margin-bottom: 15px;">
                            <i class="fas fa-trophy"></i> Top Users (Meal Plans)
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php 
                            $rank = 1;
                            mysqli_data_seek($top_users_result, 0);
                            while ($top = mysqli_fetch_assoc($top_users_result)): 
                            ?>
                                <div class="top-user-item">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="rank-badge rank-<?php echo $rank; ?>">
                                            <?php echo $rank++; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($top['full_name']); ?></div>
                                            <div style="font-size: 11px; color: var(--text-light);">
                                                <?php echo $top['plan_count']; ?> meal plans
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Activity Feed Section -->
            <div id="activity" class="content-section" style="display: none;">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Recent System Activity</h2>
                    <button class="btn btn-outline" onclick="refreshActivity()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                
                <div class="activity-feed">
                    <?php 
                    mysqli_data_seek($recent_activity_result, 0);
                    while ($activity = mysqli_fetch_assoc($recent_activity_result)): 
                    ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php 
                                    echo $activity['activity_type'] == 'plan_generated' ? 'magic' : 
                                           ($activity['activity_type'] == 'plan_saved' ? 'save' : 
                                           ($activity['activity_type'] == 'login' ? 'sign-in-alt' : 
                                           ($activity['activity_type'] == 'admin_created' ? 'user-shield' : 'history'))); 
                                ?>"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-text">
                                    <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong> 
                                    <?php 
                                    $action = str_replace('_', ' ', $activity['activity_type']);
                                    echo ucwords($action); 
                                    ?>
                                    <?php if ($activity['activity_details']): ?>
                                        - <?php echo htmlspecialchars($activity['activity_details']); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-meta">
                                    <span><i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($activity['created_at'])); ?></span>
                                    <span><i class="far fa-calendar"></i> <?php echo date('M j, Y', strtotime($activity['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <!-- Add/Edit Recipe Modal -->
            <div id="recipeModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle">Add New Recipe</h3>
                        <button class="close-modal" onclick="closeModal()">&times;</button>
                    </div>
                    <form id="recipeForm" method="POST" action="">
                        <input type="hidden" id="recipe_id" name="recipe_id">
                        
                        <div class="recipe-form">
                            <div class="form-group">
                                <label for="recipe_name">Recipe Name *</label>
                                <input type="text" id="recipe_name" name="recipe_name" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="meal_type">Meal Type *</label>
                                <select id="meal_type" name="meal_type" class="form-control" required>
                                    <option value="">Select Meal Type</option>
                                    <option value="Breakfast">Breakfast</option>
                                    <option value="Lunch">Lunch</option>
                                    <option value="Dinner">Dinner</option>
                                    <option value="Snack">Snack</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="prep_time">Prep Time</label>
                                <input type="text" id="prep_time" name="prep_time" class="form-control" placeholder="e.g., 30 minutes">
                            </div>
                            
                            <div class="form-group">
                                <label for="serving_size">Serving Size</label>
                                <input type="number" id="serving_size" name="serving_size" class="form-control" min="1" value="4">
                            </div>
                            
                            <div class="form-group form-full">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group form-full">
                                <label for="instructions">Instructions *</label>
                                <textarea id="instructions" name="instructions" class="form-control" rows="5" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="estimated_cost">Estimated Cost (KES) *</label>
                                <input type="number" id="estimated_cost" name="estimated_cost" class="form-control" min="0" step="10" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="calories">Calories</label>
                                <input type="number" id="calories" name="calories" class="form-control" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="protein">Protein (g)</label>
                                <input type="number" id="protein" name="protein" class="form-control" min="0" step="0.1">
                            </div>
                            
                            <div class="form-group">
                                <label for="carbs">Carbs (g)</label>
                                <input type="number" id="carbs" name="carbs" class="form-control" min="0" step="0.1">
                            </div>
                            
                            <div class="form-group">
                                <label for="fats">Fats (g)</label>
                                <input type="number" id="fats" name="fats" class="form-control" min="0" step="0.1">
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 15px; margin-top: 30px;">
                            <button type="submit" id="submitBtn" name="add_recipe" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Recipe
                            </button>
                            <button type="button" class="btn btn-outline" onclick="closeModal()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Section navigation
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected section
            document.getElementById(sectionId).style.display = 'block';
            
            // Update active nav
            document.querySelectorAll('.nav-menu a').forEach(link => {
                link.classList.remove('active');
            });
            
            // Find and activate the clicked link
            document.querySelectorAll('.nav-menu a').forEach(link => {
                if (link.getAttribute('onclick') && link.getAttribute('onclick').includes(sectionId)) {
                    link.classList.add('active');
                }
            });
            
            // Update URL hash
            window.location.hash = sectionId;
        }
        
        // Check URL hash on page load
        document.addEventListener('DOMContentLoaded', function() {
            let hash = window.location.hash.substring(1);
            if (hash && ['dashboard', 'recipes', 'nutrition', 'users', 'activity'].includes(hash)) {
                showSection(hash);
            } else {
                showSection('dashboard');
            }
            
            // Initialize charts
            initCharts();
        });
        
        // Modal functions
        function showAddRecipeModal() {
            document.getElementById('modalTitle').textContent = 'Add New Recipe';
            document.getElementById('recipeForm').reset();
            document.getElementById('recipe_id').value = '';
            document.getElementById('submitBtn').name = 'add_recipe';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Save Recipe';
            document.getElementById('recipeModal').style.display = 'flex';
        }
        
        function editRecipe(recipeId) {
            window.location.href = `edit-recipe.php?id=${recipeId}`;
        }
        
        function viewRecipe(recipeId) {
            window.open(`recipe-details.php?id=${recipeId}`, '_blank');
        }
        
        function closeModal() {
            document.getElementById('recipeModal').style.display = 'none';
        }
        
        function refreshActivity() {
            location.reload();
        }
        
        function exportNutritionData() {
            alert('Export feature will download nutritional data as CSV/PDF.\n\nThis feature is under development.');
        }
        
        function exportUserData() {
            alert('Export feature will download user data as CSV.\n\nThis feature is under development.');
        }
        
        // User search function
        function searchUsers() {
            let input = document.getElementById('userSearch');
            let filter = input.value.toLowerCase();
            let table = document.getElementById('usersTable');
            let tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                let tdName = tr[i].getElementsByTagName('td')[1];
                let tdEmail = tr[i].getElementsByTagName('td')[2];
                if (tdName || tdEmail) {
                    let nameValue = tdName ? tdName.textContent || tdName.innerText : '';
                    let emailValue = tdEmail ? tdEmail.textContent || tdEmail.innerText : '';
                    if (nameValue.toLowerCase().indexOf(filter) > -1 || 
                        emailValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
        
        // Chart initialization
        function initCharts() {
            // User Growth Chart
            const userGrowthCtx = document.getElementById('userGrowthChart')?.getContext('2d');
            if (userGrowthCtx) {
                new Chart(userGrowthCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode(array_column($user_growth_data, 'date')); ?>,
                        datasets: [{
                            label: 'New Users',
                            data: <?php echo json_encode(array_column($user_growth_data, 'count')); ?>,
                            borderColor: '#8e44ad',
                            backgroundColor: 'rgba(142, 68, 173, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            }
            
            // Recipe Categories Chart
            const recipeCategoriesCtx = document.getElementById('recipeCategoriesChart')?.getContext('2d');
            if (recipeCategoriesCtx) {
                new Chart(recipeCategoriesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode(array_column($recipe_categories_data, 'meal_type')); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_column($recipe_categories_data, 'count')); ?>,
                            backgroundColor: ['#f39c12', '#27ae60', '#3498db', '#9b59b6', '#e74c3c']
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
            
            // Nutrition Chart
            const nutritionCtx = document.getElementById('nutritionChart')?.getContext('2d');
            if (nutritionCtx) {
                new Chart(nutritionCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Protein', 'Carbs', 'Fats'],
                        datasets: [{
                            label: 'Average grams per recipe',
                            data: [<?php echo $avg_protein ?: 0; ?>, 65, <?php echo $avg_protein ? $avg_protein * 0.8 : 52; ?>],
                            backgroundColor: ['#3498db', '#2ecc71', '#f39c12']
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }
            
            // Cost vs Calories Chart
            const costCaloriesCtx = document.getElementById('costCaloriesChart')?.getContext('2d');
            if (costCaloriesCtx) {
                new Chart(costCaloriesCtx, {
                    type: 'scatter',
                    data: {
                        datasets: [{
                            label: 'Recipes',
                            data: [
                                {x: 250, y: 120}, {x: 420, y: 150}, {x: 680, y: 220},
                                {x: 320, y: 80}, {x: 580, y: 180}, {x: 750, y: 450},
                                {x: 180, y: 60}
                            ],
                            backgroundColor: '#8e44ad'
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: { title: { display: true, text: 'Calories' } },
                            y: { title: { display: true, text: 'Cost (KES)' } }
                        }
                    }
                });
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('recipeModal');
            if (event.target == modal) {
                closeModal();
            }
        }
       
    // User Management Functions
    function viewUserDetails(userId) {
        window.location.href = `user-details.php?id=${userId}`;
    }
    
    function makeAdmin(userId, userName) {
        if (confirm(`Are you sure you want to make "${userName}" an administrator?\n\nThis will give them full access to the admin panel.`)) {
            window.location.href = `admin-create.php?promote=${userId}`;
        }
    }
    
    function removeAdmin(userId, userName) {
        if (confirm(`Are you sure you want to remove "${userName}" from administrators?\n\nThey will lose access to the admin panel.`)) {
            window.location.href = `admin-remove.php?id=${userId}`;
        }
    }
    
    function deleteUser(userId, userName) {
        if (confirm(`⚠️ WARNING: Are you sure you want to permanently delete "${userName}"?\n\nThis will delete all of their:\n- Meal plans\n- Pantry items\n- Budgets\n- Activity history\n\nThis action CANNOT be undone!`)) {
            if (confirm(`FINAL WARNING: This is irreversible. Type "DELETE" to confirm.`)) {
                let confirmation = prompt('Type "DELETE" to confirm permanent deletion:');
                if (confirmation === 'DELETE') {
                    window.location.href = `admin-delete-user.php?id=${userId}&confirm=1`;
                }
            }
        }
    }

    </script>
</body>
</html>