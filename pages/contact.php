<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user_id'] ?? null;

    if (empty($name) || empty($email) || empty($message)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("INSERT INTO ContactMessages (name, email, message, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $email, $message, $user_id);
        if ($stmt->execute()) {
            $success = "Your message has been sent!";
        } else {
            $error = "Something went wrong. Please try again.";
        }
        $stmt->close();
    }
}

$extra_css = '/assets/css/contact.css';

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../includes/header2.php';
} else {
    require_once __DIR__ . '/../includes/header1.php';
}
?>

<main class="container mt-5">
    <h2 class="contact-title">Contact Us</h2>
    <p class="contact-subtitle">Have questions or need assistance?</p>
    <p class="contact-subtitle">Reach out to us and we'll get back to you as soon as possible!</p>
    <hr>

    <div class="row mt-4 g-4">
        <div class="col-md-7">
            <div class="contact-form-card">
                <?php if ($success): ?>
                    <p class="contact-success"><?= htmlspecialchars($success) ?></p>
                <?php elseif ($error): ?>
                    <p class="error-msg"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <form action="/pages/contact.php" method="POST">
                    <div class="contact-field">
                        <label class="contact-label">Your Name:</label>
                        <input type="text" name="name" class="contact-input" placeholder="Your Name..." required>
                    </div>
                    <div class="contact-field">
                        <label class="contact-label">Your Email:</label>
                        <input type="email" name="email" class="contact-input" placeholder="Your Email..." required>
                    </div>
                    <div class="contact-field">
                        <label class="contact-label">Your Message:</label>
                        <textarea name="message" class="contact-textarea" placeholder="Type here..." required></textarea>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="contact-btn">Send Message</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4 offset-md-1">
            <div class="contact-info-card">
                <h5 class="contact-info-title">Contact Info:</h5>
                <p class="contact-info-text">support@mindlink.com</p>
                <p class="contact-info-text">(123)-456-7890</p>
                <p class="contact-info-text">Mon - Fri, 9AM - 5PM</p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>