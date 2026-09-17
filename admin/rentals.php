<?php
include("../config/db.php");
include("../includes/admin_header.php");

$items = mysqli_query($conn, "SELECT * FROM rental_items ORDER BY item_id ASC");
$items_for_fees = mysqli_query($conn, "SELECT * FROM rental_items ORDER BY item_id ASC");

$rental_requests = mysqli_query($conn, "
    SELECT 
        r.*,
        u.first_name,
        u.last_name,
        s.status AS bill_status,
        s.total_balance,
        GROUP_CONCAT(CONCAT(ri.item_name, ' x', rri.quantity) SEPARATOR ', ') AS requested_items
    FROM rental_requests r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN soa_records s ON r.soa_id = s.soa_id
    LEFT JOIN rental_request_items rri ON r.rental_id = rri.rental_id
    LEFT JOIN rental_items ri ON rri.item_id = ri.item_id
    GROUP BY r.rental_id
    ORDER BY r.rental_id DESC
");

$calendar_rentals = mysqli_query($conn, "
    SELECT r.*, u.first_name, u.last_name
    FROM rental_requests r
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.status IN ('approved','completed')
    ORDER BY r.event_date ASC
");

$history = mysqli_query($conn, "
    SELECT 
        r.*,
        u.first_name,
        u.last_name,
        s.status AS bill_status,
        p.payment_id,
        p.receipt_number
    FROM rental_requests r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN soa_records s ON r.soa_id = s.soa_id
    LEFT JOIN payments p ON p.soa_id = r.soa_id AND p.status='approved'
    WHERE r.status IN ('completed','rejected','cancelled')
    ORDER BY r.event_date DESC
");

$total_items = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM rental_items"));
$pending_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM rental_requests WHERE status='pending'"));
$completed_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM rental_requests WHERE status='completed'"));
$paid_count = mysqli_num_rows(mysqli_query($conn, "
    SELECT r.* FROM rental_requests r
    JOIN soa_records s ON r.soa_id=s.soa_id
    WHERE s.status='paid'
"));

$calendar_events = [];

while ($cal = mysqli_fetch_assoc($calendar_rentals)) {
    $name = trim(($cal['first_name'] ?? 'Homeowner') . ' ' . ($cal['last_name'] ?? ''));

    $calendar_events[] = [
    "id" => $cal['rental_id'],
    "title" => "Rental - " . $name,
    "start" => $cal['event_date'],
    "color" => $cal['status'] == 'completed' ? "#16a34a" : "#dc2626"
    ];
}

$calendar_events_json = json_encode($calendar_events);
?>

<section class="page-title">
    <h1>Rental Management</h1>
    <p>Manage rental requests, inventory, calendar, fees, and rental history</p>
</section>

<section class="cards">
    <div class="card">
        <h3>Rental Items</h3>
        <div class="value"><?php echo $total_items; ?></div>
        <div class="note">Available inventory</div>
    </div>

    <div class="card">
        <h3>Pending Rentals</h3>
        <div class="value"><?php echo $pending_count; ?></div>
        <div class="note">Awaiting approval</div>
    </div>

    <div class="card">
        <h3>Completed Rentals</h3>
        <div class="value"><?php echo $completed_count; ?></div>
        <div class="note">Rental history</div>
    </div>

    <div class="card">
        <h3>Paid Rentals</h3>
        <div class="value"><?php echo $paid_count; ?></div>
        <div class="note">Connected billing</div>
    </div>
</section>

<div class="tabs">
    <button class="tab-btn active" onclick="openTab(event, 'requests')">Rental Requests</button>
    <button class="tab-btn" onclick="openTab(event, 'inventory')">Inventory</button>
    <button class="tab-btn" onclick="openTab(event, 'calendar')">Rental Calendar</button>
    <button class="tab-btn" onclick="openTab(event, 'fees')">Rental Fees</button>
    <button class="tab-btn" onclick="openTab(event, 'history')">Rental History</button>
</div>

<div id="requests" class="tab-content active-tab">
    <div class="panel">
        <div class="panel-header">
    <h2>Rental Requests</h2>

    <a href="clear_paid_rentals.php"
   class="btn btn-delete"
   onclick="return confirm('Clear all paid rental requests?');">
   Clear Paid Rentals
</a>
</div>

        <table class="table">
            <tr>
                <th>ID</th>
                <th>Renter</th>
                <th>Items</th>
                <th>Event Date</th>
                <th>Total</th>
                <th>Deposit</th>
                <th>Billing</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

            <?php if (mysqli_num_rows($rental_requests) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($rental_requests)) { ?>
                    <tr>
                        <td><?php echo $row['rental_id']; ?></td>
                        <td><?php echo htmlspecialchars(($row['first_name'] ?? 'Homeowner') . ' ' . ($row['last_name'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($row['requested_items'] ?? 'No items'); ?></td>
                        <td><?php echo $row['event_date']; ?></td>
                        <td>₱<?php echo number_format($row['rental_total'], 2); ?></td>
                        <td>₱<?php echo number_format($row['security_deposit'] ?? 0, 2); ?></td>
                        <td>
                            <?php if (!empty($row['soa_id'])) { ?>
                                <span class="badge <?php echo $row['bill_status'] == 'paid' ? 'green' : 'yellow'; ?>">
                                    <?php echo htmlspecialchars($row['bill_status'] ?? 'unpaid'); ?>
                                </span>
                            <?php } else { ?>
                                No bill yet
                            <?php } ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $row['status'] == 'approved' || $row['status'] == 'completed' ? 'green' : ($row['status'] == 'rejected' ? 'red' : 'yellow'); ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <?php if ($row['status'] == 'pending') { ?>
                                <a href="approve_rental.php?id=<?php echo $row['rental_id']; ?>" class="btn btn-edit"
                                   onclick="return confirm('Approve rental and create billing?');">
                                   Approve
                                </a>

                                <a href="reject_rental.php?id=<?php echo $row['rental_id']; ?>" class="btn btn-delete"
                                   onclick="return confirm('Reject this rental request?');">
                                   Reject
                                </a>
                            <?php } ?>

                            <?php if (!empty($row['soa_id']) && ($row['bill_status'] ?? '') != 'paid' && $row['status'] == 'approved') { ?>
                                <a href="mark_soa_paid.php?id=<?php echo $row['soa_id']; ?>" class="btn btn-edit"
                                   onclick="return confirm('Mark rental bill as paid?');">
                                   Mark Paid
                                </a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="9">No rental requests yet.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<div id="inventory" class="tab-content">
    <div class="panel">
        <div class="panel-header">
            <h2>Inventory</h2>
            <a href="add_rental_item.php" class="btn">Add Item</a>
        </div>

        <table class="table">
            <tr>
                <th>ID</th>
                <th>Item</th>
                <th>Quantity</th>
                <th>Rate</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($items)) { ?>
                <tr>
                    <td><?php echo $row['item_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td>₱<?php echo number_format($row['rental_fee'], 2); ?></td>
                    <td>
                        <span class="badge <?php echo $row['status'] == 'available' ? 'green' : 'red'; ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                    <td class="action-buttons">
    <a href="edit_rental_item.php?id=<?php echo $row['item_id']; ?>" class="btn btn-edit">
        Edit
    </a>

    <a href="delete_rental_item.php?id=<?php echo $row['item_id']; ?>"
       class="btn btn-delete"
       onclick="return confirm('Delete this rental item?');">
       Delete
    </a>

    <a href="mark_item_damaged.php?id=<?php echo $row['item_id']; ?>"
       class="btn btn-delete"
       onclick="return confirm('Mark this item as damaged?');">
       Mark Damaged
    </a>
</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<div id="calendar" class="tab-content">
    <div class="panel">
        <h2>Rental Calendar</h2>
        <p style="color:#cbd5d6; margin-bottom:15px;">
        Red dates are approved/reserved rentals. Green dates are completed rentals.
    </p>

        <div id="rentalCalendar"></div>
    </div>
</div>

<div id="fees" class="tab-content">
    <div class="panel">
        <h2>Rental Fees</h2>

        <table class="table">
            <tr>
                <th>Rental Item</th>
                <th>Quantity Available</th>
                <th>Rate</th>
                <th>Action</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($items_for_fees)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td><?php echo $row['item_name'] == 'Electricity' ? 'Per usage' : $row['quantity']; ?></td>
                    <td>
                        ₱<?php echo number_format($row['rental_fee'], 2); ?>
                        <?php echo $row['item_name'] == 'Electricity' ? ' per kWh' : ' per event'; ?>
                    </td>
                    <td>
                        <a href="edit_rental_item.php?id=<?php echo $row['item_id']; ?>" class="btn btn-edit">Update Fee</a>
                    </td>
                </tr>
            <?php } ?>
        </table>

        <br><br>

        <h2>Security Deposit</h2>
        <table class="table">
            <tr>
                <th>Rental Type</th>
                <th>Deposit</th>
            </tr>
            <tr>
                <td>Chairs / Tables only</td>
                <td>₱300 refundable deposit</td>
            </tr>
            <tr>
                <td>With Sound System / Spotlights</td>
                <td>₱1,000 refundable deposit</td>
            </tr>
            <tr>
                <td>Court rental</td>
                <td>₱500 refundable deposit</td>
            </tr>
        </table>

        <br><br>

        <h2>Payment Rule</h2>
        <ul style="color:#cbd5d6; line-height:1.8; margin-left:20px;">
            <li>50% down payment is required to reserve the date.</li>
            <li>Remaining balance should be paid before or on the event date.</li>
            <li>Security deposit is refundable after inspection.</li>
            <li>Payment status options: Pending, Partially Paid, Fully Paid, Refunded, Cancelled.</li>
        </ul>
    </div>
</div>

<div id="history" class="tab-content">
    <div class="panel">
        <div class="panel-header">
            <h2>Rental History</h2>

            <a href="clear_completed_rentals.php"
               class="btn btn-delete"
               onclick="return confirm('Clear completed rental records?');">
               Clear Completed
            </a>
        </div>

        <table class="table">
            <tr>
                <th>Rental ID</th>
                <th>Renter</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
                <th>Receipt</th>
            </tr>

            <?php if (mysqli_num_rows($history) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($history)) { ?>
                    <tr>
                        <td><?php echo $row['rental_id']; ?></td>
                        <td><?php echo htmlspecialchars(($row['first_name'] ?? 'Homeowner') . ' ' . ($row['last_name'] ?? '')); ?></td>
                        <td><?php echo $row['event_date']; ?></td>
                        <td>₱<?php echo number_format($row['rental_total'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $row['status'] == 'completed' ? 'green' : 'red'; ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($row['payment_id'])) { ?>
                                <a href="receipt.php?id=<?php echo $row['payment_id']; ?>" class="btn btn-view" target="_blank">
                                    Receipt
                                </a>
                            <?php } else { ?>
                                No receipt
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="6">No completed rentals yet.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

<style>
#rentalCalendar {
    background: #0f1f22 !important;
    color: #ffffff !important;
    padding: 15px !important;
    border-radius: 12px !important;
}

#rentalCalendar .fc,
#rentalCalendar .fc-scrollgrid,
#rentalCalendar .fc-theme-standard td,
#rentalCalendar .fc-theme-standard th,
#rentalCalendar .fc-daygrid-day,
#rentalCalendar .fc-daygrid-day-frame {
    background: #0f1f22 !important;
    border-color: #263b40 !important;
}

#rentalCalendar .fc-toolbar-title,
#rentalCalendar .fc-col-header-cell-cushion,
#rentalCalendar .fc-daygrid-day-number {
    color: #ffffff !important;
}

#rentalCalendar .fc-button {
    background: #dc1623 !important;
    border: none !important;
}

#rentalCalendar .fc-event {
    border: none !important;
    padding: 3px 5px !important;
    border-radius: 5px !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('rentalCalendar');

    if (calendarEl) {
        window.rentalCalendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    height: 650,
    events: <?php echo $calendar_events_json; ?>,
    dateClick: function(info) {
        alert('Selected date: ' + info.dateStr + '\\nApproved rentals will appear here after admin approval.');
    },
    eventClick: function(info) {

    if (confirm(
        info.event.title +
        '\nDate: ' + info.event.startStr +
        '\n\nCancel this reservation?'
    )) {

        window.location =
            "cancel_rental.php?id=" + info.event.id;
    }

}
});

        window.rentalCalendar.render();
    }
});

function openTab(event, tabName) {
    let contents = document.querySelectorAll(".tab-content");
    let buttons = document.querySelectorAll(".tab-btn");

    contents.forEach(content => content.classList.remove("active-tab"));
    buttons.forEach(button => button.classList.remove("active"));

    document.getElementById(tabName).classList.add("active-tab");
    event.currentTarget.classList.add("active");

    if (tabName === "calendar" && window.rentalCalendar) {
        setTimeout(function () {
            window.rentalCalendar.updateSize();
        }, 100);
    }
}


</script>

<?php include("../includes/admin_footer.php"); ?>