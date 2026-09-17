<?php
session_start();
include("config/db.php");

if (isset($_POST['register'])) {

    $name     = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);

    
    function handle_profile_upload() {
        $profile_picture = "";
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $upload_dir = "assets/uploads/profiles/";
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $new_pic = time() . "_" . basename($_FILES['profile_picture']['name']);
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_pic)) {
                $profile_picture = $new_pic;
            }
        }
        return $profile_picture;
    }

    
    $check_homeowner = mysqli_query($conn, "
        SELECT * FROM homeowners_masterlist
        WHERE LOWER(name) = LOWER('$name')
        LIMIT 1
    ");

    $is_hoa_member = mysqli_num_rows($check_homeowner) > 0;

    if (!$is_hoa_member) {
        // ── NON-HOA PATH ─────────────────────────────────────────────
        $email_taken = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email' LIMIT 1");
        if (mysqli_num_rows($email_taken) > 0) {
            echo "<script>alert('That email is already registered. Please log in instead.');</script>";
        } else {
            $profile_picture = handle_profile_upload();

            // Split name into first/last (assume "LASTNAME, FIRSTNAME" format)
            $name_parts = explode(',', $name, 2);
            $last_name  = isset($name_parts[0]) ? trim($name_parts[0]) : $name;
            $first_name = isset($name_parts[1]) ? trim($name_parts[1]) : '';

            mysqli_query($conn, "
                INSERT INTO users
                    (first_name, last_name, email, password, role, status, hoa_member, profile_picture)
                VALUES
                    ('$first_name', '$last_name', '$email', '$password',
                     'homeowner', 'pending', 0, '$profile_picture')
            ");

            echo "<script>
                alert('Registration submitted! Your account is pending admin approval since your name was not found in the HOA Masterlist.');
                window.location='login.php';
            </script>";
            exit;
        }

    } else {
        // ── HOA MEMBER PATH ──────────────────────────────────────────
        $homeowner = mysqli_fetch_assoc($check_homeowner);

        
        $existing_user_q = mysqli_query($conn, "
            SELECT u.*
            FROM homeowner_profiles hp
            LEFT JOIN users u ON hp.user_id = u.user_id
            WHERE hp.masterlist_id = '{$homeowner['homeowner_id']}'
            LIMIT 1
        ");

        if (mysqli_num_rows($existing_user_q) > 0) {
            $existing_user = mysqli_fetch_assoc($existing_user_q);

            if (!empty($existing_user['user_id'])) {
                // 3a. May account na — i-UPDATE lang, hindi mag-iinsert ng bago
                $email_conflict = mysqli_query($conn, "
                    SELECT user_id FROM users
                    WHERE email = '$email'
                    AND user_id != '{$existing_user['user_id']}'
                    LIMIT 1
                ");

                if (mysqli_num_rows($email_conflict) > 0) {
                    echo "<script>alert('That email is already used by another account.');</script>";
                } else {
                    $profile_picture = !empty($existing_user['profile_picture'])
                        ? $existing_user['profile_picture']
                        : '';

                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                        $profile_picture = handle_profile_upload();
                    }

                    mysqli_query($conn, "
                        UPDATE users
                        SET email='$email', password='$password',
                            profile_picture='$profile_picture',
                            status='active', hoa_member=1
                        WHERE user_id='{$existing_user['user_id']}'
                    ");

                    echo "<script>alert('Account updated successfully. You can now log in.'); window.location='login.php';</script>";
                    exit;
                }

            } else {
                // 3b. Profile record exist pero walang linked user — gumawa ng bagong user
                $email_taken = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email' LIMIT 1");
                if (mysqli_num_rows($email_taken) > 0) {
                    echo "<script>alert('That email is already registered. Please log in instead.');</script>";
                } else {
                    $profile_picture = handle_profile_upload();

                    mysqli_query($conn, "
                        INSERT INTO users
                            (first_name, last_name, email, password, role, status, hoa_member, profile_picture)
                        VALUES
                            ('{$homeowner['name']}', '', '$email', '$password',
                             'homeowner', 'active', 1, '$profile_picture')
                    ");
                    $new_user_id = mysqli_insert_id($conn);

                    mysqli_query($conn, "
                        UPDATE homeowner_profiles SET user_id='$new_user_id'
                        WHERE masterlist_id='{$homeowner['homeowner_id']}'
                    ");

                    echo "<script>alert('Registration successful. You can now log in.'); window.location='login.php';</script>";
                    exit;
                }
            }

        } else {
            // 3c. Wala pang profile record — fresh register
            $email_taken = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email' LIMIT 1");
            if (mysqli_num_rows($email_taken) > 0) {
                echo "<script>alert('That email is already registered. Please log in instead.');</script>";
            } else {
                $profile_picture = handle_profile_upload();

                mysqli_query($conn, "
                    INSERT INTO users
                        (first_name, last_name, email, password, role, status, hoa_member, profile_picture)
                    VALUES
                        ('{$homeowner['name']}', '', '$email', '$password',
                         'homeowner', 'active', 1, '$profile_picture')
                ");
                $new_user_id = mysqli_insert_id($conn);

                mysqli_query($conn, "
                    INSERT INTO homeowner_profiles (user_id, masterlist_id)
                    VALUES ('$new_user_id', '{$homeowner['homeowner_id']}')
                ");

                echo "<script>alert('Registration successful. You can now log in.'); window.location='login.php';</script>";
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Register - Sapilara Lower Portal</title>
<style>
* { box-sizing: border-box; font-family: Arial, sans-serif; }
body { margin: 0; background: #061514; color: white; min-height: 100vh; }
.auth-page { display: grid; grid-template-columns: 1.2fr .9fr; min-height: 100vh; }
.left-panel {
    background: linear-gradient(rgba(5,13,15,.55), rgba(5,13,15,.75)), url("assets/css/uploads/login_bg.png");
    background-size: cover; background-position: center; padding: 35px 45px;
}
.brand { display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 20px; }
.brand img { width: 55px; height: 55px; border-radius: 50%; }
.hero { margin-top: 90px; max-width: 500px; }
.hero h1 { font-size: 34px; }
.hero span { color: #ef2b35; }
.right-panel { display: flex; align-items: center; justify-content: center; padding: 40px; }
.register-box { width: 450px; background: #0f1f22; border: 1px solid #263b40; border-radius: 15px; padding: 35px; }
.register-box h2 { margin-top: 0; }
.register-box > p { color: #9ca3af; margin-bottom: 15px; }
.note { font-size: 12px; color: #9ca3af; background: #0a1a1d; border-left: 3px solid #dc1623; padding: 8px 12px; border-radius: 4px; margin-bottom: 10px; }
label { display: block; margin-top: 15px; margin-bottom: 8px; font-weight: bold; }
input { width: 100%; padding: 14px; border-radius: 8px; border: 1px solid #263b40; background: #101d20; color: white; }
input:focus { border-color: #dc1623; outline: none; }
button { width: 100%; margin-top: 25px; padding: 14px; background: #dc1623; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
button:hover { background: #b50d18; }
.bottom { text-align: center; margin-top: 20px; color: #9ca3af; }
.bottom a { color: #ef2b35; text-decoration: none; }
@media(max-width: 900px) { .auth-page { grid-template-columns: 1fr; } .left-panel { display: none; } }
</style>
</head>
<body>
<div class="auth-page">
    <div class="left-panel">
        <div class="brand">
            <img src="assets/css/uploads/logo.png">
            <div>SAPILARA<br>LOWER PORTAL</div>
        </div>
        <div class="hero">
            <h1>Join Our<br><span>Community Portal</span></h1>
            <p>Create your homeowner account and access HOA services, payments, rental requests, document requests, events, and announcements.</p>
        </div>
    </div>
    <div class="right-panel">
        <div class="register-box">
            <h2>Create Account</h2>
            <p>Register using your name from the HOA Masterlist, or sign up as a non-HOA member.</p>
            <div class="note">
                💡 <strong>HOA Members:</strong> Gamitin ang exact na pangalan sa masterlist para ma-verify ang iyong account agad.<br><br>
                🕐 <strong>Hindi HOA Member?</strong> Pwede ka pa ring mag-register — ang iyong account ay mapapailalim sa admin approval bago ma-activate.
            </div>
            <form method="POST" enctype="multipart/form-data">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Example: BETANCOR, GINA PARAS" required>
                <label>Email Address</label>
                <input type="email" name="email" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <label>Profile Picture (optional)</label>
                <input type="file" name="profile_picture" accept="image/*">
                <button type="submit" name="register">Create / Update Account</button>
            </form>
            <div class="bottom">Already have an account? <a href="login.php">Log In</a></div>
        </div>
    </div>
</div>
</body>
</html>