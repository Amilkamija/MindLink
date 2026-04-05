<?php
$pageTitle = "Settings - MindLink";
$extra_css = '/assets/css/settings.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<div class="settings-wrapper">
  <h1 class="settings-title">Settings</h1>

  <div class="settings-grid">

    <!-- Account Information -->
    <div class="settings-card">
      <div class="settings-card-header">Account Information</div>
      <div class="settings-card-body">
        <form method="POST" action="">
          <div class="settings-field-row">
            <label>Change Email</label>
            <input type="email" name="email" value="student@example.com">
          </div>

          <div class="settings-field-row">
            <label>Change Password</label>
            <input type="password" name="password" value="123456789">
          </div>

          <div class="settings-field-row">
            <label>Add Phone Number</label>
            <input type="tel" name="phone" value="................">
          </div>

          <div class="settings-btn-wrap">
            <button type="submit" name="save_account" class="settings-save-btn">
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Notification Settings -->
    <div class="settings-card">
      <div class="settings-card-header">Notification Settings</div>
      <div class="settings-card-body">
        <form method="POST" action="">
          <div class="settings-toggle-row">
            <span>Email Notifications</span>
            <label class="switch">
              <input type="checkbox" name="email_notifications" checked>
              <span class="slider"></span>
            </label>
          </div>

          <div class="settings-toggle-row">
            <span>Push Notifications</span>
            <label class="switch">
              <input type="checkbox" name="push_notifications">
              <span class="slider"></span>
            </label>
          </div>

          <div class="settings-toggle-row">
            <span>Project Updates</span>
            <label class="switch">
              <input type="checkbox" name="project_updates">
              <span class="slider"></span>
            </label>
          </div>

          <div class="settings-btn-wrap">
            <button type="submit" name="save_notifications" class="settings-save-btn">
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Privacy Settings -->
    <div class="settings-card">
      <div class="settings-card-header">Privacy Settings</div>
      <div class="settings-card-body">
        <form method="POST" action="">
          <div class="settings-toggle-row">
            <span>Show my profile on Matches</span>
            <label class="switch">
              <input type="checkbox" name="show_profile">
              <span class="slider"></span>
            </label>
          </div>

          <div class="settings-toggle-row">
            <span>Allow Profile Search</span>
            <label class="switch">
              <input type="checkbox" name="profile_search">
              <span class="slider"></span>
            </label>
          </div>

          <div class="settings-btn-wrap">
            <button type="submit" name="save_privacy" class="settings-save-btn">
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Safety & Support -->
    <div class="settings-card">
      <div class="settings-card-header">Safety &amp; Support</div>
      <div class="settings-card-body">
        <div class="settings-support-item">
          <a href="/pages/report.php">Report a Problem</a>
        </div>
        <div class="settings-support-item">
          <a href="/pages/community.php">Community Guidelines</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
