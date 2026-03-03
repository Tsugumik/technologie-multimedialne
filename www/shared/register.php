<?php
$skip_auth = true;
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

if (isLoggedIn($lab_name)) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($username) || empty($password) || empty($password_confirm)) {
        $error = "Wypełnij wszystkie pola.";
    }
    elseif ($password !== $password_confirm) {
        $error = "Podane hasła nie są identyczne.";
    }
    else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Podana nazwa użytkownika jest już zajęta.";
        }
        else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            try {
                $stmt->execute([$username, $hashed_password]);
                $success = "Konto zostało pomyślnie utworzone. Możesz się zalogować.";
            }
            catch (Exception $e) {
                $error = "Wystąpił błąd podczas rejestracji.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja - <?php echo htmlspecialchars($lab_title ?? $lab_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="flex-center py-5">
    <div class="container">
        <div class="glass-card mx-auto" style="max-width: 450px;">
            <div class="text-center mb-4">
                <h2 class="fw-bold mb-1">Rejestracja</h2>
                <p class="text-muted-custom fs-5 mb-0"><?php echo htmlspecialchars($lab_title ?? $lab_name); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger shadow-sm border-0 bg-danger text-white bg-opacity-75">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php
endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success shadow-sm border-0 bg-success text-white bg-opacity-75">
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-4 text-center">
                        <a href="login.php" class="btn btn-primary-custom w-100 py-3">Przejdź do logowania</a>
                    </div>
                </div>
            <?php
else: ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label text-white">Nazwa użytkownika</label>
                    <input type="text" name="username" class="form-control form-control-glass" required autofocus placeholder="Wpisz unikalny login" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label text-white">Hasło</label>
                    <input type="password" name="password" class="form-control form-control-glass" required placeholder="Wpisz hasło">
                </div>
                <div class="mb-4">
                    <label class="form-label text-white">Powtórz hasło</label>
                    <input type="password" name="password_confirm" class="form-control form-control-glass" required placeholder="Wpisz ponownie hasło">
                </div>
                <button type="submit" class="btn btn-primary-custom w-100 py-3 mb-3 fs-5">Utwórz konto</button>
                
                <div class="text-center">
                    <p class="text-muted-custom mb-2">Masz już konto w tym module?</p>
                    <a href="login.php" class="text-info text-decoration-none fw-semibold">Zaloguj się</a>
                </div>
            </form>
            
            <?php
endif; ?>
            
            <div class="text-center mt-4">
                 <a href="/" class="text-muted-custom text-decoration-none small">&larr; Wróć do platformy</a>
            </div>
        </div>
    </div>
</body>
</html>
