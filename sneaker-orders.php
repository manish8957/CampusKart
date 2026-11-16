<?php
// sneaker-orders.php - Student's Sneaker Order History (Zero JavaScript)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user_id = current_user_id();

// Fetch all sneaker orders for current student
$stmt = $conn->prepare("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM sneaker_orders o
    LEFT JOIN sneaker_order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();

$page_title = 'My Sneaker Orders';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="sneakers.php" class="text-decoration-none">Sneaker Store</a></li>
            <li class="breadcrumb-item active" aria-current="page">My Sneaker Orders</li>
        </ol>
    </nav>

    <!-- Orders Hub Navigation Tabs -->
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap border-bottom pb-3">
        <a href="orders.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-book me-1"></i> Books & Gadgets Orders
        </a>
        <a href="sneaker-orders.php" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold">
            <i class="bi bi-tag-fill me-1"></i> Sneaker Store Orders
        </a>
        <a href="canteen-orders.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-cup-hot-fill me-1"></i> Canteen Food Tokens
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-tag-fill text-danger me-2"></i>My Sneaker Orders & Meetups</h1>
            <p class="text-muted small mb-0">Track campus sneaker try-on requests and handovers.</p>
        </div>
        <a href="sneakers.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
            <i class="bi bi-plus-circle me-1"></i> Browse More Kicks
        </a>
    </div>

    <?php if ($orders_result->num_rows === 0): ?>
        <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-sm">
            <div class="display-2 text-danger opacity-50 mb-3"><i class="bi bi-tag"></i></div>
            <h4 class="fw-bold">No Sneaker Orders Yet</h4>
            <p class="text-muted">You haven't requested any sneaker meetups or purchases yet.</p>
            <a href="sneakers.php" class="btn btn-danger rounded-pill px-4 mt-2">
                <i class="bi bi-tag-fill me-1"></i> Explore Sneaker Store
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($ord = $orders_result->fetch_assoc()): ?>
                <div class="col-12">
                    <div class="bg-white rounded-4 border p-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-3 mb-3 border-bottom">
                            <div>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-1 fw-bold">
                                    Sneakers #<?= e($ord['order_code']) ?>
                                </span>
                                <span class="text-muted small ms-2">
                                    <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y, h:i A', strtotime($ord['date'])) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark px-3 py-2">
                                    <i class="bi bi-clock-history me-1"></i> <?= e($ord['status']) ?>
                                </span>
                                <a href="sneaker-order-confirmation.php?order_id=<?= (int)$ord['id'] ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                    View Receipt
                                </a>
                            </div>
                        </div>

                        <div class="row g-3 align-items-center">
                            <div class="col-md-5">
                                <div class="small text-muted mb-1">Campus Try-On / Meetup Location:</div>
                                <div class="fw-semibold text-dark small"><i class="bi bi-geo-alt text-danger me-1"></i> <?= e($ord['address']) ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted mb-1">Payment Method:</div>
                                <div class="fw-semibold text-dark small"><i class="bi bi-wallet2 text-success me-1"></i> <?= e($ord['payment_method']) ?></div>
                            </div>
                            <div class="col-md-2">
                                <div class="small text-muted mb-1">Pairs Ordered:</div>
                                <div class="fw-semibold text-dark small"><?= (int)$ord['item_count'] ?> <?= ($ord['item_count'] == 1) ? 'pair' : 'pairs' ?></div>
                            </div>
                            <div class="col-md-2 text-md-end">
                                <div class="small text-muted mb-1">Total:</div>
                                <div class="h5 fw-bold text-danger mb-0"><?= format_price($ord['total']) ?></div>
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

