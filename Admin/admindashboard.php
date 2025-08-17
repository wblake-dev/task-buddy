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
        $admin_name = $adminData['admin_name'] ?? "Admin";
    } else {
        $admin_name = "Admin";
    }

    // Fetch statistics
    $stats = [
        'users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'surveys' => $conn->query("SELECT COUNT(*) FROM surveys WHERE status = 'active'")->fetchColumn(),
        'withdrawals' => $conn->query("SELECT COUNT(*) FROM transactions WHERE type = 'withdrawal' AND status = 'pending'")->fetchColumn(),
        'total_amount' => $conn->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'withdrawal' AND status IN ('approved', 'completed')")->fetchColumn()
    ];

    // Fetch recent activities
    $recentActivities = $conn->query("
        SELECT 
            'withdrawal' as type,
            t.created_at as date,
            CONCAT('$', t.amount, ' withdrawal by ', u.username) as description
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.type = 'withdrawal'
        UNION ALL
        SELECT 
            'survey' as type,
            created_at as date,
            CONCAT('New survey created: ', title) as description
        FROM surveys
        ORDER BY date DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle approve/reject actions - moved here to avoid headers sent warning
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['transaction_id'])) {
        $action = $_POST['action'];
        $transactionId = (int)$_POST['transaction_id'];

        if ($action === 'approve') {
            // Update transaction status to approved
            $updateStmt = $conn->prepare("UPDATE transactions SET status = 'approved' WHERE id = :id");
            $updateStmt->execute(['id' => $transactionId]);
        } elseif ($action === 'reject') {
            // Update transaction status to rejected
            $updateStmt = $conn->prepare("UPDATE transactions SET status = 'rejected' WHERE id = :id");
            $updateStmt->execute(['id' => $transactionId]);
        }
        // Redirect to avoid form resubmission
        header("Location: admindashboard.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TaskBuddy</title>
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
        <main class="flex-1 overflow-y-auto">
            <!-- Top Bar -->
            <div class="bg-white shadow-sm p-4">
                <div class="max-w-7xl mx-auto flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-800">Dashboard</h1>
                    <div class="flex items-center gap-4">
                        <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white">
                            <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="max-w-7xl mx-auto p-6">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <!-- Total Users -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-500 text-sm font-medium">Total Users</h3>
                            <div class="text-blue-500 bg-blue-100 rounded-full p-2">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['users']); ?></p>
                        <p class="text-sm text-gray-600 mt-2">Registered users</p>
                    </div>

                    <!-- Active Surveys -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-500 text-sm font-medium">Active Surveys</h3>
                            <div class="text-green-500 bg-green-100 rounded-full p-2">
                                <i class="fas fa-poll"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['surveys']); ?></p>
                        <p class="text-sm text-gray-600 mt-2">Available surveys</p>
                    </div>

                    <!-- Pending Withdrawals -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-500 text-sm font-medium">Pending Withdrawals</h3>
                            <div class="text-yellow-500 bg-yellow-100 rounded-full p-2">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['withdrawals']); ?></p>
                        <p class="text-sm text-gray-600 mt-2">Awaiting approval</p>
                    </div>

                    <!-- Total Amount -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-500 text-sm font-medium">Total Withdrawn</h3>
                            <div class="text-purple-500 bg-purple-100 rounded-full p-2">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($stats['total_amount'], 2); ?></p>
                        <p class="text-sm text-gray-600 mt-2">All time</p>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Activity</h2>
                    <div class="space-y-4">
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                            <div class="<?php echo $activity['type'] === 'withdrawal' ? 'text-yellow-500 bg-yellow-100' : 'text-blue-500 bg-blue-100'; ?> rounded-full p-2">
                                <i class="fas <?php echo $activity['type'] === 'withdrawal' ? 'fa-money-bill-wave' : 'fa-poll'; ?>"></i>
                            </div>
                            <div>
                                <p class="text-gray-800"><?php echo htmlspecialchars($activity['description']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo date('M d, Y H:i', strtotime($activity['date'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        const menuButton = document.querySelector('[data-menu-button]');
        const sidebar = document.querySelector('aside');
        
        if (menuButton) {
            menuButton.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>
