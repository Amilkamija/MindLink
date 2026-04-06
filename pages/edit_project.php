<?php
$extra_css = '/assets/css/create_project.css';
require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($project_id <= 0) {
    header("Location: /pages/my_projects.php");
    exit();
}

$success = '';
$error = '';

$project_sql = "
    SELECT project_id, owner_id, title, description, team_size, deadline, status
    FROM Projects
    WHERE project_id = ? AND owner_id = ?
    LIMIT 1
";

$project_stmt = $conn->prepare($project_sql);
if (!$project_stmt) {
    die("Prepare failed: " . $conn->error);
}

$project_stmt->bind_param("ii", $project_id, $current_user_id);
$project_stmt->execute();
$project_result = $project_stmt->get_result();
$project = $project_result->fetch_assoc();
$project_stmt->close();

if (!$project) {
    header("Location: /pages/my_projects.php");
    exit();
}

$roles = [];
$roles_sql = "
    SELECT role_id, title, description, filled
    FROM Roles
    WHERE project_id = ?
    ORDER BY role_id ASC
    LIMIT 3
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
}
$roles_stmt->close();

$role_slots = [
    ['role_id' => null, 'title' => '', 'filled' => 0],
    ['role_id' => null, 'title' => '', 'filled' => 0],
    ['role_id' => null, 'title' => '', 'filled' => 0]
];

for ($i = 0; $i < count($roles) && $i < 3; $i++) {
    $role_slots[$i] = [
        'role_id' => $roles[$i]['role_id'],
        'title'   => $roles[$i]['title'],
        'filled'  => $roles[$i]['filled']
    ];
}

$project_title = $project['title'];
$project_description = $project['description'];
$team_size = $project['team_size'];
$project_status = $project['status'];
$deadline = $project['deadline'];
$skills_tags = '';

$tags_sql = "SELECT tag_name FROM ProjectTags WHERE project_id = ? ORDER BY tag_name ASC";
$tags_stmt = $conn->prepare($tags_sql);
if ($tags_stmt) {
    $tags_stmt->bind_param("i", $project_id);
    $tags_stmt->execute();
    $tags_result = $tags_stmt->get_result();

    $tag_names = [];
    while ($tag_row = $tags_result->fetch_assoc()) {
        $tag_names[] = $tag_row['tag_name'];
    }
    $tags_stmt->close();

    $skills_tags = implode(', ', $tag_names);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_title = trim($_POST['project_title'] ?? '');
    $project_description = trim($_POST['project_description'] ?? '');
    $team_size = trim($_POST['team_size'] ?? '');
    $project_status = trim($_POST['project_status'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');
    $skills_tags = trim($_POST['skills_tags'] ?? '');

    $posted_roles = [
        trim($_POST['role_1_title'] ?? ''),
        trim($_POST['role_2_title'] ?? ''),
        trim($_POST['role_3_title'] ?? '')
    ];

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
        $member_count_sql = "
            SELECT COUNT(*) AS total_members
            FROM TeamMembership
            WHERE project_id = ? AND status = 'active'
        ";
        $member_stmt = $conn->prepare($member_count_sql);
        $member_stmt->bind_param("i", $project_id);
        $member_stmt->execute();
        $member_result = $member_stmt->get_result();
        $member_row = $member_result->fetch_assoc();
        $member_stmt->close();

        $active_members = (int)($member_row['total_members'] ?? 0);

        if ((int)$team_size < $active_members) {
            $error = "Team size cannot be less than the current number of active members.";
        } else {
            $conn->begin_transaction();

            try {
                $update_sql = "
                    UPDATE Projects
                    SET title = ?, description = ?, team_size = ?, deadline = ?, status = ?
                    WHERE project_id = ? AND owner_id = ?
                ";

                $update_stmt = $conn->prepare($update_sql);
                if (!$update_stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                $team_size_int = (int)$team_size;
                $update_stmt->bind_param(
                    "ssissii",
                    $project_title,
                    $project_description,
                    $team_size_int,
                    $deadline,
                    $project_status,
                    $project_id,
                    $current_user_id
                );

                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update project.");
                }
                $update_stmt->close();

                for ($i = 0; $i < 3; $i++) {
                    $existing_role_id = $role_slots[$i]['role_id'];
                    $existing_filled = (int)$role_slots[$i]['filled'];
                    $new_title = $posted_roles[$i];

                    if ($existing_role_id) {
                        if ($new_title === '') {
                            if ($existing_filled === 1) {
                                throw new Exception("You cannot remove a role that has already been filled.");
                            }

                            $delete_sql = "DELETE FROM Roles WHERE role_id = ? AND project_id = ?";
                            $delete_stmt = $conn->prepare($delete_sql);
                            $delete_stmt->bind_param("ii", $existing_role_id, $project_id);
                            if (!$delete_stmt->execute()) {
                                throw new Exception("Failed to remove an empty role.");
                            }
                            $delete_stmt->close();
                        } else {
                            $role_description = "Role " . ($i + 1) . " for " . $project_title;

                            $role_update_sql = "
                                UPDATE Roles
                                SET title = ?, description = ?
                                WHERE role_id = ? AND project_id = ?
                            ";
                            $role_update_stmt = $conn->prepare($role_update_sql);
                            $role_update_stmt->bind_param("ssii", $new_title, $role_description, $existing_role_id, $project_id);

                            if (!$role_update_stmt->execute()) {
                                throw new Exception("Failed to update role.");
                            }
                            $role_update_stmt->close();
                        }
                    } else {
                        if ($new_title !== '') {
                            $role_description = "Role " . ($i + 1) . " for " . $project_title;

                            $insert_role_sql = "
                                INSERT INTO Roles (project_id, title, description, filled)
                                VALUES (?, ?, ?, 0)
                            ";
                            $insert_role_stmt = $conn->prepare($insert_role_sql);
                            $insert_role_stmt->bind_param("iss", $project_id, $new_title, $role_description);

                            if (!$insert_role_stmt->execute()) {
                                throw new Exception("Failed to add role.");
                            }
                            $insert_role_stmt->close();
                        }
                    }
                }

                $delete_tags_sql = "DELETE FROM ProjectTags WHERE project_id = ?";
                $delete_tags_stmt = $conn->prepare($delete_tags_sql);

                if (!$delete_tags_stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                $delete_tags_stmt->bind_param("i", $project_id);
                if (!$delete_tags_stmt->execute()) {
                    throw new Exception("Failed to clear old tags.");
                }
                $delete_tags_stmt->close();

                $tags_array = array_filter(array_map('trim', explode(',', $skills_tags)));

                if (!empty($tags_array)) {
                    $insert_tag_sql = "INSERT INTO ProjectTags (project_id, tag_name) VALUES (?, ?)";
                    $insert_tag_stmt = $conn->prepare($insert_tag_sql);

                    if (!$insert_tag_stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }

                    foreach ($tags_array as $tag) {
                        $insert_tag_stmt->bind_param("is", $project_id, $tag);
                        if (!$insert_tag_stmt->execute()) {
                            throw new Exception("Failed to save updated tags.");
                        }
                    }

                    $insert_tag_stmt->close();
                }

                $conn->commit();
                $success = "Project updated successfully.";

                $roles = [];
                $roles_stmt = $conn->prepare($roles_sql);
                $roles_stmt->bind_param("i", $project_id);
                $roles_stmt->execute();
                $roles_result = $roles_stmt->get_result();

                while ($row = $roles_result->fetch_assoc()) {
                    $roles[] = $row;
                }
                $roles_stmt->close();

                $role_slots = [
                    ['role_id' => null, 'title' => '', 'filled' => 0],
                    ['role_id' => null, 'title' => '', 'filled' => 0],
                    ['role_id' => null, 'title' => '', 'filled' => 0]
                ];

                for ($i = 0; $i < count($roles) && $i < 3; $i++) {
                    $role_slots[$i] = [
                        'role_id' => $roles[$i]['role_id'],
                        'title'   => $roles[$i]['title'],
                        'filled'  => $roles[$i]['filled']
                    ];
                }
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}
?>

<div class="col-lg-10 content-area">

    <div class="create-project-top">
        <div class="section-pill create-project-pill">Edit Project</div>
    </div>

    <div class="section-line"></div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="create-layout">

        <form action="/pages/edit_project.php?project_id=<?php echo (int)$project_id; ?>" method="POST" class="create-project-form">

            <div class="form-card">
                <div class="form-card-title">Project Details</div>

                <div class="create-grid two-col">
                    <div class="field-group full-width">
                        <label for="project_title">Project Title</label>
                        <input
                            type="text"
                            id="project_title"
                            name="project_title"
                            value="<?php echo htmlspecialchars($project_title, ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                    </div>

                    <div class="field-group full-width">
                        <label for="project_description">Project Description</label>
                        <textarea
                            id="project_description"
                            name="project_description"
                            required
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
                            min="1"
                            max="50"
                            value="<?php echo htmlspecialchars((string)$team_size, ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        >
                    </div>

                    <div class="field-group">
                        <label for="project_status">Project Status</label>
                        <select id="project_status" name="project_status" required>
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
                        >
                    </div>
                </div>
            </div>

            <div class="roles-panel">
                <div class="roles-panel-title">Edit Roles</div>

                <div class="role-card">
                    <div class="role-card-title">Role 1 <?php echo ($role_slots[0]['filled'] ? '(Filled)' : ''); ?></div>
                    <p>Update or remove this role.</p>
                    <input
                        type="text"
                        name="role_1_title"
                        value="<?php echo htmlspecialchars($role_slots[0]['title'], ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="e.g. Backend Developer"
                    >
                </div>

                <div class="role-card">
                    <div class="role-card-title">Role 2 <?php echo ($role_slots[1]['filled'] ? '(Filled)' : ''); ?></div>
                    <p>Update or remove this role.</p>
                    <input
                        type="text"
                        name="role_2_title"
                        value="<?php echo htmlspecialchars($role_slots[1]['title'], ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="e.g. UI/UX Designer"
                    >
                </div>

                <div class="role-card">
                    <div class="role-card-title">Role 3 <?php echo ($role_slots[2]['filled'] ? '(Filled)' : ''); ?></div>
                    <p>Update or remove this role.</p>
                    <input
                        type="text"
                        name="role_3_title"
                        value="<?php echo htmlspecialchars($role_slots[2]['title'], ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="e.g. Project Manager"
                    >
                </div>

                <div class="roles-actions">
                    <a href="/pages/my_projects.php" class="save-draft-btn">Cancel</a>
                    <button type="submit" class="publish-project-btn">Save Changes</button>
                </div>
            </div>

        </form>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>