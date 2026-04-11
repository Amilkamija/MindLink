<?php
$extra_css = '/assets/css/projects.css';
require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

$q = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$allowed_filters = ['all', 'projects', 'users'];
if (!in_array($filter, $allowed_filters, true)) {
    $filter = 'all';
}

$show_projects = ($filter === 'all' || $filter === 'projects');
$show_users    = ($filter === 'all' || $filter === 'users');

$projects = [];
$users = [];

function formatStatus($status) {
    switch ($status) {
        case 'open': return 'Open';
        case 'in_progress': return 'In Progress';
        case 'completed': return 'Completed';
        default: return ucfirst($status);
    }
}

function statusClass($status) {
    switch ($status) {
        case 'open': return 'status-open';
        case 'in_progress': return 'status-progress';
        case 'completed': return 'status-completed';
        default: return '';
    }
}

function shortText($text, $length = 120) {
    $text = $text ?? '';
    if (strlen($text) <= $length) return htmlspecialchars($text);
    return htmlspecialchars(substr($text, 0, $length)) . '...';
}

if ($q !== '') {

    if ($show_projects) {
        $project_sql = "
            SELECT DISTINCT
                p.project_id,
                p.title,
                p.description,
                p.team_size,
                p.deadline,
                p.status,
                u.course,
                u.year,
                GROUP_CONCAT(DISTINCT pt.tag_name SEPARATOR ', ') AS tags
            FROM Projects p
            JOIN Users u ON p.owner_id = u.user_id
            LEFT JOIN ProjectTags pt ON p.project_id = pt.project_id
            WHERE
                p.title LIKE CONCAT('%', ?, '%')
                OR p.description LIKE CONCAT('%', ?, '%')
                OR pt.tag_name LIKE CONCAT('%', ?, '%')
                OR u.course LIKE CONCAT('%', ?, '%')
            GROUP BY p.project_id
            ORDER BY p.created_at DESC
        ";

        $stmt = $conn->prepare($project_sql);
        $stmt->bind_param("ssss", $q, $q, $q, $q);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        $stmt->close();
    }

    if ($show_users) {
        $user_sql = "
            SELECT
                u.user_id,
                u.email,
                u.course,
                u.year,
                u.bio,
                GROUP_CONCAT(DISTINCT ut.tag_name SEPARATOR ', ') AS tags
            FROM Users u
            LEFT JOIN UserTags ut ON u.user_id = ut.user_id
            WHERE
                u.email LIKE CONCAT('%', ?, '%')
                OR u.course LIKE CONCAT('%', ?, '%')
                OR u.bio LIKE CONCAT('%', ?, '%')
                OR ut.tag_name LIKE CONCAT('%', ?, '%')
            GROUP BY u.user_id
            ORDER BY u.user_id DESC
        ";

        $stmt = $conn->prepare($user_sql);
        $stmt->bind_param("ssss", $q, $q, $q, $q);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();
    }
}
?>

<div class="col-lg-10 content-area">

    <div class="section-pill">Search Results</div>
    <div class="section-line"></div>

    <form method="GET" action="search.php" class="search-filter-form" style="margin-bottom: 20px;">
        <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">

        <label style="margin-right: 15px;">
            <input type="radio" name="filter" value="all" <?php echo $filter === 'all' ? 'checked' : ''; ?>>
            All
        </label>

        <label style="margin-right: 15px;">
            <input type="radio" name="filter" value="projects" <?php echo $filter === 'projects' ? 'checked' : ''; ?>>
            Projects
        </label>

        <label style="margin-right: 15px;">
            <input type="radio" name="filter" value="users" <?php echo $filter === 'users' ? 'checked' : ''; ?>>
            Users
        </label>

        <button type="submit" class="details-btn">Apply Filter</button>
    </form>

    <?php if ($q === ''): ?>
        <p>Please enter a search term.</p>
    <?php else: ?>

        <?php if ($show_projects): ?>
            <div class="section-pill">Projects</div>

            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $p): ?>
                    <div class="project-card">
                        <div class="project-card-header">
                            <h2><?php echo htmlspecialchars($p['title']); ?></h2>
                            <span><?php echo htmlspecialchars($p['course']); ?> - Year <?php echo (int)$p['year']; ?></span>
                        </div>

                        <div class="project-card-body">
                            <p><strong>Task:</strong> <?php echo shortText($p['description']); ?></p>

                            <p>
                                <strong>Status:</strong>
                                <span class="status <?php echo statusClass($p['status']); ?>">
                                    <?php echo formatStatus($p['status']); ?>
                                </span>
                            </p>

                            <p><strong>Tags:</strong> <?php echo htmlspecialchars($p['tags'] ?? 'None'); ?></p>
                        </div>

                        <div class="project-card-actions">
                            <a href="/pages/project_details.php?project_id=<?php echo (int)$p['project_id']; ?>" class="details-btn">
                                View
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No projects found.</p>
            <?php endif; ?>

            <?php if ($show_users): ?>
                <div class="section-line"></div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($show_users): ?>
            <div class="section-pill">Users</div>

            <?php if (!empty($users)): ?>
                <?php foreach ($users as $u): ?>
                    <div class="project-card">
                        <div class="project-card-header">
                            <h2><?php echo htmlspecialchars($u['email']); ?></h2>
                            <span><?php echo htmlspecialchars($u['course']); ?> - Year <?php echo (int)$u['year']; ?></span>
                        </div>

                        <div class="project-card-body">
                            <p><strong>Bio:</strong> <?php echo shortText($u['bio'] ?? 'No bio'); ?></p>
                            <p><strong>Tags:</strong> <?php echo htmlspecialchars($u['tags'] ?? 'None'); ?></p>
                        </div>

                        <div class="project-card-actions">
                            <a href="/pages/view_profile.php?user_id=<?php echo (int)$u['user_id']; ?>" class="details-btn">
                                View Profile
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No users found.</p>
            <?php endif; ?>
        <?php endif; ?>

    <?php endif; ?>

</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>