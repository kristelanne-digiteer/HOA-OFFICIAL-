<?php
include("../config/db.php");
include("../includes/admin_header.php");

$id = $_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM homeowners_masterlist WHERE homeowner_id = '$id'");
$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo "<script>alert('Homeowner not found'); window.location='homeowners.php';</script>";
}
?>

<section class="page-title">
    <h1>View Homeowner</h1>
    <p>Complete homeowner profile details</p>
</section>

<div class="panel">
    <table class="table">
        <tr>
            <th>Field</th>
            <th>Information</th>
        </tr>
        <tr>
            <td>Homeowner ID</td>
            <td><?php echo $row['homeowner_id']; ?></td>
        </tr>
        <tr>
            <td>Name</td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
        </tr>
        <tr>
            <td>Full Address</td>
            <td><?php echo htmlspecialchars($row['full_address']); ?></td>
        </tr>
        <tr>
            <td>Birthday</td>
            <td><?php echo $row['birthday']; ?></td>
        </tr>
        <tr>
            <td>Age</td>
            <td><?php echo $row['age']; ?></td>
        </tr>
        <tr>
            <td>Contact Number</td>
            <td><?php echo htmlspecialchars($row['contact_no']); ?></td>
        </tr>
        <tr>
            <td>Gender</td>
            <td><?php echo $row['gender']; ?></td>
        </tr>
        <tr>
            <td>Status</td>
            <td><span class="badge green"><?php echo $row['status']; ?></span></td>
        </tr>
        <tr>
            <td>Date Added</td>
            <td><?php echo $row['created_at']; ?></td>
        </tr>
    </table>

    <br>

    <a href="edit_homeowner.php?id=<?php echo $row['homeowner_id']; ?>" class="btn btn-edit">Edit</a>
    <a href="homeowners.php" class="btn">Back</a>
</div>

<?php include("../includes/admin_footer.php"); ?>