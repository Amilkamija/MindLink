<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$extra_css = '/assets/css/index.css';

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/includes/header2.php';
} else {
    require_once __DIR__ . '/includes/header1.php';
}
?>


<main class="container mt-5">
    <br>
    <h2 class="title">Stop gambling on groupmates, find a team that actually works!</h2>
    <h2 class="subtitle">and maybe something more...</h2>
    <br>
    <h6 class="paragraph">Your Gpa shouldn't depend on luck.</h6>
    <br>
    <h6 class="paragraph">Use MindLink! Connect with partners who are as serious about the project as you are.</h6>
    <h6 class="paragraph">And when the work is done, you might just find a connection worth keeping.</h6>
    <br>
    <hr>
    
    <div class="row mt-5 g-3">
        <div class="col">
            <div class="card card-1 h-100 p-3">
                <span class="card-title">BROWSE PROJECTS</span>
                <p>Looking for a group? See active project posting in your course that need your specific skills</p>
            </div>
        </div>
        <div class="col">
            <div class="card card-2 h-100 p-3">
                <span class="card-title">FIND TEAMMATES</span>
                <p>Need a partner? Browse student profiles by tech stack, availability, and academic year</p>
            </div>
        </div>
        <div class="col">
            <div class="card card-3 h-100 p-3">
                <span class="card-title">POST AN IDEA</span>
                <p>Got a final year project? Create a posting, define roles, and let the right partners find you</p>
            </div>
        </div>
        <div class="col">
            <div class="card card-4 h-100 p-3">
                <span class="card-title">POST-PROJECT CHEMISTRY</span>
                <p>Finished your assignment? Reflect on the connection and decide if you'd like to pursue something more.</p>
            </div>
        </div>
    </div>

<br>
<br>
<br>
    <div class="text-center mt-4">
        <a href="/pages/sign_up.php" class="btn btn-signup px-4 py-2">Get Started</a>
    </div>
<br>
<br>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
