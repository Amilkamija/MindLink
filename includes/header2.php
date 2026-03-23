<?php 
if (session_status() === PHP_SESSION_NONE){
session_start();
} ?>

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
        <a class="nav-link" href="/pages/contact.php">CONTACT</a>
        <a class="nav-link" href="/pages/faq.php">FAQ</a>
    </div>

    <a class="navbar-logo mx-auto d-flex align-items-center gap-0" href="/index.php">
    MINDLINK <img src="/assets/img/mindlink_logo.png" alt="MindLink" height="25">
    </a>

    <div class="d-flex gap-3 align-items-center">
    <form class="d-flex" action="/pages/search.php" method="GET">
    <div class="search-bar d-flex align-items-center">
        <img src="/assets/img/search_icon.png" alt="Search" class="search-icon">
        <input type="text" name="q" class="search-input" placeholder="SEARCH">
        <button type="submit" class="search-btn"></button>
        </div>
    </form>
    <a href="/pages/logout.php" class="btn btn-logout px-4">LOG OUT</a>
    </div>

</div>
</nav>