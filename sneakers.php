<?php
// sneakers.php - Campus Sneaker Store Catalog with Brand, Size, Colour & Price Filters (Zero JS)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Sneaker Store - Campus Kicks';

// Sanitize filter parameters
$search = trim($_GET['search'] ?? '');
$filter_brand = trim($_GET['brand'] ?? '');
$filter_size = trim($_GET['size'] ?? '');
$filter_colour = trim($_GET['colour'] ?? '');
$filter_condition = trim($_GET['condition'] ?? '');
$max_price = isset($_GET['budget']) && is_numeric($_GET['budget']) ? (float)$_GET['budget'] : 10000;
$sort = trim($_GET['sort'] ?? 'latest');

// Dynamic query builder with Prepared Statements
$where = ["s.status = 'available'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where[] = "(s.title LIKE ? OR s.brand LIKE ? OR s.colour LIKE ? OR s.description LIKE ?)";
    $s_term = '%' . $search . '%';
    $params[] = $s_term;
    $params[] = $s_term;
    $params[] = $s_term;
    $params[] = $s_term;
    $types .= "ssss";
}

if (!empty($filter_brand)) {
    $where[] = "s.brand = ?";
    $params[] = $filter_brand;
    $types .= "s";
}

if (!empty($filter_size)) {
    $where[] = "s.size = ?";
    $params[] = $filter_size;
    $types .= "s";
}

if (!empty($filter_colour)) {
    $where[] = "s.colour = ?";
    $params[] = $filter_colour;
    $types .= "s";
}

if (!empty($filter_condition)) {
    $where[] = "s.condition = ?";
    $params[] = $filter_condition;
    $types .= "s";
}

if ($max_price > 0 && $max_price < 10000) {
    $where[] = "s.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

// Sorting logic
$order_sql = "s.id DESC";
if ($sort === 'price_asc') {
    $order_sql = "s.price ASC";
} elseif ($sort === 'price_desc') {
    $order_sql = "s.price DESC";
}

$sql = "
    SELECT s.*, u.name AS seller_name, u.college AS seller_college
    FROM sneakers s
    JOIN users u ON s.seller_id = u.id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY " . $order_sql;

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$sneakers_res = $stmt->get_result();

// Get filter option lists
$all_brands = get_sneaker_brands($conn);
$all_sizes = get_sneaker_sizes($conn);
$all_colours = get_sneaker_colours($conn);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Sneaker Store Hero Banner (Pure CSS & Bootstrap) -->
<div class="container my-4">
    <div class="p-4 p-md-5 rounded-4 text-white shadow-sm position-relative overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #0284c7 100%); border-bottom: 4px solid var(--campus-yellow);">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-warning text-dark fw-bold px-3 py-2 text-uppercase mb-3">
                    <i class="bi bi-tag-fill me-1"></i> Campus Streetwear & Kicks
                </span>
                <h1 class="display-5 fw-bold text-white mb-2">Campus Sneaker Store</h1>
                <p class="lead text-light mb-4">
                    Grab pre-loved & mint condition sneakers directly from college peers. Nike, Adidas, Jordans, Converse & more at genuine student discounts!
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="sell-sneaker.php" class="btn btn-campus-yellow btn-lg px-4 fw-bold">
                        <i class="bi bi-plus-circle me-1"></i> Sell Your Sneakers
                    </a>
                    <a href="sneaker-cart.php" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-cart3 me-1"></i> View Sneaker Cart
                    </a>
                </div>
            </div>
            <div class="col-lg-4 d-none d-lg-block text-center">
                <div class="display-1 text-warning opacity-75">
                    <i class="bi bi-tag-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Sneaker Store</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="h4 fw-bold mb-1">Available Campus Kicks</h2>
            <p class="text-muted small mb-0">Browse authenticated student-listed sneakers with in-person campus inspection.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary px-3 py-2"><?= $sneakers_res->num_rows ?> <?= ($sneakers_res->num_rows == 1) ? 'sneaker' : 'sneakers' ?> listed</span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Filter Sidebar -->
        <div class="col-lg-3 col-md-4">
            <div class="filter-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-funnel-fill text-primary me-1"></i> Filters</h5>
                    <?php if (!empty($search) || !empty($filter_brand) || !empty($filter_size) || !empty($filter_colour) || !empty($filter_condition) || $max_price < 10000): ?>
                        <a href="sneakers.php" class="small text-danger text-decoration-none fw-semibold">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    <?php endif; ?>
                </div>

                <form action="sneakers.php" method="GET">
                    <!-- 1. Search Query -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Sneaker Search</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="e.g. Air Force, Superstar..." value="<?= e($search) ?>">
                    </div>

                    <!-- 2. Brand Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Brand</label>
                        <select name="brand" class="form-select form-select-sm">
                            <option value="">All Brands</option>
                            <?php foreach ($all_brands as $b): ?>
                                <option value="<?= e($b) ?>" <?= ($filter_brand === $b) ? 'selected' : '' ?>><?= e($b) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 3. Shoe Size Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Shoe Size (UK)</label>
                        <select name="size" class="form-select form-select-sm">
                            <option value="">All Sizes</option>
                            <?php foreach ($all_sizes as $s): ?>
                                <option value="<?= e($s) ?>" <?= ($filter_size === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 4. Colour Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Colour</label>
                        <select name="colour" class="form-select form-select-sm">
                            <option value="">All Colours</option>
                            <?php foreach ($all_colours as $col): ?>
                                <option value="<?= e($col) ?>" <?= ($filter_colour === $col) ? 'selected' : '' ?>><?= e($col) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 5. Condition Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Condition</label>
                        <select name="condition" class="form-select form-select-sm">
                            <option value="">All Conditions</option>
                            <option value="New" <?= ($filter_condition === 'New') ? 'selected' : '' ?>>New / Deadstock</option>
                            <option value="Like New" <?= ($filter_condition === 'Like New') ? 'selected' : '' ?>>Like New (Worn < 3 times)</option>
                            <option value="Good" <?= ($filter_condition === 'Good') ? 'selected' : '' ?>>Good (Clean, normal wear)</option>
                            <option value="Fair" <?= ($filter_condition === 'Fair') ? 'selected' : '' ?>>Fair (Noticeable creasing)</option>
                        </select>
                    </div>

                    <!-- 6. Budget Price Filter -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase">Budget Cap (₹)</label>
                        <select name="budget" class="form-select form-select-sm">
                            <option value="10000">Any Price</option>
                            <option value="1500" <?= ($max_price == 1500) ? 'selected' : '' ?>>Under ₹1,500</option>
                            <option value="2500" <?= ($max_price == 2500) ? 'selected' : '' ?>>Under ₹2,500</option>
                            <option value="4000" <?= ($max_price == 4000) ? 'selected' : '' ?>>Under ₹4,000</option>
                        </select>
                    </div>

                    <!-- 7. Sort By -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase">Sort By</label>
                        <select name="sort" class="form-select form-select-sm">
                            <option value="latest" <?= ($sort === 'latest') ? 'selected' : '' ?>>Newest Drops</option>
                            <option value="price_asc" <?= ($sort === 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_desc" <?= ($sort === 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-campus btn-sm w-100 fw-bold">
                        <i class="bi bi-funnel me-1"></i> Apply Sneaker Filters
                    </button>
                </form>
            </div>
        </div>

        <!-- Sneakers Grid -->
        <div class="col-lg-9 col-md-8">
            <?php if ($sneakers_res->num_rows === 0): ?>
                <div class="text-center py-5 bg-white rounded-4 border p-4 shadow-sm">
                    <div class="display-1 text-muted mb-3"><i class="bi bi-tag"></i></div>
                    <h4 class="fw-bold">No Sneakers Found</h4>
                    <p class="text-muted">No kicks matched your chosen brand, size, or colour filters. Try resetting the filters.</p>
                    <a href="sneakers.php" class="btn btn-campus btn-sm px-4 mt-2">View All Sneakers</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php while ($snk = $sneakers_res->fetch_assoc()): ?>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="campus-card h-100">
                                <div class="card-img-wrapper">
                                    <a href="sneaker-details.php?id=<?= (int)$snk['id'] ?>" class="d-flex align-items-center justify-content-center w-100 h-100">
                                        <img src="<?= product_img($snk['image']) ?>" alt="<?= e($snk['title']) ?>">
                                    </a>
                                    <!-- Badges -->
                                    <div class="badge-top-left">
                                        <?= condition_badge($snk['condition']) ?>
                                    </div>
                                    <div class="badge-top-right d-flex align-items-center gap-1">
                                        <span class="badge bg-dark text-white fw-bold shadow-sm">
                                            <?= e($snk['size']) ?>
                                        </span>
                                        <form action="wishlist.php" method="POST" class="m-0">
                                            <input type="hidden" name="action" value="toggle_sneaker">
                                            <input type="hidden" name="sneaker_id" value="<?= (int)$snk['id'] ?>">
                                            <button type="submit" class="btn-card-wishlist" title="Save to Wishlist">
                                                <i class="bi <?= (is_logged_in() && is_in_sneaker_wishlist($conn, current_user_id(), $snk['id'])) ? 'bi-heart-fill text-danger' : 'bi-heart' ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="card-body p-3 d-flex flex-column justify-content-between flex-grow-1">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge bg-light text-primary border fw-bold small">
                                                <?= e($snk['brand']) ?>
                                            </span>
                                            <small class="text-muted"><i class="bi bi-palette me-1"></i><?= e($snk['colour']) ?></small>
                                        </div>
                                        <h5 class="card-title fw-bold fs-6 mt-2 mb-2 card-title-clamp">
                                            <a href="sneaker-details.php?id=<?= (int)$snk['id'] ?>" class="text-dark text-decoration-none">
                                                <?= e($snk['title']) ?>
                                            </a>
                                        </h5>
                                        <p class="text-muted small mb-3 card-desc-clamp">
                                            <?= e(substr($snk['description'], 0, 85)) ?>...
                                        </p>
                                    </div>

                                    <div class="pt-2 border-top mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <span class="price-tag"><?= format_price($snk['price']) ?></span>
                                                <?php if ($snk['mrp']): ?>
                                                    <span class="mrp-strike"><?= format_price($snk['mrp']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted"><i class="bi bi-person me-1"></i><?= e($snk['seller_name']) ?></small>
                                        </div>

                                        <div class="card-action-btns">
                                            <a href="sneaker-details.php?id=<?= (int)$snk['id'] ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">
                                                <i class="bi bi-eye"></i> Details
                                            </a>
                                            <form action="wishlist.php" method="POST" class="m-0">
                                                <input type="hidden" name="action" value="toggle_sneaker">
                                                <input type="hidden" name="sneaker_id" value="<?= (int)$snk['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm btn-icon-only" title="Save to Wishlist">
                                                    <i class="bi <?= (is_logged_in() && is_in_sneaker_wishlist($conn, current_user_id(), $snk['id'])) ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                                </button>
                                            </form>
                                            <form action="sneaker-cart.php" method="POST" class="m-0 flex-grow-1">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="sneaker_id" value="<?= (int)$snk['id'] ?>">
                                                <input type="hidden" name="qty" value="1">
                                                <button type="submit" class="btn btn-campus btn-sm w-100">
                                                    <i class="bi bi-cart-plus"></i> Add
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>

