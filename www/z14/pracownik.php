<?php
session_start();
$db_name = 'z14';
require_once '../shared/config.php';

if (!isset($_SESSION['z14_role']) || $_SESSION['z14_role'] !== 'pracownik') {
    header('Location: login.php');
    exit;
}

$idp = $_SESSION['z14_user'];

$lekcje = $pdo->query('SELECT l.*, c.login AS autor FROM lekcje l JOIN coach c ON l.idc = c.idc')->fetchAll();
$testy = $pdo->query('SELECT t.*, c.login AS autor FROM test t JOIN coach c ON t.idc = c.idc')->fetchAll();
$wyniki = $pdo->prepare('SELECT w.*, t.nazwa AS test_nazwa FROM wyniki w JOIN test t ON w.idt = t.idt WHERE w.idp = ? ORDER BY datetime DESC');
$wyniki->execute([$idp]);
$wyniki = $wyniki->fetchAll();

$uzytkownik = $pdo->prepare('SELECT login FROM pracownik WHERE idp = ?');
$uzytkownik->execute([$idp]);
$uzytkownik_login = $uzytkownik->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z14 - Kursant - E-learning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .sidebar { min-height: calc(100vh - 100px); background: rgba(0,0,0,0.2); padding: 20px; border-radius: 10px; }
        .content { padding: 20px; }
        .lesson-link { color: white; text-decoration: none; display: block; padding: 10px; border-radius: 5px; margin-bottom: 5px; background: rgba(255,255,255,0.05); }
        .lesson-link:hover, .lesson-link.active { background: rgba(255,255,255,0.2); }
    </style>
</head>
<body class="py-4">
    <div class="container-fluid px-5">
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <h2>E-learning - Zalogowany jako: <?= htmlspecialchars($uzytkownik_login) ?></h2>
            <a href="logout.php" class="btn btn-outline-danger">Wyloguj</a>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="sidebar glass-card">
                    <h4>Nawigacja</h4>
                    <hr>
                    <a href="pracownik.php" class="lesson-link active">Dashboard</a>
                    <h5 class="mt-4">Lekcje</h5>
                    <?php foreach($lekcje as $l): ?>
                        <a href="lesson.php?id=<?= $l['idl'] ?>" class="lesson-link"><?= htmlspecialchars($l['nazwa']) ?></a>
                    <?php endforeach; ?>
                    <h5 class="mt-4">Testy</h5>
                    <a href="pracownik.php#testy" class="lesson-link">Dostępne testy</a>
                    <a href="pracownik.php#wyniki" class="lesson-link">Moje wyniki</a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="glass-card content mb-4" id="testy">
                    <h4>Dostępne Testy</h4>
                    <table class="table table-dark table-striped">
                        <thead><tr><th>Nazwa</th><th>Autor</th><th>Max Czas</th><th>Akcja</th></tr></thead>
                        <tbody>
                            <?php foreach($testy as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['nazwa']) ?></td>
                                <td><?= htmlspecialchars($t['autor']) ?></td>
                                <td><?= $t['max_time'] ? $t['max_time'] . 's' : 'Brak' ?></td>
                                <td><a href="test.php?id=<?= $t['idt'] ?>" class="btn btn-sm btn-primary">Rozwiąż test</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="glass-card content" id="wyniki">
                    <h4>Moje Wyniki</h4>
                    <table class="table table-dark table-striped">
                        <thead><tr><th>Test</th><th>Data</th><th>Punkty</th><th>PDF z odpowiedziami</th></tr></thead>
                        <tbody>
                            <?php foreach($wyniki as $w): ?>
                            <tr>
                                <td><?= htmlspecialchars($w['test_nazwa']) ?></td>
                                <td><?= $w['datetime'] ?></td>
                                <td><?= $w['punkty'] ?></td>
                                <td><a href="<?= htmlspecialchars($w['plik_pdf']) ?>" target="_blank" class="btn btn-sm btn-success">Otwórz PDF</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>