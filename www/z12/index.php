<?php
$lab_name = 'z12';
$lab_title = 'Zadanie 12';
$db_name = 'z12_db';
require_once '../shared/config.php';
require_once '../shared/auth.php';
require_once 'controllers/MeasurementController.php';

$controller = new MeasurementController($pdo);
$error = $controller->handlePost();

$page = $_GET['page'] ?? 'scada';

require_once '../shared/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">Moduł SCADA (Zadanie 12)</h2>
    
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $page === 'scada' ? 'active' : '' ?>" href="?page=scada">Wizualizacja (SCADA)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page === 'form' ? 'active' : '' ?>" href="?page=form">Formularz (Symulator)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page === 'table' ? 'active' : '' ?>" href="?page=table">Tabela Danych</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $page === 'geo' ? 'active' : '' ?>" href="?page=geo">Informacje o Gościach</a>
        </li>
    </ul>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Dane zostały poprawnie zapisane.</div>
    <?php endif; ?>

    <div class="view-content">
        <?php
        $viewPath = "views/{$page}.php";
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "<p>Nie znaleziono widoku: " . htmlspecialchars($page) . "</p>";
        }
        ?>
    </div>
</div>

<?php require_once '../shared/footer.php'; ?>
