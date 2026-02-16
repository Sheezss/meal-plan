<?php
$host = "127.0.0.1";
$username = "root";
$password = "";
$database = "meal-plan";
$port = 3306;

$conn = mysqli_connect($host, $username, $password, $database, $port);
// Add this at the end of config.php to verify connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    // Optional: remove in production
    // echo "<!-- Database connected successfully -->";
}


?>