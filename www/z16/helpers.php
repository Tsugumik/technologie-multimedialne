<?php
function logLogin($pdo, $id_cms, $username, $success) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO login_history (id_cms, username, ip_address, success) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_cms, $username, $ip, $success ? 1 : 0]);
}

function getChatbotResponse($pdo, $id_cms, $question, $cms_data) {
    $clean_question = mb_strtolower(trim($question));
    // Remove punctuation
    $clean_question = preg_replace('/[[:punct:]]/u', '', $clean_question);
    // Replace multiple spaces/tabs with single space
    $clean_question = preg_replace('/\s+/', ' ', $clean_question);

    $response = "";
    
    if (preg_match('/\b(cześć|czesc|dzień dobry|hejka|siema|witaj|witam)\b/u', $clean_question)) {
        $response = "Witaj Szanowny Kliencie!";
    } elseif (preg_match('/\b(kontakt|adres|telefon)\b/u', $clean_question)) {
        $response = "Kontakt: " . strip_tags($cms_data['contact']);
    } elseif (preg_match('/\b(nawigacja)\b/u', $clean_question)) {
        $response = "Jak do nas dotrzeć: " . strip_tags($cms_data['google_map_link']);
    } elseif (preg_match('/\b(oferta)\b/u', $clean_question)) {
        $response = "Nasza oferta: " . strip_tags($cms_data['offer']);
    } elseif ($clean_question === '?' || $clean_question === 'h') {
        $response = "Mogę odpowiedzieć na pytania o: cześć, kontakt, nawigacja, oferta. Wpisz jedno z tych słów.";
    } else {
        $response = "Jestem tylko początkującym botem i nie znam odpowiedzi na to pytanie.";
    }

    // Save to DB
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO chatbot (id_cms, question, question_ip, answer) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id_cms, $question, $ip, $response]);

    return $response;
}
?>
