<?php
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /pages/home.php");
    exit();
}
require_once __DIR__ . '/../config/db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resolve_keep'])) {
        $report_id = (int)$_POST['report_id'];
        $stmt = $conn->prepare("UPDATE Reports SET status = 'resolved' WHERE report_id = ?");
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $stmt->close();

    } elseif (isset($_POST['resolve_remove'])) {
        $report_id   = (int)$_POST['report_id'];
        $reported_id = (int)$_POST['reported_user_id'];

        $stmt = $conn->prepare("DELETE FROM TeamMembership WHERE user_id = ?");
        $stmt->bind_param("i", $reported_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE Reports SET status = 'resolved' WHERE report_id = ?");
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $stmt->close();

    } elseif (isset($_POST['remove_project'])) {
        $project_id = (int)$_POST['project_id'];
        $stmt = $conn->prepare("DELETE FROM Projects WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: /pages/admin.php");
    exit();
}


$totalUsers     = $conn->query("SELECT COUNT(*) FROM Users")->fetch_row()[0];
$activeProjects = $conn->query("SELECT COUNT(*) FROM Projects WHERE status IN ('open','in_progress')")->fetch_row()[0];
$totalMessages  = $conn->query("SELECT COUNT(*) FROM ContactMessages")->fetch_row()[0];
$openReports    = $conn->query("SELECT COUNT(*) FROM Reports WHERE status = 'open'")->fetch_row()[0];


$users = $conn->query("
    SELECT u.user_id, u.email, u.course, u.year, u.role,
    (SELECT COUNT(*) FROM Reports WHERE reported_user_id = u.user_id AND status = 'open') AS report_count
    FROM Users u
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);


$reports = $conn->query("
    SELECT r.report_id, r.reason, r.created_at,
    r.reported_user_id,
    rep.email  AS reporter_email,
    rep2.email AS reported_email
    FROM Reports r
    JOIN Users rep  ON r.reporter_id      = rep.user_id
    JOIN Users rep2 ON r.reported_user_id = rep2.user_id
    WHERE r.status = 'open'
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);


$projects = $conn->query("
    SELECT project_id, title, status, description FROM Projects ORDER BY created_at DESC
")->fetch_all(MYSQLI_ASSOC);

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

          <?php foreach ($users as $user): ?>
          <div class="admin-table-row" data-search="<?= htmlspecialchars(strtolower($user['email'] . ' ' . $user['course'] . ' ' . $user['year'])) ?>">
            <div style="font-size:0.85rem;"><?= htmlspecialchars($user['email']) ?></div>
            <div><?= htmlspecialchars($user['course'] ?? '—') ?></div>
            <div><?= htmlspecialchars($user['year'] ?? '—') ?></div>
            <div>
              <?php if ($user['report_count'] > 0): ?>
                <span class="status-reported">Reported</span>
              <?php elseif ($user['role'] === 'admin'): ?>
                <span class="status-active">Admin</span>
              <?php else: ?>
                <span class="status-active">Active</span>
              <?php endif; ?>
            </div>
            <div class="admin-actions">
              <a href="/pages/profile.php?id=<?= $user['user_id'] ?>" class="action-link">View</a>
            </div>
          </div>
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
                      style="background:none;border:none;cursor:pointer;padding:0;"
                      data-bs-toggle="modal" data-bs-target="#projectModal<?= $i ?>">Review</button>
              <span class="action-dot light-dot">•</span>
              <form method="POST" action="/pages/admin.php" style="display:inline"
                    onsubmit="return confirm('Remove this project permanently?')">
                <input type="hidden" name="project_id" value="<?= $project['project_id'] ?>">
                <button type="submit" name="remove_project"
                        class="action-link light-link danger-light"
                        style="background:none;border:none;cursor:pointer;padding:0;">Remove</button>
              </form>
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

        <?php if (empty($reports)): ?>
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
              <p><strong>Date:</strong> <?= htmlspecialchars($report['created_at']) ?></p>
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
              <div class="d-flex justify-content-center gap-3 mt-2">
                <form method="POST" action="/pages/admin.php">
                  <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                  <button type="submit" name="resolve_keep" class="btn btn-secondary">Keep User</button>
                </form>
                <form method="POST" action="/pages/admin.php"
                      onsubmit="return confirm('This will remove the user from all projects.')">
                  <input type="hidden" name="report_id" value="<?= $report['report_id'] ?>">
                  <input type="hidden" name="reported_user_id" value="<?= $report['reported_user_id'] ?>">
                  <button type="submit" name="resolve_remove" class="btn btn-danger">Remove from Projects</button>
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

<script>
document.getElementById('userSearch').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.admin-table-row[data-search]').forEach(function (row) {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
