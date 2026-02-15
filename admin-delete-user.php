<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['confirm']) && $_GET['confirm'] == 1) {
    $user_id = intval($_GET['id']);
    $current_user_id = $_SESSION['user_id'];
    
    // Don't allow deleting yourself
    if ($user_id == $current_user_id) {
        $_SESSION['error'] = "You cannot delete your own account!";
        header("Location: admin-dashboard.php#users");
        exit();
    }
    
    // Get user info for logging
    $user_query = mysqli_query($conn, "SELECT full_name, email FROM users WHERE user_id = $user_id");
    $user_data = mysqli_fetch_assoc($user_query);
    
    // Delete user (cascade will handle related records)
    $delete_sql = "DELETE FROM users WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $delete_sql)) {
        // Log the action
        $log_sql = "INSERT INTO user_activity (user_id, activity_type, activity_details) 
                    VALUES ($current_user_id, 'admin_action', 'Deleted user: {$user_data['email']}')";
        mysqli_query($conn, $log_sql);
        
        $_SESSION['success'] = "User '{$user_data['full_name']}' deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting user: " . mysqli_error($conn);
    }
}

header("Location: admin-dashboard.php#users");
exit();
?>