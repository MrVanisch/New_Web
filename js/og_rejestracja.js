document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registrationForm");
    const errors = document.querySelectorAll(".error");

    const nickField = form.elements["nick"];
    const emailField = form.elements["email"];
    const passwordField = form.elements["haslo1"];
    const confirmPasswordField = form.elements["haslo2"];

    // Walidacja na bieżąco
    nickField.addEventListener("input", validateNick);
    emailField.addEventListener("input", validateEmail);
    passwordField.addEventListener("input", validatePassword);
    confirmPasswordField.addEventListener("input", validateConfirmPassword);

    function validateNick() {
        const nick = nickField.value;
        if (nick.length < 4 || nick.length > 32) {
            displayError(0, "Nick musi posiadać od 4 do 32 znaków");
            nickField.classList.add("error-border");
        } else {
            clearError(0);
            nickField.classList.remove("error-border");
        }
    }

    function validateEmail() {
        const email = emailField.value;
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!re.test(email)) {
            displayError(1, "Podaj poprawny adres e-mail");
            emailField.classList.add("error-border");
        } else {
            clearError(1);
            emailField.classList.remove("error-border");
        }
    }

    function validatePassword() {
        const password = passwordField.value;
        if (password.length < 6 || password.length > 32) {
            displayError(2, "Hasło musi posiadać od 6 do 32 znaków");
            passwordField.classList.add("error-border");
        } else {
            clearError(2);
            passwordField.classList.remove("error-border");
        }
    }

    function validateConfirmPassword() {
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;
        if (password !== confirmPassword) {
            displayError(3, "Podane hasła nie są identyczne");
            confirmPasswordField.classList.add("error-border");
        } else {
            clearError(3);
            confirmPasswordField.classList.remove("error-border");
        }
    }

    function displayError(index, message) {
        errors[index].textContent = message;
    }

    function clearError(index) {
        errors[index].textContent = "";
    }
});
