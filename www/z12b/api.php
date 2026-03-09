<?php
$lab_name = 'z12b';
$db_name = 'matt125';
$db_host = '65.21.206.53';
$db_user = 'matt125';
$db_pass = '2064_1fd03b';
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
