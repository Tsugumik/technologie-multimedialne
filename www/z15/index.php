<?php
$lab_name = 'z15';
$lab_title = 'Zadanie 15 - CRM';
$db_name = 'z15';
require_once '../shared/auth.php';

$role = $_SESSION[$lab_name]['role'];

if ($role === 'client') {
    header("Location: client.php");
} elseif ($role === 'employee') {
    header("Location: employee.php");
} elseif ($role === 'admin') {
    header("Location: admin.php");
} else {
    die("Nieznana rola.");
}
exit;
