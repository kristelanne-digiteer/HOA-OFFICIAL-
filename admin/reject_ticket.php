<?php
// 1. Simulan ang session sa pinakataas
if (session_status() === PHP_SESSION_NONE) {
    session_name('HOA_ADMIN_SESSION');
session_start();
}

// 2. I-include ang Database at Email configuration files
include("../config/db.php");
include_once("../includes/email_notification.php");

// 3. Validate admin session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
$admin_id = $_SESSION['user_id'];
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Kung walang valid ticket ID, ibalik sa reports page
if ($ticket_id === 0) {
    header("Location: reports.php?tab=incidents");
    exit;
}

// Kuhanin ang detalye ng ticket para sa page title o validation
$ticket_query = mysqli_query($conn, "SELECT * FROM support_tickets WHERE ticket_id = $ticket_id");
if (mysqli_num_rows($ticket_query) === 0) {
    header("Location: reports.php?tab=incidents");
    exit;
}
$ticket = mysqli_fetch_assoc($ticket_query);

// 4. PROSESO: Kapag nag-submit ng dahilan ng pag-reject (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rejection_reason'])) {
    $rejection_reason = mysqli_real_escape_string($conn, trim($_POST['rejection_reason']));

    if (!empty($rejection_reason)) {
        // I-update ang status ng ticket bilang 'rejected' at i-save ang dahilan
        mysqli_query($conn, "
            UPDATE support_tickets 
            SET status = 'rejected', rejection_reason = '$rejection_reason' 
            WHERE ticket_id = $ticket_id
        ");

        // Kuhanin ang email ng homeowner para padalhan ng abiso
        $user_query = mysqli_query($conn, "
            SELECT u.email, CONCAT(u.first_name, ' ', u.last_name) as full_name 
            FROM users u 
            WHERE u.user_id = {$ticket['user_id']}
        ");
        $user_info = mysqli_fetch_assoc($user_query);

        if ($user_info && !empty($user_info['email'])) {
            send_ticket_notification(
                $user_info['email'],
                $user_info['full_name'],
                $ticket['subject'],
                'rejected',
                $rejection_reason
            );
        }

        // Pagkatapos ma-reject, ibalik sa ticket_detail page para makita ang update
        header("Location: ticket_detail.php?id=$ticket_id");
        exit;
    }
}

// 5. I-include ang Structural Layout Header
include("../includes/admin_header.php");
?>

<style>
.reject-container {
    max-width: 600px;
    margin: 40px auto;
}

.reject-card {
    background: #0f1f22;
    border: 1px solid #1e3a3a;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.reject-title {
    font-size: 20px;
    font-weight: bold;
    color: #ef4444;
    margin: 0 0 10px;
}

.reject-subtitle {
    color: #9ca3af;
    font-size: 14px;
    margin-bottom: 24px;
}

.ticket-summary {
    background: #0a1517;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    border-left: 3px solid #ef4444;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: bold;
    color: #d1d5db;
}

.form-group textarea {
    width: 100%;
    padding: 12px;
    background: #0a1517;
    border: 1px solid #1e3a3a;
    border-radius: 8px;
    color: white;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    min-height: 150px;
}

.form-group textarea:focus {
    outline: none;
    border-color: #ef4444;
}

.btn-group {
    display: flex;
    gap: 12px;
}

.btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s;
    text-align: center;
}

.btn-danger { background: #ef4444; color: white; }
.btn-danger:hover { background: #dc2626; }

.btn-secondary { background: #374151; color: white; }
.btn-secondary:hover { background: #4b5563; }
</style>

<div class="reject-container">
    <div class="reject-card">
        <h1 class="reject-title">✕ Reject Support Ticket</h1>
        <p class="reject-subtitle">Sigurado ka bang nais mong ireject ang ticket na ito? Magbigay ng malinaw na dahilan para sa homeowner.</p>

        <div class="ticket-summary">
            <strong>Ticket Subject:</strong> <?= htmlspecialchars($ticket['subject']) ?><br>
            <strong>Ticket ID:</strong> #<?= $ticket['ticket_id'] ?>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Reason for Rejection</label>
                <textarea name="rejection_reason" placeholder="Isulat dito kung bakit nirereject ang ticket na ito (hal. Kulang ang impormasyon, maling kategorya, etc.)..." required></textarea>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Confirm rejecting this ticket?')">
                    Confirm Reject
                </button>
                <a href="ticket_detail.php?id=<?= $ticket_id ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include("../includes/admin_footer.php"); ?>