<?php
$lab_name = 'z17';
$lab_title = 'Zadanie 17 - Forum';
$db_name = 'z17';

require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper to check if user is admin
function isAdmin() {
    global $lab_name;
    return isset($_SESSION[$lab_name]['role']) && $_SESSION[$lab_name]['role'] === 'admin';
}

// Helper to check if user is logged in
function isLoggedIn() {
    global $lab_name;
    return isset($_SESSION[$lab_name]['user_id']);
}

// Check if banned
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT is_banned FROM users WHERE id = ?");
    $stmt->execute([$_SESSION[$lab_name]['user_id']]);
    $u = $stmt->fetch();
    if ($u && $u['is_banned']) {
        session_destroy();
        header("Location: login.php?error=Twoje konto zostało zablokowane.");
        exit;
    }
}
?>
