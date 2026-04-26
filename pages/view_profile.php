<?php
$extra_css = '/assets/css/profile.css';
require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

function yearLabel($y) {
    $map = [1=>'Year 1',2=>'Year 2',3=>'Year 3',4=>'Year 4',5=>'Year 5',6=>'Postgraduate',7=>'Master'];
    return $map[(int)$y] ?? ($y ? htmlspecialchars((string)$y) : '—');
}

$profile_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($profile_id <= 0) {
    header("Location: /pages/search.php");
    exit();
}

$stmt = $conn->prepare("SELECT user_id, email, course, year, bio, profile_picture FROM Users WHERE user_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "User not found.";
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$stmt = $conn->prepare("
    SELECT ROUND(AVG(score), 1) AS avg_score, COUNT(*) AS total
    FROM Ratings
    WHERE rated_user_id = ?
");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$ratingData = $stmt->get_result()->fetch_assoc();
$stmt->close();

$avgScore = $ratingData['avg_score'] ?? 0;
$totalRatings = $ratingData['total'] ?? 0;

$stmt = $conn->prepare("
    SELECT s.skill_name
    FROM UserSkills us
    JOIN Skills s ON us.skill_id = s.skill_id
    WHERE us.user_id = ?
    ORDER BY s.skill_name ASC
");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$skillsResult = $stmt->get_result();

$skills = [];
while ($row = $skillsResult->fetch_assoc()) {
    $skills[] = $row['skill_name'];
}
$stmt->close();

$stmt = $conn->prepare("SELECT tag_name FROM UserTags WHERE user_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$tagsResult = $stmt->get_result();
$userTags = [];
while ($row = $tagsResult->fetch_assoc()) {
    $userTags[] = $row['tag_name'];
}
$stmt->close();

$stmt = $conn->prepare("
    SELECT DISTINCT
        p.project_id,
        p.title,
        p.description,
        p.team_size,
        p.status,
        p.deadline
    FROM Projects p
    LEFT JOIN TeamMembership tm
        ON tm.project_id = p.project_id AND tm.user_id = ?
    WHERE p.owner_id = ? OR tm.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("iii", $profile_id, $profile_id, $profile_id);
$stmt->execute();
$projectsResult = $stmt->get_result();

function formatStatus($status) {
    $labels = [
        'open'        => 'Open for applications',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
    ];
    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

$email_prefix = explode('@', $user['email'])[0];
$display_name = ucwords(str_replace(['.', '_', '-'], ' ', $email_prefix));
?>

<div class="col-lg-10 content-area">
    <div class="profile-top-section">
        <div class="profile-avatar-wrap">
            <div class="profile-avatar">
                <?php if (!empty($user['profile_picture'])): ?>
                    <img src="/assets/uploads/profiles/<?= htmlspecialchars($user['profile_picture']) ?>"
                        alt="Profile" class="profile-avatar-img">
                <?php else: ?>
                    <div class="profile-avatar-head"></div>
                    <div class="profile-avatar-body"></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-details">
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Course:</strong> <?= htmlspecialchars($user['course'] ?? '—') ?></p>
            <p><strong>Year:</strong> <?= yearLabel($user['year'] ?? 0) ?></p>
        </div>

        <div class="profile-rating">
            <span class="rating-label">Rating:</span>
            <span class="stars">
                <?php
                $full = floor((float)$avgScore);
                $empty = 5 - $full;
                echo str_repeat('★', $full) . str_repeat('☆', $empty);
                ?>
            </span>
            <span class="rating-count">(<?= (int)$totalRatings ?>)</span>
        </div>
    </div>

    <div class="section-line profile-line"></div>

    <div class="section-pill">About Me</div>
    <div class="about-text">
        <?= $user['bio'] ? nl2br(htmlspecialchars($user['bio'])) : 'No bio yet.' ?>
    </div>

    <?php if (!empty($skills)): ?>
        <div class="section-line profile-large-line"></div>
        <div class="section-pill">Skills</div>
        <div class="about-text">
            <?= implode(', ', array_map('htmlspecialchars', $skills)) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($userTags)): ?>
        <div class="section-line profile-line"></div>
        <div class="section-pill">Interests &amp; Hobbies</div>
        <div class="tags-container">
            <?php foreach ($userTags as $tag): ?>
                <span class="tag-chip"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="section-line profile-large-line"></div>

    <div class="section-pill">Past Projects</div>

    <div class="past-projects-container">
        <?php if ($projectsResult->num_rows > 0): ?>
            <?php while ($project = $projectsResult->fetch_assoc()): ?>
                <div class="profile-project-card">
                    <div class="project-card-header">
                        <h2><?= htmlspecialchars($project['title']) ?></h2>
                    </div>

                    <div class="project-card-body">
                        <p><?= htmlspecialchars($project['description'] ?? '') ?></p>

                        <?php
                        $statusClasses = [
                            'open'        => 'status-open',
                            'in_progress' => 'status-progress',
                            'completed'   => 'status-completed',
                        ];
                        $statusClass = $statusClasses[$project['status']] ?? '';
                        ?>

                        <p>
                            <strong>Status:</strong>
                            <span class="<?= $statusClass ?>"><?= formatStatus($project['status']) ?></span>
                        </p>
                        <p><strong>Team size:</strong> <?= htmlspecialchars($project['team_size'] ?? '—') ?></p>
                        <p><strong>Deadline:</strong> <?= htmlspecialchars($project['deadline'] ?? '—') ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No projects yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php
$projectsResult->free();
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>