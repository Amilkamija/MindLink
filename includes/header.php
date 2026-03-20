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
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom">
  <div class="container-fluid px-4">


    <div class="d-flex gap-3 align-items-center">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a class="nav-link" href="/pages/dashboard.php">Home</a>
        <a class="nav-link" href="/pages/projects.php">Projects</a>
        <a class="nav-link" href="/pages/profile.php">Profile</a>
        <a class="nav-link" href="/pages/matches.php">Matches</a>
        <a class="nav-link" href="/pages/settings.php">Settings</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <a class="nav-link" href="/pages/admin.php">Admin</a>
        <?php endif; ?>
      <?php else: ?>
        <a class="nav-link" href="/pages/contact.php">CONTACT</a>
        <a class="nav-link" href="/pages/faq.php">FAQ</a>
      <?php endif; ?>
    </div>

    <!-- Mindlink + add Logo -->
    <a class="navbar-brand mx-auto" href="/index.php">MINDLINK</a>

    <!-- Login + SIgnin + Logout -->
    <div class="d-flex gap-3 align-items-center">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/pages/logout.php" class="btn btn-dark rounded-pill px-4">LOG OUT</a>
      <?php else: ?>
        <a class="nav-link" href="/pages/login.php">LOG IN</a>
        <a href="/pages/register.php" class="btn btn-signup">SIGN UP</a>
      <?php endif; ?>
    </div>

  </div>
</nav>