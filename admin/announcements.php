<?php
include("../config/db.php");
include("../includes/admin_header.php");

// CREATE
if (isset($_POST['create'])) {
    $title   = mysqli_real_escape_string($conn, trim($_POST['title']));
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $created_by = $_SESSION['user_id'];

    mysqli_query($conn, "
        INSERT INTO announcements (title, content, created_by)
        VALUES ('$title', '$content', '$created_by')
    ");

    echo "<script>alert('Announcement created!'); window.location='announcements.php';</script>";
    exit;
}

// UPDATE
if (isset($_POST['update'])) {
    $id      = (int) $_POST['announcement_id'];
    $title   = mysqli_real_escape_string($conn, trim($_POST['title']));
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));

    mysqli_query($conn, "
        UPDATE announcements
        SET title='$title', content='$content'
        WHERE announcement_id='$id'
    ");

    echo "<script>alert('Announcement updated!'); window.location='announcements.php';</script>";
    exit;
}

// DELETE
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM announcements WHERE announcement_id='$id'");
    echo "<script>alert('Announcement deleted!'); window.location='announcements.php';</script>";
    exit;
}

// FETCH for edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $r  = mysqli_query($conn, "SELECT * FROM announcements WHERE announcement_id='$id' LIMIT 1");
    $edit_data = mysqli_fetch_assoc($r);
}

// FETCH all announcements
$all_q = mysqli_query($conn, "
    SELECT a.*, u.first_name, u.last_name
    FROM announcements a
    LEFT JOIN users u ON a.created_by = u.user_id
    ORDER BY a.created_at DESC
");
?>

<section class="page-title">
    <h1>Announcements</h1>
    <p>Create and manage community announcements</p>
</section>

<div class="panel">
    <h3><?= $edit_data ? 'Edit Announcement' : 'Create Announcement' ?></h3>

    <form method="POST">
        <?php if ($edit_data): ?>
            <input type="hidden" name="announcement_id" value="<?= $edit_data['announcement_id'] ?>">
        <?php endif; ?>

        <label>Title</label>
        <input type="text"
               name="title"
               class="form-input"
               placeholder="Announcement title"
               value="<?= $edit_data ? htmlspecialchars($edit_data['title']) : '' ?>"
               required>

        <label>Content</label>
        <textarea name="content"
                  class="form-input"
                  rows="5"
                  placeholder="Write your announcement here..."
                  required><?= $edit_data ? htmlspecialchars($edit_data['content']) : '' ?></textarea>

        <br><br>
        <?php if ($edit_data): ?>
            <button type="submit" name="update" class="btn">Update Announcement</button>
            <a href="announcements.php" class="btn btn-delete">Cancel</a>
        <?php else: ?>
            <button type="submit" name="create" class="btn">Post Announcement</button>
        <?php endif; ?>
    </form>
</div>

<div class="panel">
    <h3>All Announcements</h3>

    <?php if (mysqli_num_rows($all_q) == 0): ?>
        <p style="color:#9ca3af;">No announcements yet.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Content</th>
                    <th>Posted By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($all_q)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                    <td><?= nl2br(htmlspecialchars($row['content'])) ?></td>
                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                    <td><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
                    <td>
                        <a href="announcements.php?edit=<?= $row['announcement_id'] ?>" class="btn" style="font-size:12px;">Edit</a>
                        <a href="announcements.php?delete=<?= $row['announcement_id'] ?>"
                           class="btn btn-delete"
                           style="font-size:12px;"
                           onclick="return confirm('Delete this announcement?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include("../includes/admin_footer.php"); ?>