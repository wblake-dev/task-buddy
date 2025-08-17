<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "task_buddy_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch admin name
    $stmtAdmin = $conn->prepare("SELECT admin_name FROM admins WHERE id = :id");
    $stmtAdmin->execute(['id' => $_SESSION['admin_id']]);
    $adminData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
    $admin_name = $adminData['admin_name'] ?? "Admin";

    // Fetch surveys with statistics
    $stmt = $conn->query("
        SELECT 
            s.id,
            s.title,
            s.created_at,
            s.status,
            s.reward,
            a.admin_name as creator_name,
            COUNT(DISTINCT sr.id) as total_responses,
            COUNT(DISTINCT sr.user_id) as unique_respondents
        FROM surveys s
        LEFT JOIN admins a ON s.created_by = a.id
        LEFT JOIN survey_responses sr ON s.id = sr.survey_id
        GROUP BY s.id, s.title, s.created_at, s.status, s.reward, a.admin_name
        ORDER BY s.created_at DESC
    ");
    $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['survey_id'])) {
    $surveyId = $_POST['survey_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE surveys SET status = 'active' WHERE id = :id");
    } else if ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE surveys SET status = 'rejected' WHERE id = :id");
    } else if ($action === 'delete') {
        // Delete related survey responses first
        $stmt = $conn->prepare("DELETE FROM survey_responses WHERE survey_id = :id");
        $stmt->execute(['id' => $surveyId]);

        // Now delete the survey
        $stmt = $conn->prepare("DELETE FROM surveys WHERE id = :id");
    }

    $stmt->execute(['id' => $surveyId]);
    header("Location: manage_surveys.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Surveys - TaskBuddy Admin</title>
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
                        <a href="admindashboard.php"
                            class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_users.php"
                            class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
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
                        <a href="manage_surveys.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-700">
                            <i class="fas fa-poll"></i>
                            <span>Manage Surveys</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_withdrawals.php"
                            class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Withdrawals</span>
                        </a>
                    </li>
                    <li>
                        <a href="create_new_admin.php"
                            class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-user-plus"></i>
                            <span>Add Admin</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="mt-auto">
                <a href="admin_login.php?logout=1"
                    class="flex items-center gap-3 p-3 rounded-lg hover:bg-red-600 transition-colors">
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
                    <h1 class="text-2xl font-semibold text-gray-800">Manage Surveys</h1>
                    <div class="flex items-center gap-4">
                        <a href="create_survey.php" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Create New Survey
                        </a>
                        <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white">
                            <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Surveys Table -->
            <div class="max-w-7xl mx-auto p-6">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Survey</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Creator</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Responses</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($surveys as $survey): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div
                                                    class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-poll text-indigo-600"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($survey['title']); ?></div>
                                                    <div class="text-sm text-gray-500">Created
                                                        <?php echo date('M d, Y', strtotime($survey['created_at'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($survey['creator_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo number_format($survey['total_responses']); ?> responses</div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo number_format($survey['unique_respondents']); ?> unique
                                                respondents</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php $status = $survey['status'] ?? 'unknown'; ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php
                                                switch ($status) {
                                                    case 'active':
                                                        echo 'bg-green-100 text-green-800';
                                                        break;
                                                    case 'pending':
                                                        echo 'bg-yellow-100 text-yellow-800';
                                                        break;
                                                    case 'rejected':
                                                        echo 'bg-red-100 text-red-800';
                                                        break;
                                                    default:
                                                        echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php if ($survey['status'] === 'pending'): ?>
                                                <form method="POST" class="inline-block">
                                                    <input type="hidden" name="survey_id" value="<?php echo $survey['id']; ?>">
                                                    <button type="submit" name="action" value="approve"
                                                        class="text-green-600 hover:text-green-900 mr-4">Approve</button>
                                                    <button type="submit" name="action" value="reject"
                                                        class="text-red-600 hover:text-red-900">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="view_survey.php?id=<?php echo $survey['id']; ?>"
                                                class="text-blue-600 hover:text-blue-900 ml-4">View Details</a>
                                            <a href="edit_survey.php?id=<?php echo $survey['id']; ?>"
                                                class="text-yellow-600 hover:text-yellow-900 ml-4">Edit</a>
                                            <form method="POST" class="inline-block ml-4"
                                                onsubmit="return confirm('Are you sure you want to delete this survey?');">
                                                <input type="hidden" name="survey_id" value="<?php echo $survey['id']; ?>">
                                                <button type="submit" name="action" value="delete"
                                                    class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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