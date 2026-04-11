<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Messages - MindLink";
$extra_css = '/assets/css/messages.css';

require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$current_user_id = (int) $_SESSION['user_id'];
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedConversationId = isset($_GET['conversation_id']) && is_numeric($_GET['conversation_id'])
    ? (int) $_GET['conversation_id']
    : 0;

$startChatUserId = isset($_GET['user_id']) && is_numeric($_GET['user_id'])
    ? (int) $_GET['user_id']
    : 0;

/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
| Change this path to match your real upload folder.
|--------------------------------------------------------------------------
*/
define('PROFILE_PICTURE_BASE', '/uploads/profile_pictures/');

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function shortPreview($text, $length = 55) {
    $text = trim((string)$text);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . '...';
}

function displayUserLabel($email) {
    $email = trim((string)$email);
    if ($email === '') {
        return 'User';
    }

    if (strpos($email, '@') !== false) {
        return explode('@', $email)[0];
    }

    return $email;
}

function buildProfilePictureUrl($filename) {
    $filename = trim((string)$filename);

    if ($filename === '') {
        return '';
    }

    // If already a full URL (external image), return as-is
    if (strpos($filename, 'http://') === 0 || strpos($filename, 'https://') === 0) {
        return $filename;
    }

    // If already an absolute path starting with /
    if (strpos($filename, '/') === 0) {
        // Check if file exists on server
        $serverPath = $_SERVER['DOCUMENT_ROOT'] . $filename;
        if (file_exists($serverPath)) {
            return $filename;
        }
        return ''; // File doesn't exist, return empty to trigger fallback
    }

    // Build the full path
    $urlPath = rtrim(PROFILE_PICTURE_BASE, '/') . '/' . ltrim($filename, '/');
    $serverPath = $_SERVER['DOCUMENT_ROOT'] . $urlPath;

    // Only return the URL if the file actually exists
    if (file_exists($serverPath)) {
        return $urlPath;
    }

    return ''; // Return empty to trigger SVG fallback
}

/*
|--------------------------------------------------------------------------
| 1. Auto-create or fetch private conversation
|--------------------------------------------------------------------------
*/
if ($startChatUserId > 0 && $startChatUserId !== $current_user_id) {
    $checkUserSql = "SELECT user_id FROM Users WHERE user_id = ? LIMIT 1";
    $checkUserStmt = $conn->prepare($checkUserSql);

    if (!$checkUserStmt) {
        die("Target user check failed: " . $conn->error);
    }

    $checkUserStmt->bind_param("i", $startChatUserId);
    $checkUserStmt->execute();
    $checkUserResult = $checkUserStmt->get_result();
    $targetUserExists = $checkUserResult->fetch_assoc();
    $checkUserStmt->close();

    if ($targetUserExists) {
        $findPrivateSql = "
            SELECT c.conversation_id
            FROM Conversations c
            JOIN ConversationParticipants cp1 ON c.conversation_id = cp1.conversation_id
            JOIN ConversationParticipants cp2 ON c.conversation_id = cp2.conversation_id
            WHERE c.type = 'private'
              AND cp1.user_id = ?
              AND cp2.user_id = ?
            LIMIT 1
        ";

        $findPrivateStmt = $conn->prepare($findPrivateSql);

        if (!$findPrivateStmt) {
            die("Find private conversation failed: " . $conn->error);
        }

        $findPrivateStmt->bind_param("ii", $current_user_id, $startChatUserId);
        $findPrivateStmt->execute();
        $findPrivateResult = $findPrivateStmt->get_result();

        if ($existingConversation = $findPrivateResult->fetch_assoc()) {
            $selectedConversationId = (int) $existingConversation['conversation_id'];
        } else {
            $insertConversationSql = "
                INSERT INTO Conversations (type, project_id, created_at)
                VALUES ('private', NULL, NOW())
            ";

            if (!$conn->query($insertConversationSql)) {
                die("Create private conversation failed: " . $conn->error);
            }

            $newConversationId = (int) $conn->insert_id;

            $insertParticipantsSql = "
                INSERT INTO ConversationParticipants (conversation_id, user_id, joined_at)
                VALUES (?, ?, NOW()), (?, ?, NOW())
            ";

            $insertParticipantsStmt = $conn->prepare($insertParticipantsSql);

            if (!$insertParticipantsStmt) {
                die("Insert private participants failed: " . $conn->error);
            }

            $insertParticipantsStmt->bind_param(
                "iiii",
                $newConversationId, $current_user_id,
                $newConversationId, $startChatUserId
            );
            $insertParticipantsStmt->execute();
            $insertParticipantsStmt->close();

            $selectedConversationId = $newConversationId;
        }

        $findPrivateStmt->close();

        $redirectUrl = "/pages/messages.php?conversation_id=" . $selectedConversationId;
        if ($searchTerm !== '') {
            $redirectUrl .= "&search=" . urlencode($searchTerm);
        }

        header("Location: " . $redirectUrl);
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| 2. Handle sending message
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['conversation_id'])) {
    $messageText = trim($_POST['message']);
    $conversationId = (int) $_POST['conversation_id'];

    $accessSql = "
        SELECT 1
        FROM ConversationParticipants
        WHERE conversation_id = ? AND user_id = ?
        LIMIT 1
    ";
    $accessStmt = $conn->prepare($accessSql);

    if (!$accessStmt) {
        die("Conversation access check failed: " . $conn->error);
    }

    $accessStmt->bind_param("ii", $conversationId, $current_user_id);
    $accessStmt->execute();
    $accessResult = $accessStmt->get_result();
    $allowedToSend = $accessResult->fetch_assoc();
    $accessStmt->close();

    if ($messageText !== '' && $allowedToSend) {
        $insertSql = "
            INSERT INTO Messages (conversation_id, sender_id, content, flagged_phone, sent_at)
            VALUES (?, ?, ?, 0, NOW())
        ";
        $insertStmt = $conn->prepare($insertSql);

        if (!$insertStmt) {
            die("Insert message prepare failed: " . $conn->error);
        }

        $insertStmt->bind_param("iis", $conversationId, $current_user_id, $messageText);
        $insertStmt->execute();
        $insertStmt->close();

        $redirectUrl = "/pages/messages.php?conversation_id=" . $conversationId;
        if ($searchTerm !== '') {
            $redirectUrl .= "&search=" . urlencode($searchTerm);
        }

        header("Location: " . $redirectUrl);
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| 3. Left panel conversations
|--------------------------------------------------------------------------
*/
$conversations = [];

$sqlConversations = "
    SELECT 
        c.conversation_id,
        c.type,
        c.project_id,
        c.created_at,
        p.title AS project_title
    FROM Conversations c
    JOIN ConversationParticipants cp ON c.conversation_id = cp.conversation_id
    LEFT JOIN Projects p ON c.project_id = p.project_id
    WHERE cp.user_id = ?
    ORDER BY c.created_at DESC
";

$stmtConversations = $conn->prepare($sqlConversations);

if (!$stmtConversations) {
    die("Conversations query prepare failed: " . $conn->error);
}

$stmtConversations->bind_param("i", $current_user_id);
$stmtConversations->execute();
$resultConversations = $stmtConversations->get_result();

while ($row = $resultConversations->fetch_assoc()) {
    $row['display_title'] = 'Conversation';
    $row['display_subtitle'] = '';
    $row['preview'] = 'No messages yet';
    $row['preview_time'] = '';
    $row['avatar'] = '';

    if ($row['type'] === 'project' && !empty($row['project_title'])) {
        $row['display_title'] = $row['project_title'];
        $row['display_subtitle'] = 'Project Group';
        $row['avatar'] = '';
    } elseif ($row['type'] === 'private') {
        $otherSql = "
            SELECT u.user_id, u.email, u.profile_picture
            FROM ConversationParticipants cp
            JOIN Users u ON cp.user_id = u.user_id
            WHERE cp.conversation_id = ?
              AND cp.user_id != ?
            LIMIT 1
        ";
        $otherStmt = $conn->prepare($otherSql);

        if ($otherStmt) {
            $otherStmt->bind_param("ii", $row['conversation_id'], $current_user_id);
            $otherStmt->execute();
            $otherResult = $otherStmt->get_result();

            if ($otherUser = $otherResult->fetch_assoc()) {
                $row['display_title'] = displayUserLabel($otherUser['email']);
                $row['display_subtitle'] = 'Direct Message';
                $row['avatar'] = buildProfilePictureUrl($otherUser['profile_picture']);
            } else {
                $row['display_title'] = 'Direct Message';
                $row['display_subtitle'] = 'Private Chat';
                $row['avatar'] = '';
            }

            $otherStmt->close();
        }
    }

    $previewSql = "
        SELECT m.content, m.sent_at, m.sender_id
        FROM Messages m
        WHERE m.conversation_id = ?
        ORDER BY m.sent_at DESC
        LIMIT 1
    ";
    $previewStmt = $conn->prepare($previewSql);

    if ($previewStmt) {
        $previewStmt->bind_param("i", $row['conversation_id']);
        $previewStmt->execute();
        $previewResult = $previewStmt->get_result();

        if ($previewRow = $previewResult->fetch_assoc()) {
            $prefix = ((int) $previewRow['sender_id'] === $current_user_id) ? 'You: ' : '';
            $row['preview'] = $prefix . $previewRow['content'];
            $row['preview_time'] = $previewRow['sent_at'];
        }

        $previewStmt->close();
    }

    $conversations[] = $row;
}

$stmtConversations->close();

/*
|--------------------------------------------------------------------------
| 4. Search filter
|--------------------------------------------------------------------------
*/
if ($searchTerm !== '') {
    $filtered = [];
    $needle = mb_strtolower($searchTerm);

    foreach ($conversations as $conversation) {
        $haystack1 = mb_strtolower($conversation['display_title']);
        $haystack2 = mb_strtolower($conversation['display_subtitle']);
        $haystack3 = mb_strtolower($conversation['preview']);

        if (
            mb_strpos($haystack1, $needle) !== false ||
            mb_strpos($haystack2, $needle) !== false ||
            mb_strpos($haystack3, $needle) !== false
        ) {
            $filtered[] = $conversation;
        }
    }

    $conversations = $filtered;
}

/*
|--------------------------------------------------------------------------
| 5. Selected conversation
|--------------------------------------------------------------------------
*/
$messages = [];
$selectedConversationTitle = "Select a conversation";
$selectedConversationSubTitle = "Choose a conversation from the left panel";
$selectedProjectId = null;
$selectedConversationType = '';

if ($selectedConversationId > 0) {
    $membershipSql = "
        SELECT 1
        FROM ConversationParticipants
        WHERE conversation_id = ? AND user_id = ?
        LIMIT 1
    ";
    $membershipStmt = $conn->prepare($membershipSql);

    if (!$membershipStmt) {
        die("Membership check failed: " . $conn->error);
    }

    $membershipStmt->bind_param("ii", $selectedConversationId, $current_user_id);
    $membershipStmt->execute();
    $membershipResult = $membershipStmt->get_result();
    $hasAccess = $membershipResult->fetch_assoc();
    $membershipStmt->close();

    if ($hasAccess) {
        $conversationSql = "
            SELECT 
                c.conversation_id,
                c.type,
                c.project_id,
                p.title AS project_title
            FROM Conversations c
            LEFT JOIN Projects p ON c.project_id = p.project_id
            WHERE c.conversation_id = ?
            LIMIT 1
        ";

        $conversationStmt = $conn->prepare($conversationSql);

        if (!$conversationStmt) {
            die("Conversation header query failed: " . $conn->error);
        }

        $conversationStmt->bind_param("i", $selectedConversationId);
        $conversationStmt->execute();
        $conversationResult = $conversationStmt->get_result();

        if ($selectedConversation = $conversationResult->fetch_assoc()) {
            $selectedConversationType = $selectedConversation['type'];
            $selectedProjectId = $selectedConversation['project_id'];

            if ($selectedConversation['type'] === 'project' && !empty($selectedConversation['project_title'])) {
                $selectedConversationTitle = $selectedConversation['project_title'];
                $selectedConversationSubTitle = 'Project Group';
            } elseif ($selectedConversation['type'] === 'private') {
                $otherHeaderSql = "
                    SELECT u.email
                    FROM ConversationParticipants cp
                    JOIN Users u ON cp.user_id = u.user_id
                    WHERE cp.conversation_id = ?
                      AND cp.user_id != ?
                    LIMIT 1
                ";
                $otherHeaderStmt = $conn->prepare($otherHeaderSql);

                if ($otherHeaderStmt) {
                    $otherHeaderStmt->bind_param("ii", $selectedConversationId, $current_user_id);
                    $otherHeaderStmt->execute();
                    $otherHeaderResult = $otherHeaderStmt->get_result();

                    if ($otherHeaderUser = $otherHeaderResult->fetch_assoc()) {
                        $selectedConversationTitle = displayUserLabel($otherHeaderUser['email']);
                        $selectedConversationSubTitle = 'Direct Message';
                    } else {
                        $selectedConversationTitle = 'Direct Message';
                        $selectedConversationSubTitle = 'Private Chat';
                    }

                    $otherHeaderStmt->close();
                }
            }
        }

        $conversationStmt->close();

        $sqlMessages = "
            SELECT 
                m.message_id,
                m.conversation_id,
                m.sender_id,
                m.content,
                m.sent_at,
                u.email AS sender_email,
                u.profile_picture
            FROM Messages m
            JOIN Users u ON m.sender_id = u.user_id
            WHERE m.conversation_id = ?
            ORDER BY m.sent_at ASC
        ";

        $stmtMessages = $conn->prepare($sqlMessages);

        if (!$stmtMessages) {
            die("Messages query prepare failed: " . $conn->error);
        }

        $stmtMessages->bind_param("i", $selectedConversationId);
        $stmtMessages->execute();
        $resultMessages = $stmtMessages->get_result();

        while ($row = $resultMessages->fetch_assoc()) {
            $messages[] = $row;
        }

        $stmtMessages->close();
    } else {
        $selectedConversationId = 0;
    }
}
?>

<div class="messages-layout">

    <div class="chat-list-panel">
        <form class="search-box mb-3" method="GET" action="/pages/messages.php">
            <?php if ($selectedConversationId > 0): ?>
                <input type="hidden" name="conversation_id" value="<?php echo (int) $selectedConversationId; ?>">
            <?php endif; ?>
            <input
                type="text"
                name="search"
                placeholder="Search messages..."
                value="<?php echo safeText($searchTerm); ?>"
            >
        </form>

        <div class="section-label">Conversations</div>

        <?php if (!empty($conversations)): ?>
            <?php foreach ($conversations as $conversation): ?>
                <a href="/pages/messages.php?conversation_id=<?php echo (int) $conversation['conversation_id']; ?><?php echo ($searchTerm !== '') ? '&search=' . urlencode($searchTerm) : ''; ?>"
                   class="chat-item <?php echo ($selectedConversationId == $conversation['conversation_id']) ? 'active-chat' : ''; ?>">

                    <div class="chat-avatar">
                        <?php if (!empty($conversation['avatar'])): ?>
                            <img src="<?php echo safeText($conversation['avatar']); ?>" alt="Avatar" class="avatar-img" onerror="this.style.display='none'; this.parentElement.querySelector('.fallback-avatar').style.display='block';">
                            <svg class="fallback-avatar" style="display:none;" xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24" stroke="#2f3a1c" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="7" r="4"></circle>
                                <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
                            </svg>
                        <?php else: ?>
                            <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24" stroke="#2f3a1c" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="7" r="4"></circle>
                                <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
                            </svg>
                        <?php endif; ?>
                    </div>

                    <div class="chat-item-content">
                        <div class="chat-name"><?php echo safeText($conversation['display_title']); ?></div>

                        <?php if ($conversation['display_subtitle'] !== ''): ?>
                            <div class="chat-subname"><?php echo safeText($conversation['display_subtitle']); ?></div>
                        <?php endif; ?>

                        <div class="chat-preview"><?php echo safeText(shortPreview($conversation['preview'])); ?></div>

                        <?php if (!empty($conversation['preview_time'])): ?>
                            <div class="chat-time"><?php echo safeText($conversation['preview_time']); ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-panel-card">
                <h6>No conversations yet.</h6>
                <p>Start a chat from matches or another user profile.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="chat-panel">
        <div class="chat-header">
            <div class="chat-header-left">
                <h2><?php echo safeText($selectedConversationTitle); ?></h2>
                <p class="chat-subtitle"><?php echo safeText($selectedConversationSubTitle); ?></p>
            </div>

            <div class="chat-header-actions">
                <?php if (!empty($selectedProjectId)): ?>
                    <a href="/pages/project_details.php?project_id=<?php echo (int) $selectedProjectId; ?>" class="btn-outline-olive">View Project</a>
                <?php endif; ?>

                <?php if ($selectedConversationId > 0): ?>
                    <a href="/pages/report.php?conversation_id=<?php echo (int) $selectedConversationId; ?>" class="btn-outline-olive">Report</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-box">
            <div class="message-thread">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                        // Build avatar URL for this message sender
                        $msgAvatarUrl = buildProfilePictureUrl($msg['profile_picture']);
                        ?>
                        <?php if ((int) $msg['sender_id'] === $current_user_id): ?>
                            <div class="message-row message-right">
                                <div class="message-content-wrap">
                                    <div class="message-bubble my-message"><?php echo safeText($msg['content']); ?></div>
                                    <div class="message-meta">You · <?php echo safeText($msg['sent_at']); ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="message-row">
                                <div class="message-left-wrap">
                                    <div class="chat-avatar">
                                        <?php if (!empty($msgAvatarUrl)): ?>
                                            <img src="<?php echo safeText($msgAvatarUrl); ?>" alt="Avatar" class="avatar-img" onerror="this.style.display='none'; this.parentElement.querySelector('.fallback-avatar').style.display='block';">
                                            <svg class="fallback-avatar" style="display:none;" xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24" stroke="#2f3a1c" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="7" r="4"></circle>
                                                <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
                                            </svg>
                                        <?php else: ?>
                                            <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24" stroke="#2f3a1c" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="7" r="4"></circle>
                                                <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </div>

                                    <div class="message-content-wrap">
                                        <div class="message-bubble"><?php echo safeText($msg['content']); ?></div>
                                        <div class="message-meta">
                                            <?php echo safeText(displayUserLabel($msg['sender_email'])); ?> · <?php echo safeText($msg['sent_at']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-chat-card">
                        <h6>Select a conversation</h6>
                        <p>Select a conversation from the left panel to view messages.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($selectedConversationId > 0): ?>
                <form class="message-input-wrap" method="POST" action="">
                    <input type="hidden" name="conversation_id" value="<?php echo (int) $selectedConversationId; ?>">
                    <input type="text" name="message" placeholder="Type a message..." required>
                    <button type="submit" class="btn-olive">Send</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
