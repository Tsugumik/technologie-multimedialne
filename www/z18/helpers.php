<?php

function logLogin($pdo, $user_id, $username, $success) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, username, ip, success) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $username, $ip, $success ? 1 : 0]);
}

function getUserDir($username) {
    $dir = __DIR__ . '/uploads/' . $username;
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

function getGalleryDir($username, $folder_id) {
    $dir = getUserDir($username) . '/' . $folder_id;
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

function applyFilter($imagePath, $filter) {
    if ($filter === 'none') return;
    if (!function_exists('imagecreatefromjpeg')) return; // GD not installed

    $info = getimagesize($imagePath);
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg': $img = @imagecreatefromjpeg($imagePath); break;
        case 'image/png': $img = @imagecreatefrompng($imagePath); break;
        case 'image/gif': $img = @imagecreatefromgif($imagePath); break;
        default: return;
    }

    if (!$img) return;

    switch ($filter) {
        case 'greyscale':
            imagefilter($img, IMG_FILTER_GRAYSCALE);
            break;
        case 'sepia':
            imagefilter($img, IMG_FILTER_GRAYSCALE);
            imagefilter($img, IMG_FILTER_COLORIZE, 90, 60, 40);
            break;
        case 'negative':
            imagefilter($img, IMG_FILTER_NEGATE);
            break;
        case 'brightness':
            imagefilter($img, IMG_FILTER_BRIGHTNESS, 50);
            break;
        case 'contrast':
            imagefilter($img, IMG_FILTER_CONTRAST, -50);
            break;
    }

    switch ($mime) {
        case 'image/jpeg': imagejpeg($img, $imagePath); break;
        case 'image/png': imagepng($img, $imagePath); break;
        case 'image/gif': imagegif($img, $imagePath); break;
    }

    imagedestroy($img);
}

function addWatermark($imagePath) {
    if (!function_exists('imagecreatefromjpeg')) return; // GD not installed

    $info = getimagesize($imagePath);
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg': $img = @imagecreatefromjpeg($imagePath); break;
        case 'image/png': $img = @imagecreatefrompng($imagePath); break;
        case 'image/gif': $img = @imagecreatefromgif($imagePath); break;
        default: return;
    }

    if (!$img) return;

    $width = imagesx($img);
    $height = imagesy($img);

    $text = "Copyright-restricted";
    $font = 5; // Internal GD font
    $font_width = imagefontwidth($font) * strlen($text);
    $font_height = imagefontheight($font);

    $x = ($width - $font_width) / 2;
    $y = ($height - $font_height) / 2;

    $color = imagecolorallocatealpha($img, 255, 255, 255, 60);
    $shadow = imagecolorallocatealpha($img, 0, 0, 0, 60);

    imagestring($img, $font, $x+1, $y+1, $text, $shadow);
    imagestring($img, $font, $x, $y, $text, $color);

    switch ($mime) {
        case 'image/jpeg': imagejpeg($img, $imagePath); break;
        case 'image/png': imagepng($img, $imagePath); break;
        case 'image/gif': imagegif($img, $imagePath); break;
    }

    imagedestroy($img);
}

function getAverageRating($pdo, $photo_id) {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg FROM ratings WHERE photo_id = ?");
    $stmt->execute([$photo_id]);
    $res = $stmt->fetch();
    return $res['avg'] ? round($res['avg'], 1) : 0;
}

function getUserRating($pdo, $photo_id, $user_id) {
    $stmt = $pdo->prepare("SELECT rating FROM ratings WHERE photo_id = ? AND user_id = ?");
    $stmt->execute([$photo_id, $user_id]);
    $res = $stmt->fetch();
    return $res ? $res['rating'] : 0;
}
?>
