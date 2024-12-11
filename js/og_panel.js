document.addEventListener('DOMContentLoaded', function () {
    const messageBox = document.getElementById('message-box');

    // Sprawdź, czy komunikat istnieje, i wyświetl go z animacją
    if (messageBox.innerText.trim() !== "") {
        messageBox.classList.add('visible');

        // Po 3 sekundach ukryj komunikat
        setTimeout(() => {
            messageBox.classList.remove('visible');
            messageBox.classList.add('hidden');
        }, 3000);
    }
});
