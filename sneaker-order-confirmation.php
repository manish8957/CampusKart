<?php
// sneaker-order-confirmation.php - Sneaker Order Receipt (Zero JavaScript)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user_id = current_user_id();
$order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    header("Location: sneaker-orders.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM sneaker_orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_res = $stmt->get_result();

if ($order_res->num_rows === 0) {
    set_flash('danger', 'Sneaker order not found.');
    header("Location: sneakers.php");
    exit;
}

$order = $order_res->fetch_assoc();
$stmt->close();

// Fetch items
$i_stmt = $conn->prepare("
    SELECT soi.*, s.image 
    FROM sneaker_order_items soi
    LEFT JOIN sneakers s ON soi.sneaker_id = s.id
    WHERE soi.order_id = ?
");
$i_stmt->bind_param("i", $order_id);
$i_stmt->execute();
$items = $i_stmt->get_result();

$page_title = 'Sneaker Order #' . $order['order_code'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="bg-white rounded-4 border p-4 p-md-5 shadow-sm text-center mb-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center bg-danger text-white rounded-circle shadow-sm" style="width: 75px; height: 75px; font-size: 2.25rem;">
                        <i class="bi bi-tag-fill"></i>
                    </span>
                </div>
                <span class="badge bg-success bg-opacity-25 text-success border border-success px-3 py-1 mb-2">Sneaker Request Confirmed</span>
                <h2 class="fw-bold text-dark">Sneaker Handover Requested!</h2>
                <p class="text-muted mb-4">
                    Order Code: <strong class="text-danger"><?= e($order['order_code']) ?></strong>. The student seller has received your meetup request.
                </p>

                <!-- Order Details Grid -->
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
                        <small class="text-muted d-block">Payment Mode</small>
                        <span class="fw-bold small"><?= e($order['payment_method']) ?></span>
                    </div>
                    <div class="col-sm-3 col-6">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge bg-warning text-dark small"><?= e($order['status']) ?></span>
                    </div>
                </div>

                <!-- Handover Destination -->
                <div class="p-3 rounded-3 border text-start mb-4" style="background-color: #fef2f2; border-color: #fecaca !important;">
                    <h6 class="fw-bold text-danger mb-1"><i class="bi bi-geo-alt-fill me-1"></i> Campus Try-On Location</h6>
                    <p class="mb-1 text-dark small"><?= nl2br(e($order['address'])) ?></p>
                    <small class="text-muted">Contact: <?= e($order['phone']) ?></small>
                </div>

                <!-- Items Ordered -->
                <div class="text-start mb-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Ordered Kicks</h5>
                    <div class="d-flex flex-column gap-2">
                        <?php while ($it = $items->fetch_assoc()): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-light border">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= product_img($it['image']) ?>" alt="<?= e($it['title']) ?>" class="rounded" style="width: 45px; height: 45px; object-fit: cover;">
                                    <div>
                                        <span class="fw-bold text-dark small d-block"><?= e($it['title']) ?></span>
                                        <small class="text-muted">Brand: <?= e($it['brand']) ?> • Size: <?= e($it['size']) ?> • Qty: <?= (int)$it['qty'] ?></small>
                                    </div>
                                </div>
                                <span class="fw-bold text-primary"><?= format_price($it['price'] * $it['qty']) ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="sneaker-orders.php" class="btn btn-outline-danger px-4">
                        <i class="bi bi-bag-check me-1"></i> My Sneaker Orders
                    </a>
                    <a href="sneakers.php" class="btn btn-campus px-4">
                        <i class="bi bi-tag-fill me-1"></i> Browse More Kicks
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$i_stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>

