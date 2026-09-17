<?php
include("../config/db.php");
include("../includes/admin_header.php");

$id = $_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM document_templates WHERE template_id='$id'");
$row = mysqli_fetch_assoc($result);

if (isset($_POST['update'])) {
    $document_name = mysqli_real_escape_string($conn, $_POST['document_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $template_link = mysqli_real_escape_string($conn, $_POST['template_link']);
    $status = $_POST['status'];

    mysqli_query($conn, "
        UPDATE document_templates
        SET document_name='$document_name',
            description='$description',
            template_link='$template_link',
            status='$status'
        WHERE template_id='$id'
    ");

    echo "<script>alert('Template updated successfully'); window.location='documents.php';</script>";
}
?>

<section class="page-title">
    <h1>Edit Template</h1>
    <p>Update document template details</p>
</section>

<div class="panel">
    <form method="POST">
        <label>Document Name</label>
        <input type="text" name="document_name" class="form-input" value="<?php echo htmlspecialchars($row['document_name']); ?>" required>

        <label>Description</label>
        <textarea name="description" class="form-input" rows="4" required><?php echo htmlspecialchars($row['description']); ?></textarea>

        <label>Template Link</label>
        <input type="text" name="template_link" class="form-input" value="<?php echo htmlspecialchars($row['template_link']); ?>">

        <label>Status</label>
        <select name="status" class="form-input">
            <option value="active" <?php if($row['status']=='active') echo 'selected'; ?>>Active</option>
            <option value="inactive" <?php if($row['status']=='inactive') echo 'selected'; ?>>Inactive</option>
        </select>

        <button type="submit" name="update" class="btn">Update Template</button>
        <a href="documents.php" class="btn btn-delete">Cancel</a>
    </form>
</div>

<?php include("../includes/admin_footer.php"); ?>