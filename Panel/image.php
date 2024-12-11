<?php
// Sprawdzanie, czy plik istnieje
if (isset($_SESSION['filename']) && file_exists($_SESSION['filename'])) {
    $var_value = $_SESSION['filename'];
    $id_img = $_SESSION['filename_id'];

    // Pobranie rozszerzenia pliku (formatu obrazu)
    $imageFileType = strtolower(pathinfo($var_value, PATHINFO_EXTENSION));

    // Sprawdzanie rodzaju pliku i ładowanie odpowiedniego formatu obrazu
    switch ($imageFileType) {
        case 'jpeg':
        case 'jpg':
            $im = imagecreatefromjpeg($var_value);
            break;
        case 'png':
            $im = imagecreatefrompng($var_value);
            break;
        case 'gif':
            $im = imagecreatefromgif($var_value);
            break;
        default:
            echo "Nieobsługiwany format pliku.";
            exit();
    }

    // Sprawdzanie, czy obraz został poprawnie załadowany
    if (!$im) {
        echo "Błąd podczas wczytywania obrazu.";
        exit();
    }

    // Buforowanie wyjścia obrazu
    ob_start();
    switch ($imageFileType) {
        case 'jpeg':
        case 'jpg':
            imagejpeg($im);
            break;
        case 'png':
            imagepng($im);
            break;
        case 'gif':
            imagegif($im);
            break;
    }

    // Pobranie danych obrazu i czyszczenie bufora wyjścia
    $imagedata = ob_get_clean();

    // Wyświetlanie obrazu jako base64 z zastosowaniem klasy CSS
    echo '<img src="data:image/' . $imageFileType . ';base64,' . base64_encode($imagedata) . '" class="image"/>';
    echo '<a href="?del=' . $id_img . '"><button>X</button></a>';

    // Zwolnienie zasobów obrazu
    imagedestroy($im);
} else {
    echo "Plik nie istnieje.";
}
?>
