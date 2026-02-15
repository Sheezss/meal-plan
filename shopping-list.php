<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Get shopping list items - FIXED QUERY
$shopping_query = "SELECT 
                    sl.shoppinglist_id,
                    sl.mealplan_id,
                    sl.fooditem_id,
                    sl.quantity_needed,
                    sl.unit,
                    sl.status,
                    sl.created_at,
                    COALESCE(fi.name, 'Unknown Item') as item_name,
                    COALESCE(fi.category, 'Other') as category,
                    mp.name as plan_name,
                    mp.mealplan_id,
                    mp.user_id
                   FROM shopping_lists sl
                   LEFT JOIN food_items fi ON sl.fooditem_id = fi.fooditem_id
                   JOIN meal_plans mp ON sl.mealplan_id = mp.mealplan_id
                   WHERE mp.user_id = $user_id
                   ORDER BY 
                    CASE WHEN sl.status = 'Pending' THEN 1 ELSE 2 END,
                    COALESCE(fi.category, 'Other'), 
                    COALESCE(fi.name, 'Unknown Item')";

$shopping_result = mysqli_query($conn, $shopping_query);

if (!$shopping_result) {
    die("Query failed: " . mysqli_error($conn));
}

// Get shopping list stats
$stats = [
    'total_items' => 0,
    'pending_items' => 0,
    'estimated_cost' => 0,
    'purchased_items' => 0
];

$pending_query = "SELECT COUNT(*) as count 
                  FROM shopping_lists sl
                  JOIN meal_plans mp ON sl.mealplan_id = mp.mealplan_id
                  WHERE mp.user_id = $user_id AND sl.status = 'Pending'";
$pending_result = mysqli_query($conn, $pending_query);
if ($pending_result && mysqli_num_rows($pending_result) > 0) {
    $pending_data = mysqli_fetch_assoc($pending_result);
    $stats['pending_items'] = $pending_data['count'];
}

$purchased_query = "SELECT COUNT(*) as count 
                    FROM shopping_lists sl
                    JOIN meal_plans mp ON sl.mealplan_id = mp.mealplan_id
                    WHERE mp.user_id = $user_id AND sl.status = 'Purchased'";
$purchased_result = mysqli_query($conn, $purchased_query);
if ($purchased_result && mysqli_num_rows($purchased_result) > 0) {
    $purchased_data = mysqli_fetch_assoc($purchased_result);
    $stats['purchased_items'] = $purchased_data['count'];
}

// Calculate estimated cost
$cost_query = "SELECT SUM(sl.quantity_needed * COALESCE(fi.price_per_unit, 50)) as total_cost
               FROM shopping_lists sl
               LEFT JOIN food_items fi ON sl.fooditem_id = fi.fooditem_id
               JOIN meal_plans mp ON sl.mealplan_id = mp.mealplan_id
               WHERE mp.user_id = $user_id AND sl.status = 'Pending'";
$cost_result = mysqli_query($conn, $cost_query);
if ($cost_result && mysqli_num_rows($cost_result) > 0) {
    $cost_data = mysqli_fetch_assoc($cost_result);
    $stats['estimated_cost'] = $cost_data['total_cost'] ?? 0;
}

$stats['total_items'] = $stats['pending_items'] + $stats['purchased_items'];

// Handle mark as purchased
if (isset($_POST['mark_purchased'])) {
    $item_id = intval($_POST['item_id']);
    $update_query = "UPDATE shopping_lists SET status = 'Purchased', purchased_at = NOW() 
                     WHERE shoppinglist_id = $item_id";
    mysqli_query($conn, $update_query);
    header("Location: shopping-list.php");
    exit();
}

// Handle delete item
if (isset($_POST['delete_item'])) {
    $item_id = intval($_POST['item_id']);
    $delete_query = "DELETE FROM shopping_lists WHERE shoppinglist_id = $item_id";
    mysqli_query($conn, $delete_query);
    header("Location: shopping-list.php");
    exit();
}

// Handle clear purchased
if (isset($_POST['clear_purchased'])) {
    $clear_query = "DELETE sl FROM shopping_lists sl
                    JOIN meal_plans mp ON sl.mealplan_id = mp.mealplan_id
                    WHERE mp.user_id = $user_id AND sl.status = 'Purchased'";
    mysqli_query($conn, $clear_query);
    header("Location: shopping-list.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping List - Meal Plan System</title>
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
        
        .btn-warning {
            background: var(--accent-orange);
            color: white;
        }
        
        .btn-danger {
            background: var(--accent-red);
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
            color: var(--dark-green);
            font-size: 22px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
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
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 20px;
        }
        
        .stat-icon.total { background: linear-gradient(135deg, var(--primary-green), #2980b9); }
        .stat-icon.pending { background: linear-gradient(135deg, var(--accent-orange), #e67e22); }
        .stat-icon.purchased { background: linear-gradient(135deg, var(--secondary-green), #27ae60); }
        .stat-icon.cost { background: linear-gradient(135deg, #9b59b6, #8e44ad); }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 14px;
        }
        
        /* Shopping List Table */
        .shopping-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .shopping-table th {
            background: var(--light-bg);
            color: var(--dark-green);
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid var(--border-color);
        }
        
        .shopping-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        
        .shopping-table tr:hover {
            background: var(--light-bg);
        }
        
        .shopping-table tr.purchased {
            opacity: 0.7;
            background: #f8f9fa;
        }
        
        .shopping-table tr.purchased td {
            text-decoration: line-through;
            color: var(--text-light);
        }
        
        .item-name {
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .item-category {
            background: var(--light-green);
            color: var(--primary-green);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .item-plan {
            color: var(--text-light);
            font-size: 13px;
            margin-top: 5px;
        }
        
        .quantity-badge {
            background: var(--light-bg);
            color: var(--text-dark);
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid var(--border-color);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-purchased {
            background: #d4edda;
            color: #155724;
        }
        
        .item-actions {
            display: flex;
            gap: 8px;
        }
        
        /* Category Sections */
        .category-section {
            margin-bottom: 30px;
            background: var(--light-bg);
            border-radius: 15px;
            padding: 20px;
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
        
        /* Action Form */
        .action-form {
            display: inline;
        }
        
        /* Print Styles */
        .print-btn {
            background: var(--accent-blue);
            color: white;
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
            
            .shopping-table {
                display: block;
                overflow-x: auto;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media print {
            .sidebar, .header-actions, .section-header button, .item-actions {
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
                <p>Manage your shopping list</p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="meal_plan.php"><i class="fas fa-calendar-alt"></i> My Meal Plans</a></li>
                <li><a href="pantry.php"><i class="fas fa-utensils"></i> My Pantry</a></li>
                <li><a href="recipes.php"><i class="fas fa-book"></i> Kenyan Recipes</a></li>
                <li><a href="shopping-list.php" class="active"><i class="fas fa-shopping-cart"></i> Shopping List</a></li>
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
                    <h1>Shopping List</h1>
                    <p>Items needed for your meal plans</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print List
                    </button>
                    <a href="create-plan.php" class="btn btn-outline">
                        <i class="fas fa-magic"></i> Generate New Plan
                    </a>
                </div>
            </div>
            
            <!-- Success Message -->
            <?php if (isset($_GET['saved'])): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                    <i class="fas fa-check-circle"></i> Meal plan saved successfully! Shopping list updated.
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_items']; ?></div>
                    <div class="stat-label">Total Items</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['pending_items']; ?></div>
                    <div class="stat-label">To Buy</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon purchased">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['purchased_items']; ?></div>
                    <div class="stat-label">Purchased</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon cost">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-value">KES <?php echo number_format($stats['estimated_cost'], 0); ?></div>
                    <div class="stat-label">Estimated Cost</div>
                </div>
            </div>
            
            <!-- Shopping List -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Shopping Items</h2>
                    <?php if ($stats['purchased_items'] > 0): ?>
                    <form method="POST" action="" class="action-form" onsubmit="return confirm('Clear all purchased items?')">
                        <button type="submit" name="clear_purchased" class="btn btn-warning">
                            <i class="fas fa-trash"></i> Clear Purchased
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                
                <?php if (mysqli_num_rows($shopping_result) > 0): ?>
                    <?php 
                    // Group items by category
                    mysqli_data_seek($shopping_result, 0);
                    $items_by_category = [];
                    while ($item = mysqli_fetch_assoc($shopping_result)) {
                        $category = $item['category'] ?? 'Other';
                        if (!isset($items_by_category[$category])) {
                            $items_by_category[$category] = [];
                        }
                        $items_by_category[$category][] = $item;
                    }
                    
                    foreach ($items_by_category as $category => $category_items): 
                        $category_icon = '';
                        switch(strtolower($category)) {
                            case 'vegetables':
                                $category_icon = 'fas fa-carrot'; 
                                break;
                            case 'fruits':
                                $category_icon = 'fas fa-apple-alt'; 
                                break;
                            case 'meat':
                            case 'protein':
                                $category_icon = 'fas fa-drumstick-bite'; 
                                break;
                            case 'grains':
                            case 'cereals':
                                $category_icon = 'fas fa-wheat-awn'; 
                                break;
                            case 'dairy':
                                $category_icon = 'fas fa-cheese'; 
                                break;
                            case 'spices':
                            case 'seasonings':
                                $category_icon = 'fas fa-mortar-pestle'; 
                                break;
                            default:
                                $category_icon = 'fas fa-shopping-basket';
                        }
                    ?>
                        <div class="category-section">
                            <h3><i class="<?php echo $category_icon; ?>"></i> <?php echo htmlspecialchars($category); ?></h3>
                            <table class="shopping-table">
                                <thead>
                                    <tr>
                                        <th width="30%">Item</th>
                                        <th width="15%">Quantity</th>
                                        <th width="15%">Plan</th>
                                        <th width="15%">Status</th>
                                        <th width="25%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category_items as $item): ?>
                                        <tr class="<?php echo $item['status'] == 'Purchased' ? 'purchased' : ''; ?>">
                                            <td>
                                                <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                                <div class="item-category"><?php echo htmlspecialchars($category); ?></div>
                                            </td>
                                            <td>
                                                <span class="quantity-badge">
                                                    <?php echo $item['quantity_needed']; ?> <?php echo $item['unit']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="item-plan"><?php echo htmlspecialchars($item['plan_name']); ?></div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($item['status']); ?>">
                                                    <?php echo $item['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="item-actions">
                                                    <?php if ($item['status'] == 'Pending'): ?>
                                                        <form method="POST" action="" class="action-form">
                                                            <input type="hidden" name="item_id" value="<?php echo $item['shoppinglist_id']; ?>">
                                                            <button type="submit" name="mark_purchased" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-check"></i> Buy
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" action="" class="action-form" onsubmit="return confirm('Delete this item?')">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['shoppinglist_id']; ?>">
                                                        <button type="submit" name="delete_item" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i> Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Your shopping list is empty</h3>
                        <p>Generate a meal plan to create a shopping list</p>
                        <a href="create-plan.php" class="btn btn-primary">
                            <i class="fas fa-magic"></i> Generate Meal Plan
                        </a>
                        <?php if ($stats['purchased_items'] > 0): ?>
                        <p style="margin-top: 20px; color: var(--text-light);">
                            You have <?php echo $stats['purchased_items']; ?> purchased items. 
                            <a href="#" onclick="document.querySelector('[name=clear_purchased]').click(); return false;" style="color: var(--accent-red);">Clear them</a>.
                        </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Shopping Tips -->
            <div class="content-section">
                <h3 style="color: var(--dark-green); margin-bottom: 20px;">
                    <i class="fas fa-lightbulb"></i> Shopping Tips
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <i class="fas fa-store" style="color: var(--primary-green); font-size: 24px; margin-bottom: 10px;"></i>
                        <h4>Local Markets</h4>
                        <p style="color: var(--text-light); font-size: 14px;">
                            Visit local markets for fresh produce at better prices
                        </p>
                    </div>
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <i class="fas fa-clock" style="color: var(--accent-orange); font-size: 24px; margin-bottom: 10px;"></i>
                        <h4>Plan Ahead</h4>
                        <p style="color: var(--text-light); font-size: 14px;">
                            Shop once a week to save time and reduce impulse buying
                        </p>
                    </div>
                    <div style="background: var(--light-bg); padding: 20px; border-radius: 10px;">
                        <i class="fas fa-balance-scale" style="color: var(--accent-blue); font-size: 24px; margin-bottom: 10px;"></i>
                        <h4>Compare Prices</h4>
                        <p style="color: var(--text-light); font-size: 14px;">
                            Check prices at different stores for the best deals
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Print shopping list
        function printShoppingList() {
            window.print();
        }
        
        // Mark all as purchased
        function markAllPurchased() {
            if (confirm('Mark all items as purchased?')) {
                const forms = document.querySelectorAll('.action-form');
                forms.forEach(form => {
                    if (form.querySelector('[name="mark_purchased"]')) {
                        form.submit();
                    }
                });
            }
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+P to print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                printShoppingList();
            }
            
            // Ctrl+M to mark all
            if (e.ctrlKey && e.key === 'm') {
                e.preventDefault();
                markAllPurchased();
            }
        });
    </script>
</body>
</html>