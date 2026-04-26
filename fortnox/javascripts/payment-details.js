document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('paymentDetailsForm');
    const dropZone = document.getElementById('paymentDropZone');
    const fileInput = document.getElementById('paymentPdfInput');
    const extractButton = document.getElementById('extractButton');
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');
    const results = document.getElementById('paymentResults');
    const amountInput = document.getElementById('paymentAmount');
    const ocrInput = document.getElementById('paymentOcr');
    const accountInput = document.getElementById('paymentAccount');
    const accountLabel = document.querySelector('label[for="paymentAccount"]');
    const textPreview = document.getElementById('paymentTextPreview');

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

    function renderSelectedFile(fileName) {
        dropZone.textContent = '';

        const icon = document.createElement('i');
        icon.className = 'fas fa-file-pdf drop-font';

        const title = document.createElement('p');
        const titleText = document.createElement('b');
        titleText.textContent = 'Supplier invoice PDF';
        title.appendChild(titleText);

        const name = document.createElement('p');
        name.textContent = fileName;

        dropZone.appendChild(icon);
        dropZone.appendChild(title);
        dropZone.appendChild(name);
    }

    function setSelectedFile(file) {
        if (!file) {
            extractButton.disabled = true;
            return;
        }

        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            showMessage(errorMessage, 'Only PDF files are allowed.');
            successMessage.style.display = 'none';
            fileInput.value = '';
            extractButton.disabled = true;
            return;
        }

        renderSelectedFile(file.name);
        dropZone.classList.add('active');
        extractButton.disabled = false;
    }

    function setResult(input, detail) {
        input.value = detail && detail.value ? detail.value : '';
        input.placeholder = detail && detail.value ? '' : 'Not found';
    }

    async function copyValue(targetId) {
        const input = document.getElementById(targetId);

        if (!input || !input.value) {
            return;
        }

        try {
            await navigator.clipboard.writeText(input.value);
        } catch {
            input.select();
            document.execCommand('copy');
            input.blur();
        }

        showMessage(successMessage, 'Copied.', 1800);
        errorMessage.style.display = 'none';
    }

    dropZone.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', () => {
        setSelectedFile(fileInput.files[0]);
    });

    dropZone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropZone.classList.add('drag-over');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('drag-over');
    });

    dropZone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropZone.classList.remove('drag-over');

        if (event.dataTransfer.files.length === 0) {
            return;
        }

        fileInput.files = event.dataTransfer.files;
        setSelectedFile(fileInput.files[0]);
    });

    document.querySelectorAll('.payment-copy-button').forEach(button => {
        button.addEventListener('click', () => {
            copyValue(button.getAttribute('data-copy-target'));
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!fileInput.files[0]) {
            showMessage(errorMessage, 'Please select a PDF file.');
            successMessage.style.display = 'none';
            return;
        }

        extractButton.disabled = true;
        extractButton.innerText = 'Reading...';

        try {
            const formData = new FormData();
            formData.append('payment_pdf', fileInput.files[0]);

            const response = await fetch('../logic/extract-payment-details.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'fatal') {
                window.location.href = '../pages/login.php';
                return;
            }

            if (result.status !== 'success') {
                showMessage(errorMessage, result.message || 'Failed to extract payment details.');
                successMessage.style.display = 'none';
                results.classList.add('hidden');
                return;
            }

            setResult(amountInput, result.details.amount);
            setResult(ocrInput, result.details.ocr);
            setResult(accountInput, result.details.account);
            accountLabel.innerText = result.details.account && result.details.account.value ? result.details.account.label : 'Account';
            textPreview.value = result.text_preview || '';
            results.classList.remove('hidden');

            if (!result.text_found) {
                showMessage(errorMessage, 'No readable PDF text was found. Scanned PDFs need OCR before this page can read them.');
                successMessage.style.display = 'none';
            } else {
                showMessage(successMessage, result.message);
                errorMessage.style.display = 'none';
            }
        } catch {
            showMessage(errorMessage, 'Failed to extract payment details.');
            successMessage.style.display = 'none';
            results.classList.add('hidden');
        } finally {
            extractButton.disabled = false;
            extractButton.innerText = 'Read Payment Details';
        }
    });

});
