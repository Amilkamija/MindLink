​<?php
require_once __DIR__ . '/../config/session.php';
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
      <a class="nav-link" href="/pages/login.php">LOG IN</a>
      <a href="/pages/sign_up.php" class="btn btn-signup">SIGN UP</a>
    </div>

  </div>
</nav>
<?php require_once __DIR__ . '/cookie_consent.php'; ?>
