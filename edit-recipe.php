<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is admin
$user_id = $_SESSION['user_id'];
$admin_check = "SELECT is_admin FROM users WHERE user_id = $user_id";
$admin_result = mysqli_query($conn, $admin_check);
$user_data = mysqli_fetch_assoc($admin_result);

if (!$user_data || $user_data['is_admin'] != 1) {
    header("Location: dashboard.php");
    exit();
}

$recipe_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get recipe data
$recipe = null;
if ($recipe_id > 0) {
    $recipe_query = "SELECT * FROM recipes WHERE recipe_id = $recipe_id";
    $recipe_result = mysqli_query($conn, $recipe_query);
    $recipe = mysqli_fetch_assoc($recipe_result);
}

if (!$recipe) {
    header("Location: admin-dashboard.php");
    exit();
}

// Handle update
$message = '';
$message_type = '';

if (isset($_POST['update_recipe'])) {
    $recipe_name = mysqli_real_escape_string($conn, $_POST['recipe_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $instructions = mysqli_real_escape_string($conn, $_POST['instructions']);
    $prep_time = mysqli_real_escape_string($conn, $_POST['prep_time']);
    $serving_size = intval($_POST['serving_size']);
    $meal_type = mysqli_real_escape_string($conn, $_POST['meal_type']);
    $estimated_cost = floatval($_POST['estimated_cost']);
    $calories = intval($_POST['calories']);
    $protein = floatval($_POST['protein']);
    $carbs = floatval($_POST['carbs']);
    $fats = floatval($_POST['fats']);
    
    $sql = "UPDATE recipes SET 
            recipe_name = '$recipe_name',
            description = '$description',
            instructions = '$instructions',
            prep_time = '$prep_time',
            serving_size = $serving_size,
            meal_type = '$meal_type',
            estimated_cost = $estimated_cost,
            calories = $calories,
            protein = $protein,
            carbs = $carbs,
            fats = $fats
            WHERE recipe_id = $recipe_id";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Recipe updated successfully!";
        $message_type = 'success';
    } else {
        $message = "Error updating recipe: " . mysqli_error($conn);
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Recipe - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --admin-purple: #8e44ad;
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
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .header h1 {
            color: var(--admin-purple);
            display: flex;
            align-items: center;
            gap: 10px;
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
            background: var(--admin-purple);
            color: white;
        }
        
        .btn-outline {
            background: white;
            color: var(--admin-purple);
            border: 2px solid var(--admin-purple);
        }
        
        .recipe-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--admin-purple);
            box-shadow: 0 0 0 3px rgba(142, 68, 173, 0.1);
        }
        
        .form-full {
            grid-column: 1 / -1;
        }
        
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .recipe-form {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-edit"></i> Edit Recipe: <?php echo htmlspecialchars($recipe['recipe_name']); ?></h1>
            <a href="admin-dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="recipe-form">
                <div class="form-group">
                    <label for="recipe_name">Recipe Name *</label>
                    <input type="text" id="recipe_name" name="recipe_name" class="form-control" 
                           value="<?php echo htmlspecialchars($recipe['recipe_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="meal_type">Meal Type *</label>
                    <select id="meal_type" name="meal_type" class="form-control" required>
                        <option value="Breakfast" <?php echo $recipe['meal_type'] == 'Breakfast' ? 'selected' : ''; ?>>Breakfast</option>
                        <option value="Lunch" <?php echo $recipe['meal_type'] == 'Lunch' ? 'selected' : ''; ?>>Lunch</option>
                        <option value="Dinner" <?php echo $recipe['meal_type'] == 'Dinner' ? 'selected' : ''; ?>>Dinner</option>
                        <option value="Snack" <?php echo $recipe['meal_type'] == 'Snack' ? 'selected' : ''; ?>>Snack</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="prep_time">Prep Time</label>
                    <input type="text" id="prep_time" name="prep_time" class="form-control" 
                           value="<?php echo htmlspecialchars($recipe['prep_time']); ?>" placeholder="e.g., 30 minutes">
                </div>
                
                <div class="form-group">
                    <label for="serving_size">Serving Size</label>
                    <input type="number" id="serving_size" name="serving_size" class="form-control" 
                           value="<?php echo $recipe['serving_size'] ?: '4'; ?>" min="1">
                </div>
                
                <div class="form-group form-full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($recipe['description']); ?></textarea>
                </div>
                
                <div class="form-group form-full">
                    <label for="instructions">Instructions *</label>
                    <textarea id="instructions" name="instructions" class="form-control" rows="5" required><?php echo htmlspecialchars($recipe['instructions']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="estimated_cost">Estimated Cost (KES) *</label>
                    <input type="number" id="estimated_cost" name="estimated_cost" class="form-control" 
                           value="<?php echo $recipe['estimated_cost']; ?>" min="0" step="10" required>
                </div>
                
                <div class="form-group">
                    <label for="calories">Calories</label>
                    <input type="number" id="calories" name="calories" class="form-control" 
                           value="<?php echo $recipe['calories']; ?>" min="0">
                </div>
                
                <div class="form-group">
                    <label for="protein">Protein (g)</label>
                    <input type="number" id="protein" name="protein" class="form-control" 
                           value="<?php echo $recipe['protein']; ?>" min="0" step="0.1">
                </div>
                
                <div class="form-group">
                    <label for="carbs">Carbs (g)</label>
                    <input type="number" id="carbs" name="carbs" class="form-control" 
                           value="<?php echo $recipe['carbs']; ?>" min="0" step="0.1">
                </div>
                
                <div class="form-group">
                    <label for="fats">Fats (g)</label>
                    <input type="number" id="fats" name="fats" class="form-control" 
                           value="<?php echo $recipe['fats']; ?>" min="0" step="0.1">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="update_recipe" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Recipe
                </button>
                <a href="admin-dashboard.php" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>