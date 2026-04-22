<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$error = '';

$security_questions = [
    "What was the name of your first pet?",
    "What is your mother's maiden name?",
    "What was the name of your primary school?",
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $security_question = $_POST['security_question'] ?? '';
    $security_answer = trim($_POST['security_answer'] ?? '');

    if (strlen($password) < 8 || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $error = "Password must be at least 8 characters and include at least 1 special character.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (!in_array($security_question, $security_questions)) {
        $error = "Please select a valid security question.";
    } elseif ($security_answer === '') {
        $error = "Please provide an answer to your security question.";
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
            $answer_hash = password_hash(strtolower($security_answer), PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO Users (email, password_hash, role, security_question, security_answer_hash) VALUES (?, ?, 'student', ?, ?)");
            $stmt->bind_param("ssss", $email, $password_hash, $security_question, $answer_hash);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['role'] = 'student';
                setcookie('first_login', '1', time() + 600, '/');
                header("Location: /pages/profile.php");
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
        <div class="col-md-6">
            <div class="signup-card">
                <h4 class="signup-title">SIGN UP</h4>
                <?php if ($error): ?>
                    <p class="error-msg"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <form action="/pages/sign_up.php" method="POST">
                    <input type="email" name="email" class="signup-text" placeholder="Email" required>
                    <input type="password" name="password" class="signup-text" placeholder="Password" required>
                    <input type="password" name="confirm_password" class="signup-text" placeholder="Confirm Password" required>
                    <select name="security_question" class="signup-text" required>
                        <option value="" disabled selected>Select a security question</option>
                        <?php foreach ($security_questions as $q): ?>
                            <option value="<?= htmlspecialchars($q) ?>"><?= htmlspecialchars($q) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="security_answer" class="signup-text" placeholder="Your answer" required autocomplete="off">
                    <p class="already-account">Already have an account? <a href="/pages/login.php">Log in</a></p>
                    <button type="submit" class="signup-btn">SIGN UP</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
