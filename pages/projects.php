<?php $extra_css = '../assets/css/projects.css'; ?>
<?php require_once __DIR__ . '/../includes/header2.php'; ?>

<div class="container-fluid px-4 px-lg-5 pt-3">
  <div class="row mt-4">
    <div class="col-lg-2 sidebar">
      <a href="#">Home</a>
      <a href="#">Projects</a>
      <a href="#">Profile</a>
      <a href="#">Matches</a>
      <a href="#">Settings</a>

      <div class="admin-link-wrap">
        <a href="#">Admin</a>
      </div>
    </div>

    <div class="col-lg-10 content-area">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <button class="my-projects-btn">My projects</button>
        <button class="create-project-btn">Create a project</button>
      </div>

      <div class="section-line"></div>

      <div class="project-card">
        <div class="project-card-header">
          <h2>CS4416- Software Development</h2>
          <span>Year 3</span>
        </div>

        <div class="project-card-body">
          <p><strong>Task:</strong> Building a web-based academic collaboration platform using PHP and MySQL</p>
          <p><strong>Status:</strong> Open for applications</p>
          <p><strong>Group size :</strong> 4</p>
          <p><strong>Tags:</strong> PHP, MySQL, CSS, HTML, Organized, Consistent</p>
        </div>
      </div>

      <div class="section-line mt-4"></div>
    </div>
  </div>
</div>

</body>
</html>