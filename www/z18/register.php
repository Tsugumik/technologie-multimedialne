<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($username && $password && $password_confirm) {
        if ($password !== $password_confirm) {
            $error = "Hasła nie są identyczne.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Nazwa użytkownika jest już zajęta.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                if ($stmt->execute([$username, $hashed_password])) {
                    // Create user directory
                    getUserDir($username);
                    $success = "Rejestracja pomyślna. Możesz się teraz zalogować.";
                } else {
                    $error = "Błąd podczas rejestracji.";
                }
            }
        }
    } else {
        $error = "Wypełnij wszystkie pola.";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja - <?= $lab_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="flex-center py-5">
    <div class="container">
        <div class="glass-card mx-auto" style="max-width: 450px;">
            <div class="text-center mb-4">
                <h2 class="fw-bold mb-1">Rejestracja</h2>
                <p class="text-muted-custom fs-5 mb-0"><?= $lab_title ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-white">Nazwa użytkownika</label>
                    <input type="text" name="username" class="form-control form-control-glass" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-white">Hasło</label>
                    <input type="password" name="password" class="form-control form-control-glass" required>
                </div>
                <div class="mb-4">
                    <label class="form-label text-white">Powtórz hasło</label>
                    <input type="password" name="password_confirm" class="form-control form-control-glass" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fs-5">Zarejestruj się</button>
                
                <div class="text-center mt-3">
                    <p class="text-muted-custom mb-1">Masz już konto?</p>
                    <a href="login.php" class="text-info text-decoration-none">Zaloguj się</a>
                </div>
            </form>
            
            <div class="text-center mt-4">
                 <a href="/" class="text-muted-custom text-decoration-none small">&larr; Wróć do platformy</a>
            </div>
        </div>
    </div>
</body>
</html>
