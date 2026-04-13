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
$currentUserId = (int) $_SESSION['user_id'];

function formatStatus($status) {
    switch ((string)$status) {
        case 'open': return 'Open';
        case 'in_progress': return 'In Progress';
        case 'completed': return 'Completed';
        default: return ucfirst(str_replace('_', ' ', (string)$status));
    }
}

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function displayValue($value, $fallback = 'Not specified') {
    $value = trim((string)$value);
    return $value !== '' ? safeText($value) : safeText($fallback);
}

/*
|--------------------------------------------------------------------------
| 1. Fetch project details + owner profile
|--------------------------------------------------------------------------
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
    die('Project query failed: ' . $conn->error);
}

$stmtProject->bind_param("i", $projectId);
$stmtProject->execute();
$resultProject = $stmtProject->get_result();

if ($resultProject->num_rows === 0) {
    die("Project not found.");
}

$project = $resultProject->fetch_assoc();
$stmtProject->close();

/*
|--------------------------------------------------------------------------
| 2. Count active team members
|--------------------------------------------------------------------------
*/
$currentMemberCount = 0;

$sqlMemberCount = "
    SELECT COUNT(*) AS member_count
    FROM TeamMembership
    WHERE project_id = ? AND status = 'active'
";

$stmtMemberCount = $conn->prepare($sqlMemberCount);
if (!$stmtMemberCount) {
    die('Member count query failed: ' . $conn->error);
}

$stmtMemberCount->bind_param("i", $projectId);
$stmtMemberCount->execute();
$resultMemberCount = $stmtMemberCount->get_result();
$memberCountRow = $resultMemberCount->fetch_assoc();
$currentMemberCount = (int)($memberCountRow['member_count'] ?? 0);
$stmtMemberCount->close();

/*
|--------------------------------------------------------------------------
| 3. Check if current user is already an active team member
|--------------------------------------------------------------------------
*/
$isCurrentUserMember = false;

$sqlCheckMembership = "
    SELECT membership_id
    FROM TeamMembership
    WHERE project_id = ? AND user_id = ? AND status = 'active'
    LIMIT 1
";

$stmtCheckMembership = $conn->prepare($sqlCheckMembership);
if (!$stmtCheckMembership) {
    die('Membership check query failed: ' . $conn->error);
}

$stmtCheckMembership->bind_param("ii", $projectId, $currentUserId);
$stmtCheckMembership->execute();
$resultCheckMembership = $stmtCheckMembership->get_result();
$isCurrentUserMember = ($resultCheckMembership->num_rows > 0);
$stmtCheckMembership->close();

/*
|--------------------------------------------------------------------------
| 4. Fetch all roles
|--------------------------------------------------------------------------
*/
$roles = [];

$sqlRoles = "
    SELECT role_id, title, description, filled
    FROM Roles
    WHERE project_id = ?
    ORDER BY role_id ASC
";

$stmtRoles = $conn->prepare($sqlRoles);
if (!$stmtRoles) {
    die('Roles query failed: ' . $conn->error);
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
| 5. Fetch open roles only
|--------------------------------------------------------------------------
*/
$openRoles = [];

$sqlOpenRoles = "
    SELECT role_id, title, description, filled
    FROM Roles
    WHERE project_id = ? AND filled = 0
    ORDER BY role_id ASC
";

$stmtOpenRoles = $conn->prepare($sqlOpenRoles);
if (!$stmtOpenRoles) {
    die('Open roles query failed: ' . $conn->error);
}

$stmtOpenRoles->bind_param("i", $projectId);
$stmtOpenRoles->execute();
$resultOpenRoles = $stmtOpenRoles->get_result();

while ($row = $resultOpenRoles->fetch_assoc()) {
    $openRoles[] = $row;
}
$stmtOpenRoles->close();

$openRolesCount = count($openRoles);

/*
|--------------------------------------------------------------------------
| 6. Fetch active members
|--------------------------------------------------------------------------
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
    WHERE tm.project_id = ? AND tm.status = 'active'
    ORDER BY tm.joined_at ASC
";

$stmtMembers = $conn->prepare($sqlMembers);
if (!$stmtMembers) {
    die('Members query failed: ' . $conn->error);
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
| 7. Fetch project tags / required skills
|--------------------------------------------------------------------------
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
    die('Tags query failed: ' . $conn->error);
}

$stmtTags->bind_param("i", $projectId);
$stmtTags->execute();
$resultTags = $stmtTags->get_result();

while ($row = $resultTags->fetch_assoc()) {
    $tags[] = $row['tag_name'];
}
$stmtTags->close();

/*
|--------------------------------------------------------------------------
| 8. Project state checks
|--------------------------------------------------------------------------
*/
$teamSize = (int)($project['team_size'] ?? 0);
$isProjectFull = ($teamSize > 0 && $currentMemberCount >= $teamSize);
$isProjectOpen = ((string)$project['status'] === 'open');
$isOwner = ((int)$project['owner_id'] === $currentUserId);

$canApply = (
    $isProjectOpen &&
    !$isProjectFull &&
    !$isCurrentUserMember &&
    !$isOwner &&
    $openRolesCount > 0
);

/*
|--------------------------------------------------------------------------
| 9. Project conversation checks
|--------------------------------------------------------------------------
*/
$projectConversationId = null;
$currentUserInProjectConversation = false;

$sqlConversation = "
    SELECT conversation_id
    FROM Conversations
    WHERE project_id = ? AND type = 'project'
    LIMIT 1
";

$stmtConversation = $conn->prepare($sqlConversation);
if ($stmtConversation) {
    $stmtConversation->bind_param("i", $projectId);
    $stmtConversation->execute();
    $resultConversation = $stmtConversation->get_result();

    if ($resultConversation->num_rows > 0) {
        $conversationRow = $resultConversation->fetch_assoc();
        $projectConversationId = (int)$conversationRow['conversation_id'];
    }

    $stmtConversation->close();
}

if ($projectConversationId !== null) {
    $sqlParticipantCheck = "
        SELECT 1
        FROM ConversationParticipants
        WHERE conversation_id = ? AND user_id = ?
        LIMIT 1
    ";

    $stmtParticipantCheck = $conn->prepare($sqlParticipantCheck);
    if ($stmtParticipantCheck) {
        $stmtParticipantCheck->bind_param("ii", $projectConversationId, $currentUserId);
        $stmtParticipantCheck->execute();
        $participantResult = $stmtParticipantCheck->get_result();
        $currentUserInProjectConversation = ($participantResult->num_rows > 0);
        $stmtParticipantCheck->close();
    }
}

/*
|--------------------------------------------------------------------------
| 10. Friendly display values
|--------------------------------------------------------------------------
*/
$courseDisplay = trim((string)$project['course']) !== '' ? $project['course'] : 'Not specified';
$yearDisplay = ((int)$project['year'] > 0) ? ('Year ' . (int)$project['year']) : 'Not specified';

$chatStatusText = '';
if ($projectConversationId === null) {
    $chatStatusText = 'Project chat has not been created yet.';
} elseif (!$currentUserInProjectConversation) {
    $chatStatusText = 'Project chat exists, but you are not yet added to it.';
}
?>

<div class="content-area">

    <!-- HEADER -->
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

                <?php if ($projectConversationId !== null && $currentUserInProjectConversation): ?>
                    <a href="/pages/messages.php?conversation_id=<?php echo (int)$projectConversationId; ?>" class="detail-btn secondary-btn">
                        Open Project Chat
                    </a>
                <?php endif; ?>

                <?php if ($canApply): ?>
                    <a href="/pages/apply.php?project_id=<?php echo $projectId; ?>" class="detail-btn primary-btn">Apply</a>
                <?php endif; ?>

                <a href="/pages/report.php?project_id=<?php echo $projectId; ?>" class="detail-btn report-btn">Report</a>
            </div>
        </div>
    </div>

    <!-- INFO + SKILLS -->
    <div class="detail-grid two-col">

        <div class="detail-card">
            <div class="detail-card-title">Project Information</div>
            <div class="info-list">

                <div class="info-item">
                    <span class="info-label">Owner Email</span>
                    <span class="info-value"><?php echo displayValue($project['email']); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Course</span>
                    <span class="info-value"><?php echo safeText($courseDisplay); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Year</span>
                    <span class="info-value"><?php echo safeText($yearDisplay); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="info-value"><?php echo safeText(formatStatus($project['status'])); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Deadline</span>
                    <span class="info-value"><?php echo displayValue($project['deadline']); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Group Size</span>
                    <span class="info-value"><?php echo $teamSize; ?> Students</span>
                </div>

                <div class="info-item">
                    <span class="info-label">Current Members</span>
                    <span class="info-value"><?php echo $currentMemberCount; ?> / <?php echo $teamSize; ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Open Roles</span>
                    <span class="info-value"><?php echo $openRolesCount; ?></span>
                </div>

                <?php if ($isOwner): ?>
                    <div class="info-item">
                        <span class="info-label">Your Role</span>
                        <span class="info-value">Project Owner</span>
                    </div>
                <?php elseif ($isCurrentUserMember): ?>
                    <div class="info-item">
                        <span class="info-label">Your Status</span>
                        <span class="info-value">You are already a team member</span>
                    </div>
                <?php endif; ?>

                <?php if ($isProjectFull): ?>
                    <div class="info-item">
                        <span class="info-label">Availability</span>
                        <span class="info-value">Project is full</span>
                    </div>
                <?php elseif (!$isProjectOpen): ?>
                    <div class="info-item">
                        <span class="info-label">Availability</span>
                        <span class="info-value">Applications closed</span>
                    </div>
                <?php else: ?>
                    <div class="info-item">
                        <span class="info-label">Availability</span>
                        <span class="info-value">Applications open</span>
                    </div>
                <?php endif; ?>

                <?php if ($chatStatusText !== ''): ?>
                    <div class="info-item">
                        <span class="info-label">Project Chat</span>
                        <span class="info-value"><?php echo safeText($chatStatusText); ?></span>
                    </div>
                <?php endif; ?>

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
                    <p class="empty-text">No skills listed for this project yet.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- MEMBERS + OPEN ROLES -->
    <div class="detail-grid two-col">

        <div class="detail-card" id="members">
            <div class="detail-card-title">Team Members</div>
            <ul class="member-list">
                <?php if (!empty($members)): ?>
                    <?php foreach ($members as $member): ?>
                        <li class="member-item">
                            <a href="/pages/profile.php?user_id=<?php echo (int)$member['user_id']; ?>" class="member-link">
                                <span class="member-name"><?php echo safeText($member['email']); ?></span>
                                <span class="member-role"><?php echo safeText($member['role_title'] ?: 'Team Member'); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="member-item empty-text">No active team members found for this project yet.</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="detail-card">
            <div class="detail-card-title">Open Roles</div>
            <ul class="roles-list">
                <?php if (!empty($openRoles)): ?>
                    <?php foreach ($openRoles as $role): ?>
                        <li class="role-item">
                            <strong><?php echo safeText($role['title']); ?></strong>
                            <?php if (!empty($role['description'])): ?>
                                - <?php echo safeText($role['description']); ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="role-item empty-text">No open roles available for this project.</li>
                <?php endif; ?>
            </ul>
        </div>

    </div>

    <!-- ALL ROLES STATUS -->
    <div class="detail-card">
        <div class="detail-card-title">All Roles</div>
        <ul class="roles-list">
            <?php if (!empty($roles)): ?>
                <?php foreach ($roles as $role): ?>
                    <li class="role-item">
                        <strong><?php echo safeText($role['title']); ?></strong>
                        <?php if (!empty($role['description'])): ?>
                            - <?php echo safeText($role['description']); ?>
                        <?php endif; ?>
                        <span style="margin-left:8px; font-weight:600;">
                            (<?php echo ((int)$role['filled'] === 1) ? 'Filled' : 'Open'; ?>)
                        </span>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="role-item empty-text">No roles created for this project.</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- DESCRIPTION -->
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