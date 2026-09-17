<?php
include("../config/db.php");
include("../includes/admin_header.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$result = mysqli_query($conn, "SELECT * FROM users WHERE user_id='$id' LIMIT 1");
$row    = mysqli_fetch_assoc($result);

if (!$row) {
    echo "<script>alert('User not found.'); window.location='homeowners.php';</script>";
    exit;
}
?>

<section class="page-title">
    <h1>View User</h1>
    <p>User account details</p>
</section>

<div class="panel">
    <table class="table">
        <tr><th>Field</th><th>Information</th></tr>
        <tr><td>User ID</td><td><?php echo $row['user_id']; ?></td></tr>
        <tr><td>Name</td><td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td></tr>
        <tr><td>Email</td><td><?php echo htmlspecialchars($row['email']); ?></td></tr>
        <tr><td>Role</td><td><?php echo htmlspecialchars($row['role']); ?></td></tr>
        <tr><td>Status</td>
            <td>
                <?php
                $sc = $row['status'] === 'active' ? 'green' : ($row['status'] === 'inactive' ? 'red' : 'yellow');
                echo "<span class='badge $sc'>" . htmlspecialchars($row['status']) . "</span>";
                ?>
            </td>
        </tr>
        <tr><td>HOA Member</td>
            <td>
                <?php echo $row['hoa_member'] == 1
                    ? "<span class='badge green'>Yes</span>"
                    : "<span class='badge yellow'>No</span>"; ?>
            </td>
        </tr>
        <tr><td>Verification</td><td><?php echo htmlspecialchars($row['verification_status'] ?? '—'); ?></td></tr>
        <tr><td>Registered</td><td><?php echo htmlspecialchars($row['created_at'] ?? '—'); ?></td></tr>
    </table>

    <br>

    <?php if ($row['hoa_member'] == 0): ?>
        <?php $safe_name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES); ?>
        <a href="declare_hoa_member.php?id=<?php echo $row['user_id']; ?>"
           class="btn"
           style="background:#16a34a; color:#fff;"
           onclick="return confirm('Declare <?php echo $safe_name; ?> as an HOA member?');">
            Declare HOA Member
        </a>
    <?php endif; ?>

    <a href="homeowners.php" class="btn" style="margin-left:8px;">Back</a>
</div>

<?php include("../includes/admin_footer.php"); ?>
