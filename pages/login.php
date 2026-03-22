<?php $extra_css = '/assets/css/login.css'; ?>
<?php require_once __DIR__ . '/../includes/header1.php'; ?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="login-card">
                <h4 class="login-title">LOG IN</h4>
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