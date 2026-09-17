<?php
// 1. Simulan ang session sa pinakataas
if (session_status() === PHP_SESSION_NONE) {
    session_name('HOA_ADMIN_SESSION');
session_start();
}

// 2. I-include ang Database at Email configuration files
include("../config/db.php");
include_once("../includes/email_notification.php");

// 3. FORCE ADMIN ACCESS FOR TESTING (Bypassed ang role check para hindi ka mag-logout)
$admin_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Kung walang valid ticket ID, ibalik sa reports page
if ($ticket_id === 0) {
    header("Location: reports.php?tab=incidents");
    exit;
}

// 4. PROSESO: Kapag pinindot ang Resolve Action (✓ Resolve & Close)
if (isset($_GET['action']) && $_GET['action'] === 'resolve') {
    mysqli_query($conn, "UPDATE support_tickets SET status='closed' WHERE ticket_id=$ticket_id");
    
    $ticket_info = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT st.*, u.email, CONCAT(u.first_name, ' ', u.last_name) as full_name
        FROM support_tickets st
        LEFT JOIN users u ON st.user_id = u.user_id
        WHERE st.ticket_id = $ticket_id
    "));
    
    if ($ticket_info && !empty($ticket_info['email'])) {
        send_ticket_notification(
            $ticket_info['email'],
            $ticket_info['full_name'],
            $ticket_info['subject'],
            'resolved',
            'Your support ticket has been resolved and closed by the admin.'
        );
    }
    
    header("Location: ticket_detail.php?id=$ticket_id&resolved=1");
    exit;
}

// 5. PROSESO: Kapag nag-submit ng sagot ang admin (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $reply_message = mysqli_real_escape_string($conn, trim($_POST['reply_message']));
    
    if (!empty($reply_message)) {
        // Ipasok ang sagot ng admin sa database
        mysqli_query($conn, "
            INSERT INTO ticket_replies (ticket_id, admin_id, reply_message)
            VALUES ($ticket_id, $admin_id, '$reply_message')
        ");
        
        // Palitan ang status ng ticket bilang 'in_progress'
        mysqli_query($conn, "UPDATE support_tickets SET status='in_progress' WHERE ticket_id=$ticket_id");
        
        $ticket_info = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT st.*, u.email, CONCAT(u.first_name, ' ', u.last_name) as full_name
            FROM support_tickets st
            LEFT JOIN users u ON st.user_id = u.user_id
            WHERE st.ticket_id = $ticket_id
        "));
        
        if ($ticket_info && !empty($ticket_info['email'])) {
            send_ticket_notification(
                $ticket_info['email'],
                $ticket_info['full_name'],
                $ticket_info['subject'],
                'reply',
                $reply_message
            );
        }
        
        header("Location: ticket_detail.php?id=$ticket_id&replied=1");
        exit;
    }
}

// 6. DATA FETCH: Kuhanin ang orihinal na detalye ng ticket at homeowner
$ticket_query = mysqli_query($conn, "
    SELECT st.*, 
           CONCAT(u.first_name, ' ', u.last_name) AS homeowner_name,
           u.email as homeowner_email
    FROM support_tickets st
    LEFT JOIN users u ON st.user_id = u.user_id
    WHERE st.ticket_id = $ticket_id
");

// Kung walang nahanap na ticket, ibalik sa reports page
if (mysqli_num_rows($ticket_query) === 0) {
    header("Location: reports.php?tab=incidents");
    exit;
}

$ticket = mysqli_fetch_assoc($ticket_query);

// 7. DATA FETCH: Inalis ang maling JOIN para hindi mag-duplicate ang mga pangalan ng user account
$replies_query = mysqli_query($conn, "
    SELECT * FROM ticket_replies 
    WHERE ticket_id = $ticket_id
    ORDER BY created_at ASC
");

// 8. I-include ang Structural Layout Header
include("../includes/admin_header.php");
?>

<style>
.ticket-detail-container { max-width: 900px; margin: 0 auto; }
.ticket-card { background: #0f1f22; border: 1px solid #1e3a3a; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
.ticket-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid #1e3a3a; }
.ticket-title { font-size: 22px; font-weight: bold; color: white; margin: 0 0 8px; }
.ticket-meta { display: flex; gap: 16px; font-size: 13px; color: #9ca3af; }
.badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
.badge-green  { background: #064e3b; color: #10b981; }
.badge-yellow { background: #451a03; color: #f59e0b; }
.badge-red    { background: #450a0a; color: #ef4444; }
.homeowner-info { background: #0a1517; padding: 16px; border-radius: 8px; margin-bottom: 20px; }
.homeowner-info h4 { margin: 0 0 12px; font-size: 14px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; }
.info-item { margin-bottom: 8px; font-size: 14px; }
.info-label { color: #9ca3af; margin-right: 8px; }
.ticket-message { background: #0a1517; padding: 16px; border-radius: 8px; line-height: 1.6; color: #d1d5db; }

/* CHATBOX REPLIES STYLING */
.replies-section { margin-top: 24px; }
.replies-section h3 { font-size: 16px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; }

/* Kulay kapag galing kay Admin */
.reply-item.admin-reply { background: #0f2d4a; padding: 16px; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid #3b82f6; }
.reply-item.admin-reply .reply-author { color: #3b82f6; }

/* Kulay kapag galing sa chatbox ng Homeowner/User */
.reply-item.user-reply { background: #14282c; padding: 16px; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid #dc1623; }
.reply-item.user-reply .reply-author { color: #dc1623; }

.reply-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
.reply-author { font-weight: bold; font-size: 13px; }
.reply-date { color: #9ca3af; font-size: 12px; }
.reply-message { color: #d1d5db; line-height: 1.5; font-size: 14px; }

.reply-form { background: #0f1f22; border: 1px solid #1e3a3a; border-radius: 12px; padding: 24px; margin-top: 24px; }
.reply-form h3 { font-size: 16px; margin-bottom: 16px; }
.form-group { margin-bottom: 16px; }
.form-group textarea { width: 100%; padding: 12px; background: #0a1517; border: 1px solid #1e3a3a; border-radius: 8px; color: white; font-size: 14px; font-family: inherit; resize: vertical; min-height: 120px; }
.form-group textarea:focus { outline: none; border-color: #3b82f6; }
.btn-group { display: flex; gap: 12px; }
.btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: bold; border: none; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-success { background: #10b981; color: white; }
.btn-success:hover { background: #059669; }
.btn-danger { background: #ef4444; color: white; }
.btn-danger:hover { background: #dc2626; }
.btn-secondary { background: #374151; color: white; }
.btn-secondary:hover { background: #4b5563; }
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
.alert-success { background: #064e3b; color: #10b981; }
.back-link { display: inline-block; margin-bottom: 20px; color: #9ca3af; text-decoration: none; font-size: 14px; }
.back-link:hover { color: white; }
</style>

<div class="ticket-detail-container">
    <a href="reports.php?tab=incidents" class="back-link">← Back to Incident Reports</a>
    
    <?php if (isset($_GET['replied'])): ?>
    <div class="alert alert-success">
        ✓ Reply sent successfully! User will be notified via email.
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['resolved'])): ?>
    <div class="alert alert-success">
        ✓ Ticket has been marked as resolved!
    </div>
    <?php endif; ?>

    <div class="ticket-card">
        <div class="ticket-header">
            <div>
                <h1 class="ticket-title"><?= htmlspecialchars($ticket['subject']) ?></h1>
                <div class="ticket-meta">
                    <span>Ticket #<?= $ticket['ticket_id'] ?></span>
                    <span>•</span>
                    <span><?= date('F d, Y \a\t g:i A', strtotime($ticket['created_at'])) ?></span>
                </div>
            </div>
            <div>
                <?php if ($ticket['status'] === 'open'): ?>
                    <span class="badge badge-red">Open</span>
                <?php elseif ($ticket['status'] === 'in_progress'): ?>
                    <span class="badge badge-yellow">In Progress</span>
                <?php elseif ($ticket['status'] === 'rejected'): ?>
                    <span class="badge badge-red">Rejected</span>
                <?php else: ?>
                    <span class="badge badge-green">Closed</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="homeowner-info">
            <h4>Homeowner Information</h4>
            <div class="info-item">
                <span class="info-label">Name:</span>
                <strong><?= htmlspecialchars($ticket['homeowner_name'] ?? 'N/A') ?></strong>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <strong><?= htmlspecialchars($ticket['homeowner_email'] ?? 'N/A') ?></strong>
            </div>
        </div>

        <h4 style="color: #9ca3af; font-size: 13px; text-transform: uppercase; margin-bottom: 12px;">Original Message</h4>
        <div class="ticket-message">
            <?= nl2br(htmlspecialchars($ticket['message'])) ?>
        </div>

        <?php if ($ticket['status'] === 'rejected' && isset($ticket['rejection_reason']) && !empty($ticket['rejection_reason'])): ?>
        <div style="margin-top: 16px;">
            <h4 style="color: #ef4444; font-size: 13px; text-transform: uppercase; margin-bottom: 8px;">Rejection Reason</h4>
            <div style="background: #450a0a; padding: 12px; border-radius: 8px; color: #fca5a5;">
                <?= nl2br(htmlspecialchars($ticket['rejection_reason'])) ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($replies_query) > 0): ?>
        <div class="replies-section">
            <h3>Conversation History (<?= mysqli_num_rows($replies_query) ?>)</h3>
            <?php while ($reply = mysqli_fetch_assoc($replies_query)): ?>
                <?php 
                    // FIXED LOGIC PARA MAWALA ANG DUPLICATE USER NAME KOSTUMER
                    $is_admin_reply = ((int)$reply['admin_id'] !== 0);
                    $item_class = $is_admin_reply ? 'admin-reply' : 'user-reply';
                    $display_name = $is_admin_reply ? 'System Admin' : htmlspecialchars($ticket['homeowner_name'] ?? 'Homeowner');
                ?>
                <div class="reply-item <?= $item_class ?>">
                    <div class="reply-header">
                        <span class="reply-author">👤 <?= $display_name ?></span>
                        <span class="reply-date"><?= date('M d, Y \a\t g:i A', strtotime($reply['created_at'])) ?></span>
                    </div>
                    <div class="reply-message">
                        <?= nl2br(htmlspecialchars($reply['reply_message'])) ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($ticket['status'] !== 'closed' && $ticket['status'] !== 'rejected'): ?>
    <div class="reply-form">
        <h3>💬 Send Reply to Homeowner</h3>
        <form method="POST">
            <div class="form-group">
                <textarea name="reply_message" placeholder="Type your reply here..." required></textarea>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Send Reply</button>
                <a href="ticket_detail.php?id=<?= $ticket_id ?>&action=resolve" class="btn btn-success" onclick="return confirm('Mark this ticket as resolved?')">
                    ✓ Resolve & Close
                </a>
                <a href="reject_ticket.php?id=<?= $ticket_id ?>" class="btn btn-danger" onclick="return confirm('Reject this ticket?')">
                    ✕ Reject Ticket
                </a>
                <a href="reports.php?tab=incidents" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 24px; color: #9ca3af;">
        This ticket is resolved or rejected.
        <br><br>
        <a href="reports.php?tab=incidents" class="btn btn-secondary">Back to Incident Reports</a>
    </div>
    <?php endif; ?>
</div>

<?php include("../includes/admin_footer.php"); ?>