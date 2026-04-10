<?php
$lab_name = 'z16';
$lab_title = 'Zadanie 16 - CMS';
$db_name = 'z16';
require_once '../shared/config.php';
require_once 'helpers.php';

session_start();

$portal_id = $_SESSION['current_id_cms'] ?? 1;

if (isset($_SESSION[$lab_name]['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND id_cms = ?");
    $stmt->execute([$username, $portal_id]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION[$lab_name] = [
            'admin_id' => $admin['id'],
            'username' => $admin['username'],
            'id_cms' => $admin['id_cms']
        ];
        logLogin($pdo, $admin['id_cms'], $username, true);
        header("Location: index.php");
        exit;
    } else {
        $error = "Błędny login lub hasło.";
        logLogin($pdo, $portal_id, $username, false);
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lab_title ?> - Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-family: 'Inter', sans-serif;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="glass-card mx-auto" style="max-width: 400px;">
            <h2 class="text-center mb-4">Admin Login</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Login</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hasło</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Zaloguj</button>
            </form>
            <div class="mt-3 text-center">
                <a href="index.php" class="text-white-50 text-decoration-none small">Powrót do portalu</a>
            </div>
        </div>
    </div>
</body>
</html>
