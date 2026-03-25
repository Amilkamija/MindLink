<?php
$extra_css = '/assets/css/my_projects.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<div class="container-fluid px-4 px-lg-5 pt-3">
  <div class="row mt-4">

    <div class="col-lg-2 sidebar">
      <a href="/pages/dashboard.php">Home</a>
      <a href="/pages/projects.php">Projects</a>
      <a href="/pages/profile.php">Profile</a>
      <a href="/pages/matches.php">Matches</a>
      <a href="/pages/messages.php">Messages</a>
      <a href="/pages/settings.php">Settings</a>

      <div class="admin-link-wrap">
        <a href="/pages/admin.php">Admin</a>
      </div>
    </div>

    <div class="col-lg-10 content-area">

      <div class="section-pill">My Projects</div>

      <div class="section-line"></div>

      <!-- Example Project 1 -->
      <div class="project-card">
        <div class="project-card-header">
          <h2>MindLink Platform</h2>
          <span>Year 3</span>
        </div>

        <div class="project-card-body">
          <p><strong>Task:</strong> Build full-stack student collaboration platform</p>
          <p><strong>Status:</strong> In Progress</p>
          <p><strong>Group size:</strong> 4</p>
          <p><strong>Tags:</strong> PHP, MySQL, UI/UX</p>
        </div>

        <div class="project-card-actions">
          <a href="/pages/applications.php" class="view-applications-btn">
            View Applications
          </a>
        </div>
      </div>

      <!-- Example Project 2 -->
      <div class="project-card">
        <div class="project-card-header">
          <h2>Task Manager App</h2>
          <span>Year 2</span>
        </div>

        <div class="project-card-body">
          <p><strong>Task:</strong> Build a productivity web app</p>
          <p><strong>Status:</strong> Completed</p>
          <p><strong>Group size:</strong> 3</p>
          <p><strong>Tags:</strong> JavaScript, Firebase</p>
        </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>