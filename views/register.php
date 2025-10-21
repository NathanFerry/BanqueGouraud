<?php
// register.php
// This script handles new user registration.

// Include the database connection file.
require_once "../utils/database.php";

$username = $password = "";
$error = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  $username = $_POST['username'];
  $password = $_POST['password'];

  if (empty(trim($username))) {
    $error = "Veuillez entrer un nom d'utilisateur.";
  } elseif (empty(trim($password))) {
    $error = "Veuillez entrer un mot de passe.";
  } else {
    // VULNERABILITY #1: SQL Injection.
    // The query is built by directly concatenating user input ($username) without sanitization
    // or prepared statements. An attacker could inject malicious SQL here.
    $sql_check = "SELECT id FROM users WHERE username = '$username'";

    $result_check = $mysqli->query($sql_check);

    if ($result_check->num_rows > 0) {
      $error = "Ce nom d'utilisateur est déjà pris.";
    } else {
      // VULNERABILITY #2: Plaintext Password Storage.
      // The password is stored directly in the database without any hashing.
      // If the database is compromised, all user passwords will be exposed.
      $hased_password = hash("sha256", $password);
      $sql_insert = "INSERT INTO users (username, password) VALUES ('$username', '$hased_password')";

      if ($mysqli->query($sql_insert) === TRUE) {
        // Redirect to login page after successful registration
        header("location: login.php");
        exit();
      } else {
        $error = "Une erreur est survenue. Veuillez réessayer.";
      }
    }
  }
  $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Inscription - Banque Gouraud</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>

<body class="bg-gray-100">
  <div class="min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 m-4">
      <h2 class="text-3xl font-bold text-center text-gray-800 mb-4">Créer un compte</h2>
      <p class="text-center text-gray-600 mb-8">Devenez un "client" officiel de la Banque Gouraud.</p>

      <?php
      if (!empty($error)) {
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
        <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">S'inscrire</button>
      </form>
      <p class="text-sm text-center text-gray-600 mt-6">
        Déjà client ? <a href="login.php" class="font-medium text-blue-600 hover:underline">Connectez-vous ici</a>.
      </p>
    </div>
  </div>
</body>

</html>
