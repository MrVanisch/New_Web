<?php
session_start();

if (isset($_SESSION['zalogowany']) && $_SESSION['zalogowany'] == true) {
    header('Location: ../Panel/index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Logowanie</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' type='text/css' media='screen' href='../css/logowanie.css'>
</head>
<body>
    <!-- SVG jako tło -->
    <img id="svg1" src="../image/background-2.svg">
    <img id="svg2" src="../image/background-2.svg">

    <div id="content">
        <form action="logowanie.php" method="post">
            <div id="rlogo">Logowanie</div>
            <div class="form-item">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-item">
                <label for="haslo">Hasło</label>
                <input type="password" name="haslo" id="haslo" required>
            </div>
            
            <!-- Kontener na przyciski "Rejestracja" i "Zaloguj" -->
            <div class="button-container">
                <input id="reg" type="button" onclick="location.href='../Rejestracja/index.php';" value="Rejestracja">
                <button type="submit" id="log">Zaloguj</button>
            </div>
        </form>

        <!-- Przycisk logowania przez Google -->
        <div id="google-login">
            <a href="login_google.php">
                <img src="gg.png" alt="Google Logo">
                <span>Zaloguj przez Google</span>
            </a>
        </div>

        <?php
        if (isset($_SESSION['blad'])) {
            echo $_SESSION['blad'];
            unset($_SESSION['blad']);
        }
        ?>
    </div>
</body>
</html>