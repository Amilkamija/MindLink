<?php
$extra_css = '/assets/css/create_project.css';
require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

$success = '';
$error = '';

$project_title = '';
$module_subject = '';
$project_year = '';
$project_description = '';
$team_size = '';
$project_status = 'open';
$deadline = '';
$skills_tags = '';

$role_1_title = '';
$role_2_title = '';
$role_3_title = '';

// Check that current user still exists and is allowed to create projects
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
    $error = "Your account is suspended and you cannot create projects.";
}

$can_create_project = empty($error);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_create_project) {
    $project_title = trim($_POST['project_title'] ?? '');
    $module_subject = trim($_POST['module_subject'] ?? '');
    $project_year = trim($_POST['project_year'] ?? '');
    $project_description = trim($_POST['project_description'] ?? '');
    $team_size = trim($_POST['team_size'] ?? '');
    $project_status = trim($_POST['project_status'] ?? 'open');
    $deadline = trim($_POST['deadline'] ?? '');
    $skills_tags = trim($_POST['skills_tags'] ?? '');

    $role_1_title = trim($_POST['role_1_title'] ?? '');
    $role_2_title = trim($_POST['role_2_title'] ?? '');
    $role_3_title = trim($_POST['role_3_title'] ?? '');

    $allowed_statuses = ['open', 'in_progress', 'completed'];

    if ($project_title === '') {
        $error = "Project title is required.";
    } elseif ($project_description === '') {
        $error = "Project description is required.";
    } elseif ($team_size === '' || !ctype_digit($team_size) || (int)$team_size < 1 || (int)$team_size > 50) {
        $error = "Please enter a valid team size.";
    } elseif ($deadline === '') {
        $error = "Please choose a deadline.";
    } elseif (!in_array($project_status, $allowed_statuses, true)) {
        $error = "Invalid project status selected.";
    } else {
        $team_size_int = (int)$team_size;

        $full_description = $project_description;

        if ($module_subject !== '') {
            $full_description .= "\n\nModule / Subject: " . $module_subject;
        }

        if ($project_year !== '') {
            $full_description .= "\nPreferred Year: " . $project_year;
        }

        $conn->begin_transaction();

        try {
            $project_sql = "
                INSERT INTO Projects (owner_id, title, description, team_size, deadline, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ";

            $project_stmt = $conn->prepare($project_sql);

            if (!$project_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $project_stmt->bind_param(
                "ississ",
                $current_user_id,
                $project_title,
                $full_description,
                $team_size_int,
                $deadline,
                $project_status
            );

            if (!$project_stmt->execute()) {
                throw new Exception("Failed to create project.");
            }

            $project_id = $conn->insert_id;
            $project_stmt->close();

            $roles = [
                $role_1_title,
                $role_2_title,
                $role_3_title
            ];

            $role_insert_sql = "
                INSERT INTO Roles (project_id, title, description, filled)
                VALUES (?, ?, ?, 0)
            ";

            $role_stmt = $conn->prepare($role_insert_sql);

            if (!$role_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            foreach ($roles as $index => $role_title) {
                if ($role_title !== '') {
                    $role_description = "Role " . ($index + 1) . " for " . $project_title;
                    $role_stmt->bind_param("iss", $project_id, $role_title, $role_description);

                    if (!$role_stmt->execute()) {
                        throw new Exception("Failed to create one of the project roles.");
                    }
                }
            }

            $role_stmt->close();

            $tags_array = array_filter(array_map('trim', explode(',', $skills_tags)));

            if (!empty($tags_array)) {
                $tag_sql = "INSERT INTO ProjectTags (project_id, tag_name) VALUES (?, ?)";
                $tag_stmt = $conn->prepare($tag_sql);

                if (!$tag_stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                foreach ($tags_array as $tag) {
                    $tag_stmt->bind_param("is", $project_id, $tag);
                    if (!$tag_stmt->execute()) {
                        throw new Exception("Failed to save project tags.");
                    }
                }

                $tag_stmt->close();
            }

            $conn->commit();

            $success = "Project created successfully.";
            $project_title = '';
            $module_subject = '';
            $project_year = '';
            $project_description = '';
            $team_size = '';
            $project_status = 'open';
            $deadline = '';
            $skills_tags = '';
            $role_1_title = '';
            $role_2_title = '';
            $role_3_title = '';
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

$can_create_project = empty($error);
?>

<div class="col-lg-10 content-area">
    <div class="create-project-top">
        <div class="section-pill create-project-pill">Create Project</div>
    </div>

    <div class="section-line"></div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="create-layout">
        <form action="/pages/create_project.php" method="POST" class="create-project-form">
            <div class="form-card">
                <div class="form-card-title">Project Details</div>
                <div class="create-grid two-col">
                    <div class="field-group full-width">
                        <label for="project_title">Project Title</label>
                        <input
                            type="text"
                            id="project_title"
                            name="project_title"
                            placeholder="Enter project title"
                            value="<?php echo htmlspecialchars($project_title, ENT_QUOTES, 'UTF-8'); ?>"
                            required
                            <?php echo $can_create_project ? '' : 'disabled'; ?>
                        >
                    </div>

                    <div class="field-group">
                        <label for="module_subject">Module / Subject</label>
                        <input
                            type="text"
                            id="module_subject"
                            name="module_subject"
                            placeholder="e.g. CS4416 Software Development"
                            value="<?php echo htmlspecialchars($module_subject, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo $can_create_project ? '' : 'disabled'; ?>
                        >
                    </div>

                    <div class="field-group">
                        <label for="project_year">Year</label>
                        <select id="project_year" name="project_year" <?php echo $can_create_project ? '' : 'disabled'; ?>>
                            <option value="">Select year</option>
                            <option value="Year 1" <?php echo ($project_year === 'Year 1') ? 'selected' : ''; ?>>Year 1</option>
                            <option value="Year 2" <?php echo ($project_year === 'Year 2') ? 'selected' : ''; ?>>Year 2</option>
                            <option value="Year 3" <?php echo ($project_year === 'Year 3') ? 'selected' : ''; ?>>Year 3</option>
                            <option value="Year 4" <?php echo ($project_year === 'Year 4') ? 'selected' : ''; ?>>Year 4</option>
                        </select>
                    </div>

                    <div class="field-group full-width">
                        <label for="project_description">Project Description</label>
                        <textarea
                            id="project_description"
                            name="project_description"
                            placeholder="Describe the project..."
                            required
                            <?php echo $can_create_project ? '' : 'disabled'; ?>
                        ><?php echo htmlspecialchars($project_description, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="section-line form-divider"></div>

            <div class="form-card">
                <div class="form-card-title">Requirements</div>
                <div class="create-grid two-col">
                    <div class="field-group">
                        <label for="team_size">Team Size</label>
                        <input
                            type="number"
                            id="team_size"
                            name="team_size"
                            placeholder="e.g. 4"
                            min="1"
                            max="50"
                            value="<?php echo htmlspecialchars($team_size, ENT_QUOTES, 'UTF-8'); ?>"
                            required
                            <?php echo $can_create_project ? '' : 'disabled'; ?>
                        >
                    </div>

                    <div class="field-group">
                        <label for="project_status">Project Status</label>
                        <select id="project_status" name="project_status" required <?php echo $can_create_project ? '' : 'disabled'; ?>>
                            <option value="open" <?php echo ($project_status === 'open') ? 'selected' : ''; ?>>Open for applications</option>
                            <option value="in_progress" <?php echo ($project_status === 'in_progress') ? 'selected' : ''; ?>>In progress</option>
                            <option value="completed" <?php echo ($project_status === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="deadline">Deadline</label>
                        <input
                            type="date"
                            id="deadline"
                            name="deadline"
                            value="<?php echo htmlspecialchars($deadline, ENT_QUOTES, 'UTF-8'); ?>"
                            required
                            <?php echo $can_create_project ? '' : 'disabled'; ?>
                        >
                    </div>

                    <div class="field-group">
                        <label for="skills_tags">Skills / Tags</label>
                        <input
                            type="text"
                            id="skills_tags"
                            name="skills_tags"
                            placeholder="e.g. PHP, MySQL, UI Design"
                            value="<?php echo htmlspecialchars($skills_tags, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo $can_create_project ? '' : 'disabled'; ?>
                        >
                    </div>
                </div>
            </div>

            <div class="roles-panel">
                <div class="roles-panel-title">Roles Needed</div>

                <div class="role-card">
                    <div class="role-card-title">Role 1</div>
                    <p>Add a key role for your project team.</p>
                    <input
                        type="text"
                        name="role_1_title"
                        placeholder="e.g. Backend Developer"
                        value="<?php echo htmlspecialchars($role_1_title, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $can_create_project ? '' : 'disabled'; ?>
                    >
                </div>

                <div class="role-card">
                    <div class="role-card-title">Role 2</div>
                    <p>Add another role to balance skills.</p>
                    <input
                        type="text"
                        name="role_2_title"
                        placeholder="e.g. UI/UX Designer"
                        value="<?php echo htmlspecialchars($role_2_title, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $can_create_project ? '' : 'disabled'; ?>
                    >
                </div>

                <div class="role-card">
                    <div class="role-card-title">Role 3</div>
                    <p>Optional additional support role.</p>
                    <input
                        type="text"
                        name="role_3_title"
                        placeholder="e.g. Project Manager"
                        value="<?php echo htmlspecialchars($role_3_title, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $can_create_project ? '' : 'disabled'; ?>
                    >
                </div>

                <div class="roles-actions">
                    <a href="/pages/projects.php" class="save-draft-btn">Cancel</a>

                    <?php if (!$can_create_project): ?>
                        <button type="button" class="publish-project-btn" disabled>Publish</button>
                    <?php else: ?>
                        <button type="submit" class="publish-project-btn">Publish</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>