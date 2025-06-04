document.addEventListener("DOMContentLoaded", function () {

    const submitButton = document.getElementById('submitButton');
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');
    const form = document.getElementById('edit-user-form');

    function validateEmail(email) {
        const re = /^[a-zA-Z0-9._+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        return re.test(email);
    }

    function makePageReadOnly() {
        const inputs = document.querySelectorAll('input, textarea, button, select');
        
        inputs.forEach(input => {
            input.disabled = true;
        });

        document.body.style.pointerEvents = 'none';
    }

    function showMessage(messageElement, message, duration = 5000, reload = false) {
        messageElement.innerText = message;
        messageElement.style.display = 'block';

        setTimeout(() => {
            messageElement.classList.add('hide');

            if (reload) {
                window.location.reload();
            }
        }, duration - 1000);

        setTimeout(() => {
            messageElement.style.display = 'none';
            messageElement.classList.remove('hide');
        }, duration);
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        try {
            const supplierEmail = document.getElementById('supplier_invoices_email').value;
            const receiptsEmail = document.getElementById('receipts_own_invoices_email').value;
            const ccEmail = document.getElementById('cc_email').value;
            const fromEmail = document.getElementById('from_email').value;

            if (!validateEmail(supplierEmail) || !validateEmail(receiptsEmail) || !validateEmail(ccEmail) || !validateEmail(fromEmail)) {
                showMessage(errorMessage, 'Please enter valid email addresses.');
                successMessage.style.display = 'none';

                return;
            }

            const formData = new FormData(this);
            formData.set('test_mode', document.getElementById('test_mode').checked ? 'on' : 'off');

            submitButton.disabled = true;
            const response = await fetch('../logic/edit-user.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                showMessage(successMessage, result.message, 3000, true);
                errorMessage.style.display = 'none';
                
            } else if (result.status === 'fatal') {
                window.location.href = '../pages/login.php';
                
                return;
            } else {
                showMessage(errorMessage, result.message);
                successMessage.style.display = 'none';
            }
        } catch {
            showMessage(errorMessage, 'Settings update failed.');
            successMessage.style.display = 'none';
        } finally {
            submitButton.disabled = false;
        }
    });

    document.querySelectorAll('.delete-link').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();

            const userId = this.getAttribute('data-id');
            const dialog = document.getElementById('confirmationDialog');
            dialog.style.display = 'flex';

            document.getElementById('confirmDelete').onclick = async function() {
                try {
                    makePageReadOnly();
                    dialog.style.display = 'none';
                    const deleteUrl = `../logic/delete-user.php?id=${userId}`;
                    const response = await fetch(deleteUrl, {
                        method: 'GET'
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        showMessage(successMessage, result.message, 3000);
                        errorMessage.style.display = 'none';

                        setTimeout(() => {
                            window.location.href = '../pages/login.php';
                        }, 3000);
                    } else if (result.status === 'fatal') {
                        window.location.href = '../pages/login.php';
                                
                        return;
                    } else {
                        showMessage(errorMessage, result.message);
                        successMessage.style.display = 'none';
                    }
                } catch {
                    showMessage(errorMessage, 'Failed to delete user.');
                    successMessage.style.display = 'none';
                }
            };

            document.getElementById('cancelDelete').onclick = function() {
                dialog.style.display = 'none';
            };
        });
    });

});
