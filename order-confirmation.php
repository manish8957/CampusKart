<?php
// order-confirmation.php - Campus Order Receipt & Handover Confirmation
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user_id = current_user_id();
$order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    header("Location: orders.php");
    exit;
}

// Fetch order details with prepared statement
$order_stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE id = ? AND user_id = ?
");
$order_stmt->bind_param("ii", $order_id, $user_id);
$order_stmt->execute();
$order_res = $order_stmt->get_result();

if ($order_res->num_rows === 0) {
    set_flash('danger', 'Order not found.');
    header("Location: orders.php");
    exit;
}

$order = $order_res->fetch_assoc();
$order_stmt->close();

// Fetch order items
$items_stmt = $conn->prepare("
    SELECT oi.*, p.image, p.condition 
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result();

$page_title = 'Order Confirmation #' . $order['order_code'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Card -->
            <div class="bg-white rounded-4 border p-4 p-md-5 shadow-sm text-center mb-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle shadow-sm" style="width: 75px; height: 75px; font-size: 2.25rem;">
                        <i class="bi bi-check-lg"></i>
                    </span>
                </div>
                <span class="badge bg-success bg-opacity-25 text-success border border-success px-3 py-1 mb-2">Order Confirmed</span>
                <h2 class="fw-bold text-dark">Thank You For Your Order!</h2>
                <p class="text-muted mb-4">
                    Your request has been routed to the student seller. Order code: <strong class="text-primary"><?= e($order['order_code']) ?></strong>
                </p>

                <!-- Key Details Pill Grid -->
                <div class="row g-3 p-3 bg-light rounded-3 border text-start mb-4">
                    <div class="col-sm-3 col-6">
                        <small class="text-muted d-block">Order Date</small>
                        <span class="fw-bold small"><?= date('d M Y, h:i A', strtotime($order['date'])) ?></span>
                    </div>
                    <div class="col-sm-3 col-6">
                        <small class="text-muted d-block">Total Amount</small>
                        <span class="fw-bold text-primary small"><?= format_price($order['total']) ?></span>
                    </div>
                    <div class="col-sm-3 col-6">
                        <small class="text-muted d-block">Payment Method</small>
                        <span class="fw-bold small"><?= e($order['payment_method']) ?></span>
                    </div>
                    <div class="col-sm-3 col-6">
                        <small class="text-muted d-block">Pickup Status</small>
                        <span class="badge bg-warning text-dark small"><?= e($order['status']) ?></span>
                    </div>
                </div>

                <!-- Handover Destination Details -->
                <div class="p-3 rounded-3 border text-start mb-4" style="background-color: #eff6ff; border-color: #bfdbfe !important;">
                    <h6 class="fw-bold text-primary mb-1"><i class="bi bi-geo-alt-fill me-1"></i> Designated Pickup Point</h6>
                    <p class="mb-1 text-dark small"><?= nl2br(e($order['address'])) ?></p>
                    <small class="text-muted">Contact: <?= e($order['phone']) ?></small>
                </div>

                <!-- Ordered Items List -->
                <div class="text-start mb-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Purchased Items</h5>
                    <div class="d-flex flex-column gap-2">
                        <?php while ($item = $order_items->fetch_assoc()): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-light border">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= product_img($item['image'] ?? '') ?>" alt="<?= e($item['title']) ?>" class="rounded" style="width: 45px; height: 45px; object-fit: cover;">
                                    <div>
                                        <span class="fw-bold text-dark small d-block"><?= e($item['title']) ?></span>
                                        <small class="text-muted">Qty: <?= (int)$item['qty'] ?> × <?= format_price($item['price']) ?></small>
                                    </div>
                                </div>
                                <span class="fw-bold text-primary"><?= format_price($item['price'] * $item['qty']) ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Next Steps Accordion -->
                <div class="text-start p-3 bg-light rounded-3 border small mb-4">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle-fill text-warning me-1"></i> What happens next?</h6>
                    <ol class="ps-3 mb-0 text-secondary">
                        <li class="mb-1">The student seller is notified with your pickup point and contact number.</li>
                        <li class="mb-1">You will receive a call or WhatsApp message to confirm the meeting time (e.g. library, canteen).</li>
                        <li>Inspect the condition in person and pay via Cash or UPI on spot!</li>
                    </ol>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="orders.php" class="btn btn-outline-primary px-4">
                        <i class="bi bi-bag-check me-1"></i> View Order History
                    </a>
                    <a href="products.php" class="btn btn-campus px-4">
                        <i class="bi bi-grid-fill me-1"></i> Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$items_stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>

