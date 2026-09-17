<?php
include("../config/db.php");
include("../includes/admin_header.php");

if (isset($_POST['save'])) {
    $document_name = mysqli_real_escape_string($conn, $_POST['document_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $template_link = mysqli_real_escape_string($conn, $_POST['template_link']);

    mysqli_query($conn, "
        INSERT INTO document_templates
        (document_name, description, template_link, status)
        VALUES
        ('$document_name', '$description', '$template_link', 'active')
    ");

    echo "<script>alert('Template added successfully'); window.location='documents.php';</script>";
}
?>

<section class="page-title">
    <h1>Add Template</h1>
    <p>Add document template link</p>
</section>

<div class="panel">
    <form method="POST">
        <label>Document Name</label>
        <input type="text" name="document_name" class="form-input" required>

        <label>Description</label>
        <textarea name="description" class="form-input" rows="4" required></textarea>

        <label>Template Link</label>
        <input type="text" name="template_link" class="form-input">

        <button type="submit" name="save" class="btn">Save Template</button>
        <a href="documents.php" class="btn btn-delete">Cancel</a>
    </form>
</div>

<?php include("../includes/admin_footer.php"); ?>