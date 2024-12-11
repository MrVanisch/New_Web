<?php
session_start();
require_once '../vendor/autoload.php'; // Autoload z Composer
require '../config/config.php'; // Załaduj dane konfiguracyjne

// Ustawienie klienta Google
$client = new Google_Client();
$client->setClientId($google_client_id);
$client->setClientSecret($google_client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope("email");
$client->addScope("profile");

// Generowanie URL do logowania
$login_url = $client->createAuthUrl();

// Przekierowanie do URL logowania
header("Location: $login_url");
exit();
