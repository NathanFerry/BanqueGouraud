<?php
// login.php
// This script handles user authentication.

session_start();

// If the user is already logged in, redirect them to the dashboard.
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: ./dashboard.php");
    exit;
}

require_once "../utils/database.php";

$username = $password = "";
$username_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- VULNERABILITY #1: SQL Injection ---
    // The user input is directly concatenated into the SQL query, making it
    // vulnerable to SQL Injection. An attacker can use a crafted input like
    // ' OR '1'='1' -- to bypass the password check.
    $username = $_POST["username"];
    $password = $_POST["password"];

    if (empty(trim($username))) {
        $username_err = "Veuillez entrer votre nom d'utilisateur.";
    }

    if (empty(trim($password))) {
        $password_err = "Veuillez entrer votre mot de passe.";
    }

    if (empty($username_err) && empty($password_err)) {
        // The vulnerable query construction
        $sql = "SELECT id, username, password, is_admin FROM users WHERE username = '" . $username . "'";

        $result = $mysqli->query($sql);

        if ($result && $result->num_rows == 1) {
            $row = $result->fetch_assoc();
            
            // --- VULNERABILITY #2: Plaintext Password ---
            // The password from the database is compared directly with the user's input.
            // This implicitly confirms that passwords are stored in plaintext.
            if ($password === $row['password']) {
                // Password is correct, start a new session
                session_start();

                // Store data in session variables
                $_SESSION["loggedin"] = true;
                $_SESSION["id"] = $row['id'];
                $_SESSION["username"] = $row['username'];
                // NEW: Store admin status in the session
                $_SESSION["is_admin"] = ($row['is_admin'] == 1);

                // Redirect user to the dashboard
                header("location: ./dashboard.php");
            } else {
                $login_err = "Nom d'utilisateur ou mot de passe invalide.";
            }
        } else {
            $login_err = "Nom d'utilisateur ou mot de passe invalide.";
        }
    }
    
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Banque Gouraud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .form-container {
            background-color: white;
            padding: 2.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md">
        <a href="./welcome.php"><h1 class="text-3xl font-bold text-center text-blue-600 mb-6">Banque Gouraud</h1></a>
        <div class="form-container">
            <h2 class="text-2xl font-semibold text-center text-gray-800 mb-5">Connexion</h2>

            <?php 
            if(!empty($login_err)){
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . $login_err . '</div>';
            }        
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700">Nom d'utilisateur</label>
                    <input type="text" name="username" id="username" class="mt-1 block w-full px-3 py-2 border <?php echo (!empty($username_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" value="<?php echo $username; ?>">
                    <span class="text-red-500 text-xs italic"><?php echo $username_err; ?></span>
                </div>    
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700">Mot de passe</label>
                    <input type="password" name="password" id="password" class="mt-1 block w-full px-3 py-2 border <?php echo (!empty($password_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <span class="text-red-500 text-xs italic"><?php echo $password_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="w-full bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-blue-700 cursor-pointer" value="Se connecter">
                </div>
                <p class="text-center text-gray-600 text-sm mt-6">
                    Vous n'avez pas de compte ? <a href="register.php" class="text-blue-600 hover:underline">Inscrivez-vous ici</a>.
                </p>
            </form>
        </div>
    </div>
</body>
</html>


