document.addEventListener('DOMContentLoaded', () => {
    
    const errorMessage = document.querySelector('.error-message');
    const successMessage = document.querySelector('.success-message');

    document.querySelector('form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const yourEmail = document.getElementById('your_email');
        const userId = document.getElementById('user_id');
        
        let errors = (yourEmail) ? '' : 'Email is required.';
        errors += (userId) ? '' : '\nYou must be logged in to use this function.';

        if (errors !== '') {
            showMessage(errorMessage, errors);
            successMessage.style.display = 'none';
            makePageReadOnly();
            return;
        }

        const formData = new FormData(this);

        try {
            const response = await fetch('../logic/activate.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                makePageReadOnly();
                successMessage.textContent = result.message;
                toggleVisibility(successMessage, errorMessage, true);

                const inputs = document.querySelectorAll('input');
                inputs.forEach(input => input.setAttribute('readonly', true));

                document.querySelector('button[type="submit"]').disabled = true;

                hideMessage(successMessage, () => window.location.href = '../pages/login.php');
            } else if (result.status === 'fatal') {
                window.location.href = '../pages/login.php';
            } else if (result.status === 'error') {
                showMessage(errorMessage, result.message);
                successMessage.style.display = 'none';
            }
        } catch (error) {
            showMessage(errorMessage, error);
            successMessage.style.display = 'none';
        }
    });

    function makePageReadOnly() {
        const inputs = document.querySelectorAll('input, textarea, button, select');

        inputs.forEach(input => {
            input.disabled = true;
        });

        document.body.style.pointerEvents = 'none';
    }

    function showMessage(element, message) {
        element.textContent = message;
        element.style.display = 'block';
        document.querySelector('.success-message').style.display = 'none';
        hideMessage(element);
    }

    function toggleVisibility(showElement, hideElement, isSuccess) {
        showElement.style.display = 'block';
        hideElement.style.display = 'none';

        if (isSuccess) {
            hideMessage(showElement);
        }
    }

    function hideMessage(element, callback) {
        setTimeout(() => element.classList.add('hide'), 8000);

        setTimeout(() => {
            element.style.display = 'none';
            element.classList.remove('hide');

            if (callback) {
                callback();
            }
        }, 9000);
    }

});
