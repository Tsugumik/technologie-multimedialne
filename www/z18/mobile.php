<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION[$lab_name]['user_id'];
$galleries = $pdo->prepare("SELECT * FROM galleries WHERE user_id = ? ORDER BY created_at DESC");
$galleries->execute([$current_user_id]);
$galleries = $galleries->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gallery_id = $_POST['gallery_id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $file = $_FILES['photo'] ?? null;

    if ($gallery_id && $title && $file && $file['error'] === 0) {
        // Fetch gallery info for directory
        $stmt = $pdo->prepare("SELECT * FROM galleries WHERE id = ? AND user_id = ?");
        $stmt->execute([$gallery_id, $current_user_id]);
        $gallery = $stmt->fetch();

        if ($gallery) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($file['type'], $allowed)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $targetDir = getGalleryDir($_SESSION[$lab_name]['username'], $gallery['folder_id']);
                $targetPath = $targetDir . '/' . $filename;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    if ($gallery['type'] === 'commercial') {
                        addWatermark($targetPath);
                    }

                    $stmt = $pdo->prepare("INSERT INTO photos (gallery_id, user_id, title, filename, original_filename) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$gallery_id, $current_user_id, $title, $filename, $file['name']]);
                    $success = "Zdjęcie zostało dodane!";
                } else {
                    $error = "Błąd zapisu.";
                }
            } else {
                $error = "Zły format.";
            }
        }
    } else {
        $error = "Wypełnij wszystkie pola.";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Upload - <?= $lab_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        body { background: #121212; color: white; }
        .mobile-card {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="mobile-card">
            <h3 class="text-center mb-4">📱 Szybkie dodawanie</h3>
            
            <?php if ($success): ?>
                <div class="alert alert-success small"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger small"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label small">Wybierz galerię</label>
                    <select name="gallery_id" class="form-select form-control-glass" required>
                        <?php foreach ($galleries as $g): ?>
                            <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Tytuł</label>
                    <input type="text" name="title" class="form-control form-control-glass" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small">Zdjęcie</label>
                    <input type="file" name="photo" class="form-control form-control-glass" accept="image/*" capture="environment" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">DODAJ ZDJĘCIE</button>
            </form>
            
            <div class="text-center mt-4">
                <a href="index.php" class="text-info text-decoration-none small">Wróć do pełnej wersji</a>
            </div>
        </div>
    </div>
</body>
</html>
