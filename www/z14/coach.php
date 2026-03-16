<?php
session_start();
$db_name = 'z14';
require_once '../shared/config.php';

if (!isset($_SESSION['z14_role']) || $_SESSION['z14_role'] !== 'coach') {
    header('Location: login.php');
    exit;
}

$idc = $_SESSION['z14_user'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_lesson'])) {
        $nazwa = $_POST['nazwa'];
        $tresc = $_POST['tresc'];
        $plik = '';
        if (!empty($_FILES['plik']['name']) && $_FILES['plik']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir(__DIR__ . '/lekcje')) mkdir(__DIR__ . '/lekcje', 0777, true);
            $plik = 'lekcje/' . time() . '_' . basename($_FILES['plik']['name']);
            move_uploaded_file($_FILES['plik']['tmp_name'], __DIR__ . '/' . $plik);
        }
        $stmt = $pdo->prepare('INSERT INTO lekcje (idc, nazwa, tresc, plik_multimedialny) VALUES (?, ?, ?, ?)');
        $stmt->execute([$idc, $nazwa, $tresc, $plik]);
    } elseif (isset($_POST['add_test'])) {
        $nazwa = $_POST['nazwa'];
        $max_time = $_POST['max_time'] ? $_POST['max_time'] : null;
        $stmt = $pdo->prepare('INSERT INTO test (idc, nazwa, max_time) VALUES (?, ?, ?)');
        $stmt->execute([$idc, $nazwa, $max_time]);
    } elseif (isset($_POST['delete_lesson'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM lekcje WHERE idl = ? AND idc = ?');
        $stmt->execute([$id, $idc]);
    } elseif (isset($_POST['delete_test'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM test WHERE idt = ? AND idc = ?');
        $stmt->execute([$id, $idc]);
    }
    header('Location: coach.php');
    exit;
}

$lekcje = $pdo->prepare('SELECT * FROM lekcje WHERE idc = ?');
$lekcje->execute([$idc]);
$lekcje = $lekcje->fetchAll();

$testy = $pdo->prepare('SELECT * FROM test WHERE idc = ?');
$testy->execute([$idc]);
$testy = $testy->fetchAll();

$wyniki = $pdo->prepare('SELECT w.*, p.login, t.nazwa AS test_nazwa FROM wyniki w JOIN pracownik p ON w.idp = p.idp JOIN test t ON w.idt = t.idt WHERE t.idc = ? ORDER BY datetime DESC');
$wyniki->execute([$idc]);
$wyniki = $wyniki->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z14 - Coach - E-learning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>
</head>
<body class="py-5">
    <div class="container glass-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Panel Szkoleniowca (Coach)</h2>
            <a href="logout.php" class="btn btn-outline-danger">Wyloguj</a>
        </div>
        
        <ul class="nav nav-tabs mb-4" id="coachTabs">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#lekcjeTab">Lekcje</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#testyTab">Testy</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#wynikiTab">Wyniki Kursantów</a></li>
        </ul>

        <div class="tab-content">
            <!-- Lekcje -->
            <div class="tab-pane fade show active" id="lekcjeTab">
                <h4>Dodaj Lekcję</h4>
                <form method="POST" enctype="multipart/form-data" class="mb-4">
                    <div class="mb-2">
                        <input type="text" name="nazwa" class="form-control" placeholder="Tytuł lekcji" required>
                    </div>
                    <div class="mb-2">
                        <textarea name="tresc" id="tresc" class="form-control" placeholder="Treść lekcji"></textarea>
                        <script>CKEDITOR.replace('tresc');</script>
                    </div>
                    <div class="mb-2">
                        <label>Plik multimedialny (opcjonalnie mp3/mp4/img)</label>
                        <input type="file" name="plik" class="form-control">
                    </div>
                    <button type="submit" name="add_lesson" class="btn btn-primary">Dodaj Lekcję</button>
                </form>
                
                <h4>Twoje Lekcje</h4>
                <table class="table table-dark table-striped">
                    <thead><tr><th>ID</th><th>Tytuł</th><th>Akcje</th></tr></thead>
                    <tbody>
                        <?php foreach($lekcje as $l): ?>
                        <tr>
                            <td><?= $l['idl'] ?></td>
                            <td><?= htmlspecialchars($l['nazwa']) ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $l['idl'] ?>">
                                    <button type="submit" name="delete_lesson" class="btn btn-sm btn-danger" onclick="return confirm('Usunąć?')">Usuń</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Testy -->
            <div class="tab-pane fade" id="testyTab">
                <h4>Dodaj Test</h4>
                <form method="POST" class="mb-4 d-flex gap-2">
                    <input type="text" name="nazwa" class="form-control" placeholder="Nazwa testu" required>
                    <input type="number" name="max_time" class="form-control" placeholder="Czas (sekundy, opcjonalnie)">
                    <button type="submit" name="add_test" class="btn btn-primary">Dodaj Test</button>
                </form>

                <h4>Twoje Testy</h4>
                <table class="table table-dark table-striped">
                    <thead><tr><th>ID</th><th>Nazwa</th><th>Czas (s)</th><th>Akcje</th></tr></thead>
                    <tbody>
                        <?php foreach($testy as $t): ?>
                        <tr>
                            <td><?= $t['idt'] ?></td>
                            <td><?= htmlspecialchars($t['nazwa']) ?></td>
                            <td><?= $t['max_time'] ? $t['max_time'] : 'Brak' ?></td>
                            <td>
                                <a href="edit_test.php?id=<?= $t['idt'] ?>" class="btn btn-sm btn-info">Zarządzaj Pytaniami</a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $t['idt'] ?>">
                                    <button type="submit" name="delete_test" class="btn btn-sm btn-danger" onclick="return confirm('Usunąć?')">Usuń</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Wyniki -->
            <div class="tab-pane fade" id="wynikiTab">
                <h4>Wyniki Kursantów</h4>
                <table class="table table-dark table-striped">
                    <thead><tr><th>Test</th><th>Kursant</th><th>Data</th><th>Punkty</th><th>PDF</th></tr></thead>
                    <tbody>
                        <?php foreach($wyniki as $w): ?>
                        <tr>
                            <td><?= htmlspecialchars($w['test_nazwa']) ?></td>
                            <td><?= htmlspecialchars($w['login']) ?></td>
                            <td><?= $w['datetime'] ?></td>
                            <td><?= $w['punkty'] ?></td>
                            <td><a href="<?= htmlspecialchars($w['plik_pdf']) ?>" target="_blank" class="btn btn-sm btn-success">Pobierz PDF</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>