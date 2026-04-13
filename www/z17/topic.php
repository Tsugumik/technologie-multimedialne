<?php
require_once 'config.php';

$topic_id = $_GET['id'] ?? null;
if (!$topic_id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM topics WHERE id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();

if (!$topic) {
    die("Temat nie istnieje.");
}

$warning = "";

// Handle New Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && isset($_POST['new_post'])) {
    $content = trim($_POST['content'] ?? '');
    $file_path = NULL;

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir);
        $filename = time() . '_' . basename($_FILES['attachment']['name']);
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target)) {
            $file_path = $target;
        }
    }

    if ($content || $file_path) {
        $result = handlePostSubmission($pdo, $topic_id, $_SESSION[$lab_name]['user_id'], $content, $file_path, $_SESSION[$lab_name]['username']);
        
        if ($result['status'] === 'banned') {
            header("Location: login.php?error=Zostałeś zablokowany za używanie wulgaryzmów.");
            exit;
        } elseif ($result['status'] === 'warning') {
            $warning = "Twój post został usunięty ze względu na użyty wulgaryzm. Otrzymujesz ostrzeżenie!";
        } else {
            header("Location: topic.php?id=$topic_id");
            exit;
        }
    }
}

// Handle Post Deletion
if (isset($_GET['delete_post'])) {
    $post_id = $_GET['delete_post'];
    $stmt = $pdo->prepare("SELECT author_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $p = $stmt->fetch();

    if ($p && (isAdmin() || (isLoggedIn() && $p['author_id'] == $_SESSION[$lab_name]['user_id']))) {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        header("Location: topic.php?id=$topic_id");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT p.*, u.username as author FROM posts p LEFT JOIN users u ON p.author_id = u.id WHERE p.topic_id = ? ORDER BY p.created_at ASC");
$stmt->execute([$topic_id]);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($topic['title']) ?> - <?= $lab_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .post-card {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.5rem 0;
        }
        .post-card:last-child {
            border-bottom: none;
        }
        .author-box {
            min-width: 150px;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        .system-msg {
            background: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #ffc107;
            padding: 1rem;
            color: #ffc107;
            border-radius: 4px;
        }
    </style>
</head>
<body class="py-5">
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top glass-card rounded-0 py-3 mb-5" style="border:none; border-bottom: 1px solid rgba(255,255,255,0.1)">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><?= $lab_title ?></a>
            <div class="ms-auto">
                <a href="index.php" class="btn btn-outline-light btn-sm">Wróć do listy</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-4">
        <h1 class="text-white fw-bold mb-4"><?= htmlspecialchars($topic['title']) ?></h1>

        <?php if ($warning): ?>
            <div class="alert alert-warning"><?= $warning ?></div>
        <?php endif; ?>

        <div class="glass-card mb-4">
            <?php if (empty($posts)): ?>
                <p class="text-muted-custom text-center py-4">Brak odpowiedzi w tym temacie.</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card d-flex">
                        <div class="author-box pe-3 me-3 text-center">
                            <div class="fw-bold text-info"><?= htmlspecialchars($post['author'] ?? ($post['is_system_msg'] ? 'System' : 'Gość')) ?></div>
                            <small class="text-muted-custom d-block"><?= date('H:i d.m.y', strtotime($post['created_at'])) ?></small>
                            <?php if (isLoggedIn() && ($post['author_id'] == $_SESSION[$lab_name]['user_id'] || isAdmin())): ?>
                                <a href="?id=<?= $topic_id ?>&delete_post=<?= $post['id'] ?>" class="text-danger text-decoration-none fs-5 mt-2 d-inline-block" onclick="return confirm('Usunąć ten post?')" title="Usuń post">🗑️</a>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="<?= $post['is_system_msg'] ? 'system-msg' : 'text-white' ?>">
                                <?= nl2br(htmlspecialchars($post['content'])) ?>
                            </div>
                            <?php if ($post['file_path']): ?>
                                <div class="mt-3">
                                    <a href="<?= $post['file_path'] ?>" target="_blank" class="btn btn-outline-info btn-sm">Zobacz załącznik</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (isLoggedIn()): ?>
            <div class="glass-card">
                <h5 class="text-white mb-3">Dodaj odpowiedź</h5>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="new_post" value="1">
                    <div class="mb-3">
                        <textarea name="content" class="form-control form-control-glass" rows="4" placeholder="Twoja wiadomość..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">Załącznik (obraz, plik)</label>
                        <input type="file" name="attachment" class="form-control form-control-glass">
                    </div>
                    <button type="submit" class="btn btn-primary">Wyślij post</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info bg-info bg-opacity-10 border-info text-info">
                Zaloguj się, aby dodawać posty.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
