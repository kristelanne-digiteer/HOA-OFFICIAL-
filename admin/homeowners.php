<?php
include("../config/db.php");
include("../includes/admin_header.php");

$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all'; // all, hoa, non_hoa, pending

// ── Base query: HOA masterlist members ──────────────────────────────
$sql = "SELECT 
            hm.homeowner_id,
            hm.name,
            hm.full_address,
            hm.contact_no,
            hm.gender,
            hm.status,
            'hoa' AS member_type,
            NULL AS user_id
        FROM homeowners_masterlist hm";

$where = [];
if ($search != '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(hm.name LIKE '%$s%' OR hm.full_address LIKE '%$s%' OR hm.contact_no LIKE '%$s%')";
}
if ($filter === 'hoa') {
    // masterlist only — no extra filter needed
} elseif ($filter === 'pending') {
    $where[] = "hm.status = 'pending_verification'";
} elseif ($filter === 'non_hoa') {
    $sql = ""; // skip masterlist entirely for non_hoa filter
}

if ($sql !== "") {
    if (!empty($where)) $sql .= " WHERE " . implode(" AND ", $where);
}

// ── Non-HOA pending users (not in masterlist) ────────────────────────
// Also includes hoa_member=1 users who registered but have no masterlist link yet
$sql_nonhoa = "SELECT 
                    u.user_id AS homeowner_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS name,
                    '' AS full_address,
                    '' AS contact_no,
                    '' AS gender,
                    u.status,
                    'non_hoa' AS member_type,
                    u.user_id
               FROM users u
               LEFT JOIN homeowner_profiles hp ON u.user_id = hp.user_id
               WHERE hp.masterlist_id IS NULL";

$where_nonhoa = [];
if ($search != '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where_nonhoa[] = "(u.first_name LIKE '%$s%' OR u.last_name LIKE '%$s%' OR u.email LIKE '%$s%')";
}
if ($filter === 'pending') {
    $where_nonhoa[] = "u.status = 'pending'";
}
if (!empty($where_nonhoa)) {
    $sql_nonhoa .= " AND " . implode(" AND ", $where_nonhoa);
}

// ── Combine queries based on filter ─────────────────────────────────
if ($filter === 'non_hoa') {
    $final_sql = $sql_nonhoa . " ORDER BY name ASC";
} elseif ($filter === 'hoa') {
    $final_sql = $sql . " ORDER BY hm.homeowner_id ASC";
} else {
    // All: UNION both
    $final_sql = "({$sql}) UNION ALL ({$sql_nonhoa}) ORDER BY name ASC";
}

$result = mysqli_query($conn, $final_sql);
$total  = mysqli_num_rows($result);

// ── Stat counts ──────────────────────────────────────────────────────
$active_count   = mysqli_num_rows(mysqli_query($conn, "SELECT homeowner_id FROM homeowners_masterlist WHERE status='active'"));
$inactive_count = mysqli_num_rows(mysqli_query($conn, "SELECT homeowner_id FROM homeowners_masterlist WHERE status='inactive'"));

// Pending = masterlist pending_verification + non-HOA pending users
$pending_masterlist = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM homeowners_masterlist WHERE status='pending_verification'"))['c'];
$pending_users      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE hoa_member=0 AND status='pending'"))['c'];
$pending_count      = $pending_masterlist + $pending_users;
?>

<section class="page-title">
    <h1>Homeowners Management</h1>
    <p>Manage registered homeowners from the masterlist</p>
</section>

<section class="cards">
    <div class="card">
        <h3>Total Records Shown</h3>
        <div class="value"><?php echo $total; ?></div>
        <div class="note">Based on current search/filter</div>
    </div>

    <div class="card">
        <h3>Active Homeowners</h3>
        <div class="value"><?php echo $active_count; ?></div>
        <div class="note">Currently active (HOA)</div>
    </div>

    <div class="card">
        <h3>Inactive Homeowners</h3>
        <div class="value"><?php echo $inactive_count; ?></div>
        <div class="note" style="color:#ef4444;">Inactive records</div>
    </div>

    <div class="card">
        <h3>Pending Approval</h3>
        <div class="value"><?php echo $pending_count; ?></div>
        <div class="note" style="color:#eab308;">Awaiting approval</div>
    </div>
</section>

<div class="panel">
    <div class="panel-header">
        <h2>Homeowner List</h2>
        <a href="add_homeowner.php" class="btn">Add Homeowner</a>
    </div>

    <!-- Search + Filter -->
    <form method="GET" style="margin-bottom:15px; display:flex; gap:10px; flex-wrap:wrap;">
        <input
            type="text"
            name="search"
            placeholder="Search by name, address, or contact..."
            class="search"
            value="<?php echo htmlspecialchars($search); ?>"
            style="flex:1; min-width:200px;">

        <select name="filter" onchange="this.form.submit()" style="padding:8px 12px; border-radius:6px; border:1px solid #263b40; background:#101d20; color:white;">
            <option value="all"     <?php if($filter==='all')     echo 'selected'; ?>>All</option>
            <option value="hoa"     <?php if($filter==='hoa')     echo 'selected'; ?>>HOA Members</option>
            <option value="non_hoa" <?php if($filter==='non_hoa') echo 'selected'; ?>>Non-HOA</option>
            <option value="pending" <?php if($filter==='pending') echo 'selected'; ?>>Pending</option>
        </select>
    </form>

    <table class="table">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Address</th>
            <th>Contact</th>
            <th>Gender</th>
            <th>Type</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>

        <?php if ($total > 0) { ?>
            <?php while ($row = mysqli_fetch_assoc($result)) {
                $statusClass = 'yellow';
                if ($row['status'] == 'active')               $statusClass = 'green';
                elseif ($row['status'] == 'inactive')         $statusClass = 'red';
                elseif ($row['status'] == 'pending' || $row['status'] == 'pending_verification') $statusClass = 'yellow';

                $is_non_hoa = $row['member_type'] === 'non_hoa';
            ?>
                <tr>
                    <td><?php echo $row['homeowner_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['full_address']) ?: '<span style="color:#9ca3af;">—</span>'; ?></td>
                    <td><?php echo htmlspecialchars($row['contact_no']) ?: '<span style="color:#9ca3af;">—</span>'; ?></td>
                    <td><?php echo htmlspecialchars($row['gender']) ?: '<span style="color:#9ca3af;">—</span>'; ?></td>
                    <td>
                        <?php if ($is_non_hoa): ?>
                            <span class="badge yellow">Non-HOA</span>
                        <?php else: ?>
                            <span class="badge green">HOA</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                    <td class="action-buttons">
                        <?php if ($is_non_hoa): ?>
                            <!-- Non-HOA: view/approve/declare HOA/delete via user_id -->
                            <a href="view_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-view">View</a>
                            <a href="approve_user.php?id=<?php echo $row['user_id']; ?>"
                               class="btn btn-edit"
                               onclick="return confirm('Approve this user?');">Approve</a>
                            <?php $safe_name = htmlspecialchars($row['name'], ENT_QUOTES); ?>
                            <a href="declare_hoa_member.php?id=<?php echo $row['user_id']; ?>"
                               class="btn"
                               style="background:#16a34a; color:#fff;"
                               onclick="return confirm('Declare <?php echo $safe_name; ?> as an HOA member?');">Declare HOA</a>
                            <a href="delete_user.php?id=<?php echo $row['user_id']; ?>"
                               class="btn btn-delete"
                               onclick="return confirm('Delete this user?');">Delete</a>
                        <?php else: ?>
                            <!-- HOA masterlist: original actions -->
                            <a href="view_homeowner.php?id=<?php echo $row['homeowner_id']; ?>" class="btn btn-view">View</a>
                            <a href="edit_homeowner.php?id=<?php echo $row['homeowner_id']; ?>" class="btn btn-edit">Edit</a>
                            <a href="delete_homeowner.php?id=<?php echo $row['homeowner_id']; ?>"
                               class="btn btn-delete"
                               onclick="return confirm('Delete this homeowner?');">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="8" style="text-align:center; color:#9ca3af;">No homeowners found.</td>
            </tr>
        <?php } ?>
    </table>
</div>

<?php include("../includes/admin_footer.php"); ?>