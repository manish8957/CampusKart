<?php
// cart.php - Shopping Cart Management with Database Storage & Login Enforcement
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Phase 3 Requirement: Restrict cart to logged-in users
require_login();

$user_id = current_user_id();

// Handle Cart Actions (Add, Update, Remove, Clear)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $qty = isset($_POST['qty']) ? max(1, min(10, (int)$_POST['qty'])) : 1;

        if ($product_id > 0) {
            // Verify product exists and is available
            $prod_check = $conn->prepare("SELECT id, title, price, seller_id FROM products WHERE id = ? AND status = 'available'");
            $prod_check->bind_param("i", $product_id);
            $prod_check->execute();
            $p_res = $prod_check->get_result();

            if ($item = $p_res->fetch_assoc()) {
                if ($item['seller_id'] == $user_id) {
                    set_flash('warning', 'You cannot buy your own listed item!');
                } else {
                    // Check if already in user's cart
                    $check_cart = $conn->prepare("SELECT id, qty FROM cart WHERE user_id = ? AND product_id = ?");
                    $check_cart->bind_param("ii", $user_id, $product_id);
                    $check_cart->execute();
                    $c_res = $check_cart->get_result();

                    if ($existing = $c_res->fetch_assoc()) {
                        $new_qty = min(10, $existing['qty'] + $qty);
                        $upd_stmt = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ?");
                        $upd_stmt->bind_param("ii", $new_qty, $existing['id']);
                        $upd_stmt->execute();
                        $upd_stmt->close();
                        set_flash('info', 'Updated quantity for "' . $item['title'] . '".');
                    } else {
                        $ins_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, qty) VALUES (?, ?, ?)");
                        $ins_stmt->bind_param("iii", $user_id, $product_id, $qty);
                        $ins_stmt->execute();
                        $ins_stmt->close();
                        set_flash('success', '"' . $item['title'] . '" added to your cart.');
                    }
                    $check_cart->close();
                }
            } else {
                set_flash('danger', 'This item is no longer available.');
            }
            $prod_check->close();
        }
        header("Location: cart.php");
        exit;
    }

    if ($action === 'update') {
        $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
        $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;

        if ($cart_id > 0) {
            if ($qty <= 0) {
                $del_stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $del_stmt->bind_param("ii", $cart_id, $user_id);
                $del_stmt->execute();
                $del_stmt->close();
                set_flash('info', 'Item removed from cart.');
            } else {
                $qty = min(10, $qty);
                $upd_stmt = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ? AND user_id = ?");
                $upd_stmt->bind_param("iii", $qty, $cart_id, $user_id);
                $upd_stmt->execute();
                $upd_stmt->close();
                set_flash('success', 'Cart updated successfully.');
            }
        }
        header("Location: cart.php");
        exit;
    }

    if ($action === 'remove') {
        $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
        if ($cart_id > 0) {
            $del_stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $del_stmt->bind_param("ii", $cart_id, $user_id);
            $del_stmt->execute();
            $del_stmt->close();
            set_flash('info', 'Item removed from your cart.');
        }
        header("Location: cart.php");
        exit;
    }

    if ($action === 'clear') {
        $clear_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clear_stmt->bind_param("i", $user_id);
        $clear_stmt->execute();
        $clear_stmt->close();
        set_flash('info', 'Your cart has been cleared.');
        header("Location: cart.php");
        exit;
    }
}

// Fetch all cart items for the logged-in user
$cart_query = $conn->prepare("
    SELECT c.id AS cart_id, c.qty, p.id AS product_id, p.title, p.price, p.mrp, p.condition, p.image, 
           u.name AS seller_name, cat.name AS category_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN users u ON p.seller_id = u.id
    JOIN categories cat ON p.category_id = cat.id
    WHERE c.user_id = ?
    ORDER BY c.id DESC
");
$cart_query->bind_param("i", $user_id);
$cart_query->execute();
$cart_items = $cart_query->get_result();

$subtotal = 0.00;
$items_list = [];
while ($row = $cart_items->fetch_assoc()) {
    $item_total = $row['price'] * $row['qty'];
    $subtotal += $item_total;
    $row['item_total'] = $item_total;
    $items_list[] = $row;
}
$cart_query->close();

$campus_delivery_fee = 0.00; // Free in-person handover
$grand_total = $subtotal + $campus_delivery_fee;

$page_title = 'My Shopping Cart';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php" class="text-decoration-none">Products</a></li>
            <li class="breadcrumb-item active" aria-current="page">Cart</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-cart3 text-primary me-2"></i>My Shopping Cart</h1>
            <p class="text-muted small mb-0">Review your selected college books and gear before checkout.</p>
        </div>
        <?php if (!empty($items_list)): ?>
            <form action="cart.php" method="POST">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                    <i class="bi bi-trash3 me-1"></i> Clear Cart
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($items_list)): ?>
        <!-- Empty Cart Notice -->
        <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-sm">
            <div class="display-1 text-muted mb-3"><i class="bi bi-cart-x"></i></div>
            <h3 class="fw-bold">Your Cart is Empty</h3>
            <p class="text-muted">You haven't added any college books or gadgets yet. Find what seniors are selling!</p>
            <div class="mt-4">
                <a href="products.php" class="btn btn-campus btn-lg px-4 me-2">
                    <i class="bi bi-grid-fill me-2"></i> Explore Marketplace
                </a>
                <a href="products.php?cat=6" class="btn btn-outline-primary btn-lg px-4">
                    <i class="bi bi-collection me-2"></i> Semester Bundles
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Left: Items Table -->
            <div class="col-lg-8">
                <div class="bg-white rounded-4 border shadow-sm overflow-hidden mb-4">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="ps-4">Product Details</th>
                                    <th scope="col">Condition</th>
                                    <th scope="col">Price</th>
                                    <th scope="col" style="width: 140px;">Quantity</th>
                                    <th scope="col">Total</th>
                                    <th scope="col" class="text-end pe-4">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items_list as $item): ?>
                                    <tr>
                                        <!-- Product info -->
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <a href="product-details.php?id=<?= (int)$item['product_id'] ?>">
                                                    <img src="<?= product_img($item['image']) ?>" alt="<?= e($item['title']) ?>" class="cart-item-img">
                                                </a>
                                                <div>
                                                    <a href="product-details.php?id=<?= (int)$item['product_id'] ?>" class="fw-bold text-dark text-decoration-none d-block">
                                                        <?= e($item['title']) ?>
                                                    </a>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-tag me-1"></i> <?= e($item['category_name']) ?> • 
                                                        <i class="bi bi-person me-1"></i> <?= e($item['seller_name']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Condition -->
                                        <td>
                                            <?= condition_badge($item['condition']) ?>
                                        </td>

                                        <!-- Unit Price -->
                                        <td>
                                            <span class="fw-bold text-dark"><?= format_price($item['price']) ?></span>
                                            <?php if ($item['mrp']): ?>
                                                <small class="text-muted d-block text-decoration-line-through"><?= format_price($item['mrp']) ?></small>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Quantity Controls (Zero JavaScript - Pure HTML POST Forms) -->
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <form action="cart.php" method="POST" class="m-0">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                                                    <input type="hidden" name="qty" value="<?= max(0, $item['qty'] - 1) ?>">
                                                    <button type="submit" class="btn btn-sm btn-light border px-2 py-0 fw-bold" title="Decrease Quantity">-</button>
                                                </form>
                                                <span class="px-2 fw-bold text-dark"><?= (int)$item['qty'] ?></span>
                                                <form action="cart.php" method="POST" class="m-0">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                                                    <input type="hidden" name="qty" value="<?= min(10, $item['qty'] + 1) ?>">
                                                    <button type="submit" class="btn btn-sm btn-light border px-2 py-0 fw-bold" title="Increase Quantity" <?= ($item['qty'] >= 10) ? 'disabled' : '' ?>>+</button>
                                                </form>
                                            </div>
                                        </td>

                                        <!-- Row Total -->
                                        <td>
                                            <span class="fw-bold text-primary"><?= format_price($item['item_total']) ?></span>
                                        </td>

                                        <!-- Remove Button -->
                                        <td class="text-end pe-4">
                                            <form action="cart.php" method="POST" class="m-0 d-inline">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-2" title="Remove Item">
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

                <div class="d-flex justify-content-between align-items-center">
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Continue Shopping
                    </a>
                </div>
            </div>

            <!-- Right: Order Summary Card -->
            <div class="col-lg-4">
                <div class="summary-card">
                    <h5 class="fw-bold mb-4 pb-2 border-bottom">Order Summary</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal (<?= count($items_list) ?> <?= count($items_list) == 1 ? 'item' : 'items' ?>)</span>
                        <span class="fw-semibold text-dark"><?= format_price($subtotal) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Campus Pickup Fee</span>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success">FREE</span>
                    </div>

                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span class="text-muted">Hostel Meeting Handover</span>
                        <span class="text-dark small">Direct Peer Exchange</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-baseline mb-4">
                        <span class="h5 fw-bold text-dark">Estimated Total:</span>
                        <span class="h3 fw-bold text-primary"><?= format_price($grand_total) ?></span>
                    </div>

                    <a href="checkout.php" class="btn btn-campus btn-lg w-100 py-3 fw-bold mb-3">
                        <i class="bi bi-shield-check me-2"></i> Proceed to Checkout
                    </a>

                    <div class="p-3 bg-light rounded-3 border text-start small text-muted">
                        <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle text-primary me-1"></i> How Payment Works:</div>
                        Pay the seller via Cash or UPI in person when you meet on campus after verifying the item condition.
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

