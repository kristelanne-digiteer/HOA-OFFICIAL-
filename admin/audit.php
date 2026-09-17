<?php
include("../config/db.php");
include("../includes/admin_header.php");

$result = mysqli_query($conn, "
    SELECT a.*, u.first_name, u.last_name
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.user_id
    ORDER BY a.created_at DESC
");

$total      = mysqli_num_rows($result);
$login_cnt  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM audit_logs WHERE log_type='Authentication'"))['c'];
$trans_cnt  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM audit_logs WHERE log_type NOT IN ('Authentication','System')"))['c'];
$sys_cnt    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM audit_logs WHERE log_type='System'"))['c'];
?>

<section class="page-title">
    <h1>Audit Logs</h1>
    <p>Track user activity, transactions, and system changes</p>
</section>

<section class="cards">
    <div class="card">
        <h3>Total Logs</h3>
        <div class="value"><?php echo $total; ?></div>
        <div class="note">All recorded activities</div>
    </div>
    <div class="card">
        <h3>Login / Logout</h3>
        <div class="value"><?php echo $login_cnt; ?></div>
        <div class="note">Authentication records</div>
    </div>
    <div class="card">
        <h3>Transactions</h3>
        <div class="value"><?php echo $trans_cnt; ?></div>
        <div class="note">Payments, documents, rentals</div>
    </div>
    <div class="card">
        <h3>System</h3>
        <div class="value"><?php echo $sys_cnt; ?></div>
        <div class="note">Admin system actions</div>
    </div>
</section>

<div class="panel">
    <div class="panel-header">
        <h2>Activity Logs</h2>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="export_report.php?type=audit&format=pdf" class="btn">Export PDF</a>
            <a href="export_report.php?type=audit&format=excel" class="btn">Export Excel</a>
            <a href="clear_audit_logs.php"
               class="btn btn-delete"
               onclick="return confirm('Clear ALL audit logs? This cannot be undone.')">
                Clear Logs
            </a>
        </div>
    </div>

    <table class="table">
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Action</th>
            <th>Type</th>
            <th>Date</th>
        </tr>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php
                    
                    $type = $row['log_type'];
                    if ($type === 'Authentication')    $badge = 'yellow';
                    elseif ($type === 'Payment')       $badge = 'green';
                    elseif ($type === 'Document')      $badge = 'yellow';
                    elseif ($type === 'Rental')        $badge = 'yellow';
                    elseif ($type === 'System')        $badge = 'red';
                    else                               $badge = 'yellow';
                ?>
                <tr>
                    <td><?php echo $row['log_id']; ?></td>
                    <td><?php echo htmlspecialchars(($row['first_name'] ?? 'System') . ' ' . ($row['last_name'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($row['action']); ?></td>
                    <td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($type); ?></span></td>
                    <td><?php echo $row['created_at']; ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No audit logs found.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php include("../includes/admin_footer.php"); ?>