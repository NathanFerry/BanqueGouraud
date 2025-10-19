<?php
// dashboard.php
// This is the main page for logged-in users.

session_start();

// SECURITY: Ensure the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ./login.php");
    exit;
}

require_once "../utils/database.php";

$user_id = $_SESSION["id"];
$username = $_SESSION["username"];
$feedback_message = '';
$feedback_is_error = false;

// --- VULNERABILITY #4: Cross-Site Request Forgery (CSRF) ---
// The forms below do not contain a CSRF token. An attacker could create a malicious
// webpage with a hidden form that submits a POST request to this page. If a logged-in
// user visits the attacker's site, their browser will automatically send their session cookie,
// and the transaction (e.g., loaning 100€) will be processed without their knowledge or consent.
// Example attacker page: <body onload="document.forms[0].submit()">
//   <form action="http://localhost/dashboard.php" method="POST" style="display:none;">
//     <input type="hidden" name="amount" value="1000">
//     <input type="hidden" name="action" value="loan">
//   </form></body>

// --- Form Handling for Loans and Repayments ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['amount']) && isset($_POST['action'])) {
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
    $action = $_POST['action'];

    if ($amount === false || $amount <= 0) {
        $feedback_message = "Veuillez entrer un montant valide.";
        $feedback_is_error = true;
    } else {
        if ($action === 'loan' || $action === 'repayment') {
            $sql = "INSERT INTO transactions (user_id, transaction_type, amount) VALUES (?, ?, ?)";
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("isd", $user_id, $action, $amount);
                if ($stmt->execute()) {
                    $feedback_message = "Transaction enregistrée avec succès !";
                } else {
                    $feedback_message = "Erreur lors de l'enregistrement de la transaction.";
                    $feedback_is_error = true;
                }
                $stmt->close();
            }
        }
    }
}


// --- Fetching Transaction Data for the User ---
$transactions = [];
$total_loaned = 0;
$total_repaid = 0;

$sql_fetch = "SELECT transaction_type, amount, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC";
if ($stmt = $mysqli->prepare($sql_fetch)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
        if ($row['transaction_type'] === 'loan') {
            $total_loaned += $row['amount'];
        } else {
            $total_repaid += $row['amount'];
        }
    }
    $stmt->close();
}
$balance = $total_loaned - $total_repaid;

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title>Tableau de Bord - Banque Gouraud</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <span class="text-gray-700 font-medium">Bonjour, <?php echo htmlspecialchars($username); ?></span>
                    <a href="../utils/logout.php" class="bg-red-500 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-red-600 transition-colors">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Votre tableau de bord</h2>
        
        <?php if ($feedback_message): ?>
            <div class="<?php echo $feedback_is_error ? 'bg-red-100 border-red-500 text-red-700' : 'bg-green-100 border-green-500 text-green-700'; ?> border-l-4 p-4 rounded-r-lg mb-6" role="alert">
                <p><?php echo htmlspecialchars($feedback_message); ?></p>
            </div>
        <?php endif; ?>

        <!-- Stats and Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Stats Cards -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-6 rounded-xl shadow-lg text-center">
                    <h3 class="text-lg font-semibold text-gray-500">Solde Actuel</h3>
                    <p class="text-4xl font-extrabold <?php echo $balance >= 0 ? 'text-red-500' : 'text-green-500'; ?> mt-2"><?php echo number_format($balance, 2, ',', ' '); ?> €</p>
                    <p class="text-sm text-gray-400">Montant dû à Romain</p>
                </div>
                 <div class="bg-white p-6 rounded-xl shadow-lg text-center">
                    <h3 class="text-lg font-semibold text-gray-500">Total Prêté</h3>
                    <p class="text-3xl font-bold text-blue-500 mt-2"><?php echo number_format($total_loaned, 2, ',', ' '); ?> €</p>
                </div>
                 <div class="bg-white p-6 rounded-xl shadow-lg text-center">
                    <h3 class="text-lg font-semibold text-gray-500">Total Remboursé</h3>
                    <p class="text-3xl font-bold text-green-500 mt-2"><?php echo number_format($total_repaid, 2, ',', ' '); ?> €</p>
                </div>
            </div>
            <!-- Chart -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg">
                 <h3 class="text-xl font-bold mb-4">Répartition des transactions</h3>
                 <div class="relative h-80">
                    <canvas id="transactionsChart"></canvas>
                 </div>
            </div>
        </div>

        <!-- Forms and History -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Action Forms -->
            <div class="space-y-8">
                <!-- Loan Form -->
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4">Contracter un nouveau prêt</h3>
                    <form action="dashboard.php" method="POST">
                        <input type="hidden" name="action" value="loan">
                        <label for="loan-amount" class="block text-sm font-medium text-gray-700">Montant</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="number" name="amount" id="loan-amount" step="0.01" class="flex-1 block w-full rounded-none rounded-l-md p-2.5 border-gray-300 focus:ring-blue-500 focus:border-blue-500" placeholder="0.00">
                            <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">€</span>
                        </div>
                        <button type="submit" class="mt-4 w-full bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg shadow-md hover:bg-blue-700">Emprunter</button>
                    </form>
                </div>
                <!-- Repayment Form -->
                <div class="bg-white p-8 rounded-xl shadow-lg">
                    <h3 class="text-xl font-bold mb-4">Effectuer un remboursement</h3>
                     <form action="dashboard.php" method="POST">
                        <input type="hidden" name="action" value="repayment">
                        <label for="repay-amount" class="block text-sm font-medium text-gray-700">Montant</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="number" name="amount" id="repay-amount" step="0.01" class="flex-1 block w-full rounded-none rounded-l-md p-2.5 border-gray-300 focus:ring-green-500 focus:border-green-500" placeholder="0.00">
                            <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">€</span>
                        </div>
                        <button type="submit" class="mt-4 w-full bg-green-600 text-white font-semibold py-3 px-4 rounded-lg shadow-md hover:bg-green-700">Rembourser</button>
                    </form>
                </div>
            </div>
            <!-- Transaction History -->
            <div class="bg-white p-8 rounded-xl shadow-lg">
                <h3 class="text-xl font-bold mb-4">Historique des transactions</h3>
                <div class="max-h-96 overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($transactions)): ?>
                                <tr><td colspan="3" class="py-4 text-center text-gray-500">Aucune transaction pour le moment.</td></tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $t): ?>
                                    <tr>
                                        <td class="px-2 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium <?php echo $t['transaction_type'] === 'loan' ? 'text-red-600' : 'text-green-600'; ?>">
                                                <?php echo $t['transaction_type'] === 'loan' ? 'Prêt' : 'Remboursement'; ?>
                                            </div>
                                        </td>
                                        <td class="px-2 py-4 whitespace-nowrap text-right">
                                            <div class="text-sm font-semibold text-gray-900"><?php echo number_format($t['amount'], 2, ',', ' '); ?> €</div>
                                        </td>
                                        <td class="px-2 py-4 whitespace-nowrap text-right">
                                            <div class="text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($t['created_at'])); ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

<script>
    const ctx = document.getElementById('transactionsChart').getContext('2d');
    const transactionsChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Total Prêté', 'Total Remboursé'],
            datasets: [{
                label: 'Transactions',
                // Data is passed from PHP
                data: [<?php echo $total_loaned; ?>, <?php echo $total_repaid; ?>],
                backgroundColor: [
                    'rgba(239, 68, 68, 0.8)', // red-500
                    'rgba(22, 163, 74, 0.8)'  // green-600
                ],
                borderColor: [
                    'rgba(239, 68, 68, 1)',
                    'rgba(22, 163, 74, 1)'
                ],
                borderWidth: 1,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                label += new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(context.parsed);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>

