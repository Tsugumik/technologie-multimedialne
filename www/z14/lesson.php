<?php
session_start();
$db_name = 'z14';
require_once '../shared/config.php';

if (!isset($_SESSION['z14_role']) || ($_SESSION['z14_role'] !== 'pracownik' && $_SESSION['z14_role'] !== 'coach')) {
    header('Location: login.php');
    exit;
}

$idl = $_GET['id'] ?? null;
if (!$idl) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT l.*, c.login AS autor FROM lekcje l JOIN coach c ON l.idc = c.idc WHERE l.idl = ?');
$stmt->execute([$idl]);
$lekcja = $stmt->fetch();

if (!$lekcja) {
    die("Lekcja nie istnieje.");
}

$all_lekcje = $pdo->query('SELECT idl, nazwa FROM lekcje')->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lekcja['nazwa']) ?> - Z14</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .sidebar { min-height: calc(100vh - 100px); background: rgba(0,0,0,0.2); padding: 20px; border-radius: 10px; }
        .content { padding: 30px; }
        .lesson-link { color: white; text-decoration: none; display: block; padding: 10px; border-radius: 5px; margin-bottom: 5px; background: rgba(255,255,255,0.05); }
        .lesson-link:hover, .lesson-link.active { background: rgba(255,255,255,0.2); }
    </style>
</head>
<body class="py-4">
    <div class="container-fluid px-5">
        <div class="d-flex justify-content-between align-items-center mb-4 text-white">
            <h2>Lekcja: <?= htmlspecialchars($lekcja['nazwa']) ?></h2>
            <a href="<?= $_SESSION['z14_role'] == 'pracownik' ? 'pracownik.php' : 'coach.php' ?>" class="btn btn-outline-light">Powrót do panelu</a>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="sidebar glass-card">
                    <h4>Inne lekcje</h4>
                    <hr>
                    <?php foreach($all_lekcje as $l): ?>
                        <a href="lesson.php?id=<?= $l['idl'] ?>" class="lesson-link <?= $l['idl'] == $idl ? 'active' : '' ?>">
                            <?= htmlspecialchars($l['nazwa']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="glass-card content">
                    <h3 class="mb-3"><?= htmlspecialchars($lekcja['nazwa']) ?> <small class="text-muted fs-6">(Autor: <?= htmlspecialchars($lekcja['autor']) ?>)</small></h3>
                    <hr>
                    <div class="lesson-content bg-light text-dark p-4 rounded mb-4" style="font-size: 1.1rem;">
                        <?= $lekcja['tresc'] ?>
                    </div>
                    
                    <?php if ($lekcja['plik_multimedialny']): ?>
                        <div class="mt-4 p-3 bg-dark rounded text-center">
                            <h5>Załącznik multimedialny</h5>
                            <?php 
                            $ext = strtolower(pathinfo($lekcja['plik_multimedialny'], PATHINFO_EXTENSION));
                            $fileUrl = htmlspecialchars($lekcja['plik_multimedialny']);
                            if (in_array($ext, ['mp4', 'webm'])): ?>
                                <video width="100%" max-width="800" controls autoplay>
                                    <source src="<?= $fileUrl ?>" type="video/<?= $ext ?>">
                                    Twoja przeglądarka nie obsługuje tagu wideo.
                                </video>
                            <?php elseif (in_array($ext, ['mp3', 'wav'])): ?>
                                <audio controls autoplay class="w-100">
                                    <source src="<?= $fileUrl ?>" type="audio/<?= $ext ?>">
                                    Twoja przeglądarka nie obsługuje tagu audio.
                                </audio>
                            <?php elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                <img src="<?= $fileUrl ?>" alt="Załącznik" class="img-fluid rounded" style="max-height: 500px;">
                            <?php else: ?>
                                <a href="<?= $fileUrl ?>" class="btn btn-info" target="_blank">Pobierz plik</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-5 text-center">
                        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX" crossorigin="anonymous"></script>
                        <ins class="adsbygoogle"
                             style="display:block"
                             data-ad-client="ca-pub-XXXXXXXXXXXXXXXX"
                             data-ad-slot="XXXXXXXXXX"
                             data-ad-format="auto"
                             data-full-width-responsive="true"></ins>
                        <script>
                             (adsbygoogle = window.adsbygoogle || []).push({});
                        </script>
                        <small class="text-muted">Miejsce na Google AdSense</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>