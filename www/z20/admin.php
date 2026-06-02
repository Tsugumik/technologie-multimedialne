<?php
require_once 'config.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $target_user_id = intval($_POST['user_id'] ?? 0);
        
        if ($target_user_id === $_SESSION[$lab_name]['user_id']) {
            $error = "Nie możesz modyfikować własnego konta.";
        } else {
            // Get target user info
            $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
            $stmt->execute([$target_user_id]);
            $target_user = $stmt->fetch();

            if ($target_user) {
                if ($_POST['action'] === 'toggle_ban') {
                    if ($target_user['role'] === 'admin') {
                        $error = "Nie możesz zablokować administratora.";
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET is_banned = NOT is_banned WHERE id = ?");
                        $stmt->execute([$target_user_id]);
                        $success = "Zmieniono status blokady użytkownika " . htmlspecialchars($target_user['username']) . ".";
                    }
                } elseif ($_POST['action'] === 'change_role') {
                    $new_role = $_POST['role'] ?? 'user';
                    if (!in_array($new_role, ['user', 'moderator', 'admin'])) {
                        $error = "Nieprawidłowa rola.";
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $stmt->execute([$new_role, $target_user_id]);
                        $success = "Zmieniono rolę użytkownika " . htmlspecialchars($target_user['username']) . " na " . $new_role . ".";
                    }
                }
            } else {
                $error = "Nie znaleziono użytkownika.";
            }
        }
    }
}

// Fetch stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_announcements = $pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
$total_success_logins = $pdo->query("SELECT COUNT(*) FROM login_logs WHERE success = 1")->fetchColumn();
$total_failed_logins = $pdo->query("SELECT COUNT(*) FROM login_logs WHERE success = 0")->fetchColumn();

// Fetch users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

// Fetch announcements
$announcements = $pdo->query("
    SELECT a.*, u.username 
    FROM announcements a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC
")->fetchAll();

// Fetch login logs
$logs = $pdo->query("SELECT * FROM login_logs ORDER BY timestamp DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora - <?= htmlspecialchars($lab_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .nav-tabs .nav-link {
            color: rgba(255,255,255,0.6);
            border: none;
            background: none;
            font-weight: 600;
        }
        .nav-tabs .nav-link.active {
            color: #00c6ff;
            background: rgba(255,255,255,0.05);
            border-bottom: 3px solid #00c6ff;
            border-radius: 0.5rem 0.5rem 0 0;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
        }
        .table-responsive {
            background: rgba(0,0,0,0.2);
            border-radius: 0.75rem;
            padding: 1rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>

    <div class="glass-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-light border-opacity-10 pb-3">
            <h2 class="text-white mb-0">Panel Administratora (GIS Portal)</h2>
            <a href="index.php" class="btn btn-outline-light btn-sm">Powrót do portalu</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="row g-4 mb-5">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <span class="fs-1 d-block mb-1">👥</span>
                    <h6 class="text-muted-custom mb-1">Użytkownicy</h6>
                    <h3 class="text-white fw-bold mb-0"><?= $total_users ?></h3>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <span class="fs-1 d-block mb-1">🏠</span>
                    <h6 class="text-muted-custom mb-1">Ogłoszenia</h6>
                    <h3 class="text-white fw-bold mb-0"><?= $total_announcements ?></h3>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <span class="fs-1 d-block mb-1">✔️</span>
                    <h6 class="text-muted-custom mb-1">Udane logowania</h6>
                    <h3 class="text-success fw-bold mb-0"><?= $total_success_logins ?></h3>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <span class="fs-1 d-block mb-1">❌</span>
                    <h6 class="text-muted-custom mb-1">Nieudane logowania</h6>
                    <h3 class="text-danger fw-bold mb-0"><?= $total_failed_logins ?></h3>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users-panel" type="button">Zarządzanie Użytkownikami</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="announcements-tab" data-bs-toggle="tab" data-bs-target="#announcements-panel" type="button">Zarządzanie Ogłoszeniami</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs-panel" type="button">Logi logowania</button>
            </li>
        </ul>

        <!-- Tab Contents -->
        <div class="tab-content text-white" id="adminTabsContent">
            
            <!-- Tab 1: Users -->
            <div class="tab-pane fade show active" id="users-panel">
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nazwa użytkownika</th>
                                <th>Rola</th>
                                <th>Status</th>
                                <th>Data rejestracji</th>
                                <th class="text-end">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= $u['id'] ?></td>
                                    <td>
                                        <strong class="text-info"><?= htmlspecialchars($u['username']) ?></strong>
                                        <?php if ($u['id'] === $_SESSION[$lab_name]['user_id']): ?>
                                            <span class="badge bg-secondary ms-1">Ty</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline-block">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <select name="role" class="form-select form-select-sm form-control-glass d-inline-block w-auto" onchange="this.form.submit()" <?= $u['id'] === $_SESSION[$lab_name]['user_id'] ? 'disabled' : '' ?>>
                                                <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                                <option value="moderator" <?= $u['role'] === 'moderator' ? 'selected' : '' ?>>Moderator</option>
                                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($u['is_banned']): ?>
                                            <span class="badge bg-danger">Zablokowany</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Aktywny</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small"><?= $u['created_at'] ?></td>
                                    <td class="text-end">
                                        <?php if ($u['id'] !== $_SESSION[$lab_name]['user_id'] && $u['role'] !== 'admin'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_ban">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-sm <?= $u['is_banned'] ? 'btn-outline-success' : 'btn-outline-danger' ?>">
                                                    <?= $u['is_banned'] ? 'Odblokuj' : 'Zablokuj' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Announcements -->
            <div class="tab-pane fade" id="announcements-panel">
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tytuł</th>
                                <th>Autor</th>
                                <th>Cena (PLN)</th>
                                <th>Kategoria / Typ</th>
                                <th>Lokalizacja</th>
                                <th class="text-end">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($announcements as $ann): ?>
                                <tr>
                                    <td><?= $ann['id'] ?></td>
                                    <td>
                                        <a href="details.php?id=<?= $ann['id'] ?>" class="text-white text-decoration-none fw-semibold"><?= htmlspecialchars($ann['title']) ?></a>
                                    </td>
                                    <td><?= htmlspecialchars($ann['username']) ?></td>
                                    <td><span class="text-info font-monospace"><?= number_format($ann['price'], 2, ',', ' ') ?></span></td>
                                    <td>
                                        <span class="badge bg-primary me-1"><?= getCategoryName($ann['category']) ?></span>
                                        <span class="badge bg-success"><?= getTypeName($ann['type']) ?></span>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($ann['postal_code']) ?> <?= htmlspecialchars($ann['city']) ?></td>
                                    <td class="text-end">
                                        <a href="details.php?id=<?= $ann['id'] ?>" class="btn btn-sm btn-outline-info me-1">Zobacz</a>
                                        <a href="delete.php?type=announcement&id=<?= $ann['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno chcesz usunąć to ogłoszenie?')">Usuń</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 3: Login Logs -->
            <div class="tab-pane fade" id="logs-panel">
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nazwa użytkownika</th>
                                <th>Adres IP</th>
                                <th>Status logowania</th>
                                <th>Czas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= $log['id'] ?></td>
                                    <td><?= htmlspecialchars($log['username']) ?></td>
                                    <td class="font-monospace"><?= htmlspecialchars($log['ip']) ?></td>
                                    <td>
                                        <?php if ($log['success']): ?>
                                            <span class="text-success fw-bold">Pomyślne</span>
                                        <?php else: ?>
                                            <span class="text-danger fw-bold">Nieudane</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $log['timestamp'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Include Bootstrap JS from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
