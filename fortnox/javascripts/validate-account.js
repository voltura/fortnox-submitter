document.addEventListener("DOMContentLoaded", async function () {

    const errorMessage = document.getElementById('errorMessage');
    const successMessage = document.getElementById('successMessage');

    function showMessage(success = true, message = '', callback = null, duration = 3000) {
        const showElement = success ? successMessage : errorMessage;
        const hideElement = success ? errorMessage : successMessage;

        showElement.innerText = message || showElement.innerText;
        hideElement.style.display = 'none';
        showElement.style.display = 'block';
        void document.body.offsetWidth;

        if (success) {
            setTimeout(() => {
                showElement.classList.add('hide');
                void document.body.offsetWidth;
            }, duration - 1000);
    
            setTimeout(() => {
                showElement.style.display = 'none';
                showElement.classList.remove('hide');
                void document.body.offsetWidth;
    
                if (callback) {
                    callback();
                }
            }, duration);
        } else if (callback) {
            callback();
        }
    }

    try {
        errorMessage.style.display = 'none';
        successMessage.style.display = 'none';
        void document.body.offsetWidth;

        const form = document.querySelector('#validate-account-form');
        const formData = new FormData(form);
        const response = await fetch('../logic/validate-account.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            showMessage(true, result.message, () => window.location.href = '../pages/submit.php');
        } else {
            showMessage(false, result.message);
        }
    } catch {
        showMessage(false);
    }
});
