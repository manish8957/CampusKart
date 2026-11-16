<?php
// canteen-orders.php - Student's Canteen Food Tokens & Pre-orders History (Zero JavaScript)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user_id = current_user_id();

// Fetch all canteen orders for current student
$stmt = $conn->prepare("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM canteen_orders o
    LEFT JOIN canteen_order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();

$page_title = 'My Canteen Food Tokens';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="canteen.php" class="text-decoration-none">Campus Canteen</a></li>
            <li class="breadcrumb-item active" aria-current="page">My Canteen Food Tokens</li>
        </ol>
    </nav>

    <!-- Orders Hub Navigation Tabs -->
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap border-bottom pb-3">
        <a href="orders.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-book me-1"></i> Books & Gadgets Orders
        </a>
        <a href="sneaker-orders.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-tag-fill me-1"></i> Sneaker Store Orders
        </a>
        <a href="canteen-orders.php" class="btn btn-sm btn-warning text-dark rounded-pill px-3 fw-bold">
            <i class="bi bi-cup-hot-fill me-1"></i> Canteen Food Tokens
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-ticket-perforated-fill text-warning me-2"></i>My Canteen Food Tokens</h1>
            <p class="text-muted small mb-0">Track your break pre-orders and show tokens at the food counter.</p>
        </div>
        <a href="canteen.php" class="btn btn-warning text-dark fw-bold btn-sm rounded-pill px-3">
            <i class="bi bi-plus-circle me-1"></i> Pre-order Food
        </a>
    </div>

    <?php if ($orders_result->num_rows === 0): ?>
        <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-sm">
            <div class="display-2 text-warning opacity-75 mb-3"><i class="bi bi-cup-hot"></i></div>
            <h4 class="fw-bold">No Canteen Food Pre-orders Yet</h4>
            <p class="text-muted">You haven't ordered any meals or snacks from the college canteen yet.</p>
            <a href="canteen.php" class="btn btn-warning text-dark fw-bold rounded-pill px-4 mt-2">
                <i class="bi bi-menu-button-wide me-1"></i> Explore Canteen Menu
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($ord = $orders_result->fetch_assoc()): ?>
                <?php
                $status_class = 'bg-secondary';
                if ($ord['status'] === 'Order Received') $status_class = 'bg-info text-dark';
                elseif ($ord['status'] === 'Kitchen Preparing') $status_class = 'bg-warning text-dark';
                elseif ($ord['status'] === 'Ready for Pickup') $status_class = 'bg-success text-white';
                elseif ($ord['status'] === 'Picked Up') $status_class = 'bg-primary text-white';
                ?>
                <div class="col-12">
                    <div class="bg-white rounded-4 border p-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-3 mb-3 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark px-3 py-2 fw-bold fs-6 rounded-pill">
                                    <i class="bi bi-ticket-perforated me-1"></i> TOKEN #<?= (int)$ord['token_number'] ?>
                                </span>
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    <?= e($ord['order_code']) ?>
                                </span>
                                <span class="text-muted small ms-2">
                                    <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y, h:i A', strtotime($ord['date'])) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge <?= $status_class ?> px-3 py-2">
                                    <i class="bi bi-clock-history me-1"></i> <?= e($ord['status']) ?>
                                </span>
                                <a href="canteen-order-confirmation.php?order_id=<?= (int)$ord['id'] ?>" class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-bold">
                                    View Digital Token
                                </a>
                            </div>
                        </div>

                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <div class="small text-muted mb-1"><i class="bi bi-alarm text-warning me-1"></i> Pickup Time Slot:</div>
                                <div class="fw-bold text-primary small"><?= e($ord['pickup_time']) ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted mb-1"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Pickup Counter:</div>
                                <div class="fw-semibold text-dark small"><?= e($ord['pickup_counter']) ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted mb-1"><i class="bi bi-wallet2 text-success me-1"></i> Payment Mode:</div>
                                <div class="fw-semibold text-dark small"><?= e($ord['payment_method']) ?></div>
                            </div>
                            <div class="col-md-2 text-md-end">
                                <div class="small text-muted mb-1">Total (<?= (int)$ord['item_count'] ?> dishes):</div>
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

