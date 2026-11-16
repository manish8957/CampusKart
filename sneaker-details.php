<?php
// sneaker-details.php - Sneaker Item Details (Zero JavaScript)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$sneaker_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sneaker_id <= 0) {
    set_flash('danger', 'Invalid sneaker requested.');
    header("Location: sneakers.php");
    exit;
}

// Fetch sneaker details with prepared statement
$stmt = $conn->prepare("
    SELECT s.*, u.name AS seller_name, u.email AS seller_email, u.phone AS seller_phone, u.college AS seller_college, u.hostel AS seller_hostel
    FROM sneakers s
    JOIN users u ON s.seller_id = u.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $sneaker_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    set_flash('warning', 'Sneaker not found or already sold.');
    header("Location: sneakers.php");
    exit;
}

$sneaker = $res->fetch_assoc();
$stmt->close();

$page_title = $sneaker['title'];

// Fetch related kicks from the same brand
$rel_stmt = $conn->prepare("
    SELECT * FROM sneakers
    WHERE brand = ? AND id != ? AND status = 'available'
    LIMIT 3
");
$rel_stmt->bind_param("si", $sneaker['brand'], $sneaker_id);
$rel_stmt->execute();
$related_sneakers = $rel_stmt->get_result();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="sneakers.php" class="text-decoration-none">Sneaker Store</a></li>
            <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width: 300px;"><?= e($sneaker['title']) ?></li>
        </ol>
    </nav>

    <!-- Sneaker Card Details -->
    <div class="bg-white rounded-4 border p-4 p-lg-5 shadow-sm mb-5">
        <div class="row g-5">
            <!-- Left: Product Image & Badges -->
            <div class="col-lg-6">
                <div class="detail-img-box position-relative text-center">
                    <img src="<?= product_img($sneaker['image']) ?>" alt="<?= e($sneaker['title']) ?>" class="img-fluid">
                    <div class="position-absolute top-0 start-0 m-3">
                        <?= condition_badge($sneaker['condition']) ?>
                    </div>
                    <div class="position-absolute top-0 end-0 m-3 d-flex align-items-center gap-2">
                        <?php if ($sneaker['mrp'] && $sneaker['mrp'] > $sneaker['price']): ?>
                            <span class="badge bg-danger shadow-sm px-2 py-1 small">
                                <?= discount_percentage($sneaker['mrp'], $sneaker['price']) ?>% OFF
                            </span>
                        <?php endif; ?>
                        <!-- Floating Image Wishlist Button -->
                        <form action="wishlist.php" method="POST" class="m-0">
                            <input type="hidden" name="action" value="toggle_sneaker">
                            <input type="hidden" name="sneaker_id" value="<?= (int)$sneaker['id'] ?>">
                            <button type="submit" class="btn btn-light rounded-circle shadow border p-0 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Save to Wishlist">
                                <i class="bi <?= (is_logged_in() && is_in_sneaker_wishlist($conn, current_user_id(), $sneaker['id'])) ? 'bi-heart-fill text-danger' : 'bi-heart text-secondary' ?> fs-5"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Shoe Authenticity & Care Reassurance -->
                <div class="mt-4 p-3 bg-light rounded-3 border small">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-shield-check text-success fs-5"></i>
                        <span class="fw-bold text-dark">Campus Try-On & Fit Guarantee</span>
                    </div>
                    <p class="text-muted mb-0">
                        Always meet the student seller in person (canteen or hostel common room). Try on the sneakers for proper fit and check soles before paying!
                    </p>
                </div>
            </div>

            <!-- Right: Sneaker Specs & Actions -->
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-dark text-white px-3 py-1 fw-bold"><?= e($sneaker['brand']) ?></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-1">
                        Size: <?= e($sneaker['size']) ?>
                    </span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-1">
                        Colour: <?= e($sneaker['colour']) ?>
                    </span>
                </div>

                <h1 class="h2 fw-bold text-dark mb-3"><?= e($sneaker['title']) ?></h1>

                <!-- Price Box -->
                <div class="d-flex align-items-baseline gap-3 mb-4 p-3 bg-light rounded-3 border">
                    <span class="display-6 fw-bold text-primary"><?= format_price($sneaker['price']) ?></span>
                    <?php if ($sneaker['mrp']): ?>
                        <span class="text-muted text-decoration-line-through fs-5"><?= format_price($sneaker['mrp']) ?></span>
                        <span class="badge bg-success">Save <?= format_price($sneaker['mrp'] - $sneaker['price']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Quick Specs Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm small mb-0">
                        <tbody>
                            <tr>
                                <th class="bg-light" style="width: 35%;">Brand</th>
                                <td class="fw-bold"><?= e($sneaker['brand']) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Shoe Size</th>
                                <td class="fw-bold text-primary"><?= e($sneaker['size']) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Primary Colour</th>
                                <td><?= e($sneaker['colour']) ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Condition</th>
                                <td><?= condition_badge($sneaker['condition']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-2">Seller Notes & Description</h6>
                    <p class="text-secondary" style="line-height: 1.7;">
                        <?= nl2br(e($sneaker['description'])) ?>
                    </p>
                </div>

                <!-- Seller Info -->
                <div class="seller-box mb-4">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-person-badge-fill text-primary me-2"></i>Listed by Fellow Student</h6>
                    <div class="d-flex align-items-center gap-3">
                        <div class="brand-badge bg-primary text-white">
                            <?= strtoupper(substr($sneaker['seller_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold text-dark"><?= e($sneaker['seller_name']) ?></div>
                            <small class="text-muted d-block">
                                <i class="bi bi-building me-1"></i> <?= e($sneaker['seller_college']) ?>
                                <?php if (!empty($sneaker['seller_hostel'])): ?>
                                    • <?= e($sneaker['seller_hostel']) ?>
                                <?php endif; ?>
                            </small>
                            <small class="text-primary fw-semibold">
                                <i class="bi bi-telephone-fill me-1"></i> <?= e($sneaker['seller_phone']) ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Add to Sneaker Cart Form -->
                <form action="sneaker-cart.php" method="POST" class="mb-2">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="sneaker_id" value="<?= (int)$sneaker['id'] ?>">

                    <div class="d-grid gap-2 d-md-flex align-items-center">
                        <button type="submit" class="btn btn-campus btn-lg flex-grow-1">
                            <i class="bi bi-cart-plus-fill me-2"></i> Add to Sneaker Cart
                        </button>
                        <a href="sneaker-checkout.php?buy_now=<?= (int)$sneaker['id'] ?>" class="btn btn-campus-yellow btn-lg flex-grow-1">
                            <i class="bi bi-lightning-charge-fill me-1"></i> Buy Now
                        </a>
                    </div>
                </form>

                <!-- Sneaker Wishlist Button -->
                <div class="mb-3">
                    <form action="wishlist.php" method="POST" class="m-0">
                        <input type="hidden" name="action" value="toggle_sneaker">
                        <input type="hidden" name="sneaker_id" value="<?= (int)$sneaker['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger w-100 py-2 d-flex align-items-center justify-content-center gap-2 fw-semibold rounded-3 shadow-sm" title="Save to Wishlist">
                            <i class="bi <?= (is_logged_in() && is_in_sneaker_wishlist($conn, current_user_id(), $sneaker['id'])) ? 'bi-heart-fill fs-5 text-danger' : 'bi-heart fs-5' ?>"></i>
                            <span><?= (is_logged_in() && is_in_sneaker_wishlist($conn, current_user_id(), $sneaker['id'])) ? 'Sneakers Saved in Wishlist' : 'Add to Sneaker Wishlist' ?></span>
                        </button>
                    </form>
                </div>

                <div class="d-flex align-items-center gap-3 text-muted small mt-3">
                    <span><i class="bi bi-geo-alt me-1"></i> In-Person Campus Handover</span>
                    <span>•</span>
                    <span><i class="bi bi-shield-lock me-1"></i> Cash or UPI on Pickup</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Sneakers -->
    <?php if ($related_sneakers->num_rows > 0): ?>
        <div class="my-5">
            <h4 class="fw-bold mb-4">More <?= e($sneaker['brand']) ?> Kicks</h4>
            <div class="row g-4">
                <?php while ($rel = $related_sneakers->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="campus-card h-100">
                            <div class="card-img-wrapper">
                                <a href="sneaker-details.php?id=<?= (int)$rel['id'] ?>">
                                    <img src="<?= product_img($rel['image']) ?>" alt="<?= e($rel['title']) ?>">
                                </a>
                                <div class="badge-top-left">
                                    <?= condition_badge($rel['condition']) ?>
                                </div>
                                <div class="badge-top-right">
                                    <span class="badge bg-dark"><?= e($rel['size']) ?></span>
                                </div>
                            </div>
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <h6 class="card-title fw-bold">
                                        <a href="sneaker-details.php?id=<?= (int)$rel['id'] ?>" class="text-dark text-decoration-none">
                                            <?= e($rel['title']) ?>
                                        </a>
                                    </h6>
                                    <span class="price-tag"><?= format_price($rel['price']) ?></span>
                                </div>
                                <div class="pt-2 border-top mt-2">
                                    <a href="sneaker-details.php?id=<?= (int)$rel['id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                                        View Sneaker
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

