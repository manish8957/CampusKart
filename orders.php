<?php
// orders.php - Student's Order History
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user_id = current_user_id();

// Fetch all orders for current student
$stmt = $conn->prepare("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();

$page_title = 'My Orders';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">My Orders</li>
        </ol>
    </nav>

    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1"><i class="bi bi-bag-check text-success me-2"></i>My Orders & Campus Pickups</h1>
        <p class="text-muted small mb-0">Track items you have ordered from student peers.</p>
    </div>

    <!-- Orders Hub Navigation Tabs -->
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap border-bottom pb-3">
        <a href="orders.php" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
            <i class="bi bi-book me-1"></i> Books & Gadgets Orders
        </a>
        <a href="sneaker-orders.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-tag-fill me-1"></i> Sneaker Store Orders
        </a>
        <a href="canteen-orders.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-cup-hot-fill me-1"></i> Canteen Food Tokens
        </a>
    </div>

    <?php if ($orders_result->num_rows === 0): ?>
        <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-sm">
            <div class="display-2 text-muted mb-3"><i class="bi bi-bag-x"></i></div>
            <h4 class="fw-bold">No Orders Placed Yet</h4>
            <p class="text-muted">You haven't ordered any college items yet.</p>
            <a href="products.php" class="btn btn-campus mt-2">
                <i class="bi bi-grid me-1"></i> Browse Books & Gadgets
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($ord = $orders_result->fetch_assoc()): ?>
                <div class="col-12">
                    <div class="bg-white rounded-4 border p-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-3 mb-3 border-bottom">
                            <div>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-1">
                                    Order #<?= e($ord['order_code']) ?>
                                </span>
                                <span class="text-muted small ms-2">
                                    <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y, h:i A', strtotime($ord['date'])) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark px-3 py-2">
                                    <i class="bi bi-clock-history me-1"></i> <?= e($ord['status']) ?>
                                </span>
                                <a href="order-confirmation.php?order_id=<?= (int)$ord['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                    View Receipt
                                </a>
                            </div>
                        </div>

                        <div class="row g-3 align-items-center">
                            <div class="col-md-5">
                                <div class="small text-muted mb-1">Campus Handover Location:</div>
                                <div class="fw-semibold text-dark small"><i class="bi bi-geo-alt text-danger me-1"></i> <?= e($ord['address']) ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted mb-1">Payment Method:</div>
                                <div class="fw-semibold text-dark small"><i class="bi bi-wallet2 text-success me-1"></i> <?= e($ord['payment_method']) ?></div>
                            </div>
                            <div class="col-md-2">
                                <div class="small text-muted mb-1">Items Count:</div>
                                <div class="fw-semibold text-dark small"><?= (int)$ord['item_count'] ?> <?= ($ord['item_count'] == 1) ? 'item' : 'items' ?></div>
                            </div>
                            <div class="col-md-2 text-md-end">
                                <div class="small text-muted mb-1">Total:</div>
                                <div class="h5 fw-bold text-primary mb-0"><?= format_price($ord['total']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>

