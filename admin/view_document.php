<?php
include("../config/db.php");
include("../includes/admin_header.php");

$id = $_GET['id'];

$result = mysqli_query($conn, "
    SELECT d.*, u.first_name, u.last_name
    FROM document_requests d
    LEFT JOIN users u ON d.user_id = u.user_id
    WHERE d.request_id='$id'
");

$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo "<script>alert('Document request not found'); window.location='documents.php';</script>";
    exit;
}
?>

<section class="page-title">
    <h1>View Document Request</h1>
    <p>Review homeowner document request details</p>
</section>

<div class="panel">
    <table class="table">
        <tr><th>Field</th><th>Details</th></tr>
        <tr><td>Request ID</td><td><?php echo $row['request_id']; ?></td></tr>
        <tr><td>Homeowner</td><td><?php echo htmlspecialchars(($row['first_name'] ?? 'Homeowner') . ' ' . ($row['last_name'] ?? '')); ?></td></tr>
        <tr><td>Document Type</td><td><?php echo htmlspecialchars($row['document_type']); ?></td></tr>
        <tr><td>Purpose</td><td><?php echo htmlspecialchars($row['purpose']); ?></td></tr>
        <tr><td>Status</td><td><?php echo htmlspecialchars($row['status']); ?></td></tr>
        <tr><td>Date Requested</td><td><?php echo $row['date_requested']; ?></td></tr>
    </table>

    <br>
    <a href="approve_document.php?id=<?php echo $row['request_id']; ?>" class="btn btn-edit">Approve</a>
    <a href="complete_document.php?id=<?php echo $row['request_id']; ?>" class="btn btn-view">Complete</a>
    <a href="reject_document.php?id=<?php echo $row['request_id']; ?>" class="btn btn-delete">Reject</a>
    <a href="documents.php" class="btn">Back</a>
</div>

<?php include("../includes/admin_footer.php"); ?>