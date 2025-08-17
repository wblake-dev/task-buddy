<?php
session_start(); // Start the session

$host = 'localhost';
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password is empty
$dbname = 'task_buddy_db'; // Ensure this matches your database name

try {
    // Create connection using PDO
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['firstName'] . ' ' . $_POST['lastName']; // Combine first and last name for username
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password for security

    $email = $_POST['email']; // Get email from form

    // Check if passwords match
    if ($_POST['password'] !== $_POST['confirmPassword']) {
        $_SESSION['error'] = "Passwords do not match!";
        header("Location: register.php");
        exit();
    }

    // Insert user into the database using prepared statements
    $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    
    if ($stmt->execute()) {
        // Get the last inserted user ID
        $userId = $conn->lastInsertId();

        // Create a corresponding entry in the user_profiles table
        $sqlProfile = "INSERT INTO user_profiles (user_id, first_name, last_name, email) VALUES (:user_id, :first_name, :last_name, :email)";
        $stmtProfile = $conn->prepare($sqlProfile);
        $stmtProfile->bindParam(':user_id', $userId);
        $stmtProfile->bindParam(':first_name', $_POST['firstName']);
        $stmtProfile->bindParam(':last_name', $_POST['lastName']);
        $stmtProfile->bindParam(':email', $email);

        if ($stmtProfile->execute()) {
            $_SESSION['registration_success'] = "Registration successful! Please login to continue.";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['error'] = "Error creating profile: " . $stmtProfile->errorInfo()[2];
            header("Location: register.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Error: " . $stmt->errorInfo()[2];
        header("Location: register.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Task Buddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .form-input {
            transition: all 0.3s ease;
        }
        .form-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .register-card {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
        }
        .btn-register {
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="register-card rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <a href="../index.php" class="inline-block">
                <h1 class="text-3xl font-bold text-gray-800">
                    <span class="text-blue-600">Task</span><span class="text-yellow-500">Buddy</span>
                </h1>
            </a>
            <p class="text-gray-600 mt-2">Create your account and start earning</p>
        </div>

        <!-- Error Message -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                <span class="text-red-700"><?php echo $_SESSION['error']; ?></span>
            </div>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <!-- Registration Form -->
        <form class="space-y-6" method="post" action="">
            <!-- Name Fields -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="firstName" class="block text-sm font-medium text-gray-700 mb-1">
                        First Name <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="firstName" name="firstName" required
                            class="form-input pl-10 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="John">
                    </div>
                </div>
                <div>
                    <label for="lastName" class="block text-sm font-medium text-gray-700 mb-1">
                        Last Name <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="lastName" name="lastName" required
                            class="form-input pl-10 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Doe">
                    </div>
                </div>
            </div>

            <!-- Email Field -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email Address <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="email" id="email" name="email" required
                        class="form-input pl-10 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="john@example.com">
                </div>
            </div>

            <!-- Password Fields -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="password" id="password" name="password" required
                            class="form-input pl-10 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="••••••••">
                    </div>
                </div>
                <div>
                    <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-1">
                        Confirm Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="password" id="confirmPassword" name="confirmPassword" required
                            class="form-input pl-10 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="••••••••">
                    </div>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input type="checkbox" id="agreeTerms" required
                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                </div>
                <label for="agreeTerms" class="ml-2 block text-sm text-gray-700">
                    I agree to the <a href="#" class="text-blue-600 hover:text-blue-800">Terms of Service</a> and
                    <a href="#" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>
                    <span class="text-red-500">*</span>
                </label>
            </div>

            <!-- Register Button -->
            <button type="submit" class="btn-register w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Create Account
            </button>
        </form>

        <!-- Login Link -->
        <div class="text-center mt-6">
            <p class="text-sm text-gray-600">
                Already have an account? 
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-800">
                    Sign in here
                </a>
            </p>
        </div>
    </div>
</body>
</html>
