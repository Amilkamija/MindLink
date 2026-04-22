<?php
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /pages/home.php");
    exit();
}
require_once __DIR__ . '/../config/db.php';

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($user_id <= 0) {
    header("Location: /pages/admin.php");
    exit();
}

$stmt = $conn->prepare("SELECT user_id, email FROM Users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reported_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reported_user) {
    header("Location: /pages/admin.php");
    exit();
}

// Fetch all conversations this user is part of
$stmt = $conn->prepare("
    SELECT c.conversation_id, c.type, c.created_at,
      p.title AS project_title,
      u2.email AS other_email
    FROM Conversations c
    JOIN ConversationParticipants cp ON cp.conversation_id = c.conversation_id AND cp.user_id = ?
    LEFT JOIN Projects p ON c.project_id = p.project_id
    LEFT JOIN ConversationParticipants cp2 ON cp2.conversation_id = c.conversation_id AND cp2.user_id != ?
    LEFT JOIN Users u2 ON u2.user_id = cp2.user_id
    ORDER BY c.created_at DESC
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all messages for each conversation
$chat_logs = [];
foreach ($conversations as $conv) {
    $cid = $conv['conversation_id'];
    $stmt = $conn->prepare("
        SELECT m.content, m.sent_at, u.email AS sender_email
        FROM Messages m
        JOIN Users u ON m.sender_id = u.user_id
        WHERE m.conversation_id = ?
        ORDER BY m.sent_at ASC
    ");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $chat_logs[$cid] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$extra_css = '/assets/css/admin.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<div class="main-content">
  <div class="content-area">

    <div class="admin-left" style="max-width:860px; margin:0 auto;">

      <div class="d-flex align-items-center gap-3 mb-3">
        <a href="/pages/admin.php" class="action-link" style="font-size:0.9rem;">← Back to Admin</a>
      </div>

      <div class="section-pill admin-pill">Chat Logs — <?= htmlspecialchars(explode('@', $reported_user['email'])[0]) ?></div>
      <p style="font-size:0.85rem; color:#666; margin-bottom:20px;"><?= htmlspecialchars($reported_user['email']) ?></p>

      <?php if (empty($conversations)): ?>
        <p style="color:#57673E;">This user has no conversations.</p>
      <?php endif; ?>

      <?php foreach ($conversations as $conv): ?>
        <?php
          $cid = $conv['conversation_id'];
          $label = $conv['type'] === 'project'
            ? 'Project: ' . ($conv['project_title'] ?? 'Unknown')
            : 'Private with ' . (explode('@', $conv['other_email'] ?? 'unknown')[0]);
          $messages = $chat_logs[$cid] ?? [];
        ?>
        <div class="report-card" style="margin-bottom:18px;">
          <div class="report-card-header" style="font-size:0.9rem;">
            <?= htmlspecialchars($label) ?>
            <span style="font-size:0.78rem; opacity:0.75; margin-left:8px;"><?= htmlspecialchars($conv['created_at']) ?></span>
          </div>
          <div class="report-card-body" style="padding:14px 18px;">
            <?php if (empty($messages)): ?>
              <p style="color:#aaa; font-size:0.85rem;">No messages in this conversation.</p>
            <?php else: ?>
              <div style="max-height:320px; overflow-y:auto; display:flex; flex-direction:column; gap:8px;">
                <?php foreach ($messages as $msg): ?>
                  <?php $is_reported = (explode('@', $msg['sender_email'])[0] === explode('@', $reported_user['email'])[0]); ?>
                  <div style="display:flex; flex-direction:column; align-items:<?= $is_reported ? 'flex-end' : 'flex-start' ?>;">
                    <span style="font-size:0.72rem; color:#888; margin-bottom:2px;">
                      <?= htmlspecialchars(explode('@', $msg['sender_email'])[0]) ?> · <?= htmlspecialchars($msg['sent_at']) ?>
                    </span>
                    <div style="
                      background: <?= $is_reported ? '#57673E' : '#e8e6df' ?>;
                      color: <?= $is_reported ? '#FCF9F2' : '#222' ?>;
                      border-radius: 10px;
                      padding: 7px 12px;
                      max-width: 75%;
                      font-size: 0.88rem;
                      word-break: break-word;
                    ">
                      <?= htmlspecialchars($msg['content']) ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
