<?php require_once __DIR__ . '/../config/db.php'; ?>
<?php $extra_css = '/assets/css/forgot_password.css'; ?>
<?php require_once __DIR__ . '/../includes/header1.php'; ?>

<main class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="forgot-card">
                <h4 class="forgot-title">Forgot Password?</h4>
                <p class="forgot-desc">Enter your email address and we'll send you a link to reset your password.</p>
                <form action="/pages/forgot_password.php" method="POST">
                    <input type="email" name="email" class="forgot-text" placeholder="Email" required>
                    <button type="submit" class="sendresetlink-btn">Send reset link</button>
                </form>
            </div>
        </div>
    </div>
</main>
<!-- maaybe add a pop up message whenever you click on the send reset link button that says "If an account with that email exists, a reset link has been sent" -->  
<?php require_once __DIR__ . '/../includes/footer.php'; ?>