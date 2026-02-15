<?php
session_start();
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $current_user_id = $_SESSION['user_id'];
    
    // Don't allow removing your own admin status
    if ($user_id == $current_user_id) {
        $_SESSION['error'] = "You cannot remove your own administrator status!";
        header("Location: admin-dashboard.php#users");
        exit();
    }
    
    // Remove admin privileges
    $update_sql = "UPDATE users SET is_admin = 0, user_type = 'Individual' WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        // Get user info for logging
        $user_query = mysqli_query($conn, "SELECT full_name, email FROM users WHERE user_id = $user_id");
        $user_data = mysqli_fetch_assoc($user_query);
        
        // Log the action
        $log_sql = "INSERT INTO user_activity (user_id, activity_type, activity_details) 
                    VALUES ($current_user_id, 'admin_action', 'Removed admin privileges from: {$user_data['email']}')";
        mysqli_query($conn, $log_sql);
        
        $_SESSION['success'] = "Admin privileges removed from {$user_data['full_name']}";
    } else {
        $_SESSION['error'] = "Error updating user: " . mysqli_error($conn);
    }
}

header("Location: admin-dashboard.php#users");
exit();
?>