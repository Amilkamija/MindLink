<?php
$pageTitle = " Project Details - MindLink";
$extra_css = '/assets/css/project_detail.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<div class="container-fluid px-4 px-lg-5 pt-4">
  <div class="row mt-3">
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
      <div class="project-details-top">
        <div class="section-pill project-details-pill">Project Details</div>
      </div>

      <div class="section-line"></div>

      <div class="project-details-wrapper">

        <div class="detail-card">
          <div class="detail-header">
            <div>
              <h1 class="project-title">CS4084 - Mobile App Dev</h1>
              <p class="project-subtitle">
                Project details, team members, required skills, and available roles.
              </p>
            </div>

            <div class="project-actions">
              <a href="#" class="detail-btn secondary-btn">View Members</a>
              <a href="/pages/apply.php" class="detail-btn primary-btn">Apply</a>
              <a href="#" class="detail-btn report-btn">Report</a>
            </div>
          </div>
        </div>

        <div class="detail-grid two-col">
          <div class="detail-card">
            <div class="detail-card-title">Project Information</div>
            <p><strong>Module:</strong> CS4084</p>
            <p><strong>Category:</strong> Mobile Development</p>
            <p><strong>Status:</strong> Active</p>
            <p><strong>Deadline:</strong> 15 April 2026</p>
            <p><strong>Group Size:</strong> 4 Students</p>
          </div>

          <div class="detail-card">
            <div class="detail-card-title">Required Skills</div>
            <ul class="detail-list">
              <li>Java</li>
              <li>Android Studio</li>
              <li>UI Design</li>
              <li>Team Collaboration</li>
            </ul>
          </div>
        </div>

        <div class="detail-grid two-col">
          <div class="detail-card">
            <div class="detail-card-title">Team Members</div>
            <ul class="detail-list">
              <li>Sarah - Frontend Developer</li>
              <li>John - Backend Developer</li>
              <li>Jack - UI Designer</li>
            </ul>
          </div>

          <div class="detail-card">
            <div class="detail-card-title">Open Roles</div>
            <ul class="detail-list">
              <li>Database Support</li>
              <li>Testing & Debugging</li>
              <li>Documentation</li>
            </ul>
          </div>
        </div>

        <div class="detail-card">
          <div class="detail-card-title">Project Description</div>
          <p class="project-description-text">
            This project focuses on building a mobile application for student collaboration.
            The goal is to design and implement a responsive, user-friendly mobile app with
            clear navigation, modern interface design, and useful team functionality for
            academic project work.
          </p>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>