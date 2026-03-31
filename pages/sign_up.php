<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email            = trim($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {

        $stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "An account with that email already exists!";
        } else {
            $stmt->close();
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO Users (email, password_hash, role) VALUES (?, ?, 'student')");
            $stmt->bind_param("ss", $email, $password_hash);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['role']    = 'user';
                header("Location: /pages/home.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>

<?php $extra_css = '/assets/css/sign_up.css'; ?>
<?php require_once __DIR__ . '/../includes/header1.php'; ?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="signup-card">
                <h4 class="signup-title">SIGN UP</h4>
                <?php if ($error): ?>
                    <p class="error-msg"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <form action="/pages/sign_up.php" method="POST">
                    <input type="email" name="email" class="signup-text" placeholder="Email" required>
                    <input type="password" name="password" class="signup-text" placeholder="Password" required>
                    <input type="password" name="confirm_password" class="signup-text" placeholder="Confirm Password" required>
                    <p class="already-account">Already have an account? <a href="/pages/login.php">Log in</a></p>
                    <button type="submit" class="signup-btn">SIGN UP</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>