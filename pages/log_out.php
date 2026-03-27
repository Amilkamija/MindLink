<?php
//Starts or resumes the Session
session_start();
//Destroys the Current Session
session_destroy();
//Sends you back to the main Login Page
header("Location: /pages/login.php");

exit();
