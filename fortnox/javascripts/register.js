document.addEventListener('DOMContentLoaded', () => {
    
    const errorMessage = document.querySelector('.error-message');
    const successMessage = document.querySelector('.success-message');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordPattern = /^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{12,}$/;

    function indicatePasswordsOK() {
        if (passwordPattern.test(passwordInput.value)) {
            passwordInput.style.background = '#c3e6cb';
        } else {
            passwordInput.style.background = '#f5c6cb';
        }
        if (passwordPattern.test(confirmPasswordInput.value) && passwordInput.value === confirmPasswordInput.value) {
            confirmPasswordInput.style.background = '#c3e6cb';
        } else {
            confirmPasswordInput.style.background = '#f5c6cb';
        }
        void document.body.offsetWidth;
    }

    passwordInput.addEventListener('input', function () {
        indicatePasswordsOK();
    });

    confirmPasswordInput.addEventListener('input', function () {
        indicatePasswordsOK();
    });

    document.querySelector('form').addEventListener('submit', async function (e) {
        e.preventDefault();

        const fields = [
            { field: document.getElementById('username'), message: 'Username is required.' },
            { field: document.getElementById('password'), message: 'Password must be at least 12 characters long.', condition: (field) => field.value.length < 12 },
            { field: document.getElementById('confirm_password'), message: 'Passwords do not match.', condition: (field) => field.value !== document.getElementById('password').value },
            { field: document.getElementById('your_email'), message: 'Your Emails do not match.', condition: (field) => field.value !== document.getElementById('confirm_your_email').value },
            { field: document.getElementById('receipts_own_invoices_email'), message: 'Receipts or Own Invoices Email is required.' },
            { field: document.getElementById('supplier_invoices_email'), message: 'Supplier Invoices Email is required.' }
        ];

        let errors = [];

        fields.forEach(({ field, message, condition }) => {
            if (!field.value.trim() || (condition && condition(field))) {
                errors.push(message);
            }
        });

        if (errors.length > 0) {
            showMessage(errorMessage, errors.join('\n'));

            return;
        }

        const formData = new FormData(this);

        try {
            const response = await fetch('../logic/register.php', {
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
            } else if (result.status === 'error') {
                showMessage(errorMessage, result.message);
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
