document.addEventListener('DOMContentLoaded', () => {

    function showMessage(element, message) {
        element.textContent = message;
        element.style.display = 'block';
    }

    document.querySelector('#change-password-form').addEventListener('submit', async function (e) {
        e.preventDefault();

        try {

            const currentPassword = document.getElementById('current_password');
            const reset_token = document.getElementById('reset_token');
            const newPassword = document.getElementById('new_password');
            const confirmNewPassword = document.getElementById('confirm_new_password');
            const errorMessage = document.querySelector('.error-message');
            const successMessage = document.querySelector('.success-message');

            let errors = [];

            if ((currentPassword == null || currentPassword.value.trim() === '') && 
                (reset_token == null || reset_token.value.trim() === '')) {
                errors.push('Current password is required.');
            }

            if (newPassword.value.length < 12) {
                errors.push('New password must be at least 12 characters long.');
            }

            if (newPassword.value !== confirmNewPassword.value) {
                errors.push('New passwords do not match.');
            }

            if (errors.length > 0) {
                showMessage(errorMessage, errors.join('\n'));
                successMessage.style.display = 'none';

                return;
            }

            const formData = new FormData(this);

            const response = await fetch('../logic/change-password.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                showMessage(successMessage, result.message);
                errorMessage.style.display = 'none';

                document.querySelector('button[type="submit"]').disabled = true;

                setTimeout(() => {
                    window.location.href = '../pages/login.php';
                }, 10000);
            } else {
                showMessage(errorMessage, result.message);
                successMessage.style.display = 'none';
            }

        } catch {
            showMessage(errorMessage, 'Failed to update password.');
            successMessage.style.display = 'none';
        }
    });

});
