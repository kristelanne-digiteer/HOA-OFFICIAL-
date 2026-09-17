<?php
include("../config/db.php");
include("../includes/user_header.php");

$user_id = $_SESSION['user_id'];

$items = mysqli_query($conn, "
    SELECT *
    FROM rental_items
    WHERE status='available'
    ORDER BY item_id ASC
");

$my_requests = mysqli_query($conn, "
    SELECT *
    FROM rental_requests
    WHERE user_id='$user_id'
    ORDER BY rental_id DESC
");

$approved_rentals = mysqli_query($conn, "
    SELECT *
    FROM rental_requests
    WHERE status='approved'
    ORDER BY event_date ASC
");

$calendar_events = [];

while ($cal = mysqli_fetch_assoc($approved_rentals)) {
    $calendar_events[] = [
        "title" => "Reserved",
        "start" => $cal['event_date'],
        "color" => "#dc2626"
    ];
}

$calendar_events_json = json_encode($calendar_events);

if (isset($_POST['submit_rental'])) {
    $event_date = $_POST['event_date'];
    $payment_option = $_POST['payment_option'];
    $accepted_terms = isset($_POST['accepted_terms']) ? 1 : 0;

    $check_date = mysqli_query($conn, "
        SELECT *
        FROM rental_requests
        WHERE event_date='$event_date'
        AND status='approved'
    ");

    if (mysqli_num_rows($check_date) > 0) {
        echo "<script>
            alert('Selected date is already reserved. Please choose another date.');
            window.location='rentals.php';
        </script>";
        exit;
    }

    if (!$accepted_terms) {
        echo "<script>alert('Please agree to the rental rules and policies.');</script>";
    } else {
        mysqli_query($conn, "
            INSERT INTO rental_requests
            (user_id, event_date, rental_total, payment_option, status, accepted_terms)
            VALUES
            ('$user_id', '$event_date', 0, '$payment_option', 'pending', '$accepted_terms')
        ");

        $rental_id = mysqli_insert_id($conn);
        $rental_total = 0;

        if (isset($_POST['items'])) {
            foreach ($_POST['items'] as $item_id) {
                $qty = $_POST['qty'][$item_id] ?? 1;

                $item_q = mysqli_query($conn, "
                    SELECT *
                    FROM rental_items
                    WHERE item_id='$item_id'
                ");

                $item = mysqli_fetch_assoc($item_q);

                if ($item) {
                    $subtotal = $item['rental_fee'] * $qty;
                    $rental_total += $subtotal;

                    mysqli_query($conn, "
                        INSERT INTO rental_request_items
                        (rental_id, item_id, quantity, subtotal)
                        VALUES
                        ('$rental_id', '$item_id', '$qty', '$subtotal')
                    ");
                }
            }
        }

        mysqli_query($conn, "
            UPDATE rental_requests
            SET rental_total='$rental_total'
            WHERE rental_id='$rental_id'
        ");

        echo "<script>
            alert('Rental request submitted. Please wait for admin approval.');
            window.location='rentals.php';
        </script>";
        exit;
    }
}
?>

<section class="page-title">
    <h1>Rental Request Management</h1>
    <p>View available rentals, submit requests, and track booking status</p>
</section>

<div class="tabs">
    <button class="tab-btn active" onclick="openTab(event, 'available')">Available Rentals</button>
    <button class="tab-btn" onclick="openTab(event, 'book')">Book Rental</button>
    <button class="tab-btn" onclick="openTab(event, 'calendar')">Rental Calendar</button>
    <button class="tab-btn" onclick="openTab(event, 'rules')">Rental Rules & Policies</button>
    <button class="tab-btn" onclick="openTab(event, 'status')">Rental Status / History</button>
</div>

<div id="available" class="tab-content active-tab">
    <div class="panel">
        <h2>Available Rentals</h2>

        <table class="table">
            <tr>
                <th>Item</th>
                <th>Quantity Available</th>
                <th>Rate</th>
                <th>Status</th>
            </tr>

            <?php
            mysqli_data_seek($items, 0);
            while ($row = mysqli_fetch_assoc($items)) {
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td><?php echo $row['item_name'] == 'Electricity' ? 'Per usage' : $row['quantity']; ?></td>
                    <td>
                        ₱<?php echo number_format($row['rental_fee'], 2); ?>
                        <?php echo $row['item_name'] == 'Electricity' ? ' per kWh' : ' per event'; ?>
                    </td>
                    <td><span class="badge green"><?php echo htmlspecialchars($row['status']); ?></span></td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<div id="book" class="tab-content">
    <div class="panel">
        <h2>Book Rental</h2>

        <form method="POST">
            <label>Event Date</label>
            <input type="date" name="event_date" class="form-input" required>

            <label>Choose Payment Option</label>
            <select name="payment_option" class="form-input" required>
                <option value="GCash">GCash</option>
                <option value="Maya">Maya</option>
                <option value="Cash">Cash Payment at HOA Office</option>
            </select>

            <br>

            <h2>Items Selected</h2>

            <table class="table">
                <tr>
                    <th>Select</th>
                    <th>Item</th>
                    <th>Rate</th>
                    <th>Quantity / kWh</th>
                </tr>

                <?php
                mysqli_data_seek($items, 0);
                while ($row = mysqli_fetch_assoc($items)) {
                ?>
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                name="items[]"
                                value="<?php echo $row['item_id']; ?>"
                                class="rental-item"
                                data-rate="<?php echo $row['rental_fee']; ?>"
                                data-name="<?php echo htmlspecialchars($row['item_name']); ?>">
                        </td>

                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>

                        <td>
                            ₱<?php echo number_format($row['rental_fee'], 2); ?>
                            <?php echo $row['item_name'] == 'Electricity' ? ' per kWh' : ''; ?>
                        </td>

                        <td>
                            <input
                                type="number"
                                name="qty[<?php echo $row['item_id']; ?>]"
                                value="1"
                                min="1"
                                class="qty-input"
                                data-item="<?php echo $row['item_id']; ?>"
                                style="width:90px;">
                        </td>
                    </tr>
                <?php } ?>
            </table>

            <br>

            <div class="panel">
                <h2>Rental Total</h2>

                <p style="color:#cbd5d6;">
                    Rental Fee:
                    <strong>₱<span id="rentalFee">0.00</span></strong>
                </p>

                <p style="color:#cbd5d6;">
                    Estimated Security Deposit:
                    <strong>₱<span id="depositFee">0.00</span></strong>
                </p>

                <p style="color:#22c55e;">
                    Total Amount to Prepare:
                    <strong>₱<span id="totalFee">0.00</span></strong>
                </p>
            </div>

            <label>
                <input type="checkbox" name="accepted_terms" required>
                I have read and agree to all rental rules and policies.
            </label>

            <br><br>

            <button type="submit" name="submit_rental" class="btn">
                Submit Rental Request
            </button>
        </form>
    </div>
</div>

<div id="calendar" class="tab-content">
    <div class="panel">
        <h2>Rental Calendar / Availability</h2>

        <p style="color:#cbd5d6; margin-bottom:15px;">
            Red dates are already reserved. Click an available date to use it for your rental request.
        </p>

        <div id="userRentalCalendar"></div>
    </div>
</div>

<div id="rules" class="tab-content">
    <div class="panel">
        <h2>Rental Rules & Policies</h2>

        <div style="color:#cbd5d6; line-height:1.8;">
            <p><strong>1. General Guidelines:</strong> HOA rental items must be used responsibly and only by registered residents or authorized persons.</p>
            <p><strong>2. Reservation Policy:</strong> Requests must be submitted through the system and are confirmed only after admin approval.</p>
            <p><strong>3. Payment Policy:</strong> A 50% down payment is required to reserve the date. Full payment must be settled before or on the event date.</p>
            <p><strong>4. Security Deposit:</strong> Refundable deposit is required and may be deducted for damage, loss, or missing items.</p>
            <p><strong>5. Usage Rules:</strong> Items must be used only for the approved event and location. No subleasing or lending.</p>
            <p><strong>6. Return Policy:</strong> Items must be returned on or before the agreed return date and in the same condition.</p>
            <p><strong>7. Damage and Liability:</strong> The renter is responsible for damages or losses during the rental period.</p>
            <p><strong>8. Cancellation Policy:</strong> Cancellation must be made at least 2–3 days before the event.</p>
            <p><strong>9. HOA Rights:</strong> HOA may approve, reject, cancel, or inspect rentals before and after use.</p>
            <p><strong>10. Agreement:</strong> By submitting a rental request, the renter agrees to all rules and policies.</p>
        </div>
    </div>
</div>

<div id="status" class="tab-content">
    <div class="panel">
        <h2>Rental Status / History</h2>

        <table class="table">
            <tr>
                <th>Rental ID</th>
                <th>Event Date</th>
                <th>Total</th>
                <th>Payment Option</th>
                <th>Status</th>
                <th>Schedule</th>
            </tr>

            <?php if (mysqli_num_rows($my_requests) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($my_requests)) { ?>
                    <tr>
                        <td><?php echo $row['rental_id']; ?></td>
                        <td><?php echo $row['event_date']; ?></td>
                        <td>₱<?php echo number_format($row['rental_total'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['payment_option']); ?></td>
                        <td>
                            <span class="badge <?php echo $row['status'] == 'approved' || $row['status'] == 'completed' ? 'green' : ($row['status'] == 'rejected' ? 'red' : 'yellow'); ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'approved') { ?>
                                View schedule in rental calendar
                            <?php } elseif ($row['status'] == 'completed') { ?>
                                Completed rental record
                            <?php } else { ?>
                                Waiting for admin update
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="6">No rental requests yet.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('userRentalCalendar');

    if (calendarEl) {
        window.userRentalCalendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 650,
            events: <?php echo $calendar_events_json; ?>,
            dateClick: function(info) {
                const eventDateInput = document.querySelector('input[name="event_date"]');

                if (eventDateInput) {
                    eventDateInput.value = info.dateStr;

                    document.querySelectorAll(".tab-content").forEach(content => content.classList.remove("active-tab"));
                    document.querySelectorAll(".tab-btn").forEach(button => button.classList.remove("active"));

                    document.getElementById("book").classList.add("active-tab");

                    const bookButtons = document.querySelectorAll(".tab-btn");
                    bookButtons.forEach(button => {
                        if (button.textContent.trim() === "Book Rental") {
                            button.classList.add("active");
                        }
                    });
                }
            }
        });

        window.userRentalCalendar.render();
    }
});

function openTab(event, tabName) {
    let contents = document.querySelectorAll(".tab-content");
    let buttons = document.querySelectorAll(".tab-btn");

    contents.forEach(content => content.classList.remove("active-tab"));
    buttons.forEach(button => button.classList.remove("active"));

    document.getElementById(tabName).classList.add("active-tab");
    event.currentTarget.classList.add("active");

    if (tabName === "calendar" && window.userRentalCalendar) {
        setTimeout(function () {
            window.userRentalCalendar.updateSize();
        }, 100);
    }
}

function computeRental() {
    let rentalFee = 0;
    let deposit = 0;

    document.querySelectorAll(".rental-item").forEach(item => {
        if (item.checked) {
            let itemId = item.value;
            let rate = parseFloat(item.dataset.rate);
            let name = item.dataset.name;
            let qtyInput = document.querySelector('.qty-input[data-item="' + itemId + '"]');
            let qty = parseFloat(qtyInput.value || 1);

            rentalFee += rate * qty;

            if (name.includes("Court")) {
                deposit = Math.max(deposit, 500);
            }

            if (name.includes("Sound") || name.includes("Spotlight")) {
                deposit = Math.max(deposit, 1000);
            }

            if (name.includes("Chair") || name.includes("Table")) {
                deposit = Math.max(deposit, 300);
            }
        }
    });

    document.getElementById("rentalFee").innerText = rentalFee.toFixed(2);
    document.getElementById("depositFee").innerText = deposit.toFixed(2);
    document.getElementById("totalFee").innerText = (rentalFee + deposit).toFixed(2);
}

document.querySelectorAll(".rental-item, .qty-input").forEach(input => {
    input.addEventListener("change", computeRental);
    input.addEventListener("keyup", computeRental);
});
</script>

<?php include("../includes/user_footer.php"); ?>