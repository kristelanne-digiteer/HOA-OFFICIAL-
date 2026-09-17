<?php
include("../config/db.php");
include("../includes/admin_header.php");

$soa_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM soa_records"));
$pending_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM payments WHERE status='pending'"));
$receipt_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM payments WHERE status='approved'"));

$overdue_count = mysqli_num_rows(mysqli_query($conn, "
    SELECT * FROM soa_records 
    WHERE status='unpaid' AND due_date < CURDATE()
"));

$soa_records = mysqli_query($conn, "
    SELECT s.*,
           COALESCE(h.name, 'Unknown') AS name
    FROM soa_records s
    LEFT JOIN homeowners_masterlist h ON s.homeowner_id = h.homeowner_id
    ORDER BY s.soa_id DESC
");

$payment_records = mysqli_query($conn, "
    SELECT p.*, h.name
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.user_id
    LEFT JOIN homeowner_profiles hp ON u.user_id = hp.user_id
    LEFT JOIN homeowners_masterlist h ON hp.masterlist_id = h.homeowner_id
    ORDER BY p.payment_id DESC
");

$pending_payments = mysqli_query($conn, "
    SELECT p.*, h.name
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.user_id
    LEFT JOIN homeowner_profiles hp ON u.user_id = hp.user_id
    LEFT JOIN homeowners_masterlist h ON hp.masterlist_id = h.homeowner_id
    WHERE p.status='pending'
    ORDER BY p.payment_id DESC
");

$receipts = mysqli_query($conn, "
    SELECT p.*, h.name
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.user_id
    LEFT JOIN homeowner_profiles hp ON u.user_id = hp.user_id
    LEFT JOIN homeowners_masterlist h ON hp.masterlist_id = h.homeowner_id
    WHERE p.status='approved'
    ORDER BY p.payment_id DESC
");

$overdue_accounts = mysqli_query($conn, "
    SELECT s.*,
           COALESCE(h.name, 'Unknown') AS name
    FROM soa_records s
    LEFT JOIN homeowners_masterlist h ON s.homeowner_id = h.homeowner_id
    WHERE s.status='unpaid' AND s.due_date < CURDATE()
    ORDER BY s.due_date ASC
");
?>

<section class="page-title">
    <h1>Payments & Billing</h1>
    <p>Manage SOA, payment records, receipts, and overdue accounts</p>
</section>

<section class="cards">
    <div class="card">
        <h3>SOA Records</h3>
        <div class="value"><?php echo $soa_count; ?></div>
        <div class="note">Billing records</div>
    </div>

    <div class="card">
        <h3>Pending Payments</h3>
        <div class="value"><?php echo $pending_count; ?></div>
        <div class="note">For verification</div>
    </div>

    <div class="card">
        <h3>Receipts</h3>
        <div class="value"><?php echo $receipt_count; ?></div>
        <div class="note">Approved payments</div>
    </div>

    <div class="card">
        <h3>Overdue</h3>
        <div class="value"><?php echo $overdue_count; ?></div>
        <div class="note">Past due accounts</div>
    </div>
</section>

<div class="tabs">
    <button class="tab-btn active" onclick="openTab(event, 'billing')">Billing Setup</button>
    <button class="tab-btn" onclick="openTab(event, 'records')">Payment Records</button>
    <button class="tab-btn" onclick="openTab(event, 'pending')">Pending Payments</button>
    <button class="tab-btn" onclick="openTab(event, 'receipts')">Receipts</button>
    <button class="tab-btn" onclick="openTab(event, 'overdue')">Overdue Accounts</button>
</div>

<div id="billing" class="tab-content active-tab">
    <div class="panel">
        <div class="panel-header">
            <h2>Billing Setup</h2>
            <div style="display:flex; gap:10px;">
    
        <a href="create_bill.php" class="btn btn-primary">
        Create Bills
        </a>

        <a href="clear_paid_soa.php"
         class="btn btn-delete"
         onclick="return confirm('Clear all paid SOA records?')">
        Clear Paid
    </a>

</div>
        </div>

        <p style="color:#cbd5d6; margin-bottom:18px;">
            Set monthly dues, penalties, due dates, and generate SOA records for homeowners.
        </p>
    <form method="POST" action="bulk_mark_paid.php">

<div style="margin-bottom:15px;">
    <button
        type="submit"
        class="btn"
        onclick="return confirm('Mark selected SOA records as paid?')">
        Mark Selected Paid
    </button>
</div>
        <table class="table">
            <tr>
                <th><input type="checkbox" id="checkAll"></th>
                <th>SOA ID</th>
                <th>Homeowner</th>
                <th>Billing Month</th>
                <th>Monthly Dues</th>
                <th>Penalty</th>
                <th>Total Balance</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($soa_records)) { ?>
                <tr>
                   <td>
        <?php if($row['status'] != 'paid') { ?>
            <input
                type="checkbox"
                name="selected_soa[]"
                value="<?php echo $row['soa_id']; ?>">
        <?php } ?>
    </td>

                    <td><?php echo $row['soa_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['billing_month']); ?></td>
                    <td>₱<?php echo number_format($row['monthly_dues'], 2); ?></td>
                    <td>₱<?php echo number_format($row['penalties'], 2); ?></td>
                    <td>₱<?php echo number_format($row['total_balance'], 2); ?></td>
                    <td><?php echo $row['due_date']; ?></td>
                    <td>
                        <span class="badge <?php echo $row['status'] == 'paid' ? 'green' : 'yellow'; ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                    <td class="action-buttons">
                        <?php if ($row['status'] == 'unpaid') { ?>
                            <a href="mark_soa_paid.php?id=<?php echo $row['soa_id']; ?>"
                               class="btn btn-edit"
                               onclick="return confirm('Mark this SOA as paid?');">
                               Mark Paid
                            </a>
                        <?php } ?>

                        <a href="view_soa.php?id=<?php echo $row['soa_id']; ?>" class="btn btn-view">View SOA</a>
                        <a href="add_penalty.php?id=<?php echo $row['soa_id']; ?>" class="btn btn-delete">Penalty</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
        </form>
    </div>
</div>

<div id="records" class="tab-content">
    <div class="panel">
        <h2>Payment Records</h2>

        <table class="table">
            <tr>
                <th>Payment ID</th>
                <th>Homeowner</th>
                <th>Amount</th>
                <th>Payment Date</th>
                <th>Method</th>
                <th>Status</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($payment_records)) { ?>
                <tr>
                    <td><?php echo $row['payment_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?></td>
                    <td>₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                    <td><?php echo $row['payment_date']; ?></td>
                    <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                    <td>
                        <span class="badge <?php echo $row['status'] == 'approved' ? 'green' : ($row['status'] == 'rejected' ? 'red' : 'yellow'); ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<div id="pending" class="tab-content">
    <div class="panel">
        <h2>Pending Payments</h2>

        <table class="table">
            <tr>
                <th>Payment ID</th>
                <th>Homeowner</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Proof</th>
                <th>Actions</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($pending_payments)) { ?>
                <tr>
                    <td><?php echo $row['payment_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?></td>
                    <td>₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                    <td>
                        <?php if (!empty($row['proof_of_payment'])) { ?>
                            <a href="../assets/uploads/payments/<?php echo htmlspecialchars($row['proof_of_payment']); ?>" class="btn btn-view" target="_blank">View Proof</a>
                        <?php } else { ?>
                            No proof
                        <?php } ?>
                    </td>
                    <td class="action-buttons">
                        <a href="approve_payment.php?id=<?php echo $row['payment_id']; ?>" class="btn btn-edit">Approve</a>
                        <a href="reject_payment.php?id=<?php echo $row['payment_id']; ?>" class="btn btn-delete">Reject</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<div id="receipts" class="tab-content">
    <div class="panel">
        <h2>Receipts</h2>
    <div class="panel-header">
    <a href="clear_receipts.php" class="btn btn-delete"
       onclick="return confirm('Clear all approved receipts? This will not delete SOA records.');">
       Clear Receipts
    </a>
</div>
        <table class="table">
            <tr>
                <th>Receipt No.</th>
                <th>Homeowner</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($receipts)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['receipt_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?></td>
                    <td>₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                    <td><?php echo $row['payment_date']; ?></td>
                    <td>
                        <a href="receipt.php?id=<?php echo $row['payment_id']; ?>" class="btn btn-view">Download PDF</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<div id="overdue" class="tab-content">
    <div class="panel">
        <h2>Overdue Accounts</h2>

        <table class="table">
            <tr>
                <th>SOA ID</th>
                <th>Homeowner</th>
                <th>Total Balance</th>
                <th>Due Date</th>
                <th>Action</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($overdue_accounts)) { ?>
                <tr>
                    <td><?php echo $row['soa_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td>₱<?php echo number_format($row['total_balance'], 2); ?></td>
                    <td><?php echo $row['due_date']; ?></td>
                    <td>
                        <a href="#" class="btn btn-delete">Send Reminder</a>
                    </td>
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
<script>
document.addEventListener("DOMContentLoaded", function() {

    const checkAll = document.getElementById("checkAll");

    if(checkAll){

        checkAll.addEventListener("change", function(){

            document.querySelectorAll(
                'input[name="selected_soa[]"]'
            ).forEach(cb => {

                cb.checked = this.checked;

            });

        });

    }

});
</script>
<?php include("../includes/admin_footer.php"); ?>