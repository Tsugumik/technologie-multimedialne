<?php
session_start();
$db_name = 'z14';
require_once '../shared/config.php';

if (!isset($_SESSION['z14_role']) || $_SESSION['z14_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_coach'])) {
        $login = $_POST['login'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO coach (login, haslo) VALUES (?, ?)');
        $stmt->execute([$login, $password]);
    } elseif (isset($_POST['toggle_user'])) {
        $id = $_POST['id'];
        $type = $_POST['type']; // coach or pracownik
        $table = $type === 'coach' ? 'coach' : 'pracownik';
        $pk = $type === 'coach' ? 'idc' : 'idp';
        $stmt = $pdo->prepare("UPDATE $table SET active = NOT active WHERE $pk = ?");
        $stmt->execute([$id]);
    } elseif (isset($_POST['delete_user'])) {
        $id = $_POST['id'];
        $type = $_POST['type'];
        $table = $type === 'coach' ? 'coach' : 'pracownik';
        $pk = $type === 'coach' ? 'idc' : 'idp';
        $stmt = $pdo->prepare("DELETE FROM $table WHERE $pk = ?");
        $stmt->execute([$id]);
    } elseif (isset($_POST['delete_lesson'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM lekcje WHERE idl = ?');
        $stmt->execute([$id]);
    } elseif (isset($_POST['delete_test'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM test WHERE idt = ?');
        $stmt->execute([$id]);
    }
    header('Location: admin.php');
    exit;
}

$coaches = $pdo->query('SELECT * FROM coach')->fetchAll();
$pracownicy = $pdo->query('SELECT * FROM pracownik')->fetchAll();
$lekcje = $pdo->query('SELECT l.*, c.login AS coach_login FROM lekcje l JOIN coach c ON l.idc = c.idc')->fetchAll();
$testy = $pdo->query('SELECT t.*, c.login AS coach_login FROM test t JOIN coach c ON t.idc = c.idc')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z14 - Admin - E-learning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="py-5">
    <div class="container glass-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Panel Administratora</h2>
            <a href="logout.php" class="btn btn-outline-danger">Wyloguj</a>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <h4>Dodaj Coacha</h4>
                <form method="POST" class="d-flex gap-2">
                    <input type="text" name="login" class="form-control" placeholder="Login" required>
                    <input type="password" name="password" class="form-control" placeholder="Hasło" required>
                    <button type="submit" name="add_coach" class="btn btn-primary">Dodaj</button>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <h4>Coachowie</h4>
                <table class="table table-dark table-striped">
                    <thead><tr><th>ID</th><th>Login</th><th>Status</th><th>Akcje</th></tr></thead>
                    <tbody>
                        <?php foreach($coaches as $c): ?>
                        <tr>
                            <td><?= $c['idc'] ?></td>
                            <td><?= htmlspecialchars($c['login']) ?></td>
                            <td><?= $c['active'] ? 'Aktywny' : 'Zablokowany' ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $c['idc'] ?>">
                                    <input type="hidden" name="type" value="coach">
                                    <button type="submit" name="toggle_user" class="btn btn-sm <?= $c['active'] ? 'btn-warning' : 'btn-success' ?>">
                                        <?= $c['active'] ? 'Zablokuj' : 'Odblokuj' ?>
                                    </button>
                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Usunąć? To usunie też jego lekcje i testy.')">Usuń</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="col-md-6 mb-4">
                <h4>Pracownicy (Kursanci)</h4>
                <table class="table table-dark table-striped">
                    <thead><tr><th>ID</th><th>Login</th><th>Status</th><th>Akcje</th></tr></thead>
                    <tbody>
                        <?php foreach($pracownicy as $p): ?>
                        <tr>
                            <td><?= $p['idp'] ?></td>
                            <td><?= htmlspecialchars($p['login']) ?></td>
                            <td><?= $p['active'] ? 'Aktywny' : 'Zablokowany' ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $p['idp'] ?>">
                                    <input type="hidden" name="type" value="pracownik">
                                    <button type="submit" name="toggle_user" class="btn btn-sm <?= $p['active'] ? 'btn-warning' : 'btn-success' ?>">
                                        <?= $p['active'] ? 'Zablokuj' : 'Odblokuj' ?>
                                    </button>
                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Usunąć?')">Usuń</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <h4>Lekcje</h4>
                <table class="table table-dark table-striped">
                    <thead><tr><th>ID</th><th>Nazwa</th><th>Autor</th><th>Akcje</th></tr></thead>
                    <tbody>
                        <?php foreach($lekcje as $l): ?>
                        <tr>
                            <td><?= $l['idl'] ?></td>
                            <td><?= htmlspecialchars($l['nazwa']) ?></td>
                            <td><?= htmlspecialchars($l['coach_login']) ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $l['idl'] ?>">
                                    <button type="submit" name="delete_lesson" class="btn btn-sm btn-danger" onclick="return confirm('Usunąć lekcję?')">Usuń</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="col-md-6 mb-4">
                <h4>Testy</h4>
                <table class="table table-dark table-striped">
                    <thead><tr><th>ID</th><th>Nazwa</th><th>Autor</th><th>Akcje</th></tr></thead>
                    <tbody>
                        <?php foreach($testy as $t): ?>
                        <tr>
                            <td><?= $t['idt'] ?></td>
                            <td><?= htmlspecialchars($t['nazwa']) ?></td>
                            <td><?= htmlspecialchars($t['coach_login']) ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $t['idt'] ?>">
                                    <button type="submit" name="delete_test" class="btn btn-sm btn-danger" onclick="return confirm('Usunąć test?')">Usuń</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>