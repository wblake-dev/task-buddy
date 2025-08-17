<?php
session_start(); // Start the session
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view this page.");
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
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch user profile data
$userId = $_SESSION['user_id']; // Use the actual user session ID
$sql = "SELECT * FROM user_profiles WHERE user_id = :user_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$userProfile = $stmt->fetch(PDO::FETCH_ASSOC);
if ($userProfile === false) {
    die("No user profile found for user ID: " . htmlspecialchars($userId));
}

// Handle form submission for updating profile
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sqlUpdate = "UPDATE user_profiles SET first_name = :first_name, last_name = :last_name, email = :email, mobile_number = :mobile_number, age = :age, address = :address, state = :state, zip_code = :zip_code, city = :city, country = :country WHERE user_id = :user_id";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    
    $stmtUpdate->bindParam(':first_name', $_POST['first_name']);
    $stmtUpdate->bindParam(':last_name', $_POST['last_name']);
    $stmtUpdate->bindParam(':email', $_POST['email']);
    $stmtUpdate->bindParam(':mobile_number', $_POST['mobile_number']);
    $stmtUpdate->bindParam(':age', $_POST['age']);
    $stmtUpdate->bindParam(':address', $_POST['address']);
    $stmtUpdate->bindParam(':state', $_POST['state']);
    $stmtUpdate->bindParam(':zip_code', $_POST['zip_code']);
    $stmtUpdate->bindParam(':city', $_POST['city']);
    $stmtUpdate->bindParam(':country', $_POST['country']);
    $stmtUpdate->bindParam(':user_id', $userId);
    
    if ($stmtUpdate->execute()) {
        echo "Profile updated successfully!";
    } else {
        echo "Error updating profile: " . $stmtUpdate->errorInfo()[2];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Profile Setting - Task Buddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
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
                        <a href="../user/profile.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg bg-blue-700">
                            <i class="fas fa-user"></i>
                            <span class="font-medium">Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="../surveys/survey.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-poll"></i>
                            <span class="font-medium">Start Survey</span>
                        </a>
                    </li>
                    <li>
                        <a href="../withdrawals/withdraw.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
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
            <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-md p-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Profile Setting</h1>
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="firstName" class="block text-gray-700 font-semibold mb-2">First Name <span class="text-red-500">*</span></label>
                        <input type="text" id="firstName" name="first_name" value="<?php echo htmlspecialchars($userProfile['first_name']); ?>" placeholder="First Name" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                    </div>
                    <div>
                        <label for="lastName" class="block text-gray-700 font-semibold mb-2">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" id="lastName" name="last_name" value="<?php echo htmlspecialchars($userProfile['last_name']); ?>" placeholder="Last Name" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                    </div>
                    <div>
                        <label for="email" class="block text-gray-700 font-semibold mb-2">Email Address <span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userProfile['email']); ?>" placeholder="Email Address" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                    </div>
                    <div>
                        <label for="mobileNumber" class="block text-gray-700 font-semibold mb-2">Mobile Number</label>
                        <input type="text" id="mobileNumber" name="mobile_number" value="<?php echo htmlspecialchars($userProfile['mobile_number']); ?>" placeholder="Mobile Number" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                    </div>
                    <div>
                        <label for="age" class="block text-gray-700 font-semibold mb-2">Age</label>
                        <input type="text" id="age" name="age" value="<?php echo htmlspecialchars($userProfile['age']); ?>" placeholder="Age" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                    </div>
                    <div>
                        <label for="address" class="block text-gray-700 font-semibold mb-2">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($userProfile['address']); ?>" placeholder="Address" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                    </div>
                    <div>
                        <label for="state" class="block text-gray-700 font-semibold mb-2">State</label>
                        <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($userProfile['state']); ?>" placeholder="State" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                    </div>
                    <div>
                        <label for="zipCode" class="block text-gray-700 font-semibold mb-2">Zip Code</label>
                        <input type="text" id="zipCode" name="zip_code" value="<?php echo htmlspecialchars($userProfile['zip_code']); ?>" placeholder="Zip Code" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                    </div>
                    <div>
                        <label for="city" class="block text-gray-700 font-semibold mb-2">City</label>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($userProfile['city']); ?>" placeholder="City" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                    </div>
                    <div>
                        <label for="country" class="block text-gray-700 font-semibold mb-2">Country</label>
                        <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($userProfile['country']); ?>" placeholder="Country" class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-700 text-blue-700 font-bold py-3 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-400 transition-colors">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
