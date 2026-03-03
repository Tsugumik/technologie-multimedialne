<?php
session_start();
if (!empty($lab_name) && is_string($lab_name) && isset($_SESSION[$lab_name])) {
    unset($_SESSION[$lab_name]);
}
header("Location: login.php");
exit;
?>
