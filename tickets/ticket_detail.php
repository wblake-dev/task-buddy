<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/auth/login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Database connection using PDO
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "task_buddy_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get ticket ID from GET parameter
$ticketId = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;
if ($ticketId <= 0) {
    die("Invalid ticket ID.");
}

// Verify ticket belongs to logged-in user
$stmt = $conn->prepare("SELECT * FROM tickets WHERE id = :ticket_id AND user_id = :user_id");
$stmt->execute(['ticket_id' => $ticketId, 'user_id' => $userId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ticket) {
    die("Ticket not found or access denied.");
}

// Handle reply submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_message'])) {
    $replyMessage = trim($_POST['reply_message']);
    if ($replyMessage !== '') {
        $sql = "INSERT INTO ticket_replies (ticket_id, user_id, reply_message, replied_at, is_admin) VALUES (:ticket_id, :user_id, :reply_message, NOW(), 0)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'reply_message' => $replyMessage
        ]);
        header("Location: ticket_detail.php?ticket_id=" . $ticketId);
        exit;
    } else {
        $error = "Reply message cannot be empty.";
    }
}

// Fetch replies for the ticket
$repliesQuery = "SELECT tr.*, 
    CASE 
        WHEN tr.is_admin = 1 THEN a.admin_name 
        ELSE u.username 
    END AS username
FROM ticket_replies tr
LEFT JOIN users u ON tr.is_admin = 0 AND tr.user_id = u.id
LEFT JOIN admins a ON tr.is_admin = 1 AND tr.user_id = a.id
WHERE tr.ticket_id = :ticket_id
ORDER BY tr.replied_at ASC";
$stmtReplies = $conn->prepare($repliesQuery);
$stmtReplies->execute(['ticket_id' => $ticketId]);
$replies = $stmtReplies->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ticket Details - Task Buddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        #replies-container {
            max-height: none !important;
            overflow-y: visible !important;
            padding: 1rem;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
        }
        #replies-container > div {
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);
        }
        #replies-container > div.bg-blue-100 {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
        }
        #replies-container > div.bg-green-100 {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
        }
        #replies-container strong {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 700;
            color: #1f2937;
        }
        #replies-container .text-xs {
            font-size: 0.5rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body class="h-full">
    <div class="flex h-full">
        <!-- Sidebar -->
        <aside class="bg-blue-800 text-white w-64 p-6 hidden md:flex flex-col">
            <div class="logo text-2xl font-bold mb-8">
                <span class="text-yellow-400">Task</span>Buddy
            </div>
            <nav class="flex-1">
                <ul class="space-y-3">
                    <li>
                        <a href="../dashboard/dashboard.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="../surveys/survey.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-poll"></i>
                            <span>Start Survey</span>
                        </a>
                    </li>
                    <li>
                        <a href="../withdrawals/withdraw.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Withdraw Money</span>
                        </a>
                    </li>
                    <li>
                        <a href="../withdrawals/withdraw_history.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-history"></i>
                            <span>Withdraw History</span>
                        </a>
                    </li>
                    <li>
                        <a href="open_ticket.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Open Ticket</span>
                        </a>
                    </li>
                    <li>
                        <a href="ticket_history.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-700">
                            <i class="fas fa-list"></i>
                            <span>Ticket History</span>
                        </a>
                    </li>
                    <li>
                        <a href="../user/auth/change_password.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-key"></i>
                            <span>Change Password</span>
                        </a>
                    </li>
                    <li>
                        <a href="../user/auth/logout.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-red-600 transition-colors">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-6 max-w-7xl flex flex-col gap-6">
            <a href="ticket_history.php" class="text-blue-600 hover:underline font-semibold mb-4">&larr; Back to Ticket History</a>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Ticket: <?php echo htmlspecialchars($ticket['subject'] ?? ''); ?></h2>
            <p class="text-gray-700 mb-1"><strong>Priority:</strong> <?php echo htmlspecialchars($ticket['priority'] ?? ''); ?></p>
            <p class="text-gray-700 mb-4"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $ticket['status'])) ?? ''); ?></p>
            <p class="text-gray-700 mb-2"><strong>Message:</strong></p>
            <div class="bg-gray-100 p-4 rounded-md mb-6 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($ticket['message'] ?? '')); ?></div>

            <h3 class="text-xl font-semibold mb-4">Replies</h3>
            <div class="space-y-4 max-h-96 overflow-y-auto mb-6" id="replies-container">
            <?php if (!empty($replies)): ?>
                <?php foreach ($replies as $reply): ?>
                    <?php
                        $isAdmin = isset($reply['is_admin']) && intval($reply['is_admin']) === 1;
                        $username = htmlspecialchars($reply['username'] ?? 'Unknown');
                        $message = nl2br(htmlspecialchars($reply['reply_message'] ?? ''));
                        $timestamp = htmlspecialchars($reply['replied_at'] ?? '');
                    ?>
                    <div class="<?php echo $isAdmin ? 'bg-blue-100' : 'bg-green-100'; ?> p-3 rounded-md">
                        <strong><?php echo $isAdmin ? "Admin ($username)" : $username; ?>:</strong> <?php echo $message; ?>
                        <div class="text-xs text-gray-600 mt-1"><?php echo $timestamp; ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No replies yet.</p>
            <?php endif; ?>
            </div>

            <?php if (isset($error)): ?>
                <p class="text-red-600 mb-4"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <div>
                <form method="POST" id="reply-form">
                    <textarea name="reply_message" placeholder="Write your reply here..." required class="w-full p-3 border border-gray-300 rounded-md resize-y"></textarea>
                    <button type="submit" class="mt-3 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">Send Reply</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
