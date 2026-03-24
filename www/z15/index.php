<?php
$lab_name = 'z15';
$lab_title = 'Zadanie 15 - CRM';
$db_name = 'z15';
require_once '../shared/auth.php';

$role = $_SESSION[$lab_name]['role'];

if ($role === 'client') {
    require_once 'client.php';
} elseif ($role === 'employee') {
    require_once 'employee.php';
} elseif ($role === 'admin') {
    require_once 'admin.php';
} else {
    die("Nieznana rola.");
}
