<?php
require_once 'config.php';
unset($_SESSION[$lab_name]);
header("Location: login.php");
exit;
