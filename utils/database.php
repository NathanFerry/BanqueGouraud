<?php
// db.php
// This file handles the connection to the MariaDB database.

// --- Database Configuration ---
// Replace these with your actual database credentials.
define('DB_SERVER', '172.18.0.2');
define('DB_USERNAME', 'prod_user');
define('DB_PASSWORD', 'password'); // Default XAMPP/MAMP password is empty
define('DB_NAME', 'bank');

// --- Create Connection ---
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// --- Check Connection ---
// If the connection fails, terminate the script and display an error.
if($mysqli === false){
    die("ERROR: Could not connect. " . $mysqli->connect_error);
    echo "DB Error";
}
?>
