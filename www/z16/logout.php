<?php
session_start();
$lab_name = 'z16';
unset($_SESSION[$lab_name]);
header("Location: index.php");
exit;
