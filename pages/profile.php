
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MindLink - Profile</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/profile.css">
</head>
<body>
  <div class="container-fluid px-4 px-lg-5 pt-3">

    <div class="row align-items-center top-header">
      <div class="col-lg-3 d-flex align-items-center gap-4 small-links mb-3 mb-lg-0">
        <a href="#">CONTACT</a>
        <a href="#">FAQ</a>
      </div>

      <div class="col-lg-4 text-center mb-3 mb-lg-0">
        <div class="logo-wrap">
          <h1 class="logo-text">MINDLINK</h1>
          <img src="/assets/img/mindlink_logo.png" alt="MindLink logo" class="logo-image">
        </div>
      </div>

      <div class="col-lg-5 d-flex justify-content-lg-end align-items-center gap-3 flex-wrap">
        <div class="search-pill">
          <span>SEARCH</span>
          <img src="/assets/img/search_icon.png" class="search-icon" alt="Search">
        </div>
        <button class="logout-btn">LOG OUT</button>
      </div>
    </div>

    <div class="header-line"></div>

    <div class="row mt-3">
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
