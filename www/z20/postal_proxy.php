<?php
header('Content-Type: application/json; charset=utf-8');

$code = $_GET['code'] ?? '';
if (!preg_match('/^[0-9]{2}-[0-9]{3}$/', $code)) {
    echo json_encode(['error' => 'Niepoprawny format kodu pocztowego.']);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://kodpocztowy.intami.pl/api/" . urlencode($code));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 && $response) {
    echo $response;
} else {
    echo json_encode(['error' => 'Nie znaleziono kodu lub błąd API.']);
}
?>
