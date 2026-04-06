<!--
Author: Maksims Gerkis
Description: Logout modal
Verison:1
-->
<?php
//Starts or resumes the Session
require_once __DIR__ . '/../config/session.php';
//Destroys the Current Session
session_destroy();
//Sends you back to the main Login Page
header("Location: /pages/login.php");

exit();
