<?php require_once __DIR__ . '/../config/db.php'; ?>
<?php $extra_css = '/assets/css/contact.css'; ?>
<?php require_once __DIR__ . '/../includes/header1.php'; ?>

<main class="container mt-5">
    <h2 class="contact-title">Contact Us</h2>
    <p class="contact-subtitle">Have questions or need assistance?</p>
    <p class="contact-subtitle">Reach out to us and we'll get back to you as soon as possible!</p>
    <hr>

    <div class="row mt-4 g-4">
        <div class="col-md-7">
            <div class="contact-form-card">
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