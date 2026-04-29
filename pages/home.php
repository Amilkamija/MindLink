<?php
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}
require_once __DIR__ . '/../config/db.php';

$isSuspended = false;
$stmt = $conn->prepare("SELECT status FROM Users WHERE user_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $isSuspended = ($row['status'] ?? '') === 'suspended';
}

$extra_css = '/assets/css/home.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<h2 class="home-title">Welcome to <span class="home-title-green">MindLink!</span></h2>
<p class="home-subtitle">We are excited that you've joined our academic collaboration community.</p>
<p class="home-subtitle">MindLink helps you connect with students based on skills, interests, and project goals!</p>
<p class="home-subtitle">So you can build stronger teams and achieve better results</p>

<hr class="home-divider">

<div class="home-cards-row">
    <div class="home-card-group">
        <div class="home-card home-card-dark">
            <span class="home-card-badge">1. Complete Your Profile</span>
            <p>Before matching with others, tell us about yourself.</p>
            <ul>
                <li>Your course</li>
                <li>Your skills and strengths</li>
                <li>Availability</li>
            </ul>
        </div>
        <a href="/pages/profile.php" class="home-card-btn">Go to Profile</a>
    </div>

    <div class="home-card-group">
        <div class="home-card home-card-light">
            <span class="home-card-badge">2. Manage Projects</span>
            <p>Looking to join a team? Browse available projects and apply! Have an idea? Create your own project and define roles, skills needed, deadlines, and review applications from suitable teammates!</p>
        </div>
        <a href="/pages/projects.php" class="home-card-btn">Go to Projects</a>
    </div>

    <div class="home-card-group">
        <div class="home-card home-card-mid">
            <span class="home-card-badge">3. Explore</span>
            <p>Discover students who match your skills and interests. Apply to projects or review incoming applications.</p>
        </div>
        <a href="/pages/search.php" class="home-card-btn">Go to Search</a>
    </div>
</div>

<hr class="home-divider">

<div class="home-cards-row home-cards-row-bottom">
    <div class="home-card-group">
        <div class="home-card home-card-muted">
            <span class="home-card-badge">4. Stay Connected</span>
            <p>Message teammates directly to coordinate tasks and deadlines. Once matched, message privatley and get to know each other!</p>
        </div>
        <a href="/pages/messages.php" class="home-card-btn">Go to Messages</a>
    </div>

    <div class="home-card-group">
        <div class="home-card home-card-dark2">
            <span class="home-card-badge">5. Strengthen Your Connection!</span>
            <p>Once a project is finished you can choose to match with one of your teammates and see if the connection is worth keeping!</p>
        </div>
        <a href="/pages/matches.php" class="home-card-btn">Explore Connections</a>
    </div>
</div>

<?php if ($isSuspended): ?>
<div class="modal fade" id="suspendedModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px; overflow:hidden; border:none;">
      <div style="background:#c0392b; padding:16px 20px; display:flex; justify-content:space-between; align-items:center;">
        <div style="color:#fff; font-weight:700; font-size:1rem;">Account Suspended</div>
      </div>
      <div style="padding:20px 24px;">
        <p style="font-size:0.95rem; margin-bottom:12px;">Your account has been suspended. You currently have limited access to MindLink.</p>
        <p style="font-size:0.95rem; margin-bottom:0;">If you believe this is a mistake, you can submit an appeal and our team will review it.</p>
      </div>
      <div style="padding:12px 20px; border-top:1px solid #eee; display:flex; justify-content:space-between; align-items:center; background:#fafafa;">
        <a href="/pages/report.php" style="background:#57673E; color:#fff; border:none; border-radius:20px; padding:7px 20px; font-size:0.88rem; text-decoration:none;">Go to Appeal</a>
        <button style="background:#8a9b6e; color:#fff; border:none; border-radius:20px; padding:7px 20px; font-size:0.88rem; cursor:pointer;" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('suspendedModal')).show();
  });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
