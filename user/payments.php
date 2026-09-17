<?php
include("../config/db.php");
include("../includes/user_header.php");

$user_id = $_SESSION['user_id'];

// Fetch settings from DB (QR, account names, enabled channels)
$settings_q = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_id DESC LIMIT 1");
$settings    = mysqli_fetch_assoc($settings_q);

$profile_q = mysqli_query($conn, "
    SELECT masterlist_id 
    FROM homeowner_profiles 
    WHERE user_id='$user_id'
");
$profile       = mysqli_fetch_assoc($profile_q);
$masterlist_id = $profile['masterlist_id'] ?? 0;
$no_profile_linked = ($masterlist_id == 0);

// ── UPLOAD PAYMENT ──────────────────────────────────────────────────────────
if (isset($_POST['upload_payment'])) {
    $soa_id         = (int)$_POST['soa_id'];
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $amount_paid    = mysqli_real_escape_string($conn, $_POST['amount_paid']);
    $proof_file     = NULL;

    if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] == 0) {
        $upload_dir = "../assets/uploads/payments/";
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $proof_file = time() . "_" . basename($_FILES['proof_of_payment']['name']);
        move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $upload_dir . $proof_file);
    }

    $soa_q   = mysqli_query($conn, "SELECT * FROM soa_records WHERE soa_id='$soa_id'");
    $soa     = mysqli_fetch_assoc($soa_q);
    $purpose = !empty($soa['payment_purpose']) ? mysqli_real_escape_string($conn, $soa['payment_purpose']) : 'Monthly Dues';

    mysqli_query($conn, "
        INSERT INTO payments
        (bill_id, soa_id, user_id, amount_paid, payment_method, proof_of_payment, receipt_number, status, payment_date, payment_purpose, hidden_by_user)
        VALUES
        (NULL, '$soa_id', '$user_id', '$amount_paid', '$payment_method', '$proof_file', NULL, 'pending', NOW(), '$purpose', 0)
    ");

    logActivity($conn, $user_id, "Uploaded payment proof for SOA #$soa_id", "Payment");
    echo "<script>alert('Payment proof uploaded. Please wait for admin approval.'); window.location='payments.php';</script>";
    exit;
}

// ── FETCH DATA ───────────────────────────────────────────────────────────────
$current_bills = mysqli_query($conn, "
    SELECT * FROM soa_records
    WHERE homeowner_id='$masterlist_id' AND status='unpaid'
    ORDER BY due_date ASC
");
$bills_for_select = mysqli_query($conn, "
    SELECT * FROM soa_records
    WHERE homeowner_id='$masterlist_id' AND status='unpaid'
    ORDER BY due_date ASC
");
$payment_history = mysqli_query($conn, "
    SELECT * FROM payments
    WHERE user_id='$user_id' AND (hidden_by_user IS NULL OR hidden_by_user = 0)
    ORDER BY payment_id DESC
");

// Helper: mask account name for display e.g. "Rafael Apostol" → "R***** A."
function maskName($name) {
    if (empty($name)) return '—';
    $parts = explode(' ', trim($name));
    $masked = [];
    foreach ($parts as $i => $part) {
        if (strlen($part) <= 1) { $masked[] = $part; continue; }
        if ($i === count($parts) - 1) {
            $masked[] = strtoupper(substr($part, 0, 1)) . '.';
        } else {
            $masked[] = strtoupper(substr($part, 0, 1)) . str_repeat('*', min(strlen($part)-1, 4));
        }
    }
    return implode(' ', $masked);
}
?>

<section class="page-title">
    <h1>Payment Management</h1>
    <p>View bills, upload proof of payment, and download receipts</p>
</section>

<div class="tabs">
    <button class="tab-btn active" onclick="openTab(event, 'bills')">Current Bills</button>
    <button class="tab-btn" onclick="openTab(event, 'methods')">Payment Methods</button>
    <button class="tab-btn" onclick="openTab(event, 'history')">Payment History</button>
</div>

<!-- ══ TAB 1: CURRENT BILLS ══ -->
<div id="bills" class="tab-content active-tab">
    <div class="panel">
        <div class="panel-header">
            <h2>Current Bills</h2>
            <a href="clear_paid_bills.php" class="btn btn-delete"
               onclick="return confirm('Clear paid bills from current bills?')">Clear Paid</a>
        </div>

        <table class="table">
            <tr>
                <th>SOA ID</th><th>Purpose</th><th>Billing Month</th>
                <th>Penalty</th><th>Total Balance</th><th>Due Date</th><th>Status</th>
            </tr>
            <?php if ($no_profile_linked): ?>
                <tr>
                    <td colspan="7" style="color:#f59e0b; text-align:center; padding:16px;">
                        ⚠️ Your account has not been linked to a homeowner profile yet. Please contact the HOA admin.
                    </td>
                </tr>
            <?php elseif (mysqli_num_rows($current_bills) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($current_bills)): ?>
                    <tr>
                        <td><?php echo $row['soa_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['payment_purpose']); ?></td>
                        <td><?php echo htmlspecialchars($row['billing_month']); ?></td>
                        <td>₱<?php echo number_format($row['penalties'], 2); ?></td>
                        <td>₱<?php echo number_format($row['total_balance'], 2); ?></td>
                        <td><?php echo $row['due_date']; ?></td>
                        <td><span class="badge yellow"><?php echo htmlspecialchars($row['status']); ?></span></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center; color:#9ca3af; padding:20px;">No unpaid bills found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- ══ TAB 2: PAYMENT METHODS ══ -->
<div id="methods" class="tab-content">
    <div class="panel">
        <h2>Payment Methods</h2>

        <?php
        $gcash_on = !empty($settings['gcash_enabled']);
        $maya_on  = !empty($settings['bank_transfer_enabled']);
        $cash_on  = !empty($settings['paypal_enabled']);

        $active_methods = array_filter([$gcash_on ? 'GCash' : null, $maya_on ? 'Maya' : null, $cash_on ? 'Cash Payment at HOA Office' : null]);
        $methods_str    = !empty($active_methods) ? implode(', ', $active_methods) : 'Please contact the HOA admin for payment details.';
        ?>

        <p style="color:#cbd5d6; margin-bottom:20px;">
            Available payment options: <?php echo $methods_str; ?>
        </p>

        <?php if ($gcash_on || $maya_on): ?>
        <div style="display:flex; gap:40px; flex-wrap:wrap; margin-bottom:28px; align-items:flex-start;">

            <?php if ($gcash_on): ?>
            <div style="text-align:center;">
                <?php if (!empty($settings['gcash_qr'])): ?>
                    <img src="<?php echo htmlspecialchars('../' . ltrim($settings['gcash_qr'], '.')); ?>"
                         style="width:180px; background:#fff; padding:10px; border-radius:12px; border:2px solid #263b40;"
                         alt="GCash QR Code">
                <?php else: ?>
                    <div style="width:180px; height:180px; background:#162a2d; border-radius:12px; border:2px dashed #263b40; display:flex; align-items:center; justify-content:center; color:#4b6a70; font-size:13px;">No QR Uploaded</div>
                <?php endif; ?>
                <div style="margin-top:12px; color:#fff; line-height:1.6;">
                    <strong style="font-size:16px; color:#22c55e;">GCash</strong><br>
                    <?php if (!empty($settings['gcash_account_name'])): ?>
                        <span style="color:#e2e8f0;"><?php echo htmlspecialchars(maskName($settings['gcash_account_name'])); ?></span><br>
                        <?php if (!empty($settings['gcash_account_number'])): ?>
                            <span style="color:#9ca3af; font-size:13px;"><?php echo htmlspecialchars($settings['gcash_account_number']); ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:#9ca3af;">Contact HOA Admin</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($maya_on): ?>
            <div style="text-align:center;">
                <?php if (!empty($settings['maya_qr'])): ?>
                    <img src="<?php echo htmlspecialchars('../' . ltrim($settings['maya_qr'], '.')); ?>"
                         style="width:180px; background:#fff; padding:10px; border-radius:12px; border:2px solid #263b40;"
                         alt="Maya QR Code">
                <?php else: ?>
                    <div style="width:180px; height:180px; background:#162a2d; border-radius:12px; border:2px dashed #263b40; display:flex; align-items:center; justify-content:center; color:#4b6a70; font-size:13px;">No QR Uploaded</div>
                <?php endif; ?>
                <div style="margin-top:12px; color:#fff; line-height:1.6;">
                    <strong style="font-size:16px; color:#1d6ee3;">Maya</strong><br>
                    <?php if (!empty($settings['maya_account_name'])): ?>
                        <span style="color:#e2e8f0;"><?php echo htmlspecialchars($settings['maya_account_name']); ?></span><br>
                        <?php if (!empty($settings['maya_account_number'])): ?>
                            <span style="color:#9ca3af; font-size:13px;"><?php echo htmlspecialchars($settings['maya_account_number']); ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:#9ca3af;">Contact HOA Admin</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <?php endif; ?>

        <?php if ($cash_on): ?>
        <div style="background:#162a2d; border:1px solid #263b40; border-radius:8px; padding:14px 18px; margin-bottom:24px; display:flex; align-items:center; gap:12px;">
            <span style="font-size:22px;">🏦</span>
            <div>
                <strong style="color:#e2e8f0;">Cash Payment at HOA Office</strong><br>
                <span style="color:#9ca3af; font-size:13px;">
                    <?php echo htmlspecialchars($settings['office_hours'] ?? 'Visit HOA office during business hours'); ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <p style="color:#22c55e; font-size:14px; margin-bottom:22px;">
            📎 After payment, upload your proof of payment below for verification.
        </p>

        <!-- Upload Form -->
        <form method="POST" enctype="multipart/form-data" style="max-width:520px;">
            <div style="margin-bottom:14px;">
                <label style="display:block; margin-bottom:6px; color:#94a3b8; font-size:13px; text-transform:uppercase; letter-spacing:.5px;">Select Bill / SOA</label>
                <select name="soa_id" class="form-input" required>
                    <option value="">— Select unpaid bill —</option>
                    <?php while ($bill = mysqli_fetch_assoc($bills_for_select)): ?>
                        <option value="<?php echo $bill['soa_id']; ?>">
                            SOA #<?php echo $bill['soa_id']; ?> —
                            <?php echo htmlspecialchars($bill['payment_purpose']); ?> —
                            ₱<?php echo number_format($bill['total_balance'], 2); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block; margin-bottom:6px; color:#94a3b8; font-size:13px; text-transform:uppercase; letter-spacing:.5px;">Amount Paid (₱)</label>
                <input type="number" step="0.01" name="amount_paid" class="form-input" placeholder="0.00" required>
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block; margin-bottom:6px; color:#94a3b8; font-size:13px; text-transform:uppercase; letter-spacing:.5px;">Payment Method</label>
                <select name="payment_method" class="form-input" required>
                    <?php if ($gcash_on): ?><option value="GCash">GCash</option><?php endif; ?>
                    <?php if ($maya_on):  ?><option value="Maya">Maya</option><?php endif; ?>
                    <?php if ($cash_on):  ?><option value="Cash">Cash Payment at HOA Office</option><?php endif; ?>
                    <?php if (!$gcash_on && !$maya_on && !$cash_on): ?>
                        <option value="">No payment methods enabled</option>
                    <?php endif; ?>
                </select>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:6px; color:#94a3b8; font-size:13px; text-transform:uppercase; letter-spacing:.5px;">Upload Proof of Payment</label>
                <input type="file" name="proof_of_payment" class="form-input" accept="image/*,.pdf" required>
                <small style="color:#64748b;">Accepted: JPG, PNG, PDF</small>
            </div>

            <button type="submit" name="upload_payment" class="btn">
                📤 Submit Payment Proof
            </button>
        </form>
    </div>
</div>

<!-- ══ TAB 3: PAYMENT HISTORY ══ -->
<div id="history" class="tab-content">
    <div class="panel">
        <div class="panel-header">
            <h2>Payment History</h2>
            <a href="clear_paid_history.php" class="btn btn-delete"
               onclick="return confirm('Clear approved payments from history view?')">Clear Paid</a>
        </div>

        <table class="table">
            <tr>
                <th>Payment ID</th><th>Purpose</th><th>Amount</th>
                <th>Method</th><th>Date</th><th>Receipt No.</th><th>Status</th><th>Action</th>
            </tr>
            <?php if (mysqli_num_rows($payment_history) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($payment_history)): ?>
                    <tr>
                        <td><?php echo $row['payment_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['payment_purpose']); ?></td>
                        <td>₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                        <td><?php echo $row['payment_date']; ?></td>
                        <td><?php echo htmlspecialchars($row['receipt_number'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge <?php echo $row['status']=='approved' ? 'green' : ($row['status']=='rejected' ? 'red' : 'yellow'); ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'approved'): ?>
                                <a href="../admin/receipt.php?id=<?php echo $row['payment_id']; ?>"
                                   class="btn btn-view" target="_blank">Download Receipt</a>
                            <?php else: ?>
                                <span style="color:#9ca3af; font-size:13px;">Waiting Approval</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center; color:#9ca3af; padding:20px;">No payment history yet.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
function openTab(event, tabName) {
    document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active-tab"));
    document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
    document.getElementById(tabName).classList.add("active-tab");
    event.currentTarget.classList.add("active");
}
</script>

<?php include("../includes/user_footer.php"); ?>