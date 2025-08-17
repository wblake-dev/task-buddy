<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'task_buddy_db';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_name = $_POST['admin_name'] ?? '';
    $admin_password_plain = $_POST['admin_password'] ?? '';

    if (empty($admin_name) || empty($admin_password_plain)) {
        $message = 'Please provide both admin name and password.';
    } else {
        $conn = new mysqli($host, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $admin_password_hashed = password_hash($admin_password_plain, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO admins (admin_name, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $admin_name, $admin_password_hashed);

        if ($stmt->execute()) {
            $message = "Admin user '$admin_name' created successfully.";
        } else {
            $message = "Error creating admin user: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create New Admin - Task Buddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
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
                        <a href="admindashboard.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
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
                        <a href="create_new_admin.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-700 text-white">
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
                <div class="max-w-7xl mx-auto">
                    <h1 class="text-2xl font-semibold text-gray-800">Create New Admin</h1>
                </div>
            </div>

            <!-- Content -->
            <div class="max-w-2xl mx-auto p-6">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center mb-6">
                        <i class="fas fa-user-plus text-blue-500 text-2xl mr-3"></i>
                        <h2 class="text-xl font-semibold text-gray-800">Add New Administrator</h2>
                    </div>

                    <?php if ($message): ?>
                        <div class="mb-6 p-4 rounded-lg <?php echo strpos($message, 'successfully') !== false ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                            <div class="flex items-center">
                                <i class="fas <?php echo strpos($message, 'successfully') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="create_new_admin.php" class="space-y-6">
                        <div>
                            <label for="admin_name" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2"></i>Admin Username
                            </label>
                            <input type="text" id="admin_name" name="admin_name" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                placeholder="Enter admin username" required />
                        </div>

                        <div>
                            <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lock mr-2"></i>Password
                            </label>
                            <input type="password" id="admin_password" name="admin_password" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                placeholder="Enter secure password" required />
                            <p class="text-sm text-gray-500 mt-1">Choose a strong password for security</p>
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition flex items-center justify-center">
                                <i class="fas fa-plus mr-2"></i>Create Admin
                            </button>
                            <a href="admindashboard.php" class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition flex items-center justify-center">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>