<?php
// sneaker-checkout.php - Sneaker Order Handover & Checkout (Zero JavaScript)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user_id = current_user_id();

// Handle direct Buy Now
if (isset($_GET['buy_now']) && is_numeric($_GET['buy_now'])) {
    $buy_snk_id = (int)$_GET['buy_now'];
    $chk_c = $conn->prepare("SELECT id FROM sneaker_cart WHERE user_id = ? AND sneaker_id = ?");
    $chk_c->bind_param("ii", $user_id, $buy_snk_id);
    $chk_c->execute();
    if ($chk_c->get_result()->num_rows === 0) {
        $ins = $conn->prepare("INSERT INTO sneaker_cart (user_id, sneaker_id, qty) VALUES (?, ?, 1)");
        $ins->bind_param("ii", $user_id, $buy_snk_id);
        $ins->execute();
        $ins->close();
    }
    $chk_c->close();
}

// Fetch user info
$user_stmt = $conn->prepare("SELECT name, email, phone, college, hostel FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Fetch sneaker cart items
$stmt = $conn->prepare("
    SELECT sc.qty, s.id AS sneaker_id, s.title, s.brand, s.size, s.colour, s.price, s.image
    FROM sneaker_cart sc
    JOIN sneakers s ON sc.sneaker_id = s.id
    WHERE sc.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_res = $stmt->get_result();

$items = [];
$subtotal = 0.00;
while ($row = $cart_res->fetch_assoc()) {
    $row['line_total'] = $row['price'] * $row['qty'];
    $subtotal += $row['line_total'];
    $items[] = $row;
}
$stmt->close();

if (empty($items)) {
    set_flash('warning', 'Your sneaker cart is empty.');
    header("Location: sneakers.php");
    exit;
}

$total_amount = $subtotal;
$errors = [];

$name = $user_info['name'] ?? '';
$phone = $user_info['phone'] ?? '';
$address = !empty($user_info['hostel']) ? ($user_info['hostel'] . ', ' . $user_info['college']) : $user_info['college'];
$payment_method = 'Cash on Campus Pickup';
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'Cash on Campus Pickup');

    if (empty($name)) {
        $errors[] = 'Full name is required.';
    }
    if (empty($phone)) {
        $errors[] = 'Phone number is required for coordinating handover.';
    }
    if (empty($address)) {
        $errors[] = 'Campus meetup / hostel room is required.';
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $order_code = 'SNK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
            $full_address = $address;
            if (!empty($notes)) {
                $full_address .= " (Notes: " . $notes . ")";
            }

            // Insert into sneaker_orders
            $ins_ord = $conn->prepare("
                INSERT INTO sneaker_orders (order_code, user_id, total, address, phone, payment_method, date, status)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Pending Pickup')
            ");
            $ins_ord->bind_param("sidsss", $order_code, $user_id, $total_amount, $full_address, $phone, $payment_method);
            $ins_ord->execute();
            $order_id = $ins_ord->insert_id;
            $ins_ord->close();

            // Insert into sneaker_order_items
            $ins_item = $conn->prepare("
                INSERT INTO sneaker_order_items (order_id, sneaker_id, title, brand, size, colour, price, qty)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($items as $item) {
                $ins_item->bind_param("iissssdi", $order_id, $item['sneaker_id'], $item['title'], $item['brand'], $item['size'], $item['colour'], $item['price'], $item['qty']);
                $ins_item->execute();
            }
            $ins_item->close();

            // Clear sneaker cart
            $clr = $conn->prepare("DELETE FROM sneaker_cart WHERE user_id = ?");
            $clr->bind_param("i", $user_id);
            $clr->execute();
            $clr->close();

            $conn->commit();
            set_flash('success', 'Sneaker order #' . $order_code . ' placed successfully! The seller has been notified for campus try-on.');
            header("Location: sneaker-order-confirmation.php?order_id=" . $order_id);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Order processing failed: ' . $e->getMessage();
        }
    }
}

$page_title = 'Sneaker Checkout';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="sneakers.php" class="text-decoration-none">Sneaker Store</a></li>
            <li class="breadcrumb-item"><a href="sneaker-cart.php" class="text-decoration-none">Sneaker Cart</a></li>
            <li class="breadcrumb-item active" aria-current="page">Sneaker Checkout</li>
        </ol>
    </nav>

    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1"><i class="bi bi-tag-fill text-danger me-2"></i>Campus Sneaker Handover Checkout</h1>
        <p class="text-muted small mb-0">Confirm your campus meetup point for inspecting and trying on the sneakers.</p>
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
        <!-- Left: Form -->
        <div class="col-lg-7">
            <div class="bg-white rounded-4 border p-4 shadow-sm mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Campus Meetup & Handover Details</h5>

                <form action="sneaker-checkout.php" method="POST">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= e($name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Phone / WhatsApp</label>
                            <input type="tel" name="phone" class="form-control" value="<?= e($phone) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Campus Handover Location (Hostel Room / Department / Canteen)</label>
                        <textarea name="address" rows="3" class="form-control" required><?= e($address) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Preferred Time (Optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Meet after 4 PM at library stairs" value="<?= e($notes) ?>">
                    </div>

                    <h5 class="fw-bold mb-3 pt-3 border-top"><i class="bi bi-wallet2 text-warning me-2"></i>Payment Mode</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-check p-3 border rounded-3 bg-light">
                                <input class="form-check-input" type="radio" name="payment_method" id="payCash" value="Cash on Campus Pickup" checked>
                                <label class="form-check-label fw-semibold" for="payCash">
                                    <i class="bi bi-cash me-1 text-success"></i> Cash on Try-On
                                    <small class="d-block text-muted fw-normal">Inspect sneakers first, then pay cash.</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check p-3 border rounded-3 bg-light">
                                <input class="form-check-input" type="radio" name="payment_method" id="payUPI" value="UPI / QR on Pickup">
                                <label class="form-check-label fw-semibold" for="payUPI">
                                    <i class="bi bi-qr-code-scan me-1 text-primary"></i> UPI on Pickup
                                    <small class="d-block text-muted fw-normal">Scan seller's QR upon receiving shoes.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-campus btn-lg w-100 py-3 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Confirm & Place Sneaker Order
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Summary -->
        <div class="col-lg-5">
            <div class="summary-card">
                <h5 class="fw-bold mb-3 pb-2 border-bottom">Items in Order (<?= count($items) ?>)</h5>
                <div class="d-flex flex-column gap-3 mb-4">
                    <?php foreach ($items as $item): ?>
                        <div class="d-flex align-items-center gap-3 pb-2 border-bottom">
                            <img src="<?= product_img($item['image']) ?>" alt="<?= e($item['title']) ?>" class="rounded" style="width: 55px; height: 55px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark small text-truncate" style="max-width: 220px;">
                                    <?= e($item['title']) ?>
                                </div>
                                <div class="small text-muted">
                                    Size: <?= e($item['size']) ?> • Qty: <?= (int)$item['qty'] ?>
                                </div>
                            </div>
                            <div class="fw-bold text-dark">
                                <?= format_price($item['line_total']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                    <span class="text-muted">Campus Try-On Delivery</span>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success">FREE</span>
                </div>

                <div class="d-flex justify-content-between align-items-baseline mb-4">
                    <span class="h5 fw-bold text-dark">Total Amount:</span>
                    <span class="h3 fw-bold text-primary"><?= format_price($total_amount) ?></span>
                </div>

                <div class="p-3 bg-light rounded-3 border small text-muted">
                    <div class="fw-bold text-dark mb-1"><i class="bi bi-shield-check text-success me-1"></i> Try-On Safety Policy</div>
                    You only pay after you try the sneakers on in person and check the fit and sole quality.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

