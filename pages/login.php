<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];


    $stmt = $conn->prepare("SELECT user_id, email, password_hash, role FROM Users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            header("Location: /pages/home.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
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
