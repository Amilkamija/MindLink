<?php
if (session_status() === PHP_SESSION_NONE){
    session_start();
}

if (!empty($_COOKIE['first_login']) && isset($_SESSION['user_id'])) {
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page !== 'profile.php') {
        header("Location: /pages/profile.php");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindLink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if (isset($extra_css)): ?>
    <link rel="stylesheet" href="<?= $extra_css ?>">
    <?php endif; ?>
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom">
    <div class="container-fluid px-4">

    <div class="d-flex gap-3 align-items-center">
        <a class="nav-link" href="/pages/contact.php">CONTACT US</a>
        <a class="nav-link" href="/pages/aboutus.php">ABOUT US</a>
    </div>

    <a class="navbar-logo mx-auto d-flex align-items-center gap-0" href="/index.php">
    MINDLINK <img src="/assets/img/mindlink_logo.png" alt="MindLink" height="25">
    </a>

    <div class="d-flex gap-3 align-items-center">
    <form class="d-flex" action="/pages/search.php" method="GET">
    <div class="search-bar d-flex align-items-center">
        <img src="/assets/img/search_icon.png" alt="Search" class="search-icon" height="16">
    <input type="text" name="q" class="search-input" placeholder="Search..." required>
        <button type="submit" class="search-btn"></button>
        </div>
    </form>
            <!-- Logout button that triggers the modal instead of logging out-->
            <button class  = "btn btn-logout px-4" data-bs-toggle="modal" data-bs-target =   "#logoutModal">
                LOG OUT
            </button>
    </div>

</div>
</nav>
<?php require_once __DIR__ . '/cookie_consent.php'; ?>

<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-3">
            <p>Are you sure you want to log out?</p>
            <div class="d-flex justify-content-center gap-3">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/pages/log_out.php" class="btn btn-dark">Log Out</a>
            </div>
        </div>
    </div>
</div>

<div class="d-flex">
<div class="sidebar border-end">
    <ul class="sidebar-nav" style="list-style: none; padding: 0; margin: 0;">
    <li class="nav-item">
        <a class="nav-link" href="/pages/home.php">Home</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="/pages/projects.php">Projects</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="/pages/profile.php">Profile</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="/pages/messages.php">Messages</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="/pages/matches.php">Matches</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="/pages/report.php">Report</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="/pages/settings.php">Settings</a>
    </li>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <li class="nav-item">
        <a class="nav-link" href="/pages/admin.php">Admin</a>
    </li>
    <?php endif; ?>
    </ul>
</div>
<div class="main-content">
