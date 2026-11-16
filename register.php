<?php
// register.php - Student Registration with Server-Side Validation & Password Hashing
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$errors = [];
$name = '';
$email = '';
$phone = '';
$college = 'Apex Engineering College';
$hostel = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $college = trim($_POST['college'] ?? '');
    $hostel = trim($_POST['hostel'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Validation: Empty fields
    if (empty($name)) {
        $errors[] = 'Full name is required.';
    } elseif (strlen($name) < 3) {
        $errors[] = 'Full name must be at least 3 characters long.';
    }

    // 2. Validation: Email format
    if (empty($email)) {
        $errors[] = 'College/personal email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address (e.g., student@campus.edu).';
    }

    // 3. Validation: Phone number
    if (empty($phone)) {
        $errors[] = 'Contact phone number is required.';
    } elseif (!preg_match('/^[0-9]{10,15}$/', preg_replace('/[\s\-\+]/', '', $phone))) {
        $errors[] = 'Please enter a valid 10-digit mobile number.';
    }

    // 4. Validation: Password length and confirmation
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Password confirmation does not match.';
    }

    // 5. Check if email already registered (Prepared Statement)
    if (empty($errors)) {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $errors[] = 'An account with this email address already exists. Please log in.';
        }
        $check_stmt->close();
    }

    // 6. Insert new user with secure password hash
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_stmt = $conn->prepare("
            INSERT INTO users (name, email, password, phone, college, hostel)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insert_stmt->bind_param("ssssss", $name, $email, $hashed_password, $phone, $college, $hostel);
        
        if ($insert_stmt->execute()) {
            $new_user_id = $insert_stmt->insert_id;
            $insert_stmt->close();

            // Set session variables
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            set_flash('success', 'Welcome to CampusKart, ' . $name . '! Your student account was created successfully.');
            
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: " . $redirect);
            exit;
        } else {
            $errors[] = 'Registration failed due to a database error. Please try again.';
            $insert_stmt->close();
        }
    }
}

$page_title = 'Create Student Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="form-card shadow-sm">
                <div class="text-center mb-4">
                    <span class="brand-badge mb-2"><i class="bi bi-mortarboard-fill"></i></span>
                    <h2 class="fw-bold">Join CampusKart</h2>
                    <p class="text-muted small">Buy and sell second-hand college books & tech directly with peers</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Rahul Sharma" value="<?= e($name) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="student@campus.edu or gmail" value="<?= e($email) ?>" required>
                        </div>
                        <small class="text-muted" style="font-size: 0.78rem;">Use your college email or personal email for order updates.</small>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number (For In-Person Handover) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="10-digit mobile number" value="<?= e($phone) ?>" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="college" class="form-label">College / Institute</label>
                            <input type="text" class="form-control" id="college" name="college" placeholder="e.g. Apex Tech Campus" value="<?= e($college) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="hostel" class="form-label">Hostel / Campus Area</label>
                            <input type="text" class="form-control" id="hostel" name="hostel" placeholder="e.g. Hostel B, Room 104" value="<?= e($hostel) ?>">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="At least 6 characters" required>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required checked>
                        <label class="form-check-label small text-muted" for="termsCheck">
                            I agree to fair campus trading guidelines & verified handovers.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-campus w-100 py-2 fs-6 fw-bold">
                        <i class="bi bi-person-check-fill me-1"></i> Register Student Account
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="small text-muted mb-0">Already registered? <a href="login.php" class="fw-bold text-primary text-decoration-none">Log in here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

