<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT status, role FROM Users WHERE user_id = ?");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$current_user_row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current_user_row) {
    session_unset();
    session_destroy();
    header("Location: /pages/login.php");
    exit();
}

$is_suspended = ($current_user_row['status'] === 'suspended');
$is_admin = ($current_user_row['role'] === 'admin');

$prefill_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$prefill_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;

$error = '';
$success = '';

$reasons = [
    'Inappropriate Content',
    'Harassment',
    'Spam',
    'Misleading Description',
    'Inactive Members',
    'Other',
];

if ($conversation_id > 0 && $prefill_user_id === 0) {
    $stmt = $conn->prepare("SELECT 1 FROM ConversationParticipants WHERE conversation_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $conversation_id, $current_user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $stmt = $conn->prepare("
            SELECT u.user_id FROM ConversationParticipants cp
            JOIN Users u ON cp.user_id = u.user_id
            WHERE cp.conversation_id = ? AND cp.user_id != ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $conversation_id, $current_user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
        $prefill_user_id = (int)$row['user_id'];
        }
    }
    $stmt->close();
}

$default_tab = $prefill_project_id > 0 ? 'project' : 'user';

if ($is_admin) {
    $stmt = $conn->prepare("SELECT user_id, email FROM Users WHERE user_id != ? ORDER BY email");
    $stmt->bind_param("i", $current_user_id);
} else {
    $stmt = $conn->prepare("
        SELECT DISTINCT u.user_id, u.email
        FROM Users u
        LEFT JOIN TeamMembership tm_other ON tm_other.user_id = u.user_id
        LEFT JOIN TeamMembership tm_me ON tm_me.project_id = tm_other.project_id AND tm_me.user_id = ?
        WHERE u.user_id != ? AND (u.role = 'admin' OR tm_me.user_id IS NOT NULL)
        ORDER BY u.email
    ");
    $stmt->bind_param("ii", $current_user_id, $current_user_id);
}
$stmt->execute();
$all_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


$all_projects = $conn->query("SELECT project_id, title FROM Projects ORDER BY title")->fetch_all(MYSQLI_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_suspended) {
    $report_type = $_POST['report_type'] ?? '';
    $reported_user_id = isset($_POST['reported_user_id']) ? (int)$_POST['reported_user_id'] : 0;
    $reported_proj_id = isset($_POST['reported_project_id']) ? (int)$_POST['reported_project_id'] : 0;
    $reason = trim($_POST['reason']  ?? '');
    $details = trim($_POST['details'] ?? '');
    $full_reason = $details !== '' ? $reason . ': ' . $details : $reason;

    if (!in_array($reason, $reasons)) {
        $error = "Please select a valid reason.";
    } elseif ($report_type === 'user') {
        if ($reported_user_id <= 0) {
            $error = "Please select a user to report.";
        } elseif ($reported_user_id === $current_user_id) {
            $error = "You cannot report yourself.";
        } else {
            $allowed = false;
            if ($is_admin) {
                $allowed = true;
            } else {
                // Allow if reported user is admin
                $stmt = $conn->prepare("SELECT 1 FROM Users WHERE user_id = ? AND role = 'admin' LIMIT 1");
                $stmt->bind_param("i", $reported_user_id);
                $stmt->execute();
                $stmt->store_result();
                $allowed = $stmt->num_rows > 0;
                $stmt->close();

                if (!$allowed) {
                    // Otherwise must be a teammate
                    $stmt = $conn->prepare("
                        SELECT 1 FROM TeamMembership tm_other
                        JOIN TeamMembership tm_me ON tm_me.project_id = tm_other.project_id AND tm_me.user_id = ?
                        WHERE tm_other.user_id = ?
                        LIMIT 1
                    ");
                    $stmt->bind_param("ii", $current_user_id, $reported_user_id);
                    $stmt->execute();
                    $stmt->store_result();
                    $allowed = $stmt->num_rows > 0;
                    $stmt->close();
                }
            }

            if (!$allowed) {
                $error = "You can only report users who are in a project with you.";
            } else {
                $stmt = $conn->prepare("INSERT INTO Reports (reporter_id, reported_user_id, reason, status) VALUES (?, ?, ?, 'open')");
                $stmt->bind_param("iis", $current_user_id, $reported_user_id, $full_reason);
                $stmt->execute();
                $stmt->close();
                $success = "User reported successfully. Our team will review it shortly.";
            }
        }
    } elseif ($report_type === 'project') {
        if ($reported_proj_id <= 0) {
            $error = "Please select a project to report.";
        } else {
            $stmt = $conn->prepare("INSERT INTO Reports (reporter_id, project_id, reason, status) VALUES (?, ?, ?, 'open')");
            $stmt->bind_param("iis", $current_user_id, $reported_proj_id, $full_reason);
            $stmt->execute();
            $stmt->close();
            $success = "Project reported successfully. Our team will review it shortly.";
        }
    } else {
        $error = "Please select what you are reporting.";
    }
}

$extra_css = '/assets/css/report.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<div class="main-content">
  <div class="content-area">
    <div class="report-page-wrap">

      <div class="report-page-card">

        <div class="section-pill report-pill">Report</div>

        <?php if ($is_suspended): ?>
          <p class="report-error">Your account is suspended. You cannot submit a report.</p>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="report-success">
            <p><?= htmlspecialchars($success) ?></p>
            <a href="/pages/home.php" class="report-back-btn">Back to Home</a>
          </div>

        <?php else: ?>

          <?php if ($error): ?>
            <p class="report-error"><?= htmlspecialchars($error) ?></p>
          <?php endif; ?>

          <form method="POST" action="" class="report-form" <?= $is_suspended ? 'style="pointer-events:none;opacity:0.5;"' : '' ?>>

            <label class="report-label">What are you reporting?</label>
            <div class="report-type-toggle">
              <button type="button" class="report-type-btn <?= $default_tab === 'user' ? 'active' : '' ?>"
                      onclick="switchTab('user', this)">User</button>
              <button type="button" class="report-type-btn <?= $default_tab === 'project' ? 'active' : '' ?>"
                      onclick="switchTab('project', this)">Project</button>
            </div>
            <input type="hidden" name="report_type" id="report_type" value="<?= $default_tab ?>">

            <div id="section-user" <?= $default_tab !== 'user' ? 'style="display:none;"' : '' ?>>
              <label class="report-label">Select user</label>
              <select name="reported_user_id" class="report-select">
                <option value="" disabled <?= $prefill_user_id === 0 ? 'selected' : '' ?>>Select a user...</option>
                <?php foreach ($all_users as $u): ?>
                  <option value="<?= $u['user_id'] ?>"
                    <?= ($prefill_user_id === (int)$u['user_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(explode('@', $u['email'])[0]) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div id="section-project" <?= $default_tab !== 'project' ? 'style="display:none;"' : '' ?>>
              <label class="report-label">Select project</label>
              <select name="reported_project_id" class="report-select">
                <option value="" disabled <?= $prefill_project_id === 0 ? 'selected' : '' ?>>Select a project...</option>
                <?php foreach ($all_projects as $p): ?>
                  <option value="<?= $p['project_id'] ?>"
                    <?= ($prefill_project_id === (int)$p['project_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['title']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <label class="report-label">Reason for report</label>
            <select name="reason" class="report-select" required>
              <option value="" disabled selected>Select a reason...</option>
              <?php foreach ($reasons as $r): ?>
                <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
              <?php endforeach; ?>
            </select>

            <label class="report-label">Additional details <span class="report-optional">(optional)</span></label>
            <textarea name="details" class="report-textarea" placeholder="Describe the issue in more detail..." rows="3" maxlength="500"></textarea>

            <div class="report-actions">
              <button type="submit" class="report-submit-btn">Submit Report</button>
              <a href="javascript:history.back()" class="report-cancel-btn">Cancel</a>
            </div>
          </form>

        <?php endif; ?>

      </div>

    </div>
  </div>
</div>

<script>
function switchTab(type, btn) {
  document.getElementById('report_type').value = type;
  document.querySelectorAll('.report-type-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('section-user').style.display    = type === 'user'    ? 'block' : 'none';
  document.getElementById('section-project').style.display = type === 'project' ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
