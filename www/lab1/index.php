<?php
$lab_name = 'lab1';
$db_name = 'lab1_db';
$lab_title = 'Laboratorium 1';

require_once '../shared/auth.php';
require_once '../shared/config.php';
require_once '../shared/header.php';
?>

<div class="row align-items-center justify-content-center flex-grow-1">
    <div class="col-md-9 text-center">
        <div class="glass-card shadow-lg p-5">
            <div class="mb-4" style="font-size: 5rem;">🔬</div>
            <h1 class="display-4 fw-bold mb-4">Witaj Świecie!</h1>
            <p class="lead text-muted-custom mb-5 fs-4">Zalogowano pomyślnie. To jest widok dedykowany dla <strong>Lab 1</strong>.</p>
            
            <div class="alert bg-success bg-opacity-10 border border-success border-opacity-25 text-white p-4 rounded-4 mb-4">
                <i class="fs-4 mb-2 d-block">✓</i>
                Obecnie korzystasz z w 100% odizolowanej bazy danych:<br>
                <code class="fs-5 text-info mt-2 d-inline-block bg-dark bg-opacity-25 px-3 py-1 rounded"><?php echo htmlspecialchars($db_name); ?></code>
            </div>
            
            <p class="mt-4 text-muted-custom">
                Twoja sesja jest bezpieczna i ograniczona tylko do tego modułu. Możesz dodawać własne skrypty PHP wewnątrz katalogu <code>/lab1/</code>, które automatycznie skorzystają z przygotowanego uwierzytelniania.
            </p>
        </div>
    </div>
</div>

<?php require_once '../shared/footer.php'; ?>
