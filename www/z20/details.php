<?php
require_once 'config.php';

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    header("Location: index.php");
    exit;
}

// Fetch announcement details
$stmt = $pdo->prepare("
    SELECT a.*, u.username 
    FROM announcements a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.id = ?
");
$stmt->execute([$id]);
$announcement = $stmt->fetch();

if (!$announcement) {
    header("Location: index.php");
    exit;
}

// Fetch photos
$stmt_p = $pdo->prepare("SELECT * FROM photos WHERE announcement_id = ? ORDER BY created_at DESC");
$stmt_p->execute([$id]);
$photos = $stmt_p->fetchAll();

$current_user_id = $_SESSION[$lab_name]['user_id'] ?? null;
$can_manage = isLoggedIn() && ($announcement['user_id'] == $current_user_id || isModerator());
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($announcement['title']) ?> - <?= htmlspecialchars($lab_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    
    <!-- Leaflet GIS Map Styles & Scripts -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #detail-map {
            height: 350px;
            border-radius: 0.75rem;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .carousel-item img {
            height: 450px;
            object-fit: cover;
            border-radius: 1rem;
        }
        .gis-card {
            background: rgba(0, 198, 255, 0.05);
            border: 1px solid rgba(0, 198, 255, 0.2);
            border-radius: 1rem;
            padding: 1.5rem;
        }
        .carousel-container {
            position: relative;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>

    <div class="row">
        <!-- Left Side: Images & Description -->
        <div class="col-lg-7">
            <!-- Image Carousel -->
            <div class="carousel-container mb-4">
                <?php if (empty($photos)): ?>
                    <div class="d-flex justify-content-center align-items-center bg-secondary text-white-50" style="height: 450px; border-radius: 1rem;">
                        <div class="text-center">
                            <span style="font-size: 4rem;">🏠</span>
                            <p class="mt-2">Brak zdjęć dla tego ogłoszenia</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div id="propertyCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <?php foreach ($photos as $index => $photo): ?>
                                <button type="button" data-bs-target="#propertyCarousel" data-bs-slide-to="<?= $index ?>" class="<?= $index === 0 ? 'active' : '' ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="carousel-inner">
                            <?php foreach ($photos as $index => $photo): ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <img src="uploads/<?= htmlspecialchars($photo['filename']) ?>" class="d-block w-100" alt="Zdjęcie nieruchomości">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Title & Details -->
            <div class="glass-card mb-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <span class="badge bg-primary mb-2 me-2"><?= getCategoryName($announcement['category']) ?></span>
                        <span class="badge bg-success mb-2"><?= getTypeName($announcement['type']) ?></span>
                        <h1 class="text-white fw-bold h2 mb-1"><?= htmlspecialchars($announcement['title']) ?></h1>
                        <p class="text-muted-custom small mb-0">Dodano przez: <strong><?= htmlspecialchars($announcement['username']) ?></strong> | W dniu: <?= $announcement['created_at'] ?></p>
                    </div>
                    <div class="text-end">
                        <h2 class="text-info fw-bold mb-0"><?= number_format($announcement['price'], 2, ',', ' ') ?> PLN</h2>
                    </div>
                </div>

                <hr class="text-white border-opacity-10 my-4">

                <h4 class="text-white mb-3">Opis nieruchomości</h4>
                <p class="text-muted-custom" style="white-space: pre-wrap; font-size: 1.1rem; line-height: 1.6;"><?= htmlspecialchars($announcement['description']) ?></p>

                <?php if ($can_manage): ?>
                    <div class="mt-4 pt-3 border-top border-light border-opacity-10 text-end">
                        <a href="delete.php?type=announcement&id=<?= $announcement['id'] ?>" class="btn btn-danger" onclick="return confirm('Czy na pewno chcesz usunąć to ogłoszenie?')">Usuń ogłoszenie</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Side: Location & GIS Links -->
        <div class="col-lg-5">
            <!-- Map Details -->
            <div class="glass-card mb-4">
                <h4 class="text-white mb-3">Lokalizacja na mapie</h4>
                <div class="mb-3">
                    <strong>Adres:</strong> 
                    <span class="text-muted-custom">
                        <?= htmlspecialchars($announcement['postal_code']) ?> <?= htmlspecialchars($announcement['city']) ?><?= $announcement['street'] ? ', ul. ' . htmlspecialchars($announcement['street']) : '' ?>
                    </span>
                </div>
                <div id="detail-map" class="mb-3"></div>
                <div class="row text-center text-muted-custom small">
                    <div class="col-6">
                        <strong>Lat (Szerokość):</strong><br><span id="lat-val"><?= $announcement['latitude'] ?></span>
                    </div>
                    <div class="col-6">
                        <strong>Lng (Długość):</strong><br><span id="lng-val"><?= $announcement['longitude'] ?></span>
                    </div>
                </div>
            </div>

            <!-- ZIP Code Details (Dynamic from API) -->
            <div class="glass-card mb-4" id="postal-details-card" style="display: none;">
                <h4 class="text-white mb-3"><span class="fs-5 me-2">📮</span>Informacje o kodzie pocztowym</h4>
                <div id="postal-details-content" class="text-muted-custom">
                    <!-- Loaded dynamically via JS -->
                </div>
            </div>

            <!-- GIS Panel -->
            <div class="gis-card mb-4">
                <h4 class="text-info mb-3">📍 Systemy Informacji Przestrzennej (GIS)</h4>
                <p class="text-white-50 small mb-4">Skorzystaj z zewnętrznych kompozycji mapowych do analizy uzbrojenia terenu, granic działek, cen oraz zagrożeń środowiskowych dla tej nieruchomości:</p>
                
                <div class="d-grid gap-3">
                    <a href="https://geoportal-krajowy.pl/na-mapie#x=<?= $announcement['longitude'] ?>&y=<?= $announcement['latitude'] ?>&z=17" target="_blank" class="btn btn-outline-info text-start d-flex justify-content-between align-items-center py-2.5 px-3 rounded-3">
                        <span>🗺️ <strong>Geoportal Krajowy</strong> (granice działek, uzbrojenie)</span>
                        <span class="small">&rarr;</span>
                    </a>
                    
                    <a href="https://geoportal360.pl/map/#clk=<?= $announcement['longitude'] ?>,<?= $announcement['latitude'] ?>,17" target="_blank" class="btn btn-outline-info text-start d-flex justify-content-between align-items-center py-2.5 px-3 rounded-3">
                        <span>🛰️ <strong>Geoportal 360</strong> (ceny transakcyjne, topografia)</span>
                        <span class="small">&rarr;</span>
                    </a>

                    <a href="https://polska.e-mapa.net/?x=<?= $announcement['longitude'] ?>&y=<?= $announcement['latitude'] ?>&z=17" target="_blank" class="btn btn-outline-info text-start d-flex justify-content-between align-items-center py-2.5 px-3 rounded-3">
                        <span>📊 <strong>polska.e-mapa.net</strong> (rejestr cen, plany zagospodarowania)</span>
                        <span class="small">&rarr;</span>
                    </a>

                    <a href="https://polska.geoportal2.pl/map/www/mapa.php?mapa=polska&x=<?= $announcement['longitude'] ?>&y=<?= $announcement['latitude'] ?>&z=17" target="_blank" class="btn btn-outline-warning text-start d-flex justify-content-between align-items-center py-2.5 px-3 rounded-3">
                        <span>🌊 <strong>Mapa zagrożenia powodziowego</strong> (bezpieczeństwo)</span>
                        <span class="small">&rarr;</span>
                    </a>

                    <a href="https://powietrze.gios.gov.pl/pjp/current?lat=<?= $announcement['latitude'] ?>&lon=<?= $announcement['longitude'] ?>" target="_blank" class="btn btn-outline-success text-start d-flex justify-content-between align-items-center py-2.5 px-3 rounded-3">
                        <span>🍃 <strong>Jakość powietrza (GIOŚ)</strong> (ekologia, smog)</span>
                        <span class="small">&rarr;</span>
                    </a>
                    
                    <a href="https://kodpocztowy.intami.pl/index.html" target="_blank" class="btn btn-outline-light text-start d-flex justify-content-between align-items-center py-2.5 px-3 rounded-3">
                        <span>✉️ <strong>Baza kodów pocztowych Poczty Polskiej</strong></span>
                        <span class="small">&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet Detail Map Script -->
    <script>
        var lat = <?= $announcement['latitude'] ?>;
        var lng = <?= $announcement['longitude'] ?>;

        var map = L.map('detail-map').setView([lat, lng], 15);

        // OSM Layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Marker
        var marker = L.marker([lat, lng]).addTo(map)
            .bindPopup("<b><?= htmlspecialchars($announcement['title']) ?></b><br><?= number_format($announcement['price'], 2, ',', ' ') ?> PLN")
            .openPopup();

        // Load postal code data dynamically
        var postalCode = '<?= htmlspecialchars($announcement['postal_code']) ?>';
        fetch('postal_proxy.php?code=' + encodeURIComponent(postalCode))
            .then(res => res.json())
            .then(data => {
                if (data && data.length > 0) {
                    var card = document.getElementById('postal-details-card');
                    var content = document.getElementById('postal-details-content');
                    card.style.display = 'block';
                    
                    var html = '<ul class="list-unstyled mb-0">';
                    html += '<li class="mb-2"><strong>Województwo:</strong> ' + (data[0].wojewodztwo || 'Brak danych') + '</li>';
                    html += '<li class="mb-2"><strong>Powiat:</strong> ' + (data[0].powiat || 'Brak danych') + '</li>';
                    html += '<li class="mb-2"><strong>Gmina:</strong> ' + (data[0].gmina || 'Brak danych') + '</li>';
                    if (data[0].ulica) {
                        html += '<li class="mb-2"><strong>Zakres ulic:</strong> ' + data[0].ulica + '</li>';
                    }
                    html += '</ul>';
                    content.innerHTML = html;
                }
            })
            .catch(err => console.error("Error loading postal code details", err));
    </script>

    <!-- Include Bootstrap JS from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
