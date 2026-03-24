<?php
$extra_css = '/assets/css/apply.css';
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
      <div class="apply-top">
        <div class="section-pill apply-pill">Apply to Project</div>
      </div>

      <div class="section-line"></div>

      <div class="apply-project-card">
        <div class="apply-project-header">
          <h2>CS4416 - Software Development</h2>
          <span>Year 3</span>
        </div>

        <div class="apply-project-body">
          <p><strong>Task:</strong> Building a web-based academic collaboration platform using PHP and MySQL</p>
          <p><strong>Status:</strong> Open for applications</p>
          <p><strong>Team Size:</strong> 4</p>
          <p><strong>Deadline:</strong> 20/04/2026</p>
          <p><strong>Tags:</strong> PHP, MySQL, CSS, HTML, Organized, Consistent</p>
        </div>
      </div>

      <div class="section-line mt-4"></div>

      <div class="apply-section-title">Available Roles</div>

      <div class="roles-grid">
        <div class="role-card">
          <div class="role-card-title">Frontend Developer</div>
          <p>Build and style the user interface for the platform.</p>
          <div class="role-status">Status: Open</div>
          <button type="button" class="select-role-btn">Select</button>
        </div>

        <div class="role-card">
          <div class="role-card-title">Backend Developer</div>
          <p>Handle database logic, project applications, and server-side functionality.</p>
          <div class="role-status">Status: Open</div>
          <button type="button" class="select-role-btn">Select</button>
        </div>

        <div class="role-card">
          <div class="role-card-title">UI/UX Designer</div>
          <p>Improve visual layout, usability, and user flow across the website.</p>
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
              <option>Frontend Developer</option>
              <option>Backend Developer</option>
              <option>UI/UX Designer</option>
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
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>