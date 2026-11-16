<?php
// wishlist.php - Student Wishlist Management (Zero JavaScript)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Handle guest trying to wishlist: save pending action and redirect to login
if (!is_logged_in()) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!empty($_POST['product_id'])) {
            $_SESSION['pending_wishlist_product_id'] = (int)$_POST['product_id'];
        }
        if (!empty($_POST['sneaker_id'])) {
            $_SESSION['pending_wishlist_sneaker_id'] = (int)$_POST['sneaker_id'];
        }
    }
    $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'] ?? 'wishlist.php';
    set_flash('info', 'Please sign in to save items to your wishlist.');
    header("Location: login.php");
    exit;
}

$user_id = current_user_id();

// Handle Actions (toggle, add, remove, move_to_cart)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $sneaker_id = isset($_POST['sneaker_id']) ? (int)$_POST['sneaker_id'] : 0;

    // --- Book / Gadget Products Wishlist Actions ---
    if ($product_id > 0) {
        if ($action === 'toggle' || $action === 'add') {
            if (is_in_wishlist($conn, $user_id, $product_id)) {
                $del = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                $del->bind_param("ii", $user_id, $product_id);
                $del->execute();
                $del->close();
                set_flash('info', 'Removed item from your wishlist.');
            } else {
                $ins = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
                $ins->bind_param("ii", $user_id, $product_id);
                $ins->execute();
                $ins->close();
                set_flash('success', 'Added to your wishlist!');
            }
        } elseif ($action === 'remove') {
            $del = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $del->bind_param("ii", $user_id, $product_id);
            $del->execute();
            $del->close();
            set_flash('info', 'Item removed from your wishlist.');
        } elseif ($action === 'move_to_cart') {
            $p_stmt = $conn->prepare("SELECT id, title, price, seller_id FROM products WHERE id = ? AND status = 'available'");
            $p_stmt->bind_param("i", $product_id);
            $p_stmt->execute();
            $p_res = $p_stmt->get_result();

            if ($item = $p_res->fetch_assoc()) {
                if ($item['seller_id'] == $user_id) {
                    set_flash('warning', 'You cannot buy your own listed item.');
                } else {
                    $c_chk = $conn->prepare("SELECT id, qty FROM cart WHERE user_id = ? AND product_id = ?");
                    $c_chk->bind_param("ii", $user_id, $product_id);
                    $c_chk->execute();
                    $c_res = $c_chk->get_result();

                    if ($existing = $c_res->fetch_assoc()) {
                        $upd = $conn->prepare("UPDATE cart SET qty = qty + 1 WHERE id = ?");
                        $upd->bind_param("i", $existing['id']);
                        $upd->execute();
                        $upd->close();
                    } else {
                        $ins_c = $conn->prepare("INSERT INTO cart (user_id, product_id, qty) VALUES (?, ?, 1)");
                        $ins_c->bind_param("ii", $user_id, $product_id);
                        $ins_c->execute();
                        $ins_c->close();
                    }
                    $c_chk->close();

                    $del_w = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                    $del_w->bind_param("ii", $user_id, $product_id);
                    $del_w->execute();
                    $del_w->close();

                    set_flash('success', '"' . $item['title'] . '" moved to your cart.');
                }
            } else {
                set_flash('warning', 'This item is no longer available.');
            }
            $p_stmt->close();
        }
    }

    // --- Sneaker Wishlist Actions ---
    if ($sneaker_id > 0) {
        if ($action === 'toggle_sneaker' || $action === 'add_sneaker') {
            if (is_in_sneaker_wishlist($conn, $user_id, $sneaker_id)) {
                $del = $conn->prepare("DELETE FROM sneaker_wishlist WHERE user_id = ? AND sneaker_id = ?");
                $del->bind_param("ii", $user_id, $sneaker_id);
                $del->execute();
                $del->close();
                set_flash('info', 'Removed sneakers from your wishlist.');
            } else {
                $ins = $conn->prepare("INSERT IGNORE INTO sneaker_wishlist (user_id, sneaker_id) VALUES (?, ?)");
                $ins->bind_param("ii", $user_id, $sneaker_id);
                $ins->execute();
                $ins->close();
                set_flash('success', 'Sneakers added to your wishlist!');
            }
        } elseif ($action === 'remove_sneaker') {
            $del = $conn->prepare("DELETE FROM sneaker_wishlist WHERE user_id = ? AND sneaker_id = ?");
            $del->bind_param("ii", $user_id, $sneaker_id);
            $del->execute();
            $del->close();
            set_flash('info', 'Sneakers removed from your wishlist.');
        } elseif ($action === 'move_to_sneaker_cart') {
            $s_stmt = $conn->prepare("SELECT id, title, price, seller_id FROM sneakers WHERE id = ? AND status = 'available'");
            $s_stmt->bind_param("i", $sneaker_id);
            $s_stmt->execute();
            $s_res = $s_stmt->get_result();

            if ($snk = $s_res->fetch_assoc()) {
                if ($snk['seller_id'] == $user_id) {
                    set_flash('warning', 'You cannot buy your own listed sneaker.');
                } else {
                    $c_chk = $conn->prepare("SELECT id, qty FROM sneaker_cart WHERE user_id = ? AND sneaker_id = ?");
                    $c_chk->bind_param("ii", $user_id, $sneaker_id);
                    $c_chk->execute();
                    $c_res = $c_chk->get_result();

                    if ($existing = $c_res->fetch_assoc()) {
                        $upd = $conn->prepare("UPDATE sneaker_cart SET qty = qty + 1 WHERE id = ?");
                        $upd->bind_param("i", $existing['id']);
                        $upd->execute();
                        $upd->close();
                    } else {
                        $ins_c = $conn->prepare("INSERT INTO sneaker_cart (user_id, sneaker_id, qty) VALUES (?, ?, 1)");
                        $ins_c->bind_param("ii", $user_id, $sneaker_id);
                        $ins_c->execute();
                        $ins_c->close();
                    }
                    $c_chk->close();

                    $del_w = $conn->prepare("DELETE FROM sneaker_wishlist WHERE user_id = ? AND sneaker_id = ?");
                    $del_w->bind_param("ii", $user_id, $sneaker_id);
                    $del_w->execute();
                    $del_w->close();

                    set_flash('success', '"' . $snk['title'] . '" moved to your sneaker cart.');
                }
            } else {
                set_flash('warning', 'This sneaker is no longer available.');
            }
            $s_stmt->close();
        }
    }

    $redirect = $_SERVER['HTTP_REFERER'] ?? 'wishlist.php';
    header("Location: " . $redirect);
    exit;
}

// Fetch Books & Gadgets Wishlist Items
$stmt = $conn->prepare("
    SELECT w.id AS wishlist_id, w.created_at AS saved_at,
           p.id AS product_id, p.title, p.price, p.mrp, p.condition, p.image, p.status,
           c.name AS category_name, u.name AS seller_name
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.seller_id = u.id
    WHERE w.user_id = ?
    ORDER BY w.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wishlist_items = $stmt->get_result();

// Fetch Sneaker Wishlist Items
$s_stmt = $conn->prepare("
    SELECT sw.id AS wishlist_id, sw.created_at AS saved_at,
           s.id AS sneaker_id, s.title, s.brand, s.size, s.colour, s.price, s.mrp, s.condition, s.image, s.status,
           u.name AS seller_name
    FROM sneaker_wishlist sw
    JOIN sneakers s ON sw.sneaker_id = s.id
    JOIN users u ON s.seller_id = u.id
    WHERE sw.user_id = ?
    ORDER BY sw.id DESC
");
$s_stmt->bind_param("i", $user_id);
$s_stmt->execute();
$sneaker_wishlist_items = $s_stmt->get_result();

$total_wishlist_count = $wishlist_items->num_rows + $sneaker_wishlist_items->num_rows;

$page_title = 'My Wishlist';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">My Wishlist</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-heart-fill text-danger me-2"></i>My Campus Wishlist</h1>
            <p class="text-muted small mb-0">Saved textbooks, electronics & sneakers you are planning to purchase.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="products.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-book me-1"></i> Browse Books
            </a>
            <a href="sneakers.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                <i class="bi bi-tag-fill me-1"></i> Sneaker Store
            </a>
        </div>
    </div>

    <?php if ($total_wishlist_count === 0): ?>
        <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-sm">
            <div class="display-2 text-danger mb-3 opacity-50"><i class="bi bi-heart"></i></div>
            <h4 class="fw-bold">Your Wishlist is Empty</h4>
            <p class="text-muted mb-4">Save books, gadgets and sneakers by clicking the heart button on any item card or details page.</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="products.php" class="btn btn-campus px-4">
                    <i class="bi bi-book me-1"></i> Explore Books & Gadgets
                </a>
                <a href="sneakers.php" class="btn btn-danger px-4">
                    <i class="bi bi-tag-fill me-1"></i> Explore Sneaker Store
                </a>
            </div>
        </div>
    <?php else: ?>

        <!-- 1. Books & Gadgets Wishlist -->
        <?php if ($wishlist_items->num_rows > 0): ?>
            <div class="mb-5">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <i class="bi bi-book-fill text-primary fs-5"></i>
                    <h4 class="fw-bold mb-0">Books & Gadgets Wishlist (<?= $wishlist_items->num_rows ?>)</h4>
                </div>
                <div class="row g-4">
                    <?php while ($item = $wishlist_items->fetch_assoc()): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="campus-card h-100">
                                <div class="card-img-wrapper">
                                    <a href="product-details.php?id=<?= (int)$item['product_id'] ?>">
                                        <img src="<?= product_img($item['image']) ?>" alt="<?= e($item['title']) ?>">
                                    </a>
                                    <div class="badge-top-left">
                                        <?= condition_badge($item['condition']) ?>
                                    </div>
                                    <div class="badge-top-right">
                                        <form action="wishlist.php" method="POST" class="m-0">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light rounded-circle shadow-sm text-danger p-1 px-2 border" title="Remove from wishlist">
                                                <i class="bi bi-heart-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div>
                                        <small class="text-muted d-block mb-1">
                                            <i class="bi bi-tag me-1"></i> <?= e($item['category_name']) ?>
                                        </small>
                                        <h5 class="card-title fw-bold fs-6 mb-2">
                                            <a href="product-details.php?id=<?= (int)$item['product_id'] ?>" class="text-dark text-decoration-none">
                                                <?= e($item['title']) ?>
                                            </a>
                                        </h5>
                                        <div class="d-flex align-items-baseline gap-2 mb-2">
                                            <span class="price-tag"><?= format_price($item['price']) ?></span>
                                            <?php if ($item['mrp']): ?>
                                                <span class="mrp-strike"><?= format_price($item['mrp']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="pt-2 border-top mt-auto d-flex gap-2">
                                        <form action="wishlist.php" method="POST" class="m-0 flex-grow-1">
                                            <input type="hidden" name="action" value="move_to_cart">
                                            <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                                            <button type="submit" class="btn btn-campus btn-sm w-100">
                                                <i class="bi bi-cart-plus me-1"></i> Move to Cart
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 2. Sneaker Store Wishlist -->
        <?php if ($sneaker_wishlist_items->num_rows > 0): ?>
            <div class="mb-5">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <i class="bi bi-tag-fill text-danger fs-5"></i>
                    <h4 class="fw-bold mb-0">Sneaker Store Wishlist (<?= $sneaker_wishlist_items->num_rows ?>)</h4>
                </div>
                <div class="row g-4">
                    <?php while ($snk = $sneaker_wishlist_items->fetch_assoc()): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="campus-card h-100">
                                <div class="card-img-wrapper">
                                    <a href="sneaker-details.php?id=<?= (int)$snk['sneaker_id'] ?>">
                                        <img src="<?= product_img($snk['image']) ?>" alt="<?= e($snk['title']) ?>">
                                    </a>
                                    <div class="badge-top-left">
                                        <?= condition_badge($snk['condition']) ?>
                                    </div>
                                    <div class="badge-top-right">
                                        <form action="wishlist.php" method="POST" class="m-0">
                                            <input type="hidden" name="action" value="remove_sneaker">
                                            <input type="hidden" name="sneaker_id" value="<?= (int)$snk['sneaker_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-light rounded-circle shadow-sm text-danger p-1 px-2 border" title="Remove from wishlist">
                                                <i class="bi bi-heart-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge bg-light text-primary border small"><?= e($snk['brand']) ?></span>
                                            <span class="badge bg-dark text-white small"><?= e($snk['size']) ?></span>
                                        </div>
                                        <h5 class="card-title fw-bold fs-6 mb-2">
                                            <a href="sneaker-details.php?id=<?= (int)$snk['sneaker_id'] ?>" class="text-dark text-decoration-none">
                                                <?= e($snk['title']) ?>
                                            </a>
                                        </h5>
                                        <div class="d-flex align-items-baseline gap-2 mb-2">
                                            <span class="price-tag"><?= format_price($snk['price']) ?></span>
                                            <?php if ($snk['mrp']): ?>
                                                <span class="mrp-strike"><?= format_price($snk['mrp']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="pt-2 border-top mt-auto d-flex gap-2">
                                        <form action="wishlist.php" method="POST" class="m-0 flex-grow-1">
                                            <input type="hidden" name="action" value="move_to_sneaker_cart">
                                            <input type="hidden" name="sneaker_id" value="<?= (int)$snk['sneaker_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm w-100">
                                                <i class="bi bi-cart-plus me-1"></i> Move to Sneaker Cart
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php
$stmt->close();
$s_stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>
