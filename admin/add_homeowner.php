<?php
include("../config/db.php");
include("../includes/admin_header.php");

if (isset($_POST['save'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $full_address = mysqli_real_escape_string($conn, $_POST['full_address']);
    $birthday = $_POST['birthday'];
    $age = $_POST['age'];
    $contact_no = mysqli_real_escape_string($conn, $_POST['contact_no']);
    $gender = $_POST['gender'];

    $sql = "INSERT INTO homeowners_masterlist 
            (name, full_address, birthday, age, contact_no, gender, status)
            VALUES 
            ('$name', '$full_address', '$birthday', '$age', '$contact_no', '$gender', 'active')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Homeowner added successfully'); window.location='homeowners.php';</script>";
    } else {
        echo "<script>alert('Error adding homeowner');</script>";
    }
}
?>

<section class="page-title">
    <h1>Add Homeowner</h1>
    <p>Add a new homeowner record to the masterlist</p>
</section>

<div class="panel">
    <form method="POST">

        <label>Name</label>
        <input type="text" name="name" class="form-input" placeholder="LASTNAME, FIRSTNAME MIDDLENAME" required>

        <label>Full Address</label>
        <textarea name="full_address" class="form-input" rows="3" required></textarea>

        <label>Birthday</label>
        <input type="date" name="birthday" class="form-input" required>

        <label>Age</label>
        <input type="number" name="age" class="form-input" required>

        <label>Contact Number</label>
        <input type="text" name="contact_no" class="form-input" required>

        <label>Gender</label>
        <select name="gender" class="form-input" required>
            <option value="">Select Gender</option>
            <option value="M">Male</option>
            <option value="F">Female</option>
        </select>

        <br>

        <button type="submit" name="save" class="btn">Save Homeowner</button>
        <a href="homeowners.php" class="btn btn-delete">Cancel</a>

    </form>
</div>

<?php include("../includes/admin_footer.php"); ?>