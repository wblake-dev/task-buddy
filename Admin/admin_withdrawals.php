<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Database connection using PDO
$servername = "localhost"; // Change if necessary
$username = "root"; // Change if necessary
$password = ""; // Change if necessary
$dbname = "task_buddy_db"; // Updated database name

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch admin name from database using session admin_id
    if (isset($_SESSION['admin_id'])) {
        $stmtAdmin = $conn->prepare("SELECT admin_name FROM admins WHERE id = :id");
        $stmtAdmin->execute(['id' => $_SESSION['admin_id']]);
        $adminData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
        if ($adminData && !empty($adminData['admin_name'])) {
            $admin_name = $adminData['admin_name'];
        } else {
            $admin_name = "Admin";
        }
    } else {
        $admin_name = "Admin";
    }
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['transaction_id'])) {
        $action = $_POST['action'];
        $transactionId = (int)$_POST['transaction_id'];

        // Fetch withdrawal amount and user_id for the transaction
        $stmt = $conn->prepare("SELECT amount, user_id FROM transactions WHERE id = :id");
        $stmt->execute(['id' => $transactionId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            // Transaction not found, redirect back
            header("Location: admin_withdrawals.php");
            exit;
        }

        if ($action === 'approve') {
            // Begin transaction
            $conn->beginTransaction();
            try {
                // Check current status of the transaction to avoid double deduction
                $statusCheckStmt = $conn->prepare("SELECT status FROM transactions WHERE id = :id FOR UPDATE");
                $statusCheckStmt->execute(['id' => $transactionId]);
                $currentStatus = $statusCheckStmt->fetchColumn();

                if ($currentStatus === 'pending') {
                    // Check user's current balance
                    $balanceStmt = $conn->prepare("SELECT balance FROM users WHERE id = :user_id FOR UPDATE");
                    $balanceStmt->execute(['user_id' => $transaction['user_id']]);
                    $currentBalance = $balanceStmt->fetchColumn();

                    if ($currentBalance >= $transaction['amount']) {
                        // Update transaction status to approved
                        $updateStmt = $conn->prepare("UPDATE transactions SET status = 'approved' WHERE id = :id");
                        $updateStmt->execute(['id' => $transactionId]);

                        // Deduct amount from user's balance
                        $deductStmt = $conn->prepare("UPDATE users SET balance = balance - :amount WHERE id = :user_id");
                        $deductStmt->execute(['amount' => $transaction['amount'], 'user_id' => $transaction['user_id']]);
                    } else {
                        // Insufficient balance, reject withdrawal
                        $updateStmt = $conn->prepare("UPDATE transactions SET status = 'rejected' WHERE id = :id");
                        $updateStmt->execute(['id' => $transactionId]);
                        error_log("Withdrawal rejected due to insufficient balance for transaction ID: $transactionId");
                    }
                }

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("Error approving withdrawal: " . $e->getMessage());
            }
        } elseif ($action === 'reject') {
            // Update transaction status to rejected
            $updateStmt = $conn->prepare("UPDATE transactions SET status = 'rejected' WHERE id = :id");
            $updateStmt->execute(['id' => $transactionId]);
        }
        // Redirect to avoid form resubmission
        header("Location: admin_withdrawals.php");
        exit;
    }
}

// Fetch pending withdrawal requests
$pendingWithdrawals = [];
try {
    $stmt = $conn->prepare("SELECT t.id, t.user_id, t.amount, t.payment_method, t.bank_name, t.account_number, t.bkash_number, t.status, u.username FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.type = 'withdrawal' AND t.status = 'pending' ORDER BY t.id DESC");
    $stmt->execute();
    $pendingWithdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching pending withdrawals: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Withdrawals - Task Buddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="bg-blue-800 text-white w-64 p-6 hidden md:flex flex-col">
            <div class="logo text-2xl font-bold mb-8">
                <span class="text-yellow-400">Task</span>Buddy
            </div>
            <nav class="flex-1">
                <ul class="space-y-3">
                    <li>
                        <a href="admindashboard.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-700 text-white">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_users.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-users"></i>
                            <span>Manage Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="create_survey.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus-circle"></i>
                            <span>Create Survey</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_surveys.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-poll"></i>
                            <span>Manage Surveys</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_withdrawals.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Withdrawals</span>
                        </a>
                    </li>
                    <li>
                        <a href="create_new_admin.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-user-plus"></i>
                            <span>Add Admin</span>
                        </a>
                    </li>
                    <li>
                        <a href="support_ticket.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Support Ticket</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="mt-auto">
                <a href="admin_login.php?logout=1" class="flex items-center gap-3 p-3 rounded-lg hover:bg-red-600 transition-colors">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-7xl mx-auto">
                <div class="header mb-6">
                    <div class="admin-name font-bold text-gray-800">Welcome, <?php echo htmlspecialchars($admin_name); ?></div>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-4">Pending Withdrawal Requests</h1>
                <?php if (count($pendingWithdrawals) === 0): ?>
                    <p class="text-gray-600">No pending withdrawal requests.</p>
                <?php else: ?>
                    <table class="w-full border-collapse mb-6">
                        <thead>
                            <tr class="bg-gray-100 text-left text-gray-700">
                                <th class="p-3 border-b border-gray-300">ID</th>
                                <th class="p-3 border-b border-gray-300">User</th>
                                <th class="p-3 border-b border-gray-300">Amount</th>
                                <th class="p-3 border-b border-gray-300">Payment Method</th>
                                <th class="p-3 border-b border-gray-300">Details</th>
                                <th class="p-3 border-b border-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingWithdrawals as $withdrawal): ?>
                                <tr class="border-b border-gray-200">
                                    <td class="p-3"><?php echo htmlspecialchars($withdrawal['id']); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($withdrawal['username']); ?></td>
                                    <td class="p-3">$<?php echo number_format($withdrawal['amount'], 2); ?></td>
                                    <td class="p-3"><?php echo htmlspecialchars($withdrawal['payment_method']); ?></td>
                                    <td class="p-3 text-gray-600 text-sm">
                                        <?php
                                        if ($withdrawal['payment_method'] === 'Bank Transfer') {
                                            echo "Bank: " . htmlspecialchars($withdrawal['bank_name']) . "<br>";
                                            echo "Account: " . htmlspecialchars($withdrawal['account_number']);
                                        } elseif ($withdrawal['payment_method'] === 'Bkash') {
                                            echo "Bkash Number: " . htmlspecialchars($withdrawal['bkash_number']);
                                        }
                                        ?>
                                    </td>
                                    <td class="p-3">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($withdrawal['id']); ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="approve-btn bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded mr-2">Approve</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($withdrawal['id']); ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="reject-btn bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
