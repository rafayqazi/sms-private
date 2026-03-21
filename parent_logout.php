<?php
session_start();
session_unset();
session_destroy();

// Redirect back to login with a logout message
header("Location: login.php?logout=parent");
exit;
