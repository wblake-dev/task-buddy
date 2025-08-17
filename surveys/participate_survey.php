<?php
require_once('../includes/session_helper.php');

// Check if user is logged in and session is valid
isLoggedIn();

require_once('../user/db.php');

$survey_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';
$survey = null;
$existing_response = false;
$questions = [];

if ($survey_id <= 0) {
    $error = "Invalid survey ID provided.";
} else {
    try {
        $conn = getDBConnection();

        // Get survey details
        $stmt = $conn->prepare("SELECT * FROM surveys WHERE id = :survey_id AND status = 'active'");
        $stmt->execute(['survey_id' => $survey_id]);
        $survey = $stmt->fetch();

        if (!$survey) {
            $error = "Survey not found or inactive. Please try another survey.";
        } else {
            // Check if user already completed this survey
            $stmt = $conn->prepare("SELECT id FROM survey_responses WHERE user_id = :user_id AND survey_id = :survey_id");
            $stmt->execute(['user_id' => $_SESSION['user_id'], 'survey_id' => $survey_id]);
            $existing_response = $stmt->fetch();

            if ($existing_response) {
                $error = "You have already completed this survey.";
            } else {
                // Parse questions
                if (isset($survey['questions']) && !empty($survey['questions'])) {
                    $questions = json_decode($survey['questions'], true);
                    if (!is_array($questions) || json_last_error() !== JSON_ERROR_NONE) {
                        $error = "There was an error loading the survey questions. Please try again or contact support.";
                        error_log("JSON decode error for survey {$survey_id}: " . json_last_error_msg());
                        $questions = [];
                    }
                } else {
                    $error = "This survey has no questions available.";
                }
            }
        }

    } catch (PDOException $e) {
        $error = "Database connection error. Please try again later.";
        error_log("Survey load error: " . $e->getMessage());
        $survey = null;
        $questions = [];
    } catch (Exception $e) {
        $error = "An unexpected error occurred. Please try again later.";
        error_log("Unexpected error in participate_survey.php: " . $e->getMessage());
        $survey = null;
        $questions = [];
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$existing_response && $survey && !empty($questions)) {
    try {
        $responses = [];
        $all_questions_answered = true;

        foreach ($questions as $index => $question) {
            $question_key = 'question_' . $index;
            if (isset($_POST[$question_key]) && !empty(trim($_POST[$question_key]))) {
                $responses[$index] = trim($_POST[$question_key]);
            } else {
                $all_questions_answered = false;
                break;
            }
        }

        if (!$all_questions_answered) {
            $error = "Please answer all questions before submitting.";
        } else {
            // Save survey response
            $stmt = $conn->prepare("INSERT INTO survey_responses (survey_id, user_id, response_data) VALUES (:survey_id, :user_id, :response_data)");
            $stmt->execute([
                'survey_id' => $survey_id,
                'user_id' => $_SESSION['user_id'],
                'response_data' => json_encode($responses)
            ]);

            // Update user balance and completed tasks
            $reward_amount = isset($survey['reward']) ? floatval($survey['reward']) : 0;
            if ($reward_amount > 0) {
                $stmt = $conn->prepare("UPDATE users SET balance = balance + :reward, completed_tasks = completed_tasks + 1 WHERE id = :user_id");
                $stmt->execute([
                    'reward' => $reward_amount,
                    'user_id' => $_SESSION['user_id']
                ]);

                // Record transaction
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, status) VALUES (:user_id, :amount, 'earning', 'completed')");
                $stmt->execute([
                    'user_id' => $_SESSION['user_id'],
                    'amount' => $reward_amount
                ]);
            }

            $success = "Survey completed successfully! You earned $" . number_format($reward_amount, 2);
            $existing_response = true; // Prevent form from showing again
        }

    } catch (PDOException $e) {
        $error = "An error occurred while submitting your response. Please try again.";
        error_log("Survey submission error: " . $e->getMessage());
    } catch (Exception $e) {
        $error = "An unexpected error occurred. Please try again.";
        error_log("Unexpected submission error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($survey['title'] ?? 'Survey'); ?> - Task Buddy</title>
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
                        <a href="../dashboard/dashboard.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-home"></i>
                            <span class="font-medium">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="../user/profile.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-user"></i>
                            <span class="font-medium">Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="survey.php" class="sidebar-link flex items-center gap-3 p-3 rounded-lg bg-blue-700">
                            <i class="fas fa-poll"></i>
                            <span class="font-medium">Start Survey</span>
                        </a>
                    </li>
                    <li>
                        <a href="../withdrawals/withdraw.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-money-bill-wave"></i>
                            <span class="font-medium">Withdraw Money</span>
                        </a>
                    </li>
                    <li>
                        <a href="../withdrawals/withdraw_history.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-history"></i>
                            <span class="font-medium">Withdraw History</span>
                        </a>
                    </li>
                    <li>
                        <a href="../tickets/open_ticket.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-ticket-alt"></i>
                            <span class="font-medium">Open Ticket</span>
                        </a>
                    </li>
                    <li>
                        <a href="../tickets/ticket_history.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-list"></i>
                            <span class="font-medium">Ticket History</span>
                        </a>
                    </li>
                    <li>
                        <a href="../user/auth/change_password.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700">
                            <i class="fas fa-key"></i>
                            <span class="font-medium">Change Password</span>
                        </a>
                    </li>
                    <li>
                        <a href="../user/auth/logout.php"
                            class="sidebar-link flex items-center gap-3 p-3 rounded-lg hover:bg-red-600">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="font-medium">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-4xl mx-auto">
                <div class="mb-6">
                    <a href="survey.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Surveys
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                        <div class="mt-2">
                            <a href="survey.php" class="text-blue-600 hover:text-blue-800 underline">← Back to Surveys</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                        role="alert">
                        <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
                        <div class="mt-4">
                            <a href="survey.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                Take Another Survey
                            </a>
                            <a href="../dashboard/dashboard.php"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 ml-2">
                                Go to Dashboard
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$error && !$success && $survey && !empty($questions) && !$existing_response): ?>
                    <div class="bg-white rounded-xl shadow-md p-8">
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-800">
                                <?php echo htmlspecialchars($survey['title'] ?? 'Survey'); ?>
                            </h1>
                            <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($survey['description'] ?? ''); ?></p>
                            <div class="mt-4 p-4 bg-green-50 rounded-lg">
                                <p class="text-green-800 font-semibold">
                                    <i class="fas fa-dollar-sign mr-2"></i>
                                    Reward: $<?php echo number_format($survey['reward'] ?? 0, 2); ?>
                                </p>
                            </div>
                        </div>

                        <?php if (!empty($questions) && is_array($questions)): ?>
                            <form method="POST" class="space-y-6" onsubmit="return validateForm()">
                                <?php foreach ($questions as $index => $question): ?>
                                    <?php
                                    // Handle both 'question' and 'text' field formats
                                    $questionText = '';
                                    if (isset($question['question']) && !empty($question['question'])) {
                                        $questionText = $question['question'];
                                    } elseif (isset($question['text']) && !empty($question['text'])) {
                                        $questionText = $question['text'];
                                    }
                                    ?>
                                    <?php if (!empty($questionText)): ?>
                                        <div class="border-b border-gray-200 pb-6">
                                            <label class="block text-lg font-medium text-gray-700 mb-3">
                                                <?php echo ($index + 1) . '. ' . htmlspecialchars($questionText); ?>
                                                <span class="text-red-500">*</span>
                                            </label>

                                            <?php if (isset($question['type']) && $question['type'] === 'text'): ?>
                                                <textarea name="question_<?php echo $index; ?>" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    rows="4" placeholder="Enter your answer here..." minlength="10"></textarea>

                                            <?php elseif (isset($question['type']) && $question['type'] === 'radio'): ?>
                                                <?php
                                                // Handle simple radio button format
                                                $options = [];
                                                if (isset($question['options']) && is_array($question['options'])) {
                                                    foreach ($question['options'] as $key => $option) {
                                                        if (is_string($option)) {
                                                            $options[] = $option;
                                                        } elseif (is_array($option) && isset($option['option_text'])) {
                                                            $options[] = $option['option_text'];
                                                        }
                                                    }
                                                }
                                                ?>
                                                <?php if (!empty($options)): ?>
                                                    <div class="space-y-3">
                                                        <?php foreach ($options as $option): ?>
                                                            <label class="flex items-center">
                                                                <input type="radio" name="question_<?php echo $index; ?>"
                                                                    value="<?php echo htmlspecialchars($option); ?>" required
                                                                    class="mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                                                <span class="text-lg text-gray-700"><?php echo htmlspecialchars($option); ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-gray-500 italic p-3 bg-gray-100 rounded">
                                                        No options available for this question.
                                                    </div>
                                                <?php endif; ?>

                                            <?php elseif (isset($question['type']) && $question['type'] === 'image_radio'): ?>
                                                <?php
                                                // Handle image radio button format
                                                $options = [];
                                                if (isset($question['options']) && is_array($question['options'])) {
                                                    foreach ($question['options'] as $key => $option) {
                                                        if (is_array($option) && isset($option['option_text'])) {
                                                            $options[] = [
                                                                'text' => $option['option_text'],
                                                                'image' => $option['image_url'] ?? ''
                                                            ];
                                                        }
                                                    }
                                                }
                                                ?>
                                                <?php if (!empty($options)): ?>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                        <?php foreach ($options as $optionIndex => $option): ?>
                                                            <label class="cursor-pointer">
                                                                <input type="radio" name="question_<?php echo $index; ?>"
                                                                    value="<?php echo htmlspecialchars($option['text']); ?>" required
                                                                    class="sr-only peer">
                                                                <div
                                                                    class="border-2 border-gray-200 rounded-lg p-4 hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                                                    <?php if (!empty($option['image'])): ?>
                                                                        <div class="mb-3">
                                                                            <img src="<?php echo htmlspecialchars($option['image']); ?>"
                                                                                alt="<?php echo htmlspecialchars($option['text']); ?>"
                                                                                class="w-full h-32 object-cover rounded-md" loading="lazy"
                                                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                                            <div
                                                                                class="hidden bg-gray-200 h-32 flex items-center justify-center rounded-md">
                                                                                <span class="text-gray-500 text-sm">Image not available</span>
                                                                            </div>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="text-center font-medium text-gray-700">
                                                                        <?php echo htmlspecialchars($option['text']); ?>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-gray-500 italic p-3 bg-gray-100 rounded">
                                                        No image options available for this question.
                                                    </div>
                                                <?php endif; ?>

                                            <?php elseif (isset($question['type']) && ($question['type'] === 'rating' || $question['type'] === 'multiple_choice' || $question['type'] === 'multiple choice')): ?>
                                                <?php
                                                // Handle different option formats
                                                $options = [];
                                                if (isset($question['options']) && is_array($question['options'])) {
                                                    // Standard format: array of strings or objects
                                                    foreach ($question['options'] as $key => $option) {
                                                        if (is_string($option)) {
                                                            $options[] = $option;
                                                        } elseif (is_array($option) && isset($option['option_text'])) {
                                                            $options[] = $option['option_text'];
                                                        }
                                                    }
                                                }
                                                ?>
                                                <?php if (!empty($options)): ?>
                                                    <div class="space-y-2">
                                                        <?php foreach ($options as $option): ?>
                                                            <label class="flex items-center">
                                                                <input type="radio" name="question_<?php echo $index; ?>"
                                                                    value="<?php echo htmlspecialchars($option); ?>" required class="mr-2">
                                                                <span><?php echo htmlspecialchars($option); ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-gray-500 italic p-3 bg-gray-100 rounded">
                                                        No options available for this question.
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="text-gray-500 italic p-3 bg-gray-100 rounded">
                                                    Question type "<?php echo htmlspecialchars($question['type'] ?? 'unknown'); ?>" is not
                                                    supported.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <div class="pt-6">
                                    <button type="submit"
                                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200">
                                        Submit Survey & Earn $<?php echo number_format($survey['reward'] ?? 0, 2); ?>
                                    </button>
                                </div>
                            </form>

                            <script>
                                function validateForm() {
                                    const form = document.querySelector('form');
                                    const requiredFields = form.querySelectorAll('input[required], textarea[required]');
                                    let allValid = true;

                                    requiredFields.forEach(field => {
                                        if (field.type === 'radio') {
                                            const radioGroup = form.querySelectorAll(`input[name="${field.name}"]`);
                                            const isRadioGroupSelected = Array.from(radioGroup).some(radio => radio.checked);
                                            if (!isRadioGroupSelected) {
                                                allValid = false;
                                            }
                                        } else if (!field.value.trim()) {
                                            allValid = false;
                                        }
                                    });

                                    if (!allValid) {
                                        alert('Please answer all required questions before submitting.');
                                        return false;
                                    }

                                    return true;
                                }
                            </script>
                        <?php else: ?>
                            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4"
                                role="alert">
                                <span class="block sm:inline">This survey has no questions available at the moment.</span>
                                <div class="mt-2">
                                    <a href="survey.php" class="text-blue-600 hover:text-blue-800 underline">← Back to
                                        Surveys</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>