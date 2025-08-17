<?php
require_once('../includes/session_helper.php');

// Check if user is logged in and session is valid
isLoggedIn();

require_once('../config/config.php');

// Database connection
$servername = "localhost"; // Change if necessary
$username = "root"; // Change if necessary
$password = ""; // Change if necessary
$dbname = "task_buddy_db"; // Database name

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database $dbname :" . $e->getMessage());
}

// Fetch user data
$user_id = getCurrentUserId();
$username = getCurrentUsername();
$balance = 0.00;
$completed_tasks = 0;
$total_withdrawn = 0.00;
$total_transactions = 0;

$stmt = $conn->prepare("SELECT balance, completed_tasks FROM users WHERE id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    $balance = $result['balance'];
    $completed_tasks = $result['completed_tasks'];
}

// Calculate total withdrawn amount
$stmtWithdrawn = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total_withdrawn FROM transactions WHERE user_id = :user_id AND type = 'withdrawal' AND status = 'approved'");
$stmtWithdrawn->execute(['user_id' => $user_id]);
$withdrawnResult = $stmtWithdrawn->fetch(PDO::FETCH_ASSOC);
if ($withdrawnResult) {
    $total_withdrawn = $withdrawnResult['total_withdrawn'];
}

// Calculate total transactions
$stmtTransactions = $conn->prepare("SELECT COUNT(*) AS total_transactions FROM transactions WHERE user_id = :user_id");
$stmtTransactions->execute(['user_id' => $user_id]);
$transactionsResult = $stmtTransactions->fetch(PDO::FETCH_ASSOC);
if ($transactionsResult) {
    $total_transactions = $transactionsResult['total_transactions'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Task Buddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .sidebar-link {
            transition: all 0.3s ease;
        }

        .sidebar-link:hover {
            transform: translateX(5px);
        }

        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="bg-blue-800 text-white w-64 p-6 shadow-lg">
            <div class="logo text-2xl font-bold mb-8">
                <span class="text-yellow-400">Task</span>Buddy
            </div>
            <nav>
                <ul class="space-y-3">
                    <li>
                        <a href="../dashboard/dashboard.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg bg-blue-700">
                            <i class="fas fa-home"></i>
                            <span class="font-medium">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="../user/profile.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-user"></i>
                            <span class="font-medium">Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="../surveys/survey.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-poll"></i>
                            <span class="font-medium">Start Survey</span>
                        </a>
                    </li>
                    <li>
                        <a href="../withdrawals/withdraw.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-money-bill-wave"></i>
                            <span class="font-medium">Withdraw Money</span>
                        </a>
                    </li>
                    <li>
                        <a href="../withdrawals/withdraw_history.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-history"></i>
                            <span class="font-medium">Withdraw History</span>
                        </a>
                    </li>
                    <li>
                        <a href="../tickets/open_ticket.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-ticket-alt"></i>
                            <span class="font-medium">Open Ticket</span>
                        </a>
                    </li>
                    <li>
                        <a href="../tickets/ticket_history.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-list"></i>
                            <span class="font-medium">Ticket History</span>
                        </a>
                    </li>
                    <li>
                        <a href="../user/auth/change_password.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-key"></i>
                            <span class="font-medium">Change Password</span>
                        </a>
                    </li>
                    <li>
                        <a href="../user/auth/logout.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-red-600">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="font-medium">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-7xl mx-auto">
                <!-- Welcome Section -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Welcome back,
                        <?php echo htmlspecialchars($username); ?>!
                    </h1>
                    <p class="text-gray-600 mt-2">Here's what's happening with your account today.</p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Balance Card -->
                    <div class="card bg-white p-6 rounded-xl shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-500 text-sm font-medium">Current Balance</h3>
                            <i class="fas fa-wallet text-blue-500 text-xl"></i>
                        </div>
                        <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($balance, 2); ?></p>
                        <div class="mt-2 text-sm text-green-500">
                            <i class="fas fa-arrow-up"></i>
                            <span>Available for withdrawal</span>
                        </div>
                    </div>

                    <!-- Completed Tasks Card -->
                    <div class="card bg-white p-6 rounded-xl shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-500 text-sm font-medium">Completed Tasks</h3>
                            <i class="fas fa-tasks text-green-500 text-xl"></i>
                        </div>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $completed_tasks; ?></p>
                        <div class="mt-2 text-sm text-gray-500">
                            <span>Total surveys completed</span>
                        </div>
                    </div>

                    <!-- Total Withdrawn Card -->
                    <div class="card bg-white p-6 rounded-xl shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-500 text-sm font-medium">Total Withdrawn</h3>
                            <i class="fas fa-money-check-alt text-purple-500 text-xl"></i>
                        </div>
                        <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($total_withdrawn, 2); ?>
                        </p>
                        <div class="mt-2 text-sm text-gray-500">
                            <span>Lifetime earnings</span>
                        </div>
                    </div>

                    <!-- Total Transactions Card -->
                    <div class="card bg-white p-6 rounded-xl shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-500 text-sm font-medium">Total Transactions</h3>
                            <i class="fas fa-exchange-alt text-orange-500 text-xl"></i>
                        </div>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $total_transactions; ?></p>
                        <div class="mt-2 text-sm text-gray-500">
                            <span>All-time transactions</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Start Survey Card -->
                    <div class="card bg-white p-6 rounded-xl shadow-md">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Ready to Earn?</h3>
                        <p class="text-gray-600 mb-4">Take surveys and start earning money right away!</p>
                        <a href="../surveys/survey.php"
                            class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-play"></i>
                            Start Survey
                        </a>
                    </div>

                    <!-- Withdraw Card -->
                    <div class="card bg-white p-6 rounded-xl shadow-md">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Withdraw Your Earnings</h3>
                        <p class="text-gray-600 mb-4">Get your money whenever you want!</p>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <a href="../withdrawals/withdraw.php"
                                class="inline-flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-money-bill-wave"></i>
                                Withdraw Now
                            </a>
                            <a href="../withdrawals/withdraw_history.php"
                                class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-history"></i>
                                View History
                            </a>
                        </div>
                    </div>

                    <!-- Support Ticket Card -->
                    <div class="card bg-white p-6 rounded-xl shadow-md">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Need Help?</h3>
                        <p class="text-gray-600 mb-4">Our support team is here to assist you!</p>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <a href="../tickets/open_ticket.php"
                                class="inline-flex items-center gap-2 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                                <i class="fas fa-ticket-alt"></i>
                                Open Ticket
                            </a>
                            <a href="../tickets/ticket_history.php"
                                class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                                <i class="fas fa-list"></i>
                                View Tickets
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>