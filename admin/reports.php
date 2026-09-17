<?php
// Siguraduhing may session start sa pinakataas bago ang kahit ano para hindi mawala ang login state
if (session_status() === PHP_SESSION_NONE) {
    session_name('HOA_ADMIN_SESSION');
session_start();
}

include("../config/db.php");

// ─── POST ACTIONS HANDLING (Dapat bago mag-include ng Header) ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_closed_tickets'])) {
    mysqli_query($conn, "DELETE FROM support_tickets WHERE status='closed'");
    header("Location: reports.php?tab=incidents&cleared=1");
    exit;
}

include("../includes/admin_header.php");

// ─── PAYMENT SUMMARY ───────────────────────────────────────
$total_collected_q = mysqli_query($conn, "
    SELECT SUM(amount_paid) as total FROM payments WHERE status = 'approved'
");
$total_collected = mysqli_fetch_assoc($total_collected_q)['total'] ?? 0;

$total_pending_q = mysqli_query($conn, "
    SELECT SUM(amount_paid) as total FROM payments WHERE status = 'pending'
");
$total_pending = mysqli_fetch_assoc($total_pending_q)['total'] ?? 0;

$monthly_q = mysqli_query($conn, "
    SELECT DATE_FORMAT(payment_date, '%M %Y') as month,
           SUM(amount_paid) as total
    FROM payments
    WHERE status = 'approved'
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY MIN(payment_date) DESC
    LIMIT 12
");

// ─── RENTAL SUMMARY ────────────────────────────────────────
$rental_items_q = mysqli_query($conn, "
    SELECT ri.item_name as name,
           COUNT(rri.item_id) as times_rented,
           SUM(rri.subtotal) as total_earned
    FROM rental_request_items rri
    JOIN rental_items ri ON rri.item_id = ri.item_id
    JOIN rental_requests rr ON rri.rental_id = rr.rental_id
    WHERE rr.status = 'completed'
    GROUP BY rri.item_id
    ORDER BY times_rented DESC
");

$total_rental_q = mysqli_query($conn, "
    SELECT SUM(rri.subtotal) as total
    FROM rental_request_items rri
    JOIN rental_requests rr ON rri.rental_id = rr.rental_id
    WHERE rr.status = 'completed'
");
$total_rental = mysqli_fetch_assoc($total_rental_q)['total'] ?? 0;

// ─── DOCUMENT SUMMARY ──────────────────────────────────────
$doc_q = mysqli_query($conn, "
    SELECT document_type,
           COUNT(*) as total,
           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
           SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) as pending,
           SUM(CASE WHEN status = 'rejected'  THEN 1 ELSE 0 END) as rejected
    FROM document_requests
    GROUP BY document_type
    ORDER BY total DESC
");

// ─── INCIDENT / SUPPORT SUMMARY ────────────────────────────
$incident_q = mysqli_query($conn, "
    SELECT subject as category,
           COUNT(*) as total,
           SUM(CASE WHEN status = 'open'        THEN 1 ELSE 0 END) as open,
           SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
           SUM(CASE WHEN status = 'closed'   THEN 1 ELSE 0 END) as resolved
    FROM support_tickets
    GROUP BY subject
    ORDER BY total DESC
");

// Fetch individual tickets for the ticket list
$tickets_q = mysqli_query($conn, "
    SELECT st.ticket_id, st.subject, st.message, st.status, st.created_at,
           CONCAT(u.first_name, ' ', u.last_name) AS full_name,
           u.email,
           (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = st.ticket_id) as reply_count
    FROM support_tickets st
    LEFT JOIN users u ON st.user_id = u.user_id
    ORDER BY 
        CASE 
            WHEN st.status = 'open' THEN 1
            WHEN st.status = 'in_progress' THEN 2
            WHEN st.status = 'closed' THEN 3
        END,
        st.created_at DESC
");

$total_incidents_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM support_tickets");
$total_incidents = mysqli_fetch_assoc($total_incidents_q)['total'] ?? 0;

$open_incidents_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM support_tickets WHERE status = 'open'");
$open_incidents = mysqli_fetch_assoc($open_incidents_q)['total'] ?? 0;

$closed_count_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM support_tickets WHERE status = 'closed'");
$closed_count = mysqli_fetch_assoc($closed_count_q)['total'] ?? 0;
?>

<style>
.tab-bar {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 24px;
    border-bottom: 2px solid #1e3a3a;
    padding-bottom: 0;
}

.tab-btn {
    padding: 10px 20px;
    border: none;
    background: transparent;
    color: #9ca3af;
    font-weight: bold;
    font-size: 13px;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
    border-radius: 0;
    width: auto;
    margin-top: 0;
}

.tab-btn:hover {
    color: white;
    background: transparent;
}

.tab-btn.active {
    color: #dc1623;
    border-bottom: 3px solid #dc1623;
    background: transparent;
}

.tab-panel {
    display: none;
}

.tab-panel.active {
    display: block;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: #0f1f22;
    border: 1px solid #1e3a3a;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}

.stat-card .stat-value {
    font-size: 28px;
    font-weight: bold;
    color: #dc1623;
    margin-bottom: 6px;
}

.stat-card .stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.report-table thead tr {
    background: #0f1f22;
}

.report-table th {
    padding: 12px 16px;
    text-align: left;
    color: #9ca3af;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 1px solid #1e3a3a;
}

.report-table td {
    padding: 13px 16px;
    border-bottom: 1px solid #1e3a3a;
    vertical-align: middle;
}

.report-table tbody tr:hover {
    background: #0f1f22;
}

.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.badge-green  { background: #064e3b; color: #10b981; }
.badge-yellow { background: #451a03; color: #f59e0b; }
.badge-red    { background: #450a0a; color: #ef4444; }
.badge-blue   { background: #1e3a5f; color: #3b82f6; }

.section-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #9ca3af;
    border-bottom: 1px solid #263b40;
    padding-bottom: 6px;
    margin: 0 0 16px;
}

.export-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
}

.export-card {
    background: #0f1f22;
    border: 1px solid #1e3a3a;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
}

.export-card h4 {
    margin: 0 0 6px;
    font-size: 15px;
}

.export-card p {
    color: #9ca3af;
    font-size: 12px;
    margin: 0 0 16px;
}

.export-card a {
    display: inline-block;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: bold;
    text-decoration: none;
    margin: 3px;
}

.btn-pdf   { background: #dc1623; color: white; }
.btn-excel { background: #166534; color: white; }

/* Action Buttons */
.action-btn {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: bold;
    text-decoration: none;
    border: none;
    cursor: pointer;
    margin-right: 4px;
    transition: all 0.2s;
}

.btn-reply {
    background: #1e3a5f;
    color: #3b82f6;
}

.btn-reply:hover {
    background: #2c4a7f;
}

.btn-reject {
    background: #450a0a;
    color: #ef4444;
}

.btn-reject:hover {
    background: #5a0f0f;
}

.btn-resolve {
    background: #064e3b;
    color: #10b981;
}

.btn-resolve:hover {
    background: #085d44;
}

.btn-clear {
    background: #451a03;
    color: #f59e0b;
    padding: 10px 20px;
    font-size: 13px;
}

.btn-clear:hover {
    background: #5a2104;
}

.reply-badge {
    background: #1e3a5f;
    color: #3b82f6;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    margin-left: 6px;
}

.alert-success {
    background: #064e3b;
    color: #10b981;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 14px;
}
</style>

<section class="page-title">
    <h1>Reports & Analytics</h1>
    <p>Overview of payments, rentals, documents, and incidents</p>
</section>

<?php if (isset($_GET['cleared'])): ?>
<div class="alert-success">
    ✓ Successfully cleared all closed tickets!
</div>
<?php endif; ?>

<div class="panel">

    <div class="tab-bar">
        <button class="tab-btn <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'payments') ? 'active' : ''; ?>" onclick="switchTab('payments', this)">💰 Payment Reports</button>
        <button class="tab-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'rentals') ? 'active' : ''; ?>" onclick="switchTab('rentals', this)">📦 Rental Reports</button>
        <button class="tab-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'documents') ? 'active' : ''; ?>" onclick="switchTab('documents', this)">📄 Document Reports</button>
        <button class="tab-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'incidents') ? 'active' : ''; ?>" onclick="switchTab('incidents', this)">⚠️ Incident Reports</button>
        <button class="tab-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'export') ? 'active' : ''; ?>" onclick="switchTab('export', this)">📤 Export Reports</button>
    </div>

    <div class="tab-panel <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'payments') ? 'active' : ''; ?>" id="tab-payments">
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value">₱<?= number_format($total_collected, 2) ?></div>
                <div class="stat-label">Total Collected</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₱<?= number_format($total_pending, 2) ?></div>
                <div class="stat-label">Pending Dues</div>
            </div>
        </div>

        <div class="section-label">Monthly Income (Last 12 Months)</div>

        <?php if (mysqli_num_rows($monthly_q) == 0): ?>
            <p style="color:#9ca3af;">No payment data yet.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Total Collected</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($monthly_q)): ?>
                <tr>
                    <td><?= $row['month'] ?></td>
                    <td><strong>₱<?= number_format($row['total'], 2) ?></strong></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="tab-panel <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'rentals') ? 'active' : ''; ?>" id="tab-rentals">
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value">₱<?= number_format($total_rental, 2) ?></div>
                <div class="stat-label">Total Rental Earnings</div>
            </div>
        </div>

        <div class="section-label">Most Rented Items</div>

        <?php if (mysqli_num_rows($rental_items_q) == 0): ?>
            <p style="color:#9ca3af;">No rental data yet.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Times Rented</th>
                    <th>Total Earned</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($rental_items_q)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                    <td><?= $row['times_rented'] ?>x</td>
                    <td>₱<?= number_format($row['total_earned'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="tab-panel <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'documents') ? 'active' : ''; ?>" id="tab-documents">
        <div class="section-label">Requests Per Document Type</div>

        <?php if (mysqli_num_rows($doc_q) == 0): ?>
            <p style="color:#9ca3af;">No document requests yet.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Document Type</th>
                    <th>Total Requests</th>
                    <th>Completed</th>
                    <th>Pending</th>
                    <th>Rejected</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($doc_q)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['document_type']) ?></strong></td>
                    <td><?= $row['total'] ?></td>
                    <td><span class="badge badge-green"><?= $row['completed'] ?></span></td>
                    <td><span class="badge badge-yellow"><?= $row['pending'] ?></span></td>
                    <td><span class="badge badge-red"><?= $row['rejected'] ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="tab-panel <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'incidents') ? 'active' : ''; ?>" id="tab-incidents">
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value"><?= $total_incidents ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $open_incidents ?></div>
                <div class="stat-label">Open / Unresolved</div>
            </div>
        </div>

        <div class="section-label">Breakdown by Category</div>

        <?php if (mysqli_num_rows($incident_q) == 0): ?>
            <p style="color:#9ca3af;">No incident reports yet.</p>
        <?php else: ?>
        <div style="overflow-x:auto; margin-bottom:32px;">
        <table class="report-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Total</th>
                    <th>Open</th>
                    <th>In Progress</th>
                    <th>Closed</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($incident_q)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars(ucfirst($row['category'])) ?></strong></td>
                    <td><?= $row['total'] ?></td>
                    <td><span class="badge badge-red"><?= $row['open'] ?></span></td>
                    <td><span class="badge badge-yellow"><?= $row['in_progress'] ?></span></td>
                    <td><span class="badge badge-green"><?= $row['resolved'] ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>

        <div class="section-label" style="display: flex; justify-content: space-between; align-items: center;">
            <span>All Support Tickets</span>
            <?php if ($closed_count > 0): ?>
            <form method="POST" style="margin:0;" onsubmit="return confirm('Clear all <?= $closed_count ?> closed tickets? This action cannot be undone.');">
                <input type="hidden" name="clear_closed_tickets" value="1">
                <button type="submit" class="btn-clear">
                    🗑️ Clear Closed Tickets (<?= $closed_count ?>)
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (mysqli_num_rows($tickets_q) == 0): ?>
            <p style="color:#9ca3af;">No tickets found.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="report-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Homeowner</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($t = mysqli_fetch_assoc($tickets_q)): ?>
                <tr>
                    <td><?= $t['ticket_id'] ?></td>
                    <td><?= htmlspecialchars($t['full_name'] ?? 'N/A') ?></td>
                    <td>
                        <strong><?= htmlspecialchars($t['subject']) ?></strong>
                        <?php if ($t['reply_count'] > 0): ?>
                            <span class="reply-badge"><?= $t['reply_count'] ?> replies</span>
                        <?php endif; ?>
                    </td>
                    <td style="max-width:260px; color:#9ca3af; font-size:13px;"><?= htmlspecialchars(mb_strimwidth($t['message'], 0, 80, '...')) ?></td>
                    <td>
                        <?php if ($t['status'] == 'open'): ?>
                            <span class="badge badge-red">Open</span>
                        <?php elseif ($t['status'] == 'in_progress'): ?>
                            <span class="badge badge-yellow">In Progress</span>
                        <?php elseif ($t['status'] == 'rejected'): ?>
                            <span class="badge badge-red">Rejected</span>
                        <?php else: ?>
                            <span class="badge badge-green">Closed</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px; color:#9ca3af;"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                    <td>
                        <a href="ticket_detail.php?id=<?= $t['ticket_id'] ?>" class="action-btn btn-reply">
                            💬 Reply
                        </a>
                        
                        <?php if ($t['status'] != 'closed' && $t['status'] != 'rejected'): ?>
                        <a href="ticket_detail.php?id=<?= $t['ticket_id'] ?>&action=resolve" class="action-btn btn-resolve">
                            ✓ Resolve
                        </a>
                        <a href="reject_ticket.php?id=<?= $t['ticket_id'] ?>" class="action-btn btn-reject" onclick="return confirm('Reject this ticket?')">
                            ✕ Reject
                        </a>
                        <?php else: ?>
                            <span style="color:#4b5563; font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="tab-panel <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'export') ? 'active' : ''; ?>" id="tab-export">
        <div class="section-label">Export Reports</div>
        <div class="export-grid">
            <div class="export-card">
                <h4>💰 Payment Report</h4>
                <p>Total collections, pending dues, monthly income</p>
                <a href="export_report.php?type=payments&format=pdf"   class="btn-pdf">Export PDF</a>
                <a href="export_report.php?type=payments&format=excel" class="btn-excel">Export Excel</a>
            </div>
            <div class="export-card">
                <h4>📦 Rental Report</h4>
                <p>Most rented items and rental earnings</p>
                <a href="export_report.php?type=rentals&format=pdf"   class="btn-pdf">Export PDF</a>
                <a href="export_report.php?type=rentals&format=excel" class="btn-excel">Export Excel</a>
            </div>
            <div class="export-card">
                <h4>📄 Document Report</h4>
                <p>Total requests per document type</p>
                <a href="export_report.php?type=documents&format=pdf"   class="btn-pdf">Export PDF</a>
                <a href="export_report.php?type=documents&format=excel" class="btn-excel">Export Excel</a>
            </div>
            <div class="export-card">
                <h4>⚠️ Incident Report</h4>
                <p>Complaints and maintenance concerns</p>
                <a href="export_report.php?type=incidents&format=pdf"   class="btn-pdf">Export PDF</a>
                <a href="export_report.php?type=incidents&format=excel" class="btn-excel">Export Excel</a>
            </div>
        </div>
    </div>

</div>

<script>
function switchTab(name, btnElement) {
    const url = new URL(window.location);
    url.searchParams.set('tab', name);
    window.history.pushState({}, '', url);
    
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

    document.getElementById('tab-' + name).classList.add('active');
    if (btnElement) {
        btnElement.classList.add('active');
    }
}

window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        document.querySelectorAll('.tab-btn').forEach(b => {
            if (b.getAttribute('onclick').includes("'" + tab + "'")) {
                b.classList.add('active');
            } else {
                b.classList.remove('active');
            }
        });
    }
});
</script>

<?php include("../includes/admin_footer.php"); ?>