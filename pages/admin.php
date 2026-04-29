<?php
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /pages/home.php");
    exit();
}
require_once __DIR__ . '/../config/db.php';

/* Maps year integer (1-7) to a readable label */
function yearLabel($y) {
    $map = [1=>'Year 1',2=>'Year 2',3=>'Year 3',4=>'Year 4',5=>'Year 5',6=>'Postgraduate',7=>'Master'];
    return $map[(int)$y] ?? ($y ? htmlspecialchars((string)$y) : '—');
}

function fmtDate($val) {
    $t = strtotime((string)$val);
    return $t ? date('d-m-Y', $t) : htmlspecialchars((string)$val);
}

/* Shared helper: marks a single report as resolved */
function resolveReport($conn, $report_id) {
    $stmt = $conn->prepare("UPDATE Reports SET status = 'resolved' WHERE report_id = ?");
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $stmt->close();
}

/* Handle all admin form submissions, then redirect to avoid re-POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dismiss report — keep user/project in place
    if (isset($_POST['resolve_keep']) || isset($_POST['resolve_project_keep'])) {
        resolveReport($conn, (int)$_POST['report_id']);

    // Remove user from a specific project and resolve the report
    } elseif (isset($_POST['resolve_remove'])) {
        $report_id   = (int)($_POST['report_id'] ?? 0);
        $reported_id = (int)$_POST['reported_user_id'];
        $project_id  = (int)($_POST['remove_project_id'] ?? 0);
        if ($project_id > 0) {
            $stmt = $conn->prepare("DELETE FROM TeamMembership WHERE user_id = ? AND project_id = ?");
            $stmt->bind_param("ii", $reported_id, $project_id);
            $stmt->execute();
            $stmt->close();
        }
        if ($report_id > 0) { resolveReport($conn, $report_id); }

    // Remove user from a shared conversation and resolve the report
    } elseif (isset($_POST['resolve_remove_chat'])) {
        $report_id      = (int)($_POST['report_id'] ?? 0);
        $reported_id    = (int)$_POST['reported_user_id'];
        $conversation_id = (int)($_POST['remove_conversation_id'] ?? 0);
        if ($conversation_id > 0) {
            $stmt = $conn->prepare("DELETE FROM ConversationParticipants WHERE user_id = ? AND conversation_id = ?");
            $stmt->bind_param("ii", $reported_id, $conversation_id);
            $stmt->execute();
            $stmt->close();
        }
        resolveReport($conn, $report_id);

    } elseif (isset($_POST['remove_project'])) {
        $project_id = (int)$_POST['project_id'];
        $stmt = $conn->prepare("DELETE FROM Projects WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $stmt->close();

    } elseif (isset($_POST['suspend_user'])) {
        $uid = (int)$_POST['user_id'];
        $stmt = $conn->prepare("UPDATE Users SET status = 'suspended' WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();

    } elseif (isset($_POST['reinstate_user'])) {
        $uid = (int)$_POST['user_id'];
        $stmt = $conn->prepare("UPDATE Users SET status = 'active' WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();

    // Full user deletion: clears memberships and reports before removing the account
    } elseif (isset($_POST['remove_user'])) {
        $uid = (int)$_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM TeamMembership WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("UPDATE Reports SET status = 'resolved' WHERE reported_user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM Users WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();

    } elseif (isset($_POST['dismiss_report'])) {
        $uid = (int)$_POST['user_id'];
        $stmt = $conn->prepare("UPDATE Reports SET status = 'resolved' WHERE reported_user_id = ? AND status = 'open'");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();

    // Project removal: resolves all related reports and removes members before deleting
    } elseif (isset($_POST['resolve_project_remove'])) {
        $project_id = (int)$_POST['project_id'];
        $stmt = $conn->prepare("UPDATE Reports SET status = 'resolved' WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM TeamMembership WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM Projects WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $stmt->close();

    // Appeal approved: reinstates user, resolves open reports, and marks appeal reviewed
    } elseif (isset($_POST['reinstate_appeal'])) {
        $appeal_id = (int)$_POST['appeal_id'];
        $uid = (int)$_POST['user_id'];
        $stmt = $conn->prepare("UPDATE Users SET status = 'active' WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("UPDATE Reports SET status = 'resolved' WHERE reported_user_id = ? AND status = 'open'");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("UPDATE Appeals SET status = 'reviewed' WHERE appeal_id = ?");
        $stmt->bind_param("i", $appeal_id);
        $stmt->execute();
        $stmt->close();

    } elseif (isset($_POST['dismiss_appeal'])) {
        $appeal_id = (int)$_POST['appeal_id'];
        $stmt = $conn->prepare("UPDATE Appeals SET status = 'reviewed' WHERE appeal_id = ?");
        $stmt->bind_param("i", $appeal_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: /pages/admin.php");
    exit();
}


/* Overview counters shown in the top stat cards */
$totalUsers     = $conn->query("SELECT COUNT(*) FROM Users")->fetch_row()[0];
$activeProjects = $conn->query("SELECT COUNT(*) FROM Projects WHERE status IN ('open','in_progress')")->fetch_row()[0];
$totalMessages  = $conn->query("SELECT COUNT(*) FROM Messages")->fetch_row()[0];
$openReports    = $conn->query("SELECT COUNT(*) FROM Reports WHERE status = 'open'")->fetch_row()[0];


$users = $conn->query("
    SELECT u.user_id, u.email, u.course, u.year, u.role, u.status,
    (SELECT COUNT(*) FROM Reports WHERE reported_user_id = u.user_id AND status = 'open') AS report_count
    FROM Users u
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$userReportsRaw = $conn->query("
    SELECT r.report_id, r.reason, r.created_at, r.reporter_id, r.reported_user_id,
    COALESCE(rep.email, '[deleted user]') AS reporter_email
    FROM Reports r
    LEFT JOIN Users rep ON r.reporter_id = rep.user_id
    WHERE r.status = 'open' AND r.reported_user_id IS NOT NULL
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$userReports = [];
foreach ($userReportsRaw as $row) {
    $userReports[$row['reported_user_id']][] = $row;
}

// Fetch chat logs for all users
$chatLogs = [];
foreach ($users as $u) {
    $uid = $u['user_id'];
    $stmt = $conn->prepare("
        SELECT c.conversation_id, c.type, c.created_at, p.title AS project_title,
        MIN(u2.email) AS other_email
        FROM Conversations c
        JOIN ConversationParticipants cp ON cp.conversation_id = c.conversation_id AND cp.user_id = ?
        LEFT JOIN Projects p ON c.project_id = p.project_id
        LEFT JOIN ConversationParticipants cp2 ON cp2.conversation_id = c.conversation_id AND cp2.user_id != ?
        LEFT JOIN Users u2 ON u2.user_id = cp2.user_id
        GROUP BY c.conversation_id, c.type, c.created_at, p.title
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("ii", $uid, $uid);
    $stmt->execute();
    $convs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $chatLogs[$uid] = [];
    foreach ($convs as $conv) {
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
        $conv['messages'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $chatLogs[$uid][] = $conv;
    }
}


$reports = $conn->query("
    SELECT r.report_id, r.reason, r.created_at,
    r.reporter_id, r.reported_user_id,
    COALESCE(rep.email,  '[deleted user]') AS reporter_email,
    COALESCE(rep2.email, '[deleted user]') AS reported_email
    FROM Reports r
    LEFT JOIN Users rep  ON r.reporter_id = rep.user_id
    LEFT JOIN Users rep2 ON r.reported_user_id = rep2.user_id
    WHERE r.status = 'open' AND r.project_id IS NULL AND r.reported_user_id IS NOT NULL
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$reportedUserProjects = [];
foreach ($users as $rp) {
    $uid = (int)$rp['user_id'];
    if (isset($reportedUserProjects[$uid])) continue;
    $stmt = $conn->prepare("
        SELECT p.project_id, p.title
        FROM TeamMembership tm
        JOIN Projects p ON p.project_id = tm.project_id
        WHERE tm.user_id = ?
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $reportedUserProjects[$uid] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$reportedUserConversations = [];
foreach ($reports as $rp) {
    $reported_id = (int)$rp['reported_user_id'];
    $reporter_id = (int)$rp['reporter_id'];
    $key = $reported_id . '_' . $reporter_id;
    if (isset($reportedUserConversations[$key])) continue;
    $stmt = $conn->prepare("
        SELECT c.conversation_id,
        CASE WHEN c.type = 'project' THEN CONCAT('Group: ', p.title)
        ELSE CONCAT('Private: ', MIN(u2.email))
        END AS label
        FROM Conversations c
        JOIN ConversationParticipants cp1 ON cp1.conversation_id = c.conversation_id AND cp1.user_id = ?
        JOIN ConversationParticipants cp2 ON cp2.conversation_id = c.conversation_id AND cp2.user_id = ?
        LEFT JOIN Projects p ON p.project_id = c.project_id
        LEFT JOIN ConversationParticipants cp3 ON cp3.conversation_id = c.conversation_id AND cp3.user_id != ?
        LEFT JOIN Users u2 ON u2.user_id = cp3.user_id
        GROUP BY c.conversation_id, c.type, p.title
    ");
    $stmt->bind_param("iii", $reported_id, $reporter_id, $reported_id);
    $stmt->execute();
    $reportedUserConversations[$key] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$generalReports = $conn->query("
    SELECT r.report_id, r.reason, r.created_at,
    rep.email AS reporter_email
    FROM Reports r
    JOIN Users rep ON r.reporter_id = rep.user_id
    WHERE r.status = 'open' AND r.project_id IS NULL AND r.reported_user_id IS NULL
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$projectReports = $conn->query("
    SELECT r.report_id, r.reason, r.created_at, r.project_id,
    p.title AS project_title, p.description AS project_description,
    u.email AS reporter_email
    FROM Reports r
    JOIN Projects p ON r.project_id = p.project_id
    JOIN Users u ON r.reporter_id = u.user_id
    WHERE r.status = 'open' AND r.project_id IS NOT NULL
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);


$projects = $conn->query("
    SELECT project_id, title, status, description FROM Projects ORDER BY created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$pastReportsRaw = $conn->query("
    SELECT r.reason, r.created_at, r.reported_user_id,
    COALESCE(rep.email, '[deleted user]') AS reporter_email
    FROM Reports r
    LEFT JOIN Users rep ON r.reporter_id = rep.user_id
    WHERE r.status = 'resolved' AND r.reported_user_id IS NOT NULL
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$pastReports = [];
foreach ($pastReportsRaw as $row) {
    $pastReports[$row['reported_user_id']][] = $row;
}

$appealsRaw = $conn->query("
    SELECT a.appeal_id, a.message, a.created_at, u.user_id, u.email
    FROM Appeals a
    JOIN Users u ON a.user_id = u.user_id
    WHERE a.status = 'pending'
    ORDER BY a.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

$appeals = [];
foreach ($appealsRaw as $ap) {
    $appeals[$ap['user_id']] = $ap;
}

$extra_css = '/assets/css/admin.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<div class="main-content">
  <div class="content-area">
    <div class="admin-main-grid">

      <div class="admin-left">

        <div class="section-pill admin-pill">Platform Overview</div>

        <div class="overview-cards">
          <div class="overview-card">
            <div class="overview-icon user-icon">
              <div class="user-icon-head"></div>
              <div class="user-icon-body"></div>
            </div>
            <div class="overview-content">
              <div class="overview-number"><?= $totalUsers ?></div>
              <div class="overview-label">Total<br>Users</div>
            </div>
          </div>

          <div class="overview-card">
            <div class="overview-icon folder-icon">
              <div class="folder-back"></div>
              <div class="folder-front"></div>
            </div>
            <div class="overview-content">
              <div class="overview-number"><?= $activeProjects ?></div>
              <div class="overview-label">Active<br>Projects</div>
            </div>
          </div>

          <div class="overview-card">
            <div class="overview-icon message-icon">
              <div class="message-bubble"></div>
              <div class="message-dot dot-1"></div>
              <div class="message-dot dot-2"></div>
              <div class="message-dot dot-3"></div>
            </div>
            <div class="overview-content">
              <div class="overview-number"><?= $totalMessages ?></div>
              <div class="overview-label">Total<br>Messages</div>
            </div>
          </div>

          <div class="overview-card">
            <div class="overview-icon flag-icon">
              <div class="flag-pole"></div>
              <div class="flag-shape"></div>
            </div>
            <div class="overview-content">
              <div class="overview-number"><?= $openReports ?></div>
              <div class="overview-label">Open<br>Reports</div>
            </div>
          </div>
        </div>

        <div class="section-line admin-line"></div>

        <div class="section-pill admin-pill">User Management</div>

        <div class="admin-search-row" style="margin-bottom:16px;">
          <div class="admin-search-box" style="padding:0 18px;">
            <input type="text" id="userSearch" placeholder="Search users..."
            style="border:none;outline:none;background:transparent;width:100%;font-size:1rem;">
          </div>
        </div>

        <div class="admin-table-card">
          <div class="admin-table-header">
            <div>Email</div>
            <div>Course</div>
            <div>Year</div>
            <div>Status</div>
            <div>Actions</div>
          </div>

          <?php foreach ($users as $idx => $user): ?>
          <div class="admin-table-row" data-search="<?= htmlspecialchars(strtolower($user['email'] . ' ' . $user['course'] . ' ' . $user['year'])) ?>">
            <div><?= htmlspecialchars($user['email']) ?></div>
            <div><?= htmlspecialchars($user['course'] ?? '—') ?></div>
            <div><?= yearLabel($user['year'] ?? 0) ?></div>
            <div>
              <?php if ($user['status'] === 'suspended'): ?>
                <span class="status-suspended">Suspended</span>
              <?php elseif ($user['report_count'] > 0): ?>
                <span class="status-reported">Reported</span>
              <?php elseif ($user['role'] === 'admin'): ?>
                <span class="status-active">Admin</span>
              <?php else: ?>
                <span class="status-active">Active</span>
              <?php endif; ?>
            </div>
            <div class="admin-actions">

              <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
                <span style="color:#999;">—</span>

              <?php elseif ($user['status'] === 'suspended'): ?>
                <button type="button" class="action-link"
                        onclick="new bootstrap.Modal(document.getElementById('userActiveModal<?= $idx ?>')).show()">View</button>
                <?php if (isset($appeals[$user['user_id']])): ?>
                  <button type="button" class="action-link"
                          data-bs-toggle="modal" data-bs-target="#appealUserModal<?= $idx ?>">Appeal</button>
                <?php endif; ?>

              <?php elseif ($user['report_count'] > 0): ?>
                <button type="button" class="action-link"
                        onclick="new bootstrap.Modal(document.getElementById('userViewModal<?= $idx ?>')).show()">View</button>

              <?php else: ?>
                <button type="button" class="action-link"
                        onclick="new bootstrap.Modal(document.getElementById('userActiveModal<?= $idx ?>')).show()">View</button>
              <?php endif; ?>

            </div>
          </div>

          <?php if ($user['report_count'] > 0): ?>
          <?php
            $firstReport   = isset($userReports[$user['user_id']]) ? $userReports[$user['user_id']][0] : null;
            $firstReportId = $firstReport ? $firstReport['report_id'] : 0;
            $uProjects     = $reportedUserProjects[$user['user_id']] ?? [];
            $uConvKey      = $user['user_id'] . '_' . ($firstReport['reporter_id'] ?? 0);
            $uConvs        = $reportedUserConversations[$uConvKey] ?? [];
            $userConvs     = $chatLogs[$user['user_id']] ?? [];
          ?>
          <div class="modal fade" id="userViewModal<?= $idx ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content" style="border-radius:14px; overflow:hidden; border:none;">

                <!-- Header -->
                <div style="background:#57673E; padding:16px 20px; display:flex; justify-content:space-between; align-items:center;">
                  <div>
                    <div style="color:#FCF9F2; font-weight:600; font-size:1rem;"><?= htmlspecialchars(explode('@', $user['email'])[0]) ?></div>
                    <div style="color:#c8d4b0; font-size:0.78rem;"><?= htmlspecialchars($user['email']) ?></div>
                  </div>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div style="padding:18px 20px; max-height:75vh; overflow-y:auto;">

                  <!-- Reports -->
                  <?php if ($firstReport && isset($userReports[$user['user_id']])): ?>
                  <div style="margin-bottom:16px;">
                    <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#444; margin-bottom:8px;">Reports</div>
                    <?php foreach ($userReports[$user['user_id']] as $ur): ?>
                    <div style="background:#fff8f0; border-left:3px solid #e07b00; border-radius:6px; padding:8px 12px; margin-bottom:6px; font-size:0.83rem;">
                      <span style="font-weight:600;"><?= htmlspecialchars($ur['reason']) ?></span>
                      <span style="color:#555; margin-left:8px;">by <?= htmlspecialchars($ur['reporter_email']) ?> · <?= fmtDate($ur['created_at']) ?></span>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>

                  <!-- Chat Logs -->
                  <div style="margin-bottom:16px;">
                    <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#444; margin-bottom:8px;">Chat Logs</div>
                    <?php if (empty($userConvs)): ?>
                      <p style="color:#bbb; font-size:0.83rem; margin:0;">No conversations.</p>
                    <?php else: ?>
                      <select class="form-select form-select-sm mb-2" onchange="showChatLog(this, 'chatpanel_<?= $idx ?>')">
                        <option value="">Select a conversation...</option>
                        <?php foreach ($userConvs as $ci => $conv): ?>
                          <?php $label = $conv['type'] === 'project' ? 'Project: ' . ($conv['project_title'] ?? 'Unknown') : 'Private: ' . explode('@', $conv['other_email'] ?? 'unknown')[0]; ?>
                          <option value="chatconv_<?= $idx ?>_<?= $ci ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <div id="chatpanel_<?= $idx ?>">
                        <?php foreach ($userConvs as $ci => $conv): ?>
                          <div id="chatconv_<?= $idx ?>_<?= $ci ?>" style="display:none;">
                            <?php if (empty($conv['messages'])): ?>
                              <p style="color:#bbb; font-size:0.8rem; margin:0;">No messages.</p>
                            <?php else: ?>
                              <div style="max-height:200px; overflow-y:auto; display:flex; flex-direction:column; gap:5px; background:#f7f5ef; border-radius:8px; padding:8px;">
                                <?php foreach ($conv['messages'] as $msg): ?>
                                  <?php $isSender = ($msg['sender_email'] === $user['email']); ?>
                                  <div style="display:flex; flex-direction:column; align-items:<?= $isSender ? 'flex-end' : 'flex-start' ?>;">
                                    <span style="font-size:0.67rem; color:#aaa; margin-bottom:2px;"><?= htmlspecialchars(explode('@', $msg['sender_email'])[0]) ?> · <?= date('d-m-Y H:i', strtotime($msg['sent_at'])) ?></span>
                                    <div style="background:<?= $isSender ? '#57673E' : '#e8e6df' ?>; color:<?= $isSender ? '#FCF9F2' : '#333' ?>; border-radius:10px; padding:5px 11px; max-width:75%; font-size:0.83rem; word-break:break-word;"><?= htmlspecialchars($msg['content']) ?></div>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            <?php endif; ?>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>

                  <!-- Resolve -->
                  <div>
                    <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#444; margin-bottom:8px;">Resolve</div>
                    <div class="d-flex flex-column gap-2">
                      <?php if (!empty($uProjects)): ?>
                      <form method="POST" action="/pages/admin.php" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="report_id" value="<?= $firstReportId ?>">
                        <input type="hidden" name="reported_user_id" value="<?= $user['user_id'] ?>">
                        <select name="remove_project_id" class="form-select form-select-sm" required>
                          <option value="" disabled selected>Pick a project...</option>
                          <?php foreach ($uProjects as $up): ?>
                            <option value="<?= $up['project_id'] ?>"><?= htmlspecialchars($up['title']) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit" name="resolve_remove" class="btn btn-danger btn-sm text-nowrap" style="min-width:160px;">Remove from Project</button>
                      </form>
                      <?php else: ?>
                      <p style="font-size:0.8rem; color:#bbb; margin:0;">User is not in any projects.</p>
                      <?php endif; ?>
                      <?php if (!empty($uConvs)): ?>
                      <form method="POST" action="/pages/admin.php" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="report_id" value="<?= $firstReportId ?>">
                        <input type="hidden" name="reported_user_id" value="<?= $user['user_id'] ?>">
                        <select name="remove_conversation_id" class="form-select form-select-sm" required>
                          <option value="" disabled selected>Pick a conversation...</option>
                          <?php foreach ($uConvs as $sc): ?>
                            <option value="<?= $sc['conversation_id'] ?>"><?= htmlspecialchars($sc['label']) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit" name="resolve_remove_chat" class="btn btn-danger btn-sm text-nowrap" style="min-width:160px;">Remove from Chat</button>
                      </form>
                      <?php else: ?>
                      <p style="font-size:0.8rem; color:#bbb; margin:0;">No shared conversations found.</p>
                      <?php endif; ?>
                      <?php if (!empty($userReports[$user['user_id']])): ?>
                      <button type="button" class="btn btn-sm w-100" style="background:#57673E; color:#fff; border:none; margin-top:10px;"
                              data-bs-toggle="modal" data-bs-target="#dismissReportModal<?= $idx ?>">Dismiss Report</button>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Past Reports -->
                  <div style="margin-top:16px;">
                    <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#444; margin-bottom:8px;">Past Reports</div>
                    <?php $uPastReports = $pastReports[$user['user_id']] ?? []; ?>
                    <?php if (empty($uPastReports)): ?>
                      <p style="font-size:0.8rem; color:#bbb; margin:0;">No past reports.</p>
                    <?php else: ?>
                      <div style="display:flex; flex-direction:column; gap:6px;">
                        <?php foreach ($uPastReports as $pr): ?>
                          <div style="background:#f7f5ef; border-radius:8px; padding:8px 12px; font-size:0.82rem;">
                            <div style="color:#888; font-size:0.72rem; margin-bottom:2px;"><?= fmtDate($pr['created_at']) ?> · by <?= htmlspecialchars(explode('@', $pr['reporter_email'])[0]) ?></div>
                            <div><?= htmlspecialchars($pr['reason']) ?></div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>

                </div>

                <!-- Footer -->
                <div style="padding:12px 20px; border-top:1px solid #eee; display:flex; justify-content:space-between; align-items:center; background:#fafafa;">
                  <a href="/pages/profile.php?id=<?= $user['user_id'] ?>" class="btn btn-sm" style="background:#57673E; color:#fff; border:none;">View Profile</a>
                  <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning btn-sm" data-bs-dismiss="modal"
                            onclick="openConfirm('suspend_user', <?= $user['user_id'] ?>, 'Suspend <?= htmlspecialchars(addslashes($user['email'])) ?>?', 'This account will be suspended.', 'Suspend', 'warning')">Suspend</button>
                    <button class="btn btn-sm" style="background:#8a9b6e; color:#fff; border:none;" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>

              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($user['report_count'] > 0 && !empty($userReports[$user['user_id']])): ?>
          <div class="modal fade" id="dismissReportModal<?= $idx ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content" style="border-radius:14px; overflow:hidden; border:none;">
                <div style="background:#57673E; padding:14px 20px; display:flex; justify-content:space-between; align-items:center;">
                  <div style="color:#FCF9F2; font-weight:600; font-size:0.95rem;">Dismiss Report</div>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="/pages/admin.php">
                  <div style="padding:18px 20px;">
                    <p style="font-size:0.88rem; color:#555; margin-bottom:12px;">Select which report to dismiss:</p>
                    <select name="report_id" class="form-select form-select-sm" required>
                      <option value="" disabled selected>Pick a report...</option>
                      <?php foreach ($userReports[$user['user_id']] as $dr): ?>
                        <option value="<?= $dr['report_id'] ?>">
                          <?= htmlspecialchars($dr['reason']) ?> — by <?= htmlspecialchars(explode('@', $dr['reporter_email'])[0]) ?> · <?= fmtDate($dr['created_at']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div style="padding:12px 20px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:10px; background:#fafafa;">
                    <button type="button" class="btn btn-sm" style="background:#8a9b6e; color:#fff; border:none; border-radius:20px; padding:6px 18px;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="resolve_keep" class="btn btn-sm" style="background:#57673E; color:#fff; border:none; border-radius:20px; padding:6px 18px;">Dismiss</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($user['status'] === 'suspended' && isset($appeals[$user['user_id']])): ?>
          <?php $ap = $appeals[$user['user_id']]; ?>
          <div class="modal fade" id="appealUserModal<?= $idx ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content p-3">
                <h5 class="modal-title mb-2">Appeal from <?= htmlspecialchars(explode('@', $user['email'])[0]) ?></h5>
                <hr>
                <p><strong>User:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Submitted:</strong> <?= fmtDate($ap['created_at']) ?></p>
                <p><strong>Message:</strong></p>
                <p style="background:#f7f5ef; border-radius:8px; padding:10px; font-size:0.9rem;"><?= nl2br(htmlspecialchars($ap['message'])) ?></p>
                <div class="d-flex justify-content-between gap-2 mt-3">
                  <form method="POST" action="/pages/admin.php">
                    <input type="hidden" name="appeal_id" value="<?= $ap['appeal_id'] ?>">
                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                    <button type="submit" name="reinstate_appeal" class="btn btn-success">Reinstate User</button>
                  </form>
                  <form method="POST" action="/pages/admin.php">
                    <input type="hidden" name="appeal_id" value="<?= $ap['appeal_id'] ?>">
                    <button type="submit" name="dismiss_appeal" class="btn btn-danger">Dismiss Appeal</button>
                  </form>
                </div>
                <div class="d-flex justify-content-center mt-2">
                  <button class="btn btn-sm" style="background:#8a9b6e; color:#fff; border:none;" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($user['report_count'] == 0): ?>
          <?php
            $userConvsActive  = $chatLogs[$user['user_id']] ?? [];
            $activeUserProjects = $reportedUserProjects[$user['user_id']] ?? [];
          ?>
          <div class="modal fade" id="userActiveModal<?= $idx ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content" style="border-radius:14px; overflow:hidden; border:none;">

                <!-- Header -->
                <div style="background:#57673E; padding:16px 20px; display:flex; justify-content:space-between; align-items:center;">
                  <div>
                    <div style="color:#FCF9F2; font-weight:600; font-size:1rem;"><?= htmlspecialchars(explode('@', $user['email'])[0]) ?></div>
                    <div style="color:#c8d4b0; font-size:0.78rem;"><?= htmlspecialchars($user['email']) ?></div>
                  </div>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div style="padding:18px 20px; max-height:75vh; overflow-y:auto;">

                  <!-- User Info -->
                  <div style="margin-bottom:16px;">
                    <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#444; margin-bottom:8px;">User Info</div>
                    <div style="background:#f7f5ef; border-radius:8px; padding:10px 14px; font-size:0.85rem;">
                      <div><strong>Course:</strong> <?= htmlspecialchars($user['course'] ?? '—') ?></div>
                      <div><strong>Year:</strong> <?= yearLabel($user['year'] ?? 0) ?></div>
                      <div><strong>Status:</strong>
                        <?php if ($user['status'] === 'suspended'): ?>
                          <span class="status-suspended">Suspended</span>
                        <?php else: ?>
                          <span class="status-active">Active</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <!-- Chat Logs -->
                  <div style="margin-bottom:16px;">
                    <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#444; margin-bottom:8px;">Chat Logs</div>
                    <?php if (empty($userConvsActive)): ?>
                      <p style="color:#bbb; font-size:0.83rem; margin:0;">No conversations.</p>
                    <?php else: ?>
                      <select class="form-select form-select-sm mb-2" onchange="showChatLog(this, 'chatpanel_active_<?= $idx ?>')">
                        <option value="">Select a conversation...</option>
                        <?php foreach ($userConvsActive as $ci => $conv): ?>
                          <?php $label = $conv['type'] === 'project' ? 'Project: ' . ($conv['project_title'] ?? 'Unknown') : 'Private: ' . explode('@', $conv['other_email'] ?? 'unknown')[0]; ?>
                          <option value="chatconv_active_<?= $idx ?>_<?= $ci ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <div id="chatpanel_active_<?= $idx ?>">
                        <?php foreach ($userConvsActive as $ci => $conv): ?>
                          <div id="chatconv_active_<?= $idx ?>_<?= $ci ?>" style="display:none;">
                            <?php if (empty($conv['messages'])): ?>
                              <p style="color:#bbb; font-size:0.8rem; margin:0;">No messages.</p>
                            <?php else: ?>
                              <div style="max-height:200px; overflow-y:auto; display:flex; flex-direction:column; gap:5px; background:#f7f5ef; border-radius:8px; padding:8px;">
                                <?php foreach ($conv['messages'] as $msg): ?>
                                  <?php $isSender = ($msg['sender_email'] === $user['email']); ?>
                                  <div style="display:flex; flex-direction:column; align-items:<?= $isSender ? 'flex-end' : 'flex-start' ?>;">
                                    <span style="font-size:0.67rem; color:#aaa; margin-bottom:2px;"><?= htmlspecialchars(explode('@', $msg['sender_email'])[0]) ?> · <?= date('d-m-Y H:i', strtotime($msg['sent_at'])) ?></span>
                                    <div style="background:<?= $isSender ? '#57673E' : '#e8e6df' ?>; color:<?= $isSender ? '#FCF9F2' : '#333' ?>; border-radius:10px; padding:5px 11px; max-width:75%; font-size:0.83rem; word-break:break-word;"><?= htmlspecialchars($msg['content']) ?></div>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            <?php endif; ?>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>

                  <!-- Resolve -->
                  <div>
                    <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#444; margin-bottom:8px;">Resolve</div>
                    <div class="d-flex flex-column gap-2">
                      <?php if (!empty($activeUserProjects)): ?>
                      <form method="POST" action="/pages/admin.php" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="reported_user_id" value="<?= $user['user_id'] ?>">
                        <select name="remove_project_id" class="form-select form-select-sm" required>
                          <option value="" disabled selected>Pick a project...</option>
                          <?php foreach ($activeUserProjects as $up): ?>
                            <option value="<?= $up['project_id'] ?>"><?= htmlspecialchars($up['title']) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit" name="resolve_remove" class="btn btn-danger btn-sm text-nowrap" style="min-width:160px;">Remove from Project</button>
                      </form>
                      <?php else: ?>
                      <p style="font-size:0.8rem; color:#bbb; margin:0;">User is not in any projects.</p>
                      <?php endif; ?>
                      <?php if (!empty($userConvsActive)): ?>
                      <form method="POST" action="/pages/admin.php" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="reported_user_id" value="<?= $user['user_id'] ?>">
                        <select name="remove_conversation_id" class="form-select form-select-sm" required>
                          <option value="" disabled selected>Pick a conversation...</option>
                          <?php foreach ($userConvsActive as $ac): ?>
                            <?php $acLabel = $ac['type'] === 'project' ? 'Group: ' . ($ac['project_title'] ?? 'Unknown') : 'Private: ' . explode('@', $ac['other_email'] ?? 'unknown')[0]; ?>
                            <option value="<?= $ac['conversation_id'] ?>"><?= htmlspecialchars($acLabel) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit" name="resolve_remove_chat" class="btn btn-danger btn-sm text-nowrap" style="min-width:160px;">Remove from Chat</button>
                      </form>
                      <?php else: ?>
                      <p style="font-size:0.8rem; color:#bbb; margin:0;">No conversations found.</p>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Past Reports -->
                  <div style="margin-top:16px;">
                    <div style="font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#444; margin-bottom:8px;">Past Reports</div>
                    <?php $uPastReports = $pastReports[$user['user_id']] ?? []; ?>
                    <?php if (empty($uPastReports)): ?>
                      <p style="font-size:0.8rem; color:#bbb; margin:0;">No past reports.</p>
                    <?php else: ?>
                      <div style="display:flex; flex-direction:column; gap:6px;">
                        <?php foreach ($uPastReports as $pr): ?>
                          <div style="background:#f7f5ef; border-radius:8px; padding:8px 12px; font-size:0.82rem;">
                            <div style="color:#888; font-size:0.72rem; margin-bottom:2px;"><?= fmtDate($pr['created_at']) ?> · by <?= htmlspecialchars(explode('@', $pr['reporter_email'])[0]) ?></div>
                            <div><?= htmlspecialchars($pr['reason']) ?></div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>

                </div>

                <!-- Footer -->
                <div style="padding:12px 20px; border-top:1px solid #eee; display:flex; justify-content:space-between; align-items:center; background:#fafafa;">
                  <a href="/pages/profile.php?id=<?= $user['user_id'] ?>" class="btn btn-sm" style="background:#57673E; color:#fff; border:none;">View Profile</a>
                  <div class="d-flex gap-2">
                    <?php if ($user['status'] === 'suspended'): ?>
                    <button type="button" class="btn btn-success btn-sm" data-bs-dismiss="modal"
                            onclick="openConfirm('reinstate_user', <?= $user['user_id'] ?>, 'Reinstate <?= htmlspecialchars(addslashes($user['email'])) ?>?', 'This will restore their account access.', 'Reinstate', 'success')">Reinstate</button>
                    <?php else: ?>
                    <button type="button" class="btn btn-warning btn-sm" data-bs-dismiss="modal"
                            onclick="openConfirm('suspend_user', <?= $user['user_id'] ?>, 'Suspend <?= htmlspecialchars(addslashes($user['email'])) ?>?', 'This account will be suspended.', 'Suspend', 'warning')">Suspend</button>
                    <?php endif; ?>
                    <button class="btn btn-sm" style="background:#8a9b6e; color:#fff; border:none;" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>

              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php endforeach; ?>

          <?php if (empty($users)): ?>
          <div class="admin-table-row"><div>No users found.</div></div>
          <?php endif; ?>
        </div>

        <div class="section-pill admin-pill admin-project-pill">Project Moderation</div>

        <?php if (empty($projects)): ?>
          <p style="color:#57673E;">No projects found.</p>
        <?php endif; ?>

        <?php foreach ($projects as $i => $project): ?>
        <div class="moderation-card" style="margin-bottom:12px;">
          <div class="moderation-header">
            <div><?= htmlspecialchars($project['title']) ?></div>
            <div class="admin-actions">
              <button type="button" class="action-link light-link"
                      data-bs-toggle="modal" data-bs-target="#projectModal<?= $i ?>">Review</button>
              <button type="button" class="action-link light-link danger-light"
                      onclick="openProjectConfirm(<?= $project['project_id'] ?>, '<?= htmlspecialchars(addslashes($project['title'])) ?>')">Remove</button>
            </div>
          </div>
          <div class="moderation-body">
            <div class="moderation-left">
              <p>Status: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $project['status']))) ?></p>
            </div>
          </div>
        </div>

        
        <div class="modal fade" id="projectModal<?= $i ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3">
              <h5 class="modal-title mb-2"><?= htmlspecialchars($project['title']) ?></h5>
              <hr>
              <p><strong>Status:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $project['status']))) ?></p>
              <p><strong>Description:</strong> <?= htmlspecialchars($project['description'] ?? 'No description.') ?></p>
              <div class="d-flex justify-content-end gap-2 mt-3">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <form method="POST" action="/pages/admin.php"
                      onsubmit="return confirm('Remove this project permanently?')">
                  <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                  <button type="submit" name="remove_project" class="btn btn-danger">Remove Project</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

      </div>

      
      <div class="admin-right">

        <?php if (empty($reports) && empty($projectReports) && empty($generalReports)): ?>
        <div class="report-card">
          <div class="report-card-header">Reports &amp; Safety</div>
          <div class="report-card-body">
            <p style="color:#57673E;">No open reports.</p>
          </div>
        </div>
        <?php endif; ?>

        <?php foreach ($reports as $i => $report): ?>
        <div class="report-card">
          <div class="report-card-header">Reports &amp; Safety</div>
          <div class="report-card-body">
            <div class="report-title">User Report</div>
            <div class="report-divider"></div>
            <div class="report-name"><?= htmlspecialchars($report['reported_email']) ?></div>
            <div class="report-reason">Reason: <?= htmlspecialchars($report['reason']) ?></div>
            <div class="report-divider bottom-divider"></div>
            <div class="report-actions">
              <button class="report-btn" data-bs-toggle="modal" data-bs-target="#reviewModal<?= $i ?>">Review</button>
              <button class="report-btn" data-bs-toggle="modal" data-bs-target="#resolveModal<?= $i ?>">Resolve</button>
            </div>
          </div>
        </div>

        <div class="modal fade" id="reviewModal<?= $i ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3">
              <h5 class="modal-title mb-2">Report Details</h5>
              <hr>
              <p><strong>Reported user:</strong> <?= htmlspecialchars($report['reported_email']) ?></p>
              <p><strong>Reported by:</strong> <?= htmlspecialchars($report['reporter_email']) ?></p>
              <p><strong>Reason:</strong> <?= htmlspecialchars($report['reason']) ?></p>
              <p><strong>Date:</strong> <?= fmtDate($report['created_at']) ?></p>
              <div class="d-flex justify-content-end mt-3">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="resolveModal<?= $i ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3 text-center">
              <h5 class="modal-title mb-2">Resolve Report</h5>
              <p style="font-size:0.9rem;">Reported user: <strong><?= htmlspecialchars($report['reported_email']) ?></strong></p>
              <hr>
              <p style="font-size:0.9rem;">Choose an action:</p>
              <div class="d-flex flex-column gap-2 mt-2">
                <?php $userProjects = $reportedUserProjects[$report['reported_user_id']] ?? []; ?>
                <?php if (!empty($userProjects)): ?>
                <form method="POST" action="/pages/admin.php" class="d-flex gap-2 align-items-center">
                  <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                  <input type="hidden" name="reported_user_id" value="<?= $report['reported_user_id'] ?>">
                  <select name="remove_project_id" class="form-select form-select-sm" required>
                    <option value="" disabled selected>Pick a project...</option>
                    <?php foreach ($userProjects as $up): ?>
                      <option value="<?= $up['project_id'] ?>"><?= htmlspecialchars($up['title']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" name="resolve_remove" class="btn btn-warning btn-sm text-nowrap">Remove</button>
                </form>
                <?php else: ?>
                <p style="font-size:0.85rem; color:#999;">User is not in any projects.</p>
                <?php endif; ?>
                <?php $convKey = $report['reported_user_id'] . '_' . ($report['reporter_id'] ?? 0);
                      $sharedConvs = $reportedUserConversations[$convKey] ?? []; ?>
                <?php if (!empty($sharedConvs)): ?>
                <form method="POST" action="/pages/admin.php" class="d-flex gap-2 align-items-center">
                  <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                  <input type="hidden" name="reported_user_id" value="<?= $report['reported_user_id'] ?>">
                  <select name="remove_conversation_id" class="form-select form-select-sm" required>
                    <option value="" disabled selected>Pick a conversation...</option>
                    <?php foreach ($sharedConvs as $sc): ?>
                      <option value="<?= $sc['conversation_id'] ?>"><?= htmlspecialchars($sc['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" name="resolve_remove_chat" class="btn btn-danger btn-sm text-nowrap">Remove</button>
                </form>
                <?php else: ?>
                <p style="font-size:0.85rem; color:#999;">No shared conversations found.</p>
                <?php endif; ?>
                <form method="POST" action="/pages/admin.php">
                  <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                  <button type="submit" name="resolve_keep" class="btn w-100" style="background:#57673E; color:#fff; border:none;">Keep User</button>
                </form>
              </div>
              <div class="d-flex justify-content-center mt-3">
                <button class="btn btn-sm btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <?php foreach ($projectReports as $j => $pr): ?>
        <div class="report-card">
          <div class="report-card-header">Reports &amp; Safety</div>
          <div class="report-card-body">
            <div class="report-title">Project Report</div>
            <div class="report-divider"></div>
            <div class="report-name"><?= htmlspecialchars($pr['project_title']) ?></div>
            <div class="report-reason">Reason: <?= htmlspecialchars($pr['reason']) ?></div>
            <div class="report-divider bottom-divider"></div>
            <div class="report-actions">
              <button class="report-btn" data-bs-toggle="modal" data-bs-target="#reviewProjectModal<?= $j ?>">Review</button>
              <button class="report-btn" data-bs-toggle="modal" data-bs-target="#resolveProjectModal<?= $j ?>">Resolve</button>
            </div>
          </div>
        </div>

        <div class="modal fade" id="reviewProjectModal<?= $j ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3">
              <h5 class="modal-title mb-2">Project Report Details</h5>
              <hr>
              <p><strong>Project:</strong> <?= htmlspecialchars($pr['project_title']) ?></p>
              <p><strong>Description:</strong> <?= htmlspecialchars($pr['project_description'] ?? '—') ?></p>
              <p><strong>Reported by:</strong> <?= htmlspecialchars($pr['reporter_email']) ?></p>
              <p><strong>Reason:</strong> <?= htmlspecialchars($pr['reason']) ?></p>
              <p><strong>Date:</strong> <?= fmtDate($pr['created_at']) ?></p>
              <div class="d-flex justify-content-end mt-3">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="resolveProjectModal<?= $j ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3 text-center">
              <h5 class="modal-title mb-2">Resolve Project Report</h5>
              <p style="font-size:0.9rem;">Project: <strong><?= htmlspecialchars($pr['project_title']) ?></strong></p>
              <hr>
              <p style="font-size:0.9rem;">Choose an action:</p>
              <div class="d-flex justify-content-center gap-3 mt-2">
                <form method="POST" action="/pages/admin.php">
                  <input type="hidden" name="report_id" value="<?= $pr['report_id'] ?>">
                  <button type="submit" name="resolve_project_keep" class="btn btn-secondary">Keep Project</button>
                </form>
                <form method="POST" action="/pages/admin.php">
                  <input type="hidden" name="report_id" value="<?= $pr['report_id'] ?>">
                  <input type="hidden" name="project_id" value="<?= $pr['project_id'] ?>">
                  <button type="submit" name="resolve_project_remove" class="btn btn-danger">Remove Project</button>
                </form>
              </div>
              <div class="d-flex justify-content-center mt-3">
                <button class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <?php foreach ($generalReports as $k => $gr): ?>
        <div class="report-card">
          <div class="report-card-header">Reports &amp; Safety</div>
          <div class="report-card-body">
            <div class="report-title">General Report</div>
            <div class="report-divider"></div>
            <div class="report-name"><?= htmlspecialchars($gr['reporter_email']) ?></div>
            <div class="report-reason">Reason: <?= htmlspecialchars($gr['reason']) ?></div>
            <div class="report-divider bottom-divider"></div>
            <div class="report-actions">
              <button class="report-btn" data-bs-toggle="modal" data-bs-target="#reviewGeneralModal<?= $k ?>">Review</button>
              <button class="report-btn" data-bs-toggle="modal" data-bs-target="#resolveGeneralModal<?= $k ?>">Resolve</button>
            </div>
          </div>
        </div>

        <div class="modal fade" id="reviewGeneralModal<?= $k ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3">
              <h5 class="modal-title mb-2">General Report Details</h5>
              <hr>
              <p><strong>Reported by:</strong> <?= htmlspecialchars($gr['reporter_email']) ?></p>
              <p><strong>Reason:</strong> <?= htmlspecialchars($gr['reason']) ?></p>
              <p><strong>Date:</strong> <?= fmtDate($gr['created_at']) ?></p>
              <div class="d-flex justify-content-end mt-3">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="resolveGeneralModal<?= $k ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3 text-center">
              <h5 class="modal-title mb-2">Resolve General Report</h5>
              <p style="font-size:0.9rem;">From: <strong><?= htmlspecialchars($gr['reporter_email']) ?></strong></p>
              <hr>
              <div class="d-flex justify-content-center mt-2">
                <form method="POST" action="/pages/admin.php">
                  <input type="hidden" name="report_id" value="<?= $gr['report_id'] ?>">
                  <button type="submit" name="resolve_keep" class="btn btn-secondary">Mark Resolved</button>
                </form>
              </div>
              <div class="d-flex justify-content-center mt-3">
                <button class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="confirmActionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3 text-center">
      <h5 class="modal-title mb-2" id="confirmTitle" aria-live="polite">Confirm Action</h5>
      <hr>
      <p style="font-size:0.9rem;" id="confirmMessage"></p>
      <form method="POST" action="/pages/admin.php" id="confirmForm">
        <input type="hidden" name="user_id" id="confirmUserId">
        <input type="hidden" name="" id="confirmAction">
        <div class="d-flex justify-content-center gap-3 mt-3">
          <button type="button" class="btn btn-sm" style="background:#57673E; color:#fff; border:none;" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm" id="confirmSubmitBtn"></button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="confirmProjectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3 text-center">
      <h5 class="modal-title mb-2" id="confirmProjectTitle" aria-live="polite">Remove Project</h5>
      <hr>
      <p style="font-size:0.9rem;">This will permanently remove the project.</p>
      <form method="POST" action="/pages/admin.php" id="confirmProjectForm">
        <input type="hidden" name="project_id" id="confirmProjectId">
        <div class="d-flex justify-content-center gap-3 mt-3">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="remove_project" class="btn btn-danger">Yes, Remove</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('userSearch').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.admin-table-row[data-search]').forEach(function (row) {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
});

function openConfirm(action, userId, title, message, btnLabel, btnStyle) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmUserId').value = userId;
    document.getElementById('confirmAction').name = action;
    const btn = document.getElementById('confirmSubmitBtn');
    btn.textContent = btnLabel;
    btn.className = 'btn btn-' + btnStyle;
    new bootstrap.Modal(document.getElementById('confirmActionModal')).show();
}

function openProjectConfirm(projectId, title) {
    document.getElementById('confirmProjectTitle').textContent = 'Remove "' + title + '"?';
    document.getElementById('confirmProjectId').value = projectId;
    new bootstrap.Modal(document.getElementById('confirmProjectModal')).show();
}

function showChatLog(select, panelId) {
    const panel = document.getElementById(panelId);
    if (!panel) return;
    panel.querySelectorAll('[id^="chatconv_"]').forEach(el => el.style.display = 'none');
    if (select.value) {
        const target = document.getElementById(select.value);
        if (target) target.style.display = 'block';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
