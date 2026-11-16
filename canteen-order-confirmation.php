<?php
// canteen-order-confirmation.php - Express Food Pre-order Receipt & Token (Zero JavaScript)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user_id = current_user_id();
$order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    header("Location: canteen-orders.php");
    exit;
}

// Fetch order details
$stmt = $conn->prepare("SELECT * FROM canteen_orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_res = $stmt->get_result();

if ($order_res->num_rows === 0) {
    set_flash('danger', 'Canteen order not found.');
    header("Location: canteen.php");
    exit;
}

$order = $order_res->fetch_assoc();
$stmt->close();

// Fetch items for this canteen order
$i_stmt = $conn->prepare("
    SELECT coi.*, ci.image, ci.prep_time 
    FROM canteen_order_items coi
    LEFT JOIN canteen_items ci ON coi.item_id = ci.id
    WHERE coi.order_id = ?
");
$i_stmt->bind_param("i", $order_id);
$i_stmt->execute();
$items = $i_stmt->get_result();

$page_title = 'Canteen Token #' . $order['token_number'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Digital Food Token Card -->
            <div class="canteen-token-card mb-4 text-center">
                <span class="badge bg-success bg-opacity-25 text-success border border-success px-3 py-1 mb-2 fw-bold text-uppercase">
                    <i class="bi bi-check-circle-fill me-1"></i> Pre-Order Confirmed
                </span>

                <h1 class="display-5 fw-bold text-dark mb-1">Express Food Token</h1>
                <p class="text-muted small mb-3">Present this digital token at the pickup counter to collect your food.</p>

                <!-- Token Number Highlight Box -->
                <div class="py-3 px-4 d-inline-block bg-warning bg-opacity-10 border border-warning rounded-4 mb-4">
                    <span class="text-uppercase small fw-bold text-muted d-block" style="letter-spacing: 1px;">Pickup Token Number</span>
                    <span class="canteen-token-number">#<?= (int)$order['token_number'] ?></span>
                </div>

                <!-- Pickup Schedule Banner -->
                <div class="p-3 bg-light rounded-4 border text-start mb-4">
                    <div class="row g-3">
                        <div class="col-md-6 border-end-md">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-alarm text-warning fs-5"></i>
                                <span class="fw-bold text-dark">Scheduled Pickup Time:</span>
                            </div>
                            <div class="h5 fw-bold text-primary ms-4 mb-0"><?= e($order['pickup_time']) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-geo-alt-fill text-danger fs-5"></i>
                                <span class="fw-bold text-dark">Designated Pickup Counter:</span>
                            </div>
                            <div class="h6 fw-bold text-dark ms-4 mb-0"><?= e($order['pickup_counter']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Order Details Meta -->
                <div class="row g-3 p-3 bg-white rounded-3 border text-start mb-4">
                    <div class="col-sm-3 col-6">
                        <small class="text-muted d-block">Order Reference</small>
                        <span class="fw-bold small"><?= e($order['order_code']) ?></span>
                    </div>
                    <div class="col-sm-3 col-6">
                        <small class="text-muted d-block">Order Time</small>
                        <span class="fw-bold small"><?= date('d M Y, h:i A', strtotime($order['date'])) ?></span>
                    </div>
                    <div class="col-sm-3 col-6">
                        <small class="text-muted d-block">Payment Mode</small>
                        <span class="fw-bold small"><?= e($order['payment_method']) ?></span>
                    </div>
                    <div class="col-sm-3 col-6">
                        <small class="text-muted d-block">Kitchen Status</small>
                        <span class="badge bg-warning text-dark small"><?= e($order['status']) ?></span>
                    </div>
                </div>

                <?php if (!empty($order['special_notes'])): ?>
                    <div class="p-3 bg-light rounded-3 border text-start mb-4 small">
                        <strong class="text-dark d-block"><i class="bi bi-chat-left-dots text-primary me-1"></i> Special Preparation Notes:</strong>
                        <span class="text-muted"><?= nl2br(e($order['special_notes'])) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Ordered Items Breakdown -->
                <div class="text-start mb-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Pre-ordered Food Dishes</h5>
                    <div class="d-flex flex-column gap-2">
                        <?php while ($it = $items->fetch_assoc()): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light border">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= product_img($it['image']) ?>" alt="<?= e($it['title']) ?>" class="rounded" style="width: 48px; height: 48px; object-fit: cover; background: #fff;">
                                    <div>
                                        <span class="fw-bold text-dark small d-block"><?= e($it['title']) ?></span>
                                        <small class="text-muted">
                                            <?= food_type_badge($it['food_type']) ?> • <?= e($it['category']) ?> • Qty: <?= (int)$it['qty'] ?>
                                        </small>
                                    </div>
                                </div>
                                <span class="fw-bold text-primary"><?= format_price($it['price'] * $it['qty']) ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-3">
                        <span class="h6 fw-bold mb-0">Total Amount:</span>
                        <span class="h4 fw-bold text-primary mb-0"><?= format_price($order['total']) ?></span>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="d-flex justify-content-center gap-3 flex-wrap pt-2">
                    <a href="canteen-orders.php" class="btn btn-outline-warning text-dark fw-bold px-4 rounded-pill">
                        <i class="bi bi-receipt me-1"></i> My Active Food Tokens
                    </a>
                    <a href="canteen.php" class="btn btn-warning text-dark fw-bold px-4 rounded-pill">
                        <i class="bi bi-plus-circle me-1"></i> Order More Food
                    </a>
                    <a href="index.php" class="btn btn-campus px-4 rounded-pill">
                        <i class="bi bi-house me-1"></i> Campus Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$i_stmt->close();
require_once __DIR__ . '/includes/footer.php';

