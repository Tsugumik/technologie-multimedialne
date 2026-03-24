<?php
$lab_name = 'z15';
session_start();
unset($_SESSION[$lab_name]);
header("Location: login.php");
exit;
