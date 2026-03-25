<?php $extra_css = '../assets/css/projects.css'; ?>
<?php require_once __DIR__ . '/../includes/header2.php'; ?>

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
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <button class="my-projects-btn">Available Projects</button>

        <div class="d-flex gap-3">
          <a href="/pages/my_projects.php" class="my-projects-btn">My Projects</a>
          <a href="/pages/create_project.php" class="create-project-btn">Create a project</a>
        </div>
      </div>

      <div class="section-line"></div>

      <div class="project-card">
        <div class="project-card-header">
          <h2>CS4084 - Mobile App Dev</h2>
          <span>Year 3</span>
        </div>

        <div class="project-card-body">
          <p><strong>Task:</strong> This project focuses on building a mobile application for student collaboration </p>
          <p><strong>Status:</strong> Open for applications</p>
          <p><strong>Group size :</strong> 4</p>
          <p><strong>Tags:</strong> Java, Android Studio, UI Design, Team Collaboration</p>
        </div>

        <div class="project-card-actions">
  <a href="/pages/project_details.php?project_id=1" class="details-btn"> Project Details </a>

  <a href="/pages/apply.php?project_id=1" class="apply-btn"> Apply </a>
   </div>
</div>

      <div class="section-line mt-4"></div>
    </div>
  </div>
</div>

</body>
</html>