<<<<<<< HEAD
<?php require_once __DIR__ . '/../includes/header2.php'; ?>
<?php $extra_css = '/assets/css/shakeeba.css'; ?>
=======
>>>>>>> e7302d8 (Finalize Messages, Matches, Project Details, and Settings pages with updated responsive CSS)
<?php
$pageTitle = "Messages - MindLink";
$extra_css = '/assets/css/messages.css'; 
require_once __DIR__ . '/../includes/header2.php';
?>

<h2 class="page-title mb-4">Messages</h2>

<div class="messages-layout">

    <!-- Chat list column -->
    <div class="chat-list-panel">
        <div class="chat-list-title">Messages</div>

        <div class="search-box mb-3">
            <input type="text" class="form-control form-control-sm" placeholder="Search messages...">
        </div>

        <h6 class="fw-bold small text-uppercase text-olive mb-2">Project Groups</h6>

        <div class="chat-item">
            <div class="chat-avatar"></div>
            <div>
                <div class="fw-bold">CS4084 - Mobile App</div>
                <div class="chat-preview">John: I've updated the...</div>
                <div class="text-muted small">11:15 PM</div>
            </div>
        </div>

        <div class="chat-item">
            <div class="chat-avatar"></div>
            <div>
                <div class="fw-bold">CS4116 -Software</div>
                <div class="chat-preview">Jane: No problem...</div>
                <div class="text-muted small">4:25 PM</div>
            </div>
        </div>

        <h6 class="fw-bold small text-uppercase text-olive mt-3 mb-2">Direct Messages</h6>

        <div class="chat-item">
            <div class="chat-avatar"></div>
            <div>
                <div class="fw-bold">Sam Smith</div>
                <div class="chat-preview">Yes, let’s meet up!</div>
                <div class="text-muted small">11:00 AM</div>
            </div>
        </div>
    </div>

    <!-- Chat panel column -->
    <div class="chat-panel">
        <div class="chat-header d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0 fw-bold">CS4084 – Mobile App Dev</h5>
            <div>
                <button class="btn btn-sm btn-outline-olive">View Members</button>
                <button class="btn btn-sm btn-outline-olive">Report</button>
            </div>
        </div>

        <div class="chat-box">
            <div class="message-thread">
                <div class="message-row">
                    <div class="message-bubble">Are we still meeting at 2PM?</div>
                    <div class="message-meta">Sarah · 10:15 AM</div>
                </div>

                <div class="message-row message-right">
                    <div class="message-bubble">Yes, I’ll be there!</div>
                    <div class="message-meta">You · 10:55 AM</div>
                </div>

                <div class="message-row">
                    <div class="message-bubble">Fantastic, see you then!</div>
                    <div class="message-meta">Sarah · 11:15 AM</div>
                </div>

                <div class="message-row">
                    <div class="message-bubble">I’ve updated the Word doc due for today!</div>
                    <div class="message-meta">John · 1:30 PM</div>
                </div>
            </div>

            <div class="message-input-wrap">
                <input type="text" class="form-control" placeholder="Type a message...">
                <button class="btn btn-olive px-4">Send</button>
            </div>
        </div>
    </div>

</div>

<<<<<<< HEAD
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
=======
<?php include __DIR__ . '/../includes/footer.php'; ?>
>>>>>>> e7302d8 (Finalize Messages, Matches, Project Details, and Settings pages with updated responsive CSS)
