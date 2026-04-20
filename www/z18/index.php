<?php
require_once 'config.php';

$current_user_id = $_SESSION[$lab_name]['user_id'] ?? null;
$selected_gallery_id = $_GET['gallery_id'] ?? null;

// Get available galleries
// Users see: all public, all commercial, and their own private
// Guests see: all public and all commercial
$sql = "SELECT g.*, u.username FROM galleries g JOIN users u ON g.user_id = u.id WHERE g.type != 'private'";
if ($current_user_id) {
    $sql .= " OR g.user_id = " . intval($current_user_id);
}
$sql .= " ORDER BY g.created_at DESC";

$galleries = $pdo->query($sql)->fetchAll();

$selected_gallery = null;
$photos = [];
if ($selected_gallery_id) {
    $stmt = $pdo->prepare("SELECT g.*, u.username FROM galleries g JOIN users u ON g.user_id = u.id WHERE g.id = ?");
    $stmt->execute([$selected_gallery_id]);
    $selected_gallery = $stmt->fetch();

    if ($selected_gallery) {
        // Access check
        if ($selected_gallery['type'] === 'private' && $selected_gallery['user_id'] != $current_user_id) {
            $selected_gallery = null;
        } else {
            $stmt = $pdo->prepare("SELECT * FROM photos WHERE gallery_id = ? ORDER BY created_at DESC");
            $stmt->execute([$selected_gallery_id]);
            $photos = $stmt->fetchAll();
        }
    }
}

// Handle adding gallery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_gallery']) && isLoggedIn()) {
    $name = trim($_POST['gallery_name'] ?? '');
    $type = $_POST['gallery_type'] ?? 'public';
    
    if ($name) {
        // Get next folder_id for this user
        $stmt = $pdo->prepare("SELECT MAX(folder_id) as max_id FROM galleries WHERE user_id = ?");
        $stmt->execute([$current_user_id]);
        $row = $stmt->fetch();
        $folder_id = ($row['max_id'] ?? 0) + 1;
        
        $stmt = $pdo->prepare("INSERT INTO galleries (user_id, name, type, folder_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$current_user_id, $name, $type, $folder_id]);
        
        // Create directory
        getGalleryDir($_SESSION[$lab_name]['username'], $folder_id);
        
        header("Location: index.php?gallery_id=" . $pdo->lastInsertId());
        exit;
    }
}
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
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            transition: transform 0.3s;
            aspect-ratio: 1/1;
        }
        .gallery-item:hover {
            transform: scale(1.02);
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .gallery-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 10px;
            font-size: 0.9rem;
        }
        .sidebar {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            height: fit-content;
        }
        .gallery-link {
            display: block;
            padding: 10px;
            color: #ddd;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 5px;
            transition: background 0.2s;
        }
        .gallery-link:hover, .gallery-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .badge-type {
            font-size: 0.7rem;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../shared/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 mb-4">
                <div class="sidebar">
                    <h5 class="text-white mb-3">Galerie</h5>
                    <div class="gallery-list">
                        <?php foreach ($galleries as $g): ?>
                            <a href="index.php?gallery_id=<?= $g['id'] ?>" class="gallery-link <?= $selected_gallery_id == $g['id'] ? 'active' : '' ?>">
                                <?= htmlspecialchars($g['name']) ?>
                                <span class="badge bg-secondary badge-type"><?= $g['type'] ?></span>
                                <div class="small text-muted">by <?= htmlspecialchars($g['username']) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php if (isLoggedIn()): ?>
                        <hr class="text-white">
                        <button class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#addGalleryModal">
                            + Nowa galeria
                        </button>
                    <?php endif; ?>

                    <?php if (isAdmin()): ?>
                        <hr class="text-white">
                        <a href="admin.php" class="btn btn-outline-info btn-sm w-100">Panel Admina</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Content -->
            <div class="col-md-9">
                <div class="glass-card">
                    <?php if ($selected_gallery): ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="text-white mb-0"><?= htmlspecialchars($selected_gallery['name']) ?></h2>
                                <p class="text-muted-custom">Właściciel: <?= htmlspecialchars($selected_gallery['username']) ?> | Typ: <?= $selected_gallery['type'] ?></p>
                            </div>
                            <?php if (isLoggedIn() && ($selected_gallery['user_id'] == $current_user_id || isModerator())): ?>
                                <div>
                                    <a href="upload.php?gallery_id=<?= $selected_gallery['id'] ?>" class="btn btn-primary btn-sm">Dodaj zdjęcie</a>
                                    <a href="delete.php?type=gallery&id=<?= $selected_gallery['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Czy na pewno chcesz usunąć całą galerię?')">Usuń galerię</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (empty($photos)): ?>
                            <p class="text-center text-muted-custom py-5">Ta galeria jest jeszcze pusta.</p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($photos as $p): ?>
                                    <div class="col-md-4 col-sm-6">
                                        <a href="gallery.php?photo_id=<?= $p['id'] ?>" class="text-decoration-none">
                                            <div class="gallery-item">
                                                <img src="uploads/<?= htmlspecialchars($selected_gallery['username']) ?>/<?= $selected_gallery['folder_id'] ?>/<?= $p['filename'] ?>" alt="<?= htmlspecialchars($p['title']) ?>">
                                                <div class="gallery-info">
                                                    <div class="fw-bold"><?= htmlspecialchars($p['title']) ?></div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>⭐ <?= getAverageRating($pdo, $p['id']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <h3 class="text-white">Witaj w Galerii Zdjęć</h3>
                            <p class="text-muted-custom">Wybierz galerię z menu po lewej stronie, aby zobaczyć zdjęcia.</p>
                            <?php if (!isLoggedIn()): ?>
                                <a href="login.php" class="btn btn-primary">Zaloguj się, aby tworzyć własne galerie</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Gallery Modal -->
    <div class="modal fade" id="addGalleryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white border-secondary">
                <form method="POST">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title">Dodaj nową galerię</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nazwa galerii</label>
                            <input type="text" name="gallery_name" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Typ galerii</label>
                            <select name="gallery_type" class="form-select bg-dark text-white border-secondary">
                                <option value="public">Publiczna (widoczna dla wszystkich)</option>
                                <option value="private">Prywatna (tylko dla Ciebie)</option>
                                <option value="commercial">Komercyjna (widoczna ze znakiem wodnym)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" name="add_gallery" class="btn btn-primary">Utwórz</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../shared/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
