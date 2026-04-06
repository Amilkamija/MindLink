<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$is_own_profile = ($profile_id === $_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_own_profile && isset($_POST['bio'])) {
    $bio = trim($_POST['bio']);
    $stmt = $conn->prepare("UPDATE Users SET bio = ? WHERE user_id = ?");
    $stmt->bind_param("si", $bio, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    header("Location: /pages/profile.php");
    exit();
}

$stmt = $conn->prepare("SELECT user_id, email, course, year, bio FROM Users WHERE user_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "User not found.";
    exit();
}

$stmt = $conn->prepare("SELECT ROUND(AVG(score), 1) AS avg_score, COUNT(*) AS total FROM Ratings WHERE rated_user_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$ratingData = $stmt->get_result()->fetch_assoc();
$stmt->close();
$avgScore = $ratingData['avg_score'] ?? 0;
$totalRatings = $ratingData['total'] ?? 0;


$stmt = $conn->prepare("SELECT s.skill_name FROM UserSkills us JOIN Skills s ON us.skill_id = s.skill_id WHERE us.user_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$skillsResult = $stmt->get_result();
$skills = [];
while ($row = $skillsResult->fetch_assoc()) {
    $skills[] = $row['skill_name'];
}
$stmt->close();


$stmt = $conn->prepare("
    SELECT DISTINCT p.project_id, p.title, p.description, p.team_size, p.status
    FROM Projects p
    LEFT JOIN TeamMembership tm ON tm.project_id = p.project_id AND tm.user_id = ?
    WHERE p.owner_id = ? OR tm.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("iii", $profile_id, $profile_id, $profile_id);
$stmt->execute();
$projectsResult = $stmt->get_result();
$stmt->close();

function formatStatus($status) {
    $labels = [
        'open'        => 'Open for applications',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
    ];
    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}
?>

<?php $extra_css = '../assets/css/profile.css'; ?>
<?php require_once __DIR__ . '/../includes/header2.php'; ?>

    <div class="col-lg-10 content-area">
      <div class="profile-top-section">
        <div class="profile-avatar-wrap">
          <div class="profile-avatar">
            <div class="profile-avatar-head"></div>
            <div class="profile-avatar-body"></div>
          </div>
        </div>

        <div class="profile-details">
          <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
          <p><strong>Course:</strong> <?= htmlspecialchars($user['course'] ?? '—') ?></p>
          <p><strong>Year:</strong> <?= htmlspecialchars($user['year'] ?? '—') ?></p>
        </div>

        <div class="profile-rating">
          <span class="rating-label">Rating:</span>
          <span class="stars">
            <?php
            $full  = floor($avgScore);
            $empty = 5 - $full;
            echo str_repeat('★', $full) . str_repeat('☆', $empty);
            ?>
          </span>
          <span class="rating-count">(<?= $totalRatings ?>)</span>
        </div>
      </div>

      <div class="section-line profile-line"></div>

      <div class="about-me-header">
        <div class="section-pill">About Me</div>
        <?php if ($is_own_profile): ?>
          <button class="btn-edit-aboutme" onclick="toggleAboutMe()">Edit</button>
        <?php endif; ?>
      </div>

      <?php if ($is_own_profile): ?>
        <div id="aboutme-display" class="about-text">
          <?= $user['bio'] ? htmlspecialchars($user['bio']) : 'No bio yet.' ?>
        </div>
        <form id="aboutme-form" method="POST" action="/pages/profile.php" style="display:none;">
          <textarea name="bio" class="about-edit-textarea"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
          <button type="submit" class="btn-save-aboutme">Save</button>
        </form>
      <?php else: ?>
        <div class="about-text">
          <?= $user['bio'] ? htmlspecialchars($user['bio']) : 'No bio yet.' ?>
        </div>
      <?php endif; ?>

      <script>
        function toggleAboutMe() {
          const display = document.getElementById('aboutme-display');
          const form    = document.getElementById('aboutme-form');
          const btn     = document.querySelector('.btn-edit-aboutme');
          if (form.style.display === 'none') {
            form.style.display = 'block';
            display.style.display = 'none';
            btn.textContent = 'Cancel';
          } else {
            form.style.display = 'none';
            display.style.display = 'block';
            btn.textContent = 'Edit';
          }
        }
      </script>

      <?php if (!empty($skills)): ?>
      <div class="section-pill">Skills</div>
      <div class="about-text">
        <?= implode(', ', array_map('htmlspecialchars', $skills)) ?>
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
                <p><strong>Status:</strong> <span class="<?= $statusClass ?>"><?= formatStatus($project['status']) ?></span></p>
                <p><strong>Team size:</strong> <?= htmlspecialchars($project['team_size'] ?? '—') ?></p>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p>No projects yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
