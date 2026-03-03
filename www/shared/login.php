<?php
$skip_auth = true;
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

if (isLoggedIn($lab_name)) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION[$lab_name]['user_id'] = $user['id'];
            $_SESSION[$lab_name]['username'] = $user['username'];

            header("Location: index.php");
            exit;
        }
        else {
            $error = "Nieprawidłowy login lub hasło.";
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
    <title>Logowanie - <?php echo htmlspecialchars($lab_title ?? $lab_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="flex-center py-5">
    <div class="container">
        <div class="glass-card mx-auto" style="max-width: 450px;">
            <div class="text-center mb-4">
                <h2 class="fw-bold mb-1">Logowanie</h2>
                <p class="text-muted-custom fs-5 mb-0"><?php echo htmlspecialchars($lab_title ?? $lab_name); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger shadow-sm border-0 bg-danger text-white bg-opacity-75">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php
endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label text-white">Nazwa użytkownika</label>
                    <input type="text" name="username" class="form-control form-control-glass" required autofocus placeholder="Wpisz login">
                </div>
                <div class="mb-4">
                    <label class="form-label text-white">Hasło</label>
                    <input type="password" name="password" class="form-control form-control-glass" required placeholder="Wpisz hasło">
                </div>
                <button type="submit" class="btn btn-primary-custom w-100 py-3 mb-3 fs-5">Zaloguj się</button>
                
                <div class="text-center">
                    <p class="text-muted-custom mb-2">Nie masz konta w tym laboratorium?</p>
                    <a href="register.php" class="text-info text-decoration-none fw-semibold">Zarejestruj się</a>
                </div>
            </form>
            
            <div class="text-center mt-4">
                 <a href="/" class="text-muted-custom text-decoration-none small">&larr; Wróć do platformy</a>
            </div>
        </div>
    </div>
</body>
</html>
