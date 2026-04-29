<?php

$pageTitle = "Project Details - MindLink";
$extra_css = '/assets/css/project_details.css';

require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

/* ---------------- Helpers ---------------- */
function safeText($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function displayValue($value, $fallback = 'Not specified')
{
    $value = trim((string)$value);
    return $value !== '' ? safeText($value) : safeText($fallback);
}

function formatProjectStatus($status)
{
    $status = strtolower(trim((string)$status));

    switch ($status) {
        case 'open':
            return 'Open';
        case 'in_progress':
            return 'In Progress';
        case 'completed':
            return 'Completed';
        case 'closed':
            return 'Closed';
        case 'suspended':
            return 'Suspended';
        case 'reported':
            return 'Reported';
        case 'under_review':
            return 'Under Review';
        default:
            return ucfirst(str_replace('_', ' ', (string)$status));
    }
}

function formatDateTimeReadable($dateValue, $fallback = 'Not specified')
{
    $dateValue = trim((string)$dateValue);

    if ($dateValue === '' || $dateValue === '0000-00-00' || $dateValue === '0000-00-00 00:00:00') {
        return $fallback;
    }

    $timestamp = strtotime($dateValue);
    if ($timestamp === false) {
        return $fallback;
    }

    return date('d M Y, H:i', $timestamp);
}

function formatDateReadable($dateValue, $fallback = 'Not specified')
{
    $dateValue = trim((string)$dateValue);

    if ($dateValue === '' || $dateValue === '0000-00-00' || $dateValue === '0000-00-00 00:00:00') {
        return $fallback;
    }

    $timestamp = strtotime($dateValue);
    if ($timestamp === false) {
        return $fallback;
    }

    return date('d M Y', $timestamp);
}

function pageExists($filename)
{
    return file_exists(__DIR__ . '/' . ltrim($filename, '/'));
}

function showFriendlyError($message)
{
    echo '<div class="content-area">';
    echo '  <div class="detail-card">';
    echo '      <div class="alert alert-danger">' . safeText($message) . '</div>';
    echo '      <div style="margin-top:16px;">';
    echo '          <a href="/pages/projects.php" class="detail-btn secondary-btn">Back to Projects</a>';
    echo '      </div>';
    echo '  </div>';
    echo '</div>';

    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

function syncProjectConversationParticipants(mysqli $conn, int $projectId, int $ownerId, int $conversationId): void
{
    $insertParticipantSql = "
        INSERT IGNORE INTO ConversationParticipants (conversation_id, user_id)
        VALUES (?, ?)
    ";

    $insertStmt = $conn->prepare($insertParticipantSql);
    if (!$insertStmt) {
        return;
    }

    // Add owner
    $insertStmt->bind_param("ii", $conversationId, $ownerId);
    $insertStmt->execute();

    // Add active members
    $membersSql = "
        SELECT user_id
        FROM TeamMembership
        WHERE project_id = ? AND status = 'active'
    ";
    $membersStmt = $conn->prepare($membersSql);

    if ($membersStmt) {
        $membersStmt->bind_param("i", $projectId);
        $membersStmt->execute();
        $membersResult = $membersStmt->get_result();

        while ($member = $membersResult->fetch_assoc()) {
            $memberUserId = (int)$member['user_id'];
            $insertStmt->bind_param("ii", $conversationId, $memberUserId);
            $insertStmt->execute();
        }

        $membersStmt->close();
    }

    $insertStmt->close();
}

/* ---------------- Validate project_id ---------------- */
if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id'])) {
    showFriendlyError("Invalid project ID.");
}

$projectId = (int)$_GET['project_id'];

if ($projectId <= 0) {
    showFriendlyError("Invalid project ID.");
}

/* ---------------- Fetch project + owner ---------------- */
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
        u.year,
        u.status AS owner_status
    FROM Projects p
    JOIN Users u ON p.owner_id = u.user_id
    WHERE p.project_id = ?
    LIMIT 1
";

$stmtProject = $conn->prepare($sqlProject);
if (!$stmtProject) {
    showFriendlyError("Unable to load project details right now.");
}

$stmtProject->bind_param("i", $projectId);
$stmtProject->execute();
$resultProject = $stmtProject->get_result();

if (!$resultProject || $resultProject->num_rows === 0) {
    $stmtProject->close();
    showFriendlyError("Project not found.");
}

$project = $resultProject->fetch_assoc();
$stmtProject->close();

$ownerId = (int)$project['owner_id'];
$teamSize = (int)($project['team_size'] ?? 0);
$isOwner = ($ownerId === $currentUserId);

$projectStatus = strtolower(trim((string)($project['status'] ?? '')));
$ownerStatus = strtolower(trim((string)($project['owner_status'] ?? 'active')));

$isProjectRestricted = in_array($projectStatus, ['suspended', 'reported', 'blocked', 'under_review'], true);
$isOwnerRestricted = in_array($ownerStatus, ['suspended', 'reported', 'blocked', 'under_review'], true);

$restrictionMessages = [];
if ($isProjectRestricted) {
    $restrictionMessages[] = 'This project is currently ' . formatProjectStatus($projectStatus) . '.';
}
if ($isOwnerRestricted) {
    $restrictionMessages[] = 'The project owner account is currently restricted.';
}

/* ---------------- Count active team members ---------------- */
$currentMemberCount = 0;

$sqlMemberCount = "
    SELECT COUNT(*) AS member_count
    FROM TeamMembership
    WHERE project_id = ? AND status = 'active'
";
$stmtMemberCount = $conn->prepare($sqlMemberCount);

if ($stmtMemberCount) {
    $stmtMemberCount->bind_param("i", $projectId);
    $stmtMemberCount->execute();
    $resultMemberCount = $stmtMemberCount->get_result();
    $memberCountRow = $resultMemberCount ? $resultMemberCount->fetch_assoc() : [];
    $currentMemberCount = (int)($memberCountRow['member_count'] ?? 0);
    $stmtMemberCount->close();
}

/* ---------------- Check current user membership ---------------- */
$isCurrentUserMember = false;

$sqlCheckMembership = "
    SELECT membership_id
    FROM TeamMembership
    WHERE project_id = ? AND user_id = ? AND status = 'active'
    LIMIT 1
";
$stmtCheckMembership = $conn->prepare($sqlCheckMembership);

if ($stmtCheckMembership) {
    $stmtCheckMembership->bind_param("ii", $projectId, $currentUserId);
    $stmtCheckMembership->execute();
    $resultCheckMembership = $stmtCheckMembership->get_result();
    $isCurrentUserMember = ($resultCheckMembership && $resultCheckMembership->num_rows > 0);
    $stmtCheckMembership->close();
}

/* ---------------- Roles ---------------- */
$roles = [];
$openRoles = [];

$sqlRoles = "
    SELECT role_id, title, description, filled
    FROM Roles
    WHERE project_id = ?
    ORDER BY role_id ASC
";
$stmtRoles = $conn->prepare($sqlRoles);

if ($stmtRoles) {
    $stmtRoles->bind_param("i", $projectId);
    $stmtRoles->execute();
    $resultRoles = $stmtRoles->get_result();

    while ($row = $resultRoles->fetch_assoc()) {
        $roles[] = $row;
        if ((int)$row['filled'] === 0) {
            $openRoles[] = $row;
        }
    }

    $stmtRoles->close();
}

$openRolesCount = count($openRoles);

/* ---------------- Members ---------------- */
$members = [];

$sqlMembers = "
    SELECT
        tm.user_id,
        tm.role_id,
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

if ($stmtMembers) {
    $stmtMembers->bind_param("i", $projectId);
    $stmtMembers->execute();
    $resultMembers = $stmtMembers->get_result();

    while ($row = $resultMembers->fetch_assoc()) {
        $members[] = $row;
    }

    $stmtMembers->close();
}

/* ---------------- Required skills ---------------- */
$tags = [];

$sqlTags = "
    SELECT tag_name
    FROM ProjectTags
    WHERE project_id = ?
    ORDER BY tag_name ASC
";
$stmtTags = $conn->prepare($sqlTags);

if ($stmtTags) {
    $stmtTags->bind_param("i", $projectId);
    $stmtTags->execute();
    $resultTags = $stmtTags->get_result();

    while ($row = $resultTags->fetch_assoc()) {
        $tags[] = $row['tag_name'];
    }

    $stmtTags->close();
}

/* ---------------- Check if current user already applied ---------------- */
$applicationStatus = null;
$applicationAppliedAt = null;
$hasAlreadyApplied = false;

$sqlApplication = "
    SELECT status, applied_at
    FROM Applications
    WHERE project_id = ? AND applicant_id = ?
    ORDER BY application_id DESC
    LIMIT 1
";
$stmtApplication = $conn->prepare($sqlApplication);

if ($stmtApplication) {
    $stmtApplication->bind_param("ii", $projectId, $currentUserId);
    $stmtApplication->execute();
    $resultApplication = $stmtApplication->get_result();

    if ($resultApplication && $resultApplication->num_rows > 0) {
        $applicationRow = $resultApplication->fetch_assoc();
        $applicationStatus = (string)($applicationRow['status'] ?? '');
        $applicationAppliedAt = (string)($applicationRow['applied_at'] ?? '');
        $hasAlreadyApplied = true;
    }

    $stmtApplication->close();
}

/* ---------------- Deadline handling ---------------- */
$deadlineRaw = trim((string)($project['deadline'] ?? ''));
$deadlineTimestamp = ($deadlineRaw !== '') ? strtotime($deadlineRaw) : false;
$deadlineFormatted = formatDateTimeReadable($deadlineRaw, 'Not specified');
$isDeadlineExpired = ($deadlineTimestamp !== false && $deadlineTimestamp < time());

/* ---------------- Project state checks ---------------- */
$isProjectOpen = ($projectStatus === 'open');
$isProjectFull = ($teamSize > 0 && $currentMemberCount >= $teamSize);

$canApply = (
    !$isOwner &&
    !$isCurrentUserMember &&
    !$hasAlreadyApplied &&
    !$isProjectRestricted &&
    !$isOwnerRestricted &&
    $isProjectOpen &&
    !$isProjectFull &&
    !$isDeadlineExpired &&
    $openRolesCount > 0
);

/* ---------------- Project conversation checks ---------------- */
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

    if ($resultConversation && $resultConversation->num_rows > 0) {
        $conversationRow = $resultConversation->fetch_assoc();
        $projectConversationId = (int)$conversationRow['conversation_id'];
    }

    $stmtConversation->close();
}

if ($projectConversationId !== null) {
    syncProjectConversationParticipants($conn, $projectId, $ownerId, $projectConversationId);

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
        $currentUserInProjectConversation = ($participantResult && $participantResult->num_rows > 0);
        $stmtParticipantCheck->close();
    }
}

$canOpenChat = (
    !$isProjectRestricted &&
    !$isOwnerRestricted &&
    $projectConversationId !== null &&
    $currentUserInProjectConversation
);

/* ---------------- Friendly display values ---------------- */
$courseDisplay = trim((string)$project['course']) !== '' ? $project['course'] : 'Not specified';
$yearDisplay = ((int)$project['year'] > 0) ? ('Year ' . (int)$project['year']) : 'Not specified';
$createdAtDisplay = formatDateReadable($project['created_at'], 'Not specified');

$chatStatusText = '';
if ($projectConversationId === null) {
    $chatStatusText = 'Project chat has not been created yet.';
} elseif (!$currentUserInProjectConversation) {
    $chatStatusText = 'Project chat exists, but you are not yet added to it.';
} else {
    $chatStatusText = 'You can access the project chat.';
}

$applicationStatusDisplay = $hasAlreadyApplied
    ? ucfirst(str_replace('_', ' ', (string)$applicationStatus))
    : '';

/* ---------------- Owner action links ---------------- */
$editProjectUrl = pageExists('edit_project.php')
    ? '/pages/edit_project.php?project_id=' . (int)$projectId
    : null;

$deleteProjectUrl = pageExists('delete_project.php')
    ? '/pages/delete_project.php?project_id=' . (int)$projectId
    : null;

$manageApplicationsUrl = pageExists('applications.php')
    ? '/pages/applications.php?project_id=' . (int)$projectId
    : null;

$matchesEnabled = pageExists('matches.php');
?>

<div class="content-area">

    <?php if (!empty($restrictionMessages)): ?>
        <div class="detail-card">
            <div class="alert alert-warning">
                <?php foreach ($restrictionMessages as $message): ?>
                    <div><?php echo safeText($message); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="detail-card header-card">
        <div class="detail-header">
            <div class="header-info">
                <h1 class="project-title"><?php echo safeText($project['title']); ?></h1>
                <p class="project-subtitle">
                    View project details, team members, required skills, roles, chat status, and applications.
                </p>
            </div>

            <div class="project-actions">
                <a href="#members" class="detail-btn secondary-btn">View Members</a>

                <?php if ($canOpenChat): ?>
                    <a href="/pages/messages.php?conversation_id=<?php echo (int)$projectConversationId; ?>" class="detail-btn secondary-btn">Open Project Chat</a>
                <?php endif; ?>

                <?php if ($canApply): ?>
                    <a href="/pages/apply.php?project_id=<?php echo (int)$projectId; ?>" class="detail-btn primary-btn">Apply</a>
                <?php elseif ($hasAlreadyApplied): ?>
                    <button class="detail-btn secondary-btn" disabled>
                        Applied (<?php echo safeText($applicationStatusDisplay ?: 'Pending'); ?>)
                    </button>
                <?php elseif ($isOwner): ?>
                    <button class="detail-btn secondary-btn" disabled>You own this project</button>
                <?php elseif ($isCurrentUserMember): ?>
                    <button class="detail-btn secondary-btn" disabled>Already a team member</button>
                <?php elseif ($isProjectRestricted || $isOwnerRestricted): ?>
                    <button class="detail-btn secondary-btn" disabled>Applications unavailable</button>
                <?php elseif ($isDeadlineExpired): ?>
                    <button class="detail-btn secondary-btn" disabled>Deadline passed</button>
                <?php elseif ($isProjectFull): ?>
                    <button class="detail-btn secondary-btn" disabled>Project full</button>
                <?php elseif (!$isProjectOpen): ?>
                    <button class="detail-btn secondary-btn" disabled>Applications closed</button>
                <?php endif; ?>

                <a href="/pages/report.php?project_id=<?php echo (int)$projectId; ?>" class="detail-btn report-btn">Report</a>

                <?php if ($isOwner && $editProjectUrl !== null): ?>
                    <a href="<?php echo safeText($editProjectUrl); ?>" class="detail-btn secondary-btn">Edit Project</a>
                <?php endif; ?>

                <?php if ($isOwner && $manageApplicationsUrl !== null): ?>
                    <a href="<?php echo safeText($manageApplicationsUrl); ?>" class="detail-btn secondary-btn">Manage Applications</a>
                <?php endif; ?>

                <?php if ($isOwner && $deleteProjectUrl !== null): ?>
                    <a href="<?php echo safeText($deleteProjectUrl); ?>" class="detail-btn report-btn"
                       onclick="return confirm('Are you sure you want to delete this project?');">
                        Delete
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

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
                    <span class="info-value"><?php echo safeText(formatProjectStatus($project['status'])); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Created</span>
                    <span class="info-value"><?php echo safeText($createdAtDisplay); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Deadline</span>
                    <span class="info-value"><?php echo safeText($deadlineFormatted); ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Deadline Status</span>
                    <span class="info-value"><?php echo $isDeadlineExpired ? 'Expired' : 'Active'; ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Group Size</span>
                    <span class="info-value"><?php echo (int)$teamSize; ?> Students</span>
                </div>

                <div class="info-item">
                    <span class="info-label">Current Members</span>
                    <span class="info-value"><?php echo (int)$currentMemberCount; ?> / <?php echo (int)$teamSize; ?></span>
                </div>

                <div class="info-item">
                    <span class="info-label">Open Roles</span>
                    <span class="info-value"><?php echo (int)$openRolesCount; ?></span>
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

                <?php if ($hasAlreadyApplied): ?>
                    <div class="info-item">
                        <span class="info-label">Application Status</span>
                        <span class="info-value"><?php echo safeText($applicationStatusDisplay ?: 'Pending'); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Applied On</span>
                        <span class="info-value"><?php echo safeText(formatDateTimeReadable($applicationAppliedAt, 'Not recorded')); ?></span>
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
                <?php elseif ($isDeadlineExpired): ?>
                    <div class="info-item">
                        <span class="info-label">Availability</span>
                        <span class="info-value">Deadline has passed</span>
                    </div>
                <?php elseif ($isProjectRestricted || $isOwnerRestricted): ?>
                    <div class="info-item">
                        <span class="info-label">Availability</span>
                        <span class="info-value">Restricted</span>
                    </div>
                <?php else: ?>
                    <div class="info-item">
                        <span class="info-label">Availability</span>
                        <span class="info-value">Applications open</span>
                    </div>
                <?php endif; ?>

                <div class="info-item">
                    <span class="info-label">Project Chat</span>
                    <span class="info-value"><?php echo safeText($chatStatusText); ?></span>
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
                    <p class="empty-text">No skills listed for this project yet.</p>
                <?php endif; ?>
            </div>

            <div class="detail-card-title inner-title">Project Description</div>
            <div class="description-box scroll-box">
                <?php if (trim((string)$project['description']) !== ''): ?>
                    <p><?php echo nl2br(safeText($project['description'])); ?></p>
                <?php else: ?>
                    <p class="empty-text">No project description added yet.</p>
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
                            <div class="member-link" style="width:100%;">
                                <span class="member-name"><?php echo safeText($member['email']); ?></span>
                                <span class="member-role"><?php echo safeText($member['role_title'] ?: 'Team Member'); ?></span>
                            </div>
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

</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>