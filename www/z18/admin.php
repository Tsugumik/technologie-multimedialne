<?php
require_once 'config.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit;
}

$success = '';
$error = '';

// Handle User Actions
if (isset($_GET['action']) && isset($_GET['uid'])) {
    $uid = intval($_GET['uid']);
    $action = $_GET['action'];

    if ($uid == $_SESSION[$lab_name]['user_id']) {
        $error = "Nie możesz modyfikować własnego konta administratora.";
    } else {
        switch ($action) {
            case 'delete':
                // Get username to delete folder
                $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$uid]);
                $user = $stmt->fetch();
                if ($user) {
                    // Recursive directory deletion
                    $dir = getUserDir($user['username']);
                    if (file_exists($dir)) {
                        function rrmdir($dir) {
                            if (is_dir($dir)) {
                                $objects = scandir($dir);
                                foreach ($objects as $object) {
                                    if ($object != "." && $object != "..") {
                                        if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                                            rrmdir($dir. DIRECTORY_SEPARATOR .$object);
                                        else
                                            unlink($dir. DIRECTORY_SEPARATOR .$object);
                                    }
                                }
                                rmdir($dir);
                            }
                        }
                        rrmdir($dir);
                    }
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$uid]);
                    $success = "Użytkownik usunięty.";
                }
                break;
            case 'ban':
                $stmt = $pdo->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
                $stmt->execute([$uid]);
                $success = "Użytkownik zablokowany.";
                break;
            case 'unban':
                $stmt = $pdo->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
                $stmt->execute([$uid]);
                $success = "Użytkownik odblokowany.";
                break;
            case 'promote':
                $stmt = $pdo->prepare("UPDATE users SET role = 'moderator' WHERE id = ?");
                $stmt->execute([$uid]);
                $success = "Użytkownik mianowany moderatorem.";
                break;
            case 'demote':
                $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?");
                $stmt->execute([$uid]);
                $success = "Uprawnienia moderatora odebrane.";
                break;
        }
    }
}

$users = $pdo->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC")->fetchAll();
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
<body>
    <?php include __DIR__ . '/../shared/header.php'; ?>

    <div class="container mt-4">
        <h2 class="text-white mb-4">Panel Administratora</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- User Management -->
            <div class="col-12 mb-5">
                <div class="glass-card">
                    <h4 class="text-white mb-3">Zarządzanie użytkownikami</h4>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover small">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Użytkownik</th>
                                    <th>Rola</th>
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
                                        <td>
                                            <?php if ($u['is_banned']): ?>
                                                <span class="badge bg-danger">Zablokowany</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Aktywny</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($u['is_banned']): ?>
                                                    <a href="admin.php?action=unban&uid=<?= $u['id'] ?>" class="btn btn-outline-success">Odblokuj</a>
                                                <?php else: ?>
                                                    <a href="admin.php?action=ban&uid=<?= $u['id'] ?>" class="btn btn-outline-warning">Zablokuj</a>
                                                <?php endif; ?>

                                                <?php if ($u['role'] === 'user'): ?>
                                                    <a href="admin.php?action=promote&uid=<?= $u['id'] ?>" class="btn btn-outline-info">Moderator</a>
                                                <?php else: ?>
                                                    <a href="admin.php?action=demote&uid=<?= $u['id'] ?>" class="btn btn-outline-secondary">Zwykły user</a>
                                                <?php endif; ?>

                                                <a href="admin.php?action=delete&uid=<?= $u['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Czy na pewno chcesz usunąć tego użytkownika i wszystkie jego galerie?')">Usuń</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Login Logs -->
            <div class="col-12">
                <div class="glass-card">
                    <h4 class="text-white mb-3">Historia logowania</h4>
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-dark table-hover small">
                            <thead>
                                <tr>
                                    <th>Czas</th>
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
                                            <?php if ($log['success']): ?>
                                                <span class="text-success">Pomyślne</span>
                                            <?php else: ?>
                                                <span class="text-danger">Nieudane</span>
                                            <?php endif; ?>
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

    <?php include __DIR__ . '/../shared/footer.php'; ?>
</body>
</html>
