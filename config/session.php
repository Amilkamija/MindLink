<?php
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 80) == 443
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

session_set_cookie_params([
    'lifetime' => 1800,
    'path'     => '/',
    'secure'   => $is_https,
    'httponly' => true
]);

function regenerateSessionId() {
    session_regenerate_id();
    $_SESSION["last_regeneration"] = time();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["last_regeneration"])) {
    regenerateSessionId();
} else {
    $interval = 60 * 30;
    if (time() - $_SESSION["last_regeneration"] >= $interval) {
        regenerateSessionId();
    }
}
