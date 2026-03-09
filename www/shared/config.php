<?php
// Baza danych jest przekazywana przez zmienną $db_name zdefiniowaną w plikach danego laboratorium.
if (!isset($db_name)) {
    die("Błąd kompilacji: Brak nazwy bazy danych.");
}

$db_host = isset($db_host) ? $db_host : 'db';
$db_user = isset($db_user) ? $db_user : 'root';
$db_pass = isset($db_pass) ? $db_pass : 'rootpassword';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    die("Błąd połączenia z bazą danych ($db_name). Skontaktuj się z administratorem.");
}
?>
