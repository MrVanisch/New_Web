<?php
session_start();

// Załaduj dane z pliku config
require '../config/config.php'; 

require_once '../vendor/autoload.php'; // Autoload z Composer
use MongoDB\Client;

// Utwórz połączenie do MongoDB, używając zmiennych z config.php
$client = new Client("mongodb://$mongo_host:$mongo_port");
// Wybór bazy danych (np. 'my_database')
$database = $client->my_database;
$collection = $database->users; // wybór kolekcji (tabela users)

// Domyślna ścieżka do awatara
$default_avatar_path = '../images/avatars/default-avatar.png'; // Upewnij się, że plik znajduje się w tym miejscu

if (isset($_POST['email'])) {
    $wszystko_OK = true;

    // Sprawdzenie nicku
    $nick = $_POST['nick'];

    if ((strlen($nick) < 4) || (strlen($nick) > 32)) {
        $wszystko_OK = false;
        $_SESSION['e_nick'] = "Nick musi posiadać od 4 do 32 znaków";
    }

    if (ctype_alnum($nick) == false) {
        $wszystko_OK = false;
        $_SESSION['e_nick'] = "Nick może składać się tylko z liter i cyfr (bez polskich znaków)";
    }

    // Sprawdzenie e-maila
    $email = $_POST['email'];
    $emailB = filter_var($email, FILTER_SANITIZE_EMAIL);

    if ((filter_var($emailB, FILTER_VALIDATE_EMAIL) == false) || ($emailB != $email)) {
        $wszystko_OK = false;
        $_SESSION['e_email'] = "Podaj poprawny adres e-mail";
    }

    // Sprawdzenie hasła
    $haslo1 = $_POST['haslo1'];
    $haslo2 = $_POST['haslo2'];

    if ((strlen($haslo1) < 6) || (strlen($haslo1) > 32)) {
        $wszystko_OK = false;
        $_SESSION['e_haslo'] = "Hasło musi posiadać od 6 do 32 znaków";
    }

    if ($haslo1 != $haslo2) {
        $wszystko_OK = false;
        $_SESSION['e_haslo_i'] = "Podane hasła nie są identyczne";
    }

    $haslo_hash = password_hash($haslo1, PASSWORD_DEFAULT);

    // Sprawdzenie unikalności e-maila w MongoDB
    $existingEmail = $collection->findOne(['email' => $email]);
    if ($existingEmail) {
        $wszystko_OK = false;
        $_SESSION['e_email'] = "Istnieje już konto przypisane do tego adresu email";
    }

    // Sprawdzenie unikalności nicku w MongoDB
    $existingNick = $collection->findOne(['nick' => $nick]);
    if ($existingNick) {
        $wszystko_OK = false;
        $_SESSION['e_nick'] = "Istnieje już osoba o takim nicku";
    }

    // Zapisz użytkownika w bazie, jeśli walidacja przeszła pomyślnie
    if ($wszystko_OK == true) {
        $ip = $_SERVER['REMOTE_ADDR'];

        // Dodanie domyślnego avatara do danych użytkownika
        $collection->insertOne([
            'nick' => $nick,
            'email' => $email,
            'haslo' => $haslo_hash,
            'ip' => $ip,
            'data_rejestracji' => new MongoDB\BSON\UTCDateTime(),
            'avatar' => $default_avatar_path // Domyślny avatar
        ]);

        $_SESSION['udanarejestracja'] = true;
        header('Location: ../Logowanie/index.php');
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja</title>
    <link rel="stylesheet" href="../css/logowanie-rejestracja.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&amp;display=swap" rel="stylesheet">
    <script src="../js/og_rejestracja.js" defer></script> <!-- Skrypt walidacji -->
    <script type="text/javascript">
      var onloadCallback = function() {
        grecaptcha.render('g-recaptcha', {
          'sitekey' : '',
          'theme' : 'dark',
        });
      };
    </script>
    
    <style>
    <?php
        if (isset($_SESSION['e_nick'])) {
            echo 'input[name="nick"]{border-color: rgb(236, 76, 71);}';
        }
        if (isset($_SESSION['e_email'])) {
            echo 'input[name="email"]{border-color: rgb(236, 76, 71);}';
        }
        if (isset($_SESSION['e_haslo'])) {
            echo 'input[name="haslo1"]{border-color: rgb(236, 76, 71);}';
        }
        if (isset($_SESSION['e_haslo_i'])) {
            echo 'input[name="haslo2"]{border-color: rgb(236, 76, 71);}';
        }
    ?>
    </style>
</head>
<body>
    <img id="svg1" src="../image/background-2.svg">
    <img id="svg2" src="../image/background-2.svg">
    <div id="content" style="margin-top: 40px;">
        <form id="registrationForm" action="" method="post">
            <div id="rlogo">Rejestracja</div>
            <div class="form-item">
                <label for="nick">Nick</label>
                <input type="text" name="nick" id="ilogin" placeholder="">
                <?php
                if (isset($_SESSION['e_nick'])) {
                    echo '<div class="error">'.$_SESSION['e_nick'].'</div>';
                    unset($_SESSION['e_nick']);
                }
                ?>
            </div>
            <div class="form-item">
                <label for="email">Email</label>
                <input type="text" name="email" id="ipass" placeholder="">
                <?php
                if (isset($_SESSION['e_email'])) {
                    echo '<div class="error">'.$_SESSION['e_email'].'</div>';
                    unset($_SESSION['e_email']);
                }
                ?>
            </div>
            <div class="form-item">
                <label for="haslo1">Hasło</label>
                <input type="password" name="haslo1" id="ipass" placeholder="">
                <?php
                if (isset($_SESSION['e_haslo'])) {
                    echo '<div class="error">'.$_SESSION['e_haslo'].'</div>';
                    unset($_SESSION['e_haslo']);
                }
                ?>
            </div>
            <div class="form-item">
                <label for="haslo2">Powtórz hasło</label>
                <input type="password" name="haslo2" id="ipass" placeholder="">
                <?php
                if (isset($_SESSION['e_haslo_i'])) {
                    echo '<div class="error">'.$_SESSION['e_haslo_i'].'</div>';
                    unset($_SESSION['e_haslo_i']);
                }
                ?>
            </div>
            <div id="g-recaptcha" style="margin-bottom: 20px;"></div> <!-- Margines dla oddalenia reCAPTCHA -->
            <div id="g-recaptcha"></div>
            <?php
            if (isset($_SESSION['e_bot'])) {
                echo '<div class="error" style="transform: translate(0px, 40px);">'.$_SESSION['e_bot'].'</div>';
                unset($_SESSION['e_bot']);
            }
            ?>
            <div class="button-container">  
                <button type="submit" id="log-reg">Zarejestruj się</button>
                <button type="button" id="back-btn" onclick="window.history.back();">Wróć</button>
            </div>
        </form>
    </div>
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
</body>
</html>
