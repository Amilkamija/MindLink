<?php
$host     = getenv('DB_HOST') ?: 'sql311.infinityfree.com';
$username = getenv('DB_USER') ?: 'if0_41345101';
$password = getenv('DB_PASS') ?: 'group21projectA';
$database = getenv('DB_NAME') ?: 'if0_41345101_mindlink_db';
$port     = (int)(getenv('DB_PORT') ?: 3306);

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Unable to connect to the database. Please try again later.");
}
?>
