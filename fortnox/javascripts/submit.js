document.addEventListener('DOMContentLoaded', () => { 

    const emailForm = document.getElementById('emailForm');
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');
    const fileInputReceipt = document.getElementById('fileInputReceipt');
    const dropZoneReceipt = document.getElementById('dropZoneReceipt');
    const fileInputSupplierInvoice = document.getElementById('fileInputSupplierInvoice');
    const dropZoneSupplierInvoice = document.getElementById('dropZoneSupplierInvoice');
    const actionInput = document.querySelector('input[name="action"]');
    const submitButton = document.getElementById('submitButton');

    if (!fileInputReceipt || !fileInputSupplierInvoice) {
        return;
    }

    document.querySelector('.toggle-icon').addEventListener('click', function() {
        const subjectField = document.getElementById('subject');
        const subjectLabelField = document.getElementById('subjectlabel');
        const messageField = document.getElementById('message');
        const messageLabelField = document.getElementById('messagelabel');
        const toggleDetails = document.getElementById('toggle-details');

        if (subjectField.hasAttribute('hidden')) {
            subjectField.removeAttribute('hidden');
            subjectLabelField.removeAttribute('hidden');
            messageField.removeAttribute('hidden');
            messageLabelField.removeAttribute('hidden');
            toggleDetails.innerText = 'Hide details';
        } else {
            subjectField.setAttribute('hidden', '');
            subjectLabelField.setAttribute('hidden', '');
            messageField.setAttribute('hidden', '');
            messageLabelField.setAttribute('hidden', '');
            toggleDetails.innerText = 'Show details';
        }
    });

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

    submitButton.disabled = true;

    function resetDropZones() {
        fileInputReceipt.value = "";
        fileInputSupplierInvoice.value = "";
        dropZoneReceipt.innerHTML = `
            <i class="fas fa-receipt" style="font-size: 24px; margin-bottom: 8px;"></i>
            <p><b>Receipt or own invoice</b></p>
            <p>Drag and drop a file here, or click to select one</p>`;
        dropZoneSupplierInvoice.innerHTML = `
            <i class="fas fa-file-invoice-dollar" style="font-size: 24px; margin-bottom: 8px;"></i>
            <p><b>Supplier invoice</b></p>
            <p>Drag and drop a file here, or click to select one</p>`;
        dropZoneReceipt.classList.remove('active');
        dropZoneSupplierInvoice.classList.remove('active');
        submitButton.disabled = true;
    }

    function handleFileInput(fileInput, dropZone, isReceipt) {
        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (file) {
                dropZone.innerHTML = `
                <p><b>${isReceipt ? "Receipt or own invoice" : "Supplier invoice"}</b><p>
                <p>${file.name}</p>`;
                submitButton.disabled = false;
                actionInput.value = isReceipt ? "1" : "0";

                dropZoneReceipt.classList.toggle('active', isReceipt);
                dropZoneSupplierInvoice.classList.toggle('active', !isReceipt);

                if (isReceipt) {
                    fileInputSupplierInvoice.value = "";
                    dropZoneSupplierInvoice.innerHTML = `
                        <i class="fas fa-file-invoice-dollar" style="font-size: 24px; margin-bottom: 8px;"></i>
                        <p><b>Supplier invoice</b></p>
                        <p>Drag and drop a file here, or click to select one</p>`;
                } else {
                    fileInputReceipt.value = "";
                    dropZoneReceipt.innerHTML = `
                        <i class="fas fa-receipt" style="font-size: 24px; margin-bottom: 8px;"></i>
                        <p><b>Receipt or own invoice</b></p>
                        <p>Drag and drop a file here, or click to select one</p>`;
                }
            } else {
                submitButton.disabled = true;
            }
        });

        dropZone.addEventListener('click', () => {
            fileInput.click();
        });

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type === 'image/png' || file.type === 'image/jpeg' || file.type === 'application/pdf') {
                    fileInput.files = files;
                    dropZone.innerHTML = `
                    <p><b>${isReceipt ? "Receipt or own invoice" : "Supplier invoice"}</b><p>
                    <p>${file.name}</p>`;
                    submitButton.disabled = false;
                    actionInput.value = isReceipt ? "1" : "0";

                    dropZoneReceipt.classList.toggle('active', isReceipt);
                    dropZoneSupplierInvoice.classList.toggle('active', !isReceipt);

                    if (isReceipt) {
                        fileInputSupplierInvoice.value = "";
                        dropZoneSupplierInvoice.innerHTML = `
                            <i class="fas fa-file-invoice-dollar" style="font-size: 24px; margin-bottom: 8px;"></i>
                            <p><b>Supplier invoice</b></p>
                            <p>Drag and drop a file here, or click to select one</p>`;
                    } else {
                        fileInputReceipt.value = "";
                        dropZoneReceipt.innerHTML = `
                            <i class="fas fa-receipt" style="font-size: 24px; margin-bottom: 8px;"></i>
                            <p><b>Receipt or own invoice</b></p>
                            <p>Drag and drop a file here, or click to select one</p>`;
                    }
                } else {
                    showMessage(errorMessage, 'Only PNG, JPG, or PDF files are allowed.');
                    successMessage.style.display = 'none';
                    submitButton.disabled = true;
                }
            }
        });
    }

    handleFileInput(fileInputReceipt, dropZoneReceipt, true);
    handleFileInput(fileInputSupplierInvoice, dropZoneSupplierInvoice, false);

    emailForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        
        if (fileInputReceipt.files.length === 0 && fileInputSupplierInvoice.files.length === 0) {
            showMessage(errorMessage, 'Please select a file before submitting.');
            successMessage.style.display = 'none';
            return;
        }

        const subjectField = document.getElementById('subject');
        const messageField = document.getElementById('message');

        if (!subjectField.value.trim()) {
            subjectField.value = "Document to Fortnox";
        }

        if (!messageField.value.trim()) {
            messageField.value = "See attachment";
        }

        const formData = new FormData(this);

        if (fileInputReceipt.files.length > 0) {
            formData.append('attachment', fileInputReceipt.files[0]);
        } else if (fileInputSupplierInvoice.files.length > 0) {
            formData.append('attachment', fileInputSupplierInvoice.files[0]);
        }

        try {
            const response = await fetch('../logic/submit.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                showMessage(successMessage, result.message + (result.stored_in_database ? '' : '\nCould not store document in database.'));
                errorMessage.style.display = 'none';
                resetDropZones();
            } else if (result.status === 'fatal') {
                window.location.href = '../pages/login.php';
                
                return;
            } else {
                showMessage(errorMessage, result.message);
                successMessage.style.display = 'none';
            }
        } catch {
            showMessage(errorMessage, 'Failed to submit document.');
            successMessage.style.display = 'none';
        }
    });
    
    function updateMainContent() {
        const dropZoneContainer = document.querySelector('.drop-zone-container');
    
        if (dropZoneContainer) {
            if (window.innerWidth > 768) {
                dropZoneContainer.style.flexDirection = 'row';
            } else {
                dropZoneContainer.style.flexDirection = 'column';
            }
        }
    }
    
    updateMainContent();
    window.addEventListener('resize', updateMainContent);
    
});
