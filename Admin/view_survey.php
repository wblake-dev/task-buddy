<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "task_buddy_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $survey_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($survey_id <= 0) {
        die("Invalid survey ID.");
    }

    $stmt = $conn->prepare("SELECT * FROM surveys WHERE id = :id");
    $stmt->execute(['id' => $survey_id]);
    $survey = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$survey) {
        die("Survey not found.");
    }

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>View Survey Details - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-md mt-10">
        <h1 class="text-3xl font-bold mb-4">Survey Details</h1>
        <div class="mb-4">
            <span class="font-semibold">Title:</span> <?php echo htmlspecialchars($survey['title']); ?>
        </div>
        <div class="mb-4">
            <span class="font-semibold">Created At:</span> <?php echo date('F j, Y, g:i a', strtotime($survey['created_at'])); ?>
        </div>
        <div class="mb-4">
            <span class="font-semibold">Description:</span>
            <p class="mt-2 text-gray-700"><?php echo nl2br(htmlspecialchars($survey['description'] ?? 'No description available.')); ?></p>
        </div>
        <a href="manage_surveys.php" class="inline-block mt-6 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Back to Manage Surveys</a>
    </div>
</body>
</html>
