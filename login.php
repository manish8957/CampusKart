<?php
// login.php - Student Login with Sessions & Prepared Statements
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email format.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    // Verify user credentials with Prepared Statements and password_verify
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Regenerate session id to protect against session fixation attacks
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];

                // Auto-save pending wishlist items if guest clicked wishlist before logging in
                if (!empty($_SESSION['pending_wishlist_product_id'])) {
                    $pending_p_id = (int)$_SESSION['pending_wishlist_product_id'];
                    $ins_w = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
                    $ins_w->bind_param("ii", $user['id'], $pending_p_id);
                    $ins_w->execute();
                    $ins_w->close();
                    unset($_SESSION['pending_wishlist_product_id']);
                }
                if (!empty($_SESSION['pending_wishlist_sneaker_id'])) {
                    $pending_s_id = (int)$_SESSION['pending_wishlist_sneaker_id'];
                    $ins_sw = $conn->prepare("INSERT IGNORE INTO sneaker_wishlist (user_id, sneaker_id) VALUES (?, ?)");
                    $ins_sw->bind_param("ii", $user['id'], $pending_s_id);
                    $ins_sw->execute();
                    $ins_sw->close();
                    unset($_SESSION['pending_wishlist_sneaker_id']);
                }

                set_flash('success', 'Welcome back, ' . $user['name'] . '!');

                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $redirect);
                exit;
            } else {
                $errors[] = 'Invalid email or password combination.';
            }
        } else {
            $errors[] = 'No student account found with this email.';
        }
        $stmt->close();
    }
}

$page_title = 'Student Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="form-card shadow-sm">
                <div class="text-center mb-4">
                    <span class="brand-badge mb-2"><i class="bi bi-box-arrow-in-right"></i></span>
                    <h2 class="fw-bold">Student Sign In</h2>
                    <p class="text-muted small">Sign in to manage your cart, orders & book listings</p>
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

                <form action="login.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">College / Personal Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="name@campus.edu" value="<?= e($email) ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label mb-0">Password</label>
                            <a href="contact.php" class="small text-muted text-decoration-none">Forgot password?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="rememberMe" checked>
                        <label class="form-check-label small text-muted" for="rememberMe">
                            Remember this browser session
                        </label>
                    </div>

                    <button type="submit" class="btn btn-campus w-100 py-2 fs-6 fw-bold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to CampusKart
                    </button>
                </form>

                <!-- Demo Credentials Box for Evaluation -->
                <div class="mt-4 p-3 bg-light rounded-3 border text-start">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-bold text-dark"><i class="bi bi-info-circle text-primary me-1"></i> Demo Student Accounts:</span>
                        <span class="badge bg-secondary">Pre-seeded</span>
                    </div>
                    <div class="small text-muted">
                        <div><strong>Email:</strong> <code>aarav.sharma@campus.edu</code></div>
                        <div><strong>Password:</strong> <code>password123</code></div>
                        <div class="mt-2 pt-2 border-top">
                            <form action="login.php" method="POST" class="d-inline">
                                <input type="hidden" name="email" value="aarav.sharma@campus.edu">
                                <input type="hidden" name="password" value="password123">
                                <button type="submit" class="btn btn-outline-primary btn-sm py-1 px-3">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> 1-Click Demo Login (Aarav)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="small text-muted mb-0">New to CampusKart? <a href="register.php" class="fw-bold text-primary text-decoration-none">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

