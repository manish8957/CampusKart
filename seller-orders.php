<?php
// seller-orders.php - Campus Handover & Seller Order Requests (Zero JS - Pure PHP/HTML/CSS)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$seller_id = current_user_id();
$active_tab = trim($_GET['tab'] ?? 'books');
if ($active_tab !== 'sneakers') {
    $active_tab = 'books';
}

// Handle status updates by the seller (Mark as Handed Over / Completed)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? 'product'; // 'product' or 'sneaker'

    if ($order_id > 0) {
        if ($type === 'sneaker') {
            // Verify this sneaker order contains a sneaker listed by this seller
            $verify = $conn->prepare("
                SELECT soi.id, soi.sneaker_id
                FROM sneaker_order_items soi
                JOIN sneakers s ON soi.sneaker_id = s.id
                WHERE soi.order_id = ? AND s.seller_id = ?
            ");
            $verify->bind_param("ii", $order_id, $seller_id);
            $verify->execute();
            $v_res = $verify->get_result();

            if ($v_res->num_rows > 0 && $action === 'complete') {
                $upd = $conn->prepare("UPDATE sneaker_orders SET status = 'Completed' WHERE id = ?");
                $upd->bind_param("i", $order_id);
                $upd->execute();
                $upd->close();

                while ($item = $v_res->fetch_assoc()) {
                    if ($item['sneaker_id']) {
                        $upd_s = $conn->prepare("UPDATE sneakers SET status = 'sold' WHERE id = ? AND seller_id = ?");
                        $upd_s->bind_param("ii", $item['sneaker_id'], $seller_id);
                        $upd_s->execute();
                        $upd_s->close();
                    }
                }
                set_flash('success', 'Sneaker handover confirmed! Sneaker marked as Sold.');
            }
            $verify->close();
            header("Location: seller-orders.php?tab=sneakers");
            exit;
        } else {
            // Verify this order contains at least one product listed by this seller
            $verify = $conn->prepare("
                SELECT oi.id, oi.product_id
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ? AND p.seller_id = ?
            ");
            $verify->bind_param("ii", $order_id, $seller_id);
            $verify->execute();
            $v_res = $verify->get_result();

            if ($v_res->num_rows > 0 && $action === 'complete') {
                $upd = $conn->prepare("UPDATE orders SET status = 'Completed' WHERE id = ?");
                $upd->bind_param("i", $order_id);
                $upd->execute();
                $upd->close();

                while ($item = $v_res->fetch_assoc()) {
                    if ($item['product_id']) {
                        $upd_p = $conn->prepare("UPDATE products SET status = 'sold' WHERE id = ? AND seller_id = ?");
                        $upd_p->bind_param("ii", $item['product_id'], $seller_id);
                        $upd_p->execute();
                        $upd_p->close();
                    }
                }
                set_flash('success', 'Handover confirmed! Order has been marked as Completed and item marked as Sold.');
            }
            $verify->close();
            header("Location: seller-orders.php?tab=books");
            exit;
        }
    }
}

// Fetch all book orders containing items listed by this seller
$stmt = $conn->prepare("
    SELECT o.id AS order_id, o.order_code, o.date, o.status, o.address, o.phone AS buyer_phone, o.payment_method,
           u.name AS buyer_name, u.email AS buyer_email,
           oi.title AS product_title, oi.price, oi.qty, oi.product_id
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.seller_id = ?
    ORDER BY o.id DESC
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$seller_orders = $stmt->get_result();

// Fetch all sneaker orders containing sneakers listed by this seller
$s_stmt = $conn->prepare("
    SELECT so.id AS order_id, so.order_code, so.date, so.status, so.address, so.phone AS buyer_phone, so.payment_method,
           u.name AS buyer_name, u.email AS buyer_email,
           soi.title AS product_title, soi.price, soi.qty, soi.size, soi.colour, soi.sneaker_id
    FROM sneaker_orders so
    JOIN users u ON so.user_id = u.id
    JOIN sneaker_order_items soi ON so.id = soi.order_id
    JOIN sneakers s ON soi.sneaker_id = s.id
    WHERE s.seller_id = ?
    ORDER BY so.id DESC
");
$s_stmt->bind_param("i", $seller_id);
$s_stmt->execute();
$seller_sneaker_orders = $s_stmt->get_result();

$page_title = 'Seller Handover Requests';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="my-listings.php" class="text-decoration-none">My Listings</a></li>
            <li class="breadcrumb-item active" aria-current="page">Handover Requests</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-box-seam-fill text-warning me-2"></i>Campus Handover Requests</h1>
            <p class="text-muted small mb-0">Incoming purchase orders from student buyers for your listed books, tech, and sneakers.</p>
        </div>
        <a href="sell.php" class="btn btn-campus-yellow btn-sm px-3">
            <i class="bi bi-plus-circle me-1"></i> List New Item
        </a>
    </div>

    <!-- Navigation Tabs -->
    <div class="d-flex align-items-center gap-2 mb-4 border-bottom pb-3">
        <a href="seller-orders.php?tab=books" class="btn btn-sm rounded-pill px-3 fw-bold <?= $active_tab === 'books' ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <i class="bi bi-book me-1"></i> Books & Gadgets Requests (<?= $seller_orders->num_rows ?>)
        </a>
        <a href="seller-orders.php?tab=sneakers" class="btn btn-sm rounded-pill px-3 fw-bold <?= $active_tab === 'sneakers' ? 'btn-danger' : 'btn-outline-secondary' ?>">
            <i class="bi bi-tag-fill me-1"></i> Sneaker Try-on Requests (<?= $seller_sneaker_orders->num_rows ?>)
        </a>
    </div>

    <!-- Tab 1: Books & Gadgets -->
    <?php if ($active_tab === 'books'): ?>
        <?php if ($seller_orders->num_rows === 0): ?>
            <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-sm">
                <div class="display-2 text-warning mb-3 opacity-75"><i class="bi bi-inbox"></i></div>
                <h4 class="fw-bold">No Book Handover Requests Yet</h4>
                <p class="text-muted">When a college peer orders your listed item, their pickup location and phone will appear here.</p>
                <a href="my-listings.php" class="btn btn-campus mt-2">
                    <i class="bi bi-tags me-1"></i> View My Listed Items
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php while ($ord = $seller_orders->fetch_assoc()): ?>
                    <div class="col-12">
                        <div class="bg-white rounded-4 border p-4 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-3 mb-3 border-bottom">
                                <div>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-1 fw-bold">
                                        Order #<?= e($ord['order_code']) ?>
                                    </span>
                                    <span class="text-muted small ms-2">
                                        <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y, h:i A', strtotime($ord['date'])) ?>
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($ord['status'] === 'Completed'): ?>
                                        <span class="badge bg-success px-3 py-2">
                                            <i class="bi bi-check-circle-fill me-1"></i> Handover Completed
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark px-3 py-2">
                                            <i class="bi bi-clock-history me-1"></i> Pending Campus Pickup
                                        </span>
                                        <form action="seller-orders.php?tab=books" method="POST" class="m-0">
                                            <input type="hidden" name="type" value="product">
                                            <input type="hidden" name="order_id" value="<?= (int)$ord['order_id'] ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">
                                                <i class="bi bi-check-lg me-1"></i> Confirm Handed Over
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row g-3">
                                <!-- Ordered Item -->
                                <div class="col-md-5">
                                    <small class="text-muted d-block mb-1">Your Listed Item Ordered:</small>
                                    <h6 class="fw-bold text-dark mb-1"><?= e($ord['product_title']) ?></h6>
                                    <span class="fw-bold text-primary"><?= format_price($ord['price']) ?></span>
                                    <span class="text-muted small">(Qty: <?= (int)$ord['qty'] ?>)</span>
                                </div>

                                <!-- Buyer Details -->
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1">Buyer Student:</small>
                                    <div class="fw-bold text-dark"><?= e($ord['buyer_name']) ?></div>
                                    <div class="small text-primary fw-semibold">
                                        <i class="bi bi-telephone-fill me-1"></i> <?= e($ord['buyer_phone']) ?>
                                    </div>
                                    <small class="text-muted"><?= e($ord['buyer_email']) ?></small>
                                </div>

                                <!-- Meeting Point -->
                                <div class="col-md-4">
                                    <small class="text-muted d-block mb-1">Campus Meeting & Handover Spot:</small>
                                    <div class="p-2 rounded-2 bg-light border small text-dark">
                                        <i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= nl2br(e($ord['address'])) ?>
                                    </div>
                                    <small class="text-muted mt-1 d-block">Payment: <strong><?= e($ord['payment_method']) ?></strong></small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    <!-- Tab 2: Sneakers -->
    <?php else: ?>
        <?php if ($seller_sneaker_orders->num_rows === 0): ?>
            <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-sm">
                <div class="display-2 text-danger mb-3 opacity-50"><i class="bi bi-tag"></i></div>
                <h4 class="fw-bold">No Sneaker Meetup Requests Yet</h4>
                <p class="text-muted">When a college peer wants to try on and purchase your listed sneakers, details will appear here.</p>
                <a href="my-listings.php?tab=sneakers" class="btn btn-danger rounded-pill px-4 mt-2">
                    <i class="bi bi-tag-fill me-1"></i> View My Sneaker Listings
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php while ($sord = $seller_sneaker_orders->fetch_assoc()): ?>
                    <div class="col-12">
                        <div class="bg-white rounded-4 border p-4 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pb-3 mb-3 border-bottom">
                                <div>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-1 fw-bold">
                                        Sneakers #<?= e($sord['order_code']) ?>
                                    </span>
                                    <span class="text-muted small ms-2">
                                        <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y, h:i A', strtotime($sord['date'])) ?>
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($sord['status'] === 'Completed'): ?>
                                        <span class="badge bg-success px-3 py-2">
                                            <i class="bi bi-check-circle-fill me-1"></i> Sneaker Handed Over
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark px-3 py-2">
                                            <i class="bi bi-clock-history me-1"></i> <?= e($sord['status']) ?>
                                        </span>
                                        <form action="seller-orders.php?tab=sneakers" method="POST" class="m-0">
                                            <input type="hidden" name="type" value="sneaker">
                                            <input type="hidden" name="order_id" value="<?= (int)$sord['order_id'] ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">
                                                <i class="bi bi-check-lg me-1"></i> Confirm Handed Over
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row g-3">
                                <!-- Sneaker Item -->
                                <div class="col-md-5">
                                    <small class="text-muted d-block mb-1">Your Listed Sneaker Ordered:</small>
                                    <h6 class="fw-bold text-dark mb-1"><?= e($sord['product_title']) ?></h6>
                                    <span class="badge bg-light text-dark border small"><?= e($sord['size']) ?></span>
                                    <span class="badge bg-light text-dark border small ms-1"><?= e($sord['colour']) ?></span>
                                    <div class="fw-bold text-danger mt-1"><?= format_price($sord['price']) ?></div>
                                </div>

                                <!-- Buyer Details -->
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1">Buyer Student:</small>
                                    <div class="fw-bold text-dark"><?= e($sord['buyer_name']) ?></div>
                                    <div class="small text-danger fw-semibold">
                                        <i class="bi bi-telephone-fill me-1"></i> <?= e($sord['buyer_phone']) ?>
                                    </div>
                                    <small class="text-muted"><?= e($sord['buyer_email']) ?></small>
                                </div>

                                <!-- Meetup Point -->
                                <div class="col-md-4">
                                    <small class="text-muted d-block mb-1">Campus Try-on / Meetup Spot:</small>
                                    <div class="p-2 rounded-2 bg-light border small text-dark">
                                        <i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= nl2br(e($sord['address'])) ?>
                                    </div>
                                    <small class="text-muted mt-1 d-block">Payment: <strong><?= e($sord['payment_method']) ?></strong></small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$stmt->close();
$s_stmt->close();
require_once __DIR__ . '/includes/footer.php';
