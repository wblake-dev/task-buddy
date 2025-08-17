<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['admin_id'])) {
    die("Admin not logged in. Please login first.");
}

echo "<!-- Debug: Session admin_id: " . $_SESSION['admin_id'] . " -->";

try {
    $conn = new PDO("mysql:host=localhost;dbname=task_buddy_db", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<!-- Debug: Database connection successful -->";
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle reply submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ticket_id']) && isset($_POST['reply_message'])) {
    $ticketId = intval($_POST['ticket_id']);
    $replyMessage = trim($_POST['reply_message']);

    if ($ticketId > 0 && $replyMessage !== '') {
        // For admin replies, we'll use a special approach:
        // Store admin_id in user_id field but mark is_admin = 1
        // The query will handle the join properly
        $adminId = $_SESSION['admin_id'];
        
        // First, temporarily disable foreign key checks
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $sql = "INSERT INTO ticket_replies (ticket_id, user_id, reply_message, replied_at, is_admin) VALUES (:ticket_id, :user_id, :reply_message, NOW(), 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':ticket_id', $ticketId);
        $stmt->bindParam(':user_id', $adminId);
        $stmt->bindParam(':reply_message', $replyMessage);
        $stmt->execute();
        
        // Re-enable foreign key checks
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");

        error_log("Inserted admin reply: ticket_id=$ticketId, admin_id=$adminId, is_admin=1, message=" . $replyMessage);

        header("Location: support_ticket.php?ticket_id=" . $ticketId);
        exit;
    } else {
        die("Invalid ticket ID or empty reply message.");
    }
}

// Fetch tickets or ticket details
$ticketId = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : null;

if ($ticketId && $ticketId > 0) {
    // Fetch ticket details and replies
    $ticketQuery = "SELECT t.*, u.username FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = :ticket_id";
    $stmtTicket = $conn->prepare($ticketQuery);
    $stmtTicket->execute([':ticket_id' => $ticketId]);
    $ticket = $stmtTicket->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die("Ticket not found.");
    }

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
    $stmtReplies->execute([':ticket_id' => $ticketId]);
    $replies = $stmtReplies->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fetch all tickets
    $ticketsQuery = "SELECT t.id, t.subject, t.priority, t.message, t.created_at, u.username FROM tickets t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC";
    $stmtTickets = $conn->query($ticketsQuery);
    $tickets = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Support Tickets - Task Buddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        #replies-container {
            overflow-y: visible;
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
                        <a href="admindashboard.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_users.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
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
                        <a href="manage_surveys.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-poll"></i>
                            <span>Manage Surveys</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_withdrawals.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Withdrawals</span>
                        </a>
                    </li>
                    <li>
                        <a href="create_new_admin.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-user-plus"></i>
                            <span>Add Admin</span>
                        </a>
                    </li>
                    <li>
                        <a href="support_ticket.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-700 text-white">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Support Ticket</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="mt-auto">
                <a href="admin_login.php?logout=1" class="flex items-center gap-3 p-3 rounded-lg hover:bg-red-600 transition-colors">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-6 max-w-7xl flex flex-col gap-6">
            <?php if ($ticketId && $ticket): ?>
                <a href="support_ticket.php" class="text-blue-600 hover:underline font-semibold mb-4">&larr; Back to Tickets</a>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Ticket: <?php echo htmlspecialchars($ticket['subject'] ?? ''); ?></h2>
                <p class="text-gray-700 mb-1"><strong>User:</strong> <?php echo htmlspecialchars($ticket['username'] ?? ''); ?></p>
                <p class="text-gray-700 mb-4"><strong>Priority:</strong> <?php echo htmlspecialchars($ticket['priority'] ?? ''); ?></p>
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

                <div>
                    <form method="POST" id="reply-form">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticketId; ?>" />
                        <textarea name="reply_message" placeholder="Write your reply here..." required class="w-full p-3 border border-gray-300 rounded-md resize-y"></textarea>
                        <button type="submit" class="mt-3 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">Send Reply</button>
                    </form>
                </div>
            <?php else: ?>
                <h1 class="text-3xl font-bold mb-6">Support Tickets</h1>
                <?php if ($tickets): ?>
                    <table class="min-w-full bg-white rounded shadow overflow-hidden">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">ID</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Subject</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">User</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Priority</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Created At</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($ticket['id'] ?? ''); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($ticket['subject'] ?? ''); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($ticket['username'] ?? ''); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($ticket['priority'] ?? ''); ?></td>
                                    <td class="py-2 px-4"><?php echo htmlspecialchars($ticket['created_at'] ?? ''); ?></td>
                                    <td class="py-2 px-4">
                                        <a href="support_ticket.php?ticket_id=<?php echo $ticket['id']; ?>" class="text-blue-600 hover:underline">View & Reply</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No tickets found.</p>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>

