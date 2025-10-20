<?php
// profile.php
// Page for users to manage their profile details.

session_start();

// SECURITY: Ensure the user is logged in.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ./login.php");
    exit;
}

require_once "../utils/database.php";

$user_id = $_SESSION["id"];
$username = $_SESSION["username"];
$profile_picture = $_SESSION["profile_picture"] ?? '../uploads/default.png';

$feedback = ['message' => '', 'is_error' => false];

// --- Handle Profile Picture Upload ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_picture"])) {
    
    // --- VULNERABILITY #5: Unrestricted File Upload ---
    // This code checks the file extension on the client-side filename, but does not
    // validate the file's actual content (MIME type) or re-name the file securely.
    // An attacker could upload a file named "shell.php.jpg" or use a tool like Burp Suite
    // to change the filename to "shell.php" after the browser check.
    // If the server is configured to execute .php files in the 'uploads' directory,
    // the attacker can then navigate to 'uploads/shell.php' to execute their code on the server.
    
    $target_dir = "../uploads/";
    $original_filename = basename($_FILES["profile_picture"]["name"]);
    // Create a unique filename to prevent overwriting, but keep the original (potentially malicious) extension.
    $new_filename = uniqid() . '-' . $original_filename;
    $target_file = $target_dir . $new_filename;
    $uploadOk = 1;

    // Check file size (e.g., 5MB limit)
    if ($_FILES["profile_picture"]["size"] > 5000000) {
        $feedback['message'] = "Votre fichier est trop volumineux.";
        $feedback['is_error'] = true;
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            // Update database
            $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("si", $target_file, $user_id);
                if ($stmt->execute()) {
                    $_SESSION["profile_picture"] = $target_file; // Update session
                    $feedback['message'] = "Photo de profil mise à jour.";
                    header("location: profile.php"); // Refresh to show new pic
                    exit;
                } else {
                    $feedback['message'] = "Erreur lors de la mise à jour de la base de données.";
                    $feedback['is_error'] = true;
                }
                $stmt->close();
            }
        } else {
            $feedback['message'] = "Erreur lors du téléchargement de votre fichier.";
            $feedback['is_error'] = true;
        }
    }
}

// --- Handle Username Change ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_username'])) {
    $new_username = trim($_POST['new_username']);
    if (empty($new_username)) {
        $feedback['message'] = "Le nom d'utilisateur ne peut pas être vide.";
        $feedback['is_error'] = true;
    } elseif ($new_username === $username) {
        $feedback['message'] = "Le nouveau nom d'utilisateur est identique à l'ancien.";
        $feedback['is_error'] = true;
    } else {
        // Check if new username is already taken
        $sql = "SELECT id FROM users WHERE username = ?";
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("s", $new_username);
            $stmt->execute();
            $stmt->store_result();
            if($stmt->num_rows == 1){
                $feedback['message'] = "Ce nom d'utilisateur est déjà pris.";
                $feedback['is_error'] = true;
            } else {
                // Update username
                $sql_update = "UPDATE users SET username = ? WHERE id = ?";
                if($stmt_update = $mysqli->prepare($sql_update)) {
                    $stmt_update->bind_param("si", $new_username, $user_id);
                    if($stmt_update->execute()){
                        $_SESSION["username"] = $new_username; // Update session
                        $feedback['message'] = "Nom d'utilisateur mis à jour.";
                        header("location: profile.php");
                        exit;
                    }
                }
            }
            $stmt->close();
        }
    }
}

// --- Handle Password Change ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['current_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $feedback['message'] = "Veuillez remplir tous les champs de mot de passe.";
        $feedback['is_error'] = true;
    } elseif ($new_password !== $confirm_password) {
        $feedback['message'] = "Les nouveaux mots de passe ne correspondent pas.";
        $feedback['is_error'] = true;
    } else {
        $sql = "SELECT password FROM users WHERE id = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($db_password);
            if ($stmt->fetch()) {
                if ($current_password === $db_password) {
                    $stmt->close();
                    $sql_update = "UPDATE users SET password = ? WHERE id = ?";
                    if ($stmt_update = $mysqli->prepare($sql_update)) {
                        $stmt_update->bind_param("si", $new_password, $user_id);
                        if ($stmt_update->execute()) {
                           $feedback['message'] = "Mot de passe mis à jour avec succès.";
                        }
                    }
                } else {
                     $feedback['message'] = "Le mot de passe actuel est incorrect.";
                     $feedback['is_error'] = true;
                }
            }
        }
    }
}


$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Banque Gouraud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100">

    <!-- Header Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex-shrink-0">
                    <a href="./welcome.php"><h1 class="text-2xl font-bold text-blue-600">Banque Gouraud</h1></a>
                </div>
                <div class="flex items-center gap-4">
                    <a href="./dashboard.php" class="text-sm font-medium text-gray-600 hover:text-blue-600">Tableau de Bord</a>
                    <a href="../utils/logout.php" class="bg-red-500 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-red-600 transition-colors">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Gérer mon profil</h2>
        
        <?php if ($feedback['message']): ?>
            <div class="<?php echo $feedback['is_error'] ? 'bg-red-100 border-red-500 text-red-700' : 'bg-green-100 border-green-500 text-green-700'; ?> border-l-4 p-4 rounded-r-lg mb-6" role="alert">
                <p><?php echo htmlspecialchars($feedback['message']); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Left column for picture -->
            <div class="md:col-span-1">
                <div class="bg-white p-6 rounded-xl shadow-lg text-center">
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Photo de profil" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover" onerror="this.src='https://placehold.co/128x128/E2E8F0/4A5568?text=BG'">
                    <h3 class="text-xl font-bold"><?php echo htmlspecialchars($username); ?></h3>
                    <form action="profile.php" method="POST" enctype="multipart/form-data" class="mt-4">
                        <label for="profile_picture_input" class="cursor-pointer bg-blue-100 text-blue-700 text-sm font-semibold px-4 py-2 rounded-lg hover:bg-blue-200">
                            Changer la photo
                        </label>
                        <input type="file" name="profile_picture" id="profile_picture_input" class="hidden" onchange="this.form.submit()">
                    </form>
                </div>
            </div>

            <!-- Right column for forms -->
            <div class="md:col-span-2 space-y-8">
                <!-- Change Username -->
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4">Changer de nom d'utilisateur</h3>
                    <form action="profile.php" method="POST">
                        <div>
                            <label for="new_username" class="block text-sm font-medium text-gray-700">Nouveau nom d'utilisateur</label>
                            <input type="text" name="new_username" id="new_username" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <button type="submit" class="mt-4 w-full bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-blue-700">Enregistrer</button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="bg-white p-8 rounded-xl shadow-lg">
                     <h3 class="text-xl font-bold mb-4">Changer de mot de passe</h3>
                    <form action="profile.php" method="POST" class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Mot de passe actuel</label>
                            <input type="password" name="current_password" id="current_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">Nouveau mot de passe</label>
                            <input type="password" name="new_password" id="new_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirmer le nouveau mot de passe</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-blue-700">Changer le mot de passe</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

</body>
</html>

