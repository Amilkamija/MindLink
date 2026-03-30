<?php
$extra_css = '/assets/css/my_projects.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<div class="col-lg-10 content-area">

  <div class="section-pill">My Projects</div>

  <div class="section-line"></div>

  <!-- Project 1 -->
  <div class="project-card">
    <div class="project-card-header">
      <h2>MindLink Platform</h2>
      <span>Year 3</span>
    </div>

    <div class="project-card-body">
      <p><strong>Task:</strong> Build full-stack student collaboration platform</p>
      <p><strong>Status:</strong> <span class="status status-progress">In Progress</span></p>
      <p><strong>Group size:</strong> 4</p>
      <p><strong>Tags:</strong> PHP, MySQL, UI/UX</p>
    </div>

    <div class="project-card-actions">
      <a href="/pages/applications.php" class="view-applications-btn">
        View Applications
      </a>
    </div>
  </div>

  <!-- Project 2 -->
  <div class="project-card completed">
    <div class="project-card-header">
      <h2>Task Manager App</h2>
      <span>Year 2</span>
    </div>

    <div class="project-card-body">
      <p><strong>Task:</strong> Build a productivity web app</p>
      <p><strong>Status:</strong> <span class="status status-completed">Completed</span></p>
      <p><strong>Group size:</strong> 3</p>
      <p><strong>Tags:</strong> JavaScript, Firebase</p>
    </div>

    <div class="project-card-actions completed">
      <span class="completed-badge">Completed</span>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>