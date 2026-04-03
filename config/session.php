<?php

ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_set_cookie_params([
    'lifetime' => 1800,
    'domain'   => 'cs4116group21.infinityfree.me',
    'path'     => '/',
    'secure'   => true, //enfoces HTTPS 
    'httponly' => true //Prevents JS cookie theft
]);

function regenerate_session_id() {
    session_regenerate_id();
    $_SESSION["last_regeneration"] = time();
}

session_start();

if (!isset($_SESSION["last_regeneration"])) {
    regenerate_session_id();
} else {
    $interval = 60 * 30;
    if (time() - $_SESSION["last_regeneration"] >= $interval) {
        regenerate_session_id();
    }
}
