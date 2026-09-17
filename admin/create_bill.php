<?php
include("../config/db.php");
include("../includes/admin_header.php");

$homeowners = mysqli_query($conn, "
    SELECT homeowner_id, name 
    FROM homeowners_masterlist 
    WHERE status='active'
    ORDER BY name ASC
");

if (isset($_POST['generate'])) {
    $bill_to = $_POST['bill_to'];
    $homeowner_id = $_POST['homeowner_id'] ?? '';
    $billing_month = mysqli_real_escape_string($conn, $_POST['billing_month']);
    $monthly_dues = $_POST['monthly_dues'];
    $penalties = $_POST['penalties'];
    $due_date = $_POST['due_date'];
    $payment_purpose = mysqli_real_escape_string($conn, $_POST['payment_purpose']);

    if ($bill_to == 'all') {
        $check = mysqli_query($conn, "
            SELECT * FROM soa_records 
            WHERE billing_month='$billing_month'
            AND payment_purpose='$payment_purpose'
        ");

        if (mysqli_num_rows($check) > 0) {
            echo "<script>alert('Bills for this month and purpose already exist.');</script>";
        } else {
            mysqli_query($conn, "
                INSERT INTO soa_records
                (homeowner_id, billing_month, monthly_dues, penalties, total_balance, due_date, status, payment_purpose)
                SELECT
                    homeowner_id,
                    '$billing_month',
                    '$monthly_dues',
                    '$penalties',
                    ('$monthly_dues' + '$penalties'),
                    '$due_date',
                    'unpaid',
                    '$payment_purpose'
                FROM homeowners_masterlist
                WHERE status='active'
            ");

            echo "<script>
                alert('Bills generated for all active homeowners.');
                window.location='payments.php';
            </script>";
            exit;
        }
    } else {
        if ($homeowner_id == '') {
            echo "<script>alert('Please select a homeowner.');</script>";
        } else {
            mysqli_query($conn, "
                INSERT INTO soa_records
                (homeowner_id, billing_month, monthly_dues, penalties, total_balance, due_date, status, payment_purpose)
                VALUES
                (
                    '$homeowner_id',
                    '$billing_month',
                    '$monthly_dues',
                    '$penalties',
                    ('$monthly_dues' + '$penalties'),
                    '$due_date',
                    'unpaid',
                    '$payment_purpose'
                )
            ");

            echo "<script>
                alert('Bill generated for selected homeowner.');
                window.location='payments.php';
            </script>";
            exit;
        }
    }
}
?>

<section class="page-title">
    <h1>Create Bills</h1>
    <p>Generate SOA records for all homeowners or a selected homeowner</p>
</section>

<div class="panel">
    <form method="POST">

        <label>Bill To</label>
        <select name="bill_to" id="bill_to" class="form-input" onchange="toggleHomeownerSelect()" required>
            <option value="all">All Active Homeowners</option>
            <option value="specific">Specific Homeowner</option>
        </select>

        <div id="homeowner_select" style="display:none;">
            <label>Select Homeowner</label>
            <select name="homeowner_id" class="form-input">
                <option value="">Select homeowner</option>
                <?php while ($h = mysqli_fetch_assoc($homeowners)) { ?>
                    <option value="<?php echo $h['homeowner_id']; ?>">
                        <?php echo htmlspecialchars($h['name']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <label>Billing Month</label>
        <input 
            type="text" 
            name="billing_month" 
            class="form-input" 
            placeholder="Example: August 2026" 
            required
        >

        <label>Payment Purpose</label>
        <select name="payment_purpose" class="form-input" required>
            <option value="Monthly Dues">Monthly Dues</option>
            <option value="Maintenance Fee">Maintenance Fee</option>
            <option value="Document Fee">Document Fee</option>
            <option value="Rental Fee">Rental Fee</option>
            <option value="Penalty">Penalty</option>
            <option value="Other HOA Fee">Other HOA Fee</option>
        </select>

        <label>Amount / Monthly Dues</label>
        <input 
            type="number" 
            step="0.01" 
            name="monthly_dues" 
            class="form-input" 
            value="500.00" 
            required
        >

        <label>Penalty</label>
        <input 
            type="number" 
            step="0.01" 
            name="penalties" 
            class="form-input" 
            value="0.00" 
            required
        >

        <label>Due Date</label>
        <input 
            type="date" 
            name="due_date" 
            class="form-input" 
            required
        >

        <button 
            type="submit" 
            name="generate" 
            class="btn"
            onclick="return confirm('Generate bill/s now?');"
        >
            Generate Bills
        </button>

        <a href="payments.php" class="btn btn-delete">Cancel</a>

    </form>
</div>

<script>
function toggleHomeownerSelect() {
    let billTo = document.getElementById("bill_to").value;
    let homeownerSelect = document.getElementById("homeowner_select");

    if (billTo === "specific") {
        homeownerSelect.style.display = "block";
    } else {
        homeownerSelect.style.display = "none";
    }
}
</script>

<?php include("../includes/admin_footer.php"); ?>