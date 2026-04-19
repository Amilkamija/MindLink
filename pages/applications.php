<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pageTitle = "Applications - MindLink";
$extra_css = '/assets/css/applications.css';

require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$current_user_id = (int) $_SESSION['user_id'];
$success = '';
$error = '';

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatDateTime($value) {
    if (empty($value)) {
        return '';
    }

    $timestamp = strtotime($value);
    if (!$timestamp) {
        return (string)$value;
    }

    return date('d M Y, H:i', $timestamp);
}

/* 1. Handle actions: accept / reject / cancel */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['application_id'])) {
    $action = trim($_POST['action']);
    $application_id = (int) $_POST['application_id'];

    if ($application_id > 0) {
        $verifySql = "
            SELECT 
                a.application_id,
                a.applicant_id,
                a.project_id,
                a.role_id,
                a.status,
                p.owner_id
            FROM Applications a
            JOIN Projects p ON a.project_id = p.project_id
            WHERE a.application_id = ?
            LIMIT 1
        ";

        $verifyStmt = $conn->prepare($verifySql);
        if (!$verifyStmt) {
            $error = "Database preparation error. Please try again later.";
        } else {
            $verifyStmt->bind_param("i", $application_id);
            $verifyStmt->execute();
            $verifyResult = $verifyStmt->get_result();
            $applicationRow = $verifyResult->fetch_assoc();
            $verifyStmt->close();

            if (!$applicationRow) {
                $error = "Application not found.";
            } else {
                $isProjectOwner = ((int) $applicationRow['owner_id'] === $current_user_id);
                $isApplicant = ((int) $applicationRow['applicant_id'] === $current_user_id);

                /* Accept Application */
                if ($action === 'accept' && $isProjectOwner) {
                    $conn->begin_transaction();
                    try {
                        $updateSql = "
                            UPDATE Applications
                            SET status = 'accepted', reviewed_at = NOW()
                            WHERE application_id = ?
                        ";
                        $updateStmt = $conn->prepare($updateSql);
                        if (!$updateStmt) {
                            throw new Exception("Failed to prepare accept update.");
                        }
                        $updateStmt->bind_param("i", $application_id);
                        $updateStmt->execute();
                        $updateStmt->close();

                        // Reject others for same role
                        if (!empty($applicationRow['role_id'])) {
                            $rejectOthersSql = "
                                UPDATE Applications
                                SET status = 'rejected', reviewed_at = NOW()
                                WHERE role_id = ? 
                                  AND application_id != ? 
                                  AND status = 'pending'
                            ";
                            $rejectOthersStmt = $conn->prepare($rejectOthersSql);
                            if ($rejectOthersStmt) {
                                $rejectOthersStmt->bind_param("ii", $applicationRow['role_id'], $application_id);
                                $rejectOthersStmt->execute();
                                $rejectOthersStmt->close();
                            }
                        }

                        // Add to team if not already in
                        $checkMembershipSql = "
                            SELECT membership_id FROM TeamMembership
                            WHERE project_id = ? AND user_id = ? LIMIT 1
                        ";
                        $checkMembershipStmt = $conn->prepare($checkMembershipSql);
                        if (!$checkMembershipStmt) {
                            throw new Exception("Membership check preparation failed.");
                        }
                        $checkMembershipStmt->bind_param("ii", $applicationRow['project_id'], $applicationRow['applicant_id']);
                        $checkMembershipStmt->execute();
                        $membershipExists = $checkMembershipStmt->get_result()->fetch_assoc();
                        $checkMembershipStmt->close();

                        if (!$membershipExists) {
                            $insertMembershipSql = "
                                INSERT INTO TeamMembership (project_id, user_id, role_id, status, joined_at)
                                VALUES (?, ?, ?, 'active', NOW())
                            ";
                            $insertMembershipStmt = $conn->prepare($insertMembershipSql);
                            if (!$insertMembershipStmt) {
                                throw new Exception("Failed to prepare membership insert.");
                            }
                            $insertMembershipStmt->bind_param("iii", $applicationRow['project_id'], $applicationRow['applicant_id'], $applicationRow['role_id']);
                            $insertMembershipStmt->execute();
                            $insertMembershipStmt->close();
                        }

                        // Mark role filled
                        if (!empty($applicationRow['role_id'])) {
                            $updateRoleSql = "UPDATE Roles SET filled = 1 WHERE role_id = ?";
                            $updateRoleStmt = $conn->prepare($updateRoleSql);
                            if ($updateRoleStmt) {
                                $updateRoleStmt->bind_param("i", $applicationRow['role_id']);
                                $updateRoleStmt->execute();
                                $updateRoleStmt->close();
                            }
                        }

                        $conn->commit();
                        $success = "Application accepted successfully.";
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = "An error occurred during acceptance: " . safeText($e->getMessage());
                    }

                /* Reject Applications */
                } elseif ($action === 'reject' && $isProjectOwner) {
                    $conn->begin_transaction();
                    try {
                        $rejectSql = "
                            UPDATE Applications
                            SET status = 'rejected', reviewed_at = NOW()
                            WHERE application_id = ?
                        ";
                        $rejectStmt = $conn->prepare($rejectSql);
                        if (!$rejectStmt) {
                            throw new Exception("Failed to prepare reject SQL.");
                        }
                        $rejectStmt->bind_param("i", $application_id);
                        $rejectStmt->execute();
                        $rejectStmt->close();

                        $conn->commit();
                        $success = "Application rejected.";
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = "Could not reject application. " . safeText($e->getMessage());
                    }

                /* Cancel Application */
                } elseif ($action === 'cancel' && $isApplicant) {
                    $conn->begin_transaction();
                    try {
                        $cancelSql = "
                            UPDATE Applications
                            SET status = 'cancelled'
                            WHERE application_id = ? AND applicant_id = ? AND status = 'pending'
                        ";
                        $cancelStmt = $conn->prepare($cancelSql);
                        if (!$cancelStmt) {
                            throw new Exception("Failed to prepare cancel SQL.");
                        }
                        $cancelStmt->bind_param("ii", $application_id, $current_user_id);
                        $cancelStmt->execute();
                        $cancelStmt->close();

                        $conn->commit();
                        $success = "Application cancelled.";
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = "Could not cancel application. " . safeText($e->getMessage());
                    }

                } else {
                    $error = "You are not authorized to perform this action.";
                }
            }
        }
    }
}

/*2. Applications received for my projects */
$receivedApplications = [];
$receivedSql = "
    SELECT
        a.application_id,
        a.project_id,
        a.role_id,
        a.applicant_id,
        a.message,
        a.status,
        a.applied_at,
        p.title AS project_title,
        r.title AS role_title,
        u.email AS applicant_email,
        u.course AS applicant_course,
        u.year AS applicant_year
    FROM Applications a
    JOIN Projects p ON a.project_id = p.project_id
    LEFT JOIN Roles r ON a.role_id = r.role_id
    JOIN Users u ON a.applicant_id = u.user_id
    WHERE p.owner_id = ?
    ORDER BY 
        CASE a.status
            WHEN 'pending' THEN 1
            WHEN 'accepted' THEN 2
            WHEN 'rejected' THEN 3
            WHEN 'cancelled' THEN 4
            ELSE 5
        END,
        a.applied_at DESC
";

if ($receivedStmt = $conn->prepare($receivedSql)) {
    $receivedStmt->bind_param("i", $current_user_id);
    $receivedStmt->execute();
    $receivedResult = $receivedStmt->get_result();
    while ($row = $receivedResult->fetch_assoc()) {
        $receivedApplications[] = $row;
    }
    $receivedStmt->close();
} else {
    $error .= " Could not retrieve received applications.";
}

/* 3. My applications */
$myApplications = [];
$mySql = "
    SELECT
        a.application_id,
        a.project_id,
        a.role_id,
        a.message,
        a.status,
        a.applied_at,
        p.title AS project_title,
        r.title AS role_title,
        owner.email AS owner_email
    FROM Applications a
    JOIN Projects p ON a.project_id = p.project_id
    LEFT JOIN Roles r ON a.role_id = r.role_id
    JOIN Users owner ON p.owner_id = owner.user_id
    WHERE a.applicant_id = ?
    ORDER BY a.applied_at DESC
";

if ($myStmt = $conn->prepare($mySql)) {
    $myStmt->bind_param("i", $current_user_id);
    $myStmt->execute();
    $myResult = $myStmt->get_result();
    while ($row = $myResult->fetch_assoc()) {
        $myApplications[] = $row;
    }
    $myStmt->close();
} else {
    $error .= " Could not retrieve your applications.";
}
?>

<div class="applications-page">
    <div class="content-area">

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo safeText($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo safeText($error); ?></div>
        <?php endif; ?>

        <section class="application-section">
            <div class="section-header-row">
                <div>
                    <h2 class="section-title">Applications for My Projects</h2>
                    <p class="section-subtitle">
                        View applications sent by other users to your project roles and decide whether to accept or reject them.
                    </p>
                </div>
            </div>

            <?php if (!empty($receivedApplications)): ?>
                <div class="applications-grid">
                    <?php foreach ($receivedApplications as $app): ?>
                        <div class="application-card">
                            <div class="application-card-top">
                                <div>
                                    <h3 class="application-title"><?php echo safeText($app['project_title']); ?></h3>
                                    <p class="application-role">
                                        <?php echo !empty($app['role_title']) ? safeText($app['role_title']) : 'Role not specified'; ?>
                                    </p>
                                </div>

                                <span class="status-badge status-<?php echo safeText($app['status']); ?>">
                                    <?php echo ucfirst(safeText($app['status'])); ?>
                                </span>
                            </div>

                            <div class="application-meta">
                                <div class="meta-item">
                                    <span class="meta-label">Applicant</span>
                                    <span class="meta-value"><?php echo safeText($app['applicant_email']); ?></span>
                                </div>

                                <div class="meta-item">
                                    <span class="meta-label">Course / Year</span>
                                    <span class="meta-value">
                                        <?php
                                        $courseYear = trim((string) $app['applicant_course']);
                                        if (!empty($app['applicant_year'])) {
                                            $courseYear .= ($courseYear !== '' ? ' / ' : '') . 'Year ' . (int) $app['applicant_year'];
                                        }
                                        echo safeText($courseYear !== '' ? $courseYear : 'Not provided');
                                        ?>
                                    </span>
                                </div>

                                <div class="meta-item">
                                    <span class="meta-label">Applied on</span>
                                    <span class="meta-value"><?php echo safeText(formatDateTime($app['applied_at'])); ?></span>
                                </div>
                            </div>

                            <div class="message-box">
                                <div class="message-label">Message</div>
                                <p>
                                    <?php echo !empty($app['message']) ? nl2br(safeText($app['message'])) : 'No message provided.'; ?>
                                </p>
                            </div>

                            <div class="application-actions">
                                <a href="/pages/project_details.php?project_id=<?php echo (int) $app['project_id']; ?>" class="btn-secondary-app">
                                    View Project
                                </a>

                                <?php if ($app['status'] === 'pending'): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="application_id" value="<?php echo (int) $app['application_id']; ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn-accept">Accept</button>
                                    </form>

                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="application_id" value="<?php echo (int) $app['application_id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn-decline">Reject</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-card">
                    <h4>No applications received yet</h4>
                    <p>When users apply to roles in your projects, they will appear here.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="application-section">
            <div class="section-header-row">
                <div>
                    <h2 class="section-title">My Applications</h2>
                    <p class="section-subtitle">
                        Track the applications you sent to other projects and check their current status.
                    </p>
                </div>
            </div>

            <?php if (!empty($myApplications)): ?>
                <div class="applications-grid">
                    <?php foreach ($myApplications as $app): ?>
                        <div class="application-card application-card-soft">
                            <div class="application-card-top">
                                <div>
                                    <h3 class="application-title"><?php echo safeText($app['project_title']); ?></h3>
                                    <p class="application-role">
                                        <?php echo !empty($app['role_title']) ? safeText($app['role_title']) : 'Role not specified'; ?>
                                    </p>
                                </div>

                                <span class="status-badge status-<?php echo safeText($app['status']); ?>">
                                    <?php echo ucfirst(safeText($app['status'])); ?>
                                </span>
                            </div>

                            <div class="application-meta application-meta-two">
                                <div class="meta-item">
                                    <span class="meta-label">Project owner</span>
                                    <span class="meta-value"><?php echo safeText($app['owner_email']); ?></span>
                                </div>

                                <div class="meta-item">
                                    <span class="meta-label">Applied on</span>
                                    <span class="meta-value"><?php echo safeText(formatDateTime($app['applied_at'])); ?></span>
                                </div>
                            </div>

                            <div class="message-box">
                                <div class="message-label">My Message</div>
                                <p>
                                    <?php echo !empty($app['message']) ? nl2br(safeText($app['message'])) : 'No message provided.'; ?>
                                </p>
                            </div>

                            <div class="application-actions">
                                <a href="/pages/project_details.php?project_id=<?php echo (int) $app['project_id']; ?>" class="btn-secondary-app">
                                    View Project
                                </a>

                                <?php if ($app['status'] === 'pending'): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="application_id" value="<?php echo (int) $app['application_id']; ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="btn-decline">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state-card">
                    <h4>No applications sent yet</h4>
                    <p>Projects you apply to will appear here.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>
