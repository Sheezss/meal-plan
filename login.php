<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['is_admin'] ? 'admin-dashboard.php' : 'dashboard.php'));
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'] ?? 'user'; // 'user' or 'admin'
    
    if (empty($email) || empty($password)) {
        $error = "Please enter email and password!";
    } else {
        // Build query based on user type
        if ($user_type === 'admin') {
            // Admin login - only users with is_admin = 1
            $sql = "SELECT * FROM users WHERE email = '$email' AND is_admin = 1";
            $redirect_to = 'admin-dashboard.php';
        } else {
            // Regular user login
            $sql = "SELECT * FROM users WHERE email = '$email'";
            $redirect_to = 'dashboard.php';
        }
        
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $user['password'])) {
                // Check if admin trying to access regular dashboard or vice versa
                if ($user_type === 'admin' && $user['is_admin'] != 1) {
                    $error = "This is not an admin account! Use regular login.";
                } else if ($user_type === 'user' && $user['is_admin'] == 1) {
                    // Admin trying to login as regular user - ask them to use admin login
                    $error = "This is an admin account! Please use the Admin Login option.";
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    $_SESSION['user_type'] = $user['user_type'];
                    
                    // Record login activity
                    $activity_type = ($user['is_admin'] == 1) ? 'Admin logged in' : 'User logged in';
                    $activity_sql = "INSERT INTO user_activity (user_id, activity_type, activity_details) 
                                    VALUES ({$user['user_id']}, 'login', '$activity_type')";
                    mysqli_query($conn, $activity_sql);
                    
                    // Redirect based on admin status
                    if ($user['is_admin'] == 1) {
                        header("Location: admin-dashboard.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                }
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            if ($user_type === 'admin') {
                $error = "No admin account found with that email!";
            } else {
                $error = "No account found with that email!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Meal Plan System</title>
    <style>
        :root {
            --primary: #27ae60;
            --primary-dark: #219653;
            --secondary: #2c3e50;
            --light: #f8f9fa;
            --border: #dee2e6;
            --admin-purple: #8e44ad;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .logo-text h1 {
            color: var(--secondary);
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .logo-text p {
            color: #666;
            font-size: 14px;
        }
        
        .welcome-text {
            color: var(--secondary);
            margin-bottom: 30px;
            font-size: 18px;
        }
        
        .user-type-toggle {
            display: flex;
            background: var(--light);
            border-radius: 10px;
            padding: 5px;
            margin-bottom: 25px;
            border: 2px solid var(--border);
        }
        
        .user-type-btn {
            flex: 1;
            padding: 12px;
            border: none;
            background: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
        }
        
        .user-type-btn.active {
            background: var(--primary);
            color: white;
        }
        
        .user-type-btn.admin {
            background: var(--admin-purple);
            color: white;
        }
        
        .user-type-btn i {
            margin-right: 8px;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--secondary);
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background: var(--light);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .signup-link {
            margin-top: 30px;
            color: #666;
            font-size: 15px;
        }
        
        .signup-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .signup-link a:hover {
            text-decoration: underline;
        }
        
        .demo-credentials {
            margin-top: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            font-size: 13px;
            color: #666;
            text-align: left;
        }
        
        .demo-credentials h4 {
            color: var(--secondary);
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .demo-credentials ul {
            list-style: none;
            padding-left: 0;
        }
        
        .demo-credentials li {
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .demo-credentials i {
            color: var(--primary);
            font-size: 12px;
        }
        
        .login-hint {
            background: #e8f4fc;
            border-left: 4px solid var(--admin-purple);
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            color: #2c3e50;
            text-align: left;
        }
        
        .login-hint i {
            color: var(--admin-purple);
            margin-right: 8px;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 25px;
            }
            
            .logo {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="logo-text">
                    <h1>NutriPlan KE</h1>
                    <p>Smart • Healthy • Affordable</p>
                </div>
            </div>
            
            <h2 class="welcome-text">Welcome Back!</h2>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered'])): ?>
                <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                    <i class="fas fa-check-circle"></i> Account created successfully! Please login.
                </div>
            <?php endif; ?>
            
            <div class="user-type-toggle" id="userTypeToggle">
                <button type="button" class="user-type-btn active" data-type="user">
                    <i class="fas fa-user"></i> User Login
                </button>
                <button type="button" class="user-type-btn" data-type="admin">
                    <i class="fas fa-user-shield"></i> Admin Login
                </button>
            </div>
            
            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="user_type" id="user_type" value="user">
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="you@example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="••••••••" required>
                    <div style="text-align: right; margin-top: 5px;">
                        <a href="#" style="font-size: 13px; color: var(--primary); text-decoration: none;">
                            <i class="fas fa-key"></i> Forgot Password?
                        </a>
                    </div>
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Sign In as User
                </button>
            </form>
            
            <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <div class="login-hint" id="adminHint" style="display: none;">
                <i class="fas fa-info-circle"></i>
                <strong>Admin Login:</strong> Use your administrator credentials to access the admin dashboard.
            </div>
            <?php endif; ?>
            
            <div class="signup-link">
                Don't have an account? <a href="signup.php">Create Account</a>
            </div>
            
            <!-- Demo credentials for testing -->
            <div class="demo-credentials">
                <h4><i class="fas fa-vial"></i> Demo Accounts:</h4>
                <ul>
                    <li><i class="fas fa-user"></i> <strong>Regular User:</strong> grace@gmail.com / password123</li>
                    <li><i class="fas fa-user-shield"></i> <strong>Admin:</strong> admin@nutriplan.ke / password123</li>
                    <li><i class="fas fa-user-md"></i> <strong>Nutritionist:</strong> bukachiselinah@gmail.com / password123</li>
                </ul>
                <p style="margin-top: 10px; font-size: 12px; color: #999;">
                    <i class="fas fa-info-circle"></i> These are sample accounts from your database
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // User type toggle
        const userTypeToggle = document.getElementById('userTypeToggle');
        const userTypeBtns = userTypeToggle.querySelectorAll('.user-type-btn');
        const userTypeInput = document.getElementById('user_type');
        const loginBtn = document.getElementById('loginBtn');
        const adminHint = document.getElementById('adminHint');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        
        userTypeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                
                // Update active button
                userTypeBtns.forEach(b => b.classList.remove('active', 'admin'));
                this.classList.add('active');
                if (type === 'admin') {
                    this.classList.add('admin');
                }
                
                // Update hidden input
                userTypeInput.value = type;
                
                // Update login button text
                if (type === 'admin') {
                    loginBtn.innerHTML = '<i class="fas fa-user-shield"></i> Access Admin Dashboard';
                    loginBtn.style.background = 'linear-gradient(135deg, #8e44ad, #7d3c98)';
                    adminHint.style.display = 'block';
                } else {
                    loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In as User';
                    loginBtn.style.background = 'linear-gradient(135deg, #27ae60, #219653)';
                    adminHint.style.display = 'none';
                }
            });
        });
        
        // Auto-fill demo credentials for testing
        document.addEventListener('DOMContentLoaded', function() {
            // Uncomment to enable URL-based auto-fill
            /*
            const urlParams = new URLSearchParams(window.location.search);
            const demo = urlParams.get('demo');
            
            if (demo === 'user') {
                emailInput.value = 'grace@gmail.com';
                passwordInput.value = 'password123';
            } else if (demo === 'admin') {
                emailInput.value = 'admin@nutriplan.ke';
                passwordInput.value = 'password123';
                // Switch to admin mode
                userTypeBtns[1].click();
            }
            */
            
            // Auto-focus email field
            emailInput.focus();
        });
        
        // Show/hide password toggle
        document.addEventListener('DOMContentLoaded', function() {
            const passwordGroup = passwordInput.parentElement;
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            toggleBtn.style.position = 'absolute';
            toggleBtn.style.right = '15px';
            toggleBtn.style.top = 'calc(50% + 12px)';
            toggleBtn.style.transform = 'translateY(-50%)';
            toggleBtn.style.background = 'none';
            toggleBtn.style.border = 'none';
            toggleBtn.style.color = '#666';
            toggleBtn.style.cursor = 'pointer';
            toggleBtn.style.fontSize = '16px';
            toggleBtn.style.zIndex = '10';
            
            passwordGroup.style.position = 'relative';
            passwordGroup.appendChild(toggleBtn);
            
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
        });
    </script>
</body>
</html>