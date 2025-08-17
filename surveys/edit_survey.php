<?php
session_start();

// Database connection using PDO
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "task_buddy_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get survey ID from query parameter
$survey_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($survey_id <= 0) {
    die("Invalid survey ID.");
}

// Handle form submission to update survey and questions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $reward = $_POST['reward'] ?? 0;
    $questions = $_POST['questions'] ?? [];

    // Update survey title, description, reward
    $updateSurveyStmt = $conn->prepare("UPDATE surveys SET title = :title, description = :description, reward = :reward WHERE id = :id");
    $updateSurveyStmt->execute(['title' => $title, 'description' => $description, 'reward' => $reward, 'id' => $survey_id]);

    // Delete existing questions and options for this survey
    $deleteOptionsQuery = "DELETE o FROM options o JOIN questions q ON o.question_id = q.id WHERE q.survey_id = :survey_id";
    $stmtDeleteOptions = $conn->prepare($deleteOptionsQuery);
    $stmtDeleteOptions->execute(['survey_id' => $survey_id]);

    $deleteQuestionsQuery = "DELETE FROM questions WHERE survey_id = :survey_id";
    $stmtDeleteQuestions = $conn->prepare($deleteQuestionsQuery);
    $stmtDeleteQuestions->execute(['survey_id' => $survey_id]);

    // Insert updated questions and options
    $order_num = 1;
    foreach ($questions as $question) {
        $question_text = $question['text'] ?? '';
        $question_type = $question['type'] ?? 'text';
        $correct_answer = $question['correct_answer'] ?? null;

        $insertQuestionQuery = "INSERT INTO questions (survey_id, question_text, question_type, correct_answer, order_num) VALUES (:survey_id, :question_text, :question_type, :correct_answer, :order_num)";
        $stmtInsertQuestion = $conn->prepare($insertQuestionQuery);
        $stmtInsertQuestion->execute([
            'survey_id' => $survey_id,
            'question_text' => $question_text,
            'question_type' => $question_type,
            'correct_answer' => $correct_answer,
            'order_num' => $order_num
        ]);
        $question_id = $conn->lastInsertId();

        if ($question_type === 'multiple choice' && isset($question['options'])) {
            foreach ($question['options'] as $option) {
                $option_text = $option['option_text'] ?? '';
                $is_correct = isset($option['is_correct']) && $option['is_correct'] ? 1 : 0;

                $insertOptionQuery = "INSERT INTO options (question_id, option_text, is_correct) VALUES (:question_id, :option_text, :is_correct)";
                $stmtInsertOption = $conn->prepare($insertOptionQuery);
                $stmtInsertOption->execute([
                    'question_id' => $question_id,
                    'option_text' => $option_text,
                    'is_correct' => $is_correct
                ]);
            }
        }
        $order_num++;
    }

    // Redirect back to manage_surveys.php with success message
    header("Location: manage_surveys.php?message=Survey+updated+successfully");
    exit;
}

// Fetch survey details
$surveyStmt = $conn->prepare("SELECT * FROM surveys WHERE id = :id");
$surveyStmt->execute(['id' => $survey_id]);
$survey = $surveyStmt->fetch(PDO::FETCH_ASSOC);

if (!$survey) {
    die("Survey not found.");
}

// Fetch questions and options for the survey
$questionsStmt = $conn->prepare("SELECT * FROM questions WHERE survey_id = :survey_id ORDER BY order_num ASC");
$questionsStmt->execute(['survey_id' => $survey_id]);
$questions = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($questions as &$question) {
    if ($question['question_type'] === 'multiple choice') {
        $optionsStmt = $conn->prepare("SELECT * FROM options WHERE question_id = :question_id");
        $optionsStmt->execute(['question_id' => $question['id']]);
        $question['options'] = $optionsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
unset($question);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Survey</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .question-card {
            transition: all 0.3s ease;
        }
        .question-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <header class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-indigo-700">Task Buddy</h1>
                    <p class="text-gray-500">Edit your survey with ease</p>
                </div>
            </div>
        </header>

        <main>
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-edit text-indigo-500 mr-3"></i> Edit Survey
                </h2>
                
                <form method="POST" action="" id="surveyForm">
                    <div class="mb-8">
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Survey Title*</label>
                            <input type="text" id="title" name="title" required
                                 class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                 placeholder="Enter survey title" value="<?php echo htmlspecialchars($survey['title']); ?>">
                        </div>
                        
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea id="description" name="description" rows="3"
                                 class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                 placeholder="Describe the purpose of this survey"><?php echo htmlspecialchars($survey['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-6">
                            <label for="reward" class="block text-sm font-medium text-gray-700 mb-1">Reward Amount*</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500">$</span>
                                </div>
                                <input type="number" id="reward" name="reward" step="0.01" required
                                     class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                     placeholder="0.00" value="<?php echo htmlspecialchars($survey['reward']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-8">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-medium text-gray-800 flex items-center">
                                <i class="fas fa-question-circle text-indigo-500 mr-2"></i> Survey Questions
                            </h3>
                            <button type="button" onclick="addQuestion()"
                                 class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center">
                                <i class="fas fa-plus mr-2"></i> Add Question
                            </button>
                        </div>
                        
                        <div id="questions" class="space-y-4">
                            <!-- Questions will be dynamically added here -->
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="resetForm()"
                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition">
                            Reset
                        </button>
                        <button type="submit"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <template id="questionTemplate">
        <div class="question-card bg-white border border-gray-200 rounded-lg p-4 fade-in">
            <div class="flex justify-between items-start mb-3">
                <h4 class="font-medium text-gray-700">Question <span class="question-number">1</span></h4>
                <button type="button" onclick="removeQuestion(this)" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Question Text*</label>
                <input type="text" name="questions[0][text]" required
                     class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                     placeholder="Enter your question">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Question Type*</label>
                    <select name="questions[0][type]"
                         class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="multiple choice">Multiple Choice</option>
                        <option value="text">Text Answer</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correct Answer</label>
                    <input type="text" name="questions[0][correct_answer]"
                         class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                         placeholder="Enter correct answer (optional)">
                </div>
            </div>
            
            <div class="options-container mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Options (for Multiple Choice)</label>
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <input type="text" name="questions[0][options][0][option_text]" required
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-indigo-500 transition"
                            placeholder="Option 1">
                        <input type="checkbox" name="questions[0][options][0][is_correct]" value="1"
                            class="h-5 w-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                        <label class="text-sm font-medium text-gray-700">Correct</label>
                        <button type="button" onclick="addOption(this)" class="text-green-500 hover:text-green-700">
                            <i class="fas fa-plus-circle"></i>
                        </button>
                    </div>
                </div>
            </div>
            

            <input type="hidden" name="questions[0][order_num]" value="1" class="order-input">
        </div>
    </template>

    <script>
        let questionCount = 0;

        // Add a new question
        function addQuestion(data = null) {
            const questionsDiv = document.getElementById('questions');
            const template = document.getElementById('questionTemplate');
            const newQuestion = template.content.cloneNode(true);

            // Update question number and inputs
            const questionNumber = questionCount + 1;
            newQuestion.querySelector('.question-number').textContent = questionNumber;

            // Update input names and values
            const inputs = newQuestion.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                const name = input.getAttribute('name').replace('[0]', `[${questionCount}]`);
                input.setAttribute('name', name);
                if (data) {
                    if (input.name.endsWith('[text]')) {
                        input.value = data.text || '';
                    } else if (input.name.endsWith('[type]')) {
                        input.value = data.type || 'multiple choice';
                    } else if (input.name.endsWith('[correct_answer]')) {
                        input.value = data.correct_answer || '';
                    } else if (input.name.endsWith('[order_num]')) {
                        input.value = questionNumber;
                    }
                } else {
                    input.value = '';
                }
            });

            // Handle options for multiple choice
            const optionsContainer = newQuestion.querySelector('.options-container');
            if (optionsContainer) {
                const optionsDiv = optionsContainer.querySelector('div.space-y-2');
                optionsDiv.innerHTML = '';
                if (data && data.type === 'multiple choice' && data.options) {
                    data.options.forEach((option, index) => {
                        const optionDiv = document.createElement('div');
                        optionDiv.className = 'flex items-center space-x-2';
                        optionDiv.innerHTML = `
                            <input type="text" name="questions[${questionCount}][options][${index}][option_text]" required
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-indigo-500 transition"
                                placeholder="Option ${index + 1}" value="${option.option_text || ''}">
                            <input type="checkbox" name="questions[${questionCount}][options][${index}][is_correct]" value="1"
                                class="h-5 w-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" ${option.is_correct ? 'checked' : ''}>
                            <label class="text-sm font-medium text-gray-700">Correct</label>
                            <button type="button" onclick="removeOption(this)" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                        optionsDiv.appendChild(optionDiv);
                    });
                } else {
                    // Add default option input
                    const optionDiv = document.createElement('div');
                    optionDiv.className = 'flex items-center space-x-2';
                    optionDiv.innerHTML = `
                        <input type="text" name="questions[${questionCount}][options][0][option_text]" required
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-indigo-500 transition"
                            placeholder="Option 1">
                        <input type="checkbox" name="questions[${questionCount}][options][0][is_correct]" value="1"
                            class="h-5 w-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                        <label class="text-sm font-medium text-gray-700">Correct</label>
                        <button type="button" onclick="addOption(this)" class="text-green-500 hover:text-green-700">
                            <i class="fas fa-plus-circle"></i>
                        </button>
                    `;
                    optionsDiv.appendChild(optionDiv);
                }
            }

            questionsDiv.appendChild(newQuestion);
            questionCount++;
        }

        // Remove a question
        function removeQuestion(button) {
            const questionCard = button.closest('.question-card');
            questionCard.remove();
            updateQuestionNumbers();
        }

        // Update question numbers and input names after removal
        function updateQuestionNumbers() {
            const questions = document.querySelectorAll('.question-card');
            questions.forEach((question, index) => {
                question.querySelector('.question-number').textContent = index + 1;
                question.querySelector('input.order-input').value = index + 1;
                const inputs = question.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    const newName = name.replace(/\[\d+\]/, `[${index}]`);
                    input.setAttribute('name', newName);
                });
            });
            questionCount = questions.length;
        }

        // Reset the entire form
        function resetForm() {
            if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
                document.getElementById('surveyForm').reset();
                document.getElementById('questions').innerHTML = '';
                questionCount = 0;
                addQuestion();
            }
        }

        // Add option input
        function addOption(buttonElement) {
            const optionsContainer = buttonElement.closest('.options-container');
            const optionsDiv = optionsContainer.querySelector('div.space-y-2');
            const questionIndex = optionsContainer.closest('.question-card').querySelector('input, select, textarea').name.match(/\[(\d+)\]/)[1];
            const optionCount = optionsDiv.children.length;

            const optionDiv = document.createElement('div');
            optionDiv.className = 'flex items-center space-x-2';
            optionDiv.innerHTML = `
                <input type="text" name="questions[${questionIndex}][options][${optionCount}][option_text]" required
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-indigo-500 transition"
                    placeholder="Option ${optionCount + 1}">
                <input type="checkbox" name="questions[${questionIndex}][options][${optionCount}][is_correct]" value="1"
                    class="h-5 w-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                <label class="text-sm font-medium text-gray-700">Correct</label>
                <button type="button" onclick="removeOption(this)" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            optionsDiv.appendChild(optionDiv);
        }

        // Remove option input
        function removeOption(buttonElement) {
            const optionDiv = buttonElement.closest('.flex.items-center.space-x-2');
            optionDiv.remove();
        }

        // Initialize form with existing data
        document.addEventListener('DOMContentLoaded', () => {
            const existingQuestions = <?php echo json_encode($questions); ?>;
            if (existingQuestions.length > 0) {
                existingQuestions.forEach(q => {
                    addQuestion(q);
                });
            } else {
                addQuestion();
            }
        });
    </script>
</body>
</html>