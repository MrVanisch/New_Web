<?php
session_start();

require '../vendor/autoload.php';
require '../config/config.php';

use MongoDB\Client;
use MongoDB\BSON\ObjectId;

$client = new Client("mongodb://$mongo_host:$mongo_port");
$database = $client->my_database;
$postsCollection = $database->posts;
$usersCollection = $database->users;

// Sprawdzenie, czy sesja zawiera dane użytkownika
if (isset($_SESSION['user']) && isset($_SESSION['id'])) {
    $name = $_SESSION['user'];
    $userId = $_SESSION['id'];
} else {
    // Jeśli nie ma danych w sesji, przekierowanie do logowania
    header('Location: ../Panel/index.php');
    exit();
}

function isValidMongoId($id) {
    return preg_match('/^[a-f\d]{24}$/i', $id);
}

// Pobierz dane użytkownika, weryfikując format ID
if (isValidMongoId($userId)) {
    $user = $usersCollection->findOne(['_id' => new ObjectId($userId)]);
} else {
    $user = $usersCollection->findOne(['google_id' => $userId]);
}

if ($user) {
    // Wybierz avatar Google, jeśli użytkownik nie zaktualizował lokalnego avatara
    if (isset($user['avatar']) && !empty($user['avatar'])) {
        $avatarPath = filter_var($user['avatar'], FILTER_VALIDATE_URL) ? htmlspecialchars($user['avatar']) : '../' . htmlspecialchars($user['avatar']);
    } else {
        $avatarPath = '../img/default_avatar.png';
    }
} else {
    echo "Użytkownik nie znaleziony.";
    exit();
}

// Obsługa dodawania nowego posta
if (isset($_POST['submit_post'])) {
    $postContent = $_POST['post_content'];
    $imagePath = null;

    // Obsługa dodawania zdjęcia
    $userDir = '../img/' . $name;
    if (!file_exists($userDir)) {
        mkdir($userDir, 0777, true);
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $imageTmpPath = $_FILES['image']['tmp_name'];
        $imageName = basename($_FILES['image']['name']);
        $imagePath = $userDir . '/' . $imageName;

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $imageMimeType = mime_content_type($imageTmpPath);

        if (in_array($imageMimeType, $allowedMimeTypes)) {
            move_uploaded_file($imageTmpPath, $imagePath);
        } else {
            $_SESSION['komunikat'] = 'Nieprawidłowy format pliku. Dozwolone formaty to: JPEG, PNG, GIF.';
            $imagePath = null;
        }
    }

    $postsCollection->insertOne([
        'user_id' => $userId,
        'user_name' => $name,
        'content' => $postContent,
        'image' => $imagePath ? str_replace('../', '', $imagePath) : null,
        'likes' => 0,
        'liked_by' => [],
        'comments' => [],
        'post_time' => new MongoDB\BSON\UTCDateTime()
    ]);

    $_SESSION['komunikat'] = 'Post został dodany.';
}

// Dodawanie komentarza do posta
if (isset($_POST['submit_comment'])) {
    $commentContent = $_POST['comment_content'];

    try {
        $postId = new ObjectId($_POST['post_id']);
        $postsCollection->updateOne(
            ['_id' => $postId],
            ['$push' => ['comments' => [
                'user_name' => $name,
                'comment' => $commentContent,
                'comment_time' => new MongoDB\BSON\UTCDateTime()
            ]]]
        );
        $_SESSION['komunikat'] = 'Komentarz został dodany.';
    } catch (Exception $e) {
        $_SESSION['komunikat'] = 'Błąd: Nieprawidłowy identyfikator posta.';
    }
}

// Obsługa lajkowania posta
if (isset($_GET['like'])) {
    try {
        $postId = new ObjectId($_GET['like']);
        $post = $postsCollection->findOne(['_id' => $postId]);

        $likedByArray = $post['liked_by']->getArrayCopy();
        if (!in_array($userId, $likedByArray)) {
            $postsCollection->updateOne(
                ['_id' => $postId],
                [
                    '$inc' => ['likes' => 1],
                    '$push' => ['liked_by' => $userId]
                ]
            );
            $_SESSION['komunikat'] = 'Polubiłeś post.';
        } else {
            $_SESSION['komunikat'] = 'Już polubiłeś ten post.';
        }
    } catch (Exception $e) {
        $_SESSION['komunikat'] = 'Błąd: Nieprawidłowy identyfikator posta.';
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Posty</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' type='text/css' media='screen' href='../css/og_panel.css'>
</head>
<body>
    <nav class="menu">
        <div class="dropdown">
            <img src="<?= $avatarPath ?>" alt="Avatar" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">
            <a href="#"><?= htmlspecialchars($user['nick']) ?></a>
            <ul>
                <li><a href="profile.php">Profil</a></li>
                <li><a href="../Logowanie/logout.php">Wyloguj się</a></li>
            </ul>
        </div>
    </nav>

    <div class="dodawanie">
        <form action="" method="post" enctype="multipart/form-data">
            <textarea name="post_content" placeholder="Napisz coś..." required></textarea><br>
            <label for="image">Dodaj zdjęcie:</label>
            <input type="file" name="image" id="image" accept="image/*"><br>
            <input type="submit" name="submit_post" value="Opublikuj">
        </form>
    </div>

    <div id="message-box" class="hidden">
        <?php
        if (isset($_SESSION['komunikat'])) {
            echo '<p>' . $_SESSION['komunikat'] . '</p>';
            unset($_SESSION['komunikat']);
        }
        ?>
    </div>

    <div class="posts">
        <h2>Posty</h2>

        <?php
        $posts = $postsCollection->find([], ['sort' => ['post_time' => -1]]);
        foreach ($posts as $post) {
            $user = $usersCollection->findOne(['nick' => $post['user_name']]);

            echo '<div class="post">';
            if (!empty($user['avatar'])) {
                $avatarSrc = filter_var($user['avatar'], FILTER_VALIDATE_URL) ? $user['avatar'] : '../' . htmlspecialchars($user['avatar']);
                echo '<img src="' . $avatarSrc . '" alt="Avatar" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 10px;">';
            }
            echo '<h3>' . htmlspecialchars($post['user_name']) . '</h3>';
            echo '<p>' . htmlspecialchars($post['content']) . '</p>';

            if (!empty($post['image'])) {
                echo '<img src="../' . htmlspecialchars($post['image']) . '" alt="Zdjęcie" style="max-width: 300px;"><br>';
            }
            echo '<p>Lajki: ' . $post['likes'] . '</p>';
            echo '<a href="?like=' . $post['_id'] . '">Polub</a>';

            if (isset($post['comments']) && count($post['comments']) > 0) {
                echo '<h4>Komentarze:</h4>';
                foreach ($post['comments'] as $comment) {
                    echo '<p><strong>' . htmlspecialchars($comment['user_name']) . ':</strong> ' . htmlspecialchars($comment['comment']) . '</p>';
                }
            }
            echo '<form action="" method="post">';
            echo '<input type="hidden" name="post_id" value="' . $post['_id'] . '">';
            echo '<textarea name="comment_content" placeholder="Dodaj komentarz..." required></textarea><br>';
            echo '<input type="submit" name="submit_comment" value="Dodaj komentarz">';
            echo '</form>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
