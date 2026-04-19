<?php
$extra_css = '/assets/css/apply.css';
require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($project_id <= 0) {
    header("Location: /pages/projects.php");
    exit();
}

$success = '';
$error = '';

$user_sql = "
    SELECT user_id, status
    FROM Users
    WHERE user_id = ?
    LIMIT 1
";

$user_stmt = $conn->prepare($user_sql);
if (!$user_stmt) {
    die("Prepare failed: " . $conn->error);
}

$user_stmt->bind_param("i", $current_user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$current_user = $user_result->fetch_assoc();
$user_stmt->close();

if (!$current_user) {
    session_unset();
    session_destroy();
    header("Location: /pages/login.php");
    exit();
}

if (($current_user['status'] ?? 'active') === 'suspended') {
    $error = "Your account is suspended and you cannot apply to projects.";
} elseif (empty($current_user['course']) || empty($current_user['year']) || empty($current_user['bio'])) {
    $error = "incomplete_profile";
}

$project_sql = "
    SELECT 
        p.project_id,
        p.owner_id,
        p.title,
        p.description,
        p.team_size,
        p.deadline,
        p.status,
        u.course,
        u.year,
        u.email
    FROM Projects p
    JOIN Users u ON p.owner_id = u.user_id
    WHERE p.project_id = ?
    LIMIT 1
";

$project_stmt = $conn->prepare($project_sql);
if (!$project_stmt) {
    die("Prepare failed: " . $conn->error);
}

$project_stmt->bind_param("i", $project_id);
$project_stmt->execute();
$project_result = $project_stmt->get_result();
$project = $project_result->fetch_assoc();
$project_stmt->close();

if (!$project) {
    header("Location: /pages/projects.php");
    exit();
}

$roles = [];
$has_open_roles = false;

$roles_sql = "
    SELECT role_id, title, description, filled
    FROM Roles
    WHERE project_id = ?
    ORDER BY role_id ASC
";

$roles_stmt = $conn->prepare($roles_sql);
if (!$roles_stmt) {
    die("Prepare failed: " . $conn->error);
}

$roles_stmt->bind_param("i", $project_id);
$roles_stmt->execute();
$roles_result = $roles_stmt->get_result();

while ($row = $roles_result->fetch_assoc()) {
    $roles[] = $row;
    if ((int)$row['filled'] === 0) {
        $has_open_roles = true;
    }
}
$roles_stmt->close();

if ((int)$project['owner_id'] === $current_user_id) {
    $error = "You cannot apply to your own project.";
}

if ($project['status'] === 'completed') {
    $error = "This project has already been completed and is no longer accepting applications.";
}

if (empty($roles)) {
    $error = "This project does not currently have any roles available.";
}

if (!empty($roles) && !$has_open_roles) {
    $error = "This project is full and is no longer accepting applications.";
}

$can_apply = empty($error);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_apply) {
    $selected_role_id = isset($_POST['selected_role']) ? (int)$_POST['selected_role'] : 0;
    $application_message = trim($_POST['application_message'] ?? '');

    if ($selected_role_id <= 0) {
        $error = "Please choose a role.";
        $can_apply = false;
    } else {
        $check_role_sql = "
            SELECT role_id, title, filled
            FROM Roles
            WHERE role_id = ? AND project_id = ?
            LIMIT 1
        ";

        $check_role_stmt = $conn->prepare($check_role_sql);
        if (!$check_role_stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $check_role_stmt->bind_param("ii", $selected_role_id, $project_id);
        $check_role_stmt->execute();
        $check_role_result = $check_role_stmt->get_result();
        $selected_role = $check_role_result->fetch_assoc();
        $check_role_stmt->close();

        if (!$selected_role) {
            $error = "Invalid role selected.";
            $can_apply = false;
        } elseif ((int)$selected_role['filled'] === 1) {
            $error = "That role has already been filled.";
            $can_apply = false;
        } else {
            $duplicate_sql = "
                SELECT application_id
                FROM Applications
                WHERE project_id = ? AND role_id = ? AND applicant_id = ?
                LIMIT 1
            ";

            $duplicate_stmt = $conn->prepare($duplicate_sql);
            if (!$duplicate_stmt) {
                die("Prepare failed: " . $conn->error);
            }

            $duplicate_stmt->bind_param("iii", $project_id, $selected_role_id, $current_user_id);
            $duplicate_stmt->execute();
            $duplicate_result = $duplicate_stmt->get_result();
            $already_applied = $duplicate_result->fetch_assoc();
            $duplicate_stmt->close();

            if ($already_applied) {
                $error = "You have already applied for this role.";
                $can_apply = false;
            } else {
                $insert_sql = "
                    INSERT INTO Applications (project_id, role_id, applicant_id, message, status, applied_at)
                    VALUES (?, ?, ?, ?, 'pending', NOW())
                ";

                $insert_stmt = $conn->prepare($insert_sql);
                if (!$insert_stmt) {
                    die("Prepare failed: " . $conn->error);
                }

                $insert_stmt->bind_param("iiis", $project_id, $selected_role_id, $current_user_id, $application_message);

                if ($insert_stmt->execute()) {
                    $success = "Your application has been submitted successfully.";
                } else {
                    $error = "Failed to submit application. Please try again.";
                    $can_apply = false;
                }

                $insert_stmt->close();
            }
        }
    }
}

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

function statusClass($status) {
    switch ($status) {
        case 'open':
            return 'status-open';
        case 'in_progress':
            return 'status-progress';
        case 'completed':
            return 'status-completed';
        default:
            return '';
    }
}
?>

<div class="col-lg-10 content-area">
    <div class="apply-top">
        <div class="section-pill apply-pill">Apply to Project</div>
    </div>

    <div class="section-line"></div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <?php if ($error === 'incomplete_profile'): ?>
            <div class="alert alert-danger">You need to complete your profile (course, year, and bio) before applying. <a href="/pages/profile.php">Complete your profile</a></div>
        <?php else: ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="apply-project-card">
        <div class="apply-project-header">
            <h2><?php echo htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <span>
                <?php echo htmlspecialchars($project['course'], ENT_QUOTES, 'UTF-8'); ?> - Year <?php echo (int)$project['year']; ?>
            </span>
        </div>

        <div class="apply-project-body">
            <p><strong>Task:</strong> <?php echo htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>
                <strong>Status:</strong>
                <span class="status <?php echo statusClass($project['status']); ?>">
                    <?php echo htmlspecialchars(formatStatus($project['status']), ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </p>
            <p><strong>Team Size:</strong> <?php echo (int)$project['team_size']; ?></p>
            <p><strong>Deadline:</strong> <?php echo htmlspecialchars($project['deadline'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Owner:</strong> <?php echo htmlspecialchars($project['email'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>

    <div class="section-line mt-4"></div>

    <div class="apply-section-title">Available Roles</div>

    <div class="roles-grid">
        <?php if (!empty($roles)): ?>
            <?php foreach ($roles as $role): ?>
                <div class="role-card">
                    <div class="role-card-title"><?php echo htmlspecialchars($role['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <p><?php echo htmlspecialchars($role['description'], ENT_QUOTES, 'UTF-8'); ?></p>

                    <?php if ((int)$role['filled'] === 1): ?>
                        <div class="role-status">Status: Filled</div>
                        <button type="button" class="select-role-btn" disabled>Unavailable</button>
                    <?php else: ?>
                        <div class="role-status">Status: Open</div>
                        <button
                            type="button"
                            class="select-role-btn"
                            onclick="document.getElementById('selected_role').value='<?php echo (int)$role['role_id']; ?>';"
                            <?php echo $can_apply ? '' : 'disabled'; ?>
                        >
                            Select
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="role-card">
                <div class="role-card-title">No roles available</div>
                <p>This project does not currently have any roles listed.</p>
                <div class="role-status">Status: Unavailable</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="section-line mt-4"></div>

    <form action="/pages/apply.php?project_id=<?php echo (int)$project_id; ?>" method="POST" class="application-form-card">
        <div class="form-card-title">Your Application</div>

        <div class="application-grid">
            <div class="field-group">
                <label for="selected_role">Selected Role</label>
                <select id="selected_role" name="selected_role" required <?php echo $can_apply ? '' : 'disabled'; ?>>
                    <option value="">Choose a role</option>
                    <?php foreach ($roles as $role): ?>
                        <?php if ((int)$role['filled'] === 0): ?>
                            <option value="<?php echo (int)$role['role_id']; ?>" <?php echo (isset($_POST['selected_role']) && (int)$_POST['selected_role'] === (int)$role['role_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-group full-width">
                <label for="application_message">Why do you want to join this project?</label>
                <textarea
                    id="application_message"
                    name="application_message"
                    rows="6"
                    placeholder="Write a short message to the project owner..."
                    <?php echo $can_apply ? '' : 'disabled'; ?>
                ><?php echo htmlspecialchars($_POST['application_message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>

        <div class="application-actions">
            <a href="/pages/projects.php" class="cancel-apply-btn">Cancel</a>

            <?php if (!$can_apply): ?>
                <button type="button" class="submit-application-btn" disabled>Submit Application</button>
            <?php else: ?>
                <button type="submit" class="submit-application-btn">Submit Application</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>