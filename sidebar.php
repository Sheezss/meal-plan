<?php
// sidebar.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_name = $_SESSION['full_name'];
?>
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
        <p>Plan smart, eat healthy! 🍎</p>
    </div>
    
    <ul class="nav-menu">
        <?php
        // Get current page
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>
        <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="meal-plans.php" class="<?php echo $current_page == 'meal-plans.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> My Meal Plans</a></li>
        <li><a href="pantry.php" class="<?php echo $current_page == 'pantry.php' ? 'active' : ''; ?>"><i class="fas fa-utensils"></i> My Pantry</a></li>
        <li><a href="recipes.php" class="<?php echo $current_page == 'recipes.php' ? 'active' : ''; ?>"><i class="fas fa-book"></i> Kenyan Recipes</a></li>
        <li><a href="shopping-list.php" class="<?php echo $current_page == 'shopping-list.php' ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Shopping List</a></li>
        <li><a href="budget.php" class="<?php echo $current_page == 'budget.php' ? 'active' : ''; ?>"><i class="fas fa-wallet"></i> Budget Tracker</a></li>
        <li><a href="nutrition.php" class="<?php echo $current_page == 'nutrition.php' ? 'active' : ''; ?>"><i class="fas fa-heartbeat"></i> Nutrition Stats</a></li>
        <li><a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>"><i class="fas fa-user"></i> My Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</aside>