<?php
$lab_name = 'z12';
$db_name = 'z12_db';
require_once '../shared/config.php';
require_once '../shared/auth.php';
require_once 'models/Measurement.php';

header('Content-Type: application/json');

$model = new Measurement($pdo);
$action = $_GET['action'] ?? 'latest';

if ($action === 'latest') {
    $latest = $model->getLatest();
    echo json_encode($latest ? $latest : null);
} elseif ($action === 'history') {
    $data = array_reverse($model->getAll());
    echo json_encode($data);
}
