<?php
$extra_css = '/assets/css/projects.css';
require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

$current_user_id = $_SESSION['user_id'] ?? null;

$current_user_suspended = false;
$current_profile_incomplete = false;
if ($current_user_id) {
    $s = $conn->prepare("SELECT status, course, year, bio FROM Users WHERE user_id = ? LIMIT 1");
    $s->bind_param("i", $current_user_id);
    $s->execute();
    $s_row = $s->get_result()->fetch_assoc();
    $s->close();
    $current_user_suspended = ($s_row && $s_row['status'] === 'suspended');

    $skill_count = 0;
    $tag_count = 0;
    if ($s_row) {
        $s2 = $conn->prepare("SELECT COUNT(*) FROM UserSkills WHERE user_id = ?");
        $s2->bind_param("i", $current_user_id);
        $s2->execute();
        $s2->bind_result($skill_count);
        $s2->fetch();
        $s2->close();

        $s3 = $conn->prepare("SELECT COUNT(*) FROM UserTags WHERE user_id = ?");
        $s3->bind_param("i", $current_user_id);
        $s3->execute();
        $s3->bind_result($tag_count);
        $s3->fetch();
        $s3->close();
    }
    $current_profile_incomplete = ($s_row && (
        empty($s_row['course']) || empty($s_row['year']) || empty($s_row['bio']) ||
        $skill_count === 0 || $tag_count === 0
    ));
}

$status_filter = $_GET['status'] ?? '';
$allowed_statuses = ['open', 'in_progress', 'completed'];

$sql = "
    SELECT 
        p.project_id,
        p.owner_id,
        p.title,
        p.description,
        p.team_size,
        p.deadline,
        p.status,
        p.created_at,
        u.course,
        u.year,
        u.email
    FROM Projects p
    JOIN Users u ON p.owner_id = u.user_id
";

$params = [];
$types = "";

if (!empty($status_filter) && in_array($status_filter, $allowed_statuses, true)) {
    $sql .= " WHERE p.status = ? ";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

function formatStatus($status) {
    switch ($status) {
        case 'open':
            return 'Open for applications';
        case 'in_progress':
            return 'In Progress';
        case 'completed':
            return 'Completed';
        default:
            return ucfirst(str_replace('_', ' ', $status));
    }
}

function shortText($text, $length = 140) {
    $text = trim($text);
    if (mb_strlen($text) <= $length) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars(mb_substr($text, 0, $length), ENT_QUOTES, 'UTF-8') . '...';
}
?>

<div class="col-lg-10 content-area">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <button class="my-projects-btn">Available Projects</button>

        <div class="d-flex gap-3">
            <a href="/pages/my_projects.php" class="my-projects-btn">My Projects</a>
            <a href="/pages/create_project.php" class="create-project-btn">Create a project</a>
        </div>
    </div>

    <div class="section-line"></div>

    <div class="d-flex flex-wrap gap-2 mb-4 mt-3">
    <a href="/pages/projects.php" class="my-projects-btn filter-all">All</a>
    <a href="/pages/projects.php?status=open" class="my-projects-btn filter-open">Open</a>
    <a href="/pages/projects.php?status=in_progress" class="my-projects-btn filter-progress">In Progress</a>
    <a href="/pages/projects.php?status=completed" class="my-projects-btn filter-completed">Completed</a>
</div>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($project = $result->fetch_assoc()): ?>

            <?php
            $status = $project['status'];

            $status_class = '';
            if ($status === 'open') {
                $status_class = 'status-open';
            } elseif ($status === 'in_progress') {
                $status_class = 'status-progress';
            } elseif ($status === 'completed') {
                $status_class = 'status-completed';
            }
            ?>

            <div class="project-card">
                <div class="project-card-header">
                    <h2><?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <span>
                        <?php echo htmlspecialchars($project['course'], ENT_QUOTES, 'UTF-8'); ?> - Year <?php echo (int)$project['year']; ?>
                    </span>
                </div>

                <div class="project-card-body">
                    <p><strong>Task:</strong> <?php echo shortText($project['description']); ?></p>

                    <p>
                        <strong>Status:</strong>
                        <span class="<?php echo $status_class; ?>">
                            <?php echo formatStatus($status); ?>
                        </span>
                    </p>

                    <p><strong>Group size:</strong> <?php echo (int)$project['team_size']; ?></p>
                    <p><strong>Deadline:</strong> <?php echo htmlspecialchars($project['deadline'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Owner:</strong> <?php echo htmlspecialchars($project['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <div class="project-card-actions">
                    <a href="/pages/project_details.php?project_id=<?php echo (int)$project['project_id']; ?>" class="details-btn">
                        Project Details
                    </a>

                    <?php if (!$current_user_id): ?>
                        <a href="/pages/login.php" class="apply-btn">Login to Apply</a>

                    <?php elseif ($current_user_suspended): ?>
                        <button type="button" class="apply-btn" data-bs-toggle="modal" data-bs-target="#suspendedModal">Apply</button>

                    <?php elseif ($current_profile_incomplete): ?>
                        <button type="button" class="apply-btn" data-bs-toggle="modal" data-bs-target="#incompleteProfileModal">Apply</button>

                    <?php elseif ((int)$current_user_id === (int)$project['owner_id']): ?>
                        <span class="apply-btn" style="opacity: 0.6; pointer-events: none;">Your Project</span>

                    <?php elseif ($project['status'] === 'completed'): ?>
                        <span class="apply-btn" style="opacity: 0.6; pointer-events: none;">Completed</span>

                    <?php else: ?>
                        <a href="/pages/apply.php?project_id=<?php echo (int)$project['project_id']; ?>" class="apply-btn">
                            Apply
                        </a>
                    <?php endif; ?>

                    <?php if ($current_user_id && (int)$current_user_id !== (int)$project['owner_id']): ?>
                        <a href="/pages/report.php?project_id=<?php echo (int)$project['project_id']; ?>" class="details-btn" style="font-size:0.82rem; font-weight:500; color:#c0392b; border-color:#e8c4c0;">Report</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-line mt-4"></div>

        <?php endwhile; ?>
    <?php else: ?>
        <div class="project-card">
            <div class="project-card-body">
                <p>No projects found at the moment.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($current_profile_incomplete): ?>
<div class="modal fade" id="incompleteProfileModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-4 text-center">
    <h5 class="modal-title mb-2">Complete Your Profile First</h5>
    <hr>
    <p style="font-size:0.95rem;">You need to fill in your <strong>course</strong>, <strong>year</strong>, <strong>bio</strong>, <strong>skills</strong>, and <strong>interests</strong> before you can apply to projects.</p>
    <div class="d-flex justify-content-center gap-3 mt-3">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="/pages/profile.php" class="btn btn-success">Go to Profile</a>
    </div>
    </div>
</div>
</div>
<?php endif; ?>

<?php if ($current_user_suspended): ?>
<div class="modal fade" id="suspendedModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-4 text-center">
    <h5 class="modal-title mb-2">Account Suspended</h5>
    <hr>
    <p style="font-size:0.95rem;">Your account has been suspended/removed. You are not able to apply to projects at this time.</p>
    <p style="font-size:0.9rem; color:#888;">If you believe this is a mistake, please contact support.</p>
    <div class="d-flex justify-content-center mt-3">
    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
    </div>
    </div>
</div>
<?php endif; ?>

<?php
$stmt->close();
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>