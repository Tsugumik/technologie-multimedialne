<?php
if (!isset($lab_name))
    die();

$is_user_logged = isset($_SESSION[$lab_name]['user_id']);
$current_user = $is_user_logged ? $_SESSION[$lab_name]['username'] : '';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lab_title ?? $lab_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    
    <!-- Leaflet GIS Map Styles & Scripts (often needed globally) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        /* CSS Fix for issue #2: option tags in selects having white text on white bg */
        select.form-control-glass option {
            background-color: #203a43 !important;
            color: #ffffff !important;
        }
    </style>
</head>
<body class="bg-dark text-white d-flex flex-column" style="background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);">
    <nav class="navbar navbar-expand-lg navbar-dark border-bottom border-light border-opacity-10 py-3" style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="fs-4 d-inline-block me-2 align-middle">🧪</span> 
                <?php echo htmlspecialchars($lab_title ?? $lab_name); ?>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if ($is_user_logged): ?>
                        <li class="nav-item">
                            <span class="nav-link text-white me-3">Witaj, <strong class="text-info"><?php echo htmlspecialchars($current_user); ?></strong>!</span>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-light btn-sm rounded-pill px-4" href="logout.php">Wyloguj się</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <span class="nav-link text-white-50 me-3">Jesteś niezalogowany</span>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary-custom btn-sm rounded-pill px-4 text-decoration-none" href="login.php">Zaloguj się</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container flex-grow-1 d-flex flex-column py-5">
