<?php
include("../config/db.php");
include("../includes/admin_header.php");

$total_homeowners = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM homeowners_masterlist"));
$pending_docs     = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM document_requests WHERE status = 'pending'"));
$upcoming_events  = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM events WHERE event_date >= CURDATE()"));

$unpaid_query = mysqli_query($conn, "SELECT SUM(total_balance) AS unpaid_total FROM soa_records WHERE status = 'unpaid'");
$unpaid_row   = mysqli_fetch_assoc($unpaid_query);
$unpaid_total = $unpaid_row['unpaid_total'] ?? 0;

$audit_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM audit_logs"))['c'];
?>

<section class="page-title">
    <h1>Dashboard</h1>
    <p>Overview of your community</p>
</section>

<div class="panel" style="display:flex; align-items:center; gap:18px;">
    <img src="<?php echo $admin_photo; ?>" class="profile-photo" alt="Admin">
    <div>
        <h2 style="margin-bottom:6px;">
            Welcome, <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>!
        </h2>
        <p style="color:#cbd5d6;">
            Account Type: <span class="badge red">Administrator</span>
        </p>
    </div>
</div>

<section class="cards">
    <a href="homeowners.php" class="card dashboard-card">
        <h3>Total Homeowners</h3>
        <div class="value"><?php echo $total_homeowners; ?></div>
        <div class="note">Active masterlist records</div>
    </a>
    <a href="payments.php" class="card dashboard-card">
        <h3>Unpaid Dues</h3>
        <div class="value">₱<?php echo number_format($unpaid_total, 2); ?></div>
        <div class="note">Unpaid SOA records</div>
    </a>
    <a href="documents.php" class="card dashboard-card">
        <h3>Pending Requests</h3>
        <div class="value"><?php echo $pending_docs; ?></div>
        <div class="note">Documents pending</div>
    </a>
    <a href="events.php" class="card dashboard-card">
        <h3>Upcoming Events</h3>
        <div class="value"><?php echo $upcoming_events; ?></div>
        <div class="note">This month</div>
    </a>
</section>

<section class="content-grid">
    <div class="panel">
        <h2>Recent Activities</h2>
        <table class="table">
            <tr>
                <th>Activity</th>
                <th>Type</th>
                <th>Date</th>
            </tr>
            <?php
            $logs = mysqli_query($conn, "SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 5");
            if (mysqli_num_rows($logs) > 0) {
                while ($log = mysqli_fetch_assoc($logs)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                    <td><span class="badge yellow"><?php echo htmlspecialchars($log['log_type']); ?></span></td>
                    <td><?php echo $log['created_at']; ?></td>
                </tr>
            <?php }
            } else { ?>
                <tr><td colspan="3">No recent activities found.</td></tr>
            <?php } ?>
        </table>
    </div>

    <div class="panel">
        <h2>Quick Actions</h2>
        <a href="add_homeowner.php" class="btn quick-btn">Add Homeowner</a>
        <a href="create_bill.php" class="btn quick-btn">Create Bill</a>
        <a href="events.php" class="btn quick-btn">Post Announcement</a>
        <a href="documents.php" class="btn quick-btn">Approve Requests</a>

        <?php if ($audit_count > 0): ?>
        <a href="clear_audit_logs.php"
           class="btn btn-delete quick-btn"
           style="margin-top:12px;"
           onclick="return confirm('Clear ALL audit logs? This cannot be undone.')">
            🗑 Clear Audit Logs (<?php echo $audit_count; ?> records)
        </a>
        <?php endif; ?>
    </div>
</section>

<?php include("../includes/admin_footer.php"); ?>