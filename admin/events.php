<?php
include("../config/db.php");
include("../includes/admin_header.php");

// CREATE
if (isset($_POST['create'])) {
    $title       = mysqli_real_escape_string($conn, trim($_POST['title']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $event_type  = mysqli_real_escape_string($conn, $_POST['event_type']);
    $event_date  = $_POST['event_date'];
    $event_time  = $_POST['event_time'];
    $location    = mysqli_real_escape_string($conn, trim($_POST['location']));
    $created_by  = $_SESSION['user_id'];

    mysqli_query($conn, "
        INSERT INTO events (title, description, event_type, event_date, event_time, location, created_by)
        VALUES ('$title', '$description', '$event_type', '$event_date', '$event_time', '$location', '$created_by')
    ");

    echo "<script>alert('Event added!'); window.location='events.php';</script>";
    exit;
}

// UPDATE
if (isset($_POST['update'])) {
    $id          = (int) $_POST['event_id'];
    $title       = mysqli_real_escape_string($conn, trim($_POST['title']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $event_type  = mysqli_real_escape_string($conn, $_POST['event_type']);
    $event_date  = $_POST['event_date'];
    $event_time  = $_POST['event_time'];
    $location    = mysqli_real_escape_string($conn, trim($_POST['location']));

    mysqli_query($conn, "
        UPDATE events
        SET title       = '$title',
            description = '$description',
            event_type  = '$event_type',
            event_date  = '$event_date',
            event_time  = '$event_time',
            location    = '$location'
        WHERE event_id  = '$id'
    ");

    echo "<script>alert('Event updated!'); window.location='events.php';</script>";
    exit;
}

// DELETE
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM events WHERE event_id='$id'");
    echo "<script>alert('Event deleted!'); window.location='events.php';</script>";
    exit;
}

// FETCH for edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $r  = mysqli_query($conn, "SELECT * FROM events WHERE event_id='$id' LIMIT 1");
    $edit_data = mysqli_fetch_assoc($r);
}

// FETCH all events
$all_q = mysqli_query($conn, "
    SELECT e.*, u.first_name, u.last_name
    FROM events e
    LEFT JOIN users u ON e.created_by = u.user_id
    ORDER BY e.event_date ASC
");

$type_labels = [
    'meeting'     => '🏛 Meeting',
    'fiesta'      => '🎉 Fiesta',
    'maintenance' => '🔧 Maintenance',
    'community'   => '🌿 Community',
];

$type_colors = [
    'meeting'     => '#3b82f6',
    'fiesta'      => '#f59e0b',
    'maintenance' => '#ef4444',
    'community'   => '#10b981',
];
?>

<section class="page-title">
    <h1>Events Calendar</h1>
    <p>Manage HOA meetings, fiesta, maintenance schedules, and community events</p>
</section>

<div class="panel">
    <h3><?= $edit_data ? 'Edit Event' : 'Add Event' ?></h3>

    <form method="POST">
        <?php if ($edit_data): ?>
            <input type="hidden" name="event_id" value="<?= $edit_data['event_id'] ?>">
        <?php endif; ?>

        <label>Event Title</label>
        <input type="text"
               name="title"
               class="form-input"
               placeholder="e.g. Monthly HOA Meeting"
               value="<?= $edit_data ? htmlspecialchars($edit_data['title']) : '' ?>"
               required>

        <label>Description</label>
        <textarea name="description"
                  class="form-input"
                  rows="3"
                  placeholder="Event details..."><?= $edit_data ? htmlspecialchars($edit_data['description']) : '' ?></textarea>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
            <div>
                <label>Event Type</label>
                <select name="event_type" class="form-input" required>
                    <option value="">Select type</option>
                    <option value="meeting"     <?= ($edit_data && $edit_data['event_type'] == 'meeting')     ? 'selected' : '' ?>>🏛 Meeting</option>
                    <option value="fiesta"      <?= ($edit_data && $edit_data['event_type'] == 'fiesta')      ? 'selected' : '' ?>>🎉 Fiesta</option>
                    <option value="maintenance" <?= ($edit_data && $edit_data['event_type'] == 'maintenance') ? 'selected' : '' ?>>🔧 Maintenance</option>
                    <option value="community"   <?= ($edit_data && $edit_data['event_type'] == 'community')   ? 'selected' : '' ?>>🌿 Community</option>
                </select>
            </div>
            <div>
                <label>Location</label>
                <input type="text"
                       name="location"
                       class="form-input"
                       placeholder="e.g. Clubhouse"
                       value="<?= $edit_data ? htmlspecialchars($edit_data['location']) : '' ?>">
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
            <div>
                <label>Event Date</label>
                <input type="date"
                       name="event_date"
                       class="form-input"
                       value="<?= $edit_data ? $edit_data['event_date'] : '' ?>"
                       required>
            </div>
            <div>
                <label>Event Time</label>
                <input type="time"
                       name="event_time"
                       class="form-input"
                       value="<?= $edit_data ? $edit_data['event_time'] : '' ?>">
            </div>
        </div>

        <br>
        <?php if ($edit_data): ?>
            <button type="submit" name="update" class="btn">Update Event</button>
            <a href="events.php" class="btn btn-delete">Cancel</a>
        <?php else: ?>
            <button type="submit" name="create" class="btn">Add Event</button>
        <?php endif; ?>
    </form>
</div>

<div class="panel">
    <h3>All Events</h3>

    <?php if (mysqli_num_rows($all_q) == 0): ?>
        <p style="color:#9ca3af;">No events yet.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
<table class="data-table" style="width:100%; border-collapse:collapse;">
    <thead>
        <tr>
            <th style="padding:12px 15px; text-align:left; white-space:nowrap;">Type</th>
            <th style="padding:12px 15px; text-align:left;">Title</th>
            <th style="padding:12px 15px; text-align:left;">Description</th>
            <th style="padding:12px 15px; text-align:left; white-space:nowrap;">Date & Time</th>
            <th style="padding:12px 15px; text-align:left;">Location</th>
            <th style="padding:12px 15px; text-align:left; white-space:nowrap;">Added By</th>
            <th style="padding:12px 15px; text-align:left;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = mysqli_fetch_assoc($all_q)): ?>
        <tr style="border-top:1px solid #1e3a3a;">
            <td style="padding:14px 15px; vertical-align:top;">
                <span style="
                    background: <?= $type_colors[$row['event_type']] ?>;
                    color: white;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 12px;
                    white-space: nowrap;
                    display: inline-block;
                ">
                    <?= $type_labels[$row['event_type']] ?>
                </span>
            </td>
            <td style="padding:14px 15px; vertical-align:top;">
                <strong><?= htmlspecialchars($row['title']) ?></strong>
            </td>
            <td style="padding:14px 15px; vertical-align:top; max-width:250px; color:#9ca3af; font-size:13px;">
                <?= nl2br(htmlspecialchars($row['description'])) ?>
            </td>
            <td style="padding:14px 15px; vertical-align:top; white-space:nowrap;">
                📆 <?= date('M d, Y', strtotime($row['event_date'])) ?>
                <?php if ($row['event_time']): ?>
                    <br>🕐 <small><?= date('h:i A', strtotime($row['event_time'])) ?></small>
                <?php endif; ?>
            </td>
            <td style="padding:14px 15px; vertical-align:top;">
                <?= $row['location'] ? '📍 ' . htmlspecialchars($row['location']) : '<span style="color:#9ca3af;">—</span>' ?>
            </td>
            <td style="padding:14px 15px; vertical-align:top; white-space:nowrap;">
                <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
            </td>
            <td style="padding:14px 15px; vertical-align:top; white-space:nowrap;">
                <a href="events.php?edit=<?= $row['event_id'] ?>"
                   class="btn"
                   style="font-size:12px; padding:6px 12px; margin-bottom:5px; display:inline-block;">
                   Edit
                </a>
                <a href="events.php?delete=<?= $row['event_id'] ?>"
                   class="btn btn-delete"
                   style="font-size:12px; padding:6px 12px; display:inline-block;"
                   onclick="return confirm('Delete this event?')">
                   Delete
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</div>
    <?php endif; ?>
</div>

<?php include("../includes/admin_footer.php"); ?>