<?php
session_start();
require_once '../vendor/autoload.php';
require '../config/config.php';

$token = null; // Inicjalizacja zmiennej tokenu, aby uniknąć błędów "undefined variable"

// Konfiguracja klienta Google
$client = new Google_Client();
$client->setClientId($google_client_id); // Ustawienie ID klienta Google
$client->setClientSecret($google_client_secret); // Ustawienie sekretu klienta Google
$client->setRedirectUri($redirect_uri); // Ustawienie URI przekierowania po autoryzacji
$client->addScope("email"); // Dodanie zakresu dostępu do e-maila
$client->addScope("profile"); // Dodanie zakresu dostępu do profilu użytkownika

// Wyłączenie weryfikacji SSL (do celów diagnostycznych)
$client->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));

if (isset($_GET['code'])) { // Sprawdzenie, czy kod autoryzacyjny jest ustawiony
    try {
        // Pobranie tokenu dostępu przy użyciu kodu autoryzacyjnego
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        // Sprawdzenie, czy token jest prawidłowy
        if (is_null($token)) {
            throw new Exception('fetchAccessTokenWithAuthCode zwróciło NULL.');
        } elseif (isset($token['error'])) {
            throw new Exception('Błąd Google API: ' . $token['error'] . ' - ' . ($token['error_description'] ?? 'Brak dodatkowych informacji o błędzie.'));
        }

        // Ustawienie tokenu dostępu w kliencie Google
        $client->setAccessToken($token);

        // Pobranie informacji o użytkowniku z Google
        $google_oauth = new Google_Service_Oauth2($client);
        $user_info = $google_oauth->userinfo->get();

        // Połączenie z MongoDB
        $mongoClient = new MongoDB\Client("mongodb://$mongo_host:$mongo_port");
        $database = $mongoClient->my_database;
        $collection = $database->users;

        // Sprawdzenie, czy użytkownik już istnieje w bazie na podstawie `google_id`
        $existingUser = $collection->findOne(['google_id' => $user_info->id]);

        if (!$existingUser) {
            // Jeśli użytkownik nie istnieje, dodaj go do bazy
            $collection->insertOne([
                'nick' => $user_info->name,
                'email' => $user_info->email,
                'google_id' => $user_info->id,
                'avatar' => $user_info->picture,
                'data_rejestracji' => new MongoDB\BSON\UTCDateTime()
            ]);
        }

        // Ustawienie zmiennych sesji dla zalogowanego użytkownika
        $_SESSION['zalogowany'] = true;
        $_SESSION['id'] = $user_info->id;
        $_SESSION['user'] = $user_info->name;
        $_SESSION['email'] = $user_info->email;

        // Przekierowanie do panelu po zalogowaniu
        header('Location: ../Panel/index.php');
        exit();
    } catch (Exception $e) {
        // Wyświetlenie szczegółów błędu w celu diagnostyki
        echo 'Błąd: ' . $e->getMessage();
        exit();
    }
} else {
    // Wyświetlenie komunikatu o błędzie, jeśli brakuje kodu autoryzacyjnego
    echo 'Nie udało się zalogować przez Google. Brak kodu autoryzacyjnego.';
    exit();
}
