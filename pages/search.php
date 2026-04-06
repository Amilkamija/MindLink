<?php
$extra_css = '/assets/css/projects.css';
require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

$q = trim($_GET['q'] ?? '');

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
    if (strlen($text) <= $length) return htmlspecialchars($text);
    return htmlspecialchars(substr($text, 0, $length)) . '...';
}

if ($q !== '') {

    // 🔍 PROJECT SEARCH (INCLUDING TAGS)
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
            GROUP_CONCAT(pt.tag_name SEPARATOR ', ') AS tags
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

    // 🔍 USER SEARCH
    $user_sql = "
        SELECT user_id, email, course, year, bio
        FROM Users
        WHERE email LIKE CONCAT('%', ?, '%')
           OR course LIKE CONCAT('%', ?, '%')
    ";

    $stmt = $conn->prepare($user_sql);
    $stmt->bind_param("ss", $q, $q);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
}
?>

<div class="col-lg-10 content-area">

    <div class="section-pill">Search Results</div>
    <div class="section-line"></div>

    <?php if ($q === ''): ?>
        <p>Please enter a search term.</p>
    <?php else: ?>

        <!-- PROJECT RESULTS -->
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
                        <a href="/pages/project_details.php?project_id=<?php echo $p['project_id']; ?>" class="details-btn">
                            View
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No projects found.</p>
        <?php endif; ?>

        <div class="section-line"></div>

        <!-- USER RESULTS -->
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
                    </div>

                    <div class="project-card-actions">
                        <a href="/pages/view_profile.php?user_id=<?php echo $u['user_id']; ?>" class="details-btn">
                            View Profile
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No users found.</p>
        <?php endif; ?>

    <?php endif; ?>

</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>