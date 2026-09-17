<?php
include("../config/db.php");
include("../includes/user_header.php");

$user_id = $_SESSION['user_id'];

if (isset($_POST['submit_request'])) {
    $document_type = mysqli_real_escape_string($conn, $_POST['document_type']);

    // If "Others" ang pinili, gamitin ang custom input
    if ($document_type === 'Others') {
        $document_type = mysqli_real_escape_string($conn, trim($_POST['document_type_other']));
        if (empty($document_type)) {
            $document_type = 'Others';
        }
    }

    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);

    mysqli_query($conn, "
        INSERT INTO document_requests
        (user_id, document_type, purpose, status, date_requested)
        VALUES
        ('$user_id', '$document_type', '$purpose', 'pending', NOW())
    ");

    echo "<script>
        alert('Document request submitted successfully.');
        window.location='documents.php';
    </script>";
    exit;
}

// Fetch active templates from database
$templates_q = mysqli_query($conn, "
    SELECT * FROM document_templates
    WHERE status = 'active'
    ORDER BY document_name ASC
");

$requests = mysqli_query($conn, "
    SELECT *
    FROM document_requests
    WHERE user_id='$user_id'
    ORDER BY request_id DESC
");

$history = mysqli_query($conn, "
    SELECT *
    FROM document_requests
    WHERE user_id='$user_id'
    AND status IN ('approved','rejected','completed')
    ORDER BY request_id DESC
");
?>

<section class="page-title">
    <h1>Document Request Management</h1>
    <p>Request HOA documents and track request status</p>
</section>

<div class="tabs">
    <button class="tab-btn active" onclick="openTab(event, 'request')">Request Document</button>
    <button class="tab-btn" onclick="openTab(event, 'templates')">Available Templates</button>
    <button class="tab-btn" onclick="openTab(event, 'status')">Request Status</button>
    <button class="tab-btn" onclick="openTab(event, 'history')">Request History</button>
</div>

<!-- ── REQUEST DOCUMENT TAB ─────────────────────────────────────────────── -->
<div id="request" class="tab-content active-tab">
    <div class="panel">
        <h2>Request Document</h2>

        <form method="POST">
            <label>Document Type</label>
            <select name="document_type" class="form-input" required onchange="toggleOthersInput(this)">
                <option value="">Select document</option>

                <?php if (mysqli_num_rows($templates_q) > 0): ?>
                    <?php while ($tpl = mysqli_fetch_assoc($templates_q)): ?>
                        <option value="<?php echo htmlspecialchars($tpl['document_name']); ?>">
                            <?php echo htmlspecialchars($tpl['document_name']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php else: ?>
                    <option value="" disabled>No templates available yet</option>
                <?php endif; ?>

                <option value="Others">Others</option>
            </select>

            <!-- Lalabas lang ito kapag "Others" ang pinili -->
            <div id="others-input-wrapper" style="display:none; margin-top:10px;">
                <label>Please specify document type</label>
                <input type="text" name="document_type_other" class="form-input"
                       placeholder="Enter document name..."
                       maxlength="255">
            </div>

            <label style="margin-top:15px;">Purpose</label>
            <textarea name="purpose" class="form-input" rows="4"
                      placeholder="Example: Employment requirement, school requirement, personal records..."
                      required></textarea>

            <button type="submit" name="submit_request" class="btn">
                Submit Request
            </button>
        </form>
    </div>
</div>

<!-- ── AVAILABLE TEMPLATES TAB ──────────────────────────────────────────── -->
<div id="templates" class="tab-content">
    <div class="panel">
        <h2>Available Document Templates</h2>

        <?php
        // Re-fetch since pointer is at end
        $templates_view = mysqli_query($conn, "
            SELECT * FROM document_templates
            WHERE status = 'active'
            ORDER BY document_name ASC
        ");
        ?>

        <table class="table">
            <tr>
                <th>Document Name</th>
                <th>Description</th>
                <th>Template</th>
            </tr>

            <?php if (mysqli_num_rows($templates_view) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($templates_view)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['document_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td>
                            <?php if (!empty($row['template_link'])): ?>
                                <a href="<?php echo htmlspecialchars($row['template_link']); ?>"
                                   target="_blank" class="btn btn-view">
                                    Open Template
                                </a>
                            <?php else: ?>
                                <span style="color:#9ca3af;">No link</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No templates available yet. Please contact the HOA admin.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- ── REQUEST STATUS TAB ────────────────────────────────────────────────── -->
<div id="status" class="tab-content">
    <div class="panel">
        <h2>Request Status</h2>

        <table class="table">
            <tr>
                <th>Request ID</th>
                <th>Document Type</th>
                <th>Purpose</th>
                <th>Date Requested</th>
                <th>Status</th>
            </tr>

            <?php if (mysqli_num_rows($requests) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($requests)): ?>
                    <tr>
                        <td><?php echo $row['request_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['document_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                        <td><?php echo $row['date_requested']; ?></td>
                        <td>
                            <span class="badge <?php echo $row['status'] == 'completed' ? 'green' : ($row['status'] == 'rejected' ? 'red' : 'yellow'); ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No document requests yet.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- ── REQUEST HISTORY TAB ──────────────────────────────────────────────── -->
<div id="history" class="tab-content">
    <div class="panel">
        <h2>Request History</h2>

        <table class="table">
            <tr>
                <th>Request ID</th>
                <th>Document Type</th>
                <th>Date Requested</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php if (mysqli_num_rows($history) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($history)): ?>
                    <tr>
                        <td><?php echo $row['request_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['document_type']); ?></td>
                        <td><?php echo $row['date_requested']; ?></td>
                        <td>
                            <span class="badge <?php echo $row['status'] == 'completed' ? 'green' : ($row['status'] == 'rejected' ? 'red' : 'yellow'); ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'completed'): ?>
                                <a href="download_document.php?id=<?php echo $row['request_id']; ?>"
                                   class="btn btn-view">
                                    Download
                                </a>
                            <?php else: ?>
                                No file yet
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No request history yet.</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
function openTab(event, tabName) {
    let contents = document.querySelectorAll(".tab-content");
    let buttons  = document.querySelectorAll(".tab-btn");

    contents.forEach(c => c.classList.remove("active-tab"));
    buttons.forEach(b  => b.classList.remove("active"));

    document.getElementById(tabName).classList.add("active-tab");
    event.currentTarget.classList.add("active");
}

function toggleOthersInput(select) {
    const wrapper = document.getElementById('others-input-wrapper');
    const input   = wrapper.querySelector('input[name="document_type_other"]');

    if (select.value === 'Others') {
        wrapper.style.display = 'block';
        input.required = true;
    } else {
        wrapper.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}
</script>

<?php include("../includes/user_footer.php"); ?>