<?php
include("../config/db.php");
include("../includes/admin_header.php");

$id = $_GET['id'];

$result = mysqli_query($conn, "
    SELECT hm.*, hp.user_id, u.role, u.email, u.status AS user_status
    FROM homeowners_masterlist hm
    LEFT JOIN homeowner_profiles hp ON hm.homeowner_id = hp.masterlist_id
    LEFT JOIN users u ON hp.user_id = u.user_id
    WHERE hm.homeowner_id = '$id'
");

$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo "<script>alert('Homeowner not found'); window.location='homeowners.php';</script>";
    exit;
}

$user_id = $row['user_id'] ?? null;

if (isset($_POST['update'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $full_address = mysqli_real_escape_string($conn, $_POST['full_address']);
    $birthday = $_POST['birthday'];
    $age = $_POST['age'];
    $contact_no = mysqli_real_escape_string($conn, $_POST['contact_no']);
    $gender = $_POST['gender'];
    $status = $_POST['status'];
    $role = $_POST['role'];

    $sql = "
        UPDATE homeowners_masterlist 
        SET name = '$name',
            full_address = '$full_address',
            birthday = '$birthday',
            age = '$age',
            contact_no = '$contact_no',
            gender = '$gender',
            status = '$status'
        WHERE homeowner_id = '$id'
    ";

    $update_masterlist = mysqli_query($conn, $sql);

    if ($user_id) {
        $name_parts = explode(',', $name);

        if (count($name_parts) >= 2) {
            $last_name = trim($name_parts[0]);
            $first_name = trim($name_parts[1]);
        } else {
            $first_name = trim($name);
            $last_name = "";
        }

        $first_name = mysqli_real_escape_string($conn, $first_name);
        $last_name = mysqli_real_escape_string($conn, $last_name);
        $role = mysqli_real_escape_string($conn, $role);

        mysqli_query($conn, "
            UPDATE users
            SET first_name = '$first_name',
                last_name = '$last_name',
                role = '$role'
            WHERE user_id = '$user_id'
        ");
    }

    if ($update_masterlist) {
        echo "<script>alert('Homeowner updated successfully'); window.location='homeowners.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error updating homeowner');</script>";
    }
}
?>

<section class="page-title">
    <h1>Edit Homeowner</h1>
    <p>Update homeowner information and account role</p>
</section>

<div class="panel">
    <form method="POST">

        <label>Name</label>
        <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($row['name']); ?>" required>

        <label>Full Address</label>
        <textarea name="full_address" class="form-input" rows="3" required><?php echo htmlspecialchars($row['full_address']); ?></textarea>

        <label>Birthday</label>
        <input type="date" name="birthday" class="form-input" value="<?php echo $row['birthday']; ?>" required>

        <label>Age</label>
        <input type="number" name="age" class="form-input" value="<?php echo $row['age']; ?>" required>

        <label>Contact Number</label>
        <input type="text" name="contact_no" class="form-input" value="<?php echo htmlspecialchars($row['contact_no']); ?>" required>

        <label>Gender</label>
        <select name="gender" class="form-input" required>
            <option value="M" <?php if($row['gender'] == 'M') echo 'selected'; ?>>Male</option>
            <option value="F" <?php if($row['gender'] == 'F') echo 'selected'; ?>>Female</option>
        </select>

        <label>Status</label>
        <select name="status" class="form-input" required>
            <option value="active" <?php if($row['status'] == 'active') echo 'selected'; ?>>Active</option>
            <option value="inactive" <?php if($row['status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
            <option value="pending_verification" <?php if($row['status'] == 'pending_verification') echo 'selected'; ?>>Pending Verification</option>
        </select>

        <label>Account Role</label>
        <select name="role" class="form-input" required>
            <option value="homeowner" <?php if(($row['role'] ?? 'homeowner') == 'homeowner') echo 'selected'; ?>>
                Homeowner
            </option>
            <option value="admin" <?php if(($row['role'] ?? '') == 'admin') echo 'selected'; ?>>
                Admin
            </option>
        </select>

        <br>

        <button type="submit" name="update" class="btn">Update Homeowner</button>
        <a href="homeowners.php" class="btn btn-delete">Cancel</a>

    </form>
</div>

<?php include("../includes/admin_footer.php"); ?>