<?php  
/**
 * @file messages.php
 * @brief MindLink – secure and dynamic messaging page
 */

$pageTitle = "Messages - MindLink";
$extra_css = '/assets/css/messages.css';

require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

date_default_timezone_set('Europe/Dublin');

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$selectedConversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
$targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$searchTerm = trim($_GET['search'] ?? '');

$filter = $_GET['filter'] ?? 'all';
$allowedFilters = ['all', 'unread', 'groups'];

if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$errorMessage = '';
$infoMessage = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

define('PROFILE_PICTURE_BASE', '/uploads/profile_pictures/');

/* ---------- Utility ---------- */
function safeText($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function displayUserLabel($email) {
    return ($email && strpos($email, '@') !== false) ? explode('@', $email)[0] : ($email ?: 'User');
}

function buildProfilePictureUrl($f) {
    $f = trim((string)$f);
    if ($f === '') return '';

    if (
        strpos($f, '/') === 0 ||
        strpos($f, 'http://') === 0 ||
        strpos($f, 'https://') === 0
    ) {
        return $f;
    }

    $u = rtrim(PROFILE_PICTURE_BASE, '/') . '/' . ltrim($f, '/');
    $s = $_SERVER['DOCUMENT_ROOT'] . $u;
    return file_exists($s) ? $u : '';
}

function formatTime($dt) {
    $t = strtotime((string)$dt);
    if (!$t) return '';

    $today = strtotime(date('Y-m-d'));
    $d = strtotime(date('Y-m-d', $t));

    if ($d === $today) return 'Today ' . date('H:i', $t);
    if ($d === strtotime('-1 day', $today)) return 'Yesterday ' . date('H:i', $t);

    return date('d M Y H:i', $t);
}

function normalizeConversationType($type) {
    $type = strtolower(trim((string)$type));
    return in_array($type, ['project', 'private'], true) ? $type : 'private';
}

function normalizeUserStatus($status) {
    $status = strtolower(trim((string)$status));
    return $status !== '' ? $status : 'active';
}

function pageExists($filename) {
    return file_exists(__DIR__ . '/' . ltrim($filename, '/'));
}

/*
   Blocks phone numbers and long numeric messages. */
function containsPhoneNumber($text) {
    // Blocks all numbers: short numbers, long numbers, and phone numbers
    return preg_match('/\d/', (string)$text);
}

function jsonResponse($data) {
    if (ob_get_length()) {
        ob_clean();
    }

    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function getOrCreatePrivateConversation(mysqli $conn, int $currentUserId, int $targetUserId): int {
    if ($currentUserId <= 0 || $targetUserId <= 0 || $currentUserId === $targetUserId) {
        return 0;
    }

    $findSql = "
        SELECT c.conversation_id
        FROM Conversations c
        JOIN ConversationParticipants cp1 ON c.conversation_id = cp1.conversation_id
        JOIN ConversationParticipants cp2 ON c.conversation_id = cp2.conversation_id
        WHERE c.type = 'private'
          AND cp1.user_id = ?
          AND cp2.user_id = ?
        LIMIT 1
    ";

    $findStmt = $conn->prepare($findSql);
    if (!$findStmt) {
        return 0;
    }

    $findStmt->bind_param("ii", $currentUserId, $targetUserId);
    $findStmt->execute();
    $existing = $findStmt->get_result()->fetch_assoc();
    $findStmt->close();

    if ($existing && !empty($existing['conversation_id'])) {
        return (int)$existing['conversation_id'];
    }

    $conn->begin_transaction();

    try {
        $insertConversation = $conn->prepare("
            INSERT INTO Conversations (type, created_at)
            VALUES ('private', NOW())
        ");

        if (!$insertConversation) {
            throw new Exception("Could not create conversation.");
        }

        $insertConversation->execute();
        $conversationId = (int)$conn->insert_id;
        $insertConversation->close();

        $insertParticipant = $conn->prepare("
            INSERT INTO ConversationParticipants (conversation_id, user_id)
            VALUES (?, ?)
        ");

        if (!$insertParticipant) {
            throw new Exception("Could not add participants.");
        }

        $insertParticipant->bind_param("ii", $conversationId, $currentUserId);
        $insertParticipant->execute();

        $insertParticipant->bind_param("ii", $conversationId, $targetUserId);
        $insertParticipant->execute();

        $insertParticipant->close();

        $conn->commit();
        return $conversationId;
    } catch (Exception $e) {
        $conn->rollback();
        return 0;
    }
}

/* ---------- Check current user status ---------- */
$currentUserStatus = 'active';
$statusStmt = $conn->prepare("SELECT status FROM Users WHERE user_id = ? LIMIT 1");

if ($statusStmt) {
    $statusStmt->bind_param("i", $currentUserId);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();
    $statusRow = $statusResult ? $statusResult->fetch_assoc() : null;
    $currentUserStatus = normalizeUserStatus($statusRow['status'] ?? 'active');
    $statusStmt->close();
}

$isBlocked = in_array($currentUserStatus, ['suspended', 'reported', 'under_review', 'blocked'], true);

/* ---------- Auto-open/create DM from matches/profile ---------- */
if ($selectedConversationId <= 0 && $targetUserId > 0) {
    if ($targetUserId === $currentUserId) {
        $errorMessage = "You cannot start a direct message with yourself.";
    } else {
        $targetCheck = $conn->prepare("SELECT user_id, status FROM Users WHERE user_id = ? LIMIT 1");

        if ($targetCheck) {
            $targetCheck->bind_param("i", $targetUserId);
            $targetCheck->execute();
            $targetRow = $targetCheck->get_result()->fetch_assoc();
            $targetCheck->close();

            if ($targetRow) {
                $targetStatus = normalizeUserStatus($targetRow['status'] ?? 'active');

                if (in_array($targetStatus, ['suspended', 'reported', 'under_review', 'blocked'], true)) {
                    $errorMessage = "This user is currently unavailable for messaging.";
                } else {
                    $conversationId = getOrCreatePrivateConversation($conn, $currentUserId, $targetUserId);

                    if ($conversationId > 0) {
                        header("Location: /pages/messages.php?conversation_id=" . $conversationId);
                        exit();
                    } else {
                        $errorMessage = "Could not open a private conversation right now.";
                    }
                }
            } else {
                $errorMessage = "Selected user was not found.";
            }
        }
    }
}

/* ---------- Handle sending ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conversation_id'])) {
    $isAjax = !empty($_POST['ajax']);
    $postedToken = $_POST['csrf_token'] ?? '';
    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    $text = trim((string)($_POST['message'] ?? ''));

    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        $errorMessage = "Invalid request token. Please refresh the page and try again.";
    } elseif ($isBlocked) {
        $errorMessage = "Your account is under review. You cannot send messages.";
    } elseif ($conversationId <= 0) {
        $errorMessage = "Invalid conversation.";
    } elseif ($text === '') {
        $errorMessage = "Message cannot be empty.";
    } elseif (containsPhoneNumber($text)) {
        $errorMessage = "Phone numbers or long numbers are not allowed in chat messages.";
    } else {
        $text = mb_substr($text, 0, 2000);

        $participantStmt = $conn->prepare("
            SELECT 1
            FROM ConversationParticipants
            WHERE conversation_id = ? AND user_id = ?
            LIMIT 1
        ");

        if ($participantStmt) {
            $participantStmt->bind_param("ii", $conversationId, $currentUserId);
            $participantStmt->execute();
            $isParticipant = $participantStmt->get_result()->num_rows > 0;
            $participantStmt->close();

            if ($isParticipant) {
                $restrictionStmt = $conn->prepare("
                    SELECT
                        c.type,
                        c.project_id,
                        p.status AS project_status
                    FROM Conversations c
                    LEFT JOIN Projects p ON c.project_id = p.project_id
                    WHERE c.conversation_id = ?
                    LIMIT 1
                ");

                $canSend = true;

                if ($restrictionStmt) {
                    $restrictionStmt->bind_param("i", $conversationId);
                    $restrictionStmt->execute();
                    $restrictionRow = $restrictionStmt->get_result()->fetch_assoc();
                    $restrictionStmt->close();

                    if ($restrictionRow) {
                        $convType = normalizeConversationType($restrictionRow['type'] ?? 'private');
                        $projectStatus = strtolower(trim((string)($restrictionRow['project_status'] ?? '')));

                        if (
                            $convType === 'project' &&
                            in_array($projectStatus, ['suspended', 'reported', 'under_review', 'blocked'], true)
                        ) {
                            $canSend = false;
                            $errorMessage = "This project conversation is currently restricted.";
                        }
                    }
                }

                if ($canSend) {
                    $insertStmt = $conn->prepare("
                        INSERT INTO Messages (conversation_id, sender_id, content, sent_at)
                        VALUES (?, ?, ?, NOW())
                    ");

                    if ($insertStmt) {
                        $insertStmt->bind_param("iis", $conversationId, $currentUserId, $text);
                        $insertStmt->execute();
                        $insertStmt->close();

                        if ($isAjax) {
                            jsonResponse([
                                'success' => true,
                                'content' => nl2br(safeText($text)),
                                'display_time' => 'Just now'
                            ]);
                        }

                        header("Location: /pages/messages.php?conversation_id=" . $conversationId);
                        exit();
                    } else {
                        $errorMessage = "Could not send your message right now.";
                    }
                }
            } else {
                $errorMessage = "You are not allowed to send messages in this conversation.";
            }
        } else {
            $errorMessage = "Could not verify conversation access.";
        }
    }

    if ($isAjax && $errorMessage !== '') {
        jsonResponse([
            'success' => false,
            'message' => $errorMessage
        ]);
    }
}

/* ---------- Load sidebar conversations ---------- */
$conversations = [];

$sql = "
    SELECT
        c.conversation_id,
        c.type,
        c.project_id,
        c.created_at,
        p.title AS project_title,
        (
            SELECT MAX(m.sent_at)
            FROM Messages m
            WHERE m.conversation_id = c.conversation_id
        ) AS last_time,
        (
            SELECT u2.email
            FROM ConversationParticipants cp2
            JOIN Users u2 ON cp2.user_id = u2.user_id
            WHERE cp2.conversation_id = c.conversation_id
              AND cp2.user_id != ?
            LIMIT 1
        ) AS other_user_email,
        (
            SELECT u2.user_id
            FROM ConversationParticipants cp2
            JOIN Users u2 ON cp2.user_id = u2.user_id
            WHERE cp2.conversation_id = c.conversation_id
              AND cp2.user_id != ?
            LIMIT 1
        ) AS other_user_id
    FROM Conversations c
    JOIN ConversationParticipants cp ON c.conversation_id = cp.conversation_id
    LEFT JOIN Projects p ON c.project_id = p.project_id
    WHERE cp.user_id = ?
    ORDER BY last_time DESC, c.created_at DESC
";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("iii", $currentUserId, $currentUserId, $currentUserId);
    $stmt->execute();
    $conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

/* ---------- Filter tabs ---------- */
if ($filter === 'groups') {
    $conversations = array_filter($conversations, function ($conv) {
        return normalizeConversationType($conv['type'] ?? 'private') === 'project';
    });
}

if ($filter === 'unread') {
    $conversations = [];
}

/* ---------- Search filter ---------- */
if ($searchTerm !== '') {
    $needle = mb_strtolower($searchTerm);

    $conversations = array_filter($conversations, function ($conv) use ($needle) {
        $convType = normalizeConversationType($conv['type'] ?? 'private');

        $title = $convType === 'project'
            ? mb_strtolower((string)($conv['project_title'] ?? ''))
            : mb_strtolower(displayUserLabel($conv['other_user_email'] ?? ''));

        return mb_strpos($title, $needle) !== false;
    });
}

/* ---------- Load selected conversation ---------- */
$messages = [];
$title = "Select a conversation";
$subtitle = "Choose a conversation from the left panel";
$type = null;
$info = null;
$hasAccess = false;
$canSendInConversation = false;
$dmOtherUserId = 0;
$projectApplicationsUrl = null;

if ($selectedConversationId > 0) {
    $chk = $conn->prepare("
        SELECT 1
        FROM ConversationParticipants
        WHERE conversation_id = ? AND user_id = ?
        LIMIT 1
    ");

    if ($chk) {
        $chk->bind_param("ii", $selectedConversationId, $currentUserId);
        $chk->execute();
        $hasAccess = $chk->get_result()->num_rows > 0;
        $chk->close();
    }

    if ($hasAccess) {
        $head = $conn->prepare("
            SELECT
                c.type,
                c.project_id,
                p.title AS project_title,
                p.status AS project_status,
                p.owner_id
            FROM Conversations c
            LEFT JOIN Projects p ON c.project_id = p.project_id
            WHERE c.conversation_id = ?
            LIMIT 1
        ");

        if ($head) {
            $head->bind_param("i", $selectedConversationId);
            $head->execute();
            $info = $head->get_result()->fetch_assoc();
            $head->close();
        }

        if ($info) {
            $type = normalizeConversationType($info['type'] ?? 'private');

            if ($type === 'project') {
                $title = $info['project_title'] ?: 'Project Conversation';
                $subtitle = 'Project Group';

                $projectStatus = strtolower(trim((string)($info['project_status'] ?? '')));

                if (in_array($projectStatus, ['suspended', 'reported', 'under_review', 'blocked'], true)) {
                    $infoMessage = "This project conversation is currently restricted.";
                    $canSendInConversation = false;
                } else {
                    $canSendInConversation = !$isBlocked;
                }

                if (pageExists('applications.php') && !empty($info['project_id'])) {
                    $projectApplicationsUrl = '/pages/applications.php?project_id=' . (int)$info['project_id'];
                }
            } else {
                $usr = $conn->prepare("
                    SELECT u.user_id, u.email, u.status
                    FROM ConversationParticipants cp
                    JOIN Users u ON cp.user_id = u.user_id
                    WHERE cp.conversation_id = ? AND cp.user_id != ?
                    LIMIT 1
                ");

                if ($usr) {
                    $usr->bind_param("ii", $selectedConversationId, $currentUserId);
                    $usr->execute();
                    $u = $usr->get_result()->fetch_assoc();
                    $usr->close();

                    if ($u) {
                        $dmOtherUserId = (int)$u['user_id'];
                        $title = displayUserLabel($u['email']);
                        $subtitle = 'Direct Message';

                        $otherStatus = normalizeUserStatus($u['status'] ?? 'active');

                        if (in_array($otherStatus, ['suspended', 'reported', 'under_review', 'blocked'], true)) {
                            $infoMessage = "This user is currently restricted.";
                            $canSendInConversation = false;
                        } else {
                            $canSendInConversation = !$isBlocked;
                        }
                    }
                }
            }
        }

        $ms = $conn->prepare("
            SELECT
                m.sender_id,
                m.content,
                m.sent_at,
                u.email,
                u.profile_picture
            FROM Messages m
            JOIN Users u ON m.sender_id = u.user_id
            WHERE m.conversation_id = ?
            ORDER BY m.sent_at ASC
        ");

        if ($ms) {
            $ms->bind_param("i", $selectedConversationId);
            $ms->execute();
            $messages = $ms->get_result()->fetch_all(MYSQLI_ASSOC);
            $ms->close();
        }
    } else {
        $errorMessage = "You do not have access to this conversation.";
    }
}
?>

<div class="messages-layout">
    <div class="chat-list-panel">
        <form class="search-box" method="GET" action="/pages/messages.php">
            <?php if ($selectedConversationId > 0): ?>
                <input type="hidden" name="conversation_id" value="<?php echo (int)$selectedConversationId; ?>">
            <?php endif; ?>

            <input type="hidden" name="filter" value="<?php echo safeText($filter); ?>">

            <input
                type="text"
                name="search"
                placeholder="Search by project or username..."
                value="<?php echo safeText($searchTerm); ?>"
            >
        </form>

        <div class="chat-filter-tabs">
            <a href="/pages/messages.php?filter=all<?php echo $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : ''; ?>"
               class="filter-tab <?php echo $filter === 'all' ? 'active-filter' : ''; ?>">
                All
            </a>

            <a href="/pages/messages.php?filter=unread<?php echo $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : ''; ?>"
               class="filter-tab <?php echo $filter === 'unread' ? 'active-filter' : ''; ?>">
                Unread
            </a>

            <a href="/pages/messages.php?filter=groups<?php echo $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : ''; ?>"
               class="filter-tab <?php echo $filter === 'groups' ? 'active-filter' : ''; ?>">
                Groups
            </a>
        </div>

        <div class="section-label">Conversations</div>

        <?php if ($conversations): ?>
            <?php foreach ($conversations as $c): ?>
                <?php
                $convType = normalizeConversationType($c['type'] ?? 'private');

                $displayName = $convType === 'project'
                    ? ($c['project_title'] ?: 'Project')
                    : displayUserLabel($c['other_user_email'] ?? '');

                $conversationUrl = "/pages/messages.php?conversation_id=" . (int)$c['conversation_id'];

                if ($searchTerm !== '') {
                    $conversationUrl .= '&search=' . urlencode($searchTerm);
                }

                if ($filter !== 'all') {
                    $conversationUrl .= '&filter=' . urlencode($filter);
                }
                ?>

                <a href="<?php echo safeText($conversationUrl); ?>"
                   class="chat-item <?php echo ($selectedConversationId == $c['conversation_id']) ? 'active-chat' : ''; ?>">
                    <div class="chat-item-content">
                        <div class="chat-name"><?php echo safeText($displayName); ?></div>
                        <div class="chat-preview">
                            <?php echo safeText($c['last_time'] ? formatTime($c['last_time']) : 'No messages yet'); ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-panel-card">
                <p>
                    <?php
                    if ($filter === 'unread') {
                        echo 'Unread message tracking is not available yet.';
                    } elseif ($searchTerm !== '') {
                        echo 'No matches for "' . safeText($searchTerm) . '"';
                    } else {
                        echo 'No conversations found.';
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <div class="chat-panel">
        <div class="chat-header">
            <div class="chat-header-left">
                <h2><?php echo safeText($title); ?></h2>
                <p class="chat-subtitle"><?php echo safeText($subtitle); ?></p>
            </div>

            <?php if ($type === 'project' && isset($info['project_id']) && (int)$info['project_id'] > 0): ?>
                <div class="chat-header-actions">
                    <a href="/pages/project_details.php?project_id=<?php echo (int)$info['project_id']; ?>" class="btn-outline-olive">View Project</a>

                    <?php if ($projectApplicationsUrl !== null): ?>
                        <a href="<?php echo safeText($projectApplicationsUrl); ?>" class="btn-outline-olive">Applications</a>
                    <?php endif; ?>

                    <a href="/pages/report.php?project_id=<?php echo (int)$info['project_id']; ?>" class="btn-outline-olive">Report</a>
                </div>
            <?php elseif ($selectedConversationId > 0 && $hasAccess): ?>
                <div class="chat-header-actions">
                    <?php if ($dmOtherUserId > 0 && pageExists('matches.php')): ?>
                        <a href="/pages/matches.php?user_id=<?php echo $dmOtherUserId; ?>" class="btn-outline-olive">View Match</a>
                    <?php endif; ?>

                    <a href="/pages/report.php?conversation_id=<?php echo (int)$selectedConversationId; ?>" class="btn-outline-olive">Report</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger"><?php echo safeText($errorMessage); ?></div>
        <?php endif; ?>

        <?php if ($infoMessage !== ''): ?>
            <div class="alert alert-warning"><?php echo safeText($infoMessage); ?></div>
        <?php endif; ?>

        <div class="chat-box">
            <div class="message-thread" id="messageThread">
                <?php if ($messages): ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php $mine = (int)$msg['sender_id'] === $currentUserId; ?>

                        <div class="message-row <?php echo $mine ? 'message-right' : 'message-left'; ?>">
                            <?php if (!$mine): ?>
                                <div class="avatar-name"><?php echo safeText(displayUserLabel($msg['email'])); ?></div>
                            <?php endif; ?>

                            <div class="message-bubble-wrap">
                                <div class="message-bubble"><?php echo nl2br(safeText($msg['content'])); ?></div>
                                <div class="message-meta"><?php echo safeText(formatTime($msg['sent_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php elseif ($selectedConversationId > 0 && $hasAccess): ?>
                    <div class="empty-chat-card"><p>No messages yet. Say hi!</p></div>
                <?php else: ?>
                    <div class="empty-chat-card"><p>Select a conversation to start chatting.</p></div>
                <?php endif; ?>
            </div>

            <?php if ($selectedConversationId > 0 && $hasAccess): ?>
                <?php if ($isBlocked): ?>
                    <div class="alert alert-danger">Your account is under review. You cannot send messages.</div>
                <?php elseif (!$canSendInConversation): ?>
                    <div class="alert alert-warning">You cannot send messages in this conversation right now.</div>
                <?php else: ?>
                    <form class="message-input-wrap" method="POST" id="messageForm">
                        <input type="hidden" name="csrf_token" value="<?php echo safeText($csrfToken); ?>">
                        <input type="hidden" name="conversation_id" value="<?php echo (int)$selectedConversationId; ?>">

                        <input
                            type="text"
                            name="message"
                            id="messageInput"
                            placeholder="Type a message..."
                            maxlength="2000"
                            autocomplete="off"
                            required
                        >

                        <button type="submit" class="btn-olive">Send</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("messageForm");
    const input = document.getElementById("messageInput");
    const thread = document.getElementById("messageThread");

    if (thread) {
        thread.scrollTop = thread.scrollHeight;
    }

    if (!form || !input || !thread) return;

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const message = input.value.trim();

        if (message === "") return;

        if (/\d/.test(message)) {
    alert("Numbers are not allowed in chat messages.");
    return;
}

        const formData = new FormData(form);
        formData.append("ajax", "1");

        fetch("/pages/messages.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || "Could not send message.");
                return;
            }

            const emptyCard = thread.querySelector(".empty-chat-card");
            if (emptyCard) {
                emptyCard.remove();
            }

            const row = document.createElement("div");
            row.className = "message-row message-right";

            row.innerHTML = `
                <div class="message-bubble-wrap">
                    <div class="message-bubble">${data.content}</div>
                    <div class="message-meta">${data.display_time}</div>
                </div>
            `;

            thread.appendChild(row);
            input.value = "";
            thread.scrollTop = thread.scrollHeight;
        })
        .catch(() => {
            alert("Something went wrong. Please try again.");
        });
    });
});
</script>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>