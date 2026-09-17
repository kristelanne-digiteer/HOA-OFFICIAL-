<?php
include("../config/db.php");
include("../includes/admin_header.php");

// ── HANDLE REMOVE (POST) ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    $del_id = (int) $_POST['remove_id'];
    if ($del_id > 0) {
        // Step 1: I-delete muna ang nakataling profile para hindi mag-error ang foreign key constraint
        mysqli_query($conn, "DELETE FROM homeowner_profiles WHERE user_id='$del_id'");
        
        // Step 2: Ligtas na i-delete ang mismong user account
        mysqli_query($conn, "DELETE FROM users WHERE user_id='$del_id'");
        
        echo "<script>alert('User and profile removed successfully.'); window.location='users.php';</script>";
        exit;
    }
}

// ── FETCH all users ───────────────────────────────────────────────────
$result = mysqli_query($conn, "SELECT * FROM users ORDER BY user_id ASC");
$total  = mysqli_num_rows($result);
?>

<section class="page-title">
    <h1>User Access Management</h1>
    <p>Manage admin and homeowner accounts</p>
</section>

<section class="cards">
    <div class="card">
        <h3>Total Users</h3>
        <div class="value"><?php echo $total; ?></div>
        <div class="note">System accounts</div>
    </div>

    <div class="card">
        <h3>Roles</h3>
        <div class="value">2</div>
        <div class="note">Admin, User</div>
    </div>
</section>

<div id="viewUserModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#101d20; border:1px solid #263b40; border-radius:12px; padding:30px; width:480px; max-width:95vw; position:relative;">
        <button onclick="closeModal('viewUserModal')" style="position:absolute; top:12px; right:16px; background:none; border:none; color:#9ca3af; font-size:22px; cursor:pointer;">&times;</button>
        <h2 style="margin:0 0 20px; color:white;">User Details</h2>
        <table class="table">
            <tr><td><strong>ID</strong></td><td id="vUserId"></td></tr>
            <tr><td><strong>Name</strong></td><td id="vUserName"></td></tr>
            <tr><td><strong>Email</strong></td><td id="vUserEmail"></td></tr>
            <tr><td><strong>Role</strong></td><td id="vUserRole"></td></tr>
            <tr><td><strong>Status</strong></td><td id="vUserStatus"></td></tr>
            <tr><td><strong>HOA Member</strong></td><td id="vUserHoa"></td></tr>
            <tr><td><strong>Verification</strong></td><td id="vUserVerification"></td></tr>
        </table>
        <br>
        <button onclick="closeModal('viewUserModal')" class="btn">Close</button>
    </div>
</div>

<div id="editUserModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#101d20; border:1px solid #263b40; border-radius:12px; padding:30px; width:480px; max-width:95vw; position:relative;">
        <button onclick="closeModal('editUserModal')" style="position:absolute; top:12px; right:16px; background:none; border:none; color:#9ca3af; font-size:22px; cursor:pointer;">&times;</button>
        <h2 style="margin:0 0 20px; color:white;">Edit User</h2>
        <form method="POST" action="edit_user_inline.php">
            <input type="hidden" name="user_id" id="editUserId">

            <label>First Name</label>
            <input type="text" name="first_name" id="editFirstName" class="form-input" required style="margin-bottom:12px;">

            <label>Last Name</label>
            <input type="text" name="last_name" id="editLastName" class="form-input" required style="margin-bottom:12px;">

            <label>Email</label>
            <input type="email" name="email" id="editEmail" class="form-input" required style="margin-bottom:12px;">

            <label>Role</label>
            <select name="role" id="editRole" class="form-input" style="margin-bottom:12px;">
                <option value="admin">Admin</option>
                <option value="homeowner">User</option>
            </select>

            <label>Status</label>
            <select name="status" id="editStatus" class="form-input" style="margin-bottom:20px;">
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="inactive">Inactive</option>
            </select>

            <button type="submit" class="btn">Save Changes</button>
            <button type="button" onclick="closeModal('editUserModal')" class="btn btn-delete" style="margin-left:8px;">Cancel</button>
        </form>
    </div>
</div>

<div id="removeUserModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#101d20; border:1px solid #263b40; border-radius:12px; padding:30px; width:400px; max-width:95vw; position:relative; text-align:center;">
        <h2 style="margin:0 0 12px; color:white;">Remove User</h2>
        <p id="removeUserMsg" style="color:#9ca3af; margin-bottom:24px;"></p>
        <form method="POST" action="users.php">
            <input type="hidden" name="remove_id" id="removeUserId">
            <button type="submit" class="btn btn-delete" style="margin-right:8px;">Yes, Remove</button>
            <button type="button" onclick="closeModal('removeUserModal')" class="btn">Cancel</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>System Users</h2>
    </div>

    <table class="table">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>HOA Member</th>
            <th>Verification</th>
            <th>Actions</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($result)) {
            // Role display: map homeowner -> User, hide staff as User too
            $roleLabel = $row['role'] === 'admin' ? 'Admin' : 'User';
            $roleClass = $row['role'] === 'admin' ? 'red' : 'yellow';
            $statusClass = $row['status'] === 'active' ? 'green' : ($row['status'] === 'inactive' ? 'red' : 'yellow');
        ?>
            <tr>
                <td><?php echo $row['user_id']; ?></td>
                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><span class="badge <?php echo $roleClass; ?>"><?php echo $roleLabel; ?></span></td>
                <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                <td>
                    <?php if ($row['hoa_member'] == 1): ?>
                        <span class="badge green">Yes</span>
                    <?php else: ?>
                        <span class="badge yellow">No</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($row['verification_status'] ?? '—'); ?></td>
                <td class="action-buttons">
                    <button class="btn btn-view"
                        onclick="openView(
                            '<?php echo $row['user_id']; ?>',
                            '<?php echo htmlspecialchars(addslashes($row['first_name'] . ' ' . $row['last_name'])); ?>',
                            '<?php echo htmlspecialchars(addslashes($row['email'])); ?>',
                            '<?php echo $roleLabel; ?>',
                            '<?php echo htmlspecialchars(addslashes($row['status'])); ?>',
                            '<?php echo $row['hoa_member'] == 1 ? 'Yes' : 'No'; ?>',
                            '<?php echo htmlspecialchars(addslashes($row['verification_status'] ?? '')); ?>'
                        )">View</button>

                    <button class="btn btn-edit"
                        onclick="openEdit(
                            '<?php echo $row['user_id']; ?>',
                            '<?php echo htmlspecialchars(addslashes($row['first_name'])); ?>',
                            '<?php echo htmlspecialchars(addslashes($row['last_name'])); ?>',
                            '<?php echo htmlspecialchars(addslashes($row['email'])); ?>',
                            '<?php echo htmlspecialchars(addslashes($row['role'])); ?>',
                            '<?php echo htmlspecialchars(addslashes($row['status'])); ?>'
                        )">Edit</button>

                    <button class="btn btn-delete"
                        onclick="openRemove(
                            '<?php echo $row['user_id']; ?>',
                            '<?php echo htmlspecialchars(addslashes($row['first_name'] . ' ' . $row['last_name'])); ?>'
                        )">Remove</button>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>

<script>
function openView(id, name, email, role, status, hoa, verif) {
    document.getElementById('vUserId').textContent           = id;
    document.getElementById('vUserName').textContent         = name;
    document.getElementById('vUserEmail').textContent        = email;
    document.getElementById('vUserRole').textContent         = role;
    document.getElementById('vUserStatus').textContent       = status;
    document.getElementById('vUserHoa').textContent          = hoa;
    document.getElementById('vUserVerification').textContent = verif || '—';
    document.getElementById('viewUserModal').style.display   = 'flex';
}

function openEdit(id, firstName, lastName, email, role, status) {
    document.getElementById('editUserId').value    = id;
    document.getElementById('editFirstName').value = firstName;
    document.getElementById('editLastName').value  = lastName;
    document.getElementById('editEmail').value     = email;
    document.getElementById('editRole').value      = role;
    document.getElementById('editStatus').value    = status;
    document.getElementById('editUserModal').style.display = 'flex';
}

function openRemove(id, name) {
    document.getElementById('removeUserId').value   = id;
    document.getElementById('removeUserMsg').textContent = 'Are you sure you want to remove ' + name + '? This cannot be undone.';
    document.getElementById('removeUserModal').style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// Close modal on backdrop click
document.querySelectorAll('[id$="Modal"]').forEach(function(modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal(modal.id);
    });
});
</script>

<?php include("../includes/admin_footer.php"); ?>