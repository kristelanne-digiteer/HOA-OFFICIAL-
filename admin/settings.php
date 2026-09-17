<?php
include("../config/db.php");
include("../includes/admin_header.php");

$user_id = $_SESSION['user_id'] ?? 0;

// ── 1. HANDLE SETTINGS SAVING ──────────────────────────────────────────────
if (isset($_POST['save_settings'])) {
    $hoa_name = mysqli_real_escape_string($conn, $_POST['hoa_name'] ?? '');
    $contact  = mysqli_real_escape_string($conn, $_POST['contact_details'] ?? '');
    $hours    = mysqli_real_escape_string($conn, $_POST['office_hours'] ?? '');

    // Payment text fields
    $gcash_name = mysqli_real_escape_string($conn, $_POST['gcash_account_name'] ?? '');
    $gcash_num  = mysqli_real_escape_string($conn, $_POST['gcash_account_number'] ?? '');
    $maya_name  = mysqli_real_escape_string($conn, $_POST['maya_account_name'] ?? '');
    $maya_num   = mysqli_real_escape_string($conn, $_POST['maya_account_number'] ?? '');

    // Checkboxes
    $gcash  = isset($_POST['gcash_enabled']) ? 1 : 0;
    $paypal = isset($_POST['paypal_enabled']) ? 1 : 0;
    $bank   = isset($_POST['bank_transfer_enabled']) ? 1 : 0;

    // Fetch existing paths to retain if no new file uploaded
    $res  = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_id DESC LIMIT 1");
    $curr = mysqli_fetch_assoc($res);

    $logo_path     = $curr['company_logo'] ?? '';
    $gcash_qr_path = $curr['gcash_qr'] ?? '';
    $maya_qr_path  = $curr['maya_qr'] ?? '';

    $upload_dir = "../assets/uploads/system/";
    if (!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }

    // Upload Company Logo
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] == 0) {
        $ext = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
        $logo_path = $upload_dir . "logo_" . time() . "." . $ext;
        move_uploaded_file($_FILES['company_logo']['tmp_name'], $logo_path);
    }
    // Upload GCash QR
    if (isset($_FILES['gcash_qr']) && $_FILES['gcash_qr']['error'] == 0) {
        $ext = pathinfo($_FILES['gcash_qr']['name'], PATHINFO_EXTENSION);
        $gcash_qr_path = $upload_dir . "gcash_qr_" . time() . "." . $ext;
        move_uploaded_file($_FILES['gcash_qr']['tmp_name'], $gcash_qr_path);
    }
    // Upload Maya QR
    if (isset($_FILES['maya_qr']) && $_FILES['maya_qr']['error'] == 0) {
        $ext = pathinfo($_FILES['maya_qr']['name'], PATHINFO_EXTENSION);
        $maya_qr_path = $upload_dir . "maya_qr_" . time() . "." . $ext;
        move_uploaded_file($_FILES['maya_qr']['tmp_name'], $maya_qr_path);
    }

    mysqli_query($conn, "UPDATE settings SET
        hoa_name            = '$hoa_name',
        contact_details     = '$contact',
        office_hours        = '$hours',
        gcash_enabled       = $gcash,
        paypal_enabled      = $paypal,
        bank_transfer_enabled = $bank,
        company_logo        = '$logo_path',
        gcash_account_name  = '$gcash_name',
        gcash_account_number = '$gcash_num',
        gcash_qr            = '$gcash_qr_path',
        maya_account_name   = '$maya_name',
        maya_account_number = '$maya_num',
        maya_qr             = '$maya_qr_path'
        WHERE setting_id = (SELECT setting_id FROM (SELECT setting_id FROM settings ORDER BY setting_id DESC LIMIT 1) AS t)");

    logActivity($conn, $user_id, "Updated System, Branding & Payment Settings", "System Update");
    echo "<script>alert('Settings saved successfully!'); window.location='settings.php';</script>";
    exit;
}

// ── 2. HANDLE CUSTOM DATABASE QUERY ─────────────────────────────────────────
$query_error        = '';
$query_success      = '';
$query_result_data  = null;
$query_columns      = [];

if (isset($_POST['execute_sql']) && !empty($_POST['sql_query'])) {
    $sql = trim($_POST['sql_query']);
    if (mysqli_multi_query($conn, $sql)) {
        $query_success     = "Query executed successfully.";
        $query_result_data = mysqli_store_result($conn);
        if ($query_result_data && mysqli_num_fields($query_result_data) > 0) {
            $finfo = mysqli_fetch_fields($query_result_data);
            foreach ($finfo as $f) { $query_columns[] = $f->name; }
        }
    } else {
        $query_error = mysqli_error($conn);
    }
    logActivity($conn, $user_id, "Executed Custom DB Query via Settings Panel", "Database");
}

// ── 3. FETCH DATA ─────────────────────────────────────────────────────────────
$result   = mysqli_query($conn, "SELECT * FROM settings ORDER BY setting_id DESC LIMIT 1");
$settings = mysqli_fetch_assoc($result);
$tables_q = mysqli_query($conn, "SHOW TABLE STATUS");
?>

<style>
/* ── Layout ── */
.settings-container { display: flex; gap: 20px; margin-top: 20px; font-family: sans-serif; color: #fff; }
.settings-sidebar   { width: 240px; background: #0f1f22; border: 1px solid #263b40; border-radius: 10px; padding: 12px; height: fit-content; position: sticky; top: 20px; }
.settings-main      { flex: 1; background: #0f1f22; border: 1px solid #263b40; border-radius: 10px; padding: 28px; }

/* ── Tabs ── */
.tab-btn { display: flex; align-items: center; gap: 10px; width: 100%; text-align: left; padding: 12px 15px; cursor: pointer; background: transparent; border: none; color: #9ca3af; border-radius: 8px; font-size: 14px; margin-bottom: 4px; transition: 0.2s; }
.tab-btn:hover  { background: #162a2d; color: #fff; }
.tab-btn.active { background: #dc1623; color: white; font-weight: bold; }
.tab-content    { display: none; }
.tab-content.active { display: block; }
.tab-section-title { font-size: 17px; font-weight: bold; border-bottom: 1px solid #263b40; padding-bottom: 10px; margin: 0 0 22px; color: #e2e8f0; }

/* ── Form ── */
.form-group   { margin-bottom: 18px; }
.form-group label { display: block; margin-bottom: 6px; color: #94a3b8; font-size: 13px; letter-spacing: .4px; text-transform: uppercase; }
.form-control { width: 100%; padding: 10px 12px; background: #162a2d; border: 1px solid #263b40; border-radius: 7px; color: white; box-sizing: border-box; font-size: 14px; }
.form-control:focus { border-color: #dc1623; outline: none; box-shadow: 0 0 0 2px rgba(220,22,35,.2); }

/* ── Checkbox group ── */
.channel-card { background: #162a2d; border: 1px solid #263b40; border-radius: 8px; padding: 14px 16px; margin-bottom: 6px; }
.channel-card label { display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 15px; color: #e2e8f0; margin: 0; text-transform: none; letter-spacing: 0; }
.channel-body { padding: 16px 16px 4px 24px; border-left: 2px solid #263b40; margin: 6px 0 20px 8px; }

/* ── QR/Logo preview ── */
.media-row   { display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap; }
.preview-box { width: 100px; height: 100px; background: #263b40; border-radius: 8px; overflow: hidden; border: 1px solid #455e63; display: flex; align-items: center; justify-content: center; color: #4b6a70; font-size: 12px; flex-shrink: 0; }
.preview-box img { width: 100%; height: 100%; object-fit: cover; }

/* ── DB Table ── */
.db-table { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 13px; }
.db-table th, .db-table td { border: 1px solid #263b40; padding: 10px 12px; text-align: left; }
.db-table th { background: #162a2d; color: #94a3b8; font-weight: 600; }
.db-table tr:hover td { background: #0d191c; }
.db-scroll { max-height: 230px; overflow-y: auto; border: 1px solid #263b40; border-radius: 7px; }

/* ── SQL Editor ── */
.sql-editor { font-family: 'Courier New', monospace; background: #050b0c; color: #00ff88; padding: 16px; border-radius: 7px; border: 1px solid #263b40; width: 100%; box-sizing: border-box; resize: vertical; font-size: 13px; line-height: 1.6; }
.sql-result-table { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 13px; overflow-x: auto; display: block; }
.sql-result-table th, .sql-result-table td { border: 1px solid #263b40; padding: 8px 12px; white-space: nowrap; }
.sql-result-table th { background: #162a2d; color: #38bdf8; }

/* ── Alerts ── */
.alert-success { background: #14532d; color: #4ade80; padding: 12px 16px; border-radius: 7px; margin-bottom: 14px; font-size: 14px; border: 1px solid #166534; }
.alert-danger  { background: #451a1a; color: #f87171; padding: 12px 16px; border-radius: 7px; margin-bottom: 14px; font-size: 14px; border: 1px solid #7f1d1d; }

/* ── Save Button ── */
.btn-save { background: #dc1623; color: white; padding: 12px 36px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 24px; font-size: 15px; transition: background .2s; }
.btn-save:hover { background: #b91220; }
.btn-run  { background: #10b981; color: white; padding: 10px 22px; border: none; border-radius: 7px; cursor: pointer; margin-top: 10px; font-weight: bold; font-size: 14px; }
.btn-run:hover { background: #059669; }
</style>

<section class="page-title">
    <h1>System Console Settings</h1>
    <p>Manage community settings, branding, payment gateways, and database operations.</p>
</section>

<div class="settings-container">

    <!-- ── SIDEBAR ── -->
    <div class="settings-sidebar">
        <button class="tab-btn active" onclick="openTab(event,'hoa')">🏠 HOA &amp; Branding</button>
        <button class="tab-btn" onclick="openTab(event,'payment')">💳 Payment Gateways</button>
        <button class="tab-btn" onclick="openTab(event,'database')">🗄️ Database Manager</button>
    </div>

    <!-- ── MAIN CONTENT ── -->
    <div class="settings-main">

        <!-- FORM wraps HOA + Payment + Notify tabs -->
        <form method="POST" enctype="multipart/form-data">

            <!-- ══ TAB 1: HOA & BRANDING ══ -->
            <div id="hoa" class="tab-content active">
                <p class="tab-section-title">🏠 System &amp; Association Branding</p>

                <!-- Company Logo -->
                <div class="form-group">
                    <label>Company / HOA Logo</label>
                    <div class="media-row">
                        <div>
                            <input type="file" name="company_logo" class="form-control" accept="image/*" style="max-width:320px;">
                            <p style="color:#64748b; font-size:12px; margin:6px 0 0;">PNG, JPG — will replace current logo</p>
                        </div>
                        <div class="preview-box">
                            <?php if (!empty($settings['company_logo'])): ?>
                                <img src="<?php echo htmlspecialchars($settings['company_logo']); ?>" alt="Logo">
                            <?php else: ?>
                                No Logo
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- HOA Name -->
                <div class="form-group">
                    <label>Association Registered Name</label>
                    <input type="text" name="hoa_name" class="form-control"
                           value="<?php echo htmlspecialchars($settings['hoa_name'] ?? ''); ?>"
                           placeholder="e.g. Green Village HOA">
                </div>

                <!-- Contact -->
                <div class="form-group">
                    <label>Official Contact Information</label>
                    <input type="text" name="contact_details" class="form-control"
                           value="<?php echo htmlspecialchars($settings['contact_details'] ?? ''); ?>"
                           placeholder="e.g. +63 912 345 6789 / admin@hoa.com">
                </div>

                <!-- Office Hours -->
                <div class="form-group">
                    <label>HQ Office Business Hours</label>
                    <input type="text" name="office_hours" class="form-control"
                           value="<?php echo htmlspecialchars($settings['office_hours'] ?? ''); ?>"
                           placeholder="e.g. Monday - Friday, 8:00 AM - 5:00 PM">
                </div>

                <div id="save-btn-hoa">
                    <button type="submit" name="save_settings" class="btn-save">💾 Save Branding Settings</button>
                </div>
            </div>

            <!-- ══ TAB 2: PAYMENT GATEWAYS ══ -->
            <div id="payment" class="tab-content">
                <p class="tab-section-title">💳 Payment Gateway Accounts</p>

                <!-- GCASH -->
                <div class="channel-card">
                    <label>
                        <input type="checkbox" name="gcash_enabled" id="gc" <?php echo ($settings['gcash_enabled'] ?? 0) ? 'checked' : ''; ?>>
                        <span>Activate GCash Payment Channel</span>
                    </label>
                </div>
                <div class="channel-body">
                    <div class="form-group">
                        <label>GCash Account Name</label>
                        <input type="text" name="gcash_account_name" class="form-control"
                               value="<?php echo htmlspecialchars($settings['gcash_account_name'] ?? ''); ?>"
                               placeholder="JUAN DELA CRUZ">
                    </div>
                    <div class="form-group">
                        <label>GCash Registered Mobile Number</label>
                        <input type="text" name="gcash_account_number" class="form-control"
                               value="<?php echo htmlspecialchars($settings['gcash_account_number'] ?? ''); ?>"
                               placeholder="0917XXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>GCash QR Code Image</label>
                        <div class="media-row">
                            <div>
                                <input type="file" name="gcash_qr" class="form-control" accept="image/*" style="max-width:280px;">
                                <p style="color:#64748b; font-size:12px; margin:6px 0 0;">Upload new QR to replace current</p>
                            </div>
                            <div class="preview-box">
                                <?php if (!empty($settings['gcash_qr'])): ?>
                                    <img src="<?php echo htmlspecialchars($settings['gcash_qr']); ?>" alt="GCash QR">
                                <?php else: ?>
                                    No QR
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MAYA -->
                <div class="channel-card">
                    <label>
                        <input type="checkbox" name="bank_transfer_enabled" id="bt" <?php echo ($settings['bank_transfer_enabled'] ?? 0) ? 'checked' : ''; ?>>
                        <span>Activate Maya Payment Channel</span>
                    </label>
                </div>
                <div class="channel-body">
                    <div class="form-group">
                        <label>Maya Registered Full Name</label>
                        <input type="text" name="maya_account_name" class="form-control"
                               value="<?php echo htmlspecialchars($settings['maya_account_name'] ?? ''); ?>"
                               placeholder="JUAN DELA CRUZ">
                    </div>
                    <div class="form-group">
                        <label>Maya Wallet Number</label>
                        <input type="text" name="maya_account_number" class="form-control"
                               value="<?php echo htmlspecialchars($settings['maya_account_number'] ?? ''); ?>"
                               placeholder="0917XXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>Maya QR Code Image</label>
                        <div class="media-row">
                            <div>
                                <input type="file" name="maya_qr" class="form-control" accept="image/*" style="max-width:280px;">
                                <p style="color:#64748b; font-size:12px; margin:6px 0 0;">Upload new QR to replace current</p>
                            </div>
                            <div class="preview-box">
                                <?php if (!empty($settings['maya_qr'])): ?>
                                    <img src="<?php echo htmlspecialchars($settings['maya_qr']); ?>" alt="Maya QR">
                                <?php else: ?>
                                    No QR
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Walk-In / Cash -->
                <div class="channel-card">
                    <label>
                        <input type="checkbox" name="paypal_enabled" id="pp" <?php echo ($settings['paypal_enabled'] ?? 0) ? 'checked' : ''; ?>>
                        <span>Enable Walk-In / Cash Payments at Office Counter</span>
                    </label>
                </div>

                <div id="save-btn-payment">
                    <button type="submit" name="save_settings" class="btn-save">💾 Save Payment Settings</button>
                </div>
            </div>

        </form><!-- end main form -->

        <!-- ══ TAB 4: DATABASE MANAGER ══ -->
        <div id="database" class="tab-content">
            <p class="tab-section-title">🗄️ Live Data Engine Console</p>
            <p style="color:#ef4444; font-size:13px; font-weight:bold; margin-bottom:20px;">⚠️ WARNING: Direct SQL execution can permanently alter or corrupt your database. Proceed with extreme caution.</p>

            <!-- Table Status Summary -->
            <h4 style="color:#94a3b8; font-size:13px; text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px;">Database Table Metrics</h4>
            <div class="db-scroll">
                <table class="db-table">
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Rows</th>
                            <th>Engine</th>
                            <th>Size</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($t = mysqli_fetch_assoc($tables_q)): ?>
                            <tr>
                                <td style="font-family:monospace; color:#38bdf8;"><?php echo htmlspecialchars($t['Name']); ?></td>
                                <td><?php echo number_format($t['Rows'] ?? 0); ?></td>
                                <td><span style="font-size:11px; padding:2px 8px; background:#263b40; border-radius:4px;"><?php echo htmlspecialchars($t['Engine']); ?></span></td>
                                <td><?php echo round(($t['Data_length'] + $t['Index_length']) / 1024, 2); ?> KB</td>
                                <td style="font-size:12px; color:#64748b;"><?php echo $t['Update_time'] ? date('M d, Y', strtotime($t['Update_time'])) : '—'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- SQL Console -->
            <h4 style="color:#94a3b8; font-size:13px; text-transform:uppercase; letter-spacing:.5px; margin: 28px 0 10px;">Execute SQL Command</h4>

            <?php if (!empty($query_error)): ?>
                <div class="alert-danger">❌ Error: <?php echo htmlspecialchars($query_error); ?></div>
            <?php endif; ?>
            <?php if (!empty($query_success)): ?>
                <div class="alert-success">✅ <?php echo htmlspecialchars($query_success); ?></div>
            <?php endif; ?>

            <form method="POST">
                <textarea name="sql_query" class="sql-editor" rows="7"
                          placeholder="-- Example:&#10;SELECT * FROM users WHERE role = 'admin';&#10;-- Or:&#10;UPDATE settings SET hoa_name = 'New Name' WHERE setting_id = 1;"><?php echo htmlspecialchars($_POST['sql_query'] ?? ''); ?></textarea>
                <button type="submit" name="execute_sql" class="btn-run"
                        onclick="return confirm('Run this SQL query? This cannot be undone.');">
                    ▶ Run SQL Command
                </button>
            </form>

            <!-- Query Results -->
            <?php if ($query_result_data && count($query_columns) > 0): ?>
                <h4 style="color:#94a3b8; font-size:13px; text-transform:uppercase; letter-spacing:.5px; margin: 24px 0 8px;">Query Results</h4>
                <div style="overflow-x:auto; border:1px solid #263b40; border-radius:7px; max-height:320px; overflow-y:auto;">
                    <table class="sql-result-table">
                        <thead>
                            <tr>
                                <?php foreach ($query_columns as $col): ?>
                                    <th><?php echo htmlspecialchars($col); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($query_result_data)): ?>
                                <tr>
                                    <?php foreach ($query_columns as $col): ?>
                                        <td><?php echo htmlspecialchars($row[$col] ?? 'NULL'); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif (!empty($query_success) && $query_result_data === false): ?>
                <p style="color:#64748b; font-size:13px; margin-top:12px;">Non-SELECT query — no result set to display.</p>
            <?php endif; ?>
        </div><!-- /database tab -->

    </div><!-- /settings-main -->
</div><!-- /settings-container -->

<script>
function openTab(evt, tabName) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    evt.currentTarget.classList.add('active');
}
</script>

<?php include("../includes/admin_footer.php"); ?>