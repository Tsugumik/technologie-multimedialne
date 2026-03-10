<?php
$lab_name = 'z13';
$db_name = 'z13_db';
$lab_title = 'Zadanie 13 - ToDo';

require_once '../shared/auth.php';
require_once '../shared/config.php';

$user_id = $_SESSION['z13']['user_id'];
$username = $_SESSION['z13']['username'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_task') {
        $nazwa_zadania = trim($_POST['nazwa_zadania'] ?? '');
        if ($nazwa_zadania) {
            $stmt = $pdo->prepare("INSERT INTO zadanie (idp, nazwa_zadania) VALUES (?, ?)");
            $stmt->execute([$user_id, $nazwa_zadania]);
        }
    } elseif ($action === 'add_subtask') {
        $idz = $_POST['idz'] ?? 0;
        $idp = $_POST['idp'] ?? 0;
        $nazwa_podzadania = trim($_POST['nazwa_podzadania'] ?? '');
        
        // Verify ownership
        $stmt = $pdo->prepare("SELECT idz FROM zadanie WHERE idz = ? AND idp = ?");
        $stmt->execute([$idz, $user_id]);
        if ($stmt->fetch() && $nazwa_podzadania && $idp) {
            $stmtIns = $pdo->prepare("INSERT INTO podzadanie (idz, idp, nazwa_podzadania, stan) VALUES (?, ?, ?, 0)");
            $stmtIns->execute([$idz, $idp, $nazwa_podzadania]);
        }
    } elseif ($action === 'delete_subtask') {
        $idpz = $_POST['idpz'] ?? 0;
        $stmt = $pdo->prepare("
            SELECT z.idp FROM podzadanie p 
            JOIN zadanie z ON p.idz = z.idz 
            WHERE p.idpz = ?
        ");
        $stmt->execute([$idpz]);
        $task = $stmt->fetch();
        if ($task && $task['idp'] == $user_id) {
            $stmtDel = $pdo->prepare("DELETE FROM podzadanie WHERE idpz = ?");
            $stmtDel->execute([$idpz]);
        }
    } elseif ($action === 'delete_task') {
        $idz = $_POST['idz'] ?? 0;
        $stmtDel = $pdo->prepare("DELETE FROM zadanie WHERE idz = ? AND idp = ?");
        $stmtDel->execute([$idz, $user_id]);
    } elseif ($action === 'send_monit') {
        $idp_do = $_POST['idp_do'] ?? 0;
        $nazwa_podzadania = $_POST['nazwa_podzadania'] ?? '';
        $tresc = "Proszę o przyspieszenie prac nad podzadaniem: " . $nazwa_podzadania;
        if ($idp_do) {
            $stmt = $pdo->prepare("INSERT INTO monit (idp_od, idp_do, tresc, data) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, $idp_do, $tresc]);
        }
    }
    
    header("Location: index.php");
    exit;
}

// Fetch employees for select boxes
$pracownicy = $pdo->query("SELECT idp, login FROM pracownik WHERE login != 'admin'")->fetchAll();

require_once '../shared/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Witaj, <?= htmlspecialchars($username) ?></h2>
    </div>

    <?php if ($username === 'admin'): ?>
        <?php include 'views/admin.php'; ?>
    <?php else: ?>
        <?php include 'views/pracownik.php'; ?>
    <?php endif; ?>
</div>

<?php require_once '../shared/footer.php'; ?>
