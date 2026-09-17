<?php
include("../config/db.php");
include("../includes/user_header.php");


$user_id = $_SESSION['user_id'];

$profile = mysqli_query($conn, "
    SELECT hp.masterlist_id
    FROM homeowner_profiles hp
    WHERE hp.user_id='$user_id'
");

$profile_row = mysqli_fetch_assoc($profile);
$masterlist_id = $profile_row['masterlist_id'] ?? 0;

$unpaid_query = mysqli_query($conn, "
    SELECT SUM(total_balance) AS total_unpaid
    FROM soa_records
    WHERE homeowner_id='$masterlist_id'
    AND status='unpaid'
");

$unpaid_row = mysqli_fetch_assoc($unpaid_query);
$total_unpaid = $unpaid_row['total_unpaid'] ?? 0;

$pending_docs = mysqli_num_rows(mysqli_query($conn, "
    SELECT * FROM document_requests
    WHERE user_id='$user_id'
    AND status='pending'
"));

$rental_status = mysqli_query($conn, "
    SELECT status
    FROM rental_requests
    WHERE user_id='$user_id'
    ORDER BY rental_id DESC
    LIMIT 1
");

$rental_row = mysqli_fetch_assoc($rental_status);
$latest_rental_status = $rental_row['status'] ?? 'No Request';

$events_count = mysqli_num_rows(mysqli_query($conn, "
    SELECT * FROM events
    WHERE event_date >= CURDATE()
"));

// ===== Notifications feed =====
// Kinukuha ang lahat ng notifications mula sa helper, tapos
// itinatago ang mga "Clear" na sa session (key-based, hindi oras-based,
// kaya hindi maaapektuhan ng pagkakaiba ng oras ng PHP at MySQL server).
include("../includes/notifications_helper.php");

$notifications  = getUserNotifications($conn, $user_id, $masterlist_id);
$cleared_keys   = $_SESSION['cleared_notification_keys'] ?? [];

$notifications = array_filter($notifications, function ($n) use ($cleared_keys) {
    return !in_array($n['key'], $cleared_keys);
});

// I-sort lahat by time, pinakabago sa una
usort($notifications, function ($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
$notifications = array_slice($notifications, 0, 6);
?>

<section class="page-title">
    <h1>Dashboard</h1>
    <p>Welcome to your homeowner portal</p>
</section>
<div class="panel" style="display:flex; align-items:center; gap:18px;">
    <img src="<?php echo $user_photo; ?>" class="profile-photo" alt="Profile">

    <div>
        <h2 style="margin-bottom:6px;">
            Welcome, <?php echo htmlspecialchars($user_name); ?>!
        </h2>

        <p style="color:#cbd5d6;">
            Account Type:
            <span class="badge green">Homeowner</span>
        </p>
    </div>
</div>
<section class="cards">
    <a href="payments.php" class="card dashboard-card">
        <h3>Current Unpaid Dues</h3>
        <div class="value">₱<?php echo number_format($total_unpaid, 2); ?></div>
        <div class="note">View and pay bills</div>
    </a>

    <a href="documents.php" class="card dashboard-card">
        <h3>Pending Documents</h3>
        <div class="value"><?php echo $pending_docs; ?></div>
        <div class="note">Track requests</div>
    </a>

    <a href="rentals.php" class="card dashboard-card">
        <h3>Rental Status</h3>
        <div class="value" style="font-size:24px;"><?php echo htmlspecialchars($latest_rental_status); ?></div>
        <div class="note">Latest rental request</div>
    </a>

    <a href="events.php" class="card dashboard-card">
        <h3>Upcoming Events</h3>
        <div class="value"><?php echo $events_count; ?></div>
        <div class="note">Community schedules</div>
    </a>
</section>

<section class="content-grid">
    <div class="panel">
        <h2>Quick Actions</h2>

        <a href="payments.php" class="btn quick-btn">Pay Dues</a>
        <a href="documents.php" class="btn quick-btn">Request Documents</a>
        <a href="rentals.php" class="btn quick-btn">Submit Rental Request</a>
        <a href="events.php" class="btn quick-btn">View Announcements</a>
        <a href="support.php" class="btn quick-btn">Contact HOA</a>
    </div>

    <div class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2 style="margin:0;">Notifications</h2>
            <?php if (!empty($notifications)): ?>
                <a href="clear_notifications.php" class="btn quick-btn" style="padding:4px 12px; font-size:12px;">Clear</a>
            <?php endif; ?>
        </div>
        <?php if (empty($notifications)): ?>
            <p style="color:#cbd5d6; line-height:1.8;">
                Payment reminders, rental updates, document updates, and HOA announcements will appear here.
            </p>
        <?php else: ?>
            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:12px;">
                <?php foreach ($notifications as $note): ?>
                    <li style="display:flex; gap:10px; align-items:flex-start; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:10px;">
                        <span style="font-size:18px;"><?php echo $note['icon']; ?></span>
                        <div>
                            <div style="color:#e8eef0;"><?php echo htmlspecialchars($note['message']); ?></div>
                            <div style="color:#8a9a9c; font-size:12px; margin-top:2px;">
                                <?php echo date('M d, Y h:i A', strtotime($note['time'])); ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<?php include("../includes/user_footer.php"); ?>