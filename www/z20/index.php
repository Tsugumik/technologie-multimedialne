<?php
require_once 'config.php';

// Prepare search filters
$where = [];
$params = [];

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$type = $_GET['type'] ?? '';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$city = $_GET['city'] ?? '';

if (!empty($search)) {
    $where[] = "(a.title LIKE ? OR a.description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (!empty($category)) {
    $where[] = "a.category = ?";
    $params[] = $category;
}

if (!empty($type)) {
    $where[] = "a.type = ?";
    $params[] = $type;
}

if (!empty($price_min)) {
    $where[] = "a.price >= ?";
    $params[] = floatval($price_min);
}

if (!empty($price_max)) {
    $where[] = "a.price <= ?";
    $params[] = floatval($price_max);
}

if (!empty($city)) {
    $where[] = "a.city LIKE ?";
    $params[] = '%' . $city . '%';
}

$sql = "
    SELECT a.*, u.username, 
           (SELECT filename FROM photos WHERE announcement_id = a.id ORDER BY created_at DESC LIMIT 1) as main_photo 
    FROM announcements a 
    JOIN users u ON a.user_id = u.id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$announcements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lab_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    
    <!-- Leaflet GIS Map Styles & Scripts -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .sidebar {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            height: fit-content;
            border: 1px solid rgba(255,255,255,0.1);
        }
        #main-map {
            height: 400px;
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 25px;
        }
        .property-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            border-color: rgba(255,255,255,0.2);
        }
        .property-card img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .property-card-body {
            padding: 1.25rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .property-price {
            font-size: 1.25rem;
            color: #00c6ff;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../shared/header.php'; ?>

    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="sidebar text-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Filtrowanie ofert</h5>
                    <a href="index.php" class="text-info text-decoration-none small">Wyczyść</a>
                </div>
                
                <form method="GET">
                    <div class="mb-3">
                        <label class="form-label small">Szukaj frazy</label>
                        <input type="text" name="search" class="form-control form-control-glass form-control-sm" placeholder="np. taras, ogród" value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Kategoria</label>
                        <select name="category" class="form-select form-control-glass form-control-sm">
                            <option value="">Wszystkie</option>
                            <option value="mieszkanie" <?= $category === 'mieszkanie' ? 'selected' : '' ?>>Mieszkanie</option>
                            <option value="dom" <?= $category === 'dom' ? 'selected' : '' ?>>Dom</option>
                            <option value="dzialka_budowlana" <?= $category === 'dzialka_budowlana' ? 'selected' : '' ?>>Działka budowlana</option>
                            <option value="dzialka_rod" <?= $category === 'dzialka_rod' ? 'selected' : '' ?>>Działka ROD</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Typ oferty</label>
                        <select name="type" class="form-select form-control-glass form-control-sm">
                            <option value="">Wszystkie</option>
                            <option value="sprzedaz" <?= $type === 'sprzedaz' ? 'selected' : '' ?>>Sprzedaż</option>
                            <option value="wynajem" <?= $type === 'wynajem' ? 'selected' : '' ?>>Wynajem</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Miejscowość</label>
                        <input type="text" name="city" class="form-control form-control-glass form-control-sm" placeholder="np. Bydgoszcz" value="<?= htmlspecialchars($city) ?>">
                    </div>

                    <div class="row g-2 mb-4">
                        <label class="form-label small col-12 mb-0">Cena (PLN)</label>
                        <div class="col-6">
                            <input type="number" name="price_min" class="form-control form-control-glass form-control-sm" placeholder="Min" value="<?= htmlspecialchars($price_min) ?>">
                        </div>
                        <div class="col-6">
                            <input type="number" name="price_max" class="form-control form-control-glass form-control-sm" placeholder="Max" value="<?= htmlspecialchars($price_max) ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom btn-sm w-100 py-2">Filtruj ogłoszenia</button>
                </form>

                <hr class="text-white border-opacity-10 my-4">

                <?php if (isLoggedIn()): ?>
                    <a href="add.php" class="btn btn-success btn-sm w-100 py-2 fw-semibold">+ Dodaj ogłoszenie</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary btn-sm w-100 py-2">Zaloguj się, aby dodać</a>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                    <a href="admin.php" class="btn btn-outline-info btn-sm w-100 py-2 mt-2">Panel Administratora</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Map showing all properties -->
            <div class="glass-card p-3 mb-4">
                <h4 class="text-white mb-2">Interaktywna Mapa GIS</h4>
                <p class="text-muted-custom small">Wszystkie ogłoszenia spełniające kryteria wyszukiwania na interaktywnej mapie Polski.</p>
                <div id="main-map"></div>
            </div>

            <!-- Property Grid -->
            <div class="glass-card">
                <h3 class="text-white mb-4">Aktualne Oferty</h3>

                <?php if (empty($announcements)): ?>
                    <div class="text-center py-5 text-muted-custom">
                        <span style="font-size: 3rem;">🔍</span>
                        <p class="fs-5 mt-3 mb-0">Brak ogłoszeń spełniających wybrane kryteria.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($announcements as $ann): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="property-card">
                                    <a href="details.php?id=<?= $ann['id'] ?>">
                                        <?php if ($ann['main_photo']): ?>
                                            <img src="uploads/<?= htmlspecialchars($ann['main_photo']) ?>" alt="<?= htmlspecialchars($ann['title']) ?>">
                                        <?php else: ?>
                                            <div class="d-flex justify-content-center align-items-center bg-secondary text-white-50" style="height: 200px;">
                                                <span style="font-size: 3rem;">🏠</span>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                    <div class="property-card-body">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-primary"><?= getCategoryName($ann['category']) ?></span>
                                                <span class="badge bg-success"><?= getTypeName($ann['type']) ?></span>
                                            </div>
                                            <h5 class="text-white fw-bold mb-1 text-truncate">
                                                <a href="details.php?id=<?= $ann['id'] ?>" class="text-white text-decoration-none"><?= htmlspecialchars($ann['title']) ?></a>
                                            </h5>
                                            <p class="text-muted-custom small mb-3">📍 <?= htmlspecialchars($ann['postal_code']) ?> <?= htmlspecialchars($ann['city']) ?></p>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light border-opacity-10">
                                            <div class="property-price"><?= number_format($ann['price'], 2, ',', ' ') ?> PLN</div>
                                            <a href="details.php?id=<?= $ann['id'] ?>" class="btn btn-outline-info btn-sm">Szczegóły</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Leaflet GIS Map Interactive Script -->
    <script>
        // Init map centered on Poland
        var map = L.map('main-map').setView([52.069, 19.480], 6);

        // OSM Layers
        var streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var topoLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            maxZoom: 17,
            attribution: '© OpenTopoMap'
        });

        // Layer Control
        var baseMaps = {
            "Standardowa": streetLayer,
            "Topograficzna": topoLayer
        };
        L.control.layers(baseMaps).addTo(map);

        // Retrieve properties array for JS
        var properties = [
            <?php foreach ($announcements as $ann): ?>
            {
                id: <?= $ann['id'] ?>,
                title: <?= json_encode($ann['title']) ?>,
                price: <?= $ann['price'] ?>,
                lat: <?= $ann['latitude'] ?>,
                lng: <?= $ann['longitude'] ?>,
                category: <?= json_encode(getCategoryName($ann['category'])) ?>,
                type: <?= json_encode(getTypeName($ann['type'])) ?>,
                city: <?= json_encode($ann['city']) ?>,
                photo: <?= json_encode($ann['main_photo'] ? 'uploads/' . $ann['main_photo'] : null) ?>
            },
            <?php endforeach; ?>
        ];

        var markersGroup = L.featureGroup();

        properties.forEach(function(prop) {
            if (prop.lat && prop.lng) {
                var popupContent = '<div style="min-width: 150px;">';
                if (prop.photo) {
                    popupContent += '<img src="' + prop.photo + '" style="width: 100%; height: 80px; object-fit: cover; border-radius: 5px; margin-bottom: 5px;">';
                }
                popupContent += '<b>' + prop.title + '</b><br>';
                popupContent += '<span style="color: #0072ff; font-weight: bold;">' + parseFloat(prop.price).toLocaleString("pl-PL") + ' PLN</span><br>';
                popupContent += '<span class="badge bg-secondary" style="font-size: 0.75rem; margin-right: 5px;">' + prop.category + '</span>';
                popupContent += '<span class="badge bg-success" style="font-size: 0.75rem;">' + prop.type + '</span><br>';
                popupContent += '<div style="margin-top: 8px;"><a href="details.php?id=' + prop.id + '" class="btn btn-primary btn-sm text-white" style="font-size: 0.8rem; padding: 2px 8px; width: 100%; display: block; text-align: center;">Szczegóły &rarr;</a></div>';
                popupContent += '</div>';

                var marker = L.marker([prop.lat, prop.lng])
                    .bindPopup(popupContent);
                
                markersGroup.addLayer(marker);
            }
        });

        markersGroup.addTo(map);

        // Autofit map zoom to markers if there are any
        if (properties.length > 0) {
            map.fitBounds(markersGroup.getBounds(), {padding: [30, 30]});
            if (map.getZoom() > 15) {
                map.setZoom(15);
            }
        }
    </script>
</body>
</html>
