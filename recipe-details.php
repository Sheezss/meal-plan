<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Get recipe ID from URL
$recipe_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($recipe_id == 0) {
    header("Location: recipes.php");
    exit();
}

// Get recipe details
$recipe_query = "SELECT * FROM recipes WHERE recipe_id = $recipe_id";
$recipe_result = mysqli_query($conn, $recipe_query);

if (!$recipe_result || mysqli_num_rows($recipe_result) == 0) {
    header("Location: recipes.php");
    exit();
}

$recipe = mysqli_fetch_assoc($recipe_result);

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

// Get user's upcoming meal plans for dropdown
$plans_query = "SELECT mealplan_id, name, start_date, end_date 
                FROM meal_plans 
                WHERE user_id = $user_id 
                AND end_date >= CURDATE()
                ORDER BY start_date ASC";
$plans_result = mysqli_query($conn, $plans_query);

// Handle adding recipe to plan
$message = '';
$message_type = '';

if (isset($_POST['add_to_plan'])) {
    $meal_type = mysqli_real_escape_string($conn, $_POST['meal_type']);
    $scheduled_date = mysqli_real_escape_string($conn, $_POST['scheduled_date']);
    
    // Check if user selected an existing plan or wants to create new
    if (!empty($_POST['mealplan_id'])) {
        // Add to existing plan
        $plan_id = intval($_POST['mealplan_id']);
        
        // Check if plan belongs to user
        $check_plan = "SELECT mealplan_id FROM meal_plans WHERE mealplan_id = $plan_id AND user_id = $user_id";
        $check_result = mysqli_query($conn, $check_plan);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Add meal to meals table
            $meal_sql = "INSERT INTO meals (mealplan_id, recipe_id, meal_name, meal_type, scheduled_date) 
                         VALUES ($plan_id, $recipe_id, '{$recipe['recipe_name']}', '$meal_type', '$scheduled_date')";
            
            if (mysqli_query($conn, $meal_sql)) {
                $message = "Recipe added to your meal plan successfully!";
                $message_type = 'success';
                
                // Add ingredients to shopping list if not already there
                foreach ($ingredients as $ing) {
                    // Check if item already in shopping list for this plan
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
                $message = "Error adding recipe: " . mysqli_error($conn);
                $message_type = 'error';
            }
        } else {
            $message = "Invalid meal plan selected.";
            $message_type = 'error';
        }
    } else {
        // No plan selected - redirect to create a new plan with this recipe
        header("Location: create-plan.php?add_recipe=$recipe_id&date=$scheduled_date&type=$meal_type");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($recipe['recipe_name']); ?> - Recipe Details</title>
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
        
        .btn-lg {
            padding: 12px 30px;
            font-size: 16px;
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
        
        /* Recipe Detail Styles */
        .recipe-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .recipe-title h1 {
            color: var(--dark-green);
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .recipe-category {
            background: var(--light-green);
            color: var(--primary-green);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }
        
        .recipe-image {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, var(--light-green), #e8f8f1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .recipe-image i {
            font-size: 100px;
            color: var(--primary-green);
        }
        
        .recipe-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .meta-card {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .meta-icon {
            font-size: 30px;
            color: var(--primary-green);
            margin-bottom: 10px;
        }
        
        .meta-value {
            font-size: 28px;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .meta-label {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .nutrition-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 40px;
        }
        
        .nutrition-card {
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            padding: 15px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            text-align: center;
        }
        
        .nutrition-card h4 {
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .nutrition-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-green);
            margin-bottom: 5px;
        }
        
        .nutrition-label {
            color: var(--text-light);
            font-size: 12px;
        }
        
        .ingredients-section {
            margin-bottom: 40px;
        }
        
        .ingredients-section h2 {
            color: var(--dark-green);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .ingredients-list {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 10px;
        }
        
        .ingredient-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .ingredient-row:last-child {
            border-bottom: none;
        }
        
        .ingredient-name {
            font-weight: 500;
        }
        
        .ingredient-quantity {
            color: var(--primary-green);
            font-weight: 600;
        }
        
        .instructions-section {
            margin-bottom: 40px;
        }
        
        .instructions-section h2 {
            color: var(--dark-green);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .instructions-content {
            background: var(--light-bg);
            padding: 25px;
            border-radius: 10px;
            white-space: pre-line;
            line-height: 1.8;
        }
        
        .add-to-plan-section {
            background: linear-gradient(135deg, var(--light-green), #e8f8f1);
            padding: 30px;
            border-radius: 15px;
            margin-top: 40px;
        }
        
        .add-to-plan-section h2 {
            color: var(--dark-green);
            margin-bottom: 20px;
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
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
            text-decoration: none;
            margin-bottom: 20px;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: var(--primary-green);
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
            
            .recipe-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .recipe-title h1 {
                font-size: 28px;
            }
            
            .form-row {
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
                <p>Recipe details</p>
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
                    <a href="recipes.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Recipes
                    </a>
                </div>
                <div class="header-actions">
                    <a href="create-plan.php" class="btn btn-primary">
                        <i class="fas fa-magic"></i> Generate Plan
                    </a>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Recipe Header -->
            <div class="recipe-header">
                <div class="recipe-title">
                    <h1><?php echo htmlspecialchars($recipe['recipe_name']); ?></h1>
                    <span class="recipe-category"><?php echo htmlspecialchars($recipe['meal_type']); ?></span>
                </div>
            </div>
            
            <!-- Recipe Image -->
            <div class="recipe-image">
                <i class="fas fa-utensils"></i>
            </div>
            
            <!-- Quick Stats -->
            <div class="recipe-meta-grid">
                <div class="meta-card">
                    <div class="meta-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="meta-value"><?php echo htmlspecialchars($recipe['prep_time'] ?? '30'); ?></div>
                    <div class="meta-label">Prep Time (mins)</div>
                </div>
                
                <div class="meta-card">
                    <div class="meta-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="meta-value"><?php echo htmlspecialchars($recipe['serving_size'] ?? '4'); ?></div>
                    <div class="meta-label">Servings</div>
                </div>
                
                <div class="meta-card">
                    <div class="meta-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="meta-value">KES <?php echo number_format($recipe['estimated_cost'], 0); ?></div>
                    <div class="meta-label">Estimated Cost</div>
                </div>
            </div>
            
            <!-- Nutritional Information -->
            <div class="content-section">
                <h2><i class="fas fa-chart-pie"></i> Nutritional Information</h2>
                
                <div class="nutrition-grid">
                    <div class="nutrition-card">
                        <h4>Calories</h4>
                        <div class="nutrition-value"><?php echo $recipe['calories'] ?? '300'; ?></div>
                        <div class="nutrition-label">kcal</div>
                    </div>
                    
                    <div class="nutrition-card">
                        <h4>Protein</h4>
                        <div class="nutrition-value"><?php echo $recipe['protein'] ?? '15'; ?>g</div>
                        <div class="nutrition-label">per serving</div>
                    </div>
                    
                    <div class="nutrition-card">
                        <h4>Carbs</h4>
                        <div class="nutrition-value"><?php echo $recipe['carbs'] ?? '45'; ?>g</div>
                        <div class="nutrition-label">per serving</div>
                    </div>
                    
                    <div class="nutrition-card">
                        <h4>Fats</h4>
                        <div class="nutrition-value"><?php echo $recipe['fats'] ?? '20'; ?>g</div>
                        <div class="nutrition-label">per serving</div>
                    </div>
                </div>
            </div>
            
            <!-- Ingredients -->
            <div class="content-section">
                <h2><i class="fas fa-shopping-basket"></i> Ingredients</h2>
                
                <div class="ingredients-list">
                    <?php if (!empty($ingredients)): ?>
                        <?php foreach ($ingredients as $ing): ?>
                            <div class="ingredient-row">
                                <span class="ingredient-name"><?php echo htmlspecialchars($ing['ingredient_name']); ?></span>
                                <span class="ingredient-quantity">
                                    <?php echo $ing['quantity']; ?> <?php echo $ing['unit']; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-light); text-align: center; padding: 20px;">
                            No ingredients listed for this recipe.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Instructions -->
            <div class="content-section">
                <h2><i class="fas fa-list-ol"></i> Instructions</h2>
                
                <div class="instructions-content">
                    <?php echo nl2br(htmlspecialchars($recipe['instructions'] ?? 'No instructions available.')); ?>
                </div>
            </div>
            
            <!-- Description -->
            <?php if (!empty($recipe['description'])): ?>
            <div class="content-section">
                <h2><i class="fas fa-info-circle"></i> Description</h2>
                <p style="font-size: 16px; line-height: 1.8;">
                    <?php echo htmlspecialchars($recipe['description']); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Add to Plan Section -->
            <div class="add-to-plan-section">
                <h2><i class="fas fa-plus-circle"></i> Add This Recipe to Your Meal Plan</h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="recipe_id" value="<?php echo $recipe_id; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="meal_type">Meal Type</label>
                            <select name="meal_type" id="meal_type" class="form-control" required>
                                <option value="<?php echo $recipe['meal_type']; ?>" selected>
                                    <?php echo $recipe['meal_type']; ?> (as recommended)
                                </option>
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Dinner">Dinner</option>
                                <option value="Snack">Snack</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="scheduled_date">Date</label>
                            <input type="date" name="scheduled_date" id="scheduled_date" class="form-control" 
                                   min="<?php echo date('Y-m-d'); ?>" 
                                   value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                        </div>
                    </div>
                    
                    <?php if (mysqli_num_rows($plans_result) > 0): ?>
                        <div class="form-group">
                            <label for="mealplan_id">Or add to existing meal plan:</label>
                            <select name="mealplan_id" id="mealplan_id" class="form-control">
                                <option value="">-- Create a new plan --</option>
                                <?php while ($plan = mysqli_fetch_assoc($plans_result)): ?>
                                    <option value="<?php echo $plan['mealplan_id']; ?>">
                                        <?php echo htmlspecialchars($plan['name']); ?> 
                                        (<?php echo date('M d', strtotime($plan['start_date'])); ?> - <?php echo date('M d', strtotime($plan['end_date'])); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit" name="add_to_plan" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus-circle"></i> Add to Meal Plan
                        </button>
                        <a href="recipes.php" class="btn btn-outline btn-lg" style="margin-left: 10px;">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        // Set default date to tomorrow
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('scheduled_date');
            if (dateInput) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                dateInput.value = tomorrow.toISOString().split('T')[0];
            }
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const mealType = document.getElementById('meal_type').value;
            const date = document.getElementById('scheduled_date').value;
            
            if (!mealType) {
                e.preventDefault();
                alert('Please select a meal type');
                return;
            }
            
            if (!date) {
                e.preventDefault();
                alert('Please select a date');
                return;
            }
        });
    </script>
</body>
</html>