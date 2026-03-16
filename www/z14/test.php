<?php
session_start();
$db_name = 'z14';
require_once '../shared/config.php';
require_once 'tfpdf.php';

if (!isset($_SESSION['z14_role']) || $_SESSION['z14_role'] !== 'pracownik') {
    header('Location: login.php');
    exit;
}

$idp = $_SESSION['z14_user'];
$idt = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idt = $_POST['idt'];
}

if (!$idt) {
    header('Location: pracownik.php');
    exit;
}

$test = $pdo->prepare('SELECT * FROM test WHERE idt = ?');
$test->execute([$idt]);
$test = $test->fetch();

if (!$test) {
    die("Test nie istnieje.");
}

$pytania = $pdo->prepare('SELECT * FROM pytania WHERE idt = ?');
$pytania->execute([$idt]);
$pytania = $pytania->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $punkty = 0;
    $max_punkty = count($pytania);
    $user_answers = $_POST['answers'] ?? [];

    $pdf = new tFPDF();
    $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
    $pdf->AddFont('DejaVu','B','DejaVuSansCondensed-Bold.ttf',true);
    $pdf->AddPage();
    $pdf->SetFont('DejaVu','B',16);
    
    // Użytkownik i info o teście
    $uzytkownik = $pdo->prepare('SELECT login FROM pracownik WHERE idp = ?');
    $uzytkownik->execute([$idp]);
    $login = $uzytkownik->fetchColumn();

    $coach = $pdo->prepare('SELECT login FROM coach WHERE idc = ?');
    $coach->execute([$test['idc']]);
    $coach_login = $coach->fetchColumn();

    $pdf->Cell(0, 10, $login, 0, 1);
    $pdf->SetFont('DejaVu','',12);
    $pdf->Cell(0, 10, $test['nazwa'] . ' (' . $coach_login . ')', 0, 1);
    $pdf->Cell(0, 10, date('Y-m-d H:i:s'), 0, 1);
    $pdf->Ln(5);

    foreach ($pytania as $index => $p) {
        $idpyt = $p['idpyt'];
        $ans = $user_answers[$idpyt] ?? [];
        
        $uA = isset($ans['a']);
        $uB = isset($ans['b']);
        $uC = isset($ans['c']);
        $uD = isset($ans['d']);

        $is_correct = ($uA == $p['a'] && $uB == $p['b'] && $uC == $p['c'] && $uD == $p['d']);
        if ($is_correct) {
            $punkty++;
        }

        $pdf->SetFont('DejaVu','B',12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(0, 8, ($index + 1) . '. ' . $p['tresc_pytania']);
        $pdf->SetFont('DejaVu','',12);

        $drawAnswer = function($letter, $text, $user_checked, $is_correct_ans) use ($pdf) {
            $check = $user_checked ? 'X' : '  '; // Puste miejsce symulujące pusty checkbox
            if ($user_checked) {
                if ($is_correct_ans) {
                    $pdf->SetTextColor(0, 128, 0); // Green
                } else {
                    $pdf->SetTextColor(255, 0, 0); // Red
                }
            } else {
                if ($is_correct_ans) {
                    $pdf->SetTextColor(0, 128, 0); // Green (should have been checked)
                } else {
                    $pdf->SetTextColor(0, 0, 0); // Black
                }
            }
            $pdf->Cell(10, 8, "[$check]", 0, 0);
            $pdf->MultiCell(0, 8, $text);
        };

        $drawAnswer('a', $p['odpowiedz_a'], $uA, $p['a']);
        $drawAnswer('b', $p['odpowiedz_b'], $uB, $p['b']);
        $drawAnswer('c', $p['odpowiedz_c'], $uC, $p['c']);
        $drawAnswer('d', $p['odpowiedz_d'], $uD, $p['d']);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);
    }

    $pdf->SetFont('DejaVu','B',14);
    $pdf->Cell(0, 10, "Wynik: $punkty / $max_punkty", 0, 1);

    if (!is_dir(__DIR__ . '/pdf')) mkdir(__DIR__ . '/pdf', 0777, true);
    $pdf_file = 'pdf/wynik_' . $idp . '_' . $idt . '_' . time() . '.pdf';
    $pdf->Output('F', __DIR__ . '/' . $pdf_file);

    $stmt = $pdo->prepare('INSERT INTO wyniki (idp, idt, datetime, punkty, plik_pdf) VALUES (?, ?, NOW(), ?, ?)');
    $stmt->execute([$idp, $idt, $punkty, $pdf_file]);

    header('Location: pracownik.php#wyniki');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test: <?= htmlspecialchars($test['nazwa']) ?> - Z14</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <script>
        <?php if ($test['max_time']): ?>
        let timeLeft = <?= $test['max_time'] ?>;
        function updateTimer() {
            let m = Math.floor(timeLeft / 60);
            let s = timeLeft % 60;
            document.getElementById('timer').innerText = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
            if (timeLeft <= 0) {
                document.getElementById('testForm').submit();
            } else {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            }
        }
        window.onload = updateTimer;
        <?php endif; ?>
    </script>
</head>
<body class="py-4">
    <div class="container glass-card" style="max-width: 800px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?= htmlspecialchars($test['nazwa']) ?></h2>
            <?php if ($test['max_time']): ?>
                <h3 class="text-danger" id="timer">--:--</h3>
            <?php endif; ?>
        </div>
        
        <form method="POST" id="testForm">
            <input type="hidden" name="idt" value="<?= $test['idt'] ?>">
            
            <?php foreach($pytania as $i => $p): ?>
                <div class="mb-4 p-3 bg-dark rounded border border-secondary">
                    <h5 class="mb-3"><?= ($i+1) ?>. <?= htmlspecialchars($p['tresc_pytania']) ?></h5>
                    <?php if ($p['plik_multimedialny']): ?>
                        <div class="mb-3">
                            <?php 
                            $ext = strtolower(pathinfo($p['plik_multimedialny'], PATHINFO_EXTENSION));
                            $fileUrl = htmlspecialchars($p['plik_multimedialny']);
                            if (in_array($ext, ['mp4', 'webm'])): ?>
                                <video width="100%" controls autoplay><source src="<?= $fileUrl ?>" type="video/<?= $ext ?>"></video>
                            <?php elseif (in_array($ext, ['mp3', 'wav'])): ?>
                                <audio controls autoplay class="w-100"><source src="<?= $fileUrl ?>" type="audio/<?= $ext ?>"></audio>
                            <?php elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                <img src="<?= $fileUrl ?>" alt="Załącznik" class="img-fluid rounded">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="answers[<?= $p['idpyt'] ?>][a]" value="1" id="q<?= $p['idpyt'] ?>a">
                        <label class="form-check-label" for="q<?= $p['idpyt'] ?>a"><?= htmlspecialchars($p['odpowiedz_a']) ?></label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="answers[<?= $p['idpyt'] ?>][b]" value="1" id="q<?= $p['idpyt'] ?>b">
                        <label class="form-check-label" for="q<?= $p['idpyt'] ?>b"><?= htmlspecialchars($p['odpowiedz_b']) ?></label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="answers[<?= $p['idpyt'] ?>][c]" value="1" id="q<?= $p['idpyt'] ?>c">
                        <label class="form-check-label" for="q<?= $p['idpyt'] ?>c"><?= htmlspecialchars($p['odpowiedz_c']) ?></label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="answers[<?= $p['idpyt'] ?>][d]" value="1" id="q<?= $p['idpyt'] ?>d">
                        <label class="form-check-label" for="q<?= $p['idpyt'] ?>d"><?= htmlspecialchars($p['odpowiedz_d']) ?></label>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn btn-success btn-lg w-100 mt-3">KONIEC</button>
        </form>
    </div>
</body>
</html>