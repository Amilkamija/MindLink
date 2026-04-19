<?php
/**
 * @file matches.php
 * @brief Handles project match suggestions, active requests, and accepted matches.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Matches - MindLink";
$extra_css = '/assets/css/matches.css';

require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
define('PROFILE_PICTURE_BASE', '/uploads/');

$successMessage = '';
$errorMessage = '';

/* ---------------- Utility Functions ---------------- */
function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function yearBadge($year) {
    return !empty($year) ? 'Y' . (int)$year : '';
}

function displayUserLabel($email) {
    $email = trim((string)$email);
    if ($email === '') {
        return 'User';
    }
    return strpos($email, '@') !== false ? explode('@', $email)[0] : $email;
}

function buildProfilePictureUrl($filename) {
    $filename = trim((string)$filename);

    if (
        $filename === '' ||
        strpos($filename, 'http://') === 0 ||
        strpos($filename, 'https://') === 0 ||
        strpos($filename, '/') === 0
    ) {
        return $filename;
    }

    return rtrim(PROFILE_PICTURE_BASE, '/') . '/' . ltrim($filename, '/');
}

function getItemArray($csv) {
    if (empty($csv)) return [];
    $items = array_map('trim', explode(',', (string)$csv));
    return array_values(array_filter(array_unique($items), fn($i) => $i !== ''));
}

function formatDisplayDate($dateValue) {
    if (empty($dateValue)) return '';
    $timestamp = strtotime((string)$dateValue);
    return $timestamp ? date('d M Y', $timestamp) : '';
}

function safePrepare(mysqli $conn, $sql) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logMessage = "[" . date('Y-m-d H:i:s') . "] DB Error: " . $conn->error . PHP_EOL;
        @file_put_contents($logDir . '/error_log.txt', $logMessage, FILE_APPEND);
        return null;
    }
    return $stmt;
}

function pageExists($filename) {
    return file_exists(__DIR__ . '/' . ltrim($filename, '/'));
}

/* ---------------- CSRF ---------------- */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/* ---------------- Helper DB Checks ---------------- */
function alreadyMatched(mysqli $conn, int $projectId, int $userA, int $userB): bool {
    $sql = "
        SELECT 1
        FROM ConnectionMatches
        WHERE project_id = ?
          AND ((user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?))
        LIMIT 1
    ";
    $stmt = safePrepare($conn, $sql);
    if (!$stmt) return false;

    $stmt->bind_param("iiiii", $projectId, $userA, $userB, $userB, $userA);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function requestExists(mysqli $conn, int $projectId, int $fromUser, int $toUser, ?string $status = null): bool {
    $sql = $status
        ? "SELECT 1 FROM ConnectionRequests WHERE project_id = ? AND from_user_id = ? AND to_user_id = ? AND status = ? LIMIT 1"
        : "SELECT 1 FROM ConnectionRequests WHERE project_id = ? AND from_user_id = ? AND to_user_id = ? LIMIT 1";

    $stmt = safePrepare($conn, $sql);
    if (!$stmt) return false;

    if ($status !== null) {
        $stmt->bind_param("iiis", $projectId, $fromUser, $toUser, $status);
    } else {
        $stmt->bind_param("iii", $projectId, $fromUser, $toUser);
    }

    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

/* ---------------- POST Handling ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCsrf($csrf)) {
        $errorMessage = "Invalid request token.";
    } else {
        if ($action === 'send_match') {
            $projectId = (int)($_POST['project_id'] ?? 0);
            $targetUserId = (int)($_POST['target_user_id'] ?? 0);

            if ($projectId <= 0 || $targetUserId <= 0) {
                $errorMessage = "Invalid match request.";
            } elseif ($targetUserId === $currentUserId) {
                $errorMessage = "You cannot match with yourself.";
            } elseif (alreadyMatched($conn, $projectId, $currentUserId, $targetUserId)) {
                $info = "You are already matched with this user for this project.";
                $successMessage = $info;
            } else {
                $conn->begin_transaction();

                try {
                    // Auto-accept if reverse pending request already exists
                    if (requestExists($conn, $projectId, $targetUserId, $currentUserId, 'pending')) {
                        $updateSql = "
                            UPDATE ConnectionRequests
                            SET status = 'accepted'
                            WHERE project_id = ?
                              AND from_user_id = ?
                              AND to_user_id = ?
                              AND status = 'pending'
                        ";
                        $stmt = safePrepare($conn, $updateSql);
                        if (!$stmt) {
                            throw new Exception("Could not accept reverse request.");
                        }
                        $stmt->bind_param("iii", $projectId, $targetUserId, $currentUserId);
                        $stmt->execute();
                        $stmt->close();

                        $user1 = min($currentUserId, $targetUserId);
                        $user2 = max($currentUserId, $targetUserId);

                        if (!alreadyMatched($conn, $projectId, $user1, $user2)) {
                            $insertSql = "
                                INSERT INTO ConnectionMatches (project_id, user1_id, user2_id)
                                VALUES (?, ?, ?)
                            ";
                            $stmt = safePrepare($conn, $insertSql);
                            if (!$stmt) {
                                throw new Exception("Could not create match.");
                            }
                            $stmt->bind_param("iii", $projectId, $user1, $user2);
                            $stmt->execute();
                            $stmt->close();
                        }

                        $successMessage = "Match accepted automatically.";
                    } elseif (!requestExists($conn, $projectId, $currentUserId, $targetUserId)) {
                        $insertSql = "
                            INSERT INTO ConnectionRequests (project_id, from_user_id, to_user_id, status)
                            VALUES (?, ?, ?, 'pending')
                        ";
                        $stmt = safePrepare($conn, $insertSql);
                        if (!$stmt) {
                            throw new Exception("Could not send match request.");
                        }
                        $stmt->bind_param("iii", $projectId, $currentUserId, $targetUserId);
                        $stmt->execute();
                        $stmt->close();

                        $successMessage = "Match request sent.";
                    } else {
                        $successMessage = "A request already exists.";
                    }

                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $errorMessage = "Could not process match request.";
                }
            }
        }

        if ($action === 'accept_match') {
            $requestId = (int)($_POST['request_id'] ?? 0);
            $projectId = (int)($_POST['project_id'] ?? 0);
            $fromUserId = (int)($_POST['from_user_id'] ?? 0);

            if ($requestId > 0 && $projectId > 0 && $fromUserId > 0) {
                $conn->begin_transaction();

                try {
                    $updateSql = "
                        UPDATE ConnectionRequests
                        SET status = 'accepted'
                        WHERE request_id = ?
                          AND to_user_id = ?
                          AND status = 'pending'
                    ";
                    $stmt = safePrepare($conn, $updateSql);
                    if (!$stmt) {
                        throw new Exception("Could not update request.");
                    }
                    $stmt->bind_param("ii", $requestId, $currentUserId);
                    $stmt->execute();
                    $stmt->close();

                    $user1 = min($currentUserId, $fromUserId);
                    $user2 = max($currentUserId, $fromUserId);

                    if (!alreadyMatched($conn, $projectId, $user1, $user2)) {
                        $insertMatchSql = "
                            INSERT INTO ConnectionMatches (project_id, user1_id, user2_id)
                            VALUES (?, ?, ?)
                        ";
                        $stmt = safePrepare($conn, $insertMatchSql);
                        if (!$stmt) {
                            throw new Exception("Could not create accepted match.");
                        }
                        $stmt->bind_param("iii", $projectId, $user1, $user2);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $conn->commit();
                    $successMessage = "Match accepted.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $errorMessage = "Could not accept match.";
                }
            }
        }

        if ($action === 'decline_match') {
            $requestId = (int)($_POST['request_id'] ?? 0);

            if ($requestId > 0) {
                $updateSql = "
                    UPDATE ConnectionRequests
                    SET status = 'declined'
                    WHERE request_id = ?
                      AND to_user_id = ?
                      AND status = 'pending'
                ";
                $stmt = safePrepare($conn, $updateSql);
                if ($stmt) {
                    $stmt->bind_param("ii", $requestId, $currentUserId);
                    $stmt->execute();
                    $stmt->close();
                    $successMessage = "Match request declined.";
                } else {
                    $errorMessage = "Could not decline match request.";
                }
            }
        }
    }
}

/* ---------------- Suggested teammate profiles from completed projects ---------------- */
$sqlProfiles = "
    SELECT
        teammate.user_id,
        teammate.email,
        teammate.course,
        teammate.year,
        teammate.bio,
        teammate.profile_picture,
        p.project_id,
        p.title AS project_title,
        p.owner_id,
        GROUP_CONCAT(DISTINCT s.skill_name ORDER BY s.skill_name SEPARATOR ', ') AS skills_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut.tag_type = 'interest' THEN ut.tag_name END SEPARATOR ', ') AS interests_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut.tag_type = 'hobby' THEN ut.tag_name END SEPARATOR ', ') AS hobbies_list
    FROM TeamMembership tm_me
    JOIN TeamMembership tm_other
        ON tm_me.project_id = tm_other.project_id
       AND tm_other.user_id != tm_me.user_id
       AND tm_other.status = 'active'
    JOIN Projects p
        ON tm_me.project_id = p.project_id
    JOIN Users teammate
        ON tm_other.user_id = teammate.user_id
    LEFT JOIN ConnectionPreferences pref
        ON teammate.user_id = pref.user_id
    LEFT JOIN UserSkills us
        ON teammate.user_id = us.user_id
    LEFT JOIN Skills s
        ON us.skill_id = s.skill_id
    LEFT JOIN UserTags ut
        ON teammate.user_id = ut.user_id
    WHERE tm_me.user_id = ?
      AND tm_me.status = 'active'
      AND p.status = 'completed'
      AND COALESCE(pref.allow_requests, 1) = 1
    GROUP BY
        teammate.user_id,
        teammate.email,
        teammate.course,
        teammate.year,
        teammate.bio,
        teammate.profile_picture,
        p.project_id,
        p.title,
        p.owner_id
    ORDER BY p.created_at DESC
";

$profiles = [];
$stmt = safePrepare($conn, $sqlProfiles);
if ($stmt) {
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $profiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

/* Remove already matched profiles from suggestion cards */
$profiles = array_values(array_filter($profiles, function ($profile) use ($conn, $currentUserId) {
    return !alreadyMatched(
        $conn,
        (int)$profile['project_id'],
        $currentUserId,
        (int)$profile['user_id']
    );
}));

/* ---------------- Pending incoming match requests ---------------- */
$sqlIncoming = "
    SELECT
        cr.request_id,
        cr.project_id,
        cr.from_user_id,
        cr.status,
        p.title AS project_title,
        p.owner_id,
        u.email,
        u.course,
        u.year,
        u.bio,
        u.profile_picture,
        GROUP_CONCAT(DISTINCT s.skill_name ORDER BY s.skill_name SEPARATOR ', ') AS skills_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut.tag_type = 'interest' THEN ut.tag_name END SEPARATOR ', ') AS interests_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut.tag_type = 'hobby' THEN ut.tag_name END SEPARATOR ', ') AS hobbies_list
    FROM ConnectionRequests cr
    JOIN Projects p
        ON cr.project_id = p.project_id
    JOIN Users u
        ON cr.from_user_id = u.user_id
    LEFT JOIN UserSkills us
        ON u.user_id = us.user_id
    LEFT JOIN Skills s
        ON us.skill_id = s.skill_id
    LEFT JOIN UserTags ut
        ON u.user_id = ut.user_id
    WHERE cr.to_user_id = ?
      AND cr.status = 'pending'
    GROUP BY
        cr.request_id,
        cr.project_id,
        cr.from_user_id,
        cr.status,
        p.title,
        p.owner_id,
        u.email,
        u.course,
        u.year,
        u.bio,
        u.profile_picture
    ORDER BY cr.request_id DESC
";

$incomingRequests = [];
$stmt = safePrepare($conn, $sqlIncoming);
if ($stmt) {
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $incomingRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

/* ---------------- Accepted matches ---------------- */
$sqlAccepted = "
    SELECT
        cm.match_id,
        cm.project_id,
        p.title AS project_title,
        p.owner_id,
        CASE
            WHEN cm.user1_id = ? THEN cm.user2_id
            ELSE cm.user1_id
        END AS other_user_id,
        u.email,
        u.course,
        u.year,
        u.bio,
        u.profile_picture,
        GROUP_CONCAT(DISTINCT s.skill_name ORDER BY s.skill_name SEPARATOR ', ') AS skills_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut.tag_type = 'interest' THEN ut.tag_name END SEPARATOR ', ') AS interests_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut.tag_type = 'hobby' THEN ut.tag_name END SEPARATOR ', ') AS hobbies_list
    FROM ConnectionMatches cm
    JOIN Projects p
        ON cm.project_id = p.project_id
    JOIN Users u
        ON u.user_id = CASE
            WHEN cm.user1_id = ? THEN cm.user2_id
            ELSE cm.user1_id
        END
    LEFT JOIN UserSkills us
        ON u.user_id = us.user_id
    LEFT JOIN Skills s
        ON us.skill_id = s.skill_id
    LEFT JOIN UserTags ut
        ON u.user_id = ut.user_id
    WHERE cm.user1_id = ? OR cm.user2_id = ?
    GROUP BY
        cm.match_id,
        cm.project_id,
        p.title,
        p.owner_id,
        other_user_id,
        u.email,
        u.course,
        u.year,
        u.bio,
        u.profile_picture
    ORDER BY cm.match_id DESC
";

$acceptedMatches = [];
$stmt = safePrepare($conn, $sqlAccepted);
if ($stmt) {
    $stmt->bind_param("iiii", $currentUserId, $currentUserId, $currentUserId, $currentUserId);
    $stmt->execute();
    $acceptedMatches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$messagesEnabled = pageExists('messages.php');
$projectDetailsEnabled = pageExists('project_details.php');
$applicationsEnabled = pageExists('applications.php');
?>

<div class="matches-page">
    <div class="content-area">

        <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success"><?php echo safeText($successMessage); ?></div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger"><?php echo safeText($errorMessage); ?></div>
        <?php endif; ?>

        <!-- Suggested Matches -->
        <section class="match-section">
            <div class="section-header">
                <h2 class="section-title">Suggested Matches</h2>
                <p class="section-subtitle">
                    These are teammates you previously worked with on completed projects. You can reconnect based on shared collaboration.
                </p>
            </div>

            <?php if (!empty($profiles)): ?>
                <div class="match-grid">
                    <?php foreach ($profiles as $profile): ?>
                        <?php
                        $skills = getItemArray($profile['skills_list'] ?? '');
                        $interests = getItemArray($profile['interests_list'] ?? '');
                        $hobbies = getItemArray($profile['hobbies_list'] ?? '');
                        $avatarUrl = buildProfilePictureUrl($profile['profile_picture'] ?? '');
                        $isProjectOwner = ((int)$profile['owner_id'] === $currentUserId);
                        ?>
                        <div class="match-card">
                            <div class="match-top">
                                <div class="match-user">
                                    <div class="match-avatar">
                                        <?php if ($avatarUrl !== ''): ?>
                                            <img src="<?php echo safeText($avatarUrl); ?>" alt="Profile" class="avatar-img">
                                        <?php else: ?>
                                            <span><?php echo strtoupper(substr(displayUserLabel($profile['email']), 0, 1)); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="match-user-info">
                                        <h3><?php echo safeText(displayUserLabel($profile['email'])); ?></h3>
                                        <p>
                                            <?php echo safeText($profile['course'] ?: 'Course not specified'); ?>
                                            <?php if (!empty($profile['year'])): ?>
                                                · <?php echo safeText(yearBadge($profile['year'])); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="match-project-name">
                                    <?php echo safeText($profile['project_title']); ?>
                                </div>
                            </div>

                            <?php if (!empty($profile['bio'])): ?>
                                <div class="match-bio">
                                    <?php echo nl2br(safeText($profile['bio'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($skills)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">Skills</div>
                                    <div class="tag-list">
                                        <?php foreach ($skills as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($interests)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">Interests</div>
                                    <div class="tag-list">
                                        <?php foreach ($interests as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($hobbies)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">Hobbies</div>
                                    <div class="tag-list">
                                        <?php foreach ($hobbies as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="match-actions">
                                <?php if ($projectDetailsEnabled): ?>
                                    <a href="/pages/project_details.php?project_id=<?php echo (int)$profile['project_id']; ?>" class="match-btn secondary-btn">
                                        View Project
                                    </a>
                                <?php endif; ?>

                                <?php if ($messagesEnabled): ?>
                                    <a href="/pages/messages.php?user_id=<?php echo (int)$profile['user_id']; ?>" class="match-btn secondary-btn">
                                        Message
                                    </a>
                                <?php endif; ?>

                                <?php if ($applicationsEnabled && $isProjectOwner): ?>
                                    <a href="/pages/applications.php?project_id=<?php echo (int)$profile['project_id']; ?>" class="match-btn secondary-btn">
                                        Applications
                                    </a>
                                <?php endif; ?>

                                <a href="/pages/report.php?user_id=<?php echo (int)$profile['user_id']; ?>" class="match-btn secondary-btn">
                                    Report
                                </a>

                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="action" value="send_match">
                                    <input type="hidden" name="project_id" value="<?php echo (int)$profile['project_id']; ?>">
                                    <input type="hidden" name="target_user_id" value="<?php echo (int)$profile['user_id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo safeText($_SESSION['csrf_token']); ?>">
                                    <button type="submit" class="match-btn primary-btn">Match</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-card">
                    <h4>No suggested matches right now</h4>
                    <p>Complete projects with teammates and allowed preferences will appear here.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Incoming Requests -->
        <section class="match-section">
            <div class="section-header">
                <h2 class="section-title">Incoming Match Requests</h2>
                <p class="section-subtitle">
                    Users who want to reconnect with you after working together on a project.
                </p>
            </div>

            <?php if (!empty($incomingRequests)): ?>
                <div class="match-grid">
                    <?php foreach ($incomingRequests as $request): ?>
                        <?php
                        $skills = getItemArray($request['skills_list'] ?? '');
                        $interests = getItemArray($request['interests_list'] ?? '');
                        $hobbies = getItemArray($request['hobbies_list'] ?? '');
                        $avatarUrl = buildProfilePictureUrl($request['profile_picture'] ?? '');
                        $isProjectOwner = ((int)$request['owner_id'] === $currentUserId);
                        ?>
                        <div class="match-card soft-card">
                            <div class="match-top">
                                <div class="match-user">
                                    <div class="match-avatar">
                                        <?php if ($avatarUrl !== ''): ?>
                                            <img src="<?php echo safeText($avatarUrl); ?>" alt="Profile" class="avatar-img">
                                        <?php else: ?>
                                            <span><?php echo strtoupper(substr(displayUserLabel($request['email']), 0, 1)); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="match-user-info">
                                        <h3><?php echo safeText(displayUserLabel($request['email'])); ?></h3>
                                        <p>
                                            <?php echo safeText($request['course'] ?: 'Course not specified'); ?>
                                            <?php if (!empty($request['year'])): ?>
                                                · <?php echo safeText(yearBadge($request['year'])); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="match-project-name">
                                    <?php echo safeText($request['project_title']); ?>
                                </div>
                            </div>

                            <?php if (!empty($request['bio'])): ?>
                                <div class="match-bio">
                                    <?php echo nl2br(safeText($request['bio'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($skills)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">Skills</div>
                                    <div class="tag-list">
                                        <?php foreach ($skills as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($interests)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">Interests</div>
                                    <div class="tag-list">
                                        <?php foreach ($interests as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($hobbies)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">Hobbies</div>
                                    <div class="tag-list">
                                        <?php foreach ($hobbies as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="match-actions">
                                <?php if ($projectDetailsEnabled): ?>
                                    <a href="/pages/project_details.php?project_id=<?php echo (int)$request['project_id']; ?>" class="match-btn secondary-btn">
                                        View Project
                                    </a>
                                <?php endif; ?>

                                <?php if ($messagesEnabled): ?>
                                    <a href="/pages/messages.php?user_id=<?php echo (int)$request['from_user_id']; ?>" class="match-btn secondary-btn">
                                        Message
                                    </a>
                                <?php endif; ?>

                                <?php if ($applicationsEnabled && $isProjectOwner): ?>
                                    <a href="/pages/applications.php?project_id=<?php echo (int)$request['project_id']; ?>" class="match-btn secondary-btn">
                                        Applications
                                    </a>
                                <?php endif; ?>

                                <a href="/pages/report.php?user_id=<?php echo (int)$request['from_user_id']; ?>" class="match-btn secondary-btn">
                                    Report
                                </a>

                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="action" value="accept_match">
                                    <input type="hidden" name="request_id" value="<?php echo (int)$request['request_id']; ?>">
                                    <input type="hidden" name="project_id" value="<?php echo (int)$request['project_id']; ?>">
                                    <input type="hidden" name="from_user_id" value="<?php echo (int)$request['from_user_id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo safeText($_SESSION['csrf_token']); ?>">
                                    <button type="submit" class="match-btn primary-btn">Accept</button>
                                </form>

                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="action" value="decline_match">
                                    <input type="hidden" name="request_id" value="<?php echo (int)$request['request_id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo safeText($_SESSION['csrf_token']); ?>">
                                    <button type="submit" class="match-btn danger-btn">Decline</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-card">
                    <h4>No incoming requests</h4>
                    <p>Any pending match requests sent to you will appear here.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Accepted Matches -->
        <section class="match-section">
            <div class="section-header">
                <h2 class="section-title">Accepted Matches</h2>
                <p class="section-subtitle">
                    People you successfully matched with after working together on a completed project.
                </p>
            </div>

            <?php if (!empty($acceptedMatches)): ?>
                <div class="match-grid">
                    <?php foreach ($acceptedMatches as $match): ?>
                        <?php
                        $skills = getItemArray($match['skills_list'] ?? '');
                        $interests = getItemArray($match['interests_list'] ?? '');
                        $hobbies = getItemArray($match['hobbies_list'] ?? '');
                        $avatarUrl = buildProfilePictureUrl($match['profile_picture'] ?? '');
                        $isProjectOwner = ((int)$match['owner_id'] === $currentUserId);
                        ?>
                        <div class="match-card accepted-card">
                            <div class="match-top">
                                <div class="match-user">
                                    <div class="match-avatar">
                                        <?php if ($avatarUrl !== ''): ?>
                                            <img src="<?php echo safeText($avatarUrl); ?>" alt="Profile" class="avatar-img">
                                        <?php else: ?>
                                            <span><?php echo strtoupper(substr(displayUserLabel($match['email']), 0, 1)); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="match-user-info">
                                        <h3><?php echo safeText(displayUserLabel($match['email'])); ?></h3>
                                        <p>
                                            <?php echo safeText($match['course'] ?: 'Course not specified'); ?>
                                            <?php if (!empty($match['year'])): ?>
                                                · <?php echo safeText(yearBadge($match['year'])); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="match-project-name">
                                    <?php echo safeText($match['project_title']); ?>
                                </div>
                            </div>

                            <?php if (!empty($match['bio'])): ?>
                                <div class="match-bio">
                                    <?php echo nl2br(safeText($match['bio'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($skills)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">Skills</div>
                                    <div class="tag-list">
                                        <?php foreach ($skills as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($interests)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">Interests</div>
                                    <div class="tag-list">
                                        <?php foreach ($interests as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($hobbies)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">Hobbies</div>
                                    <div class="tag-list">
                                        <?php foreach ($hobbies as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="match-actions">
                                <?php if ($projectDetailsEnabled): ?>
                                    <a href="/pages/project_details.php?project_id=<?php echo (int)$match['project_id']; ?>" class="match-btn secondary-btn">
                                        View Project
                                    </a>
                                <?php endif; ?>

                                <?php if ($messagesEnabled): ?>
                                    <a href="/pages/messages.php?user_id=<?php echo (int)$match['other_user_id']; ?>" class="match-btn primary-btn">
                                        Message
                                    </a>
                                <?php endif; ?>

                                <?php if ($applicationsEnabled && $isProjectOwner): ?>
                                    <a href="/pages/applications.php?project_id=<?php echo (int)$match['project_id']; ?>" class="match-btn secondary-btn">
                                        Applications
                                    </a>
                                <?php endif; ?>

                                <a href="/pages/report.php?user_id=<?php echo (int)$match['other_user_id']; ?>" class="match-btn secondary-btn">
                                    Report
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-card">
                    <h4>No accepted matches yet</h4>
                    <p>Accepted matches will appear here once both users connect.</p>
                </div>
            <?php endif; ?>
        </section>

    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>