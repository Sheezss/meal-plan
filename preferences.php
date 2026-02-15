<?php
// preferences.php - Dietary Preferences Setup
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Get pantry count for workflow
$pantry_count_query = "SELECT COUNT(*) as count FROM pantry WHERE user_id = $user_id";
$pantry_count_result = mysqli_query($conn, $pantry_count_query);
$pantry_count = 0;
if ($pantry_count_result) {
    $pantry_data = mysqli_fetch_assoc($pantry_count_result);
    $pantry_count = $pantry_data['count'] ?? 0;
}

// Get existing preferences if they exist
$preferences = [];
$pref_query = "SELECT * FROM user_preferences WHERE user_id = $user_id";
$pref_result = mysqli_query($conn, $pref_query);
if (mysqli_num_rows($pref_result) > 0) {
    $preferences = mysqli_fetch_assoc($pref_result);
}

// Handle form submission
$message = '';
$message_type = '';
$saved_successfully = false;

if (isset($_POST['save_preferences'])) {
    
    $diet_type = mysqli_real_escape_string($conn, $_POST['diet_type'] ?? 'Balanced');
    $cuisine_pref = mysqli_real_escape_string($conn, $_POST['cuisine_pref'] ?? 'Kenyan');
    $spicy_level = mysqli_real_escape_string($conn, $_POST['spicy_level'] ?? 'Medium');
    $cooking_time = mysqli_real_escape_string($conn, $_POST['cooking_time'] ?? '30-45 minutes');
    $meals_per_day = intval($_POST['meals_per_day'] ?? 3);
    
    // Dietary restrictions
    $avoid_pork = isset($_POST['avoid_pork']) ? 1 : 0;
    $avoid_beef = isset($_POST['avoid_beef']) ? 1 : 0;
    $avoid_fish = isset($_POST['avoid_fish']) ? 1 : 0;
    $avoid_dairy = isset($_POST['avoid_dairy']) ? 1 : 0;
    $avoid_gluten = isset($_POST['avoid_gluten']) ? 1 : 0;
    $avoid_nuts = isset($_POST['avoid_nuts']) ? 1 : 0;
    $avoid_eggs = isset($_POST['avoid_eggs']) ? 1 : 0;
    
    // Special diets
    $vegetarian = isset($_POST['vegetarian']) ? 1 : 0;
    $vegan = isset($_POST['vegan']) ? 1 : 0;
    $low_carb = isset($_POST['low_carb']) ? 1 : 0;
    $low_fat = isset($_POST['low_fat']) ? 1 : 0;
    $low_sodium = isset($_POST['low_sodium']) ? 1 : 0;
    $sugar_free = isset($_POST['sugar_free']) ? 1 : 0;
    $high_protein = isset($_POST['high_protein']) ? 1 : 0;
    
    // Medical conditions
    $diabetic = isset($_POST['diabetic']) ? 1 : 0;
    $hypertension = isset($_POST['hypertension']) ? 1 : 0;
    $cholesterol = isset($_POST['cholesterol']) ? 1 : 0;
    $pregnancy = isset($_POST['pregnancy']) ? 1 : 0;
    $lactose = isset($_POST['lactose']) ? 1 : 0;
    
    // Meal preferences
    $pref_breakfast = isset($_POST['pref_breakfast']) ? 1 : 0;
    $pref_lunch = isset($_POST['pref_lunch']) ? 1 : 0;
    $pref_dinner = isset($_POST['pref_dinner']) ? 1 : 0;
    $pref_snacks = isset($_POST['pref_snacks']) ? 1 : 0;
    
    // Check if preferences already exist
    if (empty($preferences)) {
        // Insert new preferences
        $sql = "INSERT INTO user_preferences (
            user_id, diet_type, cuisine_pref, spicy_level, cooking_time, meals_per_day,
            avoid_pork, avoid_beef, avoid_fish, avoid_dairy, avoid_gluten, avoid_nuts, avoid_eggs,
            vegetarian, vegan, low_carb, low_fat, low_sodium, sugar_free, high_protein,
            diabetic, hypertension, cholesterol, pregnancy, lactose,
            pref_breakfast, pref_lunch, pref_dinner, pref_snacks
        ) VALUES (
            $user_id, '$diet_type', '$cuisine_pref', '$spicy_level', '$cooking_time', $meals_per_day,
            $avoid_pork, $avoid_beef, $avoid_fish, $avoid_dairy, $avoid_gluten, $avoid_nuts, $avoid_eggs,
            $vegetarian, $vegan, $low_carb, $low_fat, $low_sodium, $sugar_free, $high_protein,
            $diabetic, $hypertension, $cholesterol, $pregnancy, $lactose,
            $pref_breakfast, $pref_lunch, $pref_dinner, $pref_snacks
        )";
    } else {
        // Update existing preferences
        $sql = "UPDATE user_preferences SET 
            diet_type = '$diet_type',
            cuisine_pref = '$cuisine_pref',
            spicy_level = '$spicy_level',
            cooking_time = '$cooking_time',
            meals_per_day = $meals_per_day,
            avoid_pork = $avoid_pork,
            avoid_beef = $avoid_beef,
            avoid_fish = $avoid_fish,
            avoid_dairy = $avoid_dairy,
            avoid_gluten = $avoid_gluten,
            avoid_nuts = $avoid_nuts,
            avoid_eggs = $avoid_eggs,
            vegetarian = $vegetarian,
            vegan = $vegan,
            low_carb = $low_carb,
            low_fat = $low_fat,
            low_sodium = $low_sodium,
            sugar_free = $sugar_free,
            high_protein = $high_protein,
            diabetic = $diabetic,
            hypertension = $hypertension,
            cholesterol = $cholesterol,
            pregnancy = $pregnancy,
            lactose = $lactose,
            pref_breakfast = $pref_breakfast,
            pref_lunch = $pref_lunch,
            pref_dinner = $pref_dinner,
            pref_snacks = $pref_snacks,
            updated_at = CURRENT_TIMESTAMP
            WHERE user_id = $user_id";
    }
    
    if (mysqli_query($conn, $sql)) {
        $message = "Preferences saved successfully! Your meal plans will now be personalized based on these preferences.";
        $message_type = 'success';
        $saved_successfully = true;
        
        // Refresh preferences
        $pref_result = mysqli_query($conn, $pref_query);
        $preferences = mysqli_fetch_assoc($pref_result);
        
        // Add activity log
        $activity_details = "Updated dietary preferences: $diet_type diet, $meals_per_day meals/day";
        $activity_query = "INSERT INTO user_activity (user_id, activity_type, activity_details) 
                           VALUES ($user_id, 'preferences_updated', '$activity_details')";
        mysqli_query($conn, $activity_query);
        
    } else {
        $message = "Error saving preferences: " . mysqli_error($conn);
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Preferences - Meal Plan System</title>
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
        
        /* Sidebar - Same as pantry.php */
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
        
        .btn-lg {
            padding: 15px 40px;
            font-size: 16px;
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
        
        /* Checkbox and Radio Styles */
        .preferences-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .pref-option {
            background: var(--light-bg);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .pref-option:hover {
            border-color: var(--primary-green);
            background: var(--light-green);
        }
        
        .pref-option.selected {
            border-color: var(--primary-green);
            background: var(--light-green);
            box-shadow: 0 4px 8px rgba(39, 174, 96, 0.2);
        }
        
        .pref-option input[type="radio"],
        .pref-option input[type="checkbox"] {
            display: none;
        }
        
        .pref-option label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-weight: 500;
        }
        
        .pref-option i {
            margin-right: 10px;
            color: var(--primary-green);
            font-size: 18px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            accent-color: var(--primary-green);
        }
        
        /* Diet Card - Updated for food examples */
        .diet-card {
            background: var(--light-bg);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            position: relative;
        }
        
        .diet-card:hover {
            border-color: var(--primary-green);
            transform: translateY(-2px);
        }
        
        .diet-card h4 {
            color: var(--dark-green);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .food-examples {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed var(--border-color);
        }
        
        .food-examples h5 {
            color: var(--text-dark);
            font-size: 13px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .food-examples ul {
            list-style: none;
            padding-left: 0;
            margin-top: 5px;
        }
        
        .food-examples li {
            font-size: 12px;
            color: var(--text-light);
            padding: 3px 0;
            display: flex;
            align-items: center;
        }
        
        .food-examples li:before {
            content: "•";
            color: var(--primary-green);
            font-weight: bold;
            margin-right: 8px;
        }
        
        .food-categories {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        
        .food-category {
            background: white;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
        }
        
        .food-category h6 {
            color: var(--dark-green);
            font-size: 11px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .food-list {
            font-size: 11px;
            color: var(--text-light);
            line-height: 1.4;
        }
        
        /* Messages */
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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
        
        .message-icon {
            font-size: 24px;
        }
        
        .message-content {
            flex: 1;
        }
        
        .message-content strong {
            display: block;
            margin-bottom: 5px;
        }
        
        /* Save Confirmation Animation */
        .save-confirmation {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary-green);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideInRight 0.5s ease-out, fadeOut 0.5s ease-in 2.5s forwards;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateX(100%);
            }
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
            
            .preferences-grid {
                grid-template-columns: 1fr;
            }
            
            .food-categories {
                grid-template-columns: 1fr;
            }
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
        
        /* Progress Bar */
        .progress-container {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .progress-bar {
            height: 10px;
            background: var(--border-color);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-green), var(--secondary-green));
            border-radius: 5px;
            transition: width 0.5s ease-in-out;
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
                <p>Set your meal preferences</p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="meal_plan.php"><i class="fas fa-calendar-alt"></i> My Meal Plans</a></li>
                <li><a href="pantry.php"><i class="fas fa-utensils"></i> My Pantry</a></li>
                <li><a href="recipes.php"><i class="fas fa-book"></i> Kenyan Recipes</a></li>
                <li><a href="shopping-list.php"><i class="fas fa-shopping-cart"></i> Shopping List</a></li>
                <li><a href="budget.php"><i class="fas fa-wallet"></i> Budget Tracker</a></li>
                <li><a href="preferences.php" class="active"><i class="fas fa-sliders-h"></i> My Preferences</a></li>
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
                    <h1>Set Your Preferences</h1>
                    <p>Customize your meal planning experience</p>
                </div>
                <div class="header-actions">
                    <a href="budget.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Continue to Budget
                    </a>
                </div>
            </div>
            
            <!-- Save Confirmation -->
            <?php if ($saved_successfully): ?>
                <div class="save-confirmation">
                    <i class="fas fa-check-circle" style="font-size: 24px;"></i>
                    <div>
                        <strong>Preferences Saved!</strong>
                        <p style="font-size: 12px; margin-top: 5px;">Your settings have been updated</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <div class="message-icon">
                        <?php if ($message_type == 'success'): ?>
                            <i class="fas fa-check-circle"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-triangle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="message-content">
                        <strong><?php echo $message_type == 'success' ? 'Success!' : 'Error!'; ?></strong>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress-header">
                    <span><strong>Setup Progress</strong></span>
                    <span>Step 2 of 4</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 50%"></div>
                </div>
                <p style="color: var(--text-light); font-size: 14px; margin-top: 10px;">
                    Pantry Setup ✓ → Preferences → Budget → Generate Plan
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
                            <a href="pantry.php" class="btn btn-outline" style="margin-top: 10px;">
                                View Pantry
                            </a>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-icon" style="background: var(--accent-orange);">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                        <div class="step-content">
                            <h4>Step 2: Set Preferences</h4>
                            <p>Choose dietary preferences & meal types</p>
                            <div class="step-status">
                                <span class="badge badge-warning" style="background: var(--accent-orange); color: white;">
                                    Current Step
                                </span>
                            </div>
                            <?php if (!empty($preferences)): ?>
                                <p style="font-size: 12px; color: var(--primary-green); margin-top: 5px;">
                                    <i class="fas fa-check"></i> Preferences saved
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-icon" style="background: var(--text-light);">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="step-content">
                            <h4>Step 3: Set Budget</h4>
                            <p>Define your weekly food budget</p>
                            <div class="step-status">
                                <span class="badge badge-warning">Pending</span>
                            </div>
                            <a href="budget.php" class="btn btn-outline" style="margin-top: 10px;">
                                Next: Set Budget
                            </a>
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
                            <a href="create-plan.php" class="btn btn-outline" style="margin-top: 10px;">
                                Next: Generate Plan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Preferences Form -->
            <form method="POST" action="">
                <div class="content-section">
                    <div class="section-header">
                        <h2>Dietary Preferences</h2>
                        <p style="color: var(--text-light); font-size: 14px;">Choose your preferred eating style</p>
                    </div>
                    
                    <!-- Diet Type - Kenyan Relevant Options -->
                    <div class="form-group">
                        <label for="diet_type">Preferred Diet Type</label>
                        <div class="preferences-grid" id="dietTypeOptions">
                            <!-- Balanced Diet -->
                            <div class="pref-option <?php echo ($preferences['diet_type'] ?? 'Balanced') == 'Balanced' ? 'selected' : ''; ?>" 
                                 onclick="selectDietType('balanced')">
                                <input type="radio" id="diet_balanced" name="diet_type" value="Balanced" 
                                       <?php echo ($preferences['diet_type'] ?? 'Balanced') == 'Balanced' ? 'checked' : ''; ?>>
                                <label for="diet_balanced">
                                    <i class="fas fa-balance-scale"></i>
                                    <div>
                                        <strong>Balanced Diet</strong>
                                        <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                                            Mix of carbs, protein & veggies
                                        </p>
                                    </div>
                                </label>
                                <div class="food-examples">
                                    <h5><i class="fas fa-utensils"></i> Example Foods:</h5>
                                    <div class="food-categories">
                                        <div class="food-category">
                                            <h6>Proteins</h6>
                                            <div class="food-list">Beef, Chicken, Fish, Beans, Eggs</div>
                                        </div>
                                        <div class="food-category">
                                            <h6>Carbs</h6>
                                            <div class="food-list">Ugali, Rice, Chapati, Potatoes</div>
                                        </div>
                                        <div class="food-category">
                                            <h6>Vegetables</h6>
                                            <div class="food-list">Sukuma Wiki, Spinach, Cabbage, Carrots</div>
                                        </div>
                                        <div class="food-category">
                                            <h6>Fruits</h6>
                                            <div class="food-list">Mangoes, Bananas, Oranges, Avocado</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Keto/Low-Carb -->
                            <div class="pref-option <?php echo ($preferences['diet_type'] ?? '') == 'Keto' ? 'selected' : ''; ?>" 
                                 onclick="selectDietType('keto')">
                                <input type="radio" id="diet_keto" name="diet_type" value="Keto" 
                                       <?php echo ($preferences['diet_type'] ?? '') == 'Keto' ? 'checked' : ''; ?>>
                                <label for="diet_keto">
                                    <i class="fas fa-bacon"></i>
                                    <div>
                                        <strong>Keto / Low-Carb</strong>
                                        <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                                            High fat, low carbohydrates
                                        </p>
                                    </div>
                                </label>
                                <div class="food-examples">
                                    <h5><i class="fas fa-utensils"></i> Example Foods:</h5>
                                    <ul>
                                        <li>Meats: Beef, Chicken, Fish, Eggs</li>
                                        <li>Healthy Fats: Avocado, Coconut oil, Olive oil</li>
                                        <li>Low-Carb Veggies: Spinach, Kale, Broccoli, Cauliflower</li>
                                        <li>Nuts & Seeds: Almonds, Walnuts, Chia seeds</li>
                                        <li>Dairy: Cheese, Butter, Cream (full fat)</li>
                                    </ul>
                                    <p style="font-size: 11px; color: var(--accent-orange); margin-top: 8px;">
                                        <i class="fas fa-info-circle"></i> Avoid: Ugali, Rice, Chapati, Sugar
                                    </p>
                                </div>
                            </div>
                            
                            <!-- High Protein -->
                            <div class="pref-option <?php echo ($preferences['diet_type'] ?? '') == 'High-Protein' ? 'selected' : ''; ?>" 
                                 onclick="selectDietType('protein')">
                                <input type="radio" id="diet_protein" name="diet_type" value="High-Protein" 
                                       <?php echo ($preferences['diet_type'] ?? '') == 'High-Protein' ? 'checked' : ''; ?>>
                                <label for="diet_protein">
                                    <i class="fas fa-dumbbell"></i>
                                    <div>
                                        <strong>High Protein</strong>
                                        <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                                            Focus on protein-rich foods
                                        </p>
                                    </div>
                                </label>
                                <div class="food-examples">
                                    <h5><i class="fas fa-utensils"></i> Example Foods:</h5>
                                    <div class="food-categories">
                                        <div class="food-category">
                                            <h6>Animal Protein</h6>
                                            <div class="food-list">Beef, Chicken, Fish, Eggs, Milk</div>
                                        </div>
                                        <div class="food-category">
                                            <h6>Plant Protein</h6>
                                            <div class="food-list">Beans, Lentils, Green grams, Peas</div>
                                        </div>
                                        <div class="food-category">
                                            <h6>Protein Meals</h6>
                                            <div class="food-list">Nyama Choma, Grilled Fish, Bean Stew</div>
                                        </div>
                                        <div class="food-category">
                                            <h6>Supplements</h6>
                                            <div class="food-list">Protein shakes, Boiled eggs, Yogurt</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Traditional Kenyan -->
                            <div class="pref-option <?php echo ($preferences['diet_type'] ?? '') == 'Traditional-Kenyan' ? 'selected' : ''; ?>" 
                                 onclick="selectDietType('traditional')">
                                <input type="radio" id="diet_traditional" name="diet_type" value="Traditional-Kenyan" 
                                       <?php echo ($preferences['diet_type'] ?? '') == 'Traditional-Kenyan' ? 'checked' : ''; ?>>
                                <label for="diet_traditional">
                                    <i class="fas fa-home"></i>
                                    <div>
                                        <strong>Traditional Kenyan</strong>
                                        <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                                            Ugali, vegetables, traditional dishes
                                        </p>
                                    </div>
                                </label>
                                <div class="food-examples">
                                    <h5><i class="fas fa-utensils"></i> Example Foods:</h5>
                                    <ul>
                                        <li><strong>Staples:</strong> Ugali, Rice, Chapati, Githeri</li>
                                        <li><strong>Proteins:</strong> Beef Stew, Chicken, Fish, Beans</li>
                                        <li><strong>Vegetables:</strong> Sukuma Wiki, Kunde, Managu, Cabbage</li>
                                        <li><strong>Traditional:</strong> Matoke, Mukimo, Omena, Mursik</li>
                                        <li><strong>Snacks:</strong> Mandazi, Samosa, Viazi Karai</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <!-- Vegetarian -->
                            <div class="pref-option <?php echo ($preferences['diet_type'] ?? '') == 'Vegetarian' ? 'selected' : ''; ?>" 
                                 onclick="selectDietType('vegetarian')">
                                <input type="radio" id="diet_vegetarian" name="diet_type" value="Vegetarian" 
                                       <?php echo ($preferences['diet_type'] ?? '') == 'Vegetarian' ? 'checked' : ''; ?>>
                                <label for="diet_vegetarian">
                                    <i class="fas fa-leaf"></i>
                                    <div>
                                        <strong>Vegetarian</strong>
                                        <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                                            Plant-based, no meat
                                        </p>
                                    </div>
                                </label>
                                <div class="food-examples">
                                    <h5><i class="fas fa-utensils"></i> Example Foods:</h5>
                                    <div class="food-categories">
                                        <div class="food-category">
                                            <h6>Protein Sources</h6>
                                            <div class="food-list">Beans, Lentils, Peas, Green grams</div>
                                        </div>
                                        <div class="food-category">
                                            <h6>Dairy/Eggs</h6>
                                            <div class="food-list">Milk, Eggs, Yogurt, Cheese</div>
                                        </div>
                                        <div class="food-category">
                                            <h6>Vegetarian Meals</h6>
                                            <div class="food-list">Vegetable Stew, Bean Chapati, Githeri</div>
                                        </div>
                                        <div class="food-category">
                                            <h6>Snacks</h6>
                                            <div class="food-list">Fruits, Nuts, Vegetable Samosa</div>
                                        </div>
                                    </div>
                                    <p style="font-size: 11px; color: var(--accent-orange); margin-top: 8px;">
                                        <i class="fas fa-info-circle"></i> Includes eggs and dairy, no meat or fish
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Vegan -->
                            <div class="pref-option <?php echo ($preferences['diet_type'] ?? '') == 'Vegan' ? 'selected' : ''; ?>" 
                                 onclick="selectDietType('vegan')">
                                <input type="radio" id="diet_vegan" name="diet_type" value="Vegan" 
                                       <?php echo ($preferences['diet_type'] ?? '') == 'Vegan' ? 'checked' : ''; ?>>
                                <label for="diet_vegan">
                                    <i class="fas fa-seedling"></i>
                                    <div>
                                        <strong>Vegan</strong>
                                        <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                                            No animal products
                                        </p>
                                    </div>
                                </label>
                                <div class="food-examples">
                                    <h5><i class="fas fa-utensils"></i> Example Foods:</h5>
                                    <ul>
                                        <li><strong>Proteins:</strong> Beans, Lentils, Chickpeas, Tofu</li>
                                        <li><strong>Grains:</strong> Ugali (water only), Rice, Millet, Sorghum</li>
                                        <li><strong>Vegetables:</strong> All fresh vegetables</li>
                                        <li><strong>Fruits:</strong> All fresh fruits</li>
                                        <li><strong>Fats:</strong> Avocado, Coconut, Plant oils</li>
                                    </ul>
                                    <p style="font-size: 11px; color: var(--accent-orange); margin-top: 8px;">
                                        <i class="fas fa-info-circle"></i> No meat, dairy, eggs, or honey
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Modern Healthy -->
                            <div class="pref-option <?php echo ($preferences['diet_type'] ?? '') == 'Modern-Healthy' ? 'selected' : ''; ?>" 
                                 onclick="selectDietType('modern')">
                                <input type="radio" id="diet_modern" name="diet_type" value="Modern-Healthy" 
                                       <?php echo ($preferences['diet_type'] ?? '') == 'Modern-Healthy' ? 'checked' : ''; ?>>
                                <label for="diet_modern">
                                    <i class="fas fa-heart"></i>
                                    <div>
                                        <strong>Modern Healthy</strong>
                                        <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                                            Mix of traditional & international
                                        </p>
                                    </div>
                                </label>
                                <div class="food-examples">
                                    <h5><i class="fas fa-utensils"></i> Example Foods:</h5>
                                    <div class="food-categories">
                                        <div class="food-category">
                                            <h6>Breakfast</h6>
                                            <div class="food-list">Oatmeal, Yogurt, Fruit Smoothies, Eggs</div>
                                        </div>
                                        <div class="food-category">
                                            <h6>Lunch/Dinner</h6>
                                            <div class="food-list">Grilled Chicken, Salads, Stir-fries, Soups</div>
                                        </div>
                                        <div class="food-category">
                                            <h6>Snacks</h6>
                                            <div class="food-list">Nuts, Fruits, Whole grain crackers</div>
                                        </div>
                                        <div class="food-category">
                                            <h6>Traditional Mix</h6>
                                            <div class="food-list">Ugali with grilled fish, Vegetable rice</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Weight Management -->
                            <div class="pref-option <?php echo ($preferences['diet_type'] ?? '') == 'Weight-Management' ? 'selected' : ''; ?>" 
                                 onclick="selectDietType('weightloss')">
                                <input type="radio" id="diet_weightloss" name="diet_type" value="Weight-Management" 
                                       <?php echo ($preferences['diet_type'] ?? '') == 'Weight-Management' ? 'checked' : ''; ?>>
                                <label for="diet_weightloss">
                                    <i class="fas fa-weight"></i>
                                    <div>
                                        <strong>Weight Management</strong>
                                        <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                                            Calorie-controlled meals
                                        </p>
                                    </div>
                                </label>
                                <div class="food-examples">
                                    <h5><i class="fas fa-utensils"></i> Example Foods:</h5>
                                    <ul>
                                        <li><strong>Lean Proteins:</strong> Grilled chicken, Fish, Beans, Lentils</li>
                                        <li><strong>Low-Cal Carbs:</strong> Sweet potatoes, Brown rice, Whole grains</li>
                                        <li><strong>Vegetables:</strong> Leafy greens, Broccoli, Carrots, Tomatoes</li>
                                        <li><strong>Fruits:</strong> Berries, Apples, Watermelon, Oranges</li>
                                        <li><strong>Healthy Fats:</strong> Avocado (small), Nuts (portion controlled)</li>
                                    </ul>
                                    <p style="font-size: 11px; color: var(--accent-orange); margin-top: 8px;">
                                        <i class="fas fa-info-circle"></i> Portion-controlled, balanced meals
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Basic Preferences -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cuisine_pref">Cuisine Preference</label>
                            <select id="cuisine_pref" name="cuisine_pref" class="form-control">
                                <option value="Kenyan" <?php echo ($preferences['cuisine_pref'] ?? 'Kenyan') == 'Kenyan' ? 'selected' : ''; ?>>Kenyan Traditional</option>
                                <option value="Continental" <?php echo ($preferences['cuisine_pref'] ?? '') == 'Continental' ? 'selected' : ''; ?>>Continental</option>
                                <option value="Fusion" <?php echo ($preferences['cuisine_pref'] ?? '') == 'Fusion' ? 'selected' : ''; ?>>Fusion</option>
                                <option value="All" <?php echo ($preferences['cuisine_pref'] ?? '') == 'All' ? 'selected' : ''; ?>>All Types</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="spicy_level">Spice Level</label>
                            <select id="spicy_level" name="spicy_level" class="form-control">
                                <option value="Mild" <?php echo ($preferences['spicy_level'] ?? 'Medium') == 'Mild' ? 'selected' : ''; ?>>Mild</option>
                                <option value="Medium" <?php echo ($preferences['spicy_level'] ?? 'Medium') == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="Spicy" <?php echo ($preferences['spicy_level'] ?? '') == 'Spicy' ? 'selected' : ''; ?>>Spicy</option>
                                <option value="Very Spicy" <?php echo ($preferences['spicy_level'] ?? '') == 'Very Spicy' ? 'selected' : ''; ?>>Very Spicy</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cooking_time">Preferred Cooking Time</label>
                            <select id="cooking_time" name="cooking_time" class="form-control">
                                <option value="Quick (<30 min)" <?php echo ($preferences['cooking_time'] ?? '30-45 minutes') == 'Quick (<30 min)' ? 'selected' : ''; ?>>Quick (&lt;30 minutes)</option>
                                <option value="30-45 minutes" <?php echo ($preferences['cooking_time'] ?? '30-45 minutes') == '30-45 minutes' ? 'selected' : ''; ?>>30-45 minutes</option>
                                <option value="45-60 minutes" <?php echo ($preferences['cooking_time'] ?? '') == '45-60 minutes' ? 'selected' : ''; ?>>45-60 minutes</option>
                                <option value="No Preference" <?php echo ($preferences['cooking_time'] ?? '') == 'No Preference' ? 'selected' : ''; ?>>No Preference</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="meals_per_day">Meals Per Day</label>
                            <select id="meals_per_day" name="meals_per_day" class="form-control">
                                <option value="2" <?php echo ($preferences['meals_per_day'] ?? 3) == 2 ? 'selected' : ''; ?>>2 Meals (Breakfast & Dinner)</option>
                                <option value="3" <?php echo ($preferences['meals_per_day'] ?? 3) == 3 ? 'selected' : ''; ?>>3 Meals (Standard)</option>
                                <option value="4" <?php echo ($preferences['meals_per_day'] ?? 3) == 4 ? 'selected' : ''; ?>>4 Meals (With Snack)</option>
                                <option value="5" <?php echo ($preferences['meals_per_day'] ?? 3) == 5 ? 'selected' : ''; ?>>5 Meals (Frequent)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Dietary Restrictions -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Dietary Restrictions & Allergies</h2>
                        <p style="color: var(--text-light); font-size: 14px;">Check all that apply</p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="diet-card">
                                <h4><i class="fas fa-ban"></i> Avoid These Foods</h4>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="avoid_pork" name="avoid_pork" value="1" 
                                           <?php echo ($preferences['avoid_pork'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="avoid_pork">Pork</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="avoid_beef" name="avoid_beef" value="1" 
                                           <?php echo ($preferences['avoid_beef'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="avoid_beef">Beef</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="avoid_fish" name="avoid_fish" value="1" 
                                           <?php echo ($preferences['avoid_fish'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="avoid_fish">Fish & Seafood</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="avoid_dairy" name="avoid_dairy" value="1" 
                                           <?php echo ($preferences['avoid_dairy'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="avoid_dairy">Dairy Products</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="avoid_gluten" name="avoid_gluten" value="1" 
                                           <?php echo ($preferences['avoid_gluten'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="avoid_gluten">Gluten (Wheat, Barley)</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="avoid_nuts" name="avoid_nuts" value="1" 
                                           <?php echo ($preferences['avoid_nuts'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="avoid_nuts">Nuts</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="avoid_eggs" name="avoid_eggs" value="1" 
                                           <?php echo ($preferences['avoid_eggs'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="avoid_eggs">Eggs</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="diet-card">
                                <h4><i class="fas fa-heartbeat"></i> Special Diets</h4>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="vegetarian" name="vegetarian" value="1" 
                                           <?php echo ($preferences['vegetarian'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="vegetarian">Vegetarian (No meat)</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="vegan" name="vegan" value="1" 
                                           <?php echo ($preferences['vegan'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="vegan">Vegan (No animal products)</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="low_carb" name="low_carb" value="1" 
                                           <?php echo ($preferences['low_carb'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="low_carb">Low Carbohydrate</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="low_fat" name="low_fat" value="1" 
                                           <?php echo ($preferences['low_fat'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="low_fat">Low Fat</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="low_sodium" name="low_sodium" value="1" 
                                           <?php echo ($preferences['low_sodium'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="low_sodium">Low Sodium</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="sugar_free" name="sugar_free" value="1" 
                                           <?php echo ($preferences['sugar_free'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="sugar_free">Sugar Free / Diabetic</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="high_protein" name="high_protein" value="1" 
                                           <?php echo ($preferences['high_protein'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="high_protein">High Protein</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="diet-card">
                                <h4><i class="fas fa-stethoscope"></i> Medical Conditions</h4>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="diabetic" name="diabetic" value="1" 
                                           <?php echo ($preferences['diabetic'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="diabetic">Diabetes</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="hypertension" name="hypertension" value="1" 
                                           <?php echo ($preferences['hypertension'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="hypertension">High Blood Pressure</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="cholesterol" name="cholesterol" value="1" 
                                           <?php echo ($preferences['cholesterol'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="cholesterol">High Cholesterol</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="pregnancy" name="pregnancy" value="1" 
                                           <?php echo ($preferences['pregnancy'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="pregnancy">Pregnancy</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="lactose" name="lactose" value="1" 
                                           <?php echo ($preferences['lactose'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="lactose">Lactose Intolerant</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Meal Preferences -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Meal Preferences</h2>
                        <p style="color: var(--text-light); font-size: 14px;">Preferred meal types</p>
                    </div>
                    
                    <div class="preferences-grid">
                        <div class="pref-option <?php echo ($preferences['pref_breakfast'] ?? 1) ? 'selected' : ''; ?>" onclick="toggleMealPref(this, 'breakfast')">
                            <input type="checkbox" id="pref_breakfast" name="pref_breakfast" value="1" 
                                   <?php echo ($preferences['pref_breakfast'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="pref_breakfast">
                                <i class="fas fa-egg"></i>
                                <div>
                                    <strong>Breakfast</strong>
                                    <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                                        Morning meals
                                    </p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="pref-option <?php echo ($preferences['pref_lunch'] ?? 1) ? 'selected' : ''; ?>" onclick="toggleMealPref(this, 'lunch')">
                            <input type="checkbox" id="pref_lunch" name="pref_lunch" value="1" 
                                   <?php echo ($preferences['pref_lunch'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="pref_lunch">
                                <i class="fas fa-hamburger"></i>
                                <div>
                                    <strong>Lunch</strong>
                                    <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                                        Midday meals
                                    </p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="pref-option <?php echo ($preferences['pref_dinner'] ?? 1) ? 'selected' : ''; ?>" onclick="toggleMealPref(this, 'dinner')">
                            <input type="checkbox" id="pref_dinner" name="pref_dinner" value="1" 
                                   <?php echo ($preferences['pref_dinner'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="pref_dinner">
                                <i class="fas fa-utensils"></i>
                                <div>
                                    <strong>Dinner</strong>
                                    <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                                        Evening meals
                                    </p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="pref-option <?php echo ($preferences['pref_snacks'] ?? 0) ? 'selected' : ''; ?>" onclick="toggleMealPref(this, 'snacks')">
                            <input type="checkbox" id="pref_snacks" name="pref_snacks" value="1" 
                                   <?php echo ($preferences['pref_snacks'] ?? 0) ? 'checked' : ''; ?>>
                            <label for="pref_snacks">
                                <i class="fas fa-apple-alt"></i>
                                <div>
                                    <strong>Snacks</strong>
                                    <p style="font-size: 12px; color: var(--text-light); margin-top: 5px;">
                                        Between meals
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="content-section" style="text-align: center; background: var(--light-green);">
                    <button type="submit" name="save_preferences" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Save Preferences
                    </button>
                    <a href="budget.php" class="btn btn-outline btn-lg" style="margin-left: 15px;">
                        <i class="fas fa-arrow-right"></i> Skip to Budget
                    </a>
                    <p style="color: var(--text-light); font-size: 14px; margin-top: 15px;">
                        Your preferences will be used to generate personalized meal plans
                    </p>
                </div>
            </form>
        </main>
    </div>
    
    <script>
    // Select diet type function
    function selectDietType(type) {
        // Remove selected class from all diet options
        const dietOptions = document.querySelectorAll('#dietTypeOptions .pref-option');
        dietOptions.forEach(option => {
            option.classList.remove('selected');
        });
        
        // Add selected class to clicked option
        const clickedOption = event.currentTarget;
        clickedOption.classList.add('selected');
        
        // Check the corresponding radio button
        const radioId = 'diet_' + type;
        document.getElementById(radioId).checked = true;
    }
    
    // Toggle meal preference function
    function toggleMealPref(element, type) {
        const checkbox = element.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        
        if (checkbox.checked) {
            element.classList.add('selected');
        } else {
            element.classList.remove('selected');
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Highlight the currently selected diet type
        const selectedRadio = document.querySelector('input[name="diet_type"]:checked');
        if (selectedRadio) {
            const parentDiv = selectedRadio.closest('.pref-option');
            if (parentDiv) {
                parentDiv.classList.add('selected');
            }
        }
        
        // Check if preferences are already saved
        <?php if (!empty($preferences)): ?>
            document.querySelector('.step:nth-child(2) .badge-warning').textContent = '✓ Completed';
            document.querySelector('.step:nth-child(2) .badge-warning').className = 'badge badge-success';
        <?php endif; ?>
    });
    </script>
</body>
</html>