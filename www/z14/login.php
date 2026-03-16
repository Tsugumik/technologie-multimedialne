<?php
session_start();
$db_name = 'z14';
require_once '../shared/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'];
    $password = $_POST['password'];
    $role = $_POST['role']; // admin, coach, pracownik
    $error = '';

    if ($role == 'admin') {
        $stmt = $pdo->prepare('SELECT * FROM admin WHERE login = ?');
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['haslo'])) {
            $_SESSION['z14_user'] = $user['ida'];
            $_SESSION['z14_role'] = 'admin';
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Błędny login lub hasło dla administratora.';
        }
    } elseif ($role == 'coach') {
        $stmt = $pdo->prepare('SELECT * FROM coach WHERE login = ? AND active = 1');
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['haslo'])) {
            $_SESSION['z14_user'] = $user['idc'];
            $_SESSION['z14_role'] = 'coach';
            header('Location: coach.php');
            exit;
        } else {
            $error = 'Błędny login lub hasło dla coacha (lub konto zablokowane).';
        }
    } elseif ($role == 'pracownik') {
        $stmt = $pdo->prepare('SELECT * FROM pracownik WHERE login = ? AND active = 1');
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['haslo'])) {
            $_SESSION['z14_user'] = $user['idp'];
            $_SESSION['z14_role'] = 'pracownik';
            header('Location: pracownik.php');
            exit;
        } else {
            $error = 'Błędny login lub hasło dla pracownika (lub konto zablokowane).';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z14 - Logowanie - E-learning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="flex-center py-5">
    <div class="container">
        <div class="glass-card mx-auto" style="max-width: 500px;">
            <h2 class="text-center mb-4">Logowanie</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Rola</label>
                    <select name="role" class="form-select" required>
                        <option value="pracownik">Pracownik (Kursant)</option>
                        <option value="coach">Coach (Szkoleniowiec)</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Login</label>
                    <input type="text" name="login" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hasło</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Zaloguj</button>
            </form>
            <div class="mt-3 text-center">
                <a href="register.php">Rejestracja dla pracowników</a> | <a href="/">Wróć</a>
            </div>
        </div>
    </div>
</body>
</html>