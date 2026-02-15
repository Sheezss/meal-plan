<?php
// index.php - Homepage
session_start();

// Check if user is logged in to show appropriate navigation
$logged_in = isset($_SESSION['user_id']);
$user_name = $logged_in ? $_SESSION['full_name'] : '';

// Debug info (remove in production)
// echo "<!-- Debug: logged_in = " . ($logged_in ? 'true' : 'false') . " -->";
// echo "<!-- Debug: user_name = " . $user_name . " -->";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Plan Management System - Smart, Healthy, Affordable</title>
    <style>
        :root {
            --primary: #27ae60;
            --primary-dark: #219653;
            --secondary: #2c3e50;
            --accent: #e67e22;
            --light: #f8f9fa;
            --gray: #6c757d;
            --white: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Roboto', sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: var(--secondary);
            line-height: 1.6;
        }
        
        /* Navigation */
        .navbar {
            background: var(--white);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }
        
        .logo i {
            font-size: 28px;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            cursor: pointer;
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        .auth-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn {
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-outline {
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            border: 2px solid var(--primary);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.1) 0%, rgba(33, 150, 83, 0.05) 100%);
            padding: 150px 20px 100px;
            text-align: center;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 48px;
            color: var(--secondary);
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .hero h1 span {
            color: var(--primary);
        }
        
        .hero p {
            font-size: 20px;
            color: var(--gray);
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-bottom: 50px;
            flex-wrap: wrap;
        }
        
        .hero-image {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        }
        
        .hero-image img {
            width: 100%;
            border-radius: 10px;
        }
        
        /* Features Section */
        .features {
            padding: 100px 20px;
            background: var(--white);
        }
        
        .section-title {
            text-align: center;
            font-size: 36px;
            color: var(--secondary);
            margin-bottom: 60px;
        }
        
        .section-title span {
            color: var(--primary);
        }
        
        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background: var(--light);
            padding: 40px 30px;
            border-radius: 15px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 2px solid transparent;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .feature-card:nth-child(1) {
            animation-delay: 0.1s;
        }
        
        .feature-card:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .feature-card:nth-child(3) {
            animation-delay: 0.3s;
        }
        
        .feature-card:nth-child(4) {
            animation-delay: 0.4s;
        }
        
        .feature-card:nth-child(5) {
            animation-delay: 0.5s;
        }
        
        .feature-card:nth-child(6) {
            animation-delay: 0.6s;
        }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: white;
            font-size: 32px;
            transition: transform 0.5s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: rotateY(360deg);
        }
        
        .feature-card h3 {
            font-size: 22px;
            color: var(--secondary);
            margin-bottom: 15px;
            transition: color 0.3s ease;
        }
        
        .feature-card:hover h3 {
            color: var(--primary);
        }
        
        .feature-card p {
            color: var(--gray);
            font-size: 16px;
        }
        
        /* How It Works */
        .how-it-works {
            padding: 100px 20px;
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.05) 0%, rgba(33, 150, 83, 0.02) 100%);
        }
        
        .steps {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 40px;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 30px;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .step-content h3 {
            font-size: 24px;
            color: var(--secondary);
            margin-bottom: 10px;
        }
        
        /* CTA Section */
        .cta {
            padding: 100px 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
        }
        
        .cta h2 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        
        .cta p {
            font-size: 18px;
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0.9;
        }
        
        .cta .btn {
            background: white;
            color: var(--primary);
            padding: 15px 40px;
            font-size: 18px;
        }
        
        .cta .btn:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        /* Footer */
        footer {
            background: var(--secondary);
            color: white;
            padding: 60px 20px 30px;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-column h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: var(--primary);
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 10px;
        }
        
        .footer-column ul li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-column ul li a:hover {
            color: white;
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
        }
        
        /* About Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray);
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: var(--primary);
        }
        
        .modal h2 {
            color: var(--primary);
            margin-bottom: 20px;
            font-size: 28px;
        }
        
        .modal h3 {
            color: var(--secondary);
            margin: 20px 0 10px;
        }
        
        .modal p {
            color: var(--gray);
            margin-bottom: 15px;
            line-height: 1.7;
        }
        
        .modal ul {
            list-style: none;
            margin-bottom: 20px;
        }
        
        .modal ul li {
            margin-bottom: 10px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal ul li i {
            color: var(--primary);
            width: 20px;
        }
        
        .modal .developer-info {
            background: var(--light);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid var(--primary);
        }
        
        .modal .developer-info i {
            color: var(--primary);
            margin-right: 10px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 36px;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .nav-links {
                display: none;
            }
            
            .step {
                flex-direction: column;
                text-align: center;
            }
            
            .section-title {
                font-size: 28px;
            }
            
            .auth-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .auth-buttons span {
                display: none;
            }
        }
        
        /* Welcome message for logged in users */
        .welcome-message {
            color: var(--primary);
            font-weight: 500;
            margin-right: 15px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-utensils"></i>
                NutriPlan KE
            </a>
            
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#how-it-works">How It Works</a>
                <a onclick="openAboutModal()">About</a>
                <?php if ($logged_in): ?>
                    <a href="access_dashboard.php">Dashboard</a>
                    <a href="logout.php">Logout</a>
                <?php endif; ?>
            </div>
            
            <div class="auth-buttons">
                <?php if ($logged_in): ?>
                    <span class="welcome-message">Welcome, <?php echo htmlspecialchars($user_name); ?>!</span>
                    <a href="access_dashboard.php" class="btn btn-primary">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">Log In</a>
                    <a href="signup.php" class="btn btn-primary">Sign Up Free</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Plan Smart. <span>Eat Healthy.</span> Save Money.</h1>
            <p>The ultimate meal planning solution for Kenyan households. Create balanced meal plans, manage your pantry, track your budget, and reduce food waste.</p>
            
            <div class="hero-buttons">
                <?php if ($logged_in): ?>
                    <a href="access_dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                <?php else: ?>
                    <a href="signup.php" class="btn btn-primary">Get Started Free</a>
                    <a href="login.php" class="btn btn-outline">Already have an account? Log In</a>
                <?php endif; ?>
            </div>
            
            <div class="hero-image">
                <!-- Placeholder for dashboard screenshot -->
                <div style="background: #f8f9fa; padding: 40px; border-radius: 10px; text-align: center;">
                    <i class="fas fa-chart-pie" style="font-size: 60px; color: var(--primary); margin-bottom: 20px;"></i>
                    <h3 style="color: var(--secondary); margin-bottom: 10px;">Dashboard Preview</h3>
                    <p style="color: var(--gray);">Manage your meal plans, pantry, and budget in one place</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <h2 class="section-title">Powerful <span>Features</span> for Better Meal Planning</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>Smart Meal Plans</h3>
                <p>Create personalized weekly meal plans based on your preferences, dietary needs, and available ingredients.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <h3>Pantry Management</h3>
                <p>Track what's in your kitchen, reduce food waste, and get suggestions based on items you already have.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <h3>Budget Tracking</h3>
                <p>Set food budgets, track spending, and get cost estimates for your meal plans in Kenyan Shillings.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>Shopping Lists</h3>
                <p>Automatically generate shopping lists based on your meal plans and pantry inventory.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <h3>Nutrition Tracking</h3>
                <p>Monitor calories, proteins, carbs, and fats to maintain a balanced diet with local Kenyan foods.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <h3>Local Recipes</h3>
                <p>Access a growing database of authentic Kenyan recipes with locally available ingredients.</p>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="how-it-works">
        <h2 class="section-title">How It <span>Works</span></h2>
        
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Sign Up & Set Preferences</h3>
                    <p>Create your free account and tell us about your dietary preferences, budget, and household size.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Manage Your Pantry</h3>
                    <p>Add the food items you already have at home. Our system will use these first when creating meal plans.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Generate Meal Plans</h3>
                    <p>Create personalized meal plans that consider your pantry items, budget, and nutritional goals.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3>Shop & Cook Smart</h3>
                    <p>Get shopping lists, cost estimates, and cooking instructions. Enjoy stress-free meal preparation!</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <h2>Ready to Transform Your Meal Planning?</h2>
        <p>Join thousands of Kenyan households who are saving time, money, and reducing food waste with our smart meal planning system.</p>
        
        <?php if ($logged_in): ?>
            <a href="access_dashboard.php" class="btn">Go to Dashboard</a>
        <?php else: ?>
            <a href="signup.php" class="btn">Start Free Trial</a>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer id="about">
        <div class="footer-content">
            <div class="footer-column">
                <h3>NutriPlan KE</h3>
                <p>Smart meal planning solutions for Kenyan households. Promoting healthy eating, budget management, and food waste reduction.</p>
            </div>
            
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a onclick="openAboutModal()" style="cursor: pointer;">About</a></li>
                    <?php if (!$logged_in): ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="signup.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Features</h3>
                <ul>
                    <li><a href="#">Meal Planning</a></li>
                    <li><a href="#">Pantry Management</a></li>
                    <li><a href="#">Budget Tracking</a></li>
                    <li><a href="#">Shopping Lists</a></li>
                    <li><a href="#">Nutrition Analysis</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Contact</h3>
                <ul>
                    <li><i class="fas fa-envelope"></i> support@mealplannerke.co.ke</li>
                    <li><i class="fas fa-phone"></i> +254 700 000 000</li>
                    <li><i class="fas fa-map-marker-alt"></i> Nairobi, Kenya</li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; 2026 Meal Plan Management System. All rights reserved.</p>
            <p>Developed by Selinah Sabuti Bukachi - Diploma in Graphics & Web Design</p>
        </div>
    </footer>

    <!-- About Modal -->
    <div id="aboutModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeAboutModal()">&times;</span>
            <h2><i class="fas fa-info-circle"></i> About NutriPlan KE</h2>
            
            <h3>Our Mission</h3>
            <p>To revolutionize meal planning for Kenyan households by providing an intelligent, user-friendly platform that promotes healthy eating, reduces food waste, and helps families save money on groceries.</p>
            
            <h3>What We Offer</h3>
            <ul>
                <li><i class="fas fa-check-circle"></i> Personalized meal plans based on your preferences</li>
                <li><i class="fas fa-check-circle"></i> Smart pantry management to reduce food waste</li>
                <li><i class="fas fa-check-circle"></i> Budget tracking in Kenyan Shillings</li>
                <li><i class="fas fa-check-circle"></i> Local Kenyan recipes with authentic ingredients</li>
                <li><i class="fas fa-check-circle"></i> Automated shopping lists generation</li>
                <li><i class="fas fa-check-circle"></i> Nutritional analysis for balanced diets</li>
            </ul>
            
            <h3>Why Choose Us</h3>
            <p>NutriPlan KE is specifically designed for the Kenyan market, taking into account local ingredients, cooking methods, and family sizes. Our system helps you save an average of 30% on grocery bills while ensuring your family eats healthy, balanced meals.</p>
            
            <div class="developer-info">
                <h3><i class="fas fa-user-circle"></i> About the Developer</h3>
                <p><strong>Selinah Sabuti Bukachi</strong> is a passionate web developer currently pursuing a Diploma in Graphics & Web Design. She created NutriPlan KE to solve the common challenge Kenyan families face in planning healthy meals while managing tight budgets. Her vision is to leverage technology to promote healthier eating habits and reduce food waste across Kenya.</p>
                <p style="margin-top: 10px;"><i class="fas fa-graduation-cap"></i> Diploma in Graphics & Web Design</p>
                <p><i class="fas fa-code"></i> Full Stack Web Developer</p>
            </div>
        </div>
    </div>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 70,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 5px 20px rgba(0, 0, 0, 0.1)';
            } else {
                navbar.style.boxShadow = '0 2px 15px rgba(0, 0, 0, 0.1)';
            }
        });
        
        // About Modal Functions
        function openAboutModal() {
            document.getElementById('aboutModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeAboutModal() {
            document.getElementById('aboutModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('aboutModal');
            if (event.target == modal) {
                closeAboutModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAboutModal();
            }
        });
    </script>
</body>
</html>