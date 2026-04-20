<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? null;
$current_user_id = $_SESSION[$lab_name]['user_id'];

if (!$id) {
    header("Location: index.php");
    exit;
}

switch ($type) {
    case 'photo':
        $stmt = $pdo->prepare("SELECT p.*, g.folder_id, u.username FROM photos p JOIN galleries g ON p.gallery_id = g.id JOIN users u ON g.user_id = u.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $photo = $stmt->fetch();

        if ($photo && ($photo['user_id'] == $current_user_id || isModerator())) {
            // Delete file
            $filePath = getGalleryDir($photo['username'], $photo['folder_id']) . '/' . $photo['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            // Delete from DB
            $stmt = $pdo->prepare("DELETE FROM photos WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: index.php?gallery_id=" . ($_GET['gallery_id'] ?? ''));
        }
        break;

    case 'gallery':
        $stmt = $pdo->prepare("SELECT g.*, u.username FROM galleries g JOIN users u ON g.user_id = u.id WHERE g.id = ?");
        $stmt->execute([$id]);
        $gallery = $stmt->fetch();

        if ($gallery && ($gallery['user_id'] == $current_user_id || isModerator())) {
            // Delete folder and its contents
            $dir = getGalleryDir($gallery['username'], $gallery['folder_id']);
            if (file_exists($dir)) {
                $files = array_diff(scandir($dir), array('.', '..'));
                foreach ($files as $file) {
                    unlink($dir . '/' . $file);
                }
                rmdir($dir);
            }
            // Delete from DB
            $stmt = $pdo->prepare("DELETE FROM galleries WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: index.php");
        }
        break;

    case 'comment':
        $stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
        $stmt->execute([$id]);
        $comment = $stmt->fetch();

        if ($comment && ($comment['user_id'] == $current_user_id || isModerator())) {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: gallery.php?photo_id=" . ($_GET['photo_id'] ?? ''));
        }
        break;
}

header("Location: index.php");
exit;
