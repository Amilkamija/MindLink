<?php
$pageTitle = "Messages - MindLink";
$extra_css = '/assets/css/messages.css';
require_once __DIR__ . '/../includes/header2.php';
?>
<div class="messages-layout">

    <!-- Left side chat list -->
    <div class="chat-list-panel">
        <div class="chat-list-title">Messages</div>

        <div class="search-box mb-3">
            <input type="text" class="form-control form-control-sm" placeholder="Search messages...">
            <svg class="search-icon" xmlns="[w3.org](http://www.w3.org/2000/svg)" fill="none" viewBox="0 0 24 24" stroke="#626a42" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </div>

        <!-- Project Groups -->
        <h6 class="fw-bold small text-uppercase text-olive mb-2">Project Groups</h6>

        <div class="chat-item">
            <div class="chat-avatar">
                <svg viewBox="0 0 24 24" stroke="#3d4a24" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="7" r="4"></circle>
                    <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
                </svg>
            </div>
            <div class="chat-item-content">
                <div class="chat-name">CS4084 - Mobile App</div>
                <div class="chat-preview">John: I’ve updated the document for today...</div>
                <div class="chat-time">11:15 PM</div>
            </div>
        </div>

        <div class="chat-divider"></div>

        <div class="chat-item">
            <div class="chat-avatar">
                <svg viewBox="0 0 24 24" stroke="#3d4a24" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="7" r="4"></circle>
                    <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
                </svg>
            </div>
            <div class="chat-item-content">
                <div class="chat-name">CS4116 - Software Development</div>
                <div class="chat-preview">Jane: No problem, I’ll check it later...</div>
                <div class="chat-time">4:25 PM</div>
            </div>
        </div>

        <div class="chat-section-separator"></div>

        <!-- Direct Messages -->
        <h6 class="fw-bold small text-uppercase text-olive mt-3 mb-2">Direct Messages</h6>

        <div class="chat-item">
            <div class="chat-avatar">
                <svg viewBox="0 0 24 24" stroke="#3d4a24" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="7" r="4"></circle>
                    <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
                </svg>
            </div>
            <div class="chat-item-content">
                <div class="chat-name">Sam Smith</div>
                <div class="chat-preview">Yes, let’s meet up!</div>
                <div class="chat-time">11:00 AM</div>
            </div>
        </div>

        <div class="chat-divider"></div>

        <div class="chat-item">
            <div class="chat-avatar">
                <svg viewBox="0 0 24 24" stroke="#3d4a24" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="7" r="4"></circle>
                    <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
                </svg>
            </div>
            <div class="chat-item-content">
                <div class="chat-name">Jane Smith</div>
                <div class="chat-preview">Hey, did you finish the updates?</div>
                <div class="chat-time">6:45 AM</div>
            </div>
        </div>
    </div>

    <!-- Right side chat panel -->
    <div class="chat-panel">
        <div class="chat-header">
            <h5>CS4084 – Mobile App Dev</h5>
            <div class="chat-header-actions">
                <a href="/pages/team_members.php" class="btn-outline-olive">View Members</a>
                <a href="/pages/report.php" class="btn-outline-olive">Report</a>
            </div>
        </div>

        <div class="chat-box">
            <div class="message-thread">
                <div class="message-row">
                    <div class="message-left-wrap">
                        <div class="chat-avatar">
                            <svg viewBox="0 0 24 24" stroke="#3d4a24" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="7" r="4"></circle>
                                <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="message-bubble">Are we still meeting at 2PM?</div>
                            <div class="message-meta">Sarah · 10:15 AM</div>
                        </div>
                    </div>
                </div>

                <div class="message-row message-right">
                    <div class="message-bubble">Yes, I’ll be there!</div>
                    <div class="message-meta">You · 10:55 AM</div>
                </div>

                <div class="message-row">
                    <div class="message-left-wrap">
                        <div class="chat-avatar">
                            <svg viewBox="0 0 24 24" stroke="#3d4a24" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="7" r="4"></circle>
                                <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="message-bubble">Fantastic, see you then!</div>
                            <div class="message-meta">Sarah · 11:15 AM</div>
                        </div>
                    </div>
                </div>

                <div class="message-row">
                    <div class="message-left-wrap">
                        <div class="chat-avatar">
                            <svg viewBox="0 0 24 24" stroke="#3d4a24" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="7" r="4"></circle>
                                <path d="M5 20c0-4 4-6 7-6s7 2 7 6"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="message-bubble">I’ve updated the Word doc due for today!</div>
                            <div class="message-meta">John · 1:30 PM</div>
                        </div>
                    </div>
                </div>
            </div>

            <form class="message-input-wrap" method="POST" action="">
                <input type="text" placeholder="Type a message..." name="message">
                <button type="submit" class="btn-olive">Send</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>