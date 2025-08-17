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

// Fetch survey ID from the URL
$survey_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch questions for the selected survey
$query = "SELECT * FROM questions WHERE survey_id = :survey_id ORDER BY order_num ASC"; // Order by order_num
$stmt = $conn->prepare($query);
$stmt->execute(['survey_id' => $survey_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Participate in Survey</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        #messageBox {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            font-size: 1.25rem;
            color: #333;
            display: none;
            z-index: 1000;
        }
    </style>
</head>
<body class="bg-blue-100">
    <div class="flex h-screen">
        <main class="flex-1 p-6 overflow-y-auto">
            <h1 class="text-3xl font-semibold text-indigo-600 mb-8">Participate in Survey</h1>
            <form id="surveyForm" class="bg-white rounded-lg shadow-md p-6 space-y-6">
                <?php foreach ($questions as $question): ?>
                    <div class="mb-6 rounded-lg p-4 bg-blue-50">
                        <label class="block text-lg font-semibold text-blue-800 mb-3"><?php echo htmlspecialchars($question['question_text']); ?></label>
                        <input type="hidden" name="questions[<?php echo $question['id']; ?>][question_id]" value="<?php echo $question['id']; ?>" />
                        <?php if ($question['question_type'] == 'multiple choice'): ?>
                            <?php
                            $optionsQuery = "SELECT * FROM options WHERE question_id = :question_id";
                            $optionsStmt = $conn->prepare($optionsQuery);
                            $optionsStmt->execute(['question_id' => $question['id']]);
                            $options = $optionsStmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <?php foreach ($options as $option): ?>
                                <div class="flex items-center py-2">
                                    <input type="radio" id="option_<?php echo $option['id']; ?>" name="answers[<?php echo $question['id']; ?>]" value="<?php echo htmlspecialchars($option['option_text']); ?>" class="mr-2 text-blue-500 focus:ring-blue-500 h-5 w-5" />
                                    <label for="option_<?php echo $option['id']; ?>" class="text-blue-700"><?php echo htmlspecialchars($option['option_text']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <input type="text" name="answers[<?php echo $question['id']; ?>]" placeholder="Your answer" class="border-blue-300 focus:ring-blue-500 focus:border-blue-500" />
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>" />
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-300 ease-in-out">
                    Submit Answers
                </button>
            </form>
        </main>
    </div>

    <div id="messageBox"></div>

    <script>
        document.getElementById('surveyForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);

            fetch('submit_survey.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                const messageBox = document.getElementById('messageBox');
                messageBox.textContent = data.message;
                messageBox.style.display = 'block';

                setTimeout(() => {
                    messageBox.style.display = 'none';
                    if (data.success) {
                        window.location.href = 'dashboard.php';
                    }
                }, 5000);
            })
            .catch(error => {
                const messageBox = document.getElementById('messageBox');
                messageBox.textContent = 'An error occurred. Please try again.';
                messageBox.style.display = 'block';
                setTimeout(() => {
                    messageBox.style.display = 'none';
                }, 5000);
            });
        });
    </script>
</body>
</html>
