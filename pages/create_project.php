<?php
$extra_css = '/assets/css/create_project.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<div class="col-lg-10 content-area">

  <div class="create-project-top">
    <div class="section-pill create-project-pill">Create Project</div>
  </div>

  <div class="section-line"></div>

  <div class="create-layout">

  
    <form action="#" method="POST" class="create-project-form">

      
      <div class="form-card">
        <div class="form-card-title">Project Details</div>

        <div class="create-grid two-col">
          <div class="field-group full-width">
            <label>Project Title</label>
            <input type="text" placeholder="Enter project title">
          </div>

          <div class="field-group">
            <label>Module / Subject</label>
            <input type="text" placeholder="e.g. CS4416 Software Development">
          </div>

          <div class="field-group">
            <label>Year</label>
            <select>
              <option>Select year</option>
              <option>Year 1</option>
              <option>Year 2</option>
              <option>Year 3</option>
              <option>Year 4</option>
            </select>
          </div>

          <div class="field-group full-width">
            <label>Project Description</label>
            <textarea placeholder="Describe the project..."></textarea>
          </div>
        </div>
      </div>

     
      <div class="section-line form-divider"></div>

     
      <div class="form-card">
        <div class="form-card-title">Requirements</div>

        <div class="create-grid two-col">
          <div class="field-group">
            <label>Team Size</label>
            <input type="number" placeholder="e.g. 4">
          </div>

          <div class="field-group">
            <label>Project Status</label>
            <select>
              <option>Select status</option>
              <option>Open for applications</option>
              <option>In progress</option>
              <option>Completed</option>
            </select>
          </div>

          <div class="field-group">
            <label>Deadline</label>
            <input type="date">
          </div>

          <div class="field-group">
            <label>Skills / Tags</label>
            <input type="text" placeholder="e.g. PHP, MySQL, UI Design">
          </div>
        </div>
      </div>

    </form>

 
    <div class="roles-panel">

      <div class="roles-panel-title">Roles Needed</div>

      <div class="role-card">
        <div class="role-card-title">Role 1</div>
        <p>Add a key role for your project team.</p>
        <input type="text" placeholder="e.g. Backend Developer">
      </div>

      <div class="role-card">
        <div class="role-card-title">Role 2</div>
        <p>Add another role to balance skills.</p>
        <input type="text" placeholder="e.g. UI/UX Designer">
      </div>

      <div class="role-card">
        <div class="role-card-title">Role 3</div>
        <p>Optional additional support role.</p>
        <input type="text" placeholder="e.g. Project Manager">
      </div>

        
      <div class="roles-actions">
        <button type="button" class="save-draft-btn">Save Draft</button>
        <button type="submit" class="publish-project-btn">Publish</button>
      </div>

    </div>

  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>