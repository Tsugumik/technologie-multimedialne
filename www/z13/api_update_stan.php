<?php
$lab_name = 'z13';
$db_name = 'z13_db';
require_once '../shared/auth.php';
require_once '../shared/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['z13']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['z13']['user_id'];
$idpz = $_POST['idpz'] ?? 0;
$stan = $_POST['stan'] ?? 0;

if (!$idpz) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Ensure user is the manager of the task this subtask belongs to OR the executor of the subtask
$stmt = $pdo->prepare("
    SELECT z.idp as manager_id, p.idp as wykonawca_id, p.idz 
    FROM podzadanie p 
    JOIN zadanie z ON p.idz = z.idz 
    WHERE p.idpz = ?
");
$stmt->execute([$idpz]);
$task = $stmt->fetch();

if ($task && ($task['manager_id'] == $user_id || $task['wykonawca_id'] == $user_id)) {
    $stmtUpd = $pdo->prepare("UPDATE podzadanie SET stan = ? WHERE idpz = ?");
    if ($stmtUpd->execute([$stan, $idpz])) {
        // Oblicz nową średnią dla całego zadania
        $stmtAvg = $pdo->prepare("SELECT AVG(stan) as avg_stan FROM podzadanie WHERE idz = ?");
        $stmtAvg->execute([$task['idz']]);
        $avg_result = $stmtAvg->fetch();
        $avg_stan = $avg_result ? round($avg_result['avg_stan']) : 0;
        
        echo json_encode([
            'success' => true, 
            'stan' => $stan, 
            'avg_stan' => $avg_stan,
            'idz' => $task['idz']
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Operation failed']);
