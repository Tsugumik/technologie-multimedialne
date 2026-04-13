<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = $_GET['error'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT id, username, password, role, is_banned FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_banned']) {
                $error = "Twoje konto zostało zablokowane.";
                logLogin($pdo, $user['id'], $username, false);
            } else {
                $_SESSION[$lab_name]['user_id'] = $user['id'];
                $_SESSION[$lab_name]['username'] = $user['username'];
                $_SESSION[$lab_name]['role'] = $user['role'];

                logLogin($pdo, $user['id'], $username, true);
                header("Location: index.php");
                exit;
            }
        }
        else {
            $error = "Nieprawidłowy login lub hasło.";
            logLogin($pdo, $user ? $user['id'] : null, $username, false);
        }
    }
    else {
        $error = "Wypełnij wszystkie pola.";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - <?= $lab_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="flex-center py-5">
    <div class="container">
        <div class="glass-card mx-auto" style="max-width: 450px;">
            <div class="text-center mb-4">
                <h2 class="fw-bold mb-1">Logowanie</h2>
                <p class="text-muted-custom fs-5 mb-0"><?= $lab_title ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-white">Nazwa użytkownika</label>
                    <input type="text" name="username" class="form-control form-control-glass" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label text-white">Hasło</label>
                    <input type="password" name="password" class="form-control form-control-glass" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fs-5">Zaloguj się</button>
                
                <div class="text-center mt-3">
                    <p class="text-muted-custom mb-1">Nie masz konta?</p>
                    <a href="register.php" class="text-info text-decoration-none">Zarejestruj się</a>
                </div>
                <div class="text-center mt-2">
                    <a href="index.php" class="text-info text-decoration-none small">Kontynuuj jako gość</a>
                </div>
            </form>
            
            <div class="text-center mt-4">
                 <a href="/" class="text-muted-custom text-decoration-none small">&larr; Wróć do platformy</a>
            </div>
        </div>
    </div>
</body>
</html>
