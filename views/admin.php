<?php
// admin.php
// This is the admin panel for managing transaction requests.

session_start();

// SECURITY: Ensure the user is logged in AND is an admin.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
    // If not, redirect to the dashboard (or login page).
    // They will not be able to access this page's content.
    header("location: ./dashboard.php");
    exit;
}

require_once "../utils/database.php";

$username = $_SESSION["username"];
$feedback_message = '';
$feedback_is_error = false;

// --- Handle Admin Actions (Approve/Reject) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['transaction_id']) && isset($_POST['status'])) {
    $transaction_id = intval($_POST['transaction_id']);
    $new_status = $_POST['status'];

    if ($new_status === 'approved' || $new_status === 'rejected') {
        $sql_update = "UPDATE transactions SET status = ? WHERE id = ? AND status = 'pending'";
        if ($stmt = $mysqli->prepare($sql_update)) {
            $stmt->bind_param("si", $new_status, $transaction_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $feedback_message = "La transaction a été mise à jour.";
                } else {
                    $feedback_message = "Cette transaction a peut-être déjà été traitée.";
                    $feedback_is_error = true;
                }
            } else {
                $feedback_message = "Erreur lors de la mise à jour.";
                $feedback_is_error = true;
            }
            $stmt->close();
        }
    }
}

// --- Fetch all pending transactions ---
$pending_transactions = [];
$sql_fetch = "SELECT t.id, t.transaction_type, t.amount, t.created_at, u.username 
              FROM transactions t
              JOIN users u ON t.user_id = u.id
              WHERE t.status = 'pending' 
              ORDER BY t.created_at ASC";

$result = $mysqli->query($sql_fetch);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pending_transactions[] = $row;
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panneau Admin - Banque Gouraud</title>
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
                    <a href="./welcome.php"><h1 class="text-2xl font-bold text-blue-600">Banque Gouraud - Admin</h1></a>
                </div>
                <div class="flex items-center gap-4">
                    <a href="./dashboard.php" class="text-sm font-medium text-gray-600 hover:text-blue-600">Mon Tableau de Bord</a>
                    <a href="../utils/logout.php" class="bg-red-500 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:bg-red-600 transition-colors">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Gestion des demandes en attente</h2>

        <?php if ($feedback_message): ?>
            <div class="<?php echo $feedback_is_error ? 'bg-red-100 border-red-500 text-red-700' : 'bg-green-100 border-green-500 text-green-700'; ?> border-l-4 p-4 rounded-r-lg mb-6" role="alert">
                <p><?php echo htmlspecialchars($feedback_message); ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-white p-8 rounded-xl shadow-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($pending_transactions)): ?>
                            <tr><td colspan="5" class="py-6 text-center text-gray-500">Aucune demande en attente.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pending_transactions as $t): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($t['username']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $t['transaction_type'] === 'loan' ? 'text-blue-600' : 'text-green-600'; ?>"><?php echo $t['transaction_type'] === 'loan' ? 'Prêt' : 'Remboursement'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold"><?php echo number_format($t['amount'], 2, ',', ' '); ?> €</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex items-center justify-center gap-2">
                                            <form action="admin.php" method="POST" class="inline">
                                                <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="text-green-600 hover:text-green-900 font-semibold">Approuver</button>
                                            </form>
                                            <span class="text-gray-300">|</span>
                                            <form action="admin.php" method="POST" class="inline">
                                                <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="text-red-600 hover:text-red-900 font-semibold">Rejeter</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>

