<?php
session_start();
$db_name = 'z14';
require_once '../shared/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare('SELECT idp FROM pracownik WHERE login = ?');
    $stmt->execute([$login]);
    if ($stmt->fetch()) {
        $error = "Taki login już istnieje.";
    } else {
        $stmt = $pdo->prepare('INSERT INTO pracownik (login, haslo) VALUES (?, ?)');
        $stmt->execute([$login, $password]);
        header('Location: login.php?registered=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z14 - Rejestracja - E-learning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="flex-center py-5">
    <div class="container">
        <div class="glass-card mx-auto" style="max-width: 500px;">
            <h2 class="text-center mb-4">Rejestracja Pracownika</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Login</label>
                    <input type="text" name="login" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hasło</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Zarejestruj</button>
            </form>
            <div class="mt-3 text-center">
                <a href="login.php">Logowanie</a> | <a href="/">Wróć</a>
            </div>
        </div>
    </div>
</body>
</html>