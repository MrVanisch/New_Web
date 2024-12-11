<?php
session_start();

// Sprawdzenie, czy formularz został wysłany
if (!isset($_POST['email']) || !isset($_POST['haslo'])) {
    header('Location: index.php');
    exit();
}

require_once '../vendor/autoload.php'; // Autoload z Composer
require '../config/config.php'; // Załaduj dane konfiguracyjne do MongoDB

use MongoDB\Client;

// Połączenie z MongoDB
$client = new Client("mongodb://$mongo_host:$mongo_port");
$database = $client->my_database; // Wybierz bazę danych
$collection = $database->users; // Wybierz kolekcję (tabela)

// Zbieranie danych z formularza
$email = trim($_POST['email']); // Pobierz e-mail
$haslo = trim($_POST['haslo']); // Pobierz hasło

// Zabezpieczenie e-maila
$email = htmlentities($email, ENT_QUOTES, "UTF-8");

// Znajdź użytkownika po adresie e-mail
$user = $collection->findOne(['email' => $email]);

// Sprawdzenie, czy użytkownik istnieje
if ($user) {
    // Jeśli użytkownik istnieje, sprawdź hasło
    if (password_verify($haslo, $user['haslo'])) {
        // Ustawienie sesji
        $_SESSION['zalogowany'] = true;
        $_SESSION['id'] = (string) $user['_id']; // ID w MongoDB to obiekt, konwertujemy na string
        $_SESSION['user'] = $user['nick']; // Upewnij się, że używasz właściwego klucza (nick lub inny)
        $_SESSION['email'] = $user['email'];

        // Ustawić sesję błędu
        unset($_SESSION['blad']);
        header('Location: ../Panel/index.php');
        exit(); // Dodaj exit po headerze
    } else {
        $_SESSION['blad'] = '<span style="color:red">Nieprawidłowy e-mail lub hasło!</span>';
        header('Location: index.php');
        exit(); // Dodaj exit po headerze
    }
} else {
    $_SESSION['blad'] = '<span style="color:red">Nieprawidłowy e-mail lub hasło!</span>';
    header('Location: index.php');
    exit(); // Dodaj exit po headerze
}
?>
