<?php
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}
$extra_css = '/assets/css/home.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<h2 class="home-title">Welcome to <span class="home-title-green">MindLink!</span></h2>
<p class="home-subtitle">We are excited that you've joined our academic collaboration community.</p>
<p class="home-subtitle">MindLink helps you connect with students based on skills,interests, and project goals!</p>
<p class="home-subtitle">So you can build stronger teams and achieve better results</p>

<hr class="home-divider">

<div class="home-cards-row">
    <div class="home-card home-card-dark">
        <span class="home-card-badge">1.Complete Your Profile</span>
        <p>Before matching with others, tell us about yourself.</p>
        <ul>
            <li>Your course</li>
            <li>Your skills and strengths</li>
            <li>Availability</li>
        </ul>
    </div>
    <div class="home-card home-card-light">
        <span class="home-card-badge">2.Explore / Create Projects</span>
        <p>Looking to join a team? Browse available projects. Have an idea? Create your own project and define roles, skills needed, and deadlines</p>
    </div>
    <div class="home-card home-card-mid">
        <span class="home-card-badge">3.Find Your Matches</span>
        <p>Discover a students who match your skills and interests. Apply to projects or review incoming applications</p>
    </div>
</div>

<hr class="home-divider">

<div class="home-cards-row home-cards-row-bottom">
    <div class="home-card home-card-muted">
        <span class="home-card-badge">4.Stay Connected</span>
        <p>Once matched, you can message teammates directly to coordinate tasks and deadlines</p>
    </div>
    <div class="home-card home-card-dark2">
        <span class="home-card-badge">5.Strengthen Your Connection!</span>
        <p>Once a project is finished you can choose to match with one of your teammates and see if the connection is worth keeping!</p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>