<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';
    $type = $_POST['type'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $postal_code = trim($_POST['postal_code'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $user_id = $_SESSION[$lab_name]['user_id'];

    if (empty($title) || empty($description) || empty($category) || empty($type) || $price <= 0 || empty($postal_code) || empty($city) || $latitude === 0.0 || $longitude === 0.0) {
        $error = "Wypełnij wszystkie pola i zaznacz lokalizację na mapie.";
    } elseif (!preg_match('/^[0-9]{2}-[0-9]{3}$/', $postal_code)) {
        $error = "Kod pocztowy musi mieć format XX-XXX.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO announcements (user_id, title, description, category, type, price, postal_code, city, street, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $description, $category, $type, $price, $postal_code, $city, $street, $latitude, $longitude]);
            $announcement_id = $pdo->lastInsertId();

            // Handle file uploads
            if (!empty($_FILES['photos']['name'][0])) {
                $files = $_FILES['photos'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $uploads_dir = getUploadsDir();

                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $file_type = $files['type'][$i];
                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception("Niedozwolony format pliku. Dozwolone: JPG, PNG, GIF.");
                    }

                    $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                    $new_filename = uniqid('img_', true) . '.' . $ext;
                    $target_path = $uploads_dir . '/' . $new_filename;

                    if (move_uploaded_file($files['tmp_name'][$i], $target_path)) {
                        // Apply watermark
                        addWatermark($target_path);

                        // Save database record
                        $stmt_p = $pdo->prepare("INSERT INTO photos (announcement_id, filename, original_filename) VALUES (?, ?, ?)");
                        $stmt_p->execute([$announcement_id, $new_filename, $files['name'][$i]]);
                    } else {
                        throw new Exception("Błąd podczas zapisywania przesłanych zdjęć.");
                    }
                }
            }

            $pdo->commit();
            header("Location: details.php?id=" . $announcement_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj Ogłoszenie - <?= htmlspecialchars($lab_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    
    <!-- Leaflet GIS Map Styles & Scripts -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map {
            height: 350px;
            border-radius: 0.75rem;
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../shared/header.php'; ?>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="glass-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-light border-opacity-10 pb-3">
                    <h2 class="text-white mb-0">Dodaj Nowe Ogłoszenie</h2>
                    <a href="index.php" class="btn btn-outline-light btn-sm">Powrót do listy</a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Left column: Details -->
                        <div class="col-md-6">
                            <h4 class="text-info mb-3">Dane ogólne</h4>
                            
                            <div class="mb-3">
                                <label class="form-label text-white">Tytuł ogłoszenia</label>
                                <input type="text" name="title" class="form-control form-control-glass" placeholder="np. Słoneczne mieszkanie blisko centrum" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-white">Kategoria</label>
                                    <select name="category" class="form-select form-control-glass" required>
                                        <option value="">Wybierz...</option>
                                        <option value="mieszkanie" <?= ($_POST['category'] ?? '') === 'mieszkanie' ? 'selected' : '' ?>>Mieszkanie</option>
                                        <option value="dom" <?= ($_POST['category'] ?? '') === 'dom' ? 'selected' : '' ?>>Dom</option>
                                        <option value="dzialka_budowlana" <?= ($_POST['category'] ?? '') === 'dzialka_budowlana' ? 'selected' : '' ?>>Działka budowlana</option>
                                        <option value="dzialka_rod" <?= ($_POST['category'] ?? '') === 'dzialka_rod' ? 'selected' : '' ?>>Działka ROD</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-white">Typ ogłoszenia</label>
                                    <select name="type" class="form-select form-control-glass" required>
                                        <option value="">Wybierz...</option>
                                        <option value="sprzedaz" <?= ($_POST['type'] ?? '') === 'sprzedaz' ? 'selected' : '' ?>>Sprzedaż</option>
                                        <option value="wynajem" <?= ($_POST['type'] ?? '') === 'wynajem' ? 'selected' : '' ?>>Wynajem</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-white">Cena (PLN)</label>
                                <input type="number" step="0.01" name="price" class="form-control form-control-glass" placeholder="np. 450000" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-white">Opis nieruchomości</label>
                                <textarea name="description" class="form-control form-control-glass" rows="5" placeholder="Wprowadź szczegóły dotyczące nieruchomości..." required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-white">Zdjęcia nieruchomości (można wybrać kilka)</label>
                                <input type="file" name="photos[]" class="form-control form-control-glass" multiple accept="image/*">
                                <small class="text-muted-custom">Dozwolone formaty: JPG, PNG, GIF. Znak wodny zostanie dodany automatycznie.</small>
                            </div>
                        </div>

                        <!-- Right column: Location & GIS -->
                        <div class="col-md-6">
                            <h4 class="text-info mb-3">Lokalizacja & GIS</h4>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-white">Kod pocztowy</label>
                                    <input type="text" id="postal_code" name="postal_code" class="form-control form-control-glass" placeholder="00-000" required value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
                                    <div id="postal_loader" class="spinner-border spinner-border-sm text-info mt-1 d-none" role="status"></div>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="form-label text-white">Miejscowość / Miasto</label>
                                    <input type="text" id="city" name="city" class="form-control form-control-glass" required value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-white">Ulica / Adres (opcjonalnie)</label>
                                <input type="text" id="street" name="street" class="form-control form-control-glass" placeholder="np. Sejmowa 2" value="<?= htmlspecialchars($_POST['street'] ?? '') ?>">
                            </div>

                            <div class="mb-2">
                                <span class="text-white d-block mb-1">Zaznacz lokalizację na mapie:</span>
                                <div id="map"></div>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label text-white small">Szerokość geogr. (Lat)</label>
                                    <input type="text" id="latitude" name="latitude" class="form-control form-control-glass" readonly required value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label text-white small">Długość geogr. (Lng)</label>
                                    <input type="text" id="longitude" name="longitude" class="form-control form-control-glass" readonly required value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 border-top border-light border-opacity-10 pt-3 text-end">
                        <button type="submit" class="btn btn-primary-custom px-5 py-2 fs-5">Zapisz i opublikuj ogłoszenie</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Leaflet & Map Interactivity Script -->
    <script>
        // Init map centered on Poland
        var defaultLat = 52.069;
        var defaultLng = 19.480;
        var defaultZoom = 6;

        // If coordinates already filled in form (from validation reload)
        var formLat = parseFloat(document.getElementById('latitude').value);
        var formLng = parseFloat(document.getElementById('longitude').value);
        
        var hasMarker = false;
        if (!isNaN(formLat) && !isNaN(formLng) && formLat !== 0 && formLng !== 0) {
            defaultLat = formLat;
            defaultLng = formLng;
            defaultZoom = 14;
            hasMarker = true;
        }

        // Initialize Map
        var map = L.map('map').setView([defaultLat, defaultLng], defaultZoom);

        // Add tile layer
        var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var marker;
        if (hasMarker) {
            marker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(map);
            bindMarkerEvents(marker);
        }

        // Click on map to place pin
        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;
            
            updateCoordsFields(lat, lng);

            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, {draggable: true}).addTo(map);
                bindMarkerEvents(marker);
            }
        });

        function bindMarkerEvents(m) {
            m.on('dragend', function(e) {
                var latlng = e.target.getLatLng();
                updateCoordsFields(latlng.lat, latlng.lng);
            });
        }

        function updateCoordsFields(lat, lng) {
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);
        }

        // Postal code change triggers dynamic search
        document.getElementById('postal_code').addEventListener('change', function() {
            var code = this.value.trim();
            if (/^[0-9]{2}-[0-9]{3}$/.test(code)) {
                var loader = document.getElementById('postal_loader');
                loader.classList.remove('d-none');
                
                fetch('postal_proxy.php?code=' + encodeURIComponent(code))
                    .then(response => response.json())
                    .then(data => {
                        loader.classList.add('d-none');
                        if (data && data.length > 0) {
                            // Take first match
                            var cityVal = data[0].miejscowosc || data[0].gmina;
                            if (cityVal) {
                                document.getElementById('city').value = cityVal;
                                
                                // Geocode the city via OSM Nominatim API to place the map marker automatically
                                fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(cityVal + ', Polska'))
                                    .then(res => res.json())
                                    .then(geoData => {
                                        if (geoData && geoData.length > 0) {
                                            var lat = parseFloat(geoData[0].lat);
                                            var lng = parseFloat(geoData[0].lon);
                                            
                                            map.setView([lat, lng], 13);
                                            
                                            updateCoordsFields(lat, lng);
                                            
                                            if (marker) {
                                                marker.setLatLng([lat, lng]);
                                            } else {
                                                marker = L.marker([lat, lng], {draggable: true}).addTo(map);
                                                bindMarkerEvents(marker);
                                            }
                                        }
                                    });
                            }
                        }
                    })
                    .catch(err => {
                        loader.classList.add('d-none');
                    });
            }
        });
    </script>
</body>
</html>
