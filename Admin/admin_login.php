<?php
session_start();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

if (isset($_SESSION['admin_id'])) {
    header("Location: admindashboard.php");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "task_buddy_db";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_username = trim($_POST['admin_username'] ?? '');
    $admin_password = trim($_POST['admin_password'] ?? '');

    if ($admin_username === '' || $admin_password === '') {
        $error = "Please enter both username and password.";
    } else {
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $conn->prepare("SELECT id, password FROM admins WHERE admin_name = :admin_name LIMIT 1");
            $stmt->execute([':admin_name' => $admin_username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            // Debug output
            error_log("Admin login attempt: username = " . $admin_username);
            if ($admin) {
                error_log("Admin record found. Stored hash: " . $admin['password']);
                $verify = password_verify($admin_password, $admin['password']);
                error_log("Password verify result: " . ($verify ? "true" : "false"));
            } else {
                error_log("No admin record found for username: " . $admin_username);
            }

            if ($admin && password_verify($admin_password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin_username;
                header("Location: admindashboard.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Login - Task Buddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h1 class="text-2xl font-semibold mb-6 text-center">Admin Login</h1>
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="admin_login.php" class="space-y-4">
            <div>
                <label for="admin_username" class="block mb-1 font-medium">Username</label>
                <input type="text" id="admin_username" name="admin_username" required class="w-full border border-gray-300 rounded px-3 py-2" />
            </div>
            <div>
                <label for="admin_password" class="block mb-1 font-medium">Password</label>
                <input type="password" id="admin_password" name="admin_password" required class="w-full border border-gray-300 rounded px-3 py-2" />
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 font-semibold">Login</button>
        </form>
        <div class="mt-4 text-center">
            <a href="../user/index.php" class="text-blue-600 hover:underline">Back to Home</a>
        </div>
    </div>
</body>
</html>
