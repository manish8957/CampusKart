<?php
// products.php - Products Catalog with Search, Category Filter, Condition Filter & Budget Filter
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Campus Store - Books & Gadgets';

// Get and sanitize filter parameters
$search = trim($_GET['search'] ?? '');
$category_id = isset($_GET['cat']) && is_numeric($_GET['cat']) ? (int)$_GET['cat'] : null;
$condition_filter = trim($_GET['condition'] ?? '');
$max_price = isset($_GET['budget']) && is_numeric($_GET['budget']) ? (float)$_GET['budget'] : 5000;
$sort = trim($_GET['sort'] ?? 'latest');

// Build dynamic SQL query safely with prepared statements
$where_clauses = ["p.status = 'available'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "(p.title LIKE ? OR p.description LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($category_id) {
    $where_clauses[] = "p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if (!empty($condition_filter)) {
    $where_clauses[] = "p.condition = ?";
    $params[] = $condition_filter;
    $types .= "s";
}

if ($max_price > 0 && $max_price < 5000) {
    $where_clauses[] = "p.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

// Sorting logic
$order_sql = "p.id DESC";
if ($sort === 'price_asc') {
    $order_sql = "p.price ASC";
} elseif ($sort === 'price_desc') {
    $order_sql = "p.price DESC";
}

$sql = "
    SELECT p.*, c.name AS category_name, u.name AS seller_name, u.college AS seller_college
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.seller_id = u.id
    WHERE " . implode(" AND ", $where_clauses) . "
    ORDER BY " . $order_sql;

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products_result = $stmt->get_result();

// Fetch categories for sidebar
$all_categories = get_categories($conn);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb & Header -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Products Catalog</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1">Campus Marketplace</h1>
            <p class="text-muted small mb-0">
                Browse student-listed textbooks, calculators, electronics & study bundles.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small"><?= $products_result->num_rows ?> <?= ($products_result->num_rows == 1) ? 'item' : 'items' ?> found</span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Filter Sidebar -->
        <div class="col-lg-3 col-md-4">
            <div class="filter-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-funnel-fill text-primary me-1"></i> Filters</h5>
                    <?php if (!empty($search) || $category_id || !empty($condition_filter) || $max_price < 5000): ?>
                        <a href="products.php" class="small text-danger text-decoration-none fw-semibold">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    <?php endif; ?>
                </div>

                <form action="products.php" method="GET" id="filterForm">
                    <!-- 1. Search Query -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase">Keyword Search</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="e.g. Grewal, Casio, C++..." value="<?= e($search) ?>">
                            <button class="btn btn-outline-secondary btn-sm" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- 2. Budget Filter Slider (Innovative Touch) -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="budgetRange" class="form-label small fw-bold text-uppercase mb-0">Budget Filter</label>
                            <span class="budget-badge" id="budgetDisplay">Max: ₹<?= (int)$max_price ?></span>
                        </div>
                        <input type="range" class="form-range" min="100" max="5000" step="50" id="budgetRange" name="budget" value="<?= (int)$max_price ?>">
                        <div class="d-flex justify-content-between text-muted" style="font-size: 0.75rem;">
                            <span>₹100</span>
                            <span>₹2500</span>
                            <span>₹5000+</span>
                        </div>
                        <!-- Quick Budget Links (Pure HTML GET) -->
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <a href="products.php?budget=300<?= $category_id ? '&cat='.$category_id : '' ?>" class="badge bg-light text-dark border text-decoration-none">Under ₹300</a>
                            <a href="products.php?budget=500<?= $category_id ? '&cat='.$category_id : '' ?>" class="badge bg-light text-dark border text-decoration-none">Under ₹500</a>
                            <a href="products.php?budget=1000<?= $category_id ? '&cat='.$category_id : '' ?>" class="badge bg-light text-dark border text-decoration-none">Under ₹1000</a>
                        </div>
                    </div>

                    <!-- 3. Category Filter -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase">Category</label>
                        <div class="d-flex flex-column gap-1">
                            <a href="products.php<?= !empty($search) ? '?search='.urlencode($search) : '' ?>" class="text-decoration-none small py-1 px-2 rounded <?= empty($category_id) ? 'bg-primary text-white fw-bold' : 'text-dark' ?>">
                                All Categories
                            </a>
                            <?php foreach ($all_categories as $cat): ?>
                                <a href="products.php?cat=<?= (int)$cat['id'] ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?><?= !empty($condition_filter) ? '&condition='.urlencode($condition_filter) : '' ?>" 
                                   class="text-decoration-none small py-1 px-2 rounded d-flex justify-content-between align-items-center <?= ($category_id === (int)$cat['id']) ? 'bg-primary text-white fw-bold' : 'text-dark' ?>">
                                    <span><i class="bi <?= e($cat['icon']) ?> me-1"></i> <?= e($cat['name']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 4. Condition Filter (Innovative Touch) -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase">Item Condition</label>
                        <select name="condition" class="form-select form-select-sm">
                            <option value="">All Conditions</option>
                            <option value="New" <?= ($condition_filter === 'New') ? 'selected' : '' ?>>New / Unused</option>
                            <option value="Like New" <?= ($condition_filter === 'Like New') ? 'selected' : '' ?>>Like New (Minimal Wear)</option>
                            <option value="Good" <?= ($condition_filter === 'Good') ? 'selected' : '' ?>>Good (Minor notes/intact)</option>
                            <option value="Fair" <?= ($condition_filter === 'Fair') ? 'selected' : '' ?>>Fair (Noticeable wear)</option>
                        </select>
                    </div>

                    <!-- 5. Sort By -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Sort By</label>
                        <select name="sort" class="form-select form-select-sm">
                            <option value="latest" <?= ($sort === 'latest') ? 'selected' : '' ?>>Newest First</option>
                            <option value="price_asc" <?= ($sort === 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_desc" <?= ($sort === 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-campus btn-sm w-100 mt-2">
                        <i class="bi bi-funnel me-1"></i> Apply Filters
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Products Grid -->
        <div class="col-lg-9 col-md-8">
            <!-- Active Filter Badges -->
            <?php if (!empty($search) || $category_id || !empty($condition_filter) || $max_price < 5000): ?>
                <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                    <span class="small text-muted">Applied filters:</span>
                    <?php if (!empty($search)): ?>
                        <span class="badge bg-secondary">Search: "<?= e($search) ?>"</span>
                    <?php endif; ?>
                    <?php if ($category_id): ?>
                        <span class="badge bg-primary">Category ID: <?= (int)$category_id ?></span>
                    <?php endif; ?>
                    <?php if (!empty($condition_filter)): ?>
                        <span class="badge bg-info text-dark">Condition: <?= e($condition_filter) ?></span>
                    <?php endif; ?>
                    <?php if ($max_price < 5000): ?>
                        <span class="badge bg-warning text-dark">Max: ₹<?= (int)$max_price ?></span>
                    <?php endif; ?>
                    <a href="products.php" class="small text-danger text-decoration-none ms-2">Clear all</a>
                </div>
            <?php endif; ?>

            <?php if ($products_result->num_rows === 0): ?>
                <!-- Empty State -->
                <div class="text-center py-5 bg-white rounded-4 border p-4">
                    <div class="display-1 text-muted mb-3"><i class="bi bi-search"></i></div>
                    <h4 class="fw-bold">No Products Found</h4>
                    <p class="text-muted">No items matched your current filters or search terms. Try broadening your budget or search words.</p>
                    <a href="products.php" class="btn btn-campus btn-sm px-4 mt-2">
                        View All Items
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php while ($prod = $products_result->fetch_assoc()): ?>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="campus-card h-100">
                                <!-- Card Image -->
                                <div class="card-img-wrapper">
                                    <a href="product-details.php?id=<?= (int)$prod['id'] ?>" class="d-flex align-items-center justify-content-center w-100 h-100">
                                        <img src="<?= product_img($prod['image']) ?>" alt="<?= e($prod['title']) ?>">
                                    </a>
                                    <!-- Condition Badge -->
                                    <div class="badge-top-left">
                                        <?= condition_badge($prod['condition']) ?>
                                    </div>
                                    <div class="badge-top-right d-flex align-items-center gap-1">
                                        <?php if ($prod['mrp'] && $prod['mrp'] > $prod['price']): ?>
                                            <span class="badge bg-danger shadow-sm">
                                                <?= discount_percentage($prod['mrp'], $prod['price']) ?>% OFF
                                            </span>
                                        <?php endif; ?>
                                        <form action="wishlist.php" method="POST" class="m-0">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">
                                            <button type="submit" class="btn-card-wishlist" title="Save to Wishlist">
                                                <i class="bi <?= (is_logged_in() && is_in_wishlist($conn, current_user_id(), $prod['id'])) ? 'bi-heart-fill text-danger' : 'bi-heart' ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Card Body -->
                                <div class="card-body p-3 d-flex flex-column justify-content-between flex-grow-1">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge bg-light text-secondary border small">
                                                <i class="bi bi-tag-fill me-1 text-primary"></i> <?= e($prod['category_name']) ?>
                                            </span>
                                            <small class="text-muted"><i class="bi bi-person me-1"></i> <?= e($prod['seller_name']) ?></small>
                                        </div>
                                        <h5 class="card-title fw-bold fs-6 mt-2 mb-2 card-title-clamp">
                                            <a href="product-details.php?id=<?= (int)$prod['id'] ?>" class="text-decoration-none text-dark">
                                                <?= e($prod['title']) ?>
                                            </a>
                                        </h5>
                                        <p class="text-muted small mb-3 card-desc-clamp">
                                            <?= e(substr($prod['description'], 0, 85)) ?>...
                                        </p>
                                    </div>

                                    <!-- Price & Actions -->
                                    <div class="pt-2 border-top mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <span class="price-tag"><?= format_price($prod['price']) ?></span>
                                                <?php if ($prod['mrp']): ?>
                                                    <span class="mrp-strike"><?= format_price($prod['mrp']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="card-action-btns">
                                            <a href="product-details.php?id=<?= (int)$prod['id'] ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">
                                                <i class="bi bi-eye"></i> Details
                                            </a>
                                            <form action="wishlist.php" method="POST" class="m-0">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm btn-icon-only" title="Save to Wishlist">
                                                    <i class="bi <?= (is_logged_in() && is_in_wishlist($conn, current_user_id(), $prod['id'])) ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                                </button>
                                            </form>
                                            <form action="cart.php" method="POST" class="m-0 flex-grow-1">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="product_id" value="<?= (int)$prod['id'] ?>">
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

