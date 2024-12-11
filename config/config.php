<?php
// Dane do połączenia z MongoDB
$mongo_host = 'localhost';
$mongo_port = 27017;

// Google API configuration
$google_client_id = ''; 
$google_client_secret = '';
$redirect_uri = 'http://localhost/Logowanie/callback.php';

// Rozpoczęcie sesji
if (!session_id()) {
    session_start();
}

// Autoload z Composera
require_once '../vendor/autoload.php';

// Konfiguracja Google Client
$gClient = new Google_Client();
$gClient->setClientId($google_client_id);
$gClient->setClientSecret($google_client_secret);
$gClient->setRedirectUri($redirect_uri);
$gClient->addScope("email");
$gClient->addScope("profile");

$google_oauthV2 = new Google_Service_Oauth2($gClient);
