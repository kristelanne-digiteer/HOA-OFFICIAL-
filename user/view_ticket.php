<?php
// 1. Simulan ang session
session_name('HOA_USER_SESSION');
session_start();

// 2. I-include ang Database configuration
include("../config/db.php");

// 3. Siguraduhing naka-login ang user
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Kung walang valid ticket ID, ibalik sa support portal
if ($ticket_id === 0) {
    header("Location: support.php?tab=contact");
    exit;
}

// =========================================================
// BYPASS PARA SA FOREIGN KEY CONSTRAINT ERROR
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_user_reply'])) {
    $reply_message = mysqli_real_escape_string($conn, trim($_POST['reply_message']));
    
    if (!empty($reply_message)) {
        // Pansamantalang patayin ang foreign key checks para tanggapin ang 0 kahit NOT NULL
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");
        
        $insert_query = mysqli_query($conn, "
            INSERT INTO ticket_replies (ticket_id, admin_id, reply_message, created_at)
            VALUES ($ticket_id, 0, '$reply_message', NOW())
        ");
        
        // Ibalik muli sa aktibo ang foreign key checks
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");
        
        if ($insert_query) {
            // Ibalik sa 'open' ang status para makita ng Admin na nag-reply si user
            mysqli_query($conn, "UPDATE support_tickets SET status='open' WHERE ticket_id=$ticket_id");
            
            header("Location: view_ticket.php?id=$ticket_id&success=1");
            exit;
        } else {
            echo "Error posting reply: " . mysqli_error($conn);
            exit;
        }
    }
}
// =========================================================

// 4. DATA FETCH: Kuhanin ang detalye ng ticket
$ticket_query = mysqli_query($conn, "
    SELECT st.*, CONCAT(u.first_name, ' ', u.last_name) AS homeowner_name 
    FROM support_tickets st
    LEFT JOIN users u ON st.user_id = u.user_id
    WHERE st.ticket_id = $ticket_id AND st.user_id = $user_id
");

if (mysqli_num_rows($ticket_query) === 0) {
    header("Location: support.php?tab=contact");
    exit;
}

$ticket = mysqli_fetch_assoc($ticket_query);

// 5. DATA FETCH: Kuhanin ang lahat ng sagot (Tinanggal ang tr. sa ORDER BY para iwas error!)
$replies_query = mysqli_query($conn, "
    SELECT * FROM ticket_replies 
    WHERE ticket_id = $ticket_id
    ORDER BY created_at ASC
");

// 6. I-include ang layout header ng user
include("../includes/user_header.php");
?>

<style>
.view-ticket-container { max-width: 800px; margin: 0 auto; padding: 20px; }
.back-link { display: inline-block; margin-bottom: 20px; color: #9ca3af; text-decoration: none; font-size: 14px; transition: color 0.2s; }
.back-link:hover { color: white; }
.ticket-main-card { background: #0f1f22; border: 1px solid #1e3a3a; border-radius: 12px; padding: 24px; margin-bottom: 24px; }
.ticket-main-header { display: flex; justify-content: space-between; align-items: start; border-bottom: 1px solid #1e3a3a; padding-bottom: 16px; margin-bottom: 20px; }
.ticket-main-title { font-size: 22px; font-weight: bold; color: white; margin: 0 0 6px; }
.ticket-main-date { font-size: 13px; color: #6b7280; }
.badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
.badge-green  { background: #064e3b; color: #10b981; }
.badge-yellow { background: #451a03; color: #f59e0b; }
.badge-red    { background: #450a0a; color: #ef4444; }
.original-msg-box { background: #0a1517; padding: 16px; border-radius: 8px; color: #d1d5db; line-height: 1.6; font-size: 14px; }
.rejection-box { background: #450a0a; border: 1px solid #ef4444; padding: 16px; border-radius: 8px; color: #fca5a5; margin-top: 20px; }
.rejection-box h4 { margin: 0 0 8px; color: #ef4444; font-size: 14px; text-transform: uppercase; }

/* CHATBOX STYLE */
.replies-section { margin-top: 32px; }
.replies-section h3 { font-size: 16px; color: #9ca3af; text-transform: uppercase; margin-bottom: 16px; }

/* Admin card styling */
.reply-card.admin-reply {
    background: #0f1f22;
    border: 1px solid #1e3a3a;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
    border-left: 4px solid #3b82f6;
}
.reply-card.admin-reply .reply-sender { color: #3b82f6; font-weight: bold; }

/* Homeowner card styling */
.reply-card.user-reply {
    background: #14282c;
    border: 1px solid #224444;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
    border-left: 4px solid #dc1623;
}
.reply-card.user-reply .reply-sender { color: #dc1623; font-weight: bold; }

.reply-card-header { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; }
.reply-time { color: #6b7280; }
.reply-text { color: #e5e7eb; line-height: 1.5; font-size: 14px; }

/* USER REPLY FORM */
.user-reply-form { background: #0f1f22; border: 1px solid #1e3a3a; border-radius: 12px; padding: 24px; margin-top: 32px; }
.user-reply-form h3 { font-size: 16px; margin-bottom: 12px; color: white; }
.form-group textarea { width: 100%; padding: 12px; background: #0a1517; border: 1px solid #1e3a3a; border-radius: 8px; color: white; font-size: 14px; font-family: inherit; resize: vertical; min-height: 100px; }
.form-group textarea:focus { outline: none; border-color: #dc1623; }
.btn-submit-reply { background: #dc1623; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; transition: background 0.2s; }
.btn-submit-reply:hover { background: #b91319; }
.alert-success { background: #064e3b; color: #10b981; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
</style>

<div class="view-ticket-container">
    <a href="support.php?tab=contact" class="back-link">← Back to Support Tickets</a>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success">✓ Your reply has been posted successfully!</div>
    <?php endif; ?>

    <div class="ticket-main-card">
        <div class="ticket-main-header">
            <div>
                <h1 class="ticket-main-title"><?= htmlspecialchars($ticket['subject']) ?></h1>
                <div class="ticket-main-date">Submitted on <?= date('F d, Y \a\t g:i A', strtotime($ticket['created_at'])) ?></div>
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

        <h3 style="font-size: 13px; color: #9ca3af; text-transform: uppercase; margin-bottom: 10px;">Your Original Message</h3>
        <div class="original-msg-box">
            <?= nl2br(htmlspecialchars($ticket['message'])) ?>
        </div>

        <?php if ($ticket['status'] === 'rejected' && !empty($ticket['rejection_reason'])): ?>
        <div class="rejection-box">
            <h4>✕ Notice of Rejection</h4>
            <p><?= nl2br(htmlspecialchars($ticket['rejection_reason'])) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="replies-section">
        <h3>💬 Message Thread</h3>
        
        <?php if (mysqli_num_rows($replies_query) === 0): ?>
            <div style="background: #0f1f22; text-align: center; padding: 30px; border-radius: 8px; color: #6b7280; font-size: 14px; border: 1px dashed #1e3a3a;">
                No conversation history yet. Admin will reply soon.
            </div>
        <?php else: ?>
            <?php while ($reply = mysqli_fetch_assoc($replies_query)): ?>
                <?php 
                    // FIXED LOGIC: Kapag ang admin_id ay hindi 0, matic na Admin ito.
                    $is_admin = ((int)$reply['admin_id'] !== 0);
                    $card_class = $is_admin ? 'admin-reply' : 'user-reply';
                    
                    // Malinis na display name para iwas lito kay user at admin
                    $sender_label = $is_admin ? '👤 System Admin' : '👤 You (Homeowner)';
                ?>
                <div class="reply-card <?= $card_class ?>">
                    <div class="reply-card-header">
                        <span class="reply-sender"><?= $sender_label ?></span>
                        <span class="reply-time"><?= date('M d, Y \a\t g:i A', strtotime($reply['created_at'])) ?></span>
                    </div>
                    <div class="reply-text">
                        <?= nl2br(htmlspecialchars($reply['reply_message'])) ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <?php if ($ticket['status'] !== 'closed' && $ticket['status'] !== 'rejected'): ?>
    <div class="user-reply-form">
        <h3>💬 Reply to this message thread</h3>
        <form method="POST">
            <div class="form-group" style="margin-bottom: 12px;">
                <textarea name="reply_message" placeholder="Type your follow-up question or message here..." required></textarea>
            </div>
            <button type="submit" name="submit_user_reply" class="btn-submit-reply">Send Reply</button>
        </form>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 20px; color: #6b7280; font-size: 14px;">
        🔒 This ticket thread has been closed or archived. You cannot reply anymore.
    </div>
    <?php endif; ?>
</div>

<?php include("../includes/user_footer.php"); ?>