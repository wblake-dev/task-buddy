<?php
require_once('../includes/session_helper.php');

// Check if user is logged in and session is valid
isLoggedIn();

require_once('../user/db.php');

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn = getDBConnection();

        $survey_id = intval($_POST['survey_id'] ?? 0);
        $user_id = getCurrentUserId();

        if ($survey_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid survey ID.']);
            exit;
        }

        // Check if the user has already participated in the survey
        $checkStmt = $conn->prepare("SELECT * FROM survey_responses WHERE user_id = :user_id AND survey_id = :survey_id");
        $checkStmt->execute(['user_id' => $user_id, 'survey_id' => $survey_id]);
        $existingParticipation = $checkStmt->fetch();

        if ($existingParticipation) {
            echo json_encode(['success' => false, 'message' => 'You have already participated in this survey.']);
            exit;
        }

        // Collect response data
        $responseData = [];
        if (isset($_POST['answers']) && is_array($_POST['answers'])) {
            foreach ($_POST['answers'] as $question_id => $answer) {
                $responseData[$question_id] = $answer;
            }
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
        $rewardRow = $rewardStmt->fetch();

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

        echo json_encode([
            'success' => true,
            'message' => "Thank you for participating! You have been rewarded $" . number_format($rewardAmount, 2),
            'reward' => $rewardAmount
        ]);

    } catch (PDOException $e) {
        error_log("Survey submission error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while submitting your response.']);
    } catch (Exception $e) {
        error_log("Unexpected error in submit_survey.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}
?>