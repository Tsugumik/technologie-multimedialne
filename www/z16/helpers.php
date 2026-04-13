<?php
require_once __DIR__ . '/autoloader.php';
use A2Design\AIML\AIML;

function logLogin($pdo, $id_cms, $username, $success) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO login_history (id_cms, username, ip_address, success) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_cms, $username, $ip, $success ? 1 : 0]);
}

function getChatbotResponse($pdo, $id_cms, $question, $cms_data) {
    $aiml_file = __DIR__ . '/bot.aiml';
    
    if (!file_exists($aiml_file)) {
        return "Błąd: Brak pliku bazy wiedzy bota.";
    }

    $aiml = new AIML();
    $aiml->addDict($aiml_file);
    
    // Clean question for the library (uppercase and no punctuation is better for this library)
    $clean_question = mb_strtoupper(trim($question));
    $clean_question = preg_replace('/[[:punct:]]/u', '', $clean_question);

    // Get answer from library
    $response = $aiml->getAnswer($clean_question);

    if (!$response || $response === '...') {
        $response = "Jestem tylko początkującym botem i nie znam odpowiedzi na to pytanie. Spróbuj zapytać o kontakt lub ofertę.";
    }

    // Replace placeholders with real data
    $placeholders = [
        'DANE_KONTAKTOWE' => "Kontakt: " . strip_tags($cms_data['contact']),
        'DANE_NAWIGACJA' => "Jak do nas dotrzeć: " . strip_tags($cms_data['google_map_link']),
        'DANE_OFERTA' => "Nasza oferta: " . strip_tags($cms_data['offer'])
    ];

    foreach ($placeholders as $placeholder => $value) {
        $response = str_replace($placeholder, $value, $response);
    }

    // Save to DB
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO chatbot (id_cms, question, question_ip, answer) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_cms, $question, $ip, $response]);

    return $response;
}
?>
