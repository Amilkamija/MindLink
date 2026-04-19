<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$profile_id     = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$is_own_profile = ($profile_id === $_SESSION['user_id']);

$show_welcome = !empty($_COOKIE['first_login']) && $is_own_profile;
if ($show_welcome) {
    setcookie('first_login', '', time() - 3600, '/');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_own_profile && isset($_POST['bio'])) {
    $bio = trim($_POST['bio']);
    $stmt = $conn->prepare("UPDATE Users SET bio = ? WHERE user_id = ?");
    $stmt->bind_param("si", $bio, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    header("Location: /pages/profile.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_own_profile && isset($_POST['course'])) {
    $course = trim($_POST['course']);
    $year   = (int)$_POST['year'];
    $stmt = $conn->prepare("UPDATE Users SET course = ?, year = ? WHERE user_id = ?");
    $stmt->bind_param("sii", $course, $year, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    header("Location: /pages/profile.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_own_profile && isset($_FILES['photo'])) {
    $file     = $_FILES['photo'];
    $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2 MB

    if ($file['error'] === UPLOAD_ERR_OK && in_array($file['type'], $allowed) && $file['size'] <= $max_size) {
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
        $dest     = __DIR__ . '/../assets/uploads/profiles/' . $filename;

        if (!is_dir(__DIR__ . '/../assets/uploads/profiles/')) {
            mkdir(__DIR__ . '/../assets/uploads/profiles/', 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $stmt = $conn->prepare("UPDATE Users SET profile_picture = ? WHERE user_id = ?");
            $stmt->bind_param("si", $filename, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: /pages/profile.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_own_profile && isset($_POST['add_tag'])) {
    $tag     = trim($_POST['add_tag']);
    $new_tag = null;
    if ($tag !== '') {
        $stmt = $conn->prepare("SELECT tag_id FROM UserTags WHERE user_id = ? AND tag_name = ?");
        $stmt->bind_param("is", $_SESSION['user_id'], $tag);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO UserTags (user_id, tag_name) VALUES (?, ?)");
            $stmt->bind_param("is", $_SESSION['user_id'], $tag);
            $stmt->execute();
            $new_tag = ['tag_id' => $conn->insert_id, 'tag_name' => $tag];
        } else {
            $stmt->close();
        }
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'tag' => $new_tag]);
        exit();
    }
    header("Location: /pages/profile.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_own_profile && isset($_POST['remove_tag'])) {
    $tag_id = (int)$_POST['remove_tag'];
    $stmt   = $conn->prepare("DELETE FROM UserTags WHERE tag_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $tag_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }
    header("Location: /pages/profile.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_own_profile && isset($_POST['add_skill'])) {
    $skill_name = trim($_POST['add_skill']);
    $new_skill  = null;
    if ($skill_name !== '') {
        
        $stmt = $conn->prepare("SELECT skill_id FROM Skills WHERE skill_name = ?");
        $stmt->bind_param("s", $skill_name);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($skill_id);
            $stmt->fetch();
            $stmt->close();
        } else {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO Skills (skill_name) VALUES (?)");
            $stmt->bind_param("s", $skill_name);
            $stmt->execute();
            $skill_id = $conn->insert_id;
            $stmt->close();
        }
        
        $stmt = $conn->prepare("SELECT 1 FROM UserSkills WHERE user_id = ? AND skill_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $skill_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO UserSkills (user_id, skill_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $_SESSION['user_id'], $skill_id);
            $stmt->execute();
            $stmt->close();
            $new_skill = ['skill_id' => $skill_id, 'skill_name' => $skill_name];
        } else {
            $stmt->close();
        }
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'skill' => $new_skill]);
        exit();
    }
    header("Location: /pages/profile.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_own_profile && isset($_POST['remove_skill'])) {
    $skill_id = (int)$_POST['remove_skill'];
    $stmt = $conn->prepare("DELETE FROM UserSkills WHERE skill_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $skill_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }
    header("Location: /pages/profile.php");
    exit();
}

$stmt = $conn->prepare("SELECT user_id, email, course, year, bio, profile_picture FROM Users WHERE user_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "User not found.";
    exit();
}

$profile_incomplete = $is_own_profile && (
    empty($user['course']) || empty($user['year']) || empty($user['bio'])
);

$stmt = $conn->prepare("SELECT ROUND(AVG(score), 1) AS avg_score, COUNT(*) AS total FROM Ratings WHERE rated_user_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$ratingData = $stmt->get_result()->fetch_assoc();
$stmt->close();
$avgScore = $ratingData['avg_score'] ?? 0;
$totalRatings = $ratingData['total'] ?? 0;


$stmt = $conn->prepare("SELECT us.skill_id, s.skill_name FROM UserSkills us JOIN Skills s ON us.skill_id = s.skill_id WHERE us.user_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$skillsResult = $stmt->get_result();
$skills = [];
while ($row = $skillsResult->fetch_assoc()) {
    $skills[] = $row;
}
$stmt->close();

$stmt = $conn->prepare("SELECT tag_id, tag_name FROM UserTags WHERE user_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$tagsResult = $stmt->get_result();
$userTags = [];
while ($row = $tagsResult->fetch_assoc()) {
    $userTags[] = $row;
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
        'open' => 'Open for applications',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
    ];
    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}
?>

<?php $extra_css = '../assets/css/profile.css'; ?>
<?php require_once __DIR__ . '/../includes/header2.php'; ?>

<?php if ($show_welcome): ?>
<div id="welcome-overlay" class="welcome-overlay">
  <div class="welcome-modal">
    <div class="section-pill" style="font-size:1.1rem; padding: 8px 22px;">Welcome to MindLink!</div>
    <p class="welcome-subtitle">Let's get your profile set up so others can find and connect with you.</p>
    <ul class="welcome-checklist">
      <li>Upload a profile photo</li>
      <li>Add your course &amp; year</li>
      <li>Write a short bio</li>
      <li>Add your interests &amp; hobbies</li>
      <li>Add your skills</li>
    </ul>
    <div class="welcome-actions">
      <button class="btn-save-aboutme" onclick="closeWelcome()">Set up my profile</button>
      <button class="btn-save-aboutme" onclick="closeWelcome()">Skip for now</button>
    </div>
  </div>
</div>
<script>
  function closeWelcome() {
    document.getElementById('welcome-overlay').style.display = 'none';
  }
</script>
<?php endif; ?>

<?php if ($profile_incomplete): ?>
<div id="profile-banner" class="profile-incomplete-banner">
  <div class="profile-banner-inner">
    <span class="profile-banner-icon">&#9888;</span>
    <span class="profile-banner-text">Your profile is incomplete — you won't be able to apply to projects until you fill in your <strong>course</strong>, <strong>year</strong>, and <strong>bio</strong>.</span>
    <button class="profile-banner-close" onclick="dismissBanner()">&#10005;</button>
  </div>
</div>
<script>
function dismissBanner() {
  var b = document.getElementById('profile-banner');
  b.classList.add('banner-dismissed');
}
</script>
<?php endif; ?>

    <div class="col-lg-10 content-area">
      <div class="profile-top-section">

        
        <div class="profile-avatar-wrap">
          <?php if ($is_own_profile): ?>
            <form method="POST" action="/pages/profile.php" enctype="multipart/form-data" id="photo-form">
              <input type="file" name="photo" id="photo-input" accept="image/*" style="display:none;"
                    onchange="document.getElementById('photo-form').submit();">
            </form>
          <?php endif; ?>

          <div class="profile-avatar" <?= $is_own_profile ? 'onclick="document.getElementById(\'photo-input\').click();" title="Change photo"' : '' ?>>
            <?php if (!empty($user['profile_picture'])): ?>
              <img src="/assets/uploads/profiles/<?= htmlspecialchars($user['profile_picture']) ?>"
                  alt="Profile photo" class="profile-avatar-img">
            <?php else: ?>
              <div class="profile-avatar-head"></div>
              <div class="profile-avatar-body"></div>
            <?php endif; ?>
            <?php if ($is_own_profile): ?>
              <div class="profile-avatar-overlay">Edit</div>
            <?php endif; ?>
          </div>
        </div>

        
        <div class="profile-details">
          <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>

          <?php if ($is_own_profile): ?>
            <div id="details-display">
              <p><strong>Course:</strong> <?= htmlspecialchars($user['course'] ?? '—') ?></p>
              <p><strong>Year:</strong> <?= htmlspecialchars($user['year'] ?? '—') ?></p>
            </div>
            <form id="details-form" method="POST" action="/pages/profile.php" style="display:none;">
              <div class="details-field">
                <label>Course</label>
                <input type="text" name="course" value="<?= htmlspecialchars($user['course'] ?? '') ?>" class="details-input">
              </div>
              <div class="details-field">
                <label>Year</label>
                <select name="year" class="details-input">
                  <?php for ($y = 1; $y <= 5; $y++): ?>
                    <option value="<?= $y ?>" <?= ($user['year'] == $y) ? 'selected' : '' ?>>Year <?= $y ?></option>
                  <?php endfor; ?>
                </select>
              </div>
              <button type="submit" class="btn-save-aboutme">Save</button>
              <button type="button" class="btn-edit-aboutme" onclick="toggleDetails()" style="margin-left:8px;">Cancel</button>
            </form>
          <?php else: ?>
            <p><strong>Course:</strong> <?= htmlspecialchars($user['course'] ?? '—') ?></p>
            <p><strong>Year:</strong> <?= htmlspecialchars($user['year'] ?? '—') ?></p>
          <?php endif; ?>
        </div>

        
        <div class="profile-right-col">
          <?php if ($is_own_profile): ?>
            <button class="btn-edit-aboutme" onclick="toggleDetails()">Edit</button>
          <?php else: ?>
            <a href="/pages/report.php?user_id=<?= $profile_id ?>" class="btn-edit-aboutme" style="text-decoration:none;">Report User</a>
          <?php endif; ?>
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

        function toggleDetails() {
          const display = document.getElementById('details-display');
          const form    = document.getElementById('details-form');
          if (form.style.display === 'none') {
            form.style.display = 'block';
            display.style.display = 'none';
          } else {
            form.style.display = 'none';
            display.style.display = 'block';
          }
        }
      </script>

      <div class="section-pill">Skills</div>
      <?php if ($is_own_profile): ?>
        <form method="POST" action="/pages/profile.php" class="skill-add-form">
          <input type="text" name="add_skill" class="tag-input" placeholder="Add a skill..." maxlength="100" required>
          <button type="submit" class="btn-save-aboutme">Add</button>
        </form>
      <?php endif; ?>

      <div class="tags-container" id="skills-container">
        <?php if (!empty($skills)): ?>
          <?php foreach ($skills as $skill): ?>
            <span class="tag-chip" data-skill-id="<?= $skill['skill_id'] ?>">
              <?= htmlspecialchars($skill['skill_name']) ?>
              <?php if ($is_own_profile): ?>
                <button type="button" class="tag-remove" title="Remove">&#215;</button>
              <?php endif; ?>
            </span>
          <?php endforeach; ?>
        <?php else: ?>
          <p id="no-skills-msg" style="color:var(--text-soft); font-size:0.95rem;">No skills yet.</p>
        <?php endif; ?>
      </div>

      <?php if ($is_own_profile): ?>
      <script>
      (function () {
        const container = document.getElementById('skills-container');
        const addForm   = document.querySelector('.skill-add-form');

        function makeChip(skill) {
          const span = document.createElement('span');
          span.className = 'tag-chip';
          span.dataset.skillId = skill.skill_id;
          span.textContent = skill.skill_name + ' ';
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'tag-remove';
          btn.title = 'Remove';
          btn.innerHTML = '&#215;';
          btn.addEventListener('click', () => removeSkill(skill.skill_id, span));
          span.appendChild(btn);
          return span;
        }

        function removeSkill(skillId, chipEl) {
          const fd = new FormData();
          fd.append('remove_skill', skillId);
          fetch('/pages/profile.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
          }).then(r => r.json()).then(data => {
            if (data.success) chipEl.remove();
          });
        }

        container.querySelectorAll('.tag-chip').forEach(chip => {
          const btn = chip.querySelector('.tag-remove');
          if (btn) btn.addEventListener('click', () => removeSkill(chip.dataset.skillId, chip));
        });

        addForm.addEventListener('submit', e => {
          e.preventDefault();
          const input = addForm.querySelector('.tag-input');
          const skill = input.value.trim();
          if (!skill) return;
          const fd = new FormData();
          fd.append('add_skill', skill);
          fetch('/pages/profile.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
          }).then(r => r.json()).then(data => {
            if (data.success && data.skill) {
              const msg = document.getElementById('no-skills-msg');
              if (msg) msg.remove();
              container.appendChild(makeChip(data.skill));
              input.value = '';
            }
          });
        });
      })();
      </script>
      <?php endif; ?>

      <div class="section-line profile-line"></div>

      <div class="section-pill">Interests &amp; Hobbies</div>
      <?php if ($is_own_profile): ?>
        <form method="POST" action="/pages/profile.php" class="tag-add-form">
          <input type="text" name="add_tag" class="tag-input" placeholder="Add a tag..." maxlength="100" required>
          <button type="submit" class="btn-save-aboutme">Add</button>
        </form>
      <?php endif; ?>

      <div class="tags-container" id="tags-container">
        <?php if (!empty($userTags)): ?>
          <?php foreach ($userTags as $tag): ?>
            <span class="tag-chip" data-tag-id="<?= $tag['tag_id'] ?>">
              <?= htmlspecialchars($tag['tag_name']) ?>
              <?php if ($is_own_profile): ?>
                <button type="button" class="tag-remove" title="Remove">&#215;</button>
              <?php endif; ?>
            </span>
          <?php endforeach; ?>
        <?php else: ?>
          <p id="no-tags-msg" style="color:var(--text-soft); font-size:0.95rem;">No tags yet.</p>
        <?php endif; ?>
      </div>

      <?php if ($is_own_profile): ?>
      <script>
      (function () {
        const container = document.getElementById('tags-container');
        const addForm   = document.querySelector('.tag-add-form');

        function makeChip(tag) {
          const span = document.createElement('span');
          span.className = 'tag-chip';
          span.dataset.tagId = tag.tag_id;
          span.textContent = tag.tag_name + ' ';
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'tag-remove';
          btn.title = 'Remove';
          btn.innerHTML = '&#215;';
          btn.addEventListener('click', () => removeTag(tag.tag_id, span));
          span.appendChild(btn);
          return span;
        }

        function removeTag(tagId, chipEl) {
          const fd = new FormData();
          fd.append('remove_tag', tagId);
          fetch('/pages/profile.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
          }).then(r => r.json()).then(data => {
            if (data.success) chipEl.remove();
          });
        }

        container.querySelectorAll('.tag-chip').forEach(chip => {
          const btn = chip.querySelector('.tag-remove');
          if (btn) btn.addEventListener('click', () => removeTag(chip.dataset.tagId, chip));
        });

        addForm.addEventListener('submit', e => {
          e.preventDefault();
          const input = addForm.querySelector('.tag-input');
          const tag   = input.value.trim();
          if (!tag) return;
          const fd = new FormData();
          fd.append('add_tag', tag);
          fetch('/pages/profile.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
          }).then(r => r.json()).then(data => {
            if (data.success && data.tag) {
              const msg = document.getElementById('no-tags-msg');
              if (msg) msg.remove();
              container.appendChild(makeChip(data.tag));
              input.value = '';
            }
          });
        });
      })();
      </script>
      <?php endif; ?>

      <div class="section-line profile-large-line"></div>

      <div class="section-pill">Projects</div>

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
                    'open' => 'status-open',
                    'in_progress' => 'status-progress',
                    'completed' => 'status-completed',
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
