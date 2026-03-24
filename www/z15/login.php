<?php
$lab_name = 'z15';
$lab_title = 'Zadanie 15 - CRM';
$db_name = 'z15';
require_once '../shared/config.php';
require_once 'helpers.php';

session_start();

if (isset($_SESSION[$lab_name]['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role_req = $_POST['role'] ?? 'client';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['role'] === $role_req || ($user['role'] === 'admin' && $role_req === 'employee')) {
            $_SESSION[$lab_name] = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ];
            logLogin($pdo, $user['id'], $username, true, $user['role']);
            header("Location: index.php");
            exit;
        } else {
            $error = "Nieprawidłowa rola dla tego użytkownika.";
            logLogin($pdo, $user['id'], $username, false, $role_req);
        }
    } else {
        $error = "Błędny login lub hasło.";
        logLogin($pdo, null, $username, false, $role_req);
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lab_title ?> - Logowanie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="flex-center py-5">
    <div class="container">
        <div class="glass-card mx-auto" style="max-width: 500px;">
            <h2 class="text-center mb-4">Logowanie CRM</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Zaloguj jako:</label>
                    <select name="role" class="form-select" required>
                        <option value="client">Klient</option>
                        <option value="employee">Pracownik / Admin</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Login</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hasło</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold">Zaloguj</button>
            </form>
            <div class="mt-4 text-center">
                <a href="register.php" class="text-info text-decoration-none small">Rejestracja nowego klienta</a>
                <span class="mx-2 text-white-50">|</span>
                <a href="/" class="text-info text-decoration-none small">Wróć</a>
            </div>
        </div>
    </div>
</body>
</html>
