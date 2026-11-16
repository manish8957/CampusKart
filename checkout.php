<?php
// checkout.php - Campus Pickup Address & Order Summary
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Phase 3: Restrict checkout to logged-in users
require_login();

$user_id = current_user_id();

// Handle direct "Buy Now" bypass into cart
if (isset($_GET['buy_now']) && is_numeric($_GET['buy_now'])) {
    $buy_prod_id = (int)$_GET['buy_now'];
    // Check if in cart already
    $check_c = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
    $check_c->bind_param("ii", $user_id, $buy_prod_id);
    $check_c->execute();
    if ($check_c->get_result()->num_rows === 0) {
        $ins_c = $conn->prepare("INSERT INTO cart (user_id, product_id, qty) VALUES (?, ?, 1)");
        $ins_c->bind_param("ii", $user_id, $buy_prod_id);
        $ins_c->execute();
        $ins_c->close();
    }
    $check_c->close();
}

// Fetch user profile info
$user_stmt = $conn->prepare("SELECT name, email, phone, college, hostel FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Fetch cart items
$cart_query = $conn->prepare("
    SELECT c.id AS cart_id, c.qty, p.id AS product_id, p.title, p.price, p.image, p.condition,
           u.name AS seller_name, u.phone AS seller_phone
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN users u ON p.seller_id = u.id
    WHERE c.user_id = ?
");
$cart_query->bind_param("i", $user_id);
$cart_query->execute();
$cart_res = $cart_query->get_result();

$items = [];
$subtotal = 0.00;
while ($row = $cart_res->fetch_assoc()) {
    $row['line_total'] = $row['price'] * $row['qty'];
    $subtotal += $row['line_total'];
    $items[] = $row;
}
$cart_query->close();

if (empty($items)) {
    set_flash('warning', 'Your cart is empty. Please add items before checking out.');
    header("Location: products.php");
    exit;
}

$campus_fee = 0.00;
$total_amount = $subtotal + $campus_fee;

$errors = [];
$name = $user_info['name'] ?? '';
$phone = $user_info['phone'] ?? '';
$address = !empty($user_info['hostel']) ? ($user_info['hostel'] . ', ' . $user_info['college']) : $user_info['college'];
$payment_method = 'Cash on Campus Pickup';
$notes = '';

// Handle Checkout Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'Cash on Campus Pickup');

    // Server-side validation
    if (empty($name)) {
        $errors[] = 'Recipient student name is required.';
    }

    if (empty($phone)) {
        $errors[] = 'Contact phone number is required.';
    } elseif (!preg_match('/^[0-9]{10,15}$/', preg_replace('/[\s\-\+]/', '', $phone))) {
        $errors[] = 'Please enter a valid mobile number for campus handover.';
    }

    if (empty($address)) {
        $errors[] = 'Campus pickup address / hostel room is required.';
    } elseif (strlen($address) < 5) {
        $errors[] = 'Please provide a clear campus location (Hostel, Room, or Campus Department).';
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Generate unique Order Code
            $order_code = 'CK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            // Combine address and notes for complete handover details
            $full_address = $address;
            if (!empty($notes)) {
                $full_address .= " (Notes: " . $notes . ")";
            }

            // 1. Insert into orders table (Prepared Statement)
            $order_stmt = $conn->prepare("
                INSERT INTO orders (order_code, user_id, total, address, phone, payment_method, date, status)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Pending Pickup')
            ");
            $order_stmt->bind_param("sidsss", $order_code, $user_id, $total_amount, $full_address, $phone, $payment_method);
            $order_stmt->execute();
            $order_id = $order_stmt->insert_id;
            $order_stmt->close();

            // 2. Insert into order_items table
            $item_stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, title, price, qty)
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($items as $item) {
                $item_stmt->bind_param("iisdi", $order_id, $item['product_id'], $item['title'], $item['price'], $item['qty']);
                $item_stmt->execute();
            }
            $item_stmt->close();

            // 3. Clear user's cart
            $clear_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $clear_cart->bind_param("i", $user_id);
            $clear_cart->execute();
            $clear_cart->close();

            $conn->commit();

            set_flash('success', 'Order #' . $order_code . ' placed successfully! The seller has been notified for campus pickup.');
            header("Location: order-confirmation.php?order_id=" . $order_id);
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Order processing failed: ' . $e->getMessage();
        }
    }
}

$page_title = 'Checkout & Campus Pickup';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="cart.php" class="text-decoration-none">Cart</a></li>
            <li class="breadcrumb-item active" aria-current="page">Checkout</li>
        </ol>
    </nav>

    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1"><i class="bi bi-shield-check text-success me-2"></i>Campus Pickup Checkout</h1>
        <p class="text-muted small mb-0">Enter your campus pickup details to arrange your hand-to-hand exchange.</p>
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

    <div class="row g-4">
        <!-- Left: Address & Pickup Form -->
        <div class="col-lg-7">
            <div class="bg-white rounded-4 border p-4 shadow-sm mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Campus Delivery & Handover Location</h5>
                
                <form action="checkout.php" method="POST" novalidate>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= e($name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Phone / WhatsApp <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" value="<?= e($phone) ?>" placeholder="10-digit number" required>
                            <small class="text-muted" style="font-size: 0.75rem;">Seller will coordinate pickup over this number.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Hostel, Room No. or Campus Spot <span class="text-danger">*</span></label>
                        <textarea name="address" rows="3" class="form-control" placeholder="e.g. Aryabhata Hostel Room 304, or Meet at Central Library Ground Floor" required><?= e($address) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Preferred Meeting Time / Special Notes (Optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Available after 5 PM post lectures, or call before coming" value="<?= e($notes) ?>">
                    </div>

                    <h5 class="fw-bold mb-3 pt-3 border-top"><i class="bi bi-wallet2 text-warning me-2"></i>Payment Preference</h5>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-check p-3 border rounded-3 bg-light">
                                <input class="form-check-input" type="radio" name="payment_method" id="payCash" value="Cash on Campus Pickup" checked>
                                <label class="form-check-label fw-semibold" for="payCash">
                                    <i class="bi bi-cash me-1 text-success"></i> Cash on Pickup
                                    <small class="d-block text-muted fw-normal">Inspect the item first, then pay physical cash.</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check p-3 border rounded-3 bg-light">
                                <input class="form-check-input" type="radio" name="payment_method" id="payUPI" value="UPI / QR on Pickup">
                                <label class="form-check-label fw-semibold" for="payUPI">
                                    <i class="bi bi-qr-code-scan me-1 text-primary"></i> UPI / QR on Pickup
                                    <small class="d-block text-muted fw-normal">Scan seller's GPay/PhonePe upon meeting.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-campus btn-lg w-100 py-3 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Confirm & Place Campus Order
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Order Items Summary -->
        <div class="col-lg-5">
            <div class="summary-card">
                <h5 class="fw-bold mb-3 pb-2 border-bottom">Items in Order (<?= count($items) ?>)</h5>
                
                <div class="d-flex flex-column gap-3 mb-4" style="max-height: 320px; overflow-y: auto;">
                    <?php foreach ($items as $item): ?>
                        <div class="d-flex align-items-center gap-3 pb-2 border-bottom">
                            <img src="<?= product_img($item['image']) ?>" alt="<?= e($item['title']) ?>" class="rounded" style="width: 55px; height: 55px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark small text-truncate" style="max-width: 220px;">
                                    <?= e($item['title']) ?>
                                </div>
                                <div class="small text-muted">
                                    Qty: <?= (int)$item['qty'] ?> × <?= format_price($item['price']) ?>
                                </div>
                                <div>
                                    <?= condition_badge($item['condition']) ?>
                                </div>
                            </div>
                            <div class="fw-bold text-dark">
                                <?= format_price($item['line_total']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span class="fw-semibold text-dark"><?= format_price($subtotal) ?></span>
                </div>

                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                    <span class="text-muted">Campus Delivery</span>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success">FREE</span>
                </div>

                <div class="d-flex justify-content-between align-items-baseline mb-4">
                    <span class="h5 fw-bold text-dark">Total Amount:</span>
                    <span class="h3 fw-bold text-primary"><?= format_price($total_amount) ?></span>
                </div>

                <div class="p-3 bg-light rounded-3 border small">
                    <div class="fw-bold text-dark mb-1"><i class="bi bi-shield-fill-check text-success me-1"></i> Zero Risk Policy</div>
                    <span class="text-muted">
                        No online upfront payment required. You only pay after you hold the book in your hand and verify pages are intact.
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

