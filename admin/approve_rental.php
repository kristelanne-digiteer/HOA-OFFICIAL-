<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

$id = $_GET['id'];

$rental_q = mysqli_query($conn, "
    SELECT r.*, hp.masterlist_id
    FROM rental_requests r
    LEFT JOIN homeowner_profiles hp ON r.user_id = hp.user_id
    WHERE r.rental_id='$id'
");

if (!$rental_q) {
    die("Query error: " . mysqli_error($conn));
}

$rental = mysqli_fetch_assoc($rental_q);

if (!$rental) {
    echo "<script>alert('Rental not found.'); window.location='rentals.php';</script>";
    exit;
}

if ($rental['status'] == 'approved') {
    echo "<script>alert('This rental is already approved.'); window.location='rentals.php';</script>";
    exit;
}

// FIX: If masterlist_id is missing, look it up via the user's name in homeowners_masterlist.
// The old code fell back to user_id which could accidentally match another homeowner's ID.
$homeowner_id = null;

if (!empty($rental['masterlist_id'])) {
    // Has a proper profile link — use it
    $homeowner_id = $rental['masterlist_id'];
} else {
    // No profile link yet — try to find by name
    $user_q = mysqli_query($conn, "
        SELECT CONCAT(first_name, ' ', last_name) AS full_name
        FROM users WHERE user_id='{$rental['user_id']}' LIMIT 1
    ");
    $user_row = mysqli_fetch_assoc($user_q);

    if ($user_row) {
        $full_name = mysqli_real_escape_string($conn, trim($user_row['full_name']));
        $ml_q = mysqli_query($conn, "
            SELECT homeowner_id FROM homeowners_masterlist
            WHERE LOWER(name) = LOWER('$full_name') LIMIT 1
        ");
        if (mysqli_num_rows($ml_q) > 0) {
            $ml_row = mysqli_fetch_assoc($ml_q);
            $homeowner_id = $ml_row['homeowner_id'];

            // Also fix the missing homeowner_profiles link so this doesn't happen again
            $profile_check = mysqli_query($conn, "
                SELECT profile_id FROM homeowner_profiles WHERE user_id='{$rental['user_id']}' LIMIT 1
            ");
            if (mysqli_num_rows($profile_check) > 0) {
                mysqli_query($conn, "
                    UPDATE homeowner_profiles
                    SET masterlist_id='$homeowner_id'
                    WHERE user_id='{$rental['user_id']}'
                ");
            } else {
                mysqli_query($conn, "
                    INSERT INTO homeowner_profiles (user_id, masterlist_id)
                    VALUES ('{$rental['user_id']}', '$homeowner_id')
                ");
            }
        }
    }
}

// If still no homeowner_id found, auto-create a masterlist entry for this user
if (empty($homeowner_id)) {
    $user_q2 = mysqli_query($conn, "
        SELECT CONCAT(first_name, ' ', last_name) AS full_name, email
        FROM users WHERE user_id='{$rental['user_id']}' LIMIT 1
    ");
    $user_row2 = mysqli_fetch_assoc($user_q2);

    if ($user_row2) {
        $auto_name = mysqli_real_escape_string($conn, trim($user_row2['full_name']));

        // Double-check not already in masterlist (case-insensitive)
        $existing_ml = mysqli_query($conn, "
            SELECT homeowner_id FROM homeowners_masterlist
            WHERE LOWER(name) = LOWER('$auto_name') LIMIT 1
        ");

        if (mysqli_num_rows($existing_ml) > 0) {
            $existing_row = mysqli_fetch_assoc($existing_ml);
            $homeowner_id = $existing_row['homeowner_id'];
        } else {
            // Create new masterlist entry
            mysqli_query($conn, "
                INSERT INTO homeowners_masterlist (name, full_address, contact_no, gender, status, created_at)
                VALUES ('$auto_name', '', '', '', 'active', NOW())
            ");
            $homeowner_id = mysqli_insert_id($conn);
        }

        // Link homeowner_profiles
        $profile_chk = mysqli_query($conn, "
            SELECT profile_id FROM homeowner_profiles WHERE user_id='{$rental['user_id']}' LIMIT 1
        ");
        if (mysqli_num_rows($profile_chk) > 0) {
            mysqli_query($conn, "
                UPDATE homeowner_profiles SET masterlist_id='$homeowner_id'
                WHERE user_id='{$rental['user_id']}'
            ");
        } else {
            mysqli_query($conn, "
                INSERT INTO homeowner_profiles (user_id, masterlist_id)
                VALUES ('{$rental['user_id']}', '$homeowner_id')
            ");
        }

        // Mark user as HOA member
        mysqli_query($conn, "UPDATE users SET hoa_member=1 WHERE user_id='{$rental['user_id']}'");
    } else {
        echo "<script>alert('Cannot approve: user account not found.'); window.location='rentals.php';</script>";
        exit;
    }
}

$deposit = 0;

$items_q = mysqli_query($conn, "
    SELECT ri.item_name
    FROM rental_request_items rri
    JOIN rental_items ri ON rri.item_id = ri.item_id
    WHERE rri.rental_id='$id'
");

while ($item = mysqli_fetch_assoc($items_q)) {
    $name = $item['item_name'];

    if (stripos($name, 'Court') !== false) {
        $deposit = max($deposit, 500);
    }

    if (stripos($name, 'Sound') !== false || stripos($name, 'Spotlight') !== false) {
        $deposit = max($deposit, 1000);
    }

    if (stripos($name, 'Chair') !== false || stripos($name, 'Table') !== false) {
        $deposit = max($deposit, 300);
    }
}

$rental_total = $rental['rental_total'] ?? 0;
$total_balance = $rental_total + $deposit;
$billing_month = date("F Y", strtotime($rental['event_date']));
$due_date = $rental['event_date'];
$purpose = "Rental Fee - Event Date " . $rental['event_date'];

$insert_soa = mysqli_query($conn, "
    INSERT INTO soa_records
    (homeowner_id, billing_month, monthly_dues, penalties, total_balance, due_date, status, payment_purpose)
    VALUES
    ('{$homeowner_id}', '$billing_month', '$rental_total', '$deposit', '$total_balance', '$due_date', 'unpaid', '$purpose')
");

if (!$insert_soa) {
    die("SOA insert error: " . mysqli_error($conn));
}

$soa_id = mysqli_insert_id($conn);

$update_rental = mysqli_query($conn, "
    UPDATE rental_requests
    SET status='approved',
        payment_status='pending',
        security_deposit='$deposit',
        soa_id='$soa_id'
    WHERE rental_id='$id'
");

if (!$update_rental) {
    die("Rental update error: " . mysqli_error($conn));
}


logActivity($conn, $_SESSION['user_id'], "Approved rental request ID #$id", "Rental");
echo "<script>
    alert('Rental approved and billing created.');
    window.location='rentals.php';
</script>";
exit;
?>