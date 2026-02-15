<?php
// clear_session.php
session_start();
session_destroy();
echo "<h1>Session Cleared!</h1>";
echo "<p>All login data has been cleared.</p>";
echo '<p><a href="index.php">Go to Homepage</a></p>';
?>