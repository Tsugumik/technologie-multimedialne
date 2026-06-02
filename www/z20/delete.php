<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);
$current_user_id = $_SESSION[$lab_name]['user_id'];

if (!$id) {
    header("Location: index.php");
    exit;
}

if ($type === 'announcement') {
    // Check permission
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    $announcement = $stmt->fetch();

    if ($announcement && ($announcement['user_id'] == $current_user_id || isModerator())) {
        // Fetch all photo filenames first
        $stmt_p = $pdo->prepare("SELECT filename FROM photos WHERE announcement_id = ?");
        $stmt_p->execute([$id]);
        $photos = $stmt_p->fetchAll();

        // Delete physical files
        $uploads_dir = getUploadsDir();
        foreach ($photos as $photo) {
            $filePath = $uploads_dir . '/' . $photo['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Delete from DB (FK constraint will delete photos database records as well)
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Redirect back to index.php or admin.php depending on where we came from
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'admin.php') !== false) {
    header("Location: admin.php");
} else {
    header("Location: index.php");
}
exit;
?>
