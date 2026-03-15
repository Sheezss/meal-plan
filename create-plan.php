<?php
// create-plan.php - Personalized Pantry-Based Meal Plan Generator
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Handle form submission
$message = '';
$message_type = '';

// Check prerequisites
$has_pantry = false;
$has_preferences = false;
$has_budget = false;

// Get pantry items
$pantry_query = "SELECT * FROM pantry WHERE user_id = $user_id AND (expiry_date IS NULL OR expiry_date >= CURDATE())";
$pantry_result = mysqli_query($conn, $pantry_query);
$pantry_items = [];
while ($item = mysqli_fetch_assoc($pantry_result)) {
    $pantry_items[] = $item;
}
$has_pantry = count($pantry_items) > 0;

// Get user preferences
$preferences = [];
$pref_query = "SELECT * FROM user_preferences WHERE user_id = $user_id";
$pref_result = mysqli_query($conn, $pref_query);

if ($pref_result && mysqli_num_rows($pref_result) > 0) {
    $preferences = mysqli_fetch_assoc($pref_result);
    $has_preferences = true;
}

// Get current budget
$current_budget = [];
$budget_query = "SELECT * FROM user_budgets WHERE user_id = $user_id AND status = 'Active' ORDER BY created_at DESC LIMIT 1";
$budget_result = mysqli_query($conn, $budget_query);
if (mysqli_num_rows($budget_result) > 0) {
    $current_budget = mysqli_fetch_assoc($budget_result);
    $has_budget = true;
}

// Get all recipes with their ingredients from database
$recipes_query = "SELECT r.* FROM recipes r";
$recipes_result = mysqli_query($conn, $recipes_query);
$all_recipes = [];

while ($recipe = mysqli_fetch_assoc($recipes_result)) {
    // Get ingredients for this recipe
    $ing_query = "SELECT ri.*, fi.name as ingredient_name, fi.category 
                  FROM recipe_ingredients ri 
                  JOIN food_items fi ON ri.fooditem_id = fi.fooditem_id 
                  WHERE ri.recipe_id = {$recipe['recipe_id']}";
    $ing_result = mysqli_query($conn, $ing_query);
    $ingredients = [];
    
    while ($ing = mysqli_fetch_assoc($ing_result)) {
        $ingredients[] = [
            'name' => $ing['ingredient_name'],
            'quantity' => $ing['quantity'],
            'unit' => $ing['unit'],
            'category' => $ing['category'],
            'fooditem_id' => $ing['fooditem_id']
        ];
    }
    
    $recipe['ingredients'] = $ingredients;
    $all_recipes[] = $recipe;
}

// Helper Functions
function normalizeKenyanIngredient($name) {
    $name = strtolower(trim($name));
    
    $kenyan_synonyms = [
        'maize flour' => 'corn flour', 'corn flour' => 'maize flour',
        'cornmeal' => 'maize flour', 'posho' => 'maize flour',
        'sukuma wiki' => 'collard greens', 'collard greens' => 'sukuma wiki',
        'kale' => 'sukuma wiki', 'collards' => 'sukuma wiki',
        'tomato' => 'tomatoes', 'onion' => 'onions',
        'potato' => 'potatoes', 'carrot' => 'carrots',
        'irish potato' => 'potatoes', 'green banana' => 'matoke',
        'matoke' => 'green bananas',
        'beef' => 'nyama', 'nyama' => 'beef', 'mbuzi' => 'goat meat',
        'goat meat' => 'mbuzi', 'chicken' => 'kuku', 'kuku' => 'chicken',
        'mahindi' => 'maize', 'beans' => 'maharagwe', 'maharagwe' => 'beans',
        'rice' => 'mchele', 'mchele' => 'rice',
        'salt' => 'chumvi', 'chumvi' => 'salt', 'sugar' => 'sukari',
        'sukari' => 'sugar', 'oil' => 'mafuta', 'mafuta' => 'cooking oil',
        'cooking oil' => 'oil',
        'flour' => 'unga', 'unga' => 'flour', 'wheat flour' => 'unga wa ngano',
        'unga wa ngano' => 'wheat flour'
    ];
    
    return isset($kenyan_synonyms[$name]) ? $kenyan_synonyms[$name] : $name;
}

function checkKenyanPantry($ingredient_name, $pantry_items, $quantity_needed, $unit_needed) {
    $normalized_need = normalizeKenyanIngredient($ingredient_name);
    
    foreach ($pantry_items as $pantry_item) {
        $normalized_have = normalizeKenyanIngredient($pantry_item['item_name']);
        
        $similarity = 0;
        similar_text($normalized_need, $normalized_have, $similarity);
        
        if ($similarity > 70 || 
            strpos($normalized_have, $normalized_need) !== false || 
            strpos($normalized_need, $normalized_have) !== false) {
            
            $conversion_rate = 1;
            if (($unit_needed == 'kg' && $pantry_item['unit'] == 'g') || 
                ($unit_needed == 'g' && $pantry_item['unit'] == 'kg')) {
                $conversion_rate = 1000;
            } elseif (($unit_needed == 'litre' && $pantry_item['unit'] == 'ml') || 
                     ($unit_needed == 'ml' && $pantry_item['unit'] == 'litre')) {
                $conversion_rate = 1000;
            } elseif (($unit_needed == 'dozen' && $pantry_item['unit'] == 'piece') ||
                     ($unit_needed == 'piece' && $pantry_item['unit'] == 'dozen')) {
                $conversion_rate = 1/12;
            }
            
            $pantry_quantity_in_needed_units = $pantry_item['quantity'] * $conversion_rate;
            
            if ($pantry_quantity_in_needed_units > 0) {
                return [
                    'available' => true,
                    'quantity' => $pantry_quantity_in_needed_units,
                    'unit' => $pantry_item['unit'],
                    'pantry_id' => $pantry_item['pantry_id'],
                    'item_name' => $pantry_item['item_name']
                ];
            }
        }
    }
    
    return ['available' => false, 'quantity' => 0];
}

function calculateRecipePantryUsage($recipe, $pantry_items) {
    $pantry_used = [];
    $needed_from_store = [];
    $total_pantry_coverage = 0;
    
    foreach ($recipe['ingredients'] as $ingredient) {
        $pantry_check = checkKenyanPantry(
            $ingredient['name'], 
            $pantry_items, 
            $ingredient['quantity'], 
            $ingredient['unit']
        );
        
        if ($pantry_check['available']) {
            $usable_quantity = min($pantry_check['quantity'], $ingredient['quantity']);
            $pantry_coverage = ($usable_quantity / $ingredient['quantity']) * 100;
            $total_pantry_coverage += $pantry_coverage;
            
            $pantry_used[] = [
                'item' => $ingredient['name'],
                'pantry_item' => $pantry_check['item_name'],
                'quantity_used' => $usable_quantity,
                'unit' => $ingredient['unit'],
                'pantry_id' => $pantry_check['pantry_id'],
                'coverage' => $pantry_coverage
            ];
            
            if ($usable_quantity < $ingredient['quantity']) {
                $additional_needed = $ingredient['quantity'] - $usable_quantity;
                $needed_from_store[] = [
                    'item' => $ingredient['name'],
                    'quantity' => $additional_needed,
                    'unit' => $ingredient['unit'],
                    'estimated_cost' => $additional_needed * 50
                ];
            }
        } else {
            $needed_from_store[] = [
                'item' => $ingredient['name'],
                'quantity' => $ingredient['quantity'],
                'unit' => $ingredient['unit'],
                'estimated_cost' => $ingredient['quantity'] * 50
            ];
        }
    }
    
    $average_coverage = count($recipe['ingredients']) > 0 ? 
                       $total_pantry_coverage / count($recipe['ingredients']) : 0;
    
    return [
        'pantry_used' => $pantry_used,
        'needed_from_store' => $needed_from_store,
        'pantry_coverage' => $average_coverage,
        'store_cost' => array_sum(array_column($needed_from_store, 'estimated_cost'))
    ];
}

// Function to generate personalized meal plan with pantry priority
function generatePantryBasedMealPlan($pantry_items, $preferences, $budget, $all_recipes) {
    $meal_plan = [
        'days' => [],
        'total_cost' => 0,
        'total_pantry_coverage' => 0,
        'shopping_list' => [],
        'pantry_items_used' => [],
        'shopping_cost' => 0,
        'budget_remaining' => 0,
        'meals_per_day_setting' => 0,
        'total_calories' => 0,
        'total_protein' => 0,
        'total_carbs' => 0,
        'total_fats' => 0
    ];
    
    $diet_type = $preferences['diet_type'] ?? 'Balanced';
    $meals_per_day = $preferences['meals_per_day'] ?? 3;
    $budget_amount = $budget['amount'] ?? 3000;
    $meal_plan['meals_per_day_setting'] = $meals_per_day;
    
    $include_breakfast = $preferences['pref_breakfast'] ?? 1;
    $include_lunch = $preferences['pref_lunch'] ?? 1;
    $include_dinner = $preferences['pref_dinner'] ?? 1;
    $include_snacks = $preferences['pref_snacks'] ?? 0;
    
    $is_vegetarian = $preferences['vegetarian'] ?? 0;
    $is_vegan = $preferences['vegan'] ?? 0;
    $avoid_pork = $preferences['avoid_pork'] ?? 0;
    $avoid_beef = $preferences['avoid_beef'] ?? 0;
    $avoid_fish = $preferences['avoid_fish'] ?? 0;
    $avoid_dairy = $preferences['avoid_dairy'] ?? 0;
    $avoid_eggs = $preferences['avoid_eggs'] ?? 0;
    
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $daily_budget = $budget_amount / 7;
    
    $filtered_recipes = array_filter($all_recipes, function($recipe) use ($diet_type, $is_vegetarian, $is_vegan, $avoid_pork, $avoid_beef, $avoid_fish, $avoid_dairy, $avoid_eggs) {
        $recipe_name = strtolower($recipe['recipe_name']);
        
        if ($is_vegetarian || $is_vegan) {
            $meat_keywords = ['beef', 'chicken', 'fish', 'meat', 'nyama', 'mbuzi', 'kuku'];
            foreach ($meat_keywords as $meat) {
                if (strpos($recipe_name, $meat) !== false) {
                    return false;
                }
            }
        }
        
        if ($is_vegan) {
            $vegan_forbidden = ['dairy', 'milk', 'cheese', 'yogurt', 'cream', 'butter', 'egg'];
            foreach ($vegan_forbidden as $item) {
                if (strpos($recipe_name, $item) !== false) {
                    return false;
                }
            }
        }
        
        if ($avoid_pork && strpos($recipe_name, 'pork') !== false) return false;
        if ($avoid_beef && strpos($recipe_name, 'beef') !== false) return false;
        if ($avoid_fish && strpos($recipe_name, 'fish') !== false) return false;
        
        return true;
    });
    
    $filtered_recipes = array_values($filtered_recipes);
    
    if (empty($filtered_recipes)) {
        $filtered_recipes = $all_recipes;
    }
    
    $recipes_with_coverage = [];
    foreach ($filtered_recipes as $recipe) {
        $usage_data = calculateRecipePantryUsage($recipe, $pantry_items);
        
        $recipes_with_coverage[] = [
            'recipe' => $recipe,
            'pantry_coverage' => $usage_data['pantry_coverage'],
            'store_cost' => $usage_data['store_cost'],
            'pantry_used' => $usage_data['pantry_used'],
            'needed_from_store' => $usage_data['needed_from_store'],
            'total_cost' => $recipe['estimated_cost'],
            'calories' => $recipe['calories'] ?? 0,
            'protein' => $recipe['protein'] ?? 0,
            'carbs' => $recipe['carbs'] ?? 0,
            'fats' => $recipe['fats'] ?? 0
        ];
    }
    
    usort($recipes_with_coverage, function($a, $b) {
        if ($a['pantry_coverage'] == $b['pantry_coverage']) {
            return $a['store_cost'] <=> $b['store_cost'];
        }
        return $b['pantry_coverage'] <=> $a['pantry_coverage'];
    });
    
    $recipes_by_meal = ['Breakfast' => [], 'Lunch' => [], 'Dinner' => [], 'Snack' => []];
    foreach ($recipes_with_coverage as $recipe_data) {
        $meal_type = $recipe_data['recipe']['meal_type'];
        if (isset($recipes_by_meal[$meal_type])) {
            $recipes_by_meal[$meal_type][] = $recipe_data;
        }
    }
    
    $meal_types_to_include = [];
    if ($include_breakfast) $meal_types_to_include[] = 'Breakfast';
    if ($include_lunch) $meal_types_to_include[] = 'Lunch';
    if ($include_dinner) $meal_types_to_include[] = 'Dinner';
    if ($include_snacks) $meal_types_to_include[] = 'Snack';
    
    if (empty($meal_types_to_include)) {
        if ($meals_per_day == 1) $meal_types_to_include = ['Dinner'];
        elseif ($meals_per_day == 2) $meal_types_to_include = ['Breakfast', 'Dinner'];
        elseif ($meals_per_day == 3) $meal_types_to_include = ['Breakfast', 'Lunch', 'Dinner'];
        else $meal_types_to_include = ['Breakfast', 'Lunch', 'Dinner', 'Snack'];
    }
    
    $used_recipe_ids = [];
    $aggregated_shopping_list = [];
    $aggregated_pantry_used = [];
    
    foreach ($days as $day) {
        $day_meals = [];
        $day_cost = 0;
        $day_pantry_coverage = 0;
        $meals_added = 0;
        $day_calories = 0;
        $day_protein = 0;
        $day_carbs = 0;
        $day_fats = 0;
        
        foreach ($meal_types_to_include as $meal_type) {
            if ($meals_added >= $meals_per_day) break;
            
            if (!empty($recipes_by_meal[$meal_type])) {
                foreach ($recipes_by_meal[$meal_type] as $recipe_data) {
                    if (!in_array($recipe_data['recipe']['recipe_id'], $used_recipe_ids)) {
                        $potential_day_cost = $day_cost + $recipe_data['store_cost'];
                        
                        if ($potential_day_cost <= $daily_budget * 1.3) {
                            $day_meals[] = [
                                'data' => $recipe_data,
                                'meal_type' => $meal_type
                            ];
                            $day_cost += $recipe_data['store_cost'];
                            $day_pantry_coverage += $recipe_data['pantry_coverage'];
                            $day_calories += $recipe_data['calories'];
                            $day_protein += $recipe_data['protein'];
                            $day_carbs += $recipe_data['carbs'];
                            $day_fats += $recipe_data['fats'];
                            $used_recipe_ids[] = $recipe_data['recipe']['recipe_id'];
                            $meals_added++;
                            
                            foreach ($recipe_data['needed_from_store'] as $item) {
                                $found = false;
                                foreach ($aggregated_shopping_list as &$existing) {
                                    if ($existing['item'] == $item['item']) {
                                        $existing['quantity'] += $item['quantity'];
                                        $existing['estimated_cost'] += $item['estimated_cost'];
                                        $found = true;
                                        break;
                                    }
                                }
                                if (!$found) {
                                    $aggregated_shopping_list[] = $item;
                                }
                            }
                            
                            foreach ($recipe_data['pantry_used'] as $used) {
                                $aggregated_pantry_used[] = $used;
                            }
                            
                            break;
                        }
                    }
                }
            }
        }
        
        while ($meals_added < $meals_per_day) {
            $found_meal = false;
            
            foreach ($recipes_with_coverage as $recipe_data) {
                if (!in_array($recipe_data['recipe']['recipe_id'], $used_recipe_ids)) {
                    $potential_day_cost = $day_cost + $recipe_data['store_cost'];
                    
                    if ($potential_day_cost <= $daily_budget * 1.3) {
                        $meal_type = $recipe_data['recipe']['meal_type'];
                        if (!in_array($meal_type, $meal_types_to_include)) {
                            if ($meals_added == 0 && in_array('Breakfast', $meal_types_to_include)) {
                                $meal_type = 'Breakfast';
                            } elseif ($meals_added == 1 && in_array('Lunch', $meal_types_to_include)) {
                                $meal_type = 'Lunch';
                            } else {
                                $meal_type = 'Dinner';
                            }
                        }
                        
                        $day_meals[] = [
                            'data' => $recipe_data,
                            'meal_type' => $meal_type
                        ];
                        $day_cost += $recipe_data['store_cost'];
                        $day_pantry_coverage += $recipe_data['pantry_coverage'];
                        $day_calories += $recipe_data['calories'];
                        $day_protein += $recipe_data['protein'];
                        $day_carbs += $recipe_data['carbs'];
                        $day_fats += $recipe_data['fats'];
                        $used_recipe_ids[] = $recipe_data['recipe']['recipe_id'];
                        $meals_added++;
                        $found_meal = true;
                        
                        foreach ($recipe_data['needed_from_store'] as $item) {
                            $found = false;
                            foreach ($aggregated_shopping_list as &$existing) {
                                if ($existing['item'] == $item['item']) {
                                    $existing['quantity'] += $item['quantity'];
                                    $existing['estimated_cost'] += $item['estimated_cost'];
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $aggregated_shopping_list[] = $item;
                            }
                        }
                        
                        foreach ($recipe_data['pantry_used'] as $used) {
                            $aggregated_pantry_used[] = $used;
                        }
                        
                        break;
                    }
                }
            }
            
            if (!$found_meal) break;
        }
        
        $average_day_coverage = $meals_added > 0 ? 
                               $day_pantry_coverage / $meals_added : 0;
        
        $meal_plan['days'][$day] = [
            'meals' => $day_meals,
            'meals_count' => $meals_added,
            'cost' => $day_cost,
            'pantry_coverage' => $average_day_coverage,
            'budget_status' => $day_cost <= $daily_budget ? 'Within Budget' : 'Over Budget',
            'calories' => $day_calories,
            'protein' => $day_protein,
            'carbs' => $day_carbs,
            'fats' => $day_fats
        ];
        
        $meal_plan['total_cost'] += $day_cost;
        $meal_plan['total_pantry_coverage'] += $average_day_coverage;
        $meal_plan['total_calories'] += $day_calories;
        $meal_plan['total_protein'] += $day_protein;
        $meal_plan['total_carbs'] += $day_carbs;
        $meal_plan['total_fats'] += $day_fats;
    }
    
    $meal_plan['total_pantry_coverage'] = count($days) > 0 ? 
                                         $meal_plan['total_pantry_coverage'] / count($days) : 0;
    $meal_plan['shopping_list'] = $aggregated_shopping_list;
    $meal_plan['pantry_items_used'] = $aggregated_pantry_used;
    $meal_plan['shopping_cost'] = array_sum(array_column($aggregated_shopping_list, 'estimated_cost'));
    $meal_plan['budget_remaining'] = $budget_amount - $meal_plan['total_cost'] - $meal_plan['shopping_cost'];
    
    return $meal_plan;
}

// Generate meal plan
$generated_plan = null;
if (isset($_POST['generate_plan'])) {
    if ($has_pantry && $has_preferences && $has_budget) {
        $generated_plan = generatePantryBasedMealPlan($pantry_items, $preferences, $current_budget, $all_recipes);
        
        // Store in session so it persists after form submission
        $_SESSION['generated_plan'] = $generated_plan;
        
        $message = "Personalized meal plan generated based on your pantry items!";
        $message_type = 'success';
        
        $track_query = "INSERT INTO user_activity (user_id, activity_type, activity_details) 
                        VALUES ($user_id, 'plan_generated', 'Generated pantry-based meal plan')";
        mysqli_query($conn, $track_query);
        
    } else {
        $missing = [];
        if (!$has_pantry) $missing[] = "Pantry Setup";
        if (!$has_preferences) $missing[] = "Preferences";
        if (!$has_budget) $missing[] = "Budget";
        
        $message = "Please complete the following steps first: " . implode(", ", $missing);
        $message_type = 'error';
    }
}

// Retrieve generated plan from session if it exists
if (isset($_SESSION['generated_plan']) && empty($generated_plan)) {
    $generated_plan = $_SESSION['generated_plan'];
}

// Save meal plan
if (isset($_POST['save_plan']) && !empty($generated_plan)) {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        $plan_name = "Pantry-Based Plan - " . date('d M Y H:i:s');
        $total_cost = $generated_plan['total_cost'] + $generated_plan['shopping_cost'];
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+6 days'));
        
        // Insert meal plan with all nutrition data
        $sql = "INSERT INTO meal_plans (user_id, name, start_date, end_date, total_cost, total_calories, total_protein, total_carbs, total_fats, created_at) 
                VALUES ($user_id, '$plan_name', '$start_date', '$end_date', $total_cost, {$generated_plan['total_calories']}, {$generated_plan['total_protein']}, {$generated_plan['total_carbs']}, {$generated_plan['total_fats']}, NOW())";
        
        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Error inserting meal plan: " . mysqli_error($conn));
        }
        
        $plan_id = mysqli_insert_id($conn);
        
        // Save meals for each day
        $meals_saved = 0;
        $days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $current_monday = date('Y-m-d', strtotime('monday this week'));
        
        foreach ($generated_plan['days'] as $index => $day_data) {
            $scheduled_date = date('Y-m-d', strtotime($current_monday . ' + ' . $index . ' days'));
            
            foreach ($day_data['meals'] as $meal_entry) {
                $meal_data = $meal_entry['data'];
                $meal_type = $meal_entry['meal_type'];
                $recipe_id = $meal_data['recipe']['recipe_id'];
                $meal_name = mysqli_real_escape_string($conn, $meal_data['recipe']['recipe_name']);
                
                $meal_sql = "INSERT INTO meals (mealplan_id, recipe_id, meal_name, meal_type, scheduled_date, created_at) 
                            VALUES ($plan_id, $recipe_id, '$meal_name', '$meal_type', '$scheduled_date', NOW())";
                
                if (!mysqli_query($conn, $meal_sql)) {
                    throw new Exception("Error inserting meal: " . mysqli_error($conn));
                }
                $meals_saved++;
            }
        }
        
        // Insert into mealplan_nutrition table
        $nutrition_sql = "INSERT INTO mealplan_nutrition (mealplan_id, user_id, total_calories, total_protein, total_carbs, total_fats, total_cost, calculation_date, created_at) 
                         VALUES ($plan_id, $user_id, {$generated_plan['total_calories']}, {$generated_plan['total_protein']}, {$generated_plan['total_carbs']}, {$generated_plan['total_fats']}, $total_cost, CURDATE(), NOW())";
        
        if (!mysqli_query($conn, $nutrition_sql)) {
            throw new Exception("Error inserting nutrition summary: " . mysqli_error($conn));
        }
        
        // Save shopping list items
        $shopping_items_saved = 0;
        
        foreach ($generated_plan['shopping_list'] as $item) {
            $item_name = mysqli_real_escape_string($conn, $item['item']);
            $quantity = floatval($item['quantity']);
            $unit = mysqli_real_escape_string($conn, $item['unit']);
            
            // Try to find existing food item
            $food_query = "SELECT fooditem_id FROM food_items WHERE LOWER(name) = LOWER('$item_name') LIMIT 1";
            $food_result = mysqli_query($conn, $food_query);
            
            if ($food_result && mysqli_num_rows($food_result) > 0) {
                $food_row = mysqli_fetch_assoc($food_result);
                $fooditem_id = $food_row['fooditem_id'];
            } else {
                // Insert new food item
                $insert_food = "INSERT INTO food_items (name, category, unit, created_at) 
                               VALUES ('$item_name', 'Other', '$unit', NOW())";
                if (mysqli_query($conn, $insert_food)) {
                    $fooditem_id = mysqli_insert_id($conn);
                } else {
                    $fooditem_id = 1;
                }
            }
            
            // Insert into shopping list
            $shop_sql = "INSERT INTO shopping_lists (mealplan_id, fooditem_id, quantity_needed, unit, status, created_at) 
                        VALUES ($plan_id, $fooditem_id, $quantity, '$unit', 'Pending', NOW())";
            
            if (mysqli_query($conn, $shop_sql)) {
                $shopping_items_saved++;
            }
        }
        
        // Update budget spent if there's an active budget
        $budget_update = "UPDATE user_budgets SET current_spending = current_spending + $total_cost 
                         WHERE user_id = $user_id AND status = 'Active' 
                         AND start_date <= CURDATE() AND end_date >= CURDATE()";
        mysqli_query($conn, $budget_update);
        
        // Track activity
        $track_query = "INSERT INTO user_activity (user_id, activity_type, activity_details) 
                        VALUES ($user_id, 'plan_generated', 'Generated and saved pantry-based meal plan with $meals_saved meals')";
        mysqli_query($conn, $track_query);
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Clear the session
        unset($_SESSION['generated_plan']);
        
        // Redirect to meal_plan.php with success message
        header("Location: meal_plan.php?success=1&plan_id=$plan_id");
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = "Error saving plan: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle check again request
if (isset($_POST['check_again'])) {
    header("Location: create-plan.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Meal Plan - Meal Plan System</title>
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
            --warning-yellow: #f39c12;
            --success-blue: #3498db;
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
            background: var(--warning-yellow);
            color: white;
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        .btn-lg {
            padding: 15px 40px;
            font-size: 16px;
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
        
        .progress-container-large {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .progress-bar {
            height: 10px;
            background: var(--border-color);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-green), var(--secondary-green));
            border-radius: 5px;
            transition: width 0.5s ease-in-out;
        }
        
        .requirements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .requirement-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            border: 2px solid var(--border-color);
            text-align: center;
            transition: all 0.3s;
        }
        
        .requirement-card.completed {
            border-color: var(--primary-green);
            background: var(--light-green);
        }
        
        .requirement-card.incomplete {
            border-color: var(--accent-red);
            background: #fff5f5;
        }
        
        .requirement-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 24px;
        }
        
        .requirement-icon.completed {
            background: var(--primary-green);
        }
        
        .requirement-icon.incomplete {
            background: var(--accent-red);
        }
        
        .pantry-highlight {
            background: linear-gradient(135deg, #e8f8f1, #d5f4e6);
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            border: 2px solid #27ae60;
        }
        
        .coverage-indicator {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .coverage-high {
            background: #d4edda;
            color: #155724;
            border: 1px solid #28a745;
        }
        
        .coverage-medium {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }
        
        .coverage-low {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #dc3545;
        }
        
        .ingredient-source {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 8px;
        }
        
        .source-pantry {
            background: #d4edda;
            color: #155724;
        }
        
        .source-store {
            background: #fff3cd;
            color: #856404;
        }
        
        .pantry-items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .pantry-item-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e1f5e1;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .usage-badge {
            display: inline-block;
            background: #27ae60;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            margin-top: 5px;
        }
        
        .meal-plan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .day-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            border: 2px solid var(--border-color);
            transition: all 0.3s;
        }
        
        .day-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .day-name {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark-green);
        }
        
        .day-cost {
            background: var(--light-green);
            color: var(--primary-green);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .meals-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .meal-item {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 15px;
            border-left: 4px solid;
        }
        
        .meal-item.breakfast {
            border-left-color: #f39c12;
        }
        
        .meal-item.lunch {
            border-left-color: #2ecc71;
        }
        
        .meal-item.dinner {
            border-left-color: #3498db;
        }
        
        .meal-item.snack {
            border-left-color: #9b59b6;
        }
        
        .meal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .meal-name {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 16px;
        }
        
        .meal-type {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 3px;
        }
        
        .meal-cost {
            color: var(--primary-green);
            font-weight: 600;
        }
        
        .ingredients-list {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed var(--border-color);
        }
        
        .ingredient-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            padding: 4px 0;
            color: var(--text-light);
        }
        
        .ingredient-source {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
        }
        
        .source-pantry {
            background: #d4edda;
            color: #155724;
        }
        
        .source-store {
            background: #fff3cd;
            color: #856404;
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
            
            .meal-plan-grid {
                grid-template-columns: 1fr;
            }
            
            .requirements-grid {
                grid-template-columns: 1fr;
            }
        }
        /* Mobile Responsive Styles */
@media screen and (max-width: 768px) {
    .dashboard-container {
        flex-direction: column;
    }
    
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding: 15px;
    }
    
    .main-content {
        margin-left: 0;
        padding: 15px;
    }
    
    .top-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .welcome-message h1 {
        font-size: 24px;
    }
    
    .welcome-message p {
        font-size: 14px;
    }
    
    .header-actions {
        width: 100%;
        flex-direction: column;
    }
    
    .header-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .requirements-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .requirement-card {
        padding: 20px;
    }
    
    .progress-container-large {
        padding: 15px;
    }
    
    .progress-header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .content-section {
        padding: 20px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .section-header h2 {
        font-size: 20px;
    }
    
    .section-header .btn {
        width: 100%;
    }
    
    .meal-plan-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .day-card {
        padding: 20px;
    }
    
    .day-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .day-name {
        font-size: 18px;
    }
    
    .day-header div {
        width: 100%;
        justify-content: space-between;
    }
    
    .coverage-indicator {
        margin-left: 0;
    }
    
    .meal-item {
        padding: 12px;
    }
    
    .meal-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .meal-name {
        font-size: 15px;
    }
    
    .ingredients-list {
        margin-top: 8px;
    }
    
    .ingredient-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .ingredient-source {
        align-self: flex-start;
    }
    
    .pantry-items-grid {
        grid-template-columns: 1fr;
    }
    
    .pantry-item-card {
        padding: 12px;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .stat-value {
        font-size: 20px;
    }
    
    .stat-label {
        font-size: 12px;
    }
    
    .btn-lg {
        width: 100%;
        padding: 12px 20px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-control {
        padding: 10px 12px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
}

/* Small phones */
@media screen and (max-width: 480px) {
    .user-avatar {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .user-welcome h3 {
        font-size: 18px;
    }
    
    .user-welcome p {
        font-size: 12px;
    }
    
    .logo-text h2 {
        font-size: 18px;
    }
    
    .logo-text p {
        font-size: 10px;
    }
    
    .nav-menu a {
        padding: 10px 12px;
        font-size: 13px;
    }
    
    .nav-menu i {
        font-size: 16px;
    }
    
    .welcome-message h1 {
        font-size: 20px;
    }
    
    .welcome-message p {
        font-size: 12px;
    }
    
    .progress-header span {
        font-size: 12px;
    }
    
    .requirement-card h4 {
        font-size: 16px;
    }
    
    .requirement-card p {
        font-size: 12px;
    }
    
    .coverage-indicator {
        font-size: 10px;
        padding: 3px 8px;
    }
    
    .day-cost {
        font-size: 12px;
        padding: 3px 10px;
    }
    
    .meal-cost {
        font-size: 12px;
    }
    
    .meal-type {
        font-size: 11px;
    }
    
    .ingredient-item {
        font-size: 11px;
    }
    
    .btn {
        padding: 8px 15px;
        font-size: 12px;
    }
    
    .btn-sm {
        padding: 5px 10px;
        font-size: 11px;
    }
    
    .modal-content {
        width: 95%;
        padding: 20px;
        margin: 10px;
    }
    
    .modal-header h3 {
        font-size: 18px;
    }
}

/* Landscape mode */
@media screen and (min-width: 769px) and (max-width: 1024px) {
    .sidebar {
        width: 240px;
    }
    
    .main-content {
        margin-left: 240px;
    }
    
    .meal-plan-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .requirements-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Touch-friendly improvements */
@media (hover: none) and (pointer: coarse) {
    .nav-menu a,
    .btn,
    .requirement-card,
    .day-card,
    .meal-item,
    .pantry-item-card {
        cursor: default;
    }
    
    .nav-menu a:active,
    .btn:active {
        transform: scale(0.98);
    }
    
    input, 
    select, 
    textarea,
    button {
        font-size: 16px !important; /* Prevents zoom on iOS */
    }
}

/* Fix for notch phones */
@supports (padding: max(0px)) {
    .sidebar,
    .main-content {
        padding-left: max(15px, env(safe-area-inset-left));
        padding-right: max(15px, env(safe-area-inset-right));
    }
}

/* Better scrolling on mobile */
.sidebar,
.main-content,
.shopping-items,
.meal-plan-grid {
    -webkit-overflow-scrolling: touch;
}

/* Hide scrollbar but keep functionality on mobile */
@media screen and (max-width: 768px) {
    .sidebar::-webkit-scrollbar,
    .shopping-items::-webkit-scrollbar {
        width: 3px;
    }
    
    .sidebar::-webkit-scrollbar-thumb,
    .shopping-items::-webkit-scrollbar-thumb {
        background: var(--primary-green);
        border-radius: 3px;
    }
}

/* Loading states for mobile */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Improved tap targets */
.nav-menu a,
.btn,
.requirement-card,
.day-card,
.meal-item {
    min-height: 44px; /* Apple's recommended minimum touch target size */
}

/* Fix for modals on mobile */
.modal {
    padding: 10px;
    align-items: flex-end;
}

@media screen and (max-width: 768px) {
    .modal-content {
        max-height: 90vh;
        border-radius: 15px 15px 0 0;
        animation: slideUp 0.3s ease-out;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

/* Fix for tables on mobile */
.nutrition-table {
    display: block;
    overflow-x: auto;
    white-space: nowrap;
}

.nutrition-table th,
.nutrition-table td {
    min-width: 120px;
}

/* Fix for shopping list on mobile */
.shopping-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
}

.item-info {
    width: 100%;
    flex-wrap: wrap;
}

.item-actions {
    width: 100%;
    justify-content: flex-end;
}

/* Fix for stats grid on mobile */
.stats-grid {
    grid-template-columns: 1fr;
}

.stat-card {
    flex-direction: row;
    text-align: left;
    align-items: center;
}

.stat-icon {
    margin-right: 15px;
    margin-bottom: 0;
}

.stat-info {
    flex: 1;
}

.stat-info h3 {
    font-size: 20px;
    margin-bottom: 2px;
}

.stat-info p {
    font-size: 13px;
}

/* Fix for progress bars on mobile */
.progress-container {
    margin-bottom: 20px;
}

.progress-label {
    flex-direction: column;
    gap: 5px;
    align-items: flex-start;
}

.progress-label span:last-child {
    align-self: flex-end;
}
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
</head>
<body>
    <div class="dashboard-container">
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
                <p>Generate your meal plan</p>
            </div>
            
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="meal_plan.php"><i class="fas fa-calendar-alt"></i> My Meal Plans</a></li>
                <li><a href="pantry.php"><i class="fas fa-utensils"></i> My Pantry</a></li>
                <li><a href="recipes.php"><i class="fas fa-book"></i> Kenyan Recipes</a></li>
                <li><a href="shopping-list.php"><i class="fas fa-shopping-cart"></i> Shopping List</a></li>
                <li><a href="budget.php"><i class="fas fa-wallet"></i> Budget Tracker</a></li>
                <li><a href="preferences.php"><i class="fas fa-sliders-h"></i> My Preferences</a></li>
                <li><a href="create-plan.php" class="active"><i class="fas fa-magic"></i> Generate Plan</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="top-header">
                <div class="welcome-message">
                    <h1>Generate Your Meal Plan</h1>
                    <p>Personalized meals based on your pantry, preferences, and budget</p>
                </div>
                <div class="header-actions">
                    <a href="shopping-list.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i> View Shopping List
                    </a>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <div class="progress-container-large">
                <div class="progress-header">
                    <span><strong>Setup Progress</strong></span>
                    <span>Step 4 of 4</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 100%"></div>
                </div>
                <p style="color: var(--text-light); font-size: 14px; margin-top: 10px;">
                    Pantry Setup ✓ → Preferences ✓ → Budget ✓ → Generate Plan
                </p>
            </div>
            
            <div class="content-section">
                <h3>Setup Requirements</h3>
                <p style="color: var(--text-light); margin-bottom: 20px;">
                    Complete these steps to generate your personalized meal plan
                </p>
                
                <div class="requirements-grid">
                    <div class="requirement-card <?php echo $has_pantry ? 'completed' : 'incomplete'; ?>">
                        <div class="requirement-icon <?php echo $has_pantry ? 'completed' : 'incomplete'; ?>">
                            <i class="fas fa-<?php echo $has_pantry ? 'check' : 'times'; ?>"></i>
                        </div>
                        <h4>Pantry Setup</h4>
                        <p>Add items to your pantry</p>
                        <div class="step-status">
                            <?php if ($has_pantry): ?>
                                <span class="badge badge-success">✓ Completed</span>
                                <p style="font-size: 12px; color: var(--primary-green); margin-top: 5px;">
                                    <?php echo count($pantry_items); ?> items available
                                </p>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php endif; ?>
                        </div>
                        <a href="pantry.php" class="btn btn-sm btn-outline" style="margin-top: 15px;">
                            <?php echo $has_pantry ? 'View Pantry' : 'Setup Pantry'; ?>
                        </a>
                    </div>
                    
                    <div class="requirement-card <?php echo $has_preferences ? 'completed' : 'incomplete'; ?>">
                        <div class="requirement-icon <?php echo $has_preferences ? 'completed' : 'incomplete'; ?>">
                            <i class="fas fa-<?php echo $has_preferences ? 'check' : 'times'; ?>"></i>
                        </div>
                        <h4>Preferences</h4>
                        <p>Set dietary preferences</p>
                        <div class="step-status">
                            <?php if ($has_preferences): ?>
                                <span class="badge badge-success">✓ Completed</span>
                                <?php if (!empty($preferences['diet_type'])): ?>
                                    <p style="font-size: 12px; color: var(--primary-green); margin-top: 5px;">
                                        <?php echo htmlspecialchars($preferences['diet_type']); ?>
                                    </p>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php endif; ?>
                        </div>
                        <a href="preferences.php" class="btn btn-sm <?php echo $has_preferences ? 'btn-outline' : 'btn-primary'; ?>" style="margin-top: 15px;">
                            <?php echo $has_preferences ? 'View Preferences' : 'Set Preferences Now'; ?>
                        </a>
                    </div>
                    
                    <div class="requirement-card <?php echo $has_budget ? 'completed' : 'incomplete'; ?>">
                        <div class="requirement-icon <?php echo $has_budget ? 'completed' : 'incomplete'; ?>">
                            <i class="fas fa-<?php echo $has_budget ? 'check' : 'times'; ?>"></i>
                        </div>
                        <h4>Budget</h4>
                        <p>Set your food budget</p>
                        <div class="step-status">
                            <?php if ($has_budget): ?>
                                <span class="badge badge-success">✓ Completed</span>
                                <?php if (!empty($current_budget['amount'])): ?>
                                    <p style="font-size: 12px; color: var(--primary-green); margin-top: 5px;">
                                        KES <?php echo number_format($current_budget['amount'], 0); ?>
                                    </p>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php endif; ?>
                        </div>
                        <a href="budget.php" class="btn btn-sm btn-outline" style="margin-top: 15px;">
                            <?php echo $has_budget ? 'View Budget' : 'Set Budget'; ?>
                        </a>
                    </div>
                </div>
                
                <?php if ($has_pantry && $has_preferences && $has_budget): ?>
                    <form method="POST" action="" style="text-align: center; margin-top: 40px;">
                        <button type="submit" name="generate_plan" class="btn btn-primary btn-lg">
                            <i class="fas fa-magic"></i> Generate Personalized Meal Plan
                        </button>
                        <p style="color: var(--text-light); font-size: 14px; margin-top: 15px;">
                            Based on <?php echo count($pantry_items); ?> pantry items, <?php echo htmlspecialchars($preferences['diet_type'] ?? 'Balanced'); ?> diet, 
                            and KES <?php echo number_format($current_budget['amount'] ?? 0, 0); ?> budget
                        </p>
                    </form>
                <?php else: ?>
                    <div style="text-align: center; margin-top: 40px; padding: 30px; background: var(--light-bg); border-radius: 10px;">
                        <i class="fas fa-info-circle" style="font-size: 40px; color: var(--accent-orange); margin-bottom: 15px;"></i>
                        <h4 style="color: var(--text-dark); margin-bottom: 10px;">Complete All Requirements</h4>
                        <p style="color: var(--text-light);">
                            Please complete all three setup steps above to generate your personalized meal plan
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($generated_plan)): ?>
                <!-- Generated Plan Display Section -->
                <div class="content-section" style="background: linear-gradient(135deg, #e8f8f1, #d5f4e6);">
                    <h3 style="color: var(--dark-green); margin-bottom: 15px;">
                        <i class="fas fa-user-cog"></i> Your Meal Plan Settings
                    </h3>
                    <div style="display: flex; gap: 30px; flex-wrap: wrap; margin-top: 20px;">
                        <div style="background: white; padding: 15px 25px; border-radius: 10px; min-width: 200px;">
                            <div style="font-size: 12px; color: var(--text-light);">Meals Per Day</div>
                            <div style="font-size: 24px; font-weight: bold; color: var(--primary-green);">
                                <?php echo $generated_plan['meals_per_day_setting']; ?>
                            </div>
                        </div>
                        
                        <div style="background: white; padding: 15px 25px; border-radius: 10px; min-width: 200px;">
                            <div style="font-size: 12px; color: var(--text-light);">Weekly Budget</div>
                            <div style="font-size: 24px; font-weight: bold; color: var(--primary-green);">
                                KES <?php echo number_format($current_budget['amount'] ?? 0, 0); ?>
                            </div>
                        </div>
                        
                        <div style="background: white; padding: 15px 25px; border-radius: 10px; min-width: 200px;">
                            <div style="font-size: 12px; color: var(--text-light);">Diet Type</div>
                            <div style="font-size: 24px; font-weight: bold; color: var(--primary-green);">
                                <?php echo htmlspecialchars($preferences['diet_type'] ?? 'Balanced'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="content-section pantry-highlight">
                    <h3 style="color: var(--dark-green); margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i> Personalized Meal Plan Generated
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 25px 0;">
                        <div style="background: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                            <div style="font-size: 42px; font-weight: bold; color: var(--primary-green);">
                                <?php echo number_format($generated_plan['total_pantry_coverage'], 0); ?>%
                            </div>
                            <p style="color: var(--text-light); margin-top: 10px;">Pantry Utilization</p>
                        </div>
                        
                        <div style="background: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                            <div style="font-size: 42px; font-weight: bold; color: var(--accent-orange);">
                                <?php echo count($generated_plan['pantry_items_used']); ?>
                            </div>
                            <p style="color: var(--text-light); margin-top: 10px;">Items From Your Pantry</p>
                        </div>
                        
                        <div style="background: white; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                            <div style="font-size: 42px; font-weight: bold; color: var(--accent-blue);">
                                KES <?php echo number_format($generated_plan['shopping_cost'], 0); ?>
                            </div>
                            <p style="color: var(--text-light); margin-top: 10px;">Additional Shopping</p>
                        </div>
                    </div>
                </div>
                
                <!-- Weekly Meal Plan Display with Ingredients -->
                <div class="content-section">
                    <h3 style="color: var(--dark-green); margin-bottom: 25px;">
                        <i class="fas fa-calendar-week"></i> Your Weekly Meal Plan
                    </h3>
                    
                    <div class="meal-plan-grid">
                        <?php foreach ($generated_plan['days'] as $day => $day_data): 
                            $day_coverage_class = 'coverage-high';
                            if ($day_data['pantry_coverage'] < 40) $day_coverage_class = 'coverage-low';
                            elseif ($day_data['pantry_coverage'] < 70) $day_coverage_class = 'coverage-medium';
                        ?>
                            <div class="day-card">
                                <div class="day-header">
                                    <span class="day-name"><?php echo $day; ?></span>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span class="coverage-indicator <?php echo $day_coverage_class; ?>">
                                            <?php echo number_format($day_data['pantry_coverage'], 0); ?>% pantry
                                        </span>
                                        <span class="day-cost">KES <?php echo number_format($day_data['cost'], 0); ?></span>
                                    </div>
                                </div>
                                
                                <div style="font-size: 13px; color: var(--text-light); margin-bottom: 15px;">
                                    <i class="fas fa-utensils"></i> 
                                    <?php echo $day_data['meals_count']; ?> meals planned
                                </div>
                                
                                <div class="meals-list">
                                    <?php foreach ($day_data['meals'] as $meal_entry): 
                                        $meal_data = $meal_entry['data'];
                                        $meal_type = $meal_entry['meal_type'];
                                    ?>
                                        <div class="meal-item <?php echo strtolower($meal_type); ?>">
                                            <div class="meal-header">
                                                <div>
                                                    <div class="meal-name">
                                                        <?php echo htmlspecialchars($meal_data['recipe']['recipe_name']); ?>
                                                    </div>
                                                    <div class="meal-type">
                                                        <i class="fas fa-<?php 
                                                            echo $meal_type == 'Breakfast' ? 'sun' : 
                                                                 ($meal_type == 'Lunch' ? 'sun' : 
                                                                 ($meal_type == 'Dinner' ? 'moon' : 'apple-alt')); 
                                                        ?>"></i>
                                                        <?php echo $meal_type; ?>
                                                    </div>
                                                </div>
                                                <div class="meal-cost">
                                                    KES <?php echo number_format($meal_data['store_cost'], 0); ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($meal_data['recipe']['description'])): ?>
                                                <p style="font-size: 13px; color: var(--text-light); margin: 10px 0;">
                                                    <?php echo htmlspecialchars($meal_data['recipe']['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="ingredients-list">
                                                <div style="font-size: 12px; font-weight: 600; color: var(--text-dark); margin-bottom: 8px;">
                                                    Ingredients (<?php echo count($meal_data['recipe']['ingredients']); ?>):
                                                </div>
                                                <?php foreach ($meal_data['recipe']['ingredients'] as $ingredient): 
                                                    $source = 'store';
                                                    $source_class = 'source-store';
                                                    $source_text = 'To buy';
                                                    
                                                    foreach ($meal_data['pantry_used'] as $used) {
                                                        if ($used['item'] == $ingredient['name']) {
                                                            $source = 'pantry';
                                                            $source_class = 'source-pantry';
                                                            $source_text = 'From pantry';
                                                            break;
                                                        }
                                                    }
                                                ?>
                                                    <div class="ingredient-item">
                                                        <span>
                                                            <?php echo htmlspecialchars($ingredient['name']); ?> 
                                                            (<?php echo $ingredient['quantity']; ?> <?php echo $ingredient['unit']; ?>)
                                                        </span>
                                                        <span class="ingredient-source <?php echo $source_class; ?>">
                                                            <?php echo $source_text; ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Weekly Summary -->
                    <div style="background: var(--light-green); padding: 20px; border-radius: 10px; margin-top: 30px;">
                        <h4 style="color: var(--dark-green); margin-bottom: 15px;">Weekly Summary</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <div>
                                <div style="font-size: 12px; color: var(--text-light);">Total Meals</div>
                                <div style="font-size: 24px; font-weight: bold; color: var(--text-dark);">
                                    <?php 
                                    $total_meals = 0;
                                    foreach ($generated_plan['days'] as $day_data) {
                                        $total_meals += $day_data['meals_count'];
                                    }
                                    echo $total_meals;
                                    ?>
                                </div>
                            </div>
                            
                            <div>
                                <div style="font-size: 12px; color: var(--text-light);">Total Cost</div>
                                <div style="font-size: 24px; font-weight: bold; color: var(--text-dark);">
                                    KES <?php echo number_format($generated_plan['total_cost'], 0); ?>
                                </div>
                            </div>
                            
                            <div>
                                <div style="font-size: 12px; color: var(--text-light);">Total Calories</div>
                                <div style="font-size: 24px; font-weight: bold; color: var(--accent-orange);">
                                    <?php echo number_format($generated_plan['total_calories']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($generated_plan['shopping_list'])): ?>
                    <div class="content-section" style="background: #fff9f0; border: 2px solid #ffeed9;">
                        <h3 style="color: var(--accent-orange); margin-bottom: 20px;">
                            <i class="fas fa-shopping-cart"></i> Shopping List
                        </h3>
                        
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($generated_plan['shopping_list'] as $item): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; 
                                            padding: 12px 0; border-bottom: 1px solid #ffeed9;">
                                    <div>
                                        <span style="font-weight: 500;"><?php echo htmlspecialchars($item['item']); ?></span>
                                        <span style="color: var(--text-light); font-size: 14px; margin-left: 10px;">
                                            <?php echo number_format($item['quantity'], 2); ?> <?php echo $item['unit']; ?>
                                        </span>
                                    </div>
                                    <span style="font-weight: 600; color: var(--primary-green);">
                                        KES <?php echo number_format($item['estimated_cost'], 0); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; 
                                    padding: 20px 0; margin-top: 20px; border-top: 2px solid #ffeed9; 
                                    font-weight: 600; font-size: 18px; color: var(--dark-green);">
                            <span>Total Shopping Cost:</span>
                            <span style="font-size: 20px;">KES <?php echo number_format($generated_plan['shopping_cost'], 0); ?></span>
                        </div>
                        
                        <form method="POST" action="" style="text-align: center; margin-top: 30px;">
                            <button type="submit" name="save_plan" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Plan & Create Shopping List
                            </button>
                            <p style="color: var(--text-light); font-size: 14px; margin-top: 15px;">
                                Save this plan to access it later and generate a shopping list
                            </p>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>