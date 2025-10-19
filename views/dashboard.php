<?php
// dashboard.php
// This is the main page for logged-in users.

// Initialize the session
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de Bord - Banque Gouraud</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex-shrink-0">
                    <div class="flex-shrink-0"><a href="./welcome.php"><h1 class="text-2xl font-bold text-blue-600">Banque Gouraud</h1></a></div>
                </div>
                <div class="flex items-center">
                    <p class="text-gray-700 mr-4">Bonjour, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong> !</p>
                    <a href="../utils/logout.php" class="bg-red-500 text-white font-semibold py-2 px-4 rounded-lg hover:bg-red-600 transition">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="py-10">
        <header class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold leading-tight text-gray-900">Tableau de Bord</h1>
        </header>
        <main>
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
                <div class="px-4 py-8 sm:px-0">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-semibold mb-4">Solde de votre dette</h2>
                        <p class="text-gray-600">Cette section affichera bientôt le montant que vous devez à Romain.</p>
                        <!-- Future content like charts and tables will go here -->
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

