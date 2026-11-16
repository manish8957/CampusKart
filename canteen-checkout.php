<?php
// canteen-checkout.php - Canteen Express Checkout with Pickup Time (Zero JavaScript)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user_id = current_user_id();

// Fetch current user details
$u_stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$current_user = $u_stmt->get_result()->fetch_assoc();
$u_stmt->close();

// Fetch Canteen Cart items
$stmt = $conn->prepare("
    SELECT cc.id AS cart_id, cc.qty,
           ci.id AS item_id, ci.title, ci.category, ci.food_type, ci.price, ci.image, ci.prep_time
    FROM canteen_cart cc
    JOIN canteen_items ci ON cc.item_id = ci.id
    WHERE cc.user_id = ?
    ORDER BY cc.id ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_res = $stmt->get_result();

$items = [];
$subtotal = 0.00;
$total_items = 0;

while ($row = $cart_res->fetch_assoc()) {
    $row['line_total'] = $row['price'] * $row['qty'];
    $subtotal += $row['line_total'];
    $total_items += $row['qty'];
    $items[] = $row;
}
$stmt->close();

// If tray is empty, redirect back to canteen menu
if (empty($items)) {
    set_flash('warning', 'Your canteen tray is empty. Please select food items first.');
    header("Location: canteen.php");
    exit;
}

$grand_total = $subtotal;
$errors = [];

// Form submission handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup_slot = trim($_POST['pickup_slot'] ?? '');
    $custom_time = trim($_POST['custom_time'] ?? '');
    $pickup_counter = trim($_POST['pickup_counter'] ?? 'Counter 2 (Quick Service)');
    $phone = trim($_POST['phone'] ?? '');
    $special_notes = trim($_POST['special_notes'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'Pay at Canteen Counter');

    // Determine final pickup time string
    $final_pickup_time = '';
    if ($pickup_slot === 'custom') {
        if (empty($custom_time)) {
            $errors['custom_time'] = 'Please enter your custom pickup time.';
        } else {
            $final_pickup_time = 'Custom Time: ' . $custom_time;
        }
    } elseif (!empty($pickup_slot)) {
        $final_pickup_time = $pickup_slot;
    } else {
        $errors['pickup_slot'] = 'Please select a campus break pickup time slot.';
    }

    if (empty($phone)) {
        $errors['phone'] = 'Mobile number is required for food order verification.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid 10-digit mobile number.';
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Generate order code
            $order_code = 'CANTEEN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            // Generate daily token number (e.g. 101, 102...)
            $tok_q = $conn->query("SELECT MAX(token_number) as max_tok FROM canteen_orders WHERE DATE(date) = CURDATE()");
            $tok_row = $tok_q->fetch_assoc();
            $token_number = ($tok_row && $tok_row['max_tok'] >= 100) ? (int)$tok_row['max_tok'] + 1 : 101;

            // Insert into canteen_orders
            $o_stmt = $conn->prepare("
                INSERT INTO canteen_orders (order_code, token_number, user_id, total, pickup_time, pickup_counter, phone, special_notes, payment_method, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Order Received')
            ");
            $o_stmt->bind_param("siidsssss", $order_code, $token_number, $user_id, $grand_total, $final_pickup_time, $pickup_counter, $phone, $special_notes, $payment_method);
            $o_stmt->execute();
            $order_id = $conn->insert_id;
            $o_stmt->close();

            // Insert each tray item
            $oi_stmt = $conn->prepare("
                INSERT INTO canteen_order_items (order_id, item_id, title, category, food_type, price, qty)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($items as $item) {
                $oi_stmt->bind_param("iisssdi", $order_id, $item['item_id'], $item['title'], $item['category'], $item['food_type'], $item['price'], $item['qty']);
                $oi_stmt->execute();
            }
            $oi_stmt->close();

            // Clear student's canteen cart
            $c_del = $conn->prepare("DELETE FROM canteen_cart WHERE user_id = ?");
            $c_del->bind_param("i", $user_id);
            $c_del->execute();
            $c_del->close();

            $conn->commit();

            set_flash('success', 'Your food pre-order is confirmed! Your Express Token is #' . $token_number);
            header("Location: canteen-order-confirmation.php?order_id=" . $order_id);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $errors['general'] = 'Failed to process order. Please try again: ' . $e->getMessage();
        }
    }
}

$page_title = 'Canteen Express Checkout';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="canteen.php" class="text-decoration-none">Campus Canteen</a></li>
            <li class="breadcrumb-item"><a href="canteen-cart.php" class="text-decoration-none">Tray</a></li>
            <li class="breadcrumb-item active" aria-current="page">Select Pickup Time & Confirm</li>
        </ol>
    </nav>

    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1"><i class="bi bi-clock-history text-warning me-2"></i>Campus Canteen Express Pre-Order</h1>
        <p class="text-muted small mb-0">Select your college break pickup time slot to skip the line.</p>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left: Express Pickup Details Form -->
        <div class="col-lg-7">
            <div class="bg-white rounded-4 border p-4 shadow-sm mb-4">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <span class="badge bg-warning text-dark px-3 py-2 fs-6 rounded-pill">Step 2 of 2</span>
                    <h5 class="fw-bold mb-0">Pickup Details (No Delivery Address Needed)</h5>
                </div>

                <form action="canteen-checkout.php" method="POST">
                    <!-- Pickup Time Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold d-block text-dark">
                            <i class="bi bi-alarm-fill text-warning me-1"></i> Choose College Break Pickup Time Slot <span class="text-danger">*</span>
                        </label>
                        <p class="text-muted small mb-2">Food will be freshly prepared and kept hot at the pickup counter for your chosen slot.</p>

                        <div class="d-flex flex-column gap-2 mb-2">
                            <label class="form-check p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer bg-light">
                                <input class="form-check-input ms-0 mt-0" type="radio" name="pickup_slot" value="10:30 AM (Morning Short Break - 15m)" <?= (isset($_POST['pickup_slot']) && $_POST['pickup_slot'] === '10:30 AM (Morning Short Break - 15m)') ? 'checked' : '' ?>>
                                <div class="flex-grow-1">
                                    <strong class="d-block text-dark">10:30 AM — Morning Short Break</strong>
                                    <small class="text-muted">Ideal for hot ginger tea, coffee, and samosas between early lectures.</small>
                                </div>
                            </label>

                            <label class="form-check p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer bg-light">
                                <input class="form-check-input ms-0 mt-0" type="radio" name="pickup_slot" value="1:00 PM (Lunch Break - Shift 1)" <?= (!isset($_POST['pickup_slot']) || $_POST['pickup_slot'] === '1:00 PM (Lunch Break - Shift 1)') ? 'checked' : '' ?>>
                                <div class="flex-grow-1">
                                    <strong class="d-block text-dark">1:00 PM — Lunch Break (Shift 1)</strong>
                                    <small class="text-muted">Standard lunch bell for Engineering, Science & Tech blocks.</small>
                                </div>
                            </label>

                            <label class="form-check p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer bg-light">
                                <input class="form-check-input ms-0 mt-0" type="radio" name="pickup_slot" value="1:30 PM (Lunch Break - Shift 2)" <?= (isset($_POST['pickup_slot']) && $_POST['pickup_slot'] === '1:30 PM (Lunch Break - Shift 2)') ? 'checked' : '' ?>>
                                <div class="flex-grow-1">
                                    <strong class="d-block text-dark">1:30 PM — Lunch Break (Shift 2)</strong>
                                    <small class="text-muted">Commerce, Management & Humanities departments lunch break.</small>
                                </div>
                            </label>

                            <label class="form-check p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer bg-light">
                                <input class="form-check-input ms-0 mt-0" type="radio" name="pickup_slot" value="4:30 PM (Evening Post-Lab Break)" <?= (isset($_POST['pickup_slot']) && $_POST['pickup_slot'] === '4:30 PM (Evening Post-Lab Break)') ? 'checked' : '' ?>>
                                <div class="flex-grow-1">
                                    <strong class="d-block text-dark">4:30 PM — Evening Snacks & Post-Lab Break</strong>
                                    <small class="text-muted">Burgers, Maggi, fries, and cold beverages after lab classes.</small>
                                </div>
                            </label>

                            <label class="form-check p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer bg-light">
                                <input class="form-check-input ms-0 mt-0" type="radio" name="pickup_slot" value="6:00 PM (Hostel Dinner Pre-order)" <?= (isset($_POST['pickup_slot']) && $_POST['pickup_slot'] === '6:00 PM (Hostel Dinner Pre-order)') ? 'checked' : '' ?>>
                                <div class="flex-grow-1">
                                    <strong class="d-block text-dark">6:00 PM — Evening Canteen Pack & Dinner</strong>
                                    <small class="text-muted">Parcel packing ready for hostel study night.</small>
                                </div>
                            </label>

                            <label class="form-check p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer bg-light">
                                <input class="form-check-input ms-0 mt-0" type="radio" name="pickup_slot" value="Immediate (In 15 Mins)" <?= (isset($_POST['pickup_slot']) && $_POST['pickup_slot'] === 'Immediate (In 15 Mins)') ? 'checked' : '' ?>>
                                <div class="flex-grow-1">
                                    <strong class="d-block text-dark">Immediate Express (Next 15 Minutes)</strong>
                                    <small class="text-muted">Prepare right away as soon as the kitchen receives the order.</small>
                                </div>
                            </label>

                            <label class="form-check p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer bg-light">
                                <input class="form-check-input ms-0 mt-0" type="radio" name="pickup_slot" value="custom" <?= (isset($_POST['pickup_slot']) && $_POST['pickup_slot'] === 'custom') ? 'checked' : '' ?>>
                                <div class="flex-grow-1">
                                    <strong class="d-block text-dark">Custom Pickup Time</strong>
                                    <div class="mt-2">
                                        <input type="text" name="custom_time" class="form-control form-control-sm <?= isset($errors['custom_time']) ? 'is-invalid' : '' ?>" placeholder="e.g. 11:45 AM or 3:15 PM" value="<?= e($_POST['custom_time'] ?? '') ?>">
                                        <?php if (isset($errors['custom_time'])): ?>
                                            <div class="invalid-feedback"><?= e($errors['custom_time']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <?php if (isset($errors['pickup_slot'])): ?>
                            <div class="text-danger small mt-1"><?= e($errors['pickup_slot']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Pickup Counter Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">
                            <i class="bi bi-signpost-2-fill text-primary me-1"></i> Express Pickup Counter
                        </label>
                        <select name="pickup_counter" class="form-select">
                            <option value="Counter 2 (Quick Service & Beverages)" <?= (!isset($_POST['pickup_counter']) || $_POST['pickup_counter'] === 'Counter 2 (Quick Service & Beverages)') ? 'selected' : '' ?>>Counter 2 — Quick Service, Beverages & Snacks</option>
                            <option value="Counter 1 (Meals, Thali & Hot Kitchen)" <?= (isset($_POST['pickup_counter']) && $_POST['pickup_counter'] === 'Counter 1 (Meals, Thali & Hot Kitchen)') ? 'selected' : '' ?>>Counter 1 — Deluxe Thalis, Meals & Roti</option>
                            <option value="Counter 3 (Grills, Fast Food & Burgers)" <?= (isset($_POST['pickup_counter']) && $_POST['pickup_counter'] === 'Counter 3 (Grills, Fast Food & Burgers)') ? 'selected' : '' ?>>Counter 3 — Burgers, Fries & Maggi</option>
                            <option value="Main Central Canteen Counter" <?= (isset($_POST['pickup_counter']) && $_POST['pickup_counter'] === 'Main Central Canteen Counter') ? 'selected' : '' ?>>Main Central Canteen Counter</option>
                        </select>
                        <div class="form-text small">You will show your digital token number at this designated counter.</div>
                    </div>

                    <!-- Contact Mobile Number -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">
                            <i class="bi bi-telephone-fill text-success me-1"></i> Student Contact Phone <span class="text-danger">*</span>
                        </label>
                        <input type="tel" name="phone" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['phone'] ?? ($current_user['phone'] ?? '')) ?>" placeholder="10-digit mobile number for order notification">
                        <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback"><?= e($errors['phone']) ?></div>
                        <?php else: ?>
                            <div class="form-text small">Used to call you if your meal is ready or if an item runs out.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Special Kitchen Notes (Optional) -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">
                            <i class="bi bi-card-text text-secondary me-1"></i> Special Preparation Notes <span class="text-muted fw-normal">(Optional)</span>
                        </label>
                        <textarea name="special_notes" rows="2" class="form-control" placeholder="e.g. Extra spicy, less sugar in cold coffee, parcel packing, no onion..."><?= e($_POST['special_notes'] ?? '') ?></textarea>
                    </div>

                    <!-- Payment Mode -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">
                            <i class="bi bi-wallet2 text-primary me-1"></i> Payment Mode
                        </label>
                        <div class="d-flex flex-column gap-2">
                            <label class="form-check p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer bg-light">
                                <input class="form-check-input ms-0 mt-0" type="radio" name="payment_method" value="Pay at Canteen Counter (Cash / UPI)" checked>
                                <div>
                                    <strong class="d-block text-dark"><i class="bi bi-cash me-1 text-success"></i> Pay at Counter (Cash or Any UPI QR)</strong>
                                    <small class="text-muted">Pay quickly at Counter when you pick up your order.</small>
                                </div>
                            </label>
                            <label class="form-check p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer bg-light">
                                <input class="form-check-input ms-0 mt-0" type="radio" name="payment_method" value="Prepaid Student Campus Wallet">
                                <div>
                                    <strong class="d-block text-dark"><i class="bi bi-credit-card-2-front me-1 text-primary"></i> Prepaid Student ID / Campus Wallet</strong>
                                    <small class="text-muted">Deduct directly from your campus mess/card balance at pickup.</small>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-warning text-dark fw-bold btn-lg w-100 rounded-pill shadow-sm py-3">
                        <i class="bi bi-ticket-perforated-fill me-1"></i> Generate Express Token & Place Pre-order
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Order Summary -->
        <div class="col-lg-5">
            <div class="bg-white rounded-4 border p-4 shadow-sm mb-4">
                <h5 class="fw-bold mb-3 border-bottom pb-2">Your Food Tray Items (<?= $total_items ?>)</h5>

                <div class="d-flex flex-column gap-3 mb-4">
                    <?php foreach ($items as $item): ?>
                        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light border">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= product_img($item['image']) ?>" alt="<?= e($item['title']) ?>" class="rounded" style="width: 48px; height: 48px; object-fit: cover; background: #fff;">
                                <div>
                                    <strong class="text-dark d-block small"><?= e($item['title']) ?></strong>
                                    <small class="text-muted"><?= food_type_badge($item['food_type']) ?> × <?= (int)$item['qty'] ?></small>
                                </div>
                            </div>
                            <span class="fw-bold text-primary"><?= format_price($item['line_total']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="border-top pt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Food Subtotal:</span>
                        <span class="fw-semibold text-dark"><?= format_price($subtotal) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Express Queue Skip:</span>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success">FREE</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="h6 fw-bold mb-0">Total to Pay:</span>
                        <span class="h4 fw-bold text-primary mb-0"><?= format_price($grand_total) ?></span>
                    </div>
                </div>

                <div class="p-3 bg-warning bg-opacity-10 rounded-3 border border-warning small text-dark">
                    <i class="bi bi-shield-check text-warning me-1"></i> <strong>Zero Waiting Promise:</strong>
                    Food preparation is timed precisely to your selected break slot so it is hot when you arrive.
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';

