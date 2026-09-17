<?php
include("../config/db.php");
include("../includes/admin_header.php");

// ── DELETE TEMPLATE LOGIC (FIXED REDIRECT) ──────────────────────────────────
if (isset($_GET['delete_template_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_template_id']);
    
    // I-execute ang delete query
    $delete_query = mysqli_query($conn, "DELETE FROM document_templates WHERE template_id = '$delete_id'");
    
    if ($delete_query) {
        // Gagamit ng $_SERVER['PHP_SELF'] para bumalik sa kung anong file man ito ngayon nang ligtas
        echo "<script>alert('Template deleted successfully.'); window.location='" . $_SERVER['PHP_SELF'] . "';</script>";
        exit;
    } else {
        echo "<script>alert('Failed to delete template. Something went wrong.');</script>";
    }
}

// ── MGA EXISTING QUERIES ─────────────────────────────────────────────────────
$all_requests = mysqli_query($conn, "
    SELECT d.*, u.first_name, u.last_name
    FROM document_requests d
    LEFT JOIN users u ON d.user_id = u.user_id
    ORDER BY d.request_id DESC
");

$pending_requests = mysqli_query($conn, "
    SELECT d.*, u.first_name, u.last_name
    FROM document_requests d
    LEFT JOIN users u ON d.user_id = u.user_id
    WHERE d.status='pending'
    ORDER BY d.request_id DESC
");

$completed_requests = mysqli_query($conn, "
    SELECT d.*, u.first_name, u.last_name
    FROM document_requests d
    LEFT JOIN users u ON d.user_id = u.user_id
    WHERE d.status='completed'
    ORDER BY d.request_id DESC
");

$templates = mysqli_query($conn, "
    SELECT * FROM document_templates
    ORDER BY template_id DESC
");

$total = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM document_requests"));
$pending_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM document_requests WHERE status='pending'"));
$completed_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM document_requests WHERE status='completed'"));
$template_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM document_templates"));
?>

<section class="page-title">
    <h1>Document Requests</h1>
    <p>Review, approve, reject, and process homeowner document requests</p>
</section>

<section class="cards">
    <div class="card">
        <h3>Total Requests</h3>
        <div class="value"><?php echo $total; ?></div>
        <div class="note">All document requests</div>
    </div>

    <div class="card">
        <h3>Pending Requests</h3>
        <div class="value"><?php echo $pending_count; ?></div>
        <div class="note">Needs review</div>
    </div>

    <div class="card">
        <h3>Completed</h3>
        <div class="value"><?php echo $completed_count; ?></div>
        <div class="note">Released documents</div>
    </div>

    <div class="card">
        <h3>Templates</h3>
        <div class="value"><?php echo $template_count; ?></div>
        <div class="note">Available templates</div>
    </div>
</section>

<div class="tabs">
    <button class="tab-btn active" onclick="openTab(event, 'requestList')">Request List</button>
    <button class="tab-btn" onclick="openTab(event, 'pending')">Pending Requests</button>
    <button class="tab-btn" onclick="openTab(event, 'completed')">Completed Requests</button>
    <button class="tab-btn" onclick="openTab(event, 'templates')">Document Templates</button>
</div>

<div id="requestList" class="tab-content active-tab">
    <div class="panel">
        <div class="panel-header">
            <h2>REQUEST LIST</h2>

            <a href="clear_document_requests.php"
               class="btn btn-delete"
               onclick="return confirm('Clear rejected requests?')">
               Clear Rejected
            </a>
        </div>

        <table class="table">
            <tr>
                <th>ID</th>
                <th>Homeowner</th>
                <th>Document Type</th>
                <th>Purpose</th>
                <th>Status</th>
                <th>Date Requested</th>
                <th>Actions</th>
            </tr>

            <?php if (mysqli_num_rows($all_requests) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($all_requests)) { ?>
                    <tr>
                        <td><?php echo $row['request_id']; ?></td>
                        <td><?php echo htmlspecialchars(($row['first_name'] ?? 'Homeowner') . ' ' . ($row['last_name'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($row['document_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                        <td>
                            <span class="badge <?php echo $row['status'] == 'completed' ? 'green' : ($row['status'] == 'rejected' ? 'red' : 'yellow'); ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td><?php echo $row['date_requested']; ?></td>
                        <td class="action-buttons">
                            <a href="view_document.php?id=<?php echo $row['request_id']; ?>" class="btn btn-view">View</a>

                            <?php if ($row['status'] == 'pending') { ?>
                                <a href="approve_document.php?id=<?php echo $row['request_id']; ?>" class="btn btn-edit">Approve</a>
                                <a href="reject_document.php?id=<?php echo $row['request_id']; ?>" class="btn btn-delete">Reject</a>
                            <?php } ?>

                            <?php if ($row['status'] == 'approved') { ?>
                                <a href="upload_document.php?id=<?php echo $row['request_id']; ?>" class="btn btn-view">Upload File</a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="7">No document requests found.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<div id="pending" class="tab-content">
    <div class="panel">
        <h2>Pending Requests</h2>

        <table class="table">
            <tr>
                <th>ID</th>
                <th>Homeowner</th>
                <th>Document Type</th>
                <th>Purpose</th>
                <th>Date Requested</th>
                <th>Actions</th>
            </tr>

            <?php if (mysqli_num_rows($pending_requests) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($pending_requests)) { ?>
                    <tr>
                        <td><?php echo $row['request_id']; ?></td>
                        <td><?php echo htmlspecialchars(($row['first_name'] ?? 'Homeowner') . ' ' . ($row['last_name'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($row['document_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                        <td><?php echo $row['date_requested']; ?></td>
                        <td class="action-buttons">
                            <a href="approve_document.php?id=<?php echo $row['request_id']; ?>" class="btn btn-edit">Approve</a>
                            <a href="reject_document.php?id=<?php echo $row['request_id']; ?>" class="btn btn-delete">Reject</a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="6">No pending requests.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<div id="completed" class="tab-content">
    <div class="panel">
        <div class="panel-header">
            <h2>COMPLETED REQUESTS</h2>

            <a href="clear_completed_documents.php"
               class="btn btn-delete"
               onclick="return confirm('Clear completed requests?')">
               Clear Completed
            </a>
        </div>

        <table class="table">
            <tr>
                <th>ID</th>
                <th>Homeowner</th>
                <th>Document Type</th>
                <th>Date Requested</th>
                <th>Actions</th>
            </tr>

            <?php if (mysqli_num_rows($completed_requests) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($completed_requests)) { ?>
                    <tr>
                        <td><?php echo $row['request_id']; ?></td>
                        <td><?php echo htmlspecialchars(($row['first_name'] ?? 'Homeowner') . ' ' . ($row['last_name'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($row['document_type']); ?></td>
                        <td><?php echo $row['date_requested']; ?></td>
                        <td class="action-buttons">
                            <a href="download_document.php?id=<?php echo $row['request_id']; ?>" class="btn btn-view">Download</a>
                            <a href="archive_document.php?id=<?php echo $row['request_id']; ?>" class="btn btn-delete">Archive</a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="5">No completed requests.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<div id="templates" class="tab-content">
    <div class="panel">
        <div class="panel-header">
            <h2>Document Templates</h2>
            <a href="add_template.php" class="btn">Add Template</a>
        </div>

        <table class="table">
            <tr>
                <th>ID</th>
                <th>Document Name</th>
                <th>Description</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

            <?php if (mysqli_num_rows($templates) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($templates)) { ?>
                    <tr>
                        <td><?php echo $row['template_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['document_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><span class="badge green"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        <td class="action-buttons">
                            <?php if (!empty($row['template_link'])) { ?>
                                <a href="<?php echo htmlspecialchars($row['template_link']); ?>" target="_blank" class="btn btn-view">Open</a>
                            <?php } ?>
                            <a href="edit_template.php?id=<?php echo $row['template_id']; ?>" class="btn btn-edit">Edit</a>
                            
                            <a href="?delete_template_id=<?php echo $row['template_id']; ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this template?')">
                               Delete
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="5">No document templates found.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<script>
function openTab(event, tabName) {
    let contents = document.querySelectorAll(".tab-content");
    let buttons = document.querySelectorAll(".tab-btn");

    contents.forEach(content => content.classList.remove("active-tab"));
    buttons.forEach(button => button.classList.remove("active"));

    document.getElementById(tabName).classList.add("active-tab");
    event.currentTarget.classList.add("active");
}
</script>

<?php include("../includes/admin_footer.php"); ?>