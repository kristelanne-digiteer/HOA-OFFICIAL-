<?php
include("../config/db.php");
include("../includes/admin_header.php");

$id = $_GET['id'];

$request = mysqli_query($conn, "
    SELECT d.*, s.status AS soa_status
    FROM document_requests d
    LEFT JOIN soa_records s ON d.soa_id = s.soa_id
    WHERE d.request_id='$id'
");

$row = mysqli_fetch_assoc($request);

if (!$row) {
    echo "<script>alert('Document request not found.'); window.location='documents.php';</script>";
    exit;
}

if ($row['soa_status'] != 'paid') {
    echo "<script>
        alert('Document fee must be paid first before uploading released document.');
        window.location='documents.php';
    </script>";
    exit;
}

if (isset($_POST['upload'])) {
    if (isset($_FILES['released_file']) && $_FILES['released_file']['error'] == 0) {
        $upload_dir = "../assets/uploads/documents/";

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES['released_file']['name']);

        if (move_uploaded_file($_FILES['released_file']['tmp_name'], $upload_dir . $file_name)) {
            $doc_name = mysqli_real_escape_string($conn, trim($_POST['document_name'] ?? $row['document_type']));
            mysqli_query($conn, "
                UPDATE document_requests
                SET status='completed',
                    released_file='$file_name',
                    document_type='$doc_name'
                WHERE request_id='$id'
            ");

            echo "<script>
                alert('Released document uploaded successfully.');
                window.location='documents.php';
            </script>";
            exit;
        }
    }

    echo "<script>alert('Upload failed.');</script>";
}
?>

<section class="page-title">
    <h1>Upload Released Document</h1>
    <p>Upload the completed document file requested by the homeowner</p>
</section>

<div class="panel">
    <form method="POST" enctype="multipart/form-data">
        <label>Document Name <small style="color:#aaa;"></small></label>
        <input type="text" name="document_name" class="form-input"
               value="<?php echo htmlspecialchars($row['document_type']); ?>" required>

        <label>Payment Status</label>
        <input type="text" class="form-input"
               value="<?php echo htmlspecialchars($row['soa_status']); ?>" readonly>

        <label>Released Document File</label>
        <input type="file" name="released_file" class="form-input" accept=".pdf,.doc,.docx,image/*" required>

        <button type="submit" name="upload" class="btn">Upload Released Document</button>
        <a href="documents.php" class="btn btn-delete">Cancel</a>
    </form>
</div>

<?php include("../includes/admin_footer.php"); ?>