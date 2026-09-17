<?php
include("../config/db.php");
include("../includes/admin_header.php");

$faqs = mysqli_query($conn, "SELECT * FROM faqs ORDER BY faq_id DESC");
$tickets = mysqli_query($conn, "SELECT * FROM support_tickets ORDER BY ticket_id DESC");

$total_faqs = mysqli_num_rows($faqs);
$total_tickets = mysqli_num_rows($tickets);
?>

<section class="page-title">
    <h1>Support / FAQs</h1>
    <p>Manage homeowner support requests, FAQs, and help guides</p>
</section>

<section class="cards">
    <div class="card">
        <h3>Total FAQs</h3>
        <div class="value"><?php echo $total_faqs; ?></div>
        <div class="note">Published help items</div>
    </div>

    <div class="card">
        <h3>Support Tickets</h3>
        <div class="value"><?php echo $total_tickets; ?></div>
        <div class="note">Homeowner concerns</div>
    </div>

    <div class="card">
        <h3>Open Tickets</h3>
        <div class="value">0</div>
        <div class="note">Needs response</div>
    </div>
</section>

<div class="content-grid">
    <div class="panel">
        <div class="panel-header">
            <h2>FAQ Management</h2>
            <button class="btn">Add FAQ</button>
        </div>

        <table class="table">
            <tr>
                <th>ID</th>
                <th>Question</th>
                <th>Category</th>
                <th>Actions</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($faqs)) { ?>
                <tr>
                    <td><?php echo $row['faq_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['question']); ?></td>
                    <td><span class="badge yellow"><?php echo htmlspecialchars($row['category']); ?></span></td>
                    <td>
                        <a href="#" class="btn btn-view">View</a>
                        <a href="#" class="btn btn-edit">Edit</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>Support Tickets</h2>
            <button class="btn">Guides</button>
        </div>

        <table class="table">
            <tr>
                <th>Subject</th>
                <th>Status</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($tickets)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                    <td><span class="badge green"><?php echo htmlspecialchars($row['status']); ?></span></td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<?php include("../includes/admin_footer.php"); ?>