<?php require_once __DIR__ . '/../includes/header2.php'; ?>
<?php $extra_css = '/assets/css/shakeeba.css'; ?>
<?php
$pageTitle = "Settings - MindLink";
?>
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $successMessage = "Settings updated successfully.";
}

<div class="container">
    <section class="hero-section">
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle">Manage your account preferences and communication settings.</p>
    </section>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="panel-card settings-side-card">
                <h2 class="section-heading">Account Summary</h2>
                <p class="mb-2"><strong>Name:</strong> Shakeeba</p>
                <p class="mb-2"><strong>Role:</strong> Student User</p>
                <p class="mb-2"><strong>Status:</strong> Active</p>
                <p class="small-muted mb-0">Update your details and preferences from the form on the right.</p>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="form-card">
                <h2 class="section-heading">Profile & Preferences</h2>

                <?php if ($successMessage): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" value="Shakeeba">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" value="shakeeba@example.com">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="availability" class="form-label">Availability</label>
                            <select id="availability" name="availability" class="form-select">
                                <option>Weekdays</option>
                                <option>Weekends</option>
                                <option>Evenings</option>
                                <option>Flexible</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="communication_method" class="form-label">Preferred Communication</label>
                            <select id="communication_method" name="communication_method" class="form-select">
                                <option>Email</option>
                                <option>Chat</option>
                                <option>Phone</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="notifications" class="form-label">Email Notifications</label>
                            <select id="notifications" name="notifications" class="form-select">
                                <option value="1">Enabled</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="privacy" class="form-label">Profile Visibility</label>
                            <select id="privacy" name="privacy" class="form-select">
                                <option>Public</option>
                                <option>Connections Only</option>
                                <option>Private</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="bio" class="form-label">Short Bio</label>
                            <textarea id="bio" name="bio" class="form-control" rows="4">Computer Systems student interested in collaborative academic projects.</textarea>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                        </div>

                        <div class="col-12 col-md-4 d-grid">
                            <button type="submit" class="btn btn-mindlink">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>