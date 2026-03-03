<?php
session_start();

if (!isset($lab_name)) {
    die("Błąd kompilacji: Brak nazwy laboratorium.");
}

// Funkcja pomocnicza do sprawdzania logowania
function isLoggedIn($lab_name)
{
    return isset($_SESSION[$lab_name]['user_id']);
}

// Jeśli nie udostępniliśmy flagi $skip_auth i użytkownik nie jest zalogowany, przekieruj do logowania
if (!isset($skip_auth) || !$skip_auth) {
    if (!isLoggedIn($lab_name)) {
        header("Location: login.php");
        exit;
    }
}
?>
