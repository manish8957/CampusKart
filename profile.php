<?php
// profile.php - Student Profile & Account Settings (Zero JS - Pure PHP/HTML)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user_id = current_user_id();

$errors = [];
$success_msg = '';

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $college = trim($_POST['college'] ?? '');
        $hostel = trim($_POST['hostel'] ?? '');

        if (empty($name)) {
            $errors[] = 'Full name cannot be empty.';
        }
        if (empty($phone)) {
            $errors[] = 'Phone number is required for campus pickups.';
        } elseif (!preg_match('/^[0-9]{10,15}$/', preg_replace('/[\s\-\+]/', '', $phone))) {
            $errors[] = 'Please enter a valid mobile number.';
        }

        if (empty($errors)) {
            $upd = $conn->prepare("UPDATE users SET name = ?, phone = ?, college = ?, hostel = ? WHERE id = ?");
            $upd->bind_param("ssssi", $name, $phone, $college, $hostel, $user_id);
            if ($upd->execute()) {
                $_SESSION['user_name'] = $name;
                set_flash('success', 'Profile details updated successfully.');
            } else {
                $errors[] = 'Could not update profile details.';
            }
            $upd->close();
            header("Location: profile.php");
            exit;
        }
    } elseif ($action === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($current_pass) || empty($new_pass)) {
            $errors[] = 'Both current and new passwords are required.';
        } elseif (strlen($new_pass) < 6) {
            $errors[] = 'New password must be at least 6 characters long.';
        } elseif ($new_pass !== $confirm_pass) {
            $errors[] = 'New password confirmation does not match.';
        } else {
            // Verify current password
            $chk = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $chk->bind_param("i", $user_id);
            $chk->execute();
            $curr_hash = $chk->get_result()->fetch_assoc()['password'];
            $chk->close();

            if (!password_verify($current_pass, $curr_hash)) {
                $errors[] = 'Your current password was entered incorrectly.';
            } else {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $upd_p = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd_p->bind_param("si", $new_hash, $user_id);
                $upd_p->execute();
                $upd_p->close();
                set_flash('success', 'Your password has been changed securely.');
                header("Location: profile.php");
                exit;
            }
        }
    }
}

// Fetch user profile data
$stmt = $conn->prepare("SELECT name, email, phone, college, hostel, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$page_title = 'My Profile Settings';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Profile Settings</li>
        </ol>
    </nav>

    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1"><i class="bi bi-person-gear text-primary me-2"></i>Student Profile Settings</h1>
        <p class="text-muted small mb-0">Manage your contact details, campus pickup location, and login credentials.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Profile Info Card -->
        <div class="col-lg-7">
            <div class="bg-white rounded-4 border p-4 shadow-sm mb-4">
                <h5 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="bi bi-person-lines-fill text-primary me-2"></i>Campus Contact Details
                </h5>

                <form action="profile.php" method="POST">
                    <input type="hidden" name="action" value="update_info">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= e($user_data['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">College Email (Read-Only)</label>
                            <input type="email" class="form-control bg-light" value="<?= e($user_data['email']) ?>" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contact Phone / WhatsApp</label>
                        <input type="tel" name="phone" class="form-control" value="<?= e($user_data['phone']) ?>" required>
                        <small class="text-muted" style="font-size: 0.75rem;">Used by student buyers and sellers to coordinate in-person campus exchanges.</small>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">College / Campus</label>
                            <input type="text" name="college" class="form-control" value="<?= e($user_data['college']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hostel / Campus Spot</label>
                            <input type="text" name="hostel" class="form-control" value="<?= e($user_data['hostel']) ?>" placeholder="e.g. Block A Room 204">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-campus fw-bold px-4">
                        <i class="bi bi-check-circle me-1"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password & Account Overview -->
        <div class="col-lg-5">
            <!-- Account Overview Pill -->
            <div class="bg-white rounded-4 border p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-check text-success me-1"></i> Student Verification</h6>
                <div class="small text-muted mb-2">Member since <?= date('F Y', strtotime($user_data['created_at'])) ?></div>
                <div class="d-flex gap-2">
                    <a href="my-listings.php" class="btn btn-sm btn-outline-warning">My Listings</a>
                    <a href="orders.php" class="btn btn-sm btn-outline-success">My Orders</a>
                    <a href="seller-orders.php" class="btn btn-sm btn-outline-primary">Handover Requests</a>
                </div>
            </div>

            <!-- Password Card -->
            <div class="bg-white rounded-4 border p-4 shadow-sm">
                <h5 class="fw-bold mb-3 border-bottom pb-2">
                    <i class="bi bi-key-fill text-warning me-2"></i>Change Password
                </h5>

                <form action="profile.php" method="POST">
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password (Min 6 chars)</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Re-type new password" required>
                    </div>

                    <button type="submit" class="btn btn-campus-yellow w-100 fw-bold">
                        <i class="bi bi-shield-lock me-1"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

