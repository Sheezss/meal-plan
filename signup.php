<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['is_admin'] ? 'admin-dashboard.php' : 'dashboard.php'));
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($user_type)) {
        $error = "All required fields must be filled!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        // Check if email exists
        $check_sql = "SELECT user_id FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Email already registered!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // IMPORTANT: Regular users can NEVER register as admin
            // Admin accounts must be created manually in database or by existing admins
            $is_admin = 0; // Always 0 for user registration
            
            // Insert user (regular user only)
            $sql = "INSERT INTO users (full_name, email, password, user_type, phone_number, date_registered, is_admin) 
                    VALUES ('$full_name', '$email', '$hashed_password', '$user_type', '$phone', CURDATE(), $is_admin)";
            
            if (mysqli_query($conn, $sql)) {
                $user_id = mysqli_insert_id($conn);
                
                // Record signup activity
                $activity_sql = "INSERT INTO user_activity (user_id, activity_type, activity_details) 
                                VALUES ($user_id, 'signup', 'New $user_type account created')";
                mysqli_query($conn, $activity_sql);
                
                // Auto-create default preferences
                $pref_sql = "INSERT INTO user_preferences (user_id, diet_type, cuisine_pref, spicy_level, cooking_time, meals_per_day)
                            VALUES ($user_id, 'Balanced', 'Kenyan', 'Medium', '30-45 minutes', 3)";
                mysqli_query($conn, $pref_sql);
                
                // Redirect to login with success message
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
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
    <title>Sign Up - Meal Plan System</title>
    <style>
        :root {
            --primary: #27ae60;
            --primary-dark: #219653;
            --secondary: #2c3e50;
            --light: #f8f9fa;
            --border: #dee2e6;
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
        
        .signup-container {
            width: 100%;
            max-width: 500px;
        }
        
        .signup-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
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
            text-align: center;
        }
        
        .admin-note {
            background: #f8f0ff;
            border-left: 4px solid #8e44ad;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #2c3e50;
        }
        
        .admin-note i {
            color: #8e44ad;
            margin-right: 8px;
        }
        
        .form-group {
            margin-bottom: 20px;
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
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 18px center;
            background-size: 14px;
            padding-right: 45px;
        }
        
        .btn-signup {
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
        }
        
        .btn-signup:hover {
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
        
        .login-link {
            margin-top: 30px;
            color: #666;
            font-size: 15px;
            text-align: center;
        }
        
        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .strength-bar {
            height: 4px;
            background: #eee;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            background: #e74c3c;
            transition: all 0.3s;
        }
        
        .admin-link {
            margin-top: 20px;
            text-align: center;
        }
        
        .admin-link a {
            color: #8e44ad;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        
        .admin-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            .signup-card {
                padding: 30px 25px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
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
    <div class="signup-container">
        <div class="signup-card">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="logo-text">
                    <h1>Create Account</h1>
                    <p>Join NutriPlan KE</p>
                </div>
            </div>
            
            <h2 class="welcome-text">Start Your Healthy Journey</h2>
            
            <div class="admin-note">
                <i class="fas fa-user-shield"></i>
                <strong>Note:</strong> This form is for regular user registration only. 
                Admin accounts must be created by existing administrators.
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="signupForm">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           placeholder="John Doe" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="you@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="••••••••" required>
                        <div class="password-strength">
                            <span id="strengthText">Password strength: </span>
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="••••••••" required>
                        <div class="password-strength">
                            <span id="matchText">Passwords match: </span>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="user_type">Account Type *</label>
                        <select id="user_type" name="user_type" class="form-control" required>
                            <option value="">Select type</option>
                            <option value="Individual" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'Individual') ? 'selected' : ''; ?>>Individual</option>
                            <option value="Family" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'Family') ? 'selected' : ''; ?>>Family</option>
                            <option value="Nutritionist" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'Nutritionist') ? 'selected' : ''; ?>>Nutritionist</option>
                            <option value="Organization" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'Organization') ? 'selected' : ''; ?>>Organization</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               placeholder="0712 345 678" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn-signup">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="admin-link">
                <a href="login.php">
                    <i class="fas fa-user-shield"></i> Already have an admin account? Login here
                </a>
            </div>
            
            <div class="login-link">
                Already have a user account? <a href="login.php">Sign In</a>
            </div>
        </div>
    </div>
    
    <script>
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        const matchText = document.getElementById('matchText');
        
        passwordInput.addEventListener('input', updatePasswordStrength);
        confirmInput.addEventListener('input', checkPasswordMatch);
        
        function updatePasswordStrength() {
            const password = passwordInput.value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            const width = strength * 25;
            strengthFill.style.width = width + '%';
            
            const strengths = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#27ae60'];
            
            strengthText.textContent = 'Strength: ' + strengths[strength];
            strengthFill.style.background = colors[strength];
            
            checkPasswordMatch();
        }
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm === '') {
                matchText.textContent = 'Match: ';
                matchText.style.color = '';
            } else if (password === confirm) {
                matchText.textContent = 'Match: ✓';
                matchText.style.color = '#27ae60';
            } else {
                matchText.textContent = 'Match: ✗';
                matchText.style.color = '#e74c3c';
            }
        }
        
        // Form validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmInput.focus();
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                passwordInput.focus();
                return;
            }
        });
        
        // Auto-format phone number
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.match(/.{1,4}/g).join(' ');
            }
            e.target.value = value;
        });
        
        // Auto-focus on first field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('full_name').focus();
        });
    </script>
</body>
</html>