<?php
$pageTitle = "Matches - MindLink";
$extra_css = '/assets/css/matches.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<h2 class="page-title mb-4">Matches</h2>

<div class="matches-layout">

  <!-- Stats -->
  <div class="matches-stats">
    <div class="stat-card"><h3>12</h3><p>Project Matches</p></div>
    <div class="stat-card"><h3>5</h3><p>Pending Requests</p></div>
    <div class="stat-card"><h3>8</h3><p>Teams Joined</p></div>
    <div class="stat-card"><h3>3</h3><p>New Messages</p></div>
  </div>

  <!-- Match Grid -->
  <div class="matches-grid">

    <!-- Ahmed -->
    <div class="match-card">
      <div class="profile-avatar">
        <svg width="42" height="42" viewBox="0 0 24 24" fill="none"
             stroke="#3d4a24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="7" r="4"></circle>
          <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
        </svg>
      </div>
      <h5 class="match-name">Ahmed</h5>
      <p class="match-role">Frontend Development</p>
      <p class="match-int">Interest: Web Projects</p>
      <div class="skills-row">
        <span class="skill-tag">HTML</span>
        <span class="skill-tag">CSS</span>
        <span class="skill-tag">React</span>
      </div>
      <div class="match-btn">
        <button class="btn btn-olive w-100">View Profile</button>
      </div>
    </div>

    <!-- Fatima -->
    <div class="match-card">
      <div class="profile-avatar">
        <svg width="42" height="42" viewBox="0 0 24 24" fill="none"
             stroke="#3d4a24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="7" r="4"></circle>
          <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
        </svg>
      </div>
      <h5 class="match-name">Fatima</h5>
      <p class="match-role">UI / UX Design</p>
      <p class="match-int">Interest: Mobile Applications</p>
      <div class="skills-row">
        <span class="skill-tag">Figma</span>
        <span class="skill-tag">Adobe XD</span>
        <span class="skill-tag">Branding</span>
      </div>
      <div class="match-btn">
        <button class="btn btn-olive w-100">View Profile</button>
      </div>
    </div>

    <!-- David -->
    <div class="match-card">
      <div class="profile-avatar">
        <svg width="42" height="42" viewBox="0 0 24 24" fill="none"
             stroke="#3d4a24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="7" r="4"></circle>
          <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
        </svg>
      </div>
      <h5 class="match-name">David</h5>
      <p class="match-role">Backend Development</p>
      <p class="match-int">Interest: Databases & APIs</p>
      <div class="skills-row">
        <span class="skill-tag">Node.js</span>
        <span class="skill-tag">Express</span>
        <span class="skill-tag">MongoDB</span>
      </div>
      <div class="match-btn">
        <button class="btn btn-olive w-100">View Profile</button>
      </div>
    </div>

  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
