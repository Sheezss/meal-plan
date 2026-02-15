<?php
require_once 'config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If NOT logged in, redirect to login page
    header("Location: login.php");
    exit();
}

// Only show dashboard if logged in
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Meal Plan System</title>
    <style>
        :root {
            --primary-green: #27ae60;     /* Healthy green */
            --secondary-green: #2ecc71;   /* Fresh green */
            --accent-orange: #e67e22;     /* Vitamin C orange */
            --accent-red: #e74c3c;        /* Berry red */
            --accent-blue: #3498db;       /* Hydration blue */
            --dark-green: #2e8b57;        /* Deep green */
            --light-green: #d5f4e6;       /* Light mint */
            --light-bg: #f9fdf7;          /* Very light background */
            --card-bg: #ffffff;           /* White cards */
            --text-dark: #2c3e50;         /* Dark text */
            --text-light: #7f8c8d;        /* Light text */
            --border-color: #e1f5e1;      /* Light green border */
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
        /* Main Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        /* Meal Categories Section */
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
        .snacks .category-icon { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        
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
                <p>Don't forget to have breakfast today! 🍳</p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="meal-plans.php"><i class="fas fa-calendar-alt"></i> My Meal Plans</a></li>
                <li><a href="pantry.php"><i class="fas fa-utensils"></i> My Pantry</a></li>
                <li><a href="recipes.php"><i class="fas fa-book"></i> Kenyan Recipes</a></li>
                <li><a href="shopping-list.php"><i class="fas fa-shopping-cart"></i> Shopping List</a></li>
                <li><a href="budget.php"><i class="fas fa-wallet"></i> Budget Tracker</a></li>
                <li><a href="nutrition.php"><i class="fas fa-heartbeat"></i> Nutrition Stats</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
            </ul>
            
            <div class="todays-plan">
                <h4><i class="fas fa-calendar-day"></i> Today's Plan</h4>
                <div class="meal-time">
                    <div class="meal-time-header">
                        <span class="meal-time-title">Breakfast</span>
                        <span class="meal-time-time">06:30 AM</span>
                    </div>
                    <div class="meal-item">
                        <h5>Ugali & Sukuma Wiki</h5>
                        <p>420 cal • KES 150</p>
                    </div>
                </div>
                <div class="meal-time">
                    <div class="meal-time-header">
                        <span class="meal-time-title">Lunch</span>
                        <span class="meal-time-time">01:00 PM</span>
                    </div>
                    <div class="meal-item">
                        <h5>Rice & Beef Stew</h5>
                        <p>650 cal • KES 250</p>
                    </div>
                </div>
                <div class="meal-time">
                    <div class="meal-time-header">
                        <span class="meal-time-title">Dinner</span>
                        <span class="meal-time-time">07:30 PM</span>
                    </div>
                    <div class="meal-item">
                        <h5>Chapati & Lentils</h5>
                        <p>520 cal • KES 180</p>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <div class="welcome-message">
                    <h1>Find the menu you want</h1>
                    <p>Healthy eating made simple with local Kenyan foods</p>
                </div>
                <div class="header-actions">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search for recipes, ingredients...">
                    </div>
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="profile-btn">
                        <i class="fas fa-user"></i>
                    </button>
                </div>
            </div>
            
            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Left Column: Meal Categories -->
                <div class="meal-categories">
                    <div class="section-header">
                        <h2>Today's Meal Categories</h2>
                        <a href="#" class="view-all">View All →</a>
                    </div>
                    
                    <div class="category-grid">
                        <!-- Breakfast -->
                        <div class="category-card breakfast">
                            <div class="category-header">
                                <div class="category-icon">
                                    <i class="fas fa-sun"></i>
                                </div>
                                <h3>Breakfast <span class="meal-time-time">06:30 AM</span></h3>
                            </div>
                            <div class="meal-items">
                                <div class="meal-card">
                                    <div class="meal-info">
                                        <h4>Mandazi & Chai</h4>
                                        <span class="meal-price">KES 80</span>
                                    </div>
                                    <div class="meal-meta">
                                        <span class="meal-calories">320 cal</span>
                                        <button class="add-btn">Add</button>
                                    </div>
                                </div>
                                <div class="meal-card">
                                    <div class="meal-info">
                                        <h4>Ugali & Omena</h4>
                                        <span class="meal-price">KES 120</span>
                                    </div>
                                    <div class="meal-meta">
                                        <span class="meal-calories">450 cal</span>
                                        <button class="add-btn">Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lunch -->
                        <div class="category-card lunch">
                            <div class="category-header">
                                <div class="category-icon">
                                    <i class="fas fa-sun"></i>
                                </div>
                                <h3>Lunch <span class="meal-time-time">01:00 PM</span></h3>
                            </div>
                            <div class="meal-items">
                                <div class="meal-card">
                                    <div class="meal-info">
                                        <h4>Pilau & Kachumbari</h4>
                                        <span class="meal-price">KES 220</span>
                                    </div>
                                    <div class="meal-meta">
                                        <span class="meal-calories">680 cal</span>
                                        <button class="add-btn">Add</button>
                                    </div>
                                </div>
                                <div class="meal-card">
                                    <div class="meal-info">
                                        <h4>Githeri & Avocado</h4>
                                        <span class="meal-price">KES 180</span>
                                    </div>
                                    <div class="meal-meta">
                                        <span class="meal-calories">520 cal</span>
                                        <button class="add-btn">Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dinner -->
                        <div class="category-card dinner">
                            <div class="category-header">
                                <div class="category-icon">
                                    <i class="fas fa-moon"></i>
                                </div>
                                <h3>Dinner <span class="meal-time-time">07:30 PM</span></h3>
                            </div>
                            <div class="meal-items">
                                <div class="meal-card">
                                    <div class="meal-info">
                                        <h4>Chapati & Beans</h4>
                                        <span class="meal-price">KES 160</span>
                                    </div>
                                    <div class="meal-meta">
                                        <span class="meal-calories">580 cal</span>
                                        <button class="add-btn">Add</button>
                                    </div>
                                </div>
                                <div class="meal-card">
                                    <div class="meal-info">
                                        <h4>Mukimo & Stew</h4>
                                        <span class="meal-price">KES 200</span>
                                    </div>
                                    <div class="meal-meta">
                                        <span class="meal-calories">610 cal</span>
                                        <button class="add-btn">Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Snacks -->
                        <div class="category-card snacks">
                            <div class="category-header">
                                <div class="category-icon">
                                    <i class="fas fa-apple-alt"></i>
                                </div>
                                <h3>Snacks</h3>
                            </div>
                            <div class="meal-items">
                                <div class="meal-card">
                                    <div class="meal-info">
                                        <h4>Fresh Fruits</h4>
                                        <span class="meal-price">KES 50</span>
                                    </div>
                                    <div class="meal-meta">
                                        <span class="meal-calories">120 cal</span>
                                        <button class="add-btn">Add</button>
                                    </div>
                                </div>
                                <div class="meal-card">
                                    <div class="meal-info">
                                        <h4>Roasted Nuts</h4>
                                        <span class="meal-price">KES 100</span>
                                    </div>
                                    <div class="meal-meta">
                                        <span class="meal-calories">180 cal</span>
                                        <button class="add-btn">Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recommended Section -->
                    <div class="recommended-section">
                        <div class="section-header">
                            <h2>Recommended For You</h2>
                            <a href="#" class="view-all">View All →</a>
                        </div>
                        <div class="recommended-grid">
                            <div class="recommended-card">
                                <div class="food-icon">
                                    <i class="fas fa-drumstick-bite"></i>
                                </div>
                                <h4>Nyama Choma</h4>
                                <div class="price-comparison">
                                    <span class="current-price">KES 450</span>
                                    <span class="original-price">KES 500</span>
                                </div>
                                <button class="add-btn">Add to Plan</button>
                            </div>
                            <div class="recommended-card">
                                <div class="food-icon">
                                    <i class="fas fa-fish"></i>
                                </div>
                                <h4>Fried Tilapia</h4>
                                <div class="price-comparison">
                                    <span class="current-price">KES 380</span>
                                    <span class="original-price">KES 420</span>
                                </div>
                                <button class="add-btn">Add to Plan</button>
                            </div>
                            <div class="recommended-card">
                                <div class="food-icon">
                                    <i class="fas fa-leaf"></i>
                                </div>
                                <h4>Vegetable Mix</h4>
                                <div class="price-comparison">
                                    <span class="current-price">KES 180</span>
                                    <span class="original-price">KES 200</span>
                                </div>
                                <button class="add-btn">Add to Plan</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Your Meal Plan -->
                <div class="meal-plan-sidebar">
                    <div class="plan-header">
                        <h2>Your Meal Plan</h2>
                        <p class="plan-date">Today, <?php echo date('d M Y'); ?></p>
                    </div>
                    
                    <div class="nutrition-summary">
                        <h4>Nutrition Summary</h4>
                        <div class="nutrition-stats">
                            <div class="stat-item">
                                <div class="stat-value">1,590</div>
                                <div class="stat-label">Calories</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">62g</div>
                                <div class="stat-label">Protein</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">185g</div>
                                <div class="stat-label">Carbs</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">48g</div>
                                <div class="stat-label">Fats</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="plan-actions">
                        <button class="plan-btn primary">
                            <i class="fas fa-plus-circle"></i> Create New Plan
                        </button>
                        <button class="plan-btn secondary">
                            <i class="fas fa-shopping-cart"></i> Generate Shopping List
                        </button>
                        <button class="plan-btn secondary">
                            <i class="fas fa-print"></i> Print Plan
                        </button>
                    </div>
                    
                    <div class="todays-plan">
                        <h4><i class="fas fa-calendar-check"></i> Planned Meals</h4>
                        <div class="meal-time">
                            <div class="meal-time-header">
                                <span class="meal-time-title">Breakfast</span>
                                <span class="meal-time-time">06:30 AM</span>
                            </div>
                            <div class="meal-item">
                                <h5>Mandazi & Chai</h5>
                                <p>320 cal • KES 80</p>
                                <button class="add-btn" style="padding: 3px 8px; font-size: 11px;">Edit</button>
                            </div>
                        </div>
                        <div class="meal-time">
                            <div class="meal-time-header">
                                <span class="meal-time-title">Lunch</span>
                                <span class="meal-time-time">01:00 PM</span>
                            </div>
                            <div class="meal-item">
                                <h5>Pilau & Kachumbari</h5>
                                <p>680 cal • KES 220</p>
                                <button class="add-btn" style="padding: 3px 8px; font-size: 11px;">Edit</button>
                            </div>
                        </div>
                        <div class="meal-time">
                            <div class="meal-time-header">
                                <span class="meal-time-title">Dinner</span>
                                <span class="meal-time-time">07:30 PM</span>
                            </div>
                            <div class="meal-item">
                                <h5>Chapati & Beans</h5>
                                <p>580 cal • KES 160</p>
                                <button class="add-btn" style="padding: 3px 8px; font-size: 11px;">Edit</button>
                            </div>
                        </div>
                        <div class="meal-time">
                            <div class="meal-time-header">
                                <span class="meal-time-title">Haven't ordered yet</span>
                            </div>
                            <div class="meal-item" style="background: #f8f9fa; text-align: center; color: var(--text-light);">
                                <p><i>Drop here to add more items</i></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="budget-summary" style="margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                        <h4 style="margin-bottom: 15px; color: var(--dark-green);">Budget Summary</h4>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Today's Total:</span>
                            <span style="color: var(--primary-green); font-weight: bold;">KES 460</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Weekly Budget:</span>
                            <span>KES 3,500 / KES 5,000</span>
                        </div>
                        <div style="background: var(--light-green); height: 8px; border-radius: 4px; margin-top: 10px;">
                            <div style="width: 70%; background: var(--primary-green); height: 100%; border-radius: 4px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Simple JavaScript for interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add button functionality
            const addButtons = document.querySelectorAll('.add-btn');
            addButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const mealCard = this.closest('.meal-card');
                    const mealName = mealCard.querySelector('h4').textContent;
                    const mealPrice = mealCard.querySelector('.meal-price').textContent;
                    const mealCalories = mealCard.querySelector('.meal-calories').textContent;
                    
                    alert(`Added to your meal plan:\n${mealName}\n${mealPrice}\n${mealCalories}`);
                    this.textContent = 'Added ✓';
                    this.style.background = '#95a5a6';
                    this.disabled = true;
                });
            });
            
            // Search functionality
            const searchInput = document.querySelector('.search-bar input');
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const mealCards = document.querySelectorAll('.meal-card');
                
                mealCards.forEach(card => {
                    const mealName = card.querySelector('h4').textContent.toLowerCase();
                    if (mealName.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>