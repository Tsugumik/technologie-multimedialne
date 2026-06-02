<?php

function logLogin($pdo, $user_id, $username, $success) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, username, ip, success) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $username, $ip, $success ? 1 : 0]);
}

function getUploadsDir() {
    $dir = __DIR__ . '/uploads';
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

function addWatermark($imagePath) {
    if (!function_exists('imagecreatefromjpeg')) return; // GD not installed or enabled

    $info = getimagesize($imagePath);
    if (!$info) return;
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

    $text = "Portal Ogłoszeniowy GIS";
    $font = 5; // Internal GD font
    $font_width = imagefontwidth($font) * strlen($text);
    $font_height = imagefontheight($font);

    $x = (int)(($width - $font_width) / 2);
    $y = (int)(($height - $font_height) / 2);

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

function getCategoryName($cat) {
    switch ($cat) {
        case 'mieszkanie': return 'Mieszkanie';
        case 'dom': return 'Dom';
        case 'dzialka_budowlana': return 'Działka budowlana';
        case 'dzialka_rod': return 'Działka ROD';
        default: return htmlspecialchars($cat);
    }
}

function getTypeName($type) {
    return $type === 'sprzedaz' ? 'Sprzedaż' : 'Wynajem';
}
?>
