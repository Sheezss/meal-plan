<?php
// access_dashboard.php - Page that shows when non-logged users try to access dashboard
session_start();

// If already logged in, redirect to actual dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Dashboard - Meal Planner</title>
    <style>
        :root {
            --primary: #27ae60;
            --primary-dark: #219653;
            --light: #f8f9fa;
            --gray: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.1) 0%, rgba(33, 150, 83, 0.05) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        
        .access-container {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .icon {
            font-size: 80px;
            color: var(--primary);
            margin-bottom: 30px;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        p {
            color: var(--gray);
            margin-bottom: 40px;
            font-size: 18px;
            line-height: 1.6;
        }
        
        .buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .btn {
            padding: 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            display: block;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            border: 2px solid var(--primary);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-outline {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: rgba(39, 174, 96, 0.1);
            transform: translateY(-2px);
        }
        
        .home-link {
            margin-top: 30px;
            color: var(--gray);
            font-size: 14px;
        }
        
        .home-link a {
            color: var(--primary);
            text-decoration: none;
        }
        
        @media (max-width: 480px) {
            .access-container {
                padding: 30px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="access-container">
        <div class="icon">
            <i class="fas fa-lock"></i>
        </div>
        
        <h1>Access Your Dashboard</h1>
        <p>Please log in to access your personalized meal planning dashboard. If you don't have an account yet, you can sign up for free.</p>
        
        <div class="buttons">
            <a href="login.php" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Log In to Your Account
            </a>
            
            <a href="signup.php" class="btn btn-outline">
                <i class="fas fa-user-plus"></i> Create New Account
            </a>
        </div>
        
        <div class="home-link">
            <a href="index.php">
                <i class="fas fa-home"></i> Back to Homepage
            </a>
        </div>
    </div>
</body>
</html>