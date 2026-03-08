<?php
$lab_name = 'z12';
$db_name = 'z12_db';
require_once '../shared/config.php';
require_once '../shared/auth.php';
require_once 'models/Visitor.php';

header('Content-Type: application/json');

$model = new Visitor($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $res = $data['resolution'] ?? 'unknown';
    $color = $data['color_depth'] ?? 0;
    $cookies = !empty($data['cookies_enabled']) ? 1 : 0;
    $lat = $data['latitude'] ?? null;
    $lng = $data['longitude'] ?? null;

    $model->save($ip, $ua, $res, $color, $cookies, $lat, $lng);
    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
