<?php
$extra_css = '/assets/css/create_project.css';
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
      <div class="create-project-top">
        <div class="section-pill create-project-pill">Create Project</div>
      </div>

      <div class="section-line"></div>

      <form action="#" method="POST" class="create-project-form">

        <div class="form-card">
          <div class="form-card-title">Project Details</div>

          <div class="create-grid two-col">
            <div class="field-group full-width">
              <label for="project_title">Project Title</label>
              <input type="text" id="project_title" name="project_title" placeholder="Enter project title">
            </div>

            <div class="field-group">
              <label for="module_name">Module / Subject</label>
              <input type="text" id="module_name" name="module_name" placeholder="e.g. CS4416 Software Development">
            </div>

            <div class="field-group">
              <label for="year_group">Year</label>
              <select id="year_group" name="year_group">
                <option value="">Select year</option>
                <option>Year 1</option>
                <option>Year 2</option>
                <option>Year 3</option>
                <option>Year 4</option>
              </select>
            </div>

            <div class="field-group full-width">
              <label for="description">Project Description</label>
              <textarea id="description" name="description" rows="5" placeholder="Describe the project, goals, and what kind of teammates you are looking for..."></textarea>
            </div>
          </div>
        </div>

        <div class="form-card">
          <div class="form-card-title">Requirements</div>

          <div class="create-grid two-col">
            <div class="field-group">
              <label for="team_size">Team Size</label>
              <input type="number" id="team_size" name="team_size" placeholder="e.g. 4">
            </div>

            <div class="field-group">
              <label for="status">Project Status</label>
              <select id="status" name="status">
                <option value="">Select status</option>
                <option>Open for applications</option>
                <option>In progress</option>
                <option>Completed</option>
              </select>
            </div>

            <div class="field-group">
              <label for="deadline">Deadline</label>
              <input type="date" id="deadline" name="deadline">
            </div>

            <div class="field-group">
              <label for="tags">Skills / Tags</label>
              <input type="text" id="tags" name="tags" placeholder="e.g. PHP, MySQL, UI Design">
            </div>
          </div>
        </div>

        <div class="form-card">
          <div class="form-card-title">Roles Needed</div>

          <div class="create-grid three-col">
            <div class="field-group">
              <label for="role_1">Role 1</label>
              <input type="text" id="role_1" name="role_1" placeholder="e.g. Backend Developer">
            </div>

            <div class="field-group">
              <label for="role_2">Role 2</label>
              <input type="text" id="role_2" name="role_2" placeholder="e.g. UI/UX Designer">
            </div>

            <div class="field-group">
              <label for="role_3">Role 3</label>
              <input type="text" id="role_3" name="role_3" placeholder="e.g. Project Manager">
            </div>
          </div>
        </div>

        <div class="create-project-actions">
          <button type="button" class="save-draft-btn">Save Draft</button>
          <button type="submit" class="publish-project-btn">Publish Project</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>