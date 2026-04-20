<?php
require_once 'config.php';

$photo_id = $_GET['photo_id'] ?? null;
if (!$photo_id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, g.name as gallery_name, g.type as gallery_type, g.folder_id, u.username as owner_name 
    FROM photos p 
    JOIN galleries g ON p.gallery_id = g.id 
    JOIN users u ON g.user_id = u.id 
    WHERE p.id = ?
");
$stmt->execute([$photo_id]);
$photo = $stmt->fetch();

if (!$photo) {
    header("Location: index.php");
    exit;
}

// Access check
$current_user_id = $_SESSION[$lab_name]['user_id'] ?? null;
if ($photo['gallery_type'] === 'private' && $photo['user_id'] != $current_user_id) {
    header("Location: index.php");
    exit;
}

// Handle Comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment']) && isLoggedIn()) {
    $content = trim($_POST['comment_content'] ?? '');
    if ($content) {
        $stmt = $pdo->prepare("INSERT INTO comments (photo_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$photo_id, $current_user_id, $content]);
        header("Location: gallery.php?photo_id=" . $photo_id);
        exit;
    }
}

// Handle Rating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rate']) && isLoggedIn()) {
    $rating = intval($_POST['rating'] ?? 0);
    // Can only rate others' photos
    if ($photo['user_id'] != $current_user_id && $rating >= 1 && $rating <= 5) {
        $stmt = $pdo->prepare("INSERT INTO ratings (photo_id, user_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = ?");
        $stmt->execute([$photo_id, $current_user_id, $rating, $rating]);
        header("Location: gallery.php?photo_id=" . $photo_id);
        exit;
    }
}

$comments = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.photo_id = ? ORDER BY c.created_at ASC");
$comments->execute([$photo_id]);
$comments = $comments->fetchAll();

$avg_rating = getAverageRating($pdo, $photo_id);
$user_rating = isLoggedIn() ? getUserRating($pdo, $photo_id, $current_user_id) : 0;
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($photo['title']) ?> - <?= $lab_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .photo-container {
            text-align: center;
            background: rgba(0,0,0,0.5);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .photo-full {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .comment-box {
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .rating-star {
            font-size: 1.5rem;
            color: #555;
            cursor: pointer;
            transition: color 0.2s;
        }
        .rating-star.active {
            color: #ffc107;
        }
        .rating-star:hover {
            color: #ffdb70;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../shared/header.php'; ?>

    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php" class="text-info">Galerie</a></li>
                <li class="breadcrumb-item"><a href="index.php?gallery_id=<?= $photo['gallery_id'] ?>" class="text-info"><?= htmlspecialchars($photo['gallery_name']) ?></a></li>
                <li class="breadcrumb-item active text-white" aria-current="page"><?= htmlspecialchars($photo['title']) ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <div class="photo-container">
                    <img src="uploads/<?= htmlspecialchars($photo['owner_name']) ?>/<?= $photo['folder_id'] ?>/<?= $photo['filename'] ?>" class="photo-full" alt="<?= htmlspecialchars($photo['title']) ?>">
                    <div class="mt-3 text-white">
                        <h3><?= htmlspecialchars($photo['title']) ?></h3>
                        <p class="text-muted">Przesłane przez: <?= htmlspecialchars($photo['owner_name']) ?> | <?= $photo['created_at'] ?></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Rating Info -->
                <div class="glass-card mb-4 text-center">
                    <h5 class="text-white">Ocena: <?= $avg_rating ?> / 5 ⭐</h5>
                    
                    <?php if (isLoggedIn() && $photo['user_id'] != $current_user_id): ?>
                        <div class="mt-3">
                            <p class="text-muted small">Twoja ocena:</p>
                            <form method="POST" id="ratingForm">
                                <input type="hidden" name="rate" value="1">
                                <input type="hidden" name="rating" id="ratingInput" value="<?= $user_rating ?>">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <span class="rating-star <?= $i <= $user_rating ? 'active' : '' ?>" onclick="submitRating(<?= $i ?>)">★</span>
                                <?php endfor; ?>
                            </form>
                        </div>
                    <?php elseif (isLoggedIn() && $photo['user_id'] == $current_user_id): ?>
                        <p class="text-muted small mt-2">Nie możesz oceniać własnych zdjęć.</p>
                    <?php endif; ?>
                </div>

                <!-- Comments -->
                <div class="glass-card">
                    <h5 class="text-white mb-4">Komentarze (<?= count($comments) ?>)</h5>
                    
                    <div style="max-height: 400px; overflow-y: auto;" class="pe-2">
                        <?php if (empty($comments)): ?>
                            <p class="text-muted">Brak komentarzy.</p>
                        <?php else: ?>
                            <?php foreach ($comments as $c): ?>
                                <div class="comment-box">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold text-info"><?= htmlspecialchars($c['username']) ?></span>
                                        <span class="text-muted small"><?= date('H:i d.m.Y', strtotime($c['created_at'])) ?></span>
                                    </div>
                                    <div class="text-white small">
                                        <?= nl2br(htmlspecialchars($c['content'])) ?>
                                    </div>
                                    <?php if (isModerator()): ?>
                                        <div class="text-end mt-1">
                                            <a href="delete.php?type=comment&id=<?= $c['id'] ?>&photo_id=<?= $photo_id ?>" class="text-danger small" onclick="return confirm('Usunąć komentarz?')">Usuń</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if (isLoggedIn()): ?>
                        <hr class="text-white">
                        <form method="POST" class="mt-3">
                            <div class="mb-2">
                                <textarea name="comment_content" class="form-control form-control-glass small" rows="2" placeholder="Napisz komentarz..." required></textarea>
                            </div>
                            <button type="submit" name="add_comment" class="btn btn-primary btn-sm w-100">Dodaj komentarz</button>
                        </form>
                    <?php else: ?>
                        <p class="text-center text-muted small mt-3">
                            <a href="login.php" class="text-info">Zaloguj się</a>, aby dodać komentarz.
                        </p>
                    <?php endif; ?>
                </div>

                <?php if (isLoggedIn() && ($photo['user_id'] == $current_user_id || isModerator())): ?>
                    <div class="mt-4 text-center">
                        <a href="delete.php?type=photo&id=<?= $photo['id'] ?>&gallery_id=<?= $photo['gallery_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Czy na pewno chcesz usunąć to zdjęcie?')">Usuń zdjęcie</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../shared/footer.php'; ?>
    <script>
        function submitRating(val) {
            document.getElementById('ratingInput').value = val;
            document.getElementById('ratingForm').submit();
        }
    </script>
</body>
</html>
