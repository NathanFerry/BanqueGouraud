<?php
// index.php (Updated with secure database Q&A)

require_once "../utils/database.php"; // Include database connection

$submission_message = '';
$submission_success = false;

// --- Form Submission Handling (Using POST for creating data) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contact_submit'])) {
    if (!empty($_POST['message'])) {
        $name = !empty($_POST['name']) ? $_POST['name'] : 'Anonyme';
        $message = $_POST['message'];

        // Prepare an insert statement to prevent SQL Injection
        $sql = "INSERT INTO questions (name, message) VALUES (?, ?)";
        
        if ($stmt = $mysqli->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ss", $param_name, $param_message);
            
            // Set parameters
            $param_name = $name;
            $param_message = $message;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                $submission_message = "Merci pour votre question ! Elle a été ajoutée à notre liste d'attente.";
                $submission_success = true;
            } else {
                $submission_message = "Oops! Une erreur est survenue. Veuillez réessayer plus tard.";
            }

            // Close statement
            $stmt->close();
        }
    } else {
        $submission_message = "Veuillez entrer une question avant de soumettre.";
    }
}

// --- Fetching All Questions from the Database ---
$questions = [];
$sql_fetch = "SELECT name, message, created_at FROM questions ORDER BY created_at DESC";
if ($result = $mysqli->query($sql_fetch)) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $questions[] = $row;
        }
    }
    $result->free();
}
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue - Banque Gouraud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <!-- Header Navigation -->
    <nav class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex-shrink-0"><a href="./welcome.php"><h1 class="text-2xl font-bold text-blue-600">Banque Gouraud</h1></a></div>
                <div class="flex items-center gap-4">
                    <a href="./login.php" class="text-sm font-medium text-gray-600 hover:text-blue-600">Se Connecter</a>
                    <a href="./register.php" class="bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-blue-700">Ouvrir un compte</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Hero Section -->
        <section class="text-center py-16">
            <h1 class="text-5xl font-extrabold text-gray-900 mb-4">La confiance n'attend pas. Le remboursement non plus.</h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Bienvenue à la Banque Gouraud, l'institution financière de référence pour la gestion de dettes privées.</p>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
            <!-- Contact Form Section -->
            <section id="contact" class="bg-white p-8 rounded-2xl shadow-lg">
                <h2 class="text-3xl font-bold mb-6">Vous avez une question ?</h2>
                <p class="text-gray-600 mb-6">Posez votre question ici. Elle sera affichée publiquement pour que tout le monde puisse en profiter.</p>
                
                <?php if ($submission_message): ?>
                    <div class="<?php echo $submission_success ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'; ?> border-l-4 p-4 rounded-r-lg mb-6" role="alert">
                        <p><?php echo htmlspecialchars($submission_message); ?></p>
                    </div>
                <?php endif; ?>

                <form action="welcome.php#contact" method="POST" class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Votre Nom</label>
                        <input type="text" id="name" name="name" class="mt-1 block w-full bg-gray-50 border border-gray-300 rounded-lg shadow-sm p-2.5 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700">Votre Question</label>
                        <textarea id="message" name="message" rows="4" class="mt-1 block w-full bg-gray-50 border border-gray-300 rounded-lg shadow-sm p-2.5 focus:border-blue-500 focus:ring-blue-500" required placeholder="Ex: Quand est-ce que je serai remboursé ?"></textarea>
                    </div>
                    <div>
                        <button type="submit" name="contact_submit" value="1" class="w-full text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-5 py-3">Soumettre ma question</button>
                    </div>
                </form>
            </section>

            <!-- Display Submitted Questions Section -->
            <section class="bg-white p-8 rounded-2xl shadow-lg">
                <h3 class="text-2xl font-bold mb-6">Questions des clients :</h3>
                <div class="space-y-6 max-h-96 overflow-y-auto pr-4">
                    <?php if (!empty($questions)): ?>
                        <?php foreach ($questions as $q): ?>
                            <div class="border-l-4 border-gray-200 pl-4 py-2">
                                <p class="font-bold text-gray-900">
                                    <?php
                                    // SECURITY: Using htmlspecialchars() to prevent Stored XSS.
                                    // This neutralizes any HTML/JS in the name.
                                    echo $q['name'];
                                    ?>
                                </p>
                                <div class="text-gray-700 mt-1 prose">
                                    <?php
                                    // SECURITY: Using htmlspecialchars() again for the message.
                                    // This is the critical step to prevent Stored XSS.
                                    echo htmlspecialchars($q['message']);
                                    ?>
                                </div>
                                <p class="text-xs text-gray-400 mt-2"><?php echo date('d/m/Y H:i', strtotime($q['created_at'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Aucune question pour le moment. Soyez le premier !</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="text-center py-8 mt-12 border-t border-gray-200">
        <p class="text-sm text-gray-500">&copy; 2025 Banque Gouraud. L'argent des autres, notre passion.</p>
    </footer>

</body>
</html>


