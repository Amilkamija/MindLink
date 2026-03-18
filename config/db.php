<?php
$host     = getenv('DB_HOST') ?: 'sql311.infinityfree.com';
$username = getenv('DB_USER') ?: 'if0_41345101';
$password = getenv('DB_PASS') ?: 'group21database';
$database = getenv('DB_NAME') ?: 'if0_41345101_mindlink_db';
$port     = (int)(getenv('DB_PORT') ?: 3306);

$conn = new mysqli("sql311.infinityfree.com", "if0_41345101", "group21database", "if0_41345101_mindlink_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
