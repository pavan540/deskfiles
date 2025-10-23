<?php
// Start the session
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login page or logincheck.php
header("Location: logincheck.php");
exit();
?>
