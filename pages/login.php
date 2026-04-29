<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // track failed attempts per IP + email over a 30-min window
    $ip = $_SERVER['REMOTE_ADDR'];
    define('DB_DATETIME_FMT', 'Y-m-d H:i:s');
    $window = date(DB_DATETIME_FMT, time() - 1800);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM LoginAttempts WHERE ip_address = ? AND email = ? AND attempt_time > ?");
    $stmt->bind_param("sss", $ip, $email, $window);
    $stmt->execute();
    $stmt->bind_result($attempt_count);
    $stmt->fetch();
    $stmt->close();

    if ($attempt_count >= 3) {
        $error = "Too many failed login attempts. Please try again in 30 minutes.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, email, password_hash, role FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password_hash'])) {
                // successful login :clear any recorded attempts for this IP + email
                $stmt2 = $conn->prepare("DELETE FROM LoginAttempts WHERE ip_address = ? AND email = ?");
                $stmt2->bind_param("ss", $ip, $email);
                $stmt2->execute();
                $stmt2->close();

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                header("Location: /pages/home.php");
                exit();
            } else {
                // wrong password :record the failed attempt
                $now = date(DB_DATETIME_FMT);
                $stmt2 = $conn->prepare("INSERT INTO LoginAttempts (ip_address, email, attempt_time) VALUES (?, ?, ?)");
                $stmt2->bind_param("sss", $ip, $email, $now);
                $stmt2->execute();
                $stmt2->close();
                $error = "Invalid email or password.";
            }
        } else {
            // email not found, still record attempt to prevent email 
            $now = date(DB_DATETIME_FMT);
            $stmt2 = $conn->prepare("INSERT INTO LoginAttempts (ip_address, email, attempt_time) VALUES (?, ?, ?)");
            $stmt2->bind_param("sss", $ip, $email, $now);
            $stmt2->execute();
            $stmt2->close();
            $error = "Invalid email or password.";
        }
        $stmt->close();
    }
}
?>

<?php $extra_css = '/assets/css/login.css'; ?>
<?php require_once __DIR__ . '/../includes/header1.php'; ?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="login-card">
                <h4 class="login-title">LOG IN</h4>
                <?php if ($error): ?>
                    <p class="error-msg"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <form action="/pages/login.php" method="POST">
                    <input type="email" name="email" class="login-text" placeholder="Email" required>
                    <input type="password" name="password" class="login-text" placeholder="Password" required>
                    <p class="forgot-password"><a class="signup" href="/pages/forgot_password.php">Forgot password?</a></p>
                    <button type="submit" class="login-btn">LOG IN</button>
                    <p class="no-account">Don't have an account? <a class="signup" href="/pages/sign_up.php">Sign up</a></p>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
