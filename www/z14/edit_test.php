<?php
session_start();
$db_name = 'z14';
require_once '../shared/config.php';

if (!isset($_SESSION['z14_role']) || $_SESSION['z14_role'] !== 'coach') {
    header('Location: login.php');
    exit;
}

$idc = $_SESSION['z14_user'];
$idt = $_GET['id'] ?? null;

if (!$idt) {
    header('Location: coach.php');
    exit;
}

$test = $pdo->prepare('SELECT * FROM test WHERE idt = ? AND idc = ?');
$test->execute([$idt, $idc]);
$test = $test->fetch();

if (!$test) {
    die("Brak dostępu lub test nie istnieje.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    $tresc = $_POST['tresc_pytania'];
    $odp_a = $_POST['odpowiedz_a'];
    $odp_b = $_POST['odpowiedz_b'];
    $odp_c = $_POST['odpowiedz_c'];
    $odp_d = $_POST['odpowiedz_d'];
    $a = isset($_POST['a']) ? 1 : 0;
    $b = isset($_POST['b']) ? 1 : 0;
    $c = isset($_POST['c']) ? 1 : 0;
    $d = isset($_POST['d']) ? 1 : 0;
    
    $plik = '';
    if (!empty($_FILES['plik']['name']) && $_FILES['plik']['error'] === UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/testy/' . $idt;
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $plik = 'testy/' . $idt . '/' . time() . '_' . basename($_FILES['plik']['name']);
        move_uploaded_file($_FILES['plik']['tmp_name'], __DIR__ . '/' . $plik);
    }
    
    $stmt = $pdo->prepare('INSERT INTO pytania (idt, tresc_pytania, odpowiedz_a, odpowiedz_b, odpowiedz_c, odpowiedz_d, a, b, c, d, plik_multimedialny) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$idt, $tresc, $odp_a, $odp_b, $odp_c, $odp_d, $a, $b, $c, $d, $plik]);
    header("Location: edit_test.php?id=$idt");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_question'])) {
    $idpyt = $_POST['idpyt'];
    $stmt = $pdo->prepare('DELETE FROM pytania WHERE idpyt = ? AND idt = ?');
    $stmt->execute([$idpyt, $idt]);
    header("Location: edit_test.php?id=$idt");
    exit;
}

$pytania = $pdo->prepare('SELECT * FROM pytania WHERE idt = ?');
$pytania->execute([$idt]);
$pytania = $pytania->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edycja Testu - Z14</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="py-5">
    <div class="container glass-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Edycja Testu: <?= htmlspecialchars($test['nazwa']) ?></h2>
            <a href="coach.php" class="btn btn-outline-secondary">Powrót</a>
        </div>
        
        <h4>Dodaj Pytanie</h4>
        <form method="POST" enctype="multipart/form-data" class="mb-5">
            <input type="hidden" name="add_question" value="1">
            <div class="mb-3">
                <textarea name="tresc_pytania" class="form-control" placeholder="Treść pytania" required></textarea>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group mb-2">
                        <div class="input-group-text"><input type="checkbox" name="a" value="1"></div>
                        <input type="text" name="odpowiedz_a" class="form-control" placeholder="Odpowiedź A" required>
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-text"><input type="checkbox" name="b" value="1"></div>
                        <input type="text" name="odpowiedz_b" class="form-control" placeholder="Odpowiedź B" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-group mb-2">
                        <div class="input-group-text"><input type="checkbox" name="c" value="1"></div>
                        <input type="text" name="odpowiedz_c" class="form-control" placeholder="Odpowiedź C" required>
                    </div>
                    <div class="input-group mb-2">
                        <div class="input-group-text"><input type="checkbox" name="d" value="1"></div>
                        <input type="text" name="odpowiedz_d" class="form-control" placeholder="Odpowiedź D" required>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label>Plik multimedialny (opcjonalnie)</label>
                <input type="file" name="plik" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Zapisz Pytanie</button>
            <small class="text-muted d-block mt-2">Zaznacz poprawną/e odpowiedzi. Punkt przyznawany jest tylko jeśli kursant zaznaczy DOKŁADNIE to samo.</small>
        </form>

        <h4>Pytania w teście</h4>
        <table class="table table-dark table-striped">
            <thead><tr><th>Treść</th><th>Odpowiedzi</th><th>Akcje</th></tr></thead>
            <tbody>
                <?php foreach($pytania as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['tresc_pytania']) ?></td>
                    <td>
                        <ul class="list-unstyled mb-0">
                            <li class="<?= $p['a'] ? 'text-success fw-bold' : '' ?>">A: <?= htmlspecialchars($p['odpowiedz_a']) ?></li>
                            <li class="<?= $p['b'] ? 'text-success fw-bold' : '' ?>">B: <?= htmlspecialchars($p['odpowiedz_b']) ?></li>
                            <li class="<?= $p['c'] ? 'text-success fw-bold' : '' ?>">C: <?= htmlspecialchars($p['odpowiedz_c']) ?></li>
                            <li class="<?= $p['d'] ? 'text-success fw-bold' : '' ?>">D: <?= htmlspecialchars($p['odpowiedz_d']) ?></li>
                        </ul>
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="delete_question" value="1">
                            <input type="hidden" name="idpyt" value="<?= $p['idpyt'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Usunąć pytanie?')">Usuń</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>