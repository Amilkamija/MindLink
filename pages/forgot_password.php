<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$success    = '';
$error      = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $token = bin2hex(random_bytes(32));

            $stmt = $conn->prepare("INSERT INTO PasswordResets (email, token) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $token);
            $stmt->execute();
            $stmt->close();

            $reset_link = "https://cs4116group21.infinityfree.me/pages/reset_password.php?token=" . $token;
        }

        $success = "If an account with that email exists, a reset link has been generated.";
    }
}

$extra_css = '/assets/css/forgot_password.css';
require_once __DIR__ . '/../includes/header1.php';
?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="forgot-card">
                <h4 class="forgot-title">Forgot Password?</h4>
                <p class="forgot-desc">Enter your email address to generate a password reset link.</p>

                <?php if ($success): ?>
                    <p class="forgot-success"><?= htmlspecialchars($success) ?></p>
                <?php endif; ?>

                <?php if ($reset_link): ?>
                    <p class="forgot-desc">Your reset link:</p>
                    <a href="<?= htmlspecialchars($reset_link) ?>" class="forgot-link"><?= htmlspecialchars($reset_link) ?></a>
                <?php endif; ?>

                <?php if ($error): ?>
                    <p class="forgot-error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <?php if (!$success): ?>
                <form action="/pages/forgot_password.php" method="POST">
                    <input type="email" name="email" class="forgot-text" placeholder="Email" required>
                    <button type="submit" class="sendresetlink-btn">Send reset link</button>
                </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
