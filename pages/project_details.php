<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Project Details - MindLink";
$extra_css = '/assets/css/project_details.css';

require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id'])) {
    die("Invalid project ID.");
}

$projectId = (int) $_GET['project_id'];

function formatStatus($status) {
    switch ($status) {
        case 'open':
            return 'Open';
        case 'in_progress':
            return 'In Progress';
        case 'completed':
            return 'Completed';
        default:
            return ucfirst(str_replace('_', ' ', (string)$status));
    }
}

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| 1. Fetch project details
|--------------------------------------------------------------------------
| Uses only columns we already know from your database/screenshots.
*/
$sqlProject = "
    SELECT 
        p.project_id,
        p.owner_id,
        p.title,
        p.description,
        p.team_size,
        p.deadline,
        p.status,
        p.created_at,
        u.email,
        u.course,
        u.year
    FROM Projects p
    JOIN Users u ON p.owner_id = u.user_id
    WHERE p.project_id = ?
    LIMIT 1
";

$stmtProject = $conn->prepare($sqlProject);

if (!$stmtProject) {
    die('Project query prepare failed: ' . $conn->error);
}

$stmtProject->bind_param("i", $projectId);
$stmtProject->execute();
$resultProject = $stmtProject->get_result();

if (!$resultProject || $resultProject->num_rows === 0) {
    die("Project not found.");
}

$project = $resultProject->fetch_assoc();
$stmtProject->close();

/*
|--------------------------------------------------------------------------
| 2. Fetch project roles
|--------------------------------------------------------------------------
| Removed slots_needed because it may not exist in your Roles table.
*/
$roles = [];

$sqlRoles = "
    SELECT role_id, title, description
    FROM Roles
    WHERE project_id = ?
    ORDER BY role_id ASC
";

$stmtRoles = $conn->prepare($sqlRoles);

if (!$stmtRoles) {
    die('Roles query prepare failed: ' . $conn->error);
}

$stmtRoles->bind_param("i", $projectId);
$stmtRoles->execute();
$resultRoles = $stmtRoles->get_result();

while ($row = $resultRoles->fetch_assoc()) {
    $roles[] = $row;
}

$stmtRoles->close();

/*
|--------------------------------------------------------------------------
| 3. Fetch team members
|--------------------------------------------------------------------------
| Uses email instead of name because name may not exist in Users table.
*/
$members = [];

$sqlMembers = "
    SELECT 
        tm.user_id,
        tm.role_id,
        tm.status,
        tm.joined_at,
        u.email,
        r.title AS role_title
    FROM TeamMembership tm
    JOIN Users u ON tm.user_id = u.user_id
    LEFT JOIN Roles r ON tm.role_id = r.role_id
    WHERE tm.project_id = ?
    ORDER BY tm.joined_at ASC
";

$stmtMembers = $conn->prepare($sqlMembers);

if (!$stmtMembers) {
    die('Members query prepare failed: ' . $conn->error);
}

$stmtMembers->bind_param("i", $projectId);
$stmtMembers->execute();
$resultMembers = $stmtMembers->get_result();

while ($row = $resultMembers->fetch_assoc()) {
    $members[] = $row;
}

$stmtMembers->close();

/*
|--------------------------------------------------------------------------
| 4. Fetch project skills/tags
|--------------------------------------------------------------------------
| Your DB has ProjectTags, not ProjectSkills.
*/
$tags = [];

$sqlTags = "
    SELECT tag_name
    FROM ProjectTags 
    WHERE project_id = ?
    ORDER BY tag_name ASC
";

$stmtTags = $conn->prepare($sqlTags);

if (!$stmtTags) {
    die('Tags query prepare failed: ' . $conn->error);
}

$stmtTags->bind_param("i", $projectId);
$stmtTags->execute();
$resultTags = $stmtTags->get_result();

while ($row = $resultTags->fetch_assoc()) {
    $tags[] = $row['tag_name'];
}

$stmtTags->close();

$currentMemberCount = count($members);
?>

<div class="content-area">

    <div class="detail-card header-card">
        <div class="detail-header">
            <div class="header-info">
                <h1 class="project-title"><?php echo safeText($project['title']); ?></h1>
                <p class="project-subtitle">
                    View project details, team members, required skills, and available roles.
                </p>
            </div>

            <div class="project-actions">
                <a href="#members" class="detail-btn secondary-btn">View Members</a>
                <a href="/pages/apply.php?project_id=<?php echo (int)$project['project_id']; ?>" class="detail-btn primary-btn">Apply</a>
                <a href="#" class="detail-btn report-btn">Report</a>
            </div>
        </div>
    </div>

    <div class="detail-grid two-col">

        <div class="detail-card">
            <div class="detail-card-title">Project Information</div>
            <div class="info-list">
                <div class="info-item">
                    <span class="info-label">Owner Email</span>
                    <span class="info-value"><?php echo safeText($project['email']); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Course</span>
                    <span class="info-value"><?php echo safeText($project['course']); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Year</span>
                    <span class="info-value"><?php echo (int)$project['year']; ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="info-value"><?php echo safeText(formatStatus($project['status'])); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Deadline</span>
                    <span class="info-value"><?php echo safeText($project['deadline']); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Group Size</span>
                    <span class="info-value"><?php echo (int)$project['team_size']; ?> Students</span>
                </div>

                <div class="info-item">
                    <span class="info-label">Current Members</span>
                    <span class="info-value"><?php echo (int)$currentMemberCount; ?></span>
                </div>
            </div>
        </div>

        <div class="detail-card">
            <div class="detail-card-title">Required Skills</div>
            <div class="skills-list">
                <?php if (!empty($tags)): ?>
                    <?php foreach ($tags as $tag): ?>
                        <span class="skill-tag"><?php echo safeText($tag); ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No skills listed for this project yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="detail-grid two-col">

        <div class="detail-card" id="members">
            <div class="detail-card-title">Team Members</div>
            <ul class="member-list">
                <?php if (!empty($members)): ?>
                    <?php foreach ($members as $member): ?>
                        <li class="member-item">
                            <span class="member-name">
                                <?php echo safeText($member['email']); ?>
                            </span>
                            <span class="member-role">
                                <?php echo safeText($member['role_title'] ?: 'Team Member'); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="member-item">
                        <span class="member-name">No members joined yet.</span>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="detail-card">
            <div class="detail-card-title">Open Roles</div>
            <ul class="roles-list">
                <?php if (!empty($roles)): ?>
                    <?php foreach ($roles as $role): ?>
                        <li class="role-item">
                            <strong><?php echo safeText($role['title']); ?></strong>
                            <?php if (!empty($role['description'])): ?>
                                - <?php echo safeText($role['description']); ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="role-item">No roles available for this project.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="detail-card">
        <div class="detail-card-title">Project Description</div>
        <div class="description-box">
            <p><?php echo nl2br(safeText($project['description'])); ?></p>
        </div>
    </div>

</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>