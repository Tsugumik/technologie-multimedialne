<?php
session_start();
if (!isset($_SESSION['z14_role'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['z14_role'] == 'admin') {
    header('Location: admin.php');
} elseif ($_SESSION['z14_role'] == 'coach') {
    header('Location: coach.php');
} elseif ($_SESSION['z14_role'] == 'pracownik') {
    header('Location: pracownik.php');
}
exit;
