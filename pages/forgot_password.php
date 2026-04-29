<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$step = 1;
$error = '';
$success = '';
$reset_link = '';
$security_question = '';

//look up the user's security question by email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && !isset($_POST['security_answer'])) {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, security_question FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && !empty($user['security_question'])) {
            $_SESSION['reset_email'] = $email;
            $security_question = $user['security_question'];
            $step = 2;
        } else {
            $error = "If an account with that email exists, you will be prompted for your security question.";
        }
    }
}

// verify the security answer, then generate a reset token
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['security_answer'])) {
    $answer = strtolower(trim($_POST['security_answer']));
    $email = $_SESSION['reset_email'] ?? '';

    if (empty($email)) {
        $error = "Session expired. Please start again.";
        $step  = 1;
    } else {
        $stmt = $conn->prepare("SELECT user_id, security_question, security_answer_hash FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($answer, $user['security_answer_hash'])) {
            $token = bin2hex(random_bytes(32));
            $stmt = $conn->prepare("INSERT INTO PasswordResets (email, token) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $token);
            $stmt->execute();
            $stmt->close();

            unset($_SESSION['reset_email']);
            $reset_link = "https://cs4116group21.infinityfree.me/pages/reset_password.php?token=" . $token;
            $success = "Answer correct! Use the link below to reset your password.";
            $step = 3;
        } else {
            $security_question = $user['security_question'] ?? '';
            $error = "Incorrect answer. Please try again.";
            $step  = 2;
        }
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

                <?php if ($error): ?>
                    <p class="forgot-error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <?php if ($step === 1): ?>
                    <p class="forgot-desc">Enter your email address and we'll ask you your security question.</p>
                    <form action="/pages/forgot_password.php" method="POST">
                        <input type="email" name="email" class="forgot-text" placeholder="Email" required>
                        <button type="submit" class="sendresetlink-btn">Continue</button>
                    </form>

                <?php elseif ($step === 2): ?>
                    <p class="forgot-desc">Answer your security question to continue.</p>
                    <form action="/pages/forgot_password.php" method="POST">
                        <p class="forgot-question"><strong><?= htmlspecialchars($security_question) ?></strong></p>
                        <input type="text" name="security_answer" class="forgot-text" placeholder="Your answer" required autocomplete="off">
                        <button type="submit" class="sendresetlink-btn">Verify Answer</button>
                    </form>

                <?php elseif ($step === 3): ?>
                    <p class="forgot-success"><?= htmlspecialchars($success) ?></p>
                    <p class="forgot-desc">Your reset link:</p>
                    <a href="<?= htmlspecialchars($reset_link) ?>" class="forgot-link" aria-label="Password reset link">Click here to reset your password</a>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
