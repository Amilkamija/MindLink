<?php $extra_css = '/assets/css/sign_up.css'; ?>
<?php require_once __DIR__ . '/../includes/header1.php'; ?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="signup-card">
                <h4 class="signup-title">SIGN UP</h4>
                <form action="/pages/sign_up.php" method="POST">
                    <input type="text" name="name" class="signup-text" placeholder="Name" required>
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