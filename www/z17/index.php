<?php
require_once 'config.php';

// Handle New Topic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && isset($_POST['new_topic'])) {
    $title = trim($_POST['title'] ?? '');
    if ($title) {
        $stmt = $pdo->prepare("INSERT INTO topics (title, author_id) VALUES (?, ?)");
        $stmt->execute([$title, $_SESSION[$lab_name]['user_id']]);
        header("Location: index.php");
        exit;
    }
}

// Handle Topic Deletion (Admin only)
if (isset($_GET['delete_topic']) && isAdmin()) {
    $stmt = $pdo->prepare("DELETE FROM topics WHERE id = ?");
    $stmt->execute([$_GET['delete_topic']]);
    header("Location: index.php");
    exit;
}

$stmt = $pdo->query("SELECT t.*, u.username as author FROM topics t LEFT JOIN users u ON t.author_id = u.id ORDER BY t.created_at DESC");
$topics = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lab_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .topic-row {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .topic-row:hover {
            transform: scale(1.01);
            background: rgba(255, 255, 255, 0.1) !important;
        }
    </style>
</head>
<body class="py-5">
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top glass-card rounded-0 py-3 mb-5" style="border:none; border-bottom: 1px solid rgba(255,255,255,0.1)">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><?= $lab_title ?></a>
            <div class="ms-auto d-flex align-items-center">
                <?php if (isLoggedIn()): ?>
                    <span class="text-white me-3">Witaj, <strong><?= htmlspecialchars($_SESSION[$lab_name]['username']) ?></strong>!</span>
                    <?php if (isAdmin()): ?>
                        <a href="admin.php" class="btn btn-outline-warning btn-sm me-2">Panel Admina</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Wyloguj</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary btn-sm me-2">Zaloguj się</a>
                    <a href="register.php" class="btn btn-primary btn-sm">Zarejestruj</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-white fw-bold">Tematy dyskusji</h1>
            <?php if (isLoggedIn()): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTopicModal">Nowy Temat</button>
            <?php endif; ?>
        </div>

        <div class="glass-card">
            <?php if (empty($topics)): ?>
                <p class="text-muted-custom text-center py-5">Brak tematów na forum. Bądź pierwszy!</p>
            <?php else: ?>
                <div class="list-group list-group-flush bg-transparent">
                    <?php foreach ($topics as $topic): ?>
                        <div class="list-group-item bg-transparent text-white border-bottom border-white border-opacity-10 py-3 topic-row d-flex justify-content-between align-items-center" onclick="location.href='topic.php?id=<?= $topic['id'] ?>'">
                            <div>
                                <h5 class="mb-1"><?= htmlspecialchars($topic['title']) ?></h5>
                                <small class="text-muted-custom">Autor: <?= htmlspecialchars($topic['author'] ?? 'Gość') ?> | <?= $topic['created_at'] ?></small>
                            </div>
                            <?php if (isAdmin()): ?>
                                <a href="?delete_topic=<?= $topic['id'] ?>" class="btn btn-danger btn-sm" onclick="event.stopPropagation(); return confirm('Na pewno usunąć ten temat?')">Usuń</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- New Topic Modal -->
    <div class="modal fade" id="newTopicModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content glass-card p-0">
                <div class="modal-header border-bottom border-white border-opacity-10">
                    <h5 class="modal-title text-white">Załóż nowy temat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="new_topic" value="1">
                        <div class="mb-3">
                            <label class="form-label text-white">Tytuł tematu</label>
                            <input type="text" name="title" class="form-control form-control-glass" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-white border-opacity-10">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Stwórz</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
