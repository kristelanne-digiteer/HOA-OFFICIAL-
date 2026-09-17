<?php
session_name('HOA_USER_SESSION');
session_start();
include("../config/db.php");
include("../includes/user_header.php");

$user_id = $_SESSION['user_id'];

// SAVE / REMOVE REMINDER
if (isset($_GET['remind'])) {
    $event_id = (int) $_GET['remind'];

    $check_q = mysqli_query($conn, "
        SELECT * FROM event_reminders
        WHERE user_id = '$user_id' AND event_id = '$event_id'
        LIMIT 1
    ");

    if (mysqli_num_rows($check_q) > 0) {
        // Already saved — remove it
        mysqli_query($conn, "
            DELETE FROM event_reminders
            WHERE user_id = '$user_id' AND event_id = '$event_id'
        ");
        echo "<script>alert('Reminder removed.'); window.location='events.php';</script>";
    } else {
        // Not yet saved — add it
        mysqli_query($conn, "
            INSERT INTO event_reminders (user_id, event_id)
            VALUES ('$user_id', '$event_id')
        ");
        echo "<script>alert('Reminder saved!'); window.location='events.php';</script>";
    }
    exit;
}

// FETCH all upcoming events
$events_q = mysqli_query($conn, "
    SELECT e.*,
           IF(r.reminder_id IS NOT NULL, 1, 0) AS has_reminder
    FROM events e
    LEFT JOIN event_reminders r
           ON e.event_id = r.event_id AND r.user_id = '$user_id'
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

// Separate upcoming and past
$upcoming = [];
$past     = [];
$today    = date('Y-m-d');

while ($row = mysqli_fetch_assoc($events_q)) {
    if ($row['event_date'] >= $today) {
        $upcoming[] = $row;
    } else {
        $past[] = $row;
    }
}
?>

<style>
.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 18px;
    margin-top: 15px;
}

.event-card {
    background: #0f1f22;
    border: 1px solid #263b40;
    border-radius: 12px;
    padding: 20px;
    position: relative;
}

.event-card.past {
    opacity: 0.6;
}

.event-badge {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 12px;
    color: white;
    margin-bottom: 10px;
}

.event-card h3 {
    margin: 0 0 8px;
    font-size: 16px;
    color: white;
}

.event-card p {
    margin: 0 0 10px;
    color: #9ca3af;
    font-size: 13px;
    line-height: 1.5;
}

.event-meta {
    font-size: 12px;
    color: #9ca3af;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.event-actions {
    margin-top: 14px;
    display: flex;
    gap: 10px;
}

.btn-remind {
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-block;
}

.btn-remind.saved {
    background: #1e3a3a;
    color: #10b981;
    border: 1px solid #10b981;
}

.btn-remind.unsaved {
    background: #dc1623;
    color: white;
}

.section-title {
    font-size: 18px;
    font-weight: bold;
    margin: 30px 0 5px;
    color: white;
    border-left: 4px solid #dc1623;
    padding-left: 12px;
}

.empty-msg {
    color: #9ca3af;
    font-size: 14px;
    margin-top: 10px;
}
</style>

<section class="page-title">
    <h1>Events Calendar</h1>
    <p>Stay updated on HOA meetings, fiestas, maintenance schedules, and community events</p>
</section>

<!-- UPCOMING EVENTS -->
<div class="section-title">📅 Upcoming Events</div>

<?php if (empty($upcoming)): ?>
    <p class="empty-msg">No upcoming events at the moment.</p>
<?php else: ?>
    <div class="events-grid">
        <?php foreach ($upcoming as $row): ?>
        <div class="event-card">
            <div class="event-badge" style="background:<?= $type_colors[$row['event_type']] ?>">
                <?= $type_labels[$row['event_type']] ?>
            </div>

            <h3><?= htmlspecialchars($row['title']) ?></h3>

            <?php if ($row['description']): ?>
                <p><?= nl2br(htmlspecialchars($row['description'])) ?></p>
            <?php endif; ?>

            <div class="event-meta">
                📆 <?= date('F d, Y', strtotime($row['event_date'])) ?>
                <?php if ($row['event_time']): ?>
                    &nbsp;·&nbsp; 🕐 <?= date('h:i A', strtotime($row['event_time'])) ?>
                <?php endif; ?>
            </div>

            <?php if ($row['location']): ?>
                <div class="event-meta">
                    📍 <?= htmlspecialchars($row['location']) ?>
                </div>
            <?php endif; ?>

            <div class="event-actions">
                <a href="events.php?remind=<?= $row['event_id'] ?>"
                   class="btn-remind <?= $row['has_reminder'] ? 'saved' : 'unsaved' ?>">
                    <?= $row['has_reminder'] ? '✔ Reminder Saved' : '🔔 Save Reminder' ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- PAST EVENTS -->
<div class="section-title" style="margin-top:40px;">🕓 Past Events</div>

<?php if (empty($past)): ?>
    <p class="empty-msg">No past events.</p>
<?php else: ?>
    <div class="events-grid">
        <?php foreach ($past as $row): ?>
        <div class="event-card past">
            <div class="event-badge" style="background:<?= $type_colors[$row['event_type']] ?>">
                <?= $type_labels[$row['event_type']] ?>
            </div>

            <h3><?= htmlspecialchars($row['title']) ?></h3>

            <?php if ($row['description']): ?>
                <p><?= nl2br(htmlspecialchars($row['description'])) ?></p>
            <?php endif; ?>

            <div class="event-meta">
                📆 <?= date('F d, Y', strtotime($row['event_date'])) ?>
                <?php if ($row['event_time']): ?>
                    &nbsp;·&nbsp; 🕐 <?= date('h:i A', strtotime($row['event_time'])) ?>
                <?php endif; ?>
            </div>

            <?php if ($row['location']): ?>
                <div class="event-meta">
                    📍 <?= htmlspecialchars($row['location']) ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include("../includes/user_footer.php"); ?>