<?php
include("../config/db.php");
include("../includes/admin_header.php");

$id = $_GET['id'];

$result = mysqli_query($conn, "
    SELECT s.*, h.name, h.full_address, h.contact_no
    FROM soa_records s
    JOIN homeowners_masterlist h ON s.homeowner_id = h.homeowner_id
    WHERE s.soa_id='$id'
");

$row = mysqli_fetch_assoc($result);
?>

<section class="page-title">
    <h1>Statement of Account</h1>
    <p>View homeowner billing details</p>
</section>

<div class="panel">
    <table class="table">
        <tr><th>Field</th><th>Details</th></tr>
        <tr><td>SOA ID</td><td><?php echo $row['soa_id']; ?></td></tr>
        <tr><td>Homeowner</td><td><?php echo htmlspecialchars($row['name']); ?></td></tr>
        <tr><td>Address</td><td><?php echo htmlspecialchars($row['full_address']); ?></td></tr>
        <tr><td>Contact</td><td><?php echo htmlspecialchars($row['contact_no']); ?></td></tr>
        <tr><td>Billing Month</td><td><?php echo htmlspecialchars($row['billing_month']); ?></td></tr>
        <tr><td>Purpose</td><td><?php echo htmlspecialchars($row['payment_purpose']); ?></td></tr>
        <tr><td>Monthly Dues</td><td>₱<?php echo number_format($row['monthly_dues'], 2); ?></td></tr>
        <tr><td>Penalty</td><td>₱<?php echo number_format($row['penalties'], 2); ?></td></tr>
        <tr><td>Total Balance</td><td>₱<?php echo number_format($row['total_balance'], 2); ?></td></tr>
        <tr><td>Due Date</td><td><?php echo $row['due_date']; ?></td></tr>
        <tr><td>Status</td><td><?php echo $row['status']; ?></td></tr>
    </table>

    <br>
    <a href="payments.php" class="btn">Back</a>
</div>

<?php include("../includes/admin_footer.php"); ?>