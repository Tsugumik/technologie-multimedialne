<?php
$lab_name = 'z18';
$lab_title = 'Zadanie 18 - Photo Gallery';
$db_name = 'z18';

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

// Helper to check if user is moderator
function isModerator() {
    global $lab_name;
    return isset($_SESSION[$lab_name]['role']) && ($_SESSION[$lab_name]['role'] === 'moderator' || $_SESSION[$lab_name]['role'] === 'admin');
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
