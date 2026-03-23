<?php require_once __DIR__ . '/../includes/header2.php'; ?>
<?php $extra_css = '/assets/css/shakeebap.css'; ?>
<?php
$pageTitle = "Messages - MindLink";
?>

<h2 class="page-title">Messages</h2>

<div class="row">
    <div class="col-md-4">
        <div class="section-box">
            <h5>Chats</h5>
            <ul class="list-group">
                <li class="list-group-item">Ali - Project Discussion</li>
                <li class="list-group-item">Sara - Team Match</li>
                <li class="list-group-item">John - Collaboration Request</li>
            </ul>
        </div>
    </div>

    <div class="col-md-8">
        <div class="section-box">
            <h5>Conversation</h5>
            <div class="border rounded p-3 mb-3" style="height: 300px; overflow-y: auto;">
                <p><strong>Ali:</strong> Hi, are you available for the project meeting?</p>
                <p><strong>You:</strong> Yes, I am available this evening.</p>
            </div>

            <form method="POST" action="">
                <div class="mb-3">
                    <textarea name="message" class="form-control" rows="3" placeholder="Type your message..."></textarea>
                </div>
                <button type="submit" class="btn btn-custom">Send Message</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>