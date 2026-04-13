<?php
require_once 'config.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit;
}

// Handle User Ban/Unban
if (isset($_GET['toggle_ban'])) {
    $stmt = $pdo->prepare("UPDATE users SET is_banned = 1 - is_banned WHERE id = ?");
    $stmt->execute([$_GET['toggle_ban']]);
    header("Location: admin.php");
    exit;
}

// Handle User Deletion
if (isset($_GET['delete_user'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$_GET['delete_user']]);
    header("Location: admin.php");
    exit;
}

// Handle All Posts Deletion
if (isset($_GET['delete_all_posts'])) {
    $stmt = $pdo->prepare("DELETE FROM posts WHERE author_id = ?");
    $stmt->execute([$_GET['delete_all_posts']]);
    header("Location: admin.php");
    exit;
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$logs = $pdo->query("SELECT * FROM login_logs ORDER BY timestamp DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora - <?= $lab_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="py-5">
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top glass-card rounded-0 py-3 mb-5" style="border:none; border-bottom: 1px solid rgba(255,255,255,0.1)">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><?= $lab_title ?> - ADMIN</a>
            <div class="ms-auto">
                <a href="index.php" class="btn btn-outline-light btn-sm">Wróć do forum</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-4">
        <h1 class="text-white fw-bold mb-4">Panel Administratora</h1>

        <div class="row">
            <div class="col-12 mb-5">
                <h3 class="text-white mb-3">Zarządzanie użytkownikami</h3>
                <div class="glass-card">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Użytkownik</th>
                                    <th>Rola</th>
                                    <th>Wulgaryzmy</th>
                                    <th>Status</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?= $u['id'] ?></td>
                                        <td><?= htmlspecialchars($u['username']) ?></td>
                                        <td><?= $u['role'] ?></td>
                                        <td><?= $u['swear_count'] ?></td>
                                        <td>
                                            <span class="badge <?= $u['is_banned'] ? 'bg-danger' : 'bg-success' ?>">
                                                <?= $u['is_banned'] ? 'Zablokowany' : 'Aktywny' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($u['role'] !== 'admin'): ?>
                                                <a href="?toggle_ban=<?= $u['id'] ?>" class="btn btn-sm <?= $u['is_banned'] ? 'btn-success' : 'btn-warning' ?>">
                                                    <?= $u['is_banned'] ? 'Odblokuj' : 'Zablokuj' ?>
                                                </a>
                                                <a href="?delete_all_posts=<?= $u['id'] ?>" class="btn btn-sm btn-info" onclick="return confirm('Usunąć wszystkie posty tego użytkownika?')">Usuń posty</a>
                                                <a href="?delete_user=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Na pewno usunąć użytkownika?')">Usuń</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <h3 class="text-white mb-3">Historia logowania</h3>
                <div class="glass-card">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-dark table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Użytkownik</th>
                                    <th>IP</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= $log['timestamp'] ?></td>
                                        <td><?= htmlspecialchars($log['username']) ?></td>
                                        <td><?= $log['ip'] ?></td>
                                        <td>
                                            <span class="text-<?= $log['success'] ? 'success' : 'danger' ?>">
                                                <?= $log['success'] ? 'Sukces' : 'Porażka' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
