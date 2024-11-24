<?php
require_once '../logic/authentication-check.php';

$is_deleted_view = isset($_GET['deleted']) ? filter_var($_GET['deleted'], FILTER_VALIDATE_BOOLEAN) : false;

$whereCondition = $is_deleted_view ? 'deleted_at IS NOT NULL' : 'deleted_at IS NULL';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

if (isset($_GET['items_per_page']) && filter_var($_GET['items_per_page'], FILTER_VALIDATE_INT) !== false) {
    $itemsPerPage = (int)$_GET['items_per_page'];
    $_SESSION['items_per_page'] = $itemsPerPage;
} else {
    $itemsPerPage = (!empty($_SESSION['items_per_page']) && filter_var($_SESSION['items_per_page'], FILTER_VALIDATE_INT) !== false) ? (int)$_SESSION['items_per_page'] : 20;
}

$page = (isset($_GET['page']) && filter_var($_GET['page'], FILTER_VALIDATE_INT) !== false) ? (int)$_GET['page'] : 1;

if ($itemsPerPage === 'all') {
    $limit = null;
    $offset = 0;
} else {
    $limit = $itemsPerPage;
    $offset = ($page - 1) * $limit;
}

$sort = !empty($_GET['sort']) ? $_GET['sort'] : 'sent_datetime';
$direction = (!empty($_GET['direction']) && $_GET['direction'] === 'ASC') ? 'ASC' : 'DESC';

$validSortColumns = ['file_name', 'file_type', 'attachment_size', 'sent_datetime', 'sent_to'];
if (!in_array($sort, $validSortColumns)) {
    $sort = 'sent_datetime';
}

$query = '
    SELECT
        id
        ,file_name
        ,file_type
        ,attachment_size
        ,sent_datetime
        ,sent_to
    FROM
        user_attachments_metadata
    WHERE
        user_id = :user_id
        AND ' . $whereCondition;

if (!empty($searchTerm)) {
    $query .= '
    AND
    (
        file_name LIKE :searchTerm
        OR sent_to LIKE :searchTerm
    )';
}

$query .= ' ORDER BY ' . $sort . ' ' . strtoupper($direction);

if (!empty($limit)) {
    $query .= ' LIMIT :limit OFFSET :offset';
}

$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

if ($searchTerm !== '') {
    $searchTermWildcard = '%' . $searchTerm . '%';
    $stmt->bindParam(':searchTerm', $searchTermWildcard, PDO::PARAM_STR);
}

if ($limit) {
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
}

$stmt->execute();
$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countQuery = '
    SELECT
        COUNT(*)
    FROM
        user_attachments_metadata
    WHERE
        user_id = :user_id
        AND ' . $whereCondition;

if (!empty($searchTerm)) {
    $countQuery .= '
        AND
        (
            file_name LIKE :searchTerm
            OR sent_to LIKE :searchTerm
        )';
}

$countStmt = $pdo->prepare($countQuery);
$countStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

if (!empty($searchTerm)) {
    $countStmt->bindParam(':searchTerm', $searchTermWildcard, PDO::PARAM_STR);
}

$countStmt->execute();
$totalItems = $countStmt->fetchColumn();
$totalPages = $limit ? ceil($totalItems / $limit) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_deleted_view ? 'Archived Documents' : 'Your Documents'; ?></title>
    <link rel="preload" href="../fonts/roboto/Roboto-Regular.woff2" as="font" type="font/woff2" crossorigin="anonymous">
    <link rel="preload" href="../fonts/roboto/Roboto-Bold.woff2" as="font" type="font/woff2" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/styles.css">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="../apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon-16x16.png">
    <link rel="manifest" href="../site.webmanifest">
</head>
<body>

<button id="themeToggle">
    <i class="fas fa-moon"></i>
</button>

<div class="hamburger-menu">
    <i class="fas fa-bars"></i>

    <?php if ($is_deleted_view): ?>
        <span class="bread-crumbs">
            &nbsp;<a class="link-text" href="documents.php"><span class="link-text">Documents</span></a>
            &nbsp;<i class="fas fa-folder"></i>
            &nbsp;<i class="fas fa-angle-right"></i>
            &nbsp;<span class="link-text">Archived</span>
            &nbsp;<i class="fas fa-archive"></i>
    <?php endif; ?>
</div>

<div class="sidebar">
    <a href="submit.php" class="sidebar-link"><span class="link-text">Submit</span><i class="fas fa-upload"></i></a>
    <?php if ($is_deleted_view): ?>
        <a href="documents.php" class="sidebar-link"><span class="link-text">Documents</span><i class="fas fa-folder"></i></a>
        <a class="sidebar-link"><i class="fas fa-angle-right"></i><span class="link-text" style="text-decoration: underline;">Archived</span><i class="fas fa-archive"></i></a>
    <?php else: ?>
        <a class="sidebar-link"><span class="link-text" style="text-decoration: underline;">Documents</span><i class="fas fa-folder"></i></a>
        <a href="documents.php?deleted=true" class="sidebar-link"><i class="fas fa-angle-right"></i><span class="link-text">Archived</span><i class="fas fa-archive"></i></a>
    <?php endif; ?>
    <a href="edit-user.php" class="sidebar-link"><span class="link-text">Settings</span><i class="fas fa-cog"></i></a>
    <a href="../logic/logout.php" class="sidebar-link"><span class="link-text">Logout</span><i class="fas fa-sign-out-alt"></i></a>
    <a href="about.php" class="sidebar-link"><span class="link-text">About</span><i class="fas fa-info-circle"></i></a>
</div>

<div class="main-content">

    <div class="success-message" id="successMessage">Document <?php echo $is_deleted_view ? 'restored/deleted' : 'archived'; ?> successfully!</div>
    <div class="error-message" id="errorMessage">Failed to <?php echo $is_deleted_view ? 'restore/delete' : 'archive'; ?> document.</div>

    <div class="container">
        <br><br>
        <h2><?php echo $is_deleted_view ? 'Archived Documents' : 'Your Documents'; ?></h2>

        <form method="GET" id="itemsPerPageForm">

            <div class="search-container">
                <div class="search-wrapper">
                    <input type="text" name="search" id="search" placeholder="Search documents..." class="search-box" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <span id="clearSearch" class="clear-btn"><i class="fas fa-times"></i></span>
                </div>
                <button type="submit" id="searchButton">Search</button>
            </div>

            <div class="document-box">
                <table id="documentsTable" class="<?php echo $is_deleted_view ? 'restore' : ''; ?>">
                    <thead>
                        <tr>
                            <th>
                                <a href="?sort=file_name&direction=<?php echo $sort === 'file_name' && $direction === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($searchTerm); ?>&items_per_page=<?php echo $itemsPerPage; ?>&deleted=<?php echo $is_deleted_view ? 'true' : 'false'; ?>">File Name | Preview</a>
                                <?php if ($sort === 'file_name'): ?>
                                    <a class="no-underline" href="?sort=file_name&direction=<?php echo $sort === 'file_name' && $direction === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($searchTerm); ?>&items_per_page=<?php echo $itemsPerPage; ?>&deleted=<?php echo $is_deleted_view ? 'true' : 'false'; ?>"><i class="fa <?php echo $direction === 'ASC' ? 'fa-sort-up' : 'fa-sort-down'; ?>"></i></a>
                                <?php endif; ?>
                            </th>
                            <th>
                                <a href="?sort=file_type&direction=<?php echo $sort === 'file_type' && $direction === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($searchTerm); ?>&items_per_page=<?php echo $itemsPerPage; ?>&deleted=<?php echo $is_deleted_view ? 'true' : 'false'; ?>">File Type</a>
                                <?php if ($sort === 'file_type'): ?>
                                    <a class="no-underline" href="?sort=file_type&direction=<?php echo $sort === 'file_type' && $direction === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($searchTerm); ?>&items_per_page=<?php echo $itemsPerPage; ?>&deleted=<?php echo $is_deleted_view ? 'true' : 'false'; ?>"><i class="fa <?php echo $direction === 'ASC' ? 'fa-sort-up' : 'fa-sort-down'; ?>"></i></a>
                                <?php endif; ?>
                            </th>
                            <th>
                                <a href="?sort=attachment_size&direction=<?php echo $sort === 'attachment_size' && $direction === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($searchTerm); ?>&items_per_page=<?php echo $itemsPerPage; ?>&deleted=<?php echo $is_deleted_view ? 'true' : 'false'; ?>">Size</a>
                                <?php if ($sort === 'attachment_size'): ?>
                                    <a class="no-underline" href="?sort=attachment_size&direction=<?php echo $sort === 'attachment_size' && $direction === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($searchTerm); ?>&items_per_page=<?php echo $itemsPerPage; ?>&deleted=<?php echo $is_deleted_view ? 'true' : 'false'; ?>"><i class="fa <?php echo $direction === 'ASC' ? 'fa-sort-up' : 'fa-sort-down'; ?>"></i></a>
                                <?php endif; ?>
                            </th>
                            <th>
                                <a href="?sort=sent_datetime&direction=<?php echo $sort === 'sent_datetime' && $direction === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($searchTerm); ?>&items_per_page=<?php echo $itemsPerPage; ?>&deleted=<?php echo $is_deleted_view ? 'true' : 'false'; ?>">Sent Date</a>
                                <?php if ($sort === 'sent_datetime'): ?>
                                    <a class="no-underline" href="?sort=sent_datetime&direction=<?php echo $sort === 'sent_datetime' && $direction === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($searchTerm); ?>&items_per_page=<?php echo $itemsPerPage; ?>&deleted=<?php echo $is_deleted_view ? 'true' : 'false'; ?>"><i class="fa <?php echo $direction === 'ASC' ? 'fa-sort-up' : 'fa-sort-down'; ?>"></i></a>
                                <?php endif; ?>
                            </th>
                            <th>
                                <a href="?sort=sent_to&direction=<?php echo $sort === 'sent_to' && $direction === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($searchTerm); ?>&items_per_page=<?php echo $itemsPerPage; ?>&deleted=<?php echo $is_deleted_view ? 'true' : 'false'; ?>">Sent To</a>
                                <?php if ($sort === 'sent_to'): ?>
                                    <a class="no-underline" href="?sort=sent_to&direction=<?php echo $sort === 'sent_to' && $direction === 'ASC' ? 'DESC' : 'ASC'; ?>&search=<?php echo urlencode($searchTerm); ?>&items_per_page=<?php echo $itemsPerPage; ?>&deleted=<?php echo $is_deleted_view ? 'true' : 'false'; ?>"><i class="fa <?php echo $direction === 'ASC' ? 'fa-sort-up' : 'fa-sort-down'; ?>"></i></a>
                                <?php endif; ?>
                            </th>
                            <th>Download</th>
                            <th><?php echo $is_deleted_view ? 'Delete' : 'Archive'; ?></th>
                            <?php if ($is_deleted_view): ?>
                                <th>Restore</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attachments as $attachment): ?>
                            <tr>
                                <td><a href="../logic/view-document.php?id=<?php echo $attachment['id']; ?>" data-file-type="<?php echo htmlspecialchars($attachment['file_type']); ?>"><?php echo htmlspecialchars($attachment['file_name']); ?></a></td>
                                <td><?php echo htmlspecialchars($attachment['file_type']); ?></td>
                                <td>
                                <?php 
                                    if ($attachment['attachment_size'] < 1024 * 1024) {
                                        echo round($attachment['attachment_size'] / 1024, 0) . ' KB';
                                    } else {
                                        echo round($attachment['attachment_size'] / 1024 / 1024, 2) . ' MB';
                                    }
                                ?>
                                </td>
                                <td><?php echo htmlspecialchars($attachment['sent_datetime']); ?></td>
                                <td><a href="mailto:<?php echo htmlspecialchars($attachment['sent_to']); ?>"><?php echo htmlspecialchars($attachment['sent_to']); ?></a></td>
                                <td>
                                    <a href="#" class="download-link" data-id="<?php echo $attachment['id']; ?>">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                                <td>
                                    <a href="#" class="delete-link" data-id="<?php echo $attachment['id']; ?>" data-filename="<?php echo htmlspecialchars($attachment['file_name']); ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                                <?php if ($is_deleted_view): ?>
                                    <td>
                                        <a href="#" class="restore-link" data-id="<?php echo $attachment['id']; ?>" data-filename="<?php echo htmlspecialchars($attachment['file_name']); ?>">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="items-per-page-container">
                <label for="items_per_page">Items per page:&nbsp;</label>
                <select name="items_per_page" id="items_per_page" onchange="document.getElementById('itemsPerPageForm').submit()">
                    <option value="20" <?php echo $itemsPerPage === 20 ? 'selected' : ''; ?>>20</option>
                    <option value="40" <?php echo $itemsPerPage === 40 ? 'selected' : ''; ?>>40</option>
                    <option value="100" <?php echo $itemsPerPage === 100 ? 'selected' : ''; ?>>100</option>
                    <option value="all" <?php echo $itemsPerPage === 'all' ? 'selected' : ''; ?>>All</option>
                </select>
            </div>

            <input type="hidden" name="deleted" value="<?php echo $is_deleted_view ? 'true' : 'false'; ?>">
        </form>

        <?php if ($limit): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&items_per_page=<?php echo $itemsPerPage; ?>&deleted=<?php echo $is_deleted_view ? 'true' : 'false'; ?>&search=<?php echo urlencode($searchTerm); ?>">
                        <i class="fas fa-angle-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php if ($totalPages > 1): ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&items_per_page=<?php echo $itemsPerPage; ?>&deleted=<?php echo $is_deleted_view ? 'true' : 'false'; ?>&search=<?php echo urlencode($searchTerm); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&items_per_page=<?php echo $itemsPerPage; ?>&deleted=<?php echo $is_deleted_view ? 'true' : 'false'; ?>&search=<?php echo urlencode($searchTerm); ?>">
                        Next <i class="fas fa-angle-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <div id="confirmationDialog" class="confirmationModal">
        <div class="modal-content">
            <h2>Confirm <?php echo $is_deleted_view ? 'Permanently Delete' : 'Archive'; ?></h2>
            <p id="confirmationText">Are you sure you want to <?php echo $is_deleted_view ? 'permanently delete' : 'archive'; ?> "<span id="fileName"></span>"?</p>
            <div class="button-group">
                <button id="confirmDelete" class="cancel-button">Yes</button>
                <button id="cancelDelete" class="confirm-button">No</button>
            </div>
        </div>
    </div>

    <div id="restoreConfirmationDialog" class="confirmationModal">
        <div class="modal-content">
            <h2>Confirm Restore</h2>
            <p id="confirmationText">Are you sure you want to restore "<span id="restoreFileName"></span>"?</p>
            <div class="button-group">
                <button id="confirmRestore" class="confirm-button">Yes</button>
                <button id="cancelRestore" class="cancel-button">No</button>
            </div>
        </div>
    </div>

</div>

<div id="fileModal" class="modal">
    <span class="close">&times;</span>
    <div class="modal-content">
        <iframe id="fileIframe" style="display: none;"></iframe>
        <img id="fileImage" style="display: none;" />
    </div>
</div>

<script src="../javascripts/theme.js"></script>
<script src="../javascripts/documents.js"></script>
<?php include '../logic/cookie-consent.php'; ?>

</body>
</html>
