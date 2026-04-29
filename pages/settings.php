<?php
$pageTitle = "Settings - MindLink";
$extra_css = '/assets/css/settings.css';

require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$current_user_id = (int) $_SESSION['user_id'];
$success_message = '';
$error_message = '';

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Fetch current user data
$user_sql = "SELECT email, phone FROM Users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $current_user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

// Fetch user settings (create default if not exists)
$settings_sql = "SELECT * FROM UserSettings WHERE user_id = ?";
$settings_stmt = $conn->prepare($settings_sql);
$settings_stmt->bind_param("i", $current_user_id);
$settings_stmt->execute();
$settings_result = $settings_stmt->get_result();

if ($settings_result->num_rows === 0) {
    $insert_settings = "INSERT INTO UserSettings (user_id, email_notifications, push_notifications, project_updates, show_profile_matches, allow_profile_search) VALUES (?, 1, 0, 1, 1, 1)";
    $insert_stmt = $conn->prepare($insert_settings);
    $insert_stmt->bind_param("i", $current_user_id);
    $insert_stmt->execute();
    $insert_stmt->close();

    $settings_stmt = $conn->prepare($settings_sql);
    $settings_stmt->bind_param("i", $current_user_id);
    $settings_stmt->execute();
    $settings_result = $settings_stmt->get_result();
}

$settings_data = $settings_result->fetch_assoc();
$settings_stmt->close();

/* Handle POST - Account Information */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_account'])) {
    $new_email = trim($_POST['email'] ?? '');
    $new_phone = trim($_POST['phone'] ?? '');

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        $check_email = "SELECT user_id FROM Users WHERE email = ? AND user_id != ?";
        $check_stmt = $conn->prepare($check_email);
        $check_stmt->bind_param("si", $new_email, $current_user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_message = "This email is already in use by another account.";
        } else {
            $update_sql = "UPDATE Users SET email = ?, phone = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $new_email, $new_phone, $current_user_id);

            if ($update_stmt->execute()) {
                $success_message = "Account information updated successfully.";
                $user_data['email'] = $new_email;
                $user_data['phone'] = $new_phone;
            } else {
                $error_message = "Failed to update account information.";
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
}

/* Handle POST - Notification Settings */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notifications'])) {
    $email_notif = isset($_POST['email_notifications']) ? 1 : 0;
    $push_notif = isset($_POST['push_notifications']) ? 1 : 0;
    $project_updates = isset($_POST['project_updates']) ? 1 : 0;

    $update_sql = "UPDATE UserSettings SET email_notifications = ?, push_notifications = ?, project_updates = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("iiii", $email_notif, $push_notif, $project_updates, $current_user_id);

    if ($update_stmt->execute()) {
        $success_message = "Notification settings updated successfully.";
        $settings_data['email_notifications'] = $email_notif;
        $settings_data['push_notifications'] = $push_notif;
        $settings_data['project_updates'] = $project_updates;
    } else {
        $error_message = "Failed to update notification settings.";
    }
    $update_stmt->close();
}

/* Handle POST - Privacy Settings */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_privacy'])) {
    $show_profile = isset($_POST['show_profile']) ? 1 : 0;
    $profile_search = isset($_POST['profile_search']) ? 1 : 0;

    $update_sql = "UPDATE UserSettings SET show_profile_matches = ?, allow_profile_search = ? WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("iii", $show_profile, $profile_search, $current_user_id);

    if ($update_stmt->execute()) {
        $success_message = "Privacy settings updated successfully.";
        $settings_data['show_profile_matches'] = $show_profile;
        $settings_data['allow_profile_search'] = $profile_search;
    } else {
        $error_message = "Failed to update privacy settings.";
    }
    $update_stmt->close();
}
?>

<div class="settings-wrapper">
    <h1 class="settings-title">Settings</h1>

    <?php if ($success_message): ?>
        <div class="settings-alert settings-alert-success">
            <?php echo safeText($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="settings-alert settings-alert-error">
            <?php echo safeText($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="settings-grid">

        <!-- Account Information -->
        <div class="settings-card">
            <div class="settings-card-header">Account Information</div>
            <div class="settings-card-body">
                <form method="POST" action="">
                    <div class="settings-field-row">
                        <label>Change Email</label>
                        <input type="email" name="email" value="<?php echo safeText($user_data['email'] ?? ''); ?>" required>
                    </div>

                    <div class="settings-field-row">
                        <label>Change Password</label>
                        <a href="/pages/reset_password.php" class="settings-link-btn"> Reset Password</a>
                    </div>

                    <div class="settings-field-row">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo safeText($user_data['phone'] ?? ''); ?>" placeholder="Add phone number">
                    </div>

                    <div class="settings-btn-wrap">
                        <button type="submit" name="save_account" class="settings-save-btn">Save Changes</button>
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
                            <input type="checkbox" name="email_notifications" <?php echo ($settings_data['email_notifications'] ?? 0) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="settings-toggle-row">
                        <span>Push Notifications</span>
                        <label class="switch">
                            <input type="checkbox" name="push_notifications" <?php echo ($settings_data['push_notifications'] ?? 0) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="settings-toggle-row">
                        <span>Project Updates</span>
                        <label class="switch">
                            <input type="checkbox" name="project_updates" <?php echo ($settings_data['project_updates'] ?? 0) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="settings-btn-wrap">
                        <button type="submit" name="save_notifications" class="settings-save-btn">Save Changes</button>
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
                            <input type="checkbox" name="show_profile" <?php echo ($settings_data['show_profile_matches'] ?? 0) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="settings-toggle-row">
                        <span>Allow Profile Search</span>
                        <label class="switch">
                            <input type="checkbox" name="profile_search" <?php echo ($settings_data['allow_profile_search'] ?? 0) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="settings-btn-wrap">
                        <button type="submit" name="save_privacy" class="settings-save-btn">Save Changes</button>
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
                    <a href="/pages/faq.php">FAQ</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>