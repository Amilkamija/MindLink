<?php
$extra_css = '/assets/css/apply.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<div class="col-lg-10 content-area">
  <div class="apply-top">
    <div class="section-pill apply-pill">Apply to Project</div>
  </div>

  <div class="section-line"></div>

  <div class="apply-project-card">
    <div class="apply-project-header">
      <h2>CS4084 - Mobile App Dev</h2>
      <span>Year 3</span>
    </div>

    <div class="apply-project-body">
      <p><strong>Task:</strong> This project focuses on building a mobile application for student collaboration</p>
      <p><strong>Status:</strong> <span class="status status-open">Open for applications</span></p>
      <p><strong>Team Size:</strong> 4</p>
      <p><strong>Deadline:</strong> 15/04/2026</p>
      <p><strong>Tags:</strong> Java, Android Studio, UI Design, Team Collaboration</p>
    </div>
  </div>

  <div class="section-line mt-4"></div>

  <div class="apply-section-title">Available Roles</div>

  <div class="roles-grid">
    <div class="role-card">
      <div class="role-card-title">Database Support</div>
      <p>MySQL and Database implementation support.</p>
      <div class="role-status">Status: Open</div>
      <button type="button" class="select-role-btn">Select</button>
    </div>

    <div class="role-card">
      <div class="role-card-title">Testing and Debugging</div>
      <p>Handle database testing and debugging MySQL code.</p>
      <div class="role-status">Status: Open</div>
      <button type="button" class="select-role-btn">Select</button>
    </div>

    <div class="role-card">
      <div class="role-card-title">Documentation</div>
      <p>Database design documentation and tables descriptions</p>
      <div class="role-status">Status: Open</div>
      <button type="button" class="select-role-btn">Select</button>
    </div>
  </div>

  <div class="section-line mt-4"></div>

  <form action="#" method="POST" class="application-form-card">
    <div class="form-card-title">Your Application</div>

    <div class="application-grid">
      <div class="field-group">
        <label for="selected_role">Selected Role</label>
        <select id="selected_role" name="selected_role">
          <option value="">Choose a role</option>
          <option>Database Support</option>
          <option>Testing and Debugging</option>
          <option>Documentation</option>
        </select>
      </div>

      <div class="field-group full-width">
        <label for="application_message">Why do you want to join this project?</label>
        <textarea id="application_message" name="application_message" rows="6" placeholder="Write a short message to the project owner..."></textarea>
      </div>
    </div>

    <div class="application-actions">
      <a href="/pages/projects.php" class="cancel-apply-btn">Cancel</a>
      <button type="submit" class="submit-application-btn">Submit Application</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>