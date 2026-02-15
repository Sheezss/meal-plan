<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Get recipe details if viewing single recipe
$view_recipe = null;
if (isset($_GET['view'])) {
    $recipe_id = intval($_GET['view']);
    $view_query = "SELECT * FROM recipes WHERE recipe_id = $recipe_id";
    $view_result = mysqli_query($conn, $view_query);
    if ($view_result && mysqli_num_rows($view_result) > 0) {
        $view_recipe = mysqli_fetch_assoc($view_result);
        
        // Get ingredients for this recipe
        $ing_query = "SELECT ri.*, fi.name as ingredient_name, fi.category, fi.unit as food_unit 
                      FROM recipe_ingredients ri 
                      JOIN food_items fi ON ri.fooditem_id = fi.fooditem_id 
                      WHERE ri.recipe_id = $recipe_id";
        $ing_result = mysqli_query($conn, $ing_query);
        $ingredients = [];
        while ($ing = mysqli_fetch_assoc($ing_result)) {
            $ingredients[] = $ing;
        }
        $view_recipe['ingredients'] = $ingredients;
    }
}

// Handle adding recipe to plan
if (isset($_POST['add_to_plan'])) {
    $recipe_id = intval($_POST['recipe_id']);
    $meal_type = mysqli_real_escape_string($conn, $_POST['meal_type']);
    $scheduled_date = mysqli_real_escape_string($conn, $_POST['scheduled_date']);
    
    // Check if user has an active meal plan for this week
    $plan_check = "SELECT mealplan_id FROM meal_plans 
                   WHERE user_id = $user_id 
                   AND start_date <= '$scheduled_date' 
                   AND end_date >= '$scheduled_date' 
                   LIMIT 1";
    $plan_result = mysqli_query($conn, $plan_check);
    
    if (mysqli_num_rows($plan_result) > 0) {
        // Add to existing plan
        $plan_row = mysqli_fetch_assoc($plan_result);
        $plan_id = $plan_row['mealplan_id'];
        
        // Get recipe details
        $recipe_query = "SELECT * FROM recipes WHERE recipe_id = $recipe_id";
        $recipe_result = mysqli_query($conn, $recipe_query);
        $recipe = mysqli_fetch_assoc($recipe_result);
        
        // Add meal to meals table
        $meal_sql = "INSERT INTO meals (mealplan_id, recipe_id, meal_name, meal_type, scheduled_date) 
                     VALUES ($plan_id, $recipe_id, '{$recipe['recipe_name']}', '$meal_type', '$scheduled_date')";
        
        if (mysqli_query($conn, $meal_sql)) {
            $success_message = "Recipe added to your meal plan successfully!";
            
            // Add ingredients to shopping list if needed
            $ing_query = "SELECT ri.*, fi.name as ingredient_name 
                          FROM recipe_ingredients ri 
                          JOIN food_items fi ON ri.fooditem_id = fi.fooditem_id 
                          WHERE ri.recipe_id = $recipe_id";
            $ing_result = mysqli_query($conn, $ing_query);
            
            while ($ing = mysqli_fetch_assoc($ing_result)) {
                // Check if item already in shopping list
                $check_sql = "SELECT * FROM shopping_lists 
                              WHERE mealplan_id = $plan_id 
                              AND fooditem_id = {$ing['fooditem_id']} 
                              AND status = 'Pending'";
                $check_result = mysqli_query($conn, $check_sql);
                
                if (mysqli_num_rows($check_result) == 0) {
                    $shop_sql = "INSERT INTO shopping_lists (mealplan_id, fooditem_id, quantity_needed, unit, status) 
                                 VALUES ($plan_id, {$ing['fooditem_id']}, {$ing['quantity']}, '{$ing['unit']}', 'Pending')";
                    mysqli_query($conn, $shop_sql);
                }
            }
            
            // Track activity
            mysqli_query($conn, "INSERT INTO user_activity (user_id, activity_type, activity_details) 
                                 VALUES ($user_id, 'recipe_added', 'Added {$recipe['recipe_name']} to meal plan')");
        } else {
            $error_message = "Error adding recipe: " . mysqli_error($conn);
        }
    } else {
        // No active plan - redirect to create one
        header("Location: create-plan.php?add_recipe=$recipe_id&date=$scheduled_date&type=$meal_type");
        exit();
    }
}

// Get all Kenyan recipes from database
$recipes_query = "SELECT * FROM recipes ORDER BY meal_type, recipe_name";
$recipes_result = mysqli_query($conn, $recipes_query);

// Get recipe categories
$categories_query = "SELECT DISTINCT meal_type FROM recipes";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
while ($cat = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $cat['meal_type'];
}

// Handle search
$search = '';
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $recipes_query = "SELECT * FROM recipes WHERE recipe_name LIKE '%$search%' OR description LIKE '%$search%' ORDER BY meal_type, recipe_name";
    $recipes_result = mysqli_query($conn, $recipes_query);
}

// Handle category filter
$category_filter = '';
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category = mysqli_real_escape_string($conn, $_GET['category']);
    $recipes_query = "SELECT * FROM recipes WHERE meal_type = '$category' ORDER BY recipe_name";
    $recipes_result = mysqli_query($conn, $recipes_query);
    $category_filter = $category;
}

// Get user's upcoming meal plans for dropdown
$plans_query = "SELECT mealplan_id, name, start_date, end_date 
                FROM meal_plans 
                WHERE user_id = $user_id 
                AND end_date >= CURDATE()
                ORDER BY start_date ASC";
$plans_result = mysqli_query($conn, $plans_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenyan Recipes - Meal Plan System</title>
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
            --modal-overlay: rgba(0,0,0,0.5);
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
        }
        
        /* Search and Filter */
        .search-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .category-filter {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .category-btn {
            padding: 8px 16px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .category-btn:hover {
            background: var(--light-green);
            border-color: var(--primary-green);
        }
        
        .category-btn.active {
            background: var(--primary-green);
            color: white;
            border-color: var(--primary-green);
        }
        
        /* Recipes Grid */
        .recipes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .recipe-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            border: 2px solid var(--border-color);
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            cursor: pointer;
        }
        
        .recipe-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: var(--primary-green);
        }
        
        .recipe-image {
            height: 180px;
            background: linear-gradient(135deg, var(--light-green), #e8f8f1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-green);
            font-size: 60px;
        }
        
        .recipe-content {
            padding: 20px;
        }
        
        .recipe-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .recipe-header h3 {
            color: var(--dark-green);
            font-size: 18px;
            flex: 1;
        }
        
        .recipe-category {
            background: var(--light-green);
            color: var(--primary-green);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .recipe-description {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .recipe-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .recipe-nutrition {
            display: flex;
            gap: 15px;
        }
        
        .nutrition-item {
            text-align: center;
        }
        
        .nutrition-value {
            color: var(--primary-green);
            font-weight: bold;
            font-size: 16px;
            display: block;
        }
        
        .nutrition-label {
            color: var(--text-light);
            font-size: 11px;
            display: block;
        }
        
        .recipe-cost {
            color: var(--dark-green);
            font-weight: bold;
            font-size: 18px;
        }
        
        .recipe-actions {
            display: flex;
            gap: 10px;
        }
        
        .recipe-actions .btn {
            flex: 1;
            justify-content: center;
        }
        
        /* Category Sections */
        .category-section {
            margin-bottom: 40px;
        }
        
        .category-section h3 {
            color: var(--dark-green);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .category-section h3 i {
            color: var(--accent-orange);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-light);
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            font-size: 60px;
            color: var(--border-color);
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
        }
        
        .empty-state p {
            margin-bottom: 30px;
        }
        
        /* Recipe Icons */
        .recipe-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin: 0 auto 15px;
        }
        
        .breakfast-icon { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .lunch-icon { background: linear-gradient(135deg, #3498db, #2980b9); }
        .dinner-icon { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        .snack-icon { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--modal-overlay);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .modal-header h2 {
            color: var(--dark-green);
            font-size: 24px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--text-light);
        }
        
        .close-modal:hover {
            color: var(--accent-red);
        }
        
        .recipe-detail-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, var(--light-green), #e8f8f1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
        }
        
        .recipe-detail-image i {
            font-size: 80px;
            color: var(--primary-green);
        }
        
        .recipe-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--light-bg);
            border-radius: 15px;
        }
        
        .meta-item {
            text-align: center;
        }
        
        .meta-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-green);
        }
        
        .meta-label {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .ingredients-list {
            margin-bottom: 30px;
        }
        
        .ingredients-list h3 {
            color: var(--dark-green);
            margin-bottom: 15px;
        }
        
        .ingredients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .ingredient-item {
            background: var(--light-bg);
            padding: 12px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ingredient-name {
            font-weight: 500;
        }
        
        .ingredient-quantity {
            color: var(--primary-green);
            font-weight: 600;
        }
        
        .instructions {
            margin-bottom: 30px;
        }
        
        .instructions h3 {
            color: var(--dark-green);
            margin-bottom: 15px;
        }
        
        .instructions-content {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 10px;
            white-space: pre-line;
        }
        
        .add-to-plan-form {
            background: var(--light-green);
            padding: 25px;
            border-radius: 15px;
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
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
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
            
            .recipes-grid {
                grid-template-columns: 1fr;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                padding: 20px;
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
                <p>Discover Kenyan recipes</p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="meal_plan.php"><i class="fas fa-calendar-alt"></i> My Meal Plans</a></li>
                <li><a href="pantry.php"><i class="fas fa-utensils"></i> My Pantry</a></li>
                <li><a href="recipes.php" class="active"><i class="fas fa-book"></i> Kenyan Recipes</a></li>
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
            <!-- Top Header -->
            <div class="top-header">
                <div class="welcome-message">
                    <h1>Kenyan Recipes</h1>
                    <p>Traditional and modern Kenyan dishes for every meal</p>
                </div>
                <div class="header-actions">
                    <a href="create-plan.php" class="btn btn-primary">
                        <i class="fas fa-magic"></i> Generate Plan
                    </a>
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Search and Filter -->
            <div class="content-section">
                <form method="GET" action="" class="search-filter">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search recipes..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search) || !empty($category_filter)): ?>
                        <a href="recipes.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </form>
                
                <!-- Category Filter -->
                <div class="category-filter">
                    <a href="recipes.php" class="category-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">
                        All Recipes
                    </a>
                    <?php foreach ($categories as $category): ?>
                        <a href="?category=<?php echo urlencode($category); ?>" 
                           class="category-btn <?php echo $category_filter == $category ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recipes Grid -->
            <div class="content-section">
                <?php if (mysqli_num_rows($recipes_result) > 0): ?>
                    <?php if (empty($category_filter) && empty($search)): ?>
                        <!-- Show recipes by category -->
                        <?php 
                        // Reset pointer and organize by category
                        mysqli_data_seek($recipes_result, 0);
                        $recipes_by_category = [];
                        while ($recipe = mysqli_fetch_assoc($recipes_result)) {
                            $recipes_by_category[$recipe['meal_type']][] = $recipe;
                        }
                        
                        foreach ($recipes_by_category as $category => $category_recipes): 
                            $icon_class = strtolower($category) . '-icon';
                            $category_icon = '';
                            switch($category) {
                                case 'Breakfast': $category_icon = 'fas fa-sun'; break;
                                case 'Lunch': $category_icon = 'fas fa-sun'; break;
                                case 'Dinner': $category_icon = 'fas fa-moon'; break;
                                case 'Snack': $category_icon = 'fas fa-apple-alt'; break;
                                default: $category_icon = 'fas fa-utensils';
                            }
                        ?>
                            <div class="category-section">
                                <h3><i class="<?php echo $category_icon; ?>"></i> <?php echo htmlspecialchars($category); ?> Recipes</h3>
                                <div class="recipes-grid">
                                    <?php foreach ($category_recipes as $recipe): ?>
                                        <div class="recipe-card" onclick="viewRecipe(<?php echo $recipe['recipe_id']; ?>)">
                                            <div class="recipe-image">
                                                <div class="recipe-icon <?php echo $icon_class; ?>">
                                                    <i class="<?php echo $category_icon; ?>"></i>
                                                </div>
                                            </div>
                                            <div class="recipe-content">
                                                <div class="recipe-header">
                                                    <h3><?php echo htmlspecialchars($recipe['recipe_name']); ?></h3>
                                                    <span class="recipe-category"><?php echo htmlspecialchars($category); ?></span>
                                                </div>
                                                
                                                <p class="recipe-description">
                                                    <?php echo htmlspecialchars($recipe['description'] ?? 'Traditional Kenyan dish'); ?>
                                                </p>
                                                
                                                <div class="recipe-details">
                                                    <div class="recipe-nutrition">
                                                        <div class="nutrition-item">
                                                            <span class="nutrition-value"><?php echo $recipe['calories'] ?? '300'; ?></span>
                                                            <span class="nutrition-label">Cal</span>
                                                        </div>
                                                        <div class="nutrition-item">
                                                            <span class="nutrition-value">KES <?php echo $recipe['estimated_cost']; ?></span>
                                                            <span class="nutrition-label">Cost</span>
                                                        </div>
                                                        <div class="nutrition-item">
                                                            <span class="nutrition-value"><?php echo $recipe['prep_time'] ?? '30'; ?></span>
                                                            <span class="nutrition-label">Min</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="recipe-actions" onclick="event.stopPropagation()">
                                                    <button class="btn btn-sm btn-outline" onclick="viewRecipe(<?php echo $recipe['recipe_id']; ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <button class="btn btn-sm btn-primary" onclick="addToPlanModal(<?php echo $recipe['recipe_id']; ?>, '<?php echo addslashes($recipe['recipe_name']); ?>', '<?php echo $category; ?>')">
                                                        <i class="fas fa-plus"></i> Add to Plan
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Show all filtered recipes in one grid -->
                        <div class="recipes-grid">
                            <?php while ($recipe = mysqli_fetch_assoc($recipes_result)): 
                                $icon_class = strtolower($recipe['meal_type']) . '-icon';
                                $category_icon = '';
                                switch($recipe['meal_type']) {
                                    case 'Breakfast': $category_icon = 'fas fa-sun'; break;
                                    case 'Lunch': $category_icon = 'fas fa-sun'; break;
                                    case 'Dinner': $category_icon = 'fas fa-moon'; break;
                                    case 'Snack': $category_icon = 'fas fa-apple-alt'; break;
                                    default: $category_icon = 'fas fa-utensils';
                                }
                            ?>
                                <div class="recipe-card" onclick="window.location.href='recipe-details.php?id=<?php echo $recipe['recipe_id']; ?>'">
                                    <div class="recipe-image">
                                        <div class="recipe-icon <?php echo $icon_class; ?>">
                                            <i class="<?php echo $category_icon; ?>"></i>
                                        </div>
                                    </div>
                                    <div class="recipe-content">
                                        <div class="recipe-header">
                                            <h3><?php echo htmlspecialchars($recipe['recipe_name']); ?></h3>
                                            <span class="recipe-category"><?php echo htmlspecialchars($recipe['meal_type']); ?></span>
                                        </div>
                                        
                                        <p class="recipe-description">
                                            <?php echo htmlspecialchars($recipe['description'] ?? 'Traditional Kenyan dish'); ?>
                                        </p>
                                        
                                        <div class="recipe-details">
                                            <div class="recipe-nutrition">
                                                <div class="nutrition-item">
                                                    <span class="nutrition-value"><?php echo $recipe['calories'] ?? '300'; ?></span>
                                                    <span class="nutrition-label">Cal</span>
                                                </div>
                                                <div class="nutrition-item">
                                                    <span class="nutrition-value">KES <?php echo $recipe['estimated_cost']; ?></span>
                                                    <span class="nutrition-label">Cost</span>
                                                </div>
                                                <div class="nutrition-item">
                                                    <span class="nutrition-value"><?php echo $recipe['prep_time'] ?? '30'; ?></span>
                                                    <span class="nutrition-label">Min</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="recipe-actions" onclick="event.stopPropagation()">
                                            <button class="btn btn-sm btn-outline" onclick="viewRecipe(<?php echo $recipe['recipe_id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-primary" onclick="addToPlanModal(<?php echo $recipe['recipe_id']; ?>, '<?php echo addslashes($recipe['recipe_name']); ?>', '<?php echo $recipe['meal_type']; ?>')">
                                                <i class="fas fa-plus"></i> Add to Plan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <h3>No recipes found</h3>
                        <p><?php echo !empty($search) ? "No recipes match your search for '" . htmlspecialchars($search) . "'" : "No recipes available"; ?></p>
                        <a href="recipes.php" class="btn btn-primary">
                            <i class="fas fa-redo"></i> View All Recipes
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Popular Kenyan Recipes Info -->
            <div class="content-section">
                <h3 style="color: var(--dark-green); margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> About Kenyan Cuisine
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <i class="fas fa-drumstick-bite" style="color: var(--primary-green); font-size: 24px; margin-bottom: 10px;"></i>
                        <h4>Staple Foods</h4>
                        <p style="color: var(--text-light); font-size: 14px;">
                            Ugali, Sukuma Wiki, Githeri, Chapati, and Nyama Choma are Kenyan staples
                        </p>
                    </div>
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <i class="fas fa-leaf" style="color: var(--secondary-green); font-size: 24px; margin-bottom: 10px;"></i>
                        <h4>Healthy Options</h4>
                        <p style="color: var(--text-light); font-size: 14px;">
                            Many Kenyan dishes are vegetable-based and packed with nutrients
                        </p>
                    </div>
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <i class="fas fa-seedling" style="color: var(--accent-orange); font-size: 24px; margin-bottom: 10px;"></i>
                        <h4>Local Ingredients</h4>
                        <p style="color: var(--text-light); font-size: 14px;">
                            Recipes use locally available, fresh ingredients from Kenyan markets
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Recipe View Modal -->
    <div id="recipeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalRecipeName"></h2>
                <button class="close-modal" onclick="closeRecipeModal()">&times;</button>
            </div>
            <div id="modalRecipeContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
    
    <!-- Add to Plan Modal -->
    <div id="addToPlanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Recipe to Meal Plan</h2>
                <button class="close-modal" onclick="closeAddToPlanModal()">&times;</button>
            </div>
            <form method="POST" action="" id="addToPlanForm">
                <input type="hidden" name="recipe_id" id="plan_recipe_id">
                
                <div class="form-group">
                    <label>Recipe Name</label>
                    <input type="text" id="plan_recipe_name" class="form-control" readonly>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="meal_type">Meal Type</label>
                        <select name="meal_type" id="plan_meal_type" class="form-control" required>
                            <option value="">Select meal type</option>
                            <option value="Breakfast">Breakfast</option>
                            <option value="Lunch">Lunch</option>
                            <option value="Dinner">Dinner</option>
                            <option value="Snack">Snack</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="scheduled_date">Date</label>
                        <input type="date" name="scheduled_date" id="plan_scheduled_date" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <?php if (mysqli_num_rows($plans_result) > 0): ?>
                    <div class="form-group">
                        <label>Or select existing meal plan:</label>
                        <select name="mealplan_id" class="form-control" onchange="useExistingPlan(this.value)">
                            <option value="">-- Create new plan --</option>
                            <?php while ($plan = mysqli_fetch_assoc($plans_result)): ?>
                                <option value="<?php echo $plan['mealplan_id']; ?>">
                                    <?php echo htmlspecialchars($plan['name']); ?> 
                                    (<?php echo date('M d', strtotime($plan['start_date'])); ?> - <?php echo date('M d', strtotime($plan['end_date'])); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" name="add_to_plan" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Add to Plan
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeAddToPlanModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Recipe viewing function
        function viewRecipe(recipeId) {
            // In a real implementation, you would fetch recipe details via AJAX
            // For now, we'll redirect to a recipe detail page
            window.location.href = 'recipe-details.php?id=' + recipeId;
            
            // Alternative: Show modal with recipe details (if you want to keep on same page)
            /*
            fetch('get-recipe.php?id=' + recipeId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalRecipeName').textContent = data.recipe_name;
                    
                    let html = `
                        <div class="recipe-detail-image">
                            <i class="fas fa-utensils"></i>
                        </div>
                        
                        <div class="recipe-meta">
                            <div class="meta-item">
                                <div class="meta-value">${data.calories || '300'}</div>
                                <div class="meta-label">Calories</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-value">KES ${data.estimated_cost}</div>
                                <div class="meta-label">Cost</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-value">${data.prep_time || '30'} min</div>
                                <div class="meta-label">Prep Time</div>
                            </div>
                        </div>
                        
                        <div class="ingredients-list">
                            <h3>Ingredients</h3>
                            <div class="ingredients-grid">
                    `;
                    
                    data.ingredients.forEach(ing => {
                        html += `
                            <div class="ingredient-item">
                                <span class="ingredient-name">${ing.ingredient_name}</span>
                                <span class="ingredient-quantity">${ing.quantity} ${ing.unit}</span>
                            </div>
                        `;
                    });
                    
                    html += `
                            </div>
                        </div>
                        
                        <div class="instructions">
                            <h3>Instructions</h3>
                            <div class="instructions-content">${data.instructions || 'No instructions available.'}</div>
                        </div>
                        
                        <div class="add-to-plan-form">
                            <h3 style="color: var(--dark-green); margin-bottom: 15px;">Add to Your Meal Plan</h3>
                            <form method="POST" action="">
                                <input type="hidden" name="recipe_id" value="${recipeId}">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Meal Type</label>
                                        <select name="meal_type" class="form-control" required>
                                            <option value="${data.meal_type}">${data.meal_type}</option>
                                            <option value="Breakfast">Breakfast</option>
                                            <option value="Lunch">Lunch</option>
                                            <option value="Dinner">Dinner</option>
                                            <option value="Snack">Snack</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Date</label>
                                        <input type="date" name="scheduled_date" class="form-control" 
                                               min="${new Date().toISOString().split('T')[0]}" required>
                                    </div>
                                </div>
                                
                                <button type="submit" name="add_to_plan" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Add to Plan
                                </button>
                            </form>
                        </div>
                    `;
                    
                    document.getElementById('modalRecipeContent').innerHTML = html;
                    document.getElementById('recipeModal').style.display = 'flex';
                });
            */
        }
        
        // Add to plan modal functions
        function addToPlanModal(recipeId, recipeName, mealType) {
            document.getElementById('plan_recipe_id').value = recipeId;
            document.getElementById('plan_recipe_name').value = recipeName;
            document.getElementById('plan_meal_type').value = mealType;
            
            // Set default date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('plan_scheduled_date').value = tomorrow.toISOString().split('T')[0];
            
            document.getElementById('addToPlanModal').style.display = 'flex';
        }
        
        function closeAddToPlanModal() {
            document.getElementById('addToPlanModal').style.display = 'none';
        }
        
        function closeRecipeModal() {
            document.getElementById('recipeModal').style.display = 'none';
        }
        
        function useExistingPlan(planId) {
            if (planId) {
                // Set the mealplan_id in the form
                // You could add a hidden input or redirect
                console.log('Selected plan:', planId);
            }
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const recipeModal = document.getElementById('recipeModal');
            const addModal = document.getElementById('addToPlanModal');
            
            if (event.target == recipeModal) {
                closeRecipeModal();
            }
            if (event.target == addModal) {
                closeAddToPlanModal();
            }
        }
        
        // Quick search
        document.querySelector('.search-box input')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
        
        // Initialize date input with tomorrow's date
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('plan_scheduled_date');
            if (dateInput) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                dateInput.value = tomorrow.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>