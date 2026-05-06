<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$success = '';
$error   = '';
$token   = trim($_GET['token'] ?? '');

// Validate the token: must exist, be unused, and be less than 1 hour old , it works by ip + token (should've  been changed , per account)
$valid_token = false;
if ($token !== '') {
    $stmt = $conn->prepare(
        "SELECT reset_id, email FROM PasswordResets
        WHERE token = ? AND used = 0 AND created_at >= NOW() - INTERVAL 1 HOUR"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $valid_token = true;
        $reset_id    = $row['reset_id'];
        $reset_email = $row['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($password) || empty($confirm)) {
        $error = "Please fill in both fields.";
    } elseif (strlen($password) < 8 || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $error = "Password must be at least 8 characters and include at least 1 special character.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE Users SET password_hash = ? WHERE email = ?");
        $stmt->bind_param("ss", $hash, $reset_email);
        $stmt->execute();
        $stmt->close();

        // Mark token as used so the same link can't be reused
        $stmt = $conn->prepare("UPDATE PasswordResets SET used = 1 WHERE reset_id = ?");
        $stmt->bind_param("i", $reset_id);
        $stmt->execute();
        $stmt->close();

        $success     = "Your password has been reset. You can now log in.";
        $valid_token = false;
    }
}

$extra_css = '/assets/css/forgot_password.css';
require_once __DIR__ . '/../includes/header1.php';
?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="forgot-card">
                <h4 class="forgot-title">Reset Password</h4>

                <?php if ($success): ?>
                    <p class="forgot-success"><?= htmlspecialchars($success) ?></p>
                    <a href="/pages/login.php" class="sendresetlink-btn d-inline-block text-decoration-none mt-2">Go to Login</a>

                <?php elseif (!$valid_token): ?>
                    <p class="forgot-error">This reset link is invalid or has expired.</p>
                    <a href="/pages/forgot_password.php" class="sendresetlink-btn d-inline-block text-decoration-none mt-2">Request a new link</a>

                <?php else: ?>
                    <p class="forgot-desc">Enter your new password below.</p>

                    <?php if ($error): ?>
                        <p class="forgot-error"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>

                    <form action="/pages/reset_password.php?token=<?= htmlspecialchars($token) ?>" method="POST">
                        <input type="password" name="password" class="forgot-text" placeholder="New password" required>
                        <input type="password" name="confirm_password" class="forgot-text" placeholder="Confirm new password" required>
                        <button type="submit" class="sendresetlink-btn">Reset Password</button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
