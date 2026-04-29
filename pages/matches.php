<?php
/**
 * @file matches.php
 * @brief Handles pre-project matches, post-project matches, incoming requests, and accepted matches.
 */

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

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function yearBadge($year) {
    return !empty($year) ? 'Y' . (int)$year : '';
}

function displayUserLabel($email) {
    $email = trim((string)$email);
    if ($email === '') return 'User';
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

function normalizeProjectId($raw): ?int {
    if ($raw === '' || $raw === null) return null;

    $projectId = (int)$raw;
    return $projectId > 0 ? $projectId : null;
}

/* ---------------- CSRF ---------------- */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/* ---------------- User status check ---------------- */
function isActiveUser(mysqli $conn, int $userId): bool {
    $sql = "SELECT status FROM Users WHERE user_id = ? LIMIT 1";
    $stmt = safePrepare($conn, $sql);

    if (!$stmt) return false;

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    return $user && $user['status'] === 'active';
}

/* ---------------- Helper DB Checks ---------------- */
function alreadyMatched(mysqli $conn, ?int $projectId, int $userA, int $userB): bool {
    $sql = "
        SELECT 1
        FROM ConnectionMatches
        WHERE (
                (project_id = ?)
                OR (project_id IS NULL AND ? IS NULL)
              )
          AND (
                (user1_id = ? AND user2_id = ?)
                OR
                (user1_id = ? AND user2_id = ?)
              )
        LIMIT 1
    ";

    $stmt = safePrepare($conn, $sql);
    if (!$stmt) return false;

    $stmt->bind_param("iiiiii", $projectId, $projectId, $userA, $userB, $userB, $userA);
    $stmt->execute();

    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function requestExists(mysqli $conn, ?int $projectId, int $fromUser, int $toUser, ?string $status = null): bool {
    $sql = $status !== null
        ? "
            SELECT 1
            FROM ConnectionRequests
            WHERE (
                    (project_id = ?)
                    OR (project_id IS NULL AND ? IS NULL)
                  )
              AND from_user_id = ?
              AND to_user_id = ?
              AND status = ?
            LIMIT 1
          "
        : "
            SELECT 1
            FROM ConnectionRequests
            WHERE (
                    (project_id = ?)
                    OR (project_id IS NULL AND ? IS NULL)
                  )
              AND from_user_id = ?
              AND to_user_id = ?
            LIMIT 1
          ";

    $stmt = safePrepare($conn, $sql);
    if (!$stmt) return false;

    if ($status !== null) {
        $stmt->bind_param("iiiis", $projectId, $projectId, $fromUser, $toUser, $status);
    } else {
        $stmt->bind_param("iiii", $projectId, $projectId, $fromUser, $toUser);
    }

    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function getConnectionState(mysqli $conn, ?int $projectId, int $currentUserId, int $otherUserId): array {
    if (alreadyMatched($conn, $projectId, $currentUserId, $otherUserId)) {
        return [
            'state' => 'matched',
            'request_id' => null,
            'from_user_id' => null
        ];
    }

    $sql = "
        SELECT request_id, from_user_id, to_user_id, status
        FROM ConnectionRequests
        WHERE (
                (project_id = ?)
                OR (project_id IS NULL AND ? IS NULL)
              )
          AND (
                (from_user_id = ? AND to_user_id = ?)
                OR
                (from_user_id = ? AND to_user_id = ?)
              )
        ORDER BY request_id DESC
        LIMIT 1
    ";

    $stmt = safePrepare($conn, $sql);

    if (!$stmt) {
        return [
            'state' => 'none',
            'request_id' => null,
            'from_user_id' => null
        ];
    }

    $stmt->bind_param("iiiiii", $projectId, $projectId, $currentUserId, $otherUserId, $otherUserId, $currentUserId);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    if (!$row) {
        return [
            'state' => 'none',
            'request_id' => null,
            'from_user_id' => null
        ];
    }

    if ($row['status'] === 'pending') {
        if ((int)$row['from_user_id'] === $currentUserId) {
            return [
                'state' => 'outgoing_pending',
                'request_id' => (int)$row['request_id'],
                'from_user_id' => (int)$row['from_user_id']
            ];
        }

        return [
            'state' => 'incoming_pending',
            'request_id' => (int)$row['request_id'],
            'from_user_id' => (int)$row['from_user_id']
        ];
    }

    if ($row['status'] === 'accepted') {
        return [
            'state' => 'matched',
            'request_id' => (int)$row['request_id'],
            'from_user_id' => (int)$row['from_user_id']
        ];
    }

    return [
        'state' => 'none',
        'request_id' => null,
        'from_user_id' => null
    ];
}

$currentUserActive = isActiveUser($conn, $currentUserId);

/* ---------------- POST Handling ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCsrf($csrf)) {
        $errorMessage = "Invalid request token.";
    } elseif (!$currentUserActive) {
        $errorMessage = "Your account is suspended, so you cannot send, accept, or decline matches.";
    } else {

        if ($action === 'send_match') {
            $projectId = normalizeProjectId($_POST['project_id'] ?? null);
            $targetUserId = (int)($_POST['target_user_id'] ?? 0);

            if ($targetUserId <= 0) {
                $errorMessage = "Invalid match request.";
            } elseif ($targetUserId === $currentUserId) {
                $errorMessage = "You cannot match with yourself.";
            } elseif (!isActiveUser($conn, $targetUserId)) {
                $errorMessage = "This user is suspended, so you cannot match with them.";
            } elseif (alreadyMatched($conn, $projectId, $currentUserId, $targetUserId)) {
                $successMessage = "You are already connected with this user.";
            } else {
                $conn->begin_transaction();

                try {
                    if (requestExists($conn, $projectId, $targetUserId, $currentUserId, 'pending')) {
                        $updateSql = "
                            UPDATE ConnectionRequests
                            SET status = 'accepted'
                            WHERE (
                                    (project_id = ?)
                                    OR (project_id IS NULL AND ? IS NULL)
                                  )
                              AND from_user_id = ?
                              AND to_user_id = ?
                              AND status = 'pending'
                        ";

                        $stmt = safePrepare($conn, $updateSql);
                        if (!$stmt) throw new Exception("Could not accept reverse request.");

                        $stmt->bind_param("iiii", $projectId, $projectId, $targetUserId, $currentUserId);
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
                            if (!$stmt) throw new Exception("Could not create match.");

                            $stmt->bind_param("iii", $projectId, $user1, $user2);
                            $stmt->execute();
                            $stmt->close();
                        }

                        $successMessage = $projectId === null
                            ? "Pre-project match accepted automatically."
                            : "Post-project match accepted automatically.";
                    } elseif (!requestExists($conn, $projectId, $currentUserId, $targetUserId, 'pending')) {
                        $insertSql = "
                            INSERT INTO ConnectionRequests (project_id, from_user_id, to_user_id, status)
                            VALUES (?, ?, ?, 'pending')
                        ";

                        $stmt = safePrepare($conn, $insertSql);
                        if (!$stmt) throw new Exception("Could not send match request.");

                        $stmt->bind_param("iii", $projectId, $currentUserId, $targetUserId);
                        $stmt->execute();
                        $stmt->close();

                        $successMessage = $projectId === null
                            ? "Pre-project match request sent."
                            : "Post-project match request sent.";
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
            $projectId = normalizeProjectId($_POST['project_id'] ?? null);
            $fromUserId = (int)($_POST['from_user_id'] ?? 0);

            if ($requestId > 0 && $fromUserId > 0) {
                if (!isActiveUser($conn, $fromUserId)) {
                    $errorMessage = "This user is suspended, so you cannot accept this match.";
                } else {
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
                        if (!$stmt) throw new Exception("Could not update request.");

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
                            if (!$stmt) throw new Exception("Could not create accepted match.");

                            $stmt->bind_param("iii", $projectId, $user1, $user2);
                            $stmt->execute();
                            $stmt->close();
                        }

                        $conn->commit();

                        $successMessage = $projectId === null
                            ? "Pre-project match accepted."
                            : "Post-project match accepted.";
                    } catch (Exception $e) {
                        $conn->rollback();
                        $errorMessage = "Could not accept match.";
                    }
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

/* ---------------- Pre-project matches based on shared skills/tags ---------------- */
$sqlPreProjectMatches = "
    SELECT
        u.user_id,
        u.email,
        u.course,
        u.year,
        u.bio,
        u.profile_picture,

        COUNT(DISTINCT us_shared.skill_id) AS shared_skill_count,
        COUNT(DISTINCT CONCAT(ut_shared.tag_type, ':', LOWER(TRIM(ut_shared.tag_name)))) AS shared_tag_count,

        (
            COUNT(DISTINCT us_shared.skill_id) * 2
            +
            COUNT(DISTINCT CONCAT(ut_shared.tag_type, ':', LOWER(TRIM(ut_shared.tag_name))))
        ) AS match_score,

        GROUP_CONCAT(DISTINCT s_all.skill_name ORDER BY s_all.skill_name SEPARATOR ', ') AS skills_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut_all.tag_type = 'interest' THEN ut_all.tag_name END ORDER BY ut_all.tag_name SEPARATOR ', ') AS interests_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut_all.tag_type = 'hobby' THEN ut_all.tag_name END ORDER BY ut_all.tag_name SEPARATOR ', ') AS hobbies_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut_all.tag_type = 'general' THEN ut_all.tag_name END ORDER BY ut_all.tag_name SEPARATOR ', ') AS general_list,

        GROUP_CONCAT(DISTINCT s_shared.skill_name ORDER BY s_shared.skill_name SEPARATOR ', ') AS shared_skills_list,
        GROUP_CONCAT(DISTINCT ut_shared.tag_name ORDER BY ut_shared.tag_name SEPARATOR ', ') AS shared_tags_list

    FROM Users u

    LEFT JOIN ConnectionPreferences pref
        ON u.user_id = pref.user_id

    LEFT JOIN UserSkills us_shared
        ON u.user_id = us_shared.user_id
       AND us_shared.skill_id IN (
            SELECT skill_id
            FROM UserSkills
            WHERE user_id = ?
       )

    LEFT JOIN Skills s_shared
        ON us_shared.skill_id = s_shared.skill_id

    LEFT JOIN UserTags ut_shared
        ON u.user_id = ut_shared.user_id
       AND ut_shared.tag_type IN ('interest', 'hobby', 'general')
       AND LOWER(TRIM(ut_shared.tag_name)) IN (
            SELECT LOWER(TRIM(tag_name))
            FROM UserTags
            WHERE user_id = ?
              AND tag_type IN ('interest', 'hobby', 'general')
       )

    LEFT JOIN UserSkills us_all
        ON u.user_id = us_all.user_id

    LEFT JOIN Skills s_all
        ON us_all.skill_id = s_all.skill_id

    LEFT JOIN UserTags ut_all
        ON u.user_id = ut_all.user_id

    WHERE u.user_id != ?
      AND u.status = 'active'
      AND COALESCE(pref.allow_requests, 1) = 1
      AND NOT EXISTS (
            SELECT 1
            FROM ConnectionMatches cm
            WHERE cm.project_id IS NULL
              AND (
                    (cm.user1_id = ? AND cm.user2_id = u.user_id)
                    OR
                    (cm.user1_id = u.user_id AND cm.user2_id = ?)
                  )
      )

    GROUP BY
        u.user_id,
        u.email,
        u.course,
        u.year,
        u.bio,
        u.profile_picture

    HAVING shared_skill_count > 0 OR shared_tag_count > 0

    ORDER BY match_score DESC, shared_skill_count DESC, shared_tag_count DESC, u.user_id DESC
";

$preProjectMatches = [];
$stmt = safePrepare($conn, $sqlPreProjectMatches);

if ($stmt) {
    $stmt->bind_param("iiiii", $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId);
    $stmt->execute();
    $preProjectMatches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

/* ---------------- Post-project matches from completed projects ---------------- */
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
        GROUP_CONCAT(DISTINCT CASE WHEN ut.tag_type = 'hobby' THEN ut.tag_name END SEPARATOR ', ') AS hobbies_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut.tag_type = 'general' THEN ut.tag_name END SEPARATOR ', ') AS general_list

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
      AND teammate.status = 'active'
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

        CASE
            WHEN cr.project_id IS NULL THEN 'Pre-Project Request'
            ELSE p.title
        END AS project_title,

        CASE
            WHEN cr.project_id IS NULL THEN 'Pre-Project'
            ELSE 'Post-Project'
        END AS request_scope,

        p.owner_id,
        u.email,
        u.course,
        u.year,
        u.bio,
        u.profile_picture,

        GROUP_CONCAT(DISTINCT s.skill_name ORDER BY s.skill_name SEPARATOR ', ') AS skills_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut.tag_type = 'interest' THEN ut.tag_name END SEPARATOR ', ') AS interests_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut.tag_type = 'hobby' THEN ut.tag_name END SEPARATOR ', ') AS hobbies_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut.tag_type = 'general' THEN ut.tag_name END SEPARATOR ', ') AS general_list

    FROM ConnectionRequests cr

    LEFT JOIN Projects p
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
      AND u.status = 'active'

    GROUP BY
        cr.request_id,
        cr.project_id,
        cr.from_user_id,
        cr.status,
        project_title,
        request_scope,
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

        CASE
            WHEN cm.project_id IS NULL THEN 'Pre-Project Connection'
            ELSE p.title
        END AS project_title,

        CASE
            WHEN cm.project_id IS NULL THEN 'Pre-Project'
            ELSE 'Post-Project'
        END AS match_scope,

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
        GROUP_CONCAT(DISTINCT CASE WHEN ut.tag_type = 'hobby' THEN ut.tag_name END SEPARATOR ', ') AS hobbies_list,
        GROUP_CONCAT(DISTINCT CASE WHEN ut.tag_type = 'general' THEN ut.tag_name END SEPARATOR ', ') AS general_list

    FROM ConnectionMatches cm

    LEFT JOIN Projects p
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

    WHERE (cm.user1_id = ? OR cm.user2_id = ?)
      AND u.status = 'active'

    GROUP BY
        cm.match_id,
        cm.project_id,
        project_title,
        match_scope,
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

        <!-- Pre-Project Matches -->
        <section class="match-section">
            <div class="section-header">
                <h2 class="section-title">Pre-Project Matches</h2>
                <p class="section-subtitle">
                    These users are suggested before any project collaboration based on shared skills, interests, hobbies, and general tags.
                    Shared skills are weighted more strongly in the match score.
                </p>
            </div>

            <?php if (!empty($preProjectMatches)): ?>
                <div class="match-grid">
                    <?php foreach ($preProjectMatches as $profile): ?>
                        <?php
                        $skills = getItemArray($profile['skills_list'] ?? '');
                        $interests = getItemArray($profile['interests_list'] ?? '');
                        $hobbies = getItemArray($profile['hobbies_list'] ?? '');
                        $general = getItemArray($profile['general_list'] ?? '');
                        $sharedSkills = getItemArray($profile['shared_skills_list'] ?? '');
                        $sharedTags = getItemArray($profile['shared_tags_list'] ?? '');
                        $avatarUrl = buildProfilePictureUrl($profile['profile_picture'] ?? '');
                        $userInitial = strtoupper(substr(displayUserLabel($profile['email']), 0, 1));
                        $connectionState = getConnectionState($conn, null, $currentUserId, (int)$profile['user_id']);
                        ?>
                        <div class="match-card">
                            <div class="match-top">
                                <div class="match-user">
                                    <div class="match-avatar">
                                        <?php if ($avatarUrl !== ''): ?>
                                            <img
                                                src="<?php echo safeText($avatarUrl); ?>"
                                                alt="<?php echo safeText(displayUserLabel($profile['email'])); ?>"
                                                class="avatar-img"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            >
                                            <span class="avatar-fallback" style="display:none;"><?php echo safeText($userInitial); ?></span>
                                        <?php else: ?>
                                            <span class="avatar-fallback"><?php echo safeText($userInitial); ?></span>
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
                                    Score: <?php echo (int)$profile['match_score']; ?>
                                </div>
                            </div>

                            <?php if (!empty($profile['bio'])): ?>
                                <div class="match-bio">
                                    <?php echo nl2br(safeText($profile['bio'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($sharedSkills) || !empty($sharedTags)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">Why you matched</div>
                                    <div class="tag-list">
                                        <?php foreach ($sharedSkills as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?> · shared skill</span>
                                        <?php endforeach; ?>
                                        <?php foreach ($sharedTags as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?> · shared tag</span>
                                        <?php endforeach; ?>
                                    </div>
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

                            <?php if (!empty($general)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">General</div>
                                    <div class="tag-list">
                                        <?php foreach ($general as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="match-actions">
                                <?php if ($messagesEnabled && $connectionState['state'] === 'matched'): ?>
                                    <a href="/pages/messages.php?user_id=<?php echo (int)$profile['user_id']; ?>" class="match-btn primary-btn">
                                        Message
                                    </a>
                                <?php endif; ?>

                                <a href="/pages/report.php?user_id=<?php echo (int)$profile['user_id']; ?>" class="match-btn secondary-btn">
                                    Report
                                </a>

                                <?php if ($connectionState['state'] === 'none'): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="send_match">
                                        <input type="hidden" name="project_id" value="">
                                        <input type="hidden" name="target_user_id" value="<?php echo (int)$profile['user_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo safeText($_SESSION['csrf_token']); ?>">
                                        <button type="submit" class="match-btn secondary-btn">Match</button>
                                    </form>
                                <?php elseif ($connectionState['state'] === 'outgoing_pending'): ?>
                                    <button type="button" class="match-btn secondary-btn" disabled>Pending</button>
                                <?php elseif ($connectionState['state'] === 'incoming_pending'): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="accept_match">
                                        <input type="hidden" name="request_id" value="<?php echo (int)$connectionState['request_id']; ?>">
                                        <input type="hidden" name="project_id" value="">
                                        <input type="hidden" name="from_user_id" value="<?php echo (int)$connectionState['from_user_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo safeText($_SESSION['csrf_token']); ?>">
                                        <button type="submit" class="match-btn secondary-btn">Accept</button>
                                    </form>

                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="decline_match">
                                        <input type="hidden" name="request_id" value="<?php echo (int)$connectionState['request_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo safeText($_SESSION['csrf_token']); ?>">
                                        <button type="submit" class="match-btn danger-btn">Decline</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-card">
                    <h4>No pre-project matches right now</h4>
                    <p>Add more skills, interests, hobbies, or general tags to your profile to get better recommendations.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Post-Project Matches -->
        <section class="match-section">
            <div class="section-header">
                <h2 class="section-title">Post-Project Matches</h2>
                <p class="section-subtitle">
                    These are teammates you previously worked with on completed projects. You can reconnect based on shared collaboration history.
                </p>
            </div>

            <?php if (!empty($profiles)): ?>
                <div class="match-grid">
                    <?php foreach ($profiles as $profile): ?>
                        <?php
                        $skills = getItemArray($profile['skills_list'] ?? '');
                        $interests = getItemArray($profile['interests_list'] ?? '');
                        $hobbies = getItemArray($profile['hobbies_list'] ?? '');
                        $general = getItemArray($profile['general_list'] ?? '');
                        $avatarUrl = buildProfilePictureUrl($profile['profile_picture'] ?? '');
                        $isProjectOwner = ((int)$profile['owner_id'] === $currentUserId);
                        $userInitial = strtoupper(substr(displayUserLabel($profile['email']), 0, 1));
                        $connectionState = getConnectionState($conn, (int)$profile['project_id'], $currentUserId, (int)$profile['user_id']);
                        ?>
                        <div class="match-card">
                            <div class="match-top">
                                <div class="match-user">
                                    <div class="match-avatar">
                                        <?php if ($avatarUrl !== ''): ?>
                                            <img
                                                src="<?php echo safeText($avatarUrl); ?>"
                                                alt="<?php echo safeText(displayUserLabel($profile['email'])); ?>"
                                                class="avatar-img"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            >
                                            <span class="avatar-fallback" style="display:none;"><?php echo safeText($userInitial); ?></span>
                                        <?php else: ?>
                                            <span class="avatar-fallback"><?php echo safeText($userInitial); ?></span>
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

                            <?php if (!empty($general)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">General</div>
                                    <div class="tag-list">
                                        <?php foreach ($general as $item): ?>
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

                                <?php if ($messagesEnabled && $connectionState['state'] === 'matched'): ?>
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

                                <?php if ($connectionState['state'] === 'none'): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="send_match">
                                        <input type="hidden" name="project_id" value="<?php echo (int)$profile['project_id']; ?>">
                                        <input type="hidden" name="target_user_id" value="<?php echo (int)$profile['user_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo safeText($_SESSION['csrf_token']); ?>">
                                        <button type="submit" class="match-btn primary-btn">Match</button>
                                    </form>
                                <?php elseif ($connectionState['state'] === 'outgoing_pending'): ?>
                                    <button type="button" class="match-btn secondary-btn" disabled>Pending</button>
                                <?php elseif ($connectionState['state'] === 'incoming_pending'): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="accept_match">
                                        <input type="hidden" name="request_id" value="<?php echo (int)$connectionState['request_id']; ?>">
                                        <input type="hidden" name="project_id" value="<?php echo (int)$profile['project_id']; ?>">
                                        <input type="hidden" name="from_user_id" value="<?php echo (int)$connectionState['from_user_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo safeText($_SESSION['csrf_token']); ?>">
                                        <button type="submit" class="match-btn primary-btn">Accept</button>
                                    </form>

                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="action" value="decline_match">
                                        <input type="hidden" name="request_id" value="<?php echo (int)$connectionState['request_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo safeText($_SESSION['csrf_token']); ?>">
                                        <button type="submit" class="match-btn danger-btn">Decline</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-card">
                    <h4>No post-project matches right now</h4>
                    <p>Complete projects with teammates and allowed preferences will appear here.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Incoming Requests -->
        <section class="match-section">
            <div class="section-header">
                <h2 class="section-title">Incoming Match Requests</h2>
                <p class="section-subtitle">
                    Users who sent you pre-project or post-project match requests.
                </p>
            </div>

            <?php if (!empty($incomingRequests)): ?>
                <div class="match-grid">
                    <?php foreach ($incomingRequests as $request): ?>
                        <?php
                        $skills = getItemArray($request['skills_list'] ?? '');
                        $interests = getItemArray($request['interests_list'] ?? '');
                        $hobbies = getItemArray($request['hobbies_list'] ?? '');
                        $general = getItemArray($request['general_list'] ?? '');
                        $avatarUrl = buildProfilePictureUrl($request['profile_picture'] ?? '');
                        $userInitial = strtoupper(substr(displayUserLabel($request['email']), 0, 1));
                        ?>
                        <div class="match-card soft-card">
                            <div class="match-top">
                                <div class="match-user">
                                    <div class="match-avatar">
                                        <?php if ($avatarUrl !== ''): ?>
                                            <img
                                                src="<?php echo safeText($avatarUrl); ?>"
                                                alt="<?php echo safeText(displayUserLabel($request['email'])); ?>"
                                                class="avatar-img"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            >
                                            <span class="avatar-fallback" style="display:none;"><?php echo safeText($userInitial); ?></span>
                                        <?php else: ?>
                                            <span class="avatar-fallback"><?php echo safeText($userInitial); ?></span>
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

                            <?php if (!empty($general)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">General</div>
                                    <div class="tag-list">
                                        <?php foreach ($general as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="match-actions">
                                <a href="/pages/report.php?user_id=<?php echo (int)$request['from_user_id']; ?>" class="match-btn secondary-btn">
                                    Report
                                </a>

                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="action" value="accept_match">
                                    <input type="hidden" name="request_id" value="<?php echo (int)$request['request_id']; ?>">
                                    <input type="hidden" name="project_id" value="<?php echo $request['project_id'] !== null ? (int)$request['project_id'] : ''; ?>">
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
                    <p>Any pending pre-project or post-project match requests sent to you will appear here.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Accepted Matches -->
        <section class="match-section">
            <div class="section-header">
                <h2 class="section-title">Accepted Matches</h2>
                <p class="section-subtitle">
                    People you successfully matched with through pre-project or post-project connections.
                </p>
            </div>

            <?php if (!empty($acceptedMatches)): ?>
                <div class="match-grid">
                    <?php foreach ($acceptedMatches as $match): ?>
                        <?php
                        $skills = getItemArray($match['skills_list'] ?? '');
                        $interests = getItemArray($match['interests_list'] ?? '');
                        $hobbies = getItemArray($match['hobbies_list'] ?? '');
                        $general = getItemArray($match['general_list'] ?? '');
                        $avatarUrl = buildProfilePictureUrl($match['profile_picture'] ?? '');
                        $userInitial = strtoupper(substr(displayUserLabel($match['email']), 0, 1));
                        ?>
                        <div class="match-card accepted-card">
                            <div class="match-top">
                                <div class="match-user">
                                    <div class="match-avatar">
                                        <?php if ($avatarUrl !== ''): ?>
                                            <img
                                                src="<?php echo safeText($avatarUrl); ?>"
                                                alt="<?php echo safeText(displayUserLabel($match['email'])); ?>"
                                                class="avatar-img"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            >
                                            <span class="avatar-fallback" style="display:none;"><?php echo safeText($userInitial); ?></span>
                                        <?php else: ?>
                                            <span class="avatar-fallback"><?php echo safeText($userInitial); ?></span>
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

                            <?php if (!empty($general)): ?>
                                <div class="tag-block">
                                    <div class="tag-label">General</div>
                                    <div class="tag-list">
                                        <?php foreach ($general as $item): ?>
                                            <span class="tag-chip"><?php echo safeText($item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="match-actions">
                                <?php if ($messagesEnabled): ?>
                                    <a href="/pages/messages.php?user_id=<?php echo (int)$match['other_user_id']; ?>" class="match-btn primary-btn">
                                        Message
                                    </a>
                                <?php endif; ?>

                                <?php if ($match['project_id'] !== null && $projectDetailsEnabled): ?>
                                    <a href="/pages/project_details.php?project_id=<?php echo (int)$match['project_id']; ?>" class="match-btn secondary-btn">
                                        View Project
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
                    <p>Accepted matches will appear here once users connect.</p>
                </div>
            <?php endif; ?>
        </section>

    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>