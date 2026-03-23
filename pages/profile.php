<?php $extra_css = '../assets/css/profile.css'; ?>
<?php require_once __DIR__ . '/../includes/header2.php'; ?>

<div class="container-fluid px-4 px-lg-5 pt-3">
  <div class="row mt-3">
    <div class="col-lg-2 sidebar">
      <a href="/pages/dashboard.php">Home</a>
      <a href="/pages/projects.php">Projects</a>
      <a href="/pages/profile.php">Profile</a>
      <a href="/pages/matches.php">Matches</a>
      <a href="/pages/settings.php">Settings</a>

      <div class="admin-link-wrap">
        <a href="/pages/admin.php">Admin</a>
      </div>
    </div>

    <div class="col-lg-10 content-area">
      <div class="profile-top-section">
        <div class="profile-avatar-wrap">
          <div class="profile-avatar">
            <div class="profile-avatar-head"></div>
            <div class="profile-avatar-body"></div>
          </div>
        </div>

        <div class="profile-details">
          <p>Name:</p>
          <p>Age:</p>
          <p>Course:</p>
        </div>

        <div class="profile-rating">
          <span class="rating-label">Rating:</span>
          <span class="stars">★★★★★</span>
        </div>
      </div>

      <div class="section-line profile-line"></div>

      <div class="section-pill">About Me</div>

      <div class="about-text">
        Type here...
      </div>

      <div class="section-line profile-large-line"></div>

      <div class="section-pill">Past Projects</div>

      <div class="profile-project-card">
        <div class="project-card-header">
          <h2>CS4084 - Mobile Application Development</h2>
        </div>

        <div class="project-card-body">
          <p><strong>Task:</strong> Building an Android App using Android Studio</p>
          <p><strong>Status:</strong> Complete</p>
          <p><strong>Group size:</strong> 4 <span class="view-members">View Members</span></p>
          <p><strong>Tags:</strong> Android, Gradle, Java, Android Studio, Organized, Consistent</p>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>