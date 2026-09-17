<?php
include("../config/db.php");
include("../includes/admin_header.php");

$id = $_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM rental_items WHERE item_id='$id'");
$row = mysqli_fetch_assoc($result);

if (isset($_POST['update'])) {
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $quantity = $_POST['quantity'];
    $rental_fee = $_POST['rental_fee'];
    $status = $_POST['status'];

    mysqli_query($conn, "
        UPDATE rental_items
        SET item_name='$item_name',
            quantity='$quantity',
            rental_fee='$rental_fee',
            status='$status'
        WHERE item_id='$id'
    ");

    echo "<script>
        alert('Rental item updated successfully.');
        window.location='rentals.php';
    </script>";
}
?>

<section class="page-title">
    <h1>Edit Rental Item</h1>
    <p>Update inventory quantity, rate, and status</p>
</section>

<div class="panel">
    <form method="POST">

        <label>Item Name</label>
        <input type="text" name="item_name" class="form-input"
               value="<?php echo htmlspecialchars($row['item_name']); ?>" required>

        <label>Quantity</label>
        <input type="number" name="quantity" class="form-input"
               value="<?php echo $row['quantity']; ?>" required>

        <label>Rental Fee</label>
        <input type="number" step="0.01" name="rental_fee" class="form-input"
               value="<?php echo $row['rental_fee']; ?>" required>

        <label>Status</label>
        <select name="status" class="form-input">
            <option value="available" <?php if($row['status']=='available') echo 'selected'; ?>>Available</option>
            <option value="damaged" <?php if($row['status']=='damaged') echo 'selected'; ?>>Damaged</option>
            <option value="unavailable" <?php if($row['status']=='unavailable') echo 'selected'; ?>>Unavailable</option>
        </select>

        <button type="submit" name="update" class="btn">Update Item</button>
        <a href="rentals.php" class="btn btn-delete">Cancel</a>

    </form>
</div>

<?php include("../includes/admin_footer.php"); ?>