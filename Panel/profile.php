<?php
session_start();

require '../config/config.php';
require '../vendor/autoload.php';

use MongoDB\Client;

$client = new Client("mongodb://$mongo_host:$mongo_port");
$database = $client->my_database;
$collection = $database->users;
$nickChangesCollection = $database->nick_changes;
$passwordChangesCollection = $database->password_changes;

$id = $_SESSION['id'];

// Sprawdzenie formatu ID użytkownika
$id_field = preg_match('/^[a-f\d]{24}$/i', $id) ? '_id' : 'google_id';
$id_value = $id_field === '_id' ? new MongoDB\BSON\ObjectId($id) : $id;

// Pobierz dane użytkownika z odpowiednim ID
$user = $collection->findOne([$id_field => $id_value]);

if (!$user) {
    header('Location: index.php');
    exit();
}

$avatarDirectory = '../images/avatars/' . $user['nick'] . '/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obsługa zmiany nicku
    if (isset($_POST['update_nick'])) {
        $new_nick = htmlentities(trim($_POST['nick']), ENT_QUOTES, "UTF-8");
        $old_nick = $user['nick'];

        $collection->updateOne(
            [$id_field => $id_value],
            ['$set' => ['nick' => $new_nick]]
        );

        $nickChangesCollection->insertOne([
            'user_id' => $id,
            'old_nick' => $old_nick,
            'new_nick' => $new_nick,
            'change_date' => new MongoDB\BSON\UTCDateTime()
        ]);

        $_SESSION['user'] = $new_nick;
    }

    // Obsługa zmiany hasła tylko dla kont MongoDB
    if (isset($_POST['update_password']) && $id_field === '_id') {
        $new_password = trim($_POST['haslo']);
        if (!empty($new_password)) {
            $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);
            $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                ['$set' => ['haslo' => $hashedPassword]]
            );
            $passwordChangesCollection->insertOne([
                'user_id' => $id,
                'new_password_hash' => $hashedPassword,
                'change_date' => new MongoDB\BSON\UTCDateTime()
            ]);
        }
    }

    // Obsługa zmiany awatara
    if (isset($_POST['update_avatar']) && isset($_FILES['avatar'])) {
        if (!is_dir($avatarDirectory)) {
            mkdir($avatarDirectory, 0777, true);
        }

        $avatarFile = $_FILES['avatar']['tmp_name'];
        $avatarFileName = basename($_FILES['avatar']['name']);
        $targetFilePath = $avatarDirectory . $avatarFileName;
        $check = getimagesize($avatarFile);

        if ($check !== false) {
            if (move_uploaded_file($avatarFile, $targetFilePath)) {
                $collection->updateOne(
                    [$id_field => $id_value],
                    ['$set' => ['avatar' => $targetFilePath]]
                );
            } else {
                echo "Wystąpił błąd podczas przesyłania pliku.";
            }
        } else {
            echo "Wybrany plik nie jest obrazem.";
        }
    }

    header('Location: ../Panel/profile.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edytuj profil</title>
    <link rel='stylesheet' type='text/css' media='screen' href='../css/edit.css'>
</head>
<body>
    <div class="form-container">
        <h1>Edytuj profil</h1>

        <!-- Formularz zmiany nicku -->
        <div class="form-section">
            <h2>Zmień nick</h2>
            <form action="" method="post">
                <label for="nick">Nowy nick:</label>
                <input type="text" name="nick" value="<?php echo htmlspecialchars($user['nick']); ?>" required>
                <input type="submit" name="update_nick" value="Zmień nick">
            </form>
        </div>

        <!-- Formularz zmiany hasła tylko dla kont MongoDB -->
        <?php if ($id_field === '_id') : ?>
        <div class="form-section">
            <h2>Zmień hasło</h2>
            <form action="" method="post">
                <label for="haslo">Nowe hasło:</label>
                <input type="password" name="haslo" placeholder="Zostaw puste, jeśli nie chcesz zmieniać">
                <input type="submit" name="update_password" value="Zmień hasło">
            </form>
        </div>
        <?php endif; ?>

        <!-- Formularz zmiany awatara -->
        <div class="form-section">
            <h2>Zmień awatar</h2>
            <form action="" method="post" enctype="multipart/form-data">
                <label for="avatar">Nowy awatar:</label>
                <input type="file" name="avatar" required>
                <input type="submit" name="update_avatar" value="Zmień awatar">
            </form>
        </div>

        <!-- Przycisk powrotu do Panelu -->
        <div class="form-section">
            <a href="../Panel/index.php" class="back-button">Wróć do Panelu</a>
        </div>
    </div>
</body>
</html>
