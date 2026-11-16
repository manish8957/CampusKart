<?php
// canteen-cart.php - Campus Canteen Food Tray / Cart (Zero JavaScript)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user_id = current_user_id();

// Handle Canteen Cart Actions (Pure HTML POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $qty = isset($_POST['qty']) ? max(1, min(10, (int)$_POST['qty'])) : 1;

        if ($item_id > 0) {
            $chk = $conn->prepare("SELECT id, title, price, is_available FROM canteen_items WHERE id = ?");
            $chk->bind_param("i", $item_id);
            $chk->execute();
            $d_res = $chk->get_result();

            if ($item = $d_res->fetch_assoc()) {
                if (!$item['is_available']) {
                    set_flash('danger', 'Sorry, "' . $item['title'] . '" is currently sold out in the canteen.');
                } else {
                    $c_chk = $conn->prepare("SELECT id, qty FROM canteen_cart WHERE user_id = ? AND item_id = ?");
                    $c_chk->bind_param("ii", $user_id, $item_id);
                    $c_chk->execute();
                    $exist = $c_chk->get_result()->fetch_assoc();

                    if ($exist) {
                        $new_qty = min(10, $exist['qty'] + $qty);
                        $upd = $conn->prepare("UPDATE canteen_cart SET qty = ? WHERE id = ?");
                        $upd->bind_param("ii", $new_qty, $exist['id']);
                        $upd->execute();
                        $upd->close();
                    } else {
                        $ins = $conn->prepare("INSERT INTO canteen_cart (user_id, item_id, qty) VALUES (?, ?, ?)");
                        $ins->bind_param("iii", $user_id, $item_id, $qty);
                        $ins->execute();
                        $ins->close();
                    }
                    $c_chk->close();
                    set_flash('success', 'Added ' . $qty . 'x "' . $item['title'] . '" to your Canteen Tray.');
                }
            } else {
                set_flash('danger', 'Dish not found.');
            }
            $chk->close();
        }

        // Return to referring page if requested, else canteen-cart.php
        $redirect = $_POST['redirect'] ?? 'canteen-cart.php';
        header("Location: " . $redirect);
        exit;
    }

    if ($action === 'update') {
        $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
        $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;

        if ($cart_id > 0) {
            if ($qty <= 0) {
                $del = $conn->prepare("DELETE FROM canteen_cart WHERE id = ? AND user_id = ?");
                $del->bind_param("ii", $cart_id, $user_id);
                $del->execute();
                $del->close();
                set_flash('info', 'Dish removed from canteen tray.');
            } else {
                $qty = min(10, $qty);
                $upd = $conn->prepare("UPDATE canteen_cart SET qty = ? WHERE id = ? AND user_id = ?");
                $upd->bind_param("iii", $qty, $cart_id, $user_id);
                $upd->execute();
                $upd->close();
                set_flash('success', 'Canteen tray updated.');
            }
        }
        header("Location: canteen-cart.php");
        exit;
    }

    if ($action === 'remove') {
        $cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
        if ($cart_id > 0) {
            $del = $conn->prepare("DELETE FROM canteen_cart WHERE id = ? AND user_id = ?");
            $del->bind_param("ii", $cart_id, $user_id);
            $del->execute();
            $del->close();
            set_flash('info', 'Dish removed from canteen tray.');
        }
        header("Location: canteen-cart.php");
        exit;
    }

    if ($action === 'clear') {
        $clr = $conn->prepare("DELETE FROM canteen_cart WHERE user_id = ?");
        $clr->bind_param("i", $user_id);
        $clr->execute();
        $clr->close();
        set_flash('info', 'Canteen tray cleared.');
        header("Location: canteen-cart.php");
        exit;
    }
}

// Fetch Canteen Cart Items
$stmt = $conn->prepare("
    SELECT cc.id AS cart_id, cc.qty,
           ci.id AS item_id, ci.title, ci.category, ci.food_type, ci.price, ci.image, ci.prep_time
    FROM canteen_cart cc
    JOIN canteen_items ci ON cc.item_id = ci.id
    WHERE cc.user_id = ?
    ORDER BY cc.id DESC
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

$grand_total = $subtotal;

$page_title = 'My Canteen Tray';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="canteen.php" class="text-decoration-none">Campus Canteen</a></li>
            <li class="breadcrumb-item active" aria-current="page">Canteen Food Tray</li>
        </ol>
    </nav>

    <!-- Header & Clear Tray -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-cup-hot-fill text-warning me-2"></i>My Canteen Food Tray</h1>
            <p class="text-muted small mb-0">Review your food pre-order before choosing your pickup time slot.</p>
        </div>
        <?php if (!empty($items)): ?>
            <form action="canteen-cart.php" method="POST" class="m-0">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">
                    <i class="bi bi-trash3 me-1"></i> Clear Tray
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($items)): ?>
        <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-sm">
            <div class="display-1 text-warning opacity-75 mb-3"><i class="bi bi-cup-hot"></i></div>
            <h3 class="fw-bold">Your Canteen Tray is Empty</h3>
            <p class="text-muted">Craving hot samosas, delicious thali, or cold coffee? Browse the campus menu now!</p>
            <a href="canteen.php" class="btn btn-warning text-dark fw-bold btn-lg px-4 mt-2 rounded-pill">
                <i class="bi bi-menu-button-wide me-1"></i> Browse Canteen Menu
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Left: Table of Tray Items -->
            <div class="col-lg-8">
                <div class="bg-white rounded-4 border shadow-sm overflow-hidden mb-4">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="ps-4">Dish</th>
                                    <th scope="col">Category & Type</th>
                                    <th scope="col">Price</th>
                                    <th scope="col" style="width: 150px;">Quantity</th>
                                    <th scope="col">Total</th>
                                    <th scope="col" class="text-end pe-4">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <!-- Dish Details -->
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= product_img($item['image']) ?>" alt="<?= e($item['title']) ?>" class="cart-item-img border" style="width: 60px; height: 60px; object-fit: cover; background: #fff;">
                                                <div>
                                                    <span class="fw-bold text-dark d-block">
                                                        <?= e($item['title']) ?>
                                                    </span>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-clock me-1 text-warning"></i> Prep: <?= e($item['prep_time']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Category & Diet -->
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <span class="badge bg-light text-dark border align-self-start small"><?= e($item['category']) ?></span>
                                                <div><?= food_type_badge($item['food_type']) ?></div>
                                            </div>
                                        </td>

                                        <!-- Price -->
                                        <td>
                                            <span class="fw-semibold text-dark"><?= format_price($item['price']) ?></span>
                                        </td>

                                        <!-- Quantity Controls (Pure HTML Forms, zero JS) -->
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <!-- Decrement Form -->
                                                <form action="canteen-cart.php" method="POST" class="d-inline m-0">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                                                    <input type="hidden" name="qty" value="<?= (int)$item['qty'] - 1 ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-0 fw-bold" style="min-width: 28px;" title="Decrease quantity">−</button>
                                                </form>

                                                <span class="fw-bold px-2"><?= (int)$item['qty'] ?></span>

                                                <!-- Increment Form -->
                                                <form action="canteen-cart.php" method="POST" class="d-inline m-0">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                                                    <input type="hidden" name="qty" value="<?= (int)$item['qty'] + 1 ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-0 fw-bold" style="min-width: 28px;" <?= $item['qty'] >= 10 ? 'disabled' : '' ?> title="Increase quantity">+</button>
                                                </form>
                                            </div>
                                        </td>

                                        <!-- Line Total -->
                                        <td>
                                            <span class="fw-bold text-primary"><?= format_price($item['line_total']) ?></span>
                                        </td>

                                        <!-- Remove Form -->
                                        <td class="text-end pe-4">
                                            <form action="canteen-cart.php" method="POST" class="d-inline m-0">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                                                <button type="submit" class="btn btn-link text-danger p-0" title="Remove dish">
                                                    <i class="bi bi-trash3-fill"></i>
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
                    <a href="canteen.php" class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i> Add More Food Items
                    </a>
                </div>
            </div>

            <!-- Right: Pre-order Summary Card -->
            <div class="col-lg-4">
                <div class="bg-white rounded-4 border p-4 shadow-sm mb-4">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Pre-order Summary</h5>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Food Items:</span>
                        <span class="fw-semibold text-dark"><?= $total_items ?> item<?= $total_items > 1 ? 's' : '' ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Items Subtotal:</span>
                        <span class="fw-semibold text-dark"><?= format_price($subtotal) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Express Counter Service:</span>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success">FREE</span>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="h6 fw-bold mb-0">Total Amount:</span>
                        <span class="h4 fw-bold text-primary mb-0"><?= format_price($grand_total) ?></span>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-4 small text-muted">
                        <div class="fw-bold text-dark mb-1"><i class="bi bi-ticket-perforated text-warning me-1"></i> Pickup Time Selection Next</div>
                        On the next step, you will choose your exact break slot (e.g. 1:00 PM Lunch) and pickup counter instead of a delivery address.
                    </div>

                    <a href="canteen-checkout.php" class="btn btn-warning text-dark fw-bold w-100 py-3 rounded-pill shadow-sm">
                        <i class="bi bi-clock-history me-1"></i> Select Pickup Time & Order
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';

