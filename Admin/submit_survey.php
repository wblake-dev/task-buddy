<?php
session_start();

// Database connection using PDO
$servername = "localhost"; // Change if necessary
$username = "root"; // Change if necessary
$password = ""; // Change if necessary
$dbname = "task_buddy_db"; // Updated database name

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database $dbname :" . $e->getMessage());
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $survey_id = $_POST['survey_id'];
    $user_id = $_SESSION['user_id'] ?? null; // Use session user_id

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'User not logged in.']);
        exit;
    }

    // Check if the user has already participated in the survey
    $checkStmt = $conn->prepare("SELECT * FROM survey_responses WHERE user_id = :user_id AND survey_id = :survey_id");
    $checkStmt->execute(['user_id' => $user_id, 'survey_id' => $survey_id]);
    $existingParticipation = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingParticipation) {
        echo json_encode(['success' => false, 'message' => 'You have already participated in this survey.']);
        exit; // Stop further execution
    }

    // Collect response data
    $responseData = [];
    foreach ($_POST['answers'] as $question_id => $answer) {
        $responseData[$question_id] = $answer;
    }

    // Insert into survey_responses table
    $stmt = $conn->prepare("INSERT INTO survey_responses (user_id, survey_id, response_data) VALUES (:user_id, :survey_id, :response_data)");
    $stmt->execute([
        'user_id' => $user_id,
        'survey_id' => $survey_id,
        'response_data' => json_encode($responseData)
    ]);

    // Fetch reward amount from surveys table
    $rewardStmt = $conn->prepare("SELECT reward FROM surveys WHERE id = :survey_id AND status = 'active'");
    $rewardStmt->execute(['survey_id' => $survey_id]);
    $rewardRow = $rewardStmt->fetch(PDO::FETCH_ASSOC);

    if (!$rewardRow) {
        echo json_encode(['success' => false, 'message' => 'Survey not found or inactive.']);
        exit;
    }

    $rewardAmount = floatval($rewardRow['reward']);

    // Update user balance and completed tasks
    $updateRewardStmt = $conn->prepare("UPDATE users SET balance = balance + :rewardAmount, completed_tasks = completed_tasks + 1 WHERE id = :user_id");
    $updateRewardStmt->execute(['rewardAmount' => $rewardAmount, 'user_id' => $user_id]);

    // Record transaction
    $transactionStmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, status) VALUES (:user_id, :amount, 'earning', 'completed')");
    $transactionStmt->execute([
        'user_id' => $user_id,
        'amount' => $rewardAmount
    ]);

    $message = "Thank you for participating! You have been rewarded $" . number_format($rewardAmount, 2);
    echo json_encode(['success' => true, 'message' => $message, 'reward' => $rewardAmount]);
    exit;
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}
?>