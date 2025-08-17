<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/auth/login.php");
    exit();
}

// Database connection using PDO
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "task_buddy_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $user_id = $_SESSION['user_id'];

    // Initialize optional fields
    $bank_name = null;
    $account_number = null;
    $bkash_number = null;

    if ($payment_method === 'Bank Transfer') {
        $account_number = $_POST['account_number'] ?? null;
    } elseif ($payment_method === 'Bkash') {
        $bkash_number = $_POST['bkash_number'] ?? null;
    }
    
    // Validate amount
    if ($amount <= 0) {
        $error = "Amount must be greater than 0";
    } else {
        // Check user's balance
        $stmt = $conn->prepare("SELECT balance FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $balance = $stmt->fetchColumn();
        
        if ($amount > $balance) {
            $error = "Insufficient balance";
        } else {
            // Insert withdrawal request with additional fields
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, payment_method, bank_name, account_number, bkash_number, status) VALUES (:user_id, :amount, 'withdrawal', :payment_method, :bank_name, :account_number, :bkash_number, 'pending')");
            $stmt->execute([
                'user_id' => $user_id,
                'amount' => $amount,
                'payment_method' => $payment_method,
                'bank_name' => $bank_name,
                'account_number' => $account_number,
                'bkash_number' => $bkash_number
            ]);
            
            $success = "Withdrawal request submitted successfully";
        }
    }
}

// Fetch user's current balance
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$balance = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Money - Task Buddy</title>
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
                        <a href="../dashboard/dashboard.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-home"></i>
                            <span class="font-medium">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="../surveys/survey.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-poll"></i>
                            <span class="font-medium">Start Survey</span>
                        </a>
                    </li>
                    <li>
                        <a href="../withdrawals/withdraw.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg bg-blue-700">
                            <i class="fas fa-money-bill-wave"></i>
                            <span class="font-medium">Withdraw Money</span>
                        </a>
                    </li>
                    <li>
                        <a href="../withdrawals/withdraw_history.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-history"></i>
                            <span class="font-medium">Withdraw History</span>
                        </a>
                    </li>
                    <li>
                        <a href="../tickets/open_ticket.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-ticket-alt"></i>
                            <span class="font-medium">Open Ticket</span>
                        </a>
                    </li>
                    <li>
                        <a href="../tickets/ticket_history.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-list"></i>
                            <span class="font-medium">Ticket History</span>
                        </a>
                    </li>
                    <li>
                        <a href="../user/auth/change_password.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-key"></i>
                            <span class="font-medium">Change Password</span>
                        </a>
                    </li>
                    <li>
                        <a href="../user/auth/logout.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-red-600">
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
                    <h1 class="text-3xl font-bold text-gray-800">Withdraw Money</h1>
                    <p class="text-gray-600 mt-2">Get your earnings whenever you want!</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Current Balance Card -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">Current Balance</h2>
                            <p class="text-gray-600 mt-1">Available for withdrawal</p>
                        </div>
                        <div class="text-2xl font-bold text-blue-600">$<?php echo number_format($balance, 2); ?></div>
                    </div>
                </div>

                <!-- Withdrawal Form -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700">Amount to Withdraw</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">$</span>
                                </div>
                        <input type="number" step="0.01" name="amount" id="amount" required
                            class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md"
                            placeholder="0.00">
                            </div>
                        </div>

                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                            <select name="payment_method" id="payment_method" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Bkash">Bkash</option>
                            </select>
                        </div>

                        <div id="bank-details" class="hidden">
                            <label for="account_number" class="block text-sm font-medium text-gray-700 mt-4">Account Number</label>
                            <input type="text" name="account_number" id="account_number" class="mt-1 block w-full border border-gray-300 rounded-md p-2" placeholder="Enter your account number">
                        </div>

                        <div id="bkash-details" class="hidden">
                            <label for="bkash_number" class="block text-sm font-medium text-gray-700 mt-4">Bkash Number</label>
                            <input type="text" name="bkash_number" id="bkash_number" class="mt-1 block w-full border border-gray-300 rounded-md p-2" placeholder="Enter your Bkash number">
                        </div>

                        <script>
                            const paymentMethodSelect = document.getElementById('payment_method');
                            const bankDetails = document.getElementById('bank-details');
                            const bkashDetails = document.getElementById('bkash-details');

                            function togglePaymentDetails() {
                                if (paymentMethodSelect.value === 'Bank Transfer') {
                                    bankDetails.classList.remove('hidden');
                                    bkashDetails.classList.add('hidden');
                                } else if (paymentMethodSelect.value === 'Bkash') {
                                    bkashDetails.classList.remove('hidden');
                                    bankDetails.classList.add('hidden');
                                } else {
                                    bankDetails.classList.add('hidden');
                                    bkashDetails.classList.add('hidden');
                                }
                            }

                            paymentMethodSelect.addEventListener('change', togglePaymentDetails);

                            // Initialize on page load
                            togglePaymentDetails();
                        </script>

                        <div>
                            <button type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Submit Withdrawal Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
