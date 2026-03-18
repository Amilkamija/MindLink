<?php
$host     = "sql311.infinityfree.com";
$username = "if0_41345101";
$password = "group21database";
$database = "if0_41345101_mindlink_db";
$port     = 3306;

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>