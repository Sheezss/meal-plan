<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Get user's meal plans
$query = "SELECT * FROM meal_plans WHERE user_id = $user_id ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Meal Plans - Meal Plan System</title>
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
        }
        
        /* Meal Plans Grid */
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .plan-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            border: 2px solid var(--border-color);
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: var(--primary-green);
        }
        
        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .plan-header h3 {
            color: var(--dark-green);
            font-size: 18px;
            font-weight: 600;
            flex: 1;
        }
        
        .plan-dates {
            background: var(--light-green);
            color: var(--primary-green);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .plan-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .stat {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
            font-size: 14px;
        }
        
        .stat i {
            color: var(--primary-green);
        }
        
        .plan-actions {
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-light);
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
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
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
        
        /* Plan Details */
        .plan-details {
            margin-top: 15px;
            font-size: 14px;
            color: var(--text-light);
        }
        
        .plan-details p {
            margin-bottom: 5px;
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
            
            .plans-grid {
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
                <p>Manage your meal plans</p>
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
                    <h1>My Meal Plans</h1>
                    <p>Create and manage your weekly meal schedules</p>
                </div>
                <div class="header-actions">
                    <a href="create-plan.php" class="btn btn-primary">
                        <i class="fas fa-magic"></i> Generate New Plan
                    </a>
                </div>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2>Your Meal Plans</h2>
                    <button class="btn btn-primary" onclick="window.location.href='create-plan.php'">
                        <i class="fas fa-plus"></i> Create New Plan
                    </button>
                </div>
                
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="plans-grid">
                        <?php while ($plan = mysqli_fetch_assoc($result)): 
                            // Determine plan status
                            $start_date = strtotime($plan['start_date']);
                            $end_date = strtotime($plan['end_date']);
                            $current_date = time();
                            
                            $status = '';
                            $status_class = '';
                            
                            if ($current_date >= $start_date && $current_date <= $end_date) {
                                $status = 'Active';
                                $status_class = 'status-active';
                            } elseif ($current_date < $start_date) {
                                $status = 'Upcoming';
                                $status_class = 'status-upcoming';
                            } else {
                                $status = 'Completed';
                                $status_class = 'status-completed';
                            }
                            
                            // Get meal count for this plan
                            $meal_query = "SELECT COUNT(*) as meal_count FROM meals WHERE mealplan_id = {$plan['mealplan_id']}";
                            $meal_result = mysqli_query($conn, $meal_query);
                            $meal_count = 0;
                            if ($meal_result && mysqli_num_rows($meal_result) > 0) {
                                $meal_data = mysqli_fetch_assoc($meal_result);
                                $meal_count = $meal_data['meal_count'];
                            }
                        ?>
                            <div class="plan-card">
                                <div class="plan-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($plan['name']); ?></h3>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $status; ?>
                                        </span>
                                    </div>
                                    <span class="plan-dates">
                                        <?php echo date('d M', strtotime($plan['start_date'])); ?> - 
                                        <?php echo date('d M', strtotime($plan['end_date'])); ?>
                                    </span>
                                </div>
                                
                                <div class="plan-details">
                                    <p>Created: <?php echo date('F d, Y', strtotime($plan['created_at'])); ?></p>
                                    <p>Duration: <?php echo ceil(($end_date - $start_date) / (60 * 60 * 24)) + 1; ?> days</p>
                                </div>
                                
                                <div class="plan-stats">
                                    <div class="stat">
                                        <i class="fas fa-utensils"></i>
                                        <span>Meals: <?php echo $meal_count; ?></span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-wallet"></i>
                                        <span>KES <?php echo number_format($plan['total_cost'], 0); ?></span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-calendar-day"></i>
                                        <span>Day <?php 
                                            if ($status == 'Active') {
                                                $days_passed = ceil(($current_date - $start_date) / (60 * 60 * 24)) + 1;
                                                echo min($days_passed, 7) . '/7';
                                            } elseif ($status == 'Completed') {
                                                echo '7/7';
                                            } else {
                                                echo '0/7';
                                            }
                                        ?></span>
                                    </div>
                                </div>
                                
                                <div class="plan-actions">
                                    <button class="btn btn-sm btn-outline" onclick="viewPlan(<?php echo $plan['mealplan_id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-outline" onclick="editPlan(<?php echo $plan['mealplan_id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-primary" onclick="viewShoppingList(<?php echo $plan['mealplan_id']; ?>)">
                                        <i class="fas fa-shopping-cart"></i> Shop
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>No meal plans yet</h3>
                        <p>Generate your first personalized meal plan to get started</p>
                        <p style="font-size: 14px; margin-bottom: 20px; color: var(--text-light);">
                            Complete these steps first:
                        </p>
                        <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 30px;">
                            <div style="text-align: center;">
                                <div style="width: 40px; height: 40px; background: var(--primary-green); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 5px;">
                                    1
                                </div>
                                <a href="pantry.php" style="font-size: 12px; color: var(--primary-green); text-decoration: none;">
                                    Set up Pantry
                                </a>
                            </div>
                            <div style="text-align: center;">
                                <div style="width: 40px; height: 40px; background: var(--accent-orange); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 5px;">
                                    2
                                </div>
                                <a href="preferences.php" style="font-size: 12px; color: var(--accent-orange); text-decoration: none;">
                                    Set Preferences
                                </a>
                            </div>
                            <div style="text-align: center;">
                                <div style="width: 40px; height: 40px; background: var(--accent-blue); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 5px;">
                                    3
                                </div>
                                <a href="budget.php" style="font-size: 12px; color: var(--accent-blue); text-decoration: none;">
                                    Set Budget
                                </a>
                            </div>
                        </div>
                        <a href="create-plan.php" class="btn btn-primary">
                            <i class="fas fa-magic"></i> Generate Your First Plan
                        </a>
                        <p style="margin-top: 20px; font-size: 14px; color: var(--text-light);">
                            Or <a href="pantry.php" style="color: var(--primary-green);">start by setting up your pantry</a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tips Section -->
            <div class="content-section">
                <h3 style="color: var(--dark-green); margin-bottom: 20px;">
                    <i class="fas fa-lightbulb"></i> Meal Planning Tips
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <i class="fas fa-recycle" style="color: var(--primary-green); font-size: 24px; margin-bottom: 10px;"></i>
                        <h4>Use Pantry First</h4>
                        <p style="color: var(--text-light); font-size: 14px;">
                            Always check your pantry before planning meals to reduce waste and save money
                        </p>
                        <a href="pantry.php" style="color: var(--primary-green); font-size: 13px; text-decoration: none; font-weight: 500;">
                            Manage pantry →
                        </a>
                    </div>
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <i class="fas fa-balance-scale" style="color: var(--accent-orange); font-size: 24px; margin-bottom: 10px;"></i>
                        <h4>Budget Friendly</h4>
                        <p style="color: var(--text-light); font-size: 14px;">
                            Set a realistic budget and let the system suggest meals within your means
                        </p>
                        <a href="budget.php" style="color: var(--accent-orange); font-size: 13px; text-decoration: none; font-weight: 500;">
                            Set budget →
                        </a>
                    </div>
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <i class="fas fa-heart" style="color: var(--accent-red); font-size: 24px; margin-bottom: 10px;"></i>
                        <h4>Healthy Choices</h4>
                        <p style="color: var(--text-light); font-size: 14px;">
                            Choose dietary preferences that match your health goals and lifestyle
                        </p>
                        <a href="preferences.php" style="color: var(--accent-red); font-size: 13px; text-decoration: none; font-weight: 500;">
                            Set preferences →
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function viewPlan(planId) {
            window.location.href = 'view-plan.php?id=' + planId;
        }
        
        function editPlan(planId) {
            // Redirect to edit page or show edit modal
            alert('Edit feature for plan ' + planId + ' coming soon!');
        }
        
        function viewShoppingList(planId) {
            window.location.href = 'shopping-list.php?plan=' + planId;
        }
        
        // Quick stats
        document.addEventListener('DOMContentLoaded', function() {
            const planCards = document.querySelectorAll('.plan-card');
            planCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    if (!e.target.closest('.plan-actions')) {
                        const planId = this.querySelector('.plan-actions button').getAttribute('onclick').match(/\d+/)[0];
                        viewPlan(planId);
                    }
                });
            });
        });
    </script>
</body>
</html>