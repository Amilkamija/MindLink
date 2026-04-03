<?php
$pageTitle = "Project Details - MindLink";
$extra_css = '/assets/css/project_details.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<!-- Main Content -->
<div class="content-area">

  <!-- Header Card -->
  <div class="detail-card header-card">
    <div class="detail-header">
      <div class="header-info">
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

  <!-- Project Info & Skills -->
  <div class="detail-grid two-col">

    <div class="detail-card">
      <div class="detail-card-title">Project Information</div>
      <div class="info-list">
        <div class="info-item">
          <span class="info-label">Module</span>
          <span class="info-value">CS4084</span>
        </div>
        <div class="info-item">
          <span class="info-label">Category</span>
          <span class="info-value">Mobile Development</span>
        </div>
        <div class="info-item">
          <span class="info-label">Status</span>
          <span class="info-value">Active</span>
        </div>
        <div class="info-item">
          <span class="info-label">Deadline</span>
          <span class="info-value">15 April 2026</span>
        </div>
        <div class="info-item">
          <span class="info-label">Group Size</span>
          <span class="info-value">4 Students</span>
        </div>
      </div>
    </div>

    <div class="detail-card">
      <div class="detail-card-title">Required Skills</div>
      <div class="skills-list">
        <span class="skill-tag">Java</span>
        <span class="skill-tag">Android Studio</span>
        <span class="skill-tag">UI Design</span>
        <span class="skill-tag">Team Collaboration</span>
      </div>
    </div>
  </div>

  <!-- Team & Roles -->
  <div class="detail-grid two-col">

    <div class="detail-card">
      <div class="detail-card-title">Team Members</div>
      <ul class="member-list">
        <li class="member-item">
          <span class="member-name">Sarah</span>
          <span class="member-role">Frontend Developer</span>
        </li>
        <li class="member-item">
          <span class="member-name">John</span>
          <span class="member-role">Backend Developer</span>
        </li>
        <li class="member-item">
          <span class="member-name">Jack</span>
          <span class="member-role">UI Designer</span>
        </li>
      </ul>
    </div>

    <div class="detail-card">
      <div class="detail-card-title">Open Roles</div>
      <ul class="roles-list">
        <li class="role-item">Database Support</li>
        <li class="role-item">Testing & Debugging</li>
        <li class="role-item">Documentation</li>
      </ul>
    </div>
  </div>

  <!-- Description -->
  <div class="detail-card">
    <div class="detail-card-title">Project Description</div>
    <div class="description-box">
      <p>
        This project focuses on building a mobile application for student collaboration.
        The goal is to design and implement a responsive, user-friendly mobile app with
        clear navigation, modern interface design, and useful team functionality for
        academic project work.
      </p>
    </div>
  </div>

</div> <!-- end .content-area -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

