<?php
// sneaker-cart.php - Sneaker Shopping Cart (Zero JavaScript - Pure HTML/PHP)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user_id = current_user_id();

// Handle Sneaker Cart POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $sneaker_id = isset($_POST['sneaker_id']) ? (int)$_POST['sneaker_id'] : 0;
        $qty = isset($_POST['qty']) ? max(1, min(5, (int)$_POST['qty'])) : 1;

        if ($sneaker_id > 0) {
            $chk = $conn->prepare("SELECT id, title, price, seller_id FROM sneakers WHERE id = ? AND status = 'available'");
            $chk->bind_param("i", $sneaker_id);
            $chk->execute();
            $s_res = $chk->get_result();

            if ($item = $s_res->fetch_assoc()) {
                if ($item['seller_id'] == $user_id) {
                    set_flash('warning', 'You cannot buy your own listed sneaker!');
                } else {
                    $c_chk = $conn->prepare("SELECT id, qty FROM sneaker_cart WHERE user_id = ? AND sneaker_id = ?");
                    $c_chk->bind_param("ii", $user_id, $sneaker_id);
                    $c_chk->execute();
                    $exist = $c_chk->get_result()->fetch_assoc();

                    if ($exist) {
                        $new_qty = min(5, $exist['qty'] + $qty);
                        $upd = $conn->prepare("UPDATE sneaker_cart SET qty = ? WHERE id = ?");
                        $upd->bind_param("ii", $new_qty, $exist['id']);
                        $upd->execute();
                        $upd->close();
                    } else {
                        $ins = $conn->prepare("INSERT INTO sneaker_cart (user_id, sneaker_id, qty) VALUES (?, ?, ?)");
                        $ins->bind_param("iii", $user_id, $sneaker_id, $qty);
                        $ins->execute();
                        $ins->close();
                    }
                    $c_chk->close();
                    set_flash('success', '"' . $item['title'] . '" added to your Sneaker Cart.');
                }
            } else {
                set_flash('danger', 'This sneaker is no longer available.');
            }
            $chk->close();
        }
        header("Location: sneaker-cart.php");
        exit;
    }

    if ($action === 'update') {
        $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
        $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;

        if ($cart_id > 0) {
            if ($qty <= 0) {
                $del = $conn->prepare("DELETE FROM sneaker_cart WHERE id = ? AND user_id = ?");
                $del->bind_param("ii", $cart_id, $user_id);
                $del->execute();
                $del->close();
                set_flash('info', 'Sneaker removed from cart.');
            } else {
                $qty = min(5, $qty);
                $upd = $conn->prepare("UPDATE sneaker_cart SET qty = ? WHERE id = ? AND user_id = ?");
                $upd->bind_param("iii", $qty, $cart_id, $user_id);
                $upd->execute();
                $upd->close();
                set_flash('success', 'Sneaker cart updated.');
            }
        }
        header("Location: sneaker-cart.php");
        exit;
    }

    if ($action === 'remove') {
        $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
        if ($cart_id > 0) {
            $del = $conn->prepare("DELETE FROM sneaker_cart WHERE id = ? AND user_id = ?");
            $del->bind_param("ii", $cart_id, $user_id);
            $del->execute();
            $del->close();
            set_flash('info', 'Sneaker removed from cart.');
        }
        header("Location: sneaker-cart.php");
        exit;
    }

    if ($action === 'clear') {
        $clr = $conn->prepare("DELETE FROM sneaker_cart WHERE user_id = ?");
        $clr->bind_param("i", $user_id);
        $clr->execute();
        $clr->close();
        set_flash('info', 'Sneaker cart cleared.');
        header("Location: sneaker-cart.php");
        exit;
    }
}

// Fetch Sneaker Cart Items
$stmt = $conn->prepare("
    SELECT sc.id AS cart_id, sc.qty,
           s.id AS sneaker_id, s.title, s.brand, s.size, s.colour, s.price, s.mrp, s.condition, s.image,
           u.name AS seller_name
    FROM sneaker_cart sc
    JOIN sneakers s ON sc.sneaker_id = s.id
    JOIN users u ON s.seller_id = u.id
    WHERE sc.user_id = ?
    ORDER BY sc.id DESC
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

$grand_total = $subtotal; // Free campus delivery

$page_title = 'My Sneaker Cart';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="sneakers.php" class="text-decoration-none">Sneaker Store</a></li>
            <li class="breadcrumb-item active" aria-current="page">Sneaker Cart</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-tag-fill text-danger me-2"></i>My Sneaker Cart</h1>
            <p class="text-muted small mb-0">Review your selected college kicks before campus handover.</p>
        </div>
        <?php if (!empty($items)): ?>
            <form action="sneaker-cart.php" method="POST" class="m-0">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                    <i class="bi bi-trash3 me-1"></i> Clear Cart
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($items)): ?>
        <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-sm">
            <div class="display-1 text-muted mb-3"><i class="bi bi-bag-x"></i></div>
            <h3 class="fw-bold">Your Sneaker Cart is Empty</h3>
            <p class="text-muted">Explore authentic pre-loved college kicks from students on campus.</p>
            <a href="sneakers.php" class="btn btn-campus btn-lg px-4 mt-2">
                <i class="bi bi-tag-fill me-1"></i> Browse Sneaker Store
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Left: Table of Items -->
            <div class="col-lg-8">
                <div class="bg-white rounded-4 border shadow-sm overflow-hidden mb-4">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="ps-4">Sneaker</th>
                                    <th scope="col">Size & Colour</th>
                                    <th scope="col">Price</th>
                                    <th scope="col" style="width: 140px;">Quantity</th>
                                    <th scope="col">Total</th>
                                    <th scope="col" class="text-end pe-4">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <a href="sneaker-details.php?id=<?= (int)$item['sneaker_id'] ?>">
                                                    <img src="<?= product_img($item['image']) ?>" alt="<?= e($item['title']) ?>" class="cart-item-img">
                                                </a>
                                                <div>
                                                    <a href="sneaker-details.php?id=<?= (int)$item['sneaker_id'] ?>" class="fw-bold text-dark text-decoration-none d-block">
                                                        <?= e($item['title']) ?>
                                                    </a>
                                                    <small class="text-muted d-block">
                                                        <span class="badge bg-light text-dark border"><?= e($item['brand']) ?></span> • 
                                                        Seller: <?= e($item['seller_name']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="badge bg-dark"><?= e($item['size']) ?></span>
                                            <small class="text-muted d-block"><?= e($item['colour']) ?></small>
                                        </td>

                                        <td>
                                            <span class="fw-bold text-dark"><?= format_price($item['price']) ?></span>
                                            <?php if ($item['mrp']): ?>
                                                <small class="text-muted d-block text-decoration-line-through"><?= format_price($item['mrp']) ?></small>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Quantity Form Controls (Pure HTML POST) -->
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <form action="sneaker-cart.php" method="POST" class="m-0">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                                                    <input type="hidden" name="qty" value="<?= max(0, $item['qty'] - 1) ?>">
                                                    <button type="submit" class="btn btn-sm btn-light border px-2 py-0 fw-bold">-</button>
                                                </form>
                                                <span class="px-2 fw-bold text-dark"><?= (int)$item['qty'] ?></span>
                                                <form action="sneaker-cart.php" method="POST" class="m-0">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                                                    <input type="hidden" name="qty" value="<?= min(5, $item['qty'] + 1) ?>">
                                                    <button type="submit" class="btn btn-sm btn-light border px-2 py-0 fw-bold" <?= ($item['qty'] >= 5) ? 'disabled' : '' ?>>+</button>
                                                </form>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="fw-bold text-primary"><?= format_price($item['line_total']) ?></span>
                                        </td>

                                        <td class="text-end pe-4">
                                            <form action="sneaker-cart.php" method="POST" class="m-0 d-inline">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-2" title="Remove">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <a href="sneakers.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Continue Sneaker Shopping
                </a>
            </div>

            <!-- Right: Order Summary Card -->
            <div class="col-lg-4">
                <div class="summary-card">
                    <h5 class="fw-bold mb-4 pb-2 border-bottom">Sneaker Order Summary</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal (<?= count($items) ?> <?= count($items) == 1 ? 'pair' : 'pairs' ?>)</span>
                        <span class="fw-semibold text-dark"><?= format_price($subtotal) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span class="text-muted">Campus Try-On Handover</span>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success">FREE</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-baseline mb-4">
                        <span class="h5 fw-bold text-dark">Total:</span>
                        <span class="h3 fw-bold text-primary"><?= format_price($grand_total) ?></span>
                    </div>

                    <a href="sneaker-checkout.php" class="btn btn-campus btn-lg w-100 py-3 fw-bold mb-3">
                        <i class="bi bi-shield-check me-2"></i> Proceed to Checkout
                    </a>

                    <div class="p-3 bg-light rounded-3 border small text-muted">
                        <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle text-primary me-1"></i> How It Works:</div>
                        Meet the student seller on campus, try the shoes on for fit, inspect the sole and condition, then pay via Cash or UPI on the spot.
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

