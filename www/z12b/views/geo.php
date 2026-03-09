<?php
require_once 'models/Visitor.php';
$model = new Visitor($pdo);
$data = $model->getAll();
?>

<div class="mb-4">
    <h4>Rejestracja informacji o przeglądarce i geolokalizacji</h4>
    <p>Trwa zbieranie danych z Twojej przeglądarki i geolokalizacji (może pojawić się monit o zgodę)...</p>
    <div id="geo-status" class="alert alert-info" style="display: none;"></div>
</div>

<div class="table-responsive mt-4">
    <table class="table table-bordered table-striped" style="font-size: 0.9em;">
        <thead class="table-dark">
            <tr>
                <th>ID</th><th>IP</th><th>User-Agent</th><th>Rozdzielczość</th><th>Kolory</th><th>Ciasteczka</th><th>Lat</th><th>Lng</th><th>Data</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['ip_address']) ?></td>
                <td><small><?= htmlspecialchars(substr($row['user_agent'], 0, 50)) ?>...</small></td>
                <td><?= htmlspecialchars($row['resolution']) ?></td>
                <td><?= htmlspecialchars($row['color_depth']) ?> bit</td>
                <td><?= $row['cookies_enabled'] ? 'TAK' : 'NIE' ?></td>
                <td><?= htmlspecialchars($row['latitude'] ?? 'Brak') ?></td>
                <td><?= htmlspecialchars($row['longitude'] ?? 'Brak') ?></td>
                <td><?= htmlspecialchars($row['visited_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    if (localStorage.getItem('geo_registered_session')) {
        document.getElementById('geo-status').style.display = 'block';
        document.getElementById('geo-status').className = 'alert alert-secondary';
        document.getElementById('geo-status').textContent = 'Twoje dane zostały już zarejestrowane podczas tej wizyty.';
        return;
    }

    const visitorData = {
        resolution: window.screen.width + "x" + window.screen.height,
        color_depth: window.screen.colorDepth,
        cookies_enabled: navigator.cookieEnabled,
        latitude: null,
        longitude: null
    };

    const sendData = () => {
        fetch('geo_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(visitorData)
        })
        .then(res => res.json())
        .then(data => {
            const status = document.getElementById('geo-status');
            status.style.display = 'block';
            if (data.status === 'success') {
                status.className = 'alert alert-success';
                status.textContent = 'Dane zostały pomyślnie zarejestrowane. Odśwież stronę, aby zobaczyć nowy wpis.';
                localStorage.setItem('geo_registered_session', '1');
            } else {
                status.className = 'alert alert-danger';
                status.textContent = 'Wystąpił błąd podczas rejestracji danych.';
            }
        });
    };

    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                visitorData.latitude = position.coords.latitude;
                visitorData.longitude = position.coords.longitude;
                sendData();
            },
            (error) => {
                console.warn("Brak zgody na geolokalizację lub błąd:", error);
                sendData();
            }
        );
    } else {
        sendData();
    }
});
</script>
