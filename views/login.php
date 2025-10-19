<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// login.php
// This script handles user authentication.

// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect them to dashboard page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

// Include database connection file
require_once "../utils/database.php";

$username = $password = "";
$error = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST["username"];
    $password = $_POST["password"];

    if (empty(trim($username))) {
        $error = "Veuillez entrer votre nom d'utilisateur.";
    } elseif (empty(trim($password))) {
        $error = "Veuillez entrer votre mot de passe.";
    } else {
        // VULNERABILITY: SQL Injection.
        // The query is constructed by concatenating user input directly.
        // An attacker can use this to bypass authentication.
        // For example, using username: ' OR '1'='1' --
        $sql = "SELECT id, username, is_admin FROM users WHERE username = '$username' AND password = '$password'";
        
        $result = $mysqli->query($sql);

        if ($result && $result->num_rows == 1) {
            // User authenticated successfully
            $user = $result->fetch_assoc();
            
            // Store data in session variables
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["is_admin"] = $user["is_admin"];
            
            // Redirect user to the dashboard page
            header("location: dashboard.php");
            exit();

        } else {
            // Display an error message if authentication failed
            $error = "Nom d'utilisateur ou mot de passe incorrect.";
        }
    }
    
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Banque Gouraud</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 m-4">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-4">Connexion</h2>
            <p class="text-center text-gray-600 mb-8">Accédez à votre espace personnel.</p>
            
            <?php 
            if(!empty($error)){
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">' . htmlspecialchars($error) . '</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-5">
                    <label for="username" class="block mb-2 text-sm font-medium text-gray-700">Nom d'utilisateur</label>
                    <input type="text" name="username" id="username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div class="mb-6">
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-700">Mot de passe</label>
                    <input type="password" name="password" id="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Se connecter</button>
            </form>
             <p class="text-sm text-center text-gray-600 mt-6">
                Pas encore client ? <a href="register.php" class="font-medium text-blue-600 hover:underline">Créez un compte</a>.
            </p>
        </div>
    </div>
</body>
</html>

