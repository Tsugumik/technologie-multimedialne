<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$gallery_id = $_GET['gallery_id'] ?? null;
if (!$gallery_id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT g.*, u.username FROM galleries g JOIN users u ON g.user_id = u.id WHERE g.id = ?");
$stmt->execute([$gallery_id]);
$gallery = $stmt->fetch();

if (!$gallery || ($gallery['user_id'] != $_SESSION[$lab_name]['user_id'] && !isModerator())) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $filter = $_POST['filter'] ?? 'none';
    $file = $_FILES['photo'] ?? null;

    if ($title && $file && $file['error'] === 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($file['type'], $allowed)) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $targetDir = getGalleryDir($gallery['username'], $gallery['folder_id']);
            $targetPath = $targetDir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Apply filter
                applyFilter($targetPath, $filter);

                // Apply watermark if commercial
                if ($gallery['type'] === 'commercial') {
                    addWatermark($targetPath);
                }

                $stmt = $pdo->prepare("INSERT INTO photos (gallery_id, user_id, title, filename, original_filename) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$gallery_id, $_SESSION[$lab_name]['user_id'], $title, $filename, $file['name']]);

                header("Location: index.php?gallery_id=" . $gallery_id);
                exit;
            } else {
                $error = "Błąd podczas zapisywania pliku.";
            }
        } else {
            $error = "Niedozwolony format pliku (tylko JPG, PNG, GIF).";
        }
    } else {
        $error = "Wypełnij wszystkie pola i wybierz zdjęcie.";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj zdjęcie - <?= $lab_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../shared/header.php'; ?>

    <div class="container mt-4">
        <div class="glass-card mx-auto" style="max-width: 600px;">
            <h2 class="text-white mb-4">Dodaj zdjęcie do: <?= htmlspecialchars($gallery['name']) ?></h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label text-white">Tytuł zdjęcia</label>
                    <input type="text" name="title" class="form-control form-control-glass" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-white">Zdjęcie</label>
                    <input type="file" name="photo" class="form-control form-control-glass" accept="image/*" required>
                </div>
                <div class="mb-4">
                    <label class="form-label text-white">Filtr</label>
                    <select name="filter" class="form-select form-control-glass">
                        <option value="none">Brak</option>
                        <option value="greyscale">Odcienie szarości</option>
                        <option value="sepia">Sepia</option>
                        <option value="negative">Negatyw</option>
                        <option value="brightness">Jasność</option>
                        <option value="contrast">Kontrast</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">Prześlij</button>
                    <a href="index.php?gallery_id=<?= $gallery_id ?>" class="btn btn-outline-secondary">Anuluj</a>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../shared/footer.php'; ?>
</body>
</html>
