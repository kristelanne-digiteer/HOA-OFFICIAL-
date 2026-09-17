<?php
include("../config/db.php");
include("../includes/admin_header.php");

$id = $_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM soa_records WHERE soa_id='$id'");
$row = mysqli_fetch_assoc($result);

if (isset($_POST['save'])) {
    $penalty = $_POST['penalty'];

    $new_total = $row['monthly_dues'] + $penalty;

    mysqli_query($conn, "
        UPDATE soa_records
        SET penalties='$penalty',
            total_balance='$new_total'
        WHERE soa_id='$id'
    ");

    echo "<script>
        alert('Penalty updated successfully');
        window.location='payments.php';
    </script>";
}
?>

<section class="page-title">
    <h1>Add Penalty</h1>
    <p>Update penalty for this SOA record</p>
</section>

<div class="panel">
    <form method="POST">
        <label>Penalty Amount</label>
        <input type="number" step="0.01" name="penalty" class="form-input" value="<?php echo $row['penalties']; ?>" required>

        <button type="submit" name="save" class="btn">Save Penalty</button>
        <a href="payments.php" class="btn btn-delete">Cancel</a>
    </form>
</div>

<?php include("../includes/admin_footer.php"); ?>