<?php

session_start();

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['zalogowany'])) {
    header('Location: ../Panel/index.php');
    exit();
}

require_once '../vendor/autoload.php'; // Autoload z Composer
require '../config/config.php'; // Konfiguracja MongoDB

use MongoDB\Client;

$client = new Client("mongodb://$mongo_host:$mongo_port");
$database = $client->my_database; // Nazwa bazy danych
$collection = $database->photos; // Kolekcja zdjęć

// Tworzenie katalogu dla użytkownika
$nazwa = $_SESSION['user'];
$folder = "img/$nazwa";

if (!file_exists($folder)) {
    mkdir($folder, 0777);
    echo "Utworzono katalog dla użytkownika.";
} else {
    echo "Folder już istnieje.";
}

// Przesyłanie zdjęcia
$target_file = "$folder/" . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Sprawdzenie, czy plik to rzeczywiste zdjęcie
if (isset($_POST["submit"])) {
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if ($check !== false) {
        echo "Plik jest obrazem - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "Plik nie jest obrazem.";
        $_SESSION['komunikat'] = '<span style="color:red">Plik nie jest obrazem.</span>';
        $uploadOk = 0;
    }
}

// Sprawdzenie, czy plik już istnieje
if (file_exists($target_file)) {
    echo "Ten plik już istnieje.";
    $_SESSION['komunikat'] = '<span style="color:red">Ten plik już istnieje.</span>';
    $uploadOk = 0;
}

// Sprawdzenie rozmiaru pliku
if ($_FILES["fileToUpload"]["size"] > 50000000) { // Limit rozmiaru 50MB
    echo "Plik jest za duży.";
    $_SESSION['komunikat'] = '<span style="color:red">Plik jest za duży.</span>';
    $uploadOk = 0;
}

// Dozwolone formaty plików
if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
    echo "Tylko pliki JPG, JPEG, PNG i GIF są dozwolone.";
    $_SESSION['komunikat'] = '<span style="color:red">Tylko pliki JPG, JPEG, PNG i GIF są dozwolone.</span>';
    $uploadOk = 0;
}

// Sprawdzenie, czy wystąpiły błędy
if ($uploadOk == 0) {
    echo "Plik nie został przesłany.";
    $_SESSION['komunikat'] = '<span style="color:red">Plik nie został przesłany.</span>';
    header('Location: ../Panel/index.php'); // Przekierowanie po błędzie
} else {
    // Przesyłanie pliku
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        $_SESSION['komunikat'] = '<span style="color:blue">Zdjęcie zostało przesłane.</span>';
        echo "Plik " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " został przesłany.";

        // Wstawianie ścieżki zdjęcia do MongoDB
        $userid = $_SESSION['id'];
        $name = $_SESSION['user'];
        $lokalizacja = $target_file;

        // Dodawanie informacji o zdjęciu do kolekcji w MongoDB
        $insertResult = $collection->insertOne([
            'user_id' => $userid,
            'user_name' => $name,
            'path' => $lokalizacja,
            'upload_time' => new MongoDB\BSON\UTCDateTime() // Zapisanie daty przesłania
        ]);

        if ($insertResult->getInsertedCount() > 0) {
            echo "Zdjęcie zostało zapisane w bazie danych.";
        } else {
            echo "Błąd przy zapisywaniu zdjęcia w bazie danych.";
        }

        header('Location: ../Panel/index.php'); // Przekierowanie po sukcesie
    } else {
        echo "Wystąpił problem podczas przesyłania pliku.";
    }
}
?>
