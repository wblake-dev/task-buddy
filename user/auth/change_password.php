<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once('../db.php');

$error = '';
$success = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        try {
            $conn = getDBConnection();
            
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :user_id");
                $stmt->execute([
                    'password' => $hashed_password,
                    'user_id' => $_SESSION['user_id']
                ]);
                
                $success = "Password changed successfully!";
            } else {
                $error = "Current password is incorrect.";
            }
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again.";
            error_log("Change password error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Task Buddy</title>
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
                        <a href="../../dashboard/dashboard.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-home"></i>
                            <span class="font-medium">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="../profile.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-user"></i>
                            <span class="font-medium">Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="../../surveys/survey.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-poll"></i>
                            <span class="font-medium">Start Survey</span>
                        </a>
                    </li>
                    <li>
                        <a href="../../withdrawals/withdraw.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-money-bill-wave"></i>
                            <span class="font-medium">Withdraw Money</span>
                        </a>
                    </li>
                    <li>
                        <a href="../../withdrawals/withdraw_history.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-history"></i>
                            <span class="font-medium">Withdraw History</span>
                        </a>
                    </li>
                    <li>
                        <a href="../../tickets/open_ticket.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-ticket-alt"></i>
                            <span class="font-medium">Open Ticket</span>
                        </a>
                    </li>
                    <li>
                        <a href="../../tickets/ticket_history.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-list"></i>
                            <span class="font-medium">Ticket History</span>
                        </a>
                    </li>
                    <li>
                        <a href="change_password.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg bg-blue-700">
                            <i class="fas fa-key"></i>
                            <span class="font-medium">Change Password</span>
                        </a>
                    </li>
                    <li>
                        <a href="logout.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-red-600">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="font-medium">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-xl shadow-md p-8">
                    <h1 class="text-3xl font-bold text-gray-800 mb-6">Change Password</h1>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                            <div class="mt-1 relative">
                                <input type="password" id="current_password" name="current_password" required
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <div class="mt-1 relative">
                                <input type="password" id="new_password" name="new_password" required
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Password must be at least 6 characters long.</p>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                            <div class="mt-1 relative">
                                <input type="password" id="confirm_password" name="confirm_password" required
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                        </div>

                        <div>
                            <button type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>