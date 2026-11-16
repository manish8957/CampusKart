<?php
// product-details.php - Product Detail View
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$product_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    set_flash('danger', 'Invalid product requested.');
    header("Location: products.php");
    exit;
}

// Fetch product details using prepared statement
$stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name, c.id AS cat_id, 
           u.name AS seller_name, u.email AS seller_email, u.phone AS seller_phone, u.college AS seller_college, u.hostel AS seller_hostel
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.seller_id = u.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    set_flash('warning', 'Product not found or has been sold.');
    header("Location: products.php");
    exit;
}

$product = $result->fetch_assoc();
$stmt->close();

$page_title = $product['title'];

// Fetch related items from the same category
$rel_stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name, u.name AS seller_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.seller_id = u.id
    WHERE p.category_id = ? AND p.id != ? AND p.status = 'available'
    ORDER BY p.id DESC
    LIMIT 3
");
$rel_stmt->bind_param("ii", $product['category_id'], $product_id);
$rel_stmt->execute();
$related_products = $rel_stmt->get_result();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php" class="text-decoration-none">Products</a></li>
            <li class="breadcrumb-item"><a href="products.php?cat=<?= (int)$product['cat_id'] ?>" class="text-decoration-none"><?= e($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width: 300px;"><?= e($product['title']) ?></li>
        </ol>
    </nav>

    <!-- Main Product Card -->
    <div class="bg-white rounded-4 border p-4 p-lg-5 shadow-sm mb-5">
        <div class="row g-5">
            <!-- Left: Product Image & Badges -->
            <div class="col-lg-6">
                <div class="detail-img-box position-relative text-center">
                    <img src="<?= product_img($product['image']) ?>" alt="<?= e($product['title']) ?>" class="img-fluid">
                    <div class="position-absolute top-0 start-0 m-3">
                        <?= condition_badge($product['condition']) ?>
                    </div>
                    <div class="position-absolute top-0 end-0 m-3 d-flex align-items-center gap-2">
                        <?php if ($product['mrp'] && $product['mrp'] > $product['price']): ?>
                            <span class="badge bg-danger shadow-sm px-2 py-1 small">
                                <?= discount_percentage($product['mrp'], $product['price']) ?>% OFF
                            </span>
                        <?php endif; ?>
                        <!-- Floating Image Wishlist Button -->
                        <form action="wishlist.php" method="POST" class="m-0">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                            <button type="submit" class="btn btn-light rounded-circle shadow border p-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Save to Wishlist">
                                <i class="bi <?= (is_logged_in() && is_in_wishlist($conn, current_user_id(), $product['id'])) ? 'bi-heart-fill text-danger' : 'bi-heart text-secondary' ?> fs-5"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Campus Reassurance Box -->
                <div class="mt-4 p-3 bg-light rounded-3 border small">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-shield-check text-success fs-5"></i>
                        <span class="fw-bold text-dark">CampusKart Inspection Guarantee</span>
                    </div>
                    <p class="text-muted mb-0">
                        Always meet the seller at an open campus hub (library, canteen or college gate). Inspect pages or test electronics before handing over cash!
                    </p>
                </div>
            </div>

            <!-- Right: Product Info & Actions -->
            <div class="col-lg-6">
                <!-- Category & Status -->
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-1">
                        <?= e($product['category_name']) ?>
                    </span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-1">
                        <i class="bi bi-dot"></i> In Stock & Ready
                    </span>
                </div>

                <h1 class="h2 fw-bold text-dark mb-3"><?= e($product['title']) ?></h1>

                <!-- Price Box -->
                <div class="d-flex align-items-baseline gap-3 mb-4 p-3 bg-light rounded-3 border">
                    <span class="display-6 fw-bold text-primary"><?= format_price($product['price']) ?></span>
                    <?php if ($product['mrp']): ?>
                        <span class="text-muted text-decoration-line-through fs-5"><?= format_price($product['mrp']) ?></span>
                        <span class="badge bg-success">Save <?= format_price($product['mrp'] - $product['price']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Condition Detail -->
                <div class="mb-4">
                    <h6 class="fw-bold mb-2">Condition: <?= condition_badge($product['condition']) ?></h6>
                    <div class="p-3 rounded-3 border" style="background-color: #fefce8; border-color: #fef08a !important;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle-fill text-warning fs-5"></i>
                            <div>
                                <?php if (strtolower($product['condition']) === 'new'): ?>
                                    <strong>Untouched & Unused:</strong> Completely brand new condition, no marks, no folded pages.
                                <?php elseif (strtolower($product['condition']) === 'like new'): ?>
                                    <strong>Like New:</strong> Barely used for a few lectures, crisp pages, all accessories intact.
                                <?php elseif (strtolower($product['condition']) === 'good'): ?>
                                    <strong>Good Condition:</strong> Genuine student use, minor pencil underlines/notes, completely readable.
                                <?php else: ?>
                                    <strong>Fair Condition:</strong> Shows study wear, highlighted passages, cover creased but 100% complete.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-2">Item Description</h6>
                    <p class="text-secondary" style="line-height: 1.7;">
                        <?= nl2br(e($product['description'])) ?>
                    </p>
                </div>

                <!-- Verified Student Seller Box -->
                <div class="seller-box mb-4">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-person-badge-fill text-primary me-2"></i>Listed by Peer Student</h6>
                    <div class="d-flex align-items-center gap-3">
                        <div class="brand-badge bg-primary text-white">
                            <?= strtoupper(substr($product['seller_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold text-dark"><?= e($product['seller_name']) ?></div>
                            <small class="text-muted d-block">
                                <i class="bi bi-building me-1"></i> <?= e($product['seller_college']) ?>
                                <?php if (!empty($product['seller_hostel'])): ?>
                                    • <?= e($product['seller_hostel']) ?>
                                <?php endif; ?>
                            </small>
                            <small class="text-primary fw-semibold">
                                <i class="bi bi-telephone-fill me-1"></i> <?= e($product['seller_phone']) ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Add to Cart / Order Action -->
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <form action="cart.php" method="POST" class="m-0 flex-grow-1 d-flex gap-2">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <select name="qty" class="form-select form-select-sm" style="width: 75px;" title="Quantity">
                            <option value="1" selected>1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                        </select>
                        <button type="submit" class="btn btn-campus btn-lg flex-grow-1">
                            <i class="bi bi-cart-plus-fill me-1"></i> Add to Cart
                        </button>
                    </form>
                    <a href="checkout.php?buy_now=<?= (int)$product['id'] ?>" class="btn btn-campus-yellow btn-lg flex-grow-1">
                        <i class="bi bi-lightning-charge-fill me-1"></i> Buy Now
                    </a>
                </div>

                <!-- Wishlist Button (Full Width, Prominent) -->
                <div class="mb-3">
                    <form action="wishlist.php" method="POST" class="m-0">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger w-100 py-2 d-flex align-items-center justify-content-center gap-2 fw-semibold rounded-3 shadow-sm" title="Save to Wishlist">
                            <i class="bi <?= (is_logged_in() && is_in_wishlist($conn, current_user_id(), $product['id'])) ? 'bi-heart-fill fs-5 text-danger' : 'bi-heart fs-5' ?>"></i>
                            <span><?= (is_logged_in() && is_in_wishlist($conn, current_user_id(), $product['id'])) ? 'Saved in Your Wishlist' : 'Add to Wishlist' ?></span>
                        </button>
                    </form>
                </div>

                <div class="d-flex align-items-center gap-3 text-muted small mt-3">
                    <span><i class="bi bi-truck me-1"></i> Campus Handover (Free)</span>
                    <span>•</span>
                    <span><i class="bi bi-shield-lock me-1"></i> Cash or UPI on Pickup</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if ($related_products->num_rows > 0): ?>
        <div class="my-5">
            <h4 class="fw-bold mb-4">More in <?= e($product['category_name']) ?></h4>
            <div class="row g-4">
                <?php while ($rel = $related_products->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="campus-card h-100">
                            <div class="card-img-wrapper">
                                <a href="product-details.php?id=<?= (int)$rel['id'] ?>">
                                    <img src="<?= product_img($rel['image']) ?>" alt="<?= e($rel['title']) ?>">
                                </a>
                                <div class="badge-top-left">
                                    <?= condition_badge($rel['condition']) ?>
                                </div>
                            </div>
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <h6 class="card-title fw-bold">
                                        <a href="product-details.php?id=<?= (int)$rel['id'] ?>" class="text-decoration-none text-dark">
                                            <?= e($rel['title']) ?>
                                        </a>
                                    </h6>
                                    <p class="text-muted small"><?= e(substr($rel['description'], 0, 70)) ?>...</p>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <span class="price-tag"><?= format_price($rel['price']) ?></span>
                                    <a href="product-details.php?id=<?= (int)$rel['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        View Item
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$rel_stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>

