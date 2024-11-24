document.addEventListener('DOMContentLoaded', () => {

    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');
    const isDeletedView = new URLSearchParams(window.location.search).get('deleted') === 'true';
    const downloadLinks = document.querySelectorAll('.download-link');
    const themeToggleButton1 = document.getElementById('themeToggle');

    function makePageDisabled() {
        themeToggleButton1.style.display = 'none';
        themeToggleButton1.classList.add('hide');
    }

    function makePageEnabled() {
        themeToggleButton1.style.display = 'block';
        themeToggleButton1.classList.remove('hide');
    }

    function showMessage(messageElement, message, duration = 3000) {
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

    // Details
    const tooltips = document.querySelectorAll('.tooltip');

    tooltips.forEach(tooltip => {
        tooltip.addEventListener('click', function (e) {
            e.stopPropagation();
            this.classList.toggle('active');
        });
    });

    document.addEventListener('click', function () {
        tooltips.forEach(tooltip => tooltip.classList.remove('active'));
    });

    // Search functionality
    document.getElementById('searchButton').addEventListener('click', function() {
        const itemsPerPageForm = document.getElementById('itemsPerPageForm');
        const urlParams = new URLSearchParams(new FormData(itemsPerPageForm));
        urlParams.set('page', 1);
        window.location.search = urlParams.toString();
    });

    const searchInput = document.getElementById("search");
    const clearBtn = document.getElementById("clearSearch");
    const searchForm = document.getElementById("itemsPerPageForm");

    searchInput.addEventListener("input", function () {
        clearBtn.style.display = searchInput.value.trim() ? "inline" : "none";
    });

    clearBtn.addEventListener("click", function () {
        searchInput.value = "";
        clearBtn.style.display = "none";
        searchForm.submit();
    });

    if (searchInput.value.trim()) {
        clearBtn.style.display = "inline";
    }    
    
    // File preview modal
    document.querySelectorAll('#documentsTable a[data-file-type]').forEach(link => {
        link.addEventListener('click', function(event) {
            try {
                makePageDisabled();
                event.preventDefault();
                const fileType = this.getAttribute('data-file-type');
                const fileUrl = this.href;
                const modal = document.getElementById('fileModal');
                const iframe = document.getElementById('fileIframe');
                const img = document.getElementById('fileImage');
    
                if (fileType === 'application/pdf') {
                    iframe.src = fileUrl;
                    iframe.style.display = 'block';
                    img.style.display = 'none';
                    modal.style.display = 'block';
    
                    window.onclick = function(event) {
                        makePageDisabled();

                        if (event.target === modal) {
                            modal.style.display = 'none';
                            iframe.src = '';
                        }
                        makePageEnabled();
                    };
                } else if (fileType.startsWith('image/')) {
                    img.src = fileUrl;
                    img.style.display = 'block';
                    iframe.style.display = 'none';
                    modal.style.display = 'block';
    
                    window.onclick = function() {
                        makePageDisabled();
                        modal.style.display = 'none';
                        makePageEnabled();
                        img.src = '';
                    };
                }

            }
            finally {
                event.stopPropagation();
            }

        });
    });

    document.querySelector('.close').onclick = function(event) {
        makePageDisabled();
        document.getElementById('fileModal').style.display = 'none';
        document.getElementById('fileIframe').src = '';
        document.getElementById('fileImage').src = '';
        makePageEnabled();
    };

    // Confirmation dialog for delete
    document.querySelectorAll('.delete-link').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const fileId = this.getAttribute('data-id');
            const fileName = this.getAttribute('data-filename');
            const tableRow = this.closest('tr');
            document.getElementById('fileName').textContent = fileName;
            const dialog = document.getElementById('confirmationDialog');
            dialog.style.display = 'flex';

            document.getElementById('confirmDelete').onclick = async function() {
                try {
                    dialog.style.display = 'none';
                    let deleteUrl = `../logic/delete-document.php?id=${fileId}`;

                    if (isDeletedView) {
                        deleteUrl += '&permanent=true';
                    }

                    const response = await fetch(deleteUrl, {
                        method: 'GET'
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        tableRow.remove();
                        showMessage(successMessage, result.message, 3000);
                        errorMessage.style.display = 'none';
                    } else if (result.status === 'fatal') {
                        window.location.href = '../pages/login.php';
                        
                        return;
                    } else {
                        showMessage(errorMessage, result.message, 5000);
                        successMessage.style.display = 'none';
                    }
                } catch {
                    showMessage(errorMessage, isDeletedView ? 'Failed to delete document.' : 'Failed to archive document.', 5000);
                    successMessage.style.display = 'none';
                }
            };

            document.getElementById('cancelDelete').onclick = function() {
                dialog.style.display = 'none';
            };
        });
    });

    // Confirmation dialog for restore
    document.querySelectorAll('.restore-link').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const fileId = this.getAttribute('data-id');
            const fileName = this.getAttribute('data-filename');
            const tableRow = this.closest('tr');
            document.getElementById('restoreFileName').textContent = fileName;
            const restoreDialog = document.getElementById('restoreConfirmationDialog');
            restoreDialog.style.display = 'flex';

            document.getElementById('confirmRestore').onclick = async function() {
                restoreDialog.style.display = 'none';
                let restoreUrl = `../logic/restore-document.php?id=${fileId}`;

                try {
                    const response = await fetch(restoreUrl, {
                        method: 'GET'
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        tableRow.remove();
                        showMessage(successMessage, result.message, 3000);
                        errorMessage.style.display = 'none';
                    } else if (result.status === 'fatal') {
                        window.location.href = '../pages/login.php';
                        
                        return;
                    } else {
                        showMessage(errorMessage, result.message, 5000);
                        successMessage.style.display = 'none';
                    }
                } catch {
                    showMessage(errorMessage, 'Failed to restore document.', 5000);
                    successMessage.style.display = 'none';
                }
            };

            document.getElementById('cancelRestore').onclick = function() {
                restoreDialog.style.display = 'none';
            };
        });
    });

    // Download logic
    downloadLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const id = this.getAttribute('data-id');

            fetch(`../logic/download.php?id=${id}`)
                .then(async response => {
                    if (!response.ok) {

                        return Promise.reject();
                    }

                    const contentDisposition = response.headers.get('Content-Disposition');
                    let filename = 'document';

                    if (contentDisposition && contentDisposition.includes('filename=')) {
                        filename = contentDisposition.split('filename=')[1].split(';')[0].trim().replace(/"/g, '');
                    }

                    const blob = await response.blob();

                    return ({ blob, filename });
                })
                .then(({ blob, filename }) => {
                    if (blob.type === 'application/json') {
                        blob.text().then(text => {
                            const data = JSON.parse(text);
                            if (data.status === 'error') {
                                showMessage(errorMessage, data.message);
                                successMessage.style.display = 'none';
                            } else if (data.status === 'fatal') {
                                window.location.href = '../pages/login.php';
                                
                                return;
                            }
                        });

                        return;
                    }

                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');

                    a.style.display = 'none';
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                })
                .catch(() => {
                    showMessage(errorMessage, 'Failed to download document.');
                    successMessage.style.display = 'none';
                });
        });
    });

    function updateMainContent() {
        const mainContent = document.querySelector('.main-content');
        if (window.innerWidth > 768) {
            mainContent.style.marginLeft = '200px';
        } else {
            mainContent.style.marginLeft = '0';
            mainContent.style.padding = '10px';
        }
    }

    updateMainContent();
    window.addEventListener('resize', updateMainContent);

    function updateTableColumns() {
        const screenWidth = window.innerWidth;
        const fileTypeTh = document.querySelector('#documentsTable th:nth-child(2)');
        const detailsTh = document.querySelector('#documentsTable th:nth-child(3)');
        const sizeTh = document.querySelector('#documentsTable th:nth-child(4)');
        const sentDateTh = document.querySelector('#documentsTable th:nth-child(5)');
        const sentToTh = document.querySelector('#documentsTable th:nth-child(6)');
        const rows = document.querySelectorAll('#documentsTable tbody tr');

        if (fileTypeTh) {
            fileTypeTh.style.display = 'none';
        }

        rows.forEach(row => {
            const fileTypeTd = row.querySelector('td:nth-child(2)');

            if (fileTypeTd) {
                fileTypeTd.style.display = 'none';
            }
        });

        if (screenWidth < 1200) {
            if (detailsTh) {
                detailsTh.style.display = 'none';
            }

            if (sizeTh) {
                sizeTh.style.display = 'none';
            }

            if (sentDateTh) {
                sentDateTh.style.display = 'none';
            }

            if (sentToTh) {
                sentToTh.style.display = 'none';
            }

            rows.forEach(row => {
                if (row.querySelector('td:nth-child(3)')) {
                    row.querySelector('td:nth-child(3)').style.display = 'none';
                }

                if (row.querySelector('td:nth-child(4)')) {
                    row.querySelector('td:nth-child(4)').style.display = 'none';
                }

                if (row.querySelector('td:nth-child(5)')) {
                    row.querySelector('td:nth-child(5)').style.display = 'none';
                }

                if (row.querySelector('td:nth-child(6)')) {
                    row.querySelector('td:nth-child(6)').style.display = 'none';
                }
            });
        } else {
            if (detailsTh) {
                detailsTh.style.display = '';
            }

            if (sizeTh) {
                sizeTh.style.display = '';
            }

            if (sentDateTh) {
                sentDateTh.style.display = '';
            }

            if (sentToTh) {
                sentToTh.style.display = '';
            }

            rows.forEach(row => {
                if (row.querySelector('td:nth-child(3)')) {
                    row.querySelector('td:nth-child(3)').style.display = '';
                }

                if (row.querySelector('td:nth-child(4)')) {
                    row.querySelector('td:nth-child(4)').style.display = '';
                }

                if (row.querySelector('td:nth-child(5)')) {
                    row.querySelector('td:nth-child(5)').style.display = '';
                }

                if (row.querySelector('td:nth-child(6)')) {
                    row.querySelector('td:nth-child(6)').style.display = '';
                }
            });
        }
    }

    updateTableColumns();
    window.addEventListener('resize', updateTableColumns);
});
