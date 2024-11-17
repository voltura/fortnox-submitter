document.addEventListener("DOMContentLoaded", function () {

    const submitButton = document.getElementById('submitButton');
    const errorMessage = document.getElementById('errorMessage');
    const successMessage = document.getElementById('successMessage');
    const form = document.getElementById('login-user-form');

    function makePageReadOnly() {
        const inputs = document.querySelectorAll('input, textarea, button, select');

        inputs.forEach(input => {
            input.disabled = true;
        });

        document.body.style.pointerEvents = 'none';
    }

    function showMessage(messageElement, message, duration = 5000) {
        messageElement.innerText = message;
        messageElement.style.display = 'block';

        setTimeout(() => {
            messageElement.classList.add('hide');
        }, duration - 1000);

        setTimeout(() => {
            messageElement.style.display = 'none';
            messageElement.classList.remove('hide');
        }, duration);
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        try {
            submitButton.disabled = true;
            const response = await fetch('../logic/login.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                makePageReadOnly();
                showMessage(successMessage, result.message, 1000);
                errorMessage.style.display = 'none';
                window.location.href = '../pages/submit.php';
            } else {
                showMessage(errorMessage, result.message);
                successMessage.style.display = 'none';
            }
        } catch {
            showMessage(errorMessage,'Login failed.');
            successMessage.style.display = 'none';
        } finally {
            submitButton.disabled = false;
        }
    });

});
