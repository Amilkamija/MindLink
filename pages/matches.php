<?php
$pageTitle = "Matches - MindLink";
$extra_css = '/assets/css/matches.css';

require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$current_user_id = (int) $_SESSION['user_id'];

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function yearBadge($year) {
    if (!empty($year)) {
        return 'Y' . (int)$year;
    }
    return '';
}

function alreadyMatched($conn, $projectId, $userA, $userB) {
    $sql = "
        SELECT 1
        FROM ConnectionMatches
        WHERE project_id = ?
          AND (
                (user1_id = ? AND user2_id = ?)
             OR (user1_id = ? AND user2_id = ?)
          )
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("iiiii", $projectId, $userA, $userB, $userB, $userA);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function requestExists($conn, $projectId, $fromUser, $toUser, $status = null) {
    if ($status !== null) {
        $sql = "
            SELECT 1
            FROM ConnectionRequests
            WHERE project_id = ?
              AND from_user_id = ?
              AND to_user_id = ?
              AND status = ?
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("iiis", $projectId, $fromUser, $toUser, $status);
    } else {
        $sql = "
            SELECT 1
            FROM ConnectionRequests
            WHERE project_id = ?
              AND from_user_id = ?
              AND to_user_id = ?
            LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("iii", $projectId, $fromUser, $toUser);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

/*
|--------------------------------------------------------------------------
| Handle POST actions
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'send_match') {
        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $targetUserId = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;

        if ($projectId > 0 && $targetUserId > 0 && $targetUserId !== $current_user_id) {
            if (!alreadyMatched($conn, $projectId, $current_user_id, $targetUserId)) {
                // If reverse pending request exists, accept it and create match
                if (requestExists($conn, $projectId, $targetUserId, $current_user_id, 'pending')) {
                    $updateSql = "
                        UPDATE ConnectionRequests
                        SET status = 'accepted'
                        WHERE project_id = ?
                          AND from_user_id = ?
                          AND to_user_id = ?
                          AND status = 'pending'
                    ";
                    $updateStmt = $conn->prepare($updateSql);
                    if ($updateStmt) {
                        $updateStmt->bind_param("iii", $projectId, $targetUserId, $current_user_id);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }

                    $user1 = min($current_user_id, $targetUserId);
                    $user2 = max($current_user_id, $targetUserId);

                    if (!alreadyMatched($conn, $projectId, $user1, $user2)) {
                        $insertMatchSql = "
                            INSERT INTO ConnectionMatches (project_id, user1_id, user2_id)
                            VALUES (?, ?, ?)
                        ";
                        $insertMatchStmt = $conn->prepare($insertMatchSql);
                        if ($insertMatchStmt) {
                            $insertMatchStmt->bind_param("iii", $projectId, $user1, $user2);
                            $insertMatchStmt->execute();
                            $insertMatchStmt->close();
                        }
                    }
                } else {
                    // Insert new pending request if none exists
                    if (!requestExists($conn, $projectId, $current_user_id, $targetUserId)) {
                        $insertRequestSql = "
                            INSERT INTO ConnectionRequests (project_id, from_user_id, to_user_id, status)
                            VALUES (?, ?, ?, 'pending')
                        ";
                        $insertRequestStmt = $conn->prepare($insertRequestSql);
                        if ($insertRequestStmt) {
                            $insertRequestStmt->bind_param("iii", $projectId, $current_user_id, $targetUserId);
                            $insertRequestStmt->execute();
                            $insertRequestStmt->close();
                        }
                    }
                }
            }
        }

        header("Location: /pages/matches.php");
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'accept_match') {
        $requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
        $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $fromUserId = isset($_POST['from_user_id']) ? (int)$_POST['from_user_id'] : 0;

        if ($requestId > 0 && $projectId > 0 && $fromUserId > 0) {
            $updateSql = "
                UPDATE ConnectionRequests
                SET status = 'accepted'
                WHERE request_id = ?
                  AND to_user_id = ?
                  AND status = 'pending'
            ";
            $updateStmt = $conn->prepare($updateSql);
            if ($updateStmt) {
                $updateStmt->bind_param("ii", $requestId, $current_user_id);
                $updateStmt->execute();
                $updateStmt->close();
            }

            $user1 = min($current_user_id, $fromUserId);
            $user2 = max($current_user_id, $fromUserId);

            if (!alreadyMatched($conn, $projectId, $user1, $user2)) {
                $insertMatchSql = "
                    INSERT INTO ConnectionMatches (project_id, user1_id, user2_id)
                    VALUES (?, ?, ?)
                ";
                $insertMatchStmt = $conn->prepare($insertMatchSql);
                if ($insertMatchStmt) {
                    $insertMatchStmt->bind_param("iii", $projectId, $user1, $user2);
                    $insertMatchStmt->execute();
                    $insertMatchStmt->close();
                }
            }
        }

        header("Location: /pages/matches.php");
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| 1. Profiles from completed shared projects
|--------------------------------------------------------------------------
*/
$profiles = [];

$sqlProfiles = "
    SELECT DISTINCT
        teammate.user_id,
        teammate.email,
        teammate.course,
        teammate.year,
        teammate.bio,
        teammate.profile_picture,
        p.project_id,
        p.title AS project_title
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
    WHERE tm_me.user_id = ?
      AND tm_me.status = 'active'
      AND p.status = 'completed'
      AND COALESCE(pref.allow_requests, 1) = 1
    ORDER BY p.created_at DESC, teammate.user_id ASC
";

$stmtProfiles = $conn->prepare($sqlProfiles);

if (!$stmtProfiles) {
    die("Profiles query failed: " . $conn->error);
}

$stmtProfiles->bind_param("i", $current_user_id);
$stmtProfiles->execute();
$resultProfiles = $stmtProfiles->get_result();

while ($row = $resultProfiles->fetch_assoc()) {
    $candidateId = (int)$row['user_id'];
    $projectId = (int)$row['project_id'];

    // Skip if already matched
    if (alreadyMatched($conn, $projectId, $current_user_id, $candidateId)) {
        continue;
    }

    // Skip if pending request already exists in either direction
    if (
        requestExists($conn, $projectId, $current_user_id, $candidateId, 'pending') ||
        requestExists($conn, $projectId, $candidateId, $current_user_id, 'pending')
    ) {
        continue;
    }

    $profiles[] = $row;
}

$stmtProfiles->close();

/*
|--------------------------------------------------------------------------
| 2. Incoming match requests
|--------------------------------------------------------------------------
*/
$incomingRequests = [];

$sqlRequests = "
    SELECT
        cr.request_id,
        cr.project_id,
        cr.from_user_id,
        cr.to_user_id,
        cr.status,
        cr.created_at,
        u.email,
        u.course,
        u.year,
        u.bio,
        u.profile_picture,
        p.title AS project_title
    FROM ConnectionRequests cr
    JOIN Users u
        ON cr.from_user_id = u.user_id
    JOIN Projects p
        ON cr.project_id = p.project_id
    WHERE cr.to_user_id = ?
      AND cr.status = 'pending'
    ORDER BY cr.created_at DESC
";

$stmtRequests = $conn->prepare($sqlRequests);

if (!$stmtRequests) {
    die("Incoming requests query failed: " . $conn->error);
}

$stmtRequests->bind_param("i", $current_user_id);
$stmtRequests->execute();
$resultRequests = $stmtRequests->get_result();

while ($row = $resultRequests->fetch_assoc()) {
    $incomingRequests[] = $row;
}

$stmtRequests->close();
?>

<div class="matches-container">
  <div class="section-header">
    <h2>Strengthen your Connection!</h2>
    <p>Find potential matches with students you've worked with. Chat, connect, and see if your collaboration can turn into something more!</p>
  </div>

  <div class="divider"></div>

  <div class="subheader">Profiles from completed projects:</div>

  <div class="profiles-grid">
    <?php if (!empty($profiles)): ?>
      <?php foreach ($profiles as $profile): ?>
        <div class="profile-card">
          <div class="avatar-wrapper">
            <div class="avatar-circle">
              <?php if (!empty($profile['profile_picture'])): ?>
                <img src="/uploads/<?php echo safeText($profile['profile_picture']); ?>" alt="Profile" style="width:60px;height:60px;border-radius:50%;object-fit:cover;">
              <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                  <path d="M12,12A4,4 0 1,0 8,8A4,4 0 0,0 12,12ZM12,14C9.33,14 4,15.34 4,18V20H20V18C20,15.34 14.67,14 12,14Z"/>
                </svg>
              <?php endif; ?>
            </div>

            <div class="heart-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5c0-2.5 1.99-4.5 4.5-4.5c1.74 0 3.41 1.01 4.22 2.61C11.09 5.01 12.76 4 14.5 4C17.01 4 19 6 19 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
              </svg>
            </div>

            <?php if (!empty($profile['year'])): ?>
              <span class="age-number"><?php echo safeText(yearBadge($profile['year'])); ?></span>
            <?php endif; ?>
          </div>

          <div class="profile-name"><?php echo safeText($profile['email']); ?></div>
          <div class="profile-course"><?php echo safeText($profile['course'] ?: 'Course not listed'); ?></div>
          <div class="skill-box"><?php echo safeText($profile['project_title']); ?></div>

          <form method="POST" action="">
            <input type="hidden" name="action" value="send_match">
            <input type="hidden" name="project_id" value="<?php echo (int)$profile['project_id']; ?>">
            <input type="hidden" name="target_user_id" value="<?php echo (int)$profile['user_id']; ?>">
            <button type="submit" class="match-btn">MATCH!</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="how-it-works" style="max-width:100%;">
        <h3>No profiles available yet</h3>
        <p>You will see potential matches here after you complete a project with other teammates.</p>
      </div>
    <?php endif; ?>
  </div>

  <div class="divider"></div>

  <div class="subheader">Match Requests:</div>

  <div class="profiles-grid">
    <?php if (!empty($incomingRequests)): ?>
      <?php foreach ($incomingRequests as $request): ?>
        <div class="profile-card">
          <div class="avatar-wrapper">
            <div class="avatar-circle">
              <?php if (!empty($request['profile_picture'])): ?>
                <img src="/uploads/<?php echo safeText($request['profile_picture']); ?>" alt="Profile" style="width:60px;height:60px;border-radius:50%;object-fit:cover;">
              <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                  <path d="M12,12A4,4 0 1,0 8,8A4,4 0 0,0 12,12ZM12,14C9.33,14 4,15.34 4,18V20H20V18C20,15.34 14.67,14 12,14Z"/>
                </svg>
              <?php endif; ?>
            </div>

            <div class="heart-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5c0-2.5 1.99-4.5 4.5-4.5c1.74 0 3.41 1.01 4.22 2.61C11.09 5.01 12.76 4 14.5 4C17.01 4 19 6 19 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
              </svg>
            </div>

            <?php if (!empty($request['year'])): ?>
              <span class="age-number"><?php echo safeText(yearBadge($request['year'])); ?></span>
            <?php endif; ?>
          </div>

          <div class="profile-name"><?php echo safeText($request['email']); ?></div>
          <div class="profile-course"><?php echo safeText($request['course'] ?: 'Course not listed'); ?></div>
          <div class="skill-box"><?php echo safeText($request['project_title']); ?></div>

          <form method="POST" action="">
            <input type="hidden" name="action" value="accept_match">
            <input type="hidden" name="request_id" value="<?php echo (int)$request['request_id']; ?>">
            <input type="hidden" name="project_id" value="<?php echo (int)$request['project_id']; ?>">
            <input type="hidden" name="from_user_id" value="<?php echo (int)$request['from_user_id']; ?>">
            <button type="submit" class="match-btn accept-btn">ACCEPT</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="how-it-works" style="max-width:100%;">
        <h3>No match requests yet</h3>
        <p>Incoming connection requests will appear here.</p>
      </div>
    <?php endif; ?>
  </div>

  <div class="how-it-works">
    <h3>How does it work?</h3>
    <ul>
      <li>Review profiles of previous teammates.</li>
      <li>Match with a profile to indicate potential interest.</li>
      <li>If they also match with you, start chatting and get to know each other.</li>
    </ul>
  </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>