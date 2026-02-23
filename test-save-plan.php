<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$user_id = $_SESSION['user_id'];
echo "<h2>Test Saving Meal Plan</h2>";
echo "<p>User ID: $user_id</p>";

// Simple test insert
$plan_name = "TEST PLAN " . date('Y-m-d H:i:s');
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+6 days'));
$total_cost = 1000;

$sql = "INSERT INTO meal_plans (user_id, name, start_date, end_date, total_cost, created_at) 
        VALUES ($user_id, '$plan_name', '$start_date', '$end_date', $total_cost, NOW())";

echo "<p>SQL: " . htmlspecialchars($sql) . "</p>";

if (mysqli_query($conn, $sql)) {
    $plan_id = mysqli_insert_id($conn);
    echo "<p style='color:green'>✅ SUCCESS! Meal plan saved with ID: $plan_id</p>";
    
    // Verify it was saved
    $check = mysqli_query($conn, "SELECT * FROM meal_plans WHERE mealplan_id = $plan_id");
    if (mysqli_num_rows($check) > 0) {
        $plan = mysqli_fetch_assoc($check);
        echo "<p>✅ Verified: Plan exists in database</p>";
        echo "<pre>";
        print_r($plan);
        echo "</pre>";
    }
} else {
    echo "<p style='color:red'>❌ ERROR: " . mysqli_error($conn) . "</p>";
}

// Show all plans for this user
$all_plans = mysqli_query($conn, "SELECT * FROM meal_plans WHERE user_id = $user_id ORDER BY mealplan_id DESC");
echo "<h3>All Your Meal Plans:</h3>";
if (mysqli_num_rows($all_plans) > 0) {
    echo "<ul>";
    while ($plan = mysqli_fetch_assoc($all_plans)) {
        echo "<li>ID: {$plan['mealplan_id']} - {$plan['name']} - {$plan['created_at']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No meal plans found for user $user_id</p>";
}
?>