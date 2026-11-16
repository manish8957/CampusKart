<?php
// canteen.php - Campus Canteen Pre-order Catalog (Zero JavaScript)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Capture and sanitize filters
$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$food_type = trim($_GET['food_type'] ?? '');
$sort = trim($_GET['sort'] ?? 'default');

// Valid categories
$valid_categories = ['Snacks', 'Meals', 'Beverages', 'Fast Food'];
if (!in_array($category, $valid_categories)) {
    $category = '';
}

// Build query
$where_clauses = ["is_available = 1"];
$params = [];
$types = "";

if ($search !== '') {
    $where_clauses[] = "(title LIKE ? OR description LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($category !== '') {
    $where_clauses[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($food_type === 'Veg' || $food_type === 'Non-Veg') {
    $where_clauses[] = "food_type = ?";
    $params[] = $food_type;
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Sorting
$order_by = "id ASC";
if ($sort === 'price_asc') {
    $order_by = "price ASC";
} elseif ($sort === 'price_desc') {
    $order_by = "price DESC";
} elseif ($sort === 'title') {
    $order_by = "title ASC";
}

$sql = "SELECT * FROM canteen_items WHERE {$where_sql} ORDER BY {$order_by}";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$items_result = $stmt->get_result();

// Get items in user's canteen tray if logged in
$tray_item_counts = [];
$tray_total_qty = 0;
$tray_total_amount = 0.00;
if (is_logged_in()) {
    $u_id = current_user_id();
    $tray_q = $conn->prepare("
        SELECT c.item_id, c.qty, ci.price 
        FROM canteen_cart c 
        JOIN canteen_items ci ON c.item_id = ci.id 
        WHERE c.user_id = ?
    ");
    $tray_q->bind_param("i", $u_id);
    $tray_q->execute();
    $tray_res = $tray_q->get_result();
    while ($tr = $tray_res->fetch_assoc()) {
        $tray_item_counts[$tr['item_id']] = (int)$tr['qty'];
        $tray_total_qty += (int)$tr['qty'];
        $tray_total_amount += ((float)$tr['price'] * (int)$tr['qty']);
    }
    $tray_q->close();
}

$page_title = 'Campus Canteen Pre-order';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Campus Canteen</li>
        </ol>
    </nav>

    <!-- Canteen Hero Banner -->
    <div class="canteen-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-warning text-dark fw-bold px-3 py-1 mb-2 text-uppercase" style="letter-spacing: 0.5px;">
                    <i class="bi bi-lightning-charge-fill me-1"></i> Express Campus Pre-order
                </span>
                <h1 class="display-6 fw-bold text-white mb-2">Campus Canteen Food Hub</h1>
                <p class="text-white text-opacity-90 lead mb-3 fs-6">
                    Beat the 1:00 PM lunch queue! Pre-order your hot meals, quick snacks, and cutting chai. Pick your time slot and collect instantly with your express token.
                </p>
                <div class="d-flex flex-wrap gap-2 small">
                    <span class="badge bg-white text-dark border px-3 py-2 fw-semibold shadow-sm" style="color: #000000 !important;">
                        <i class="bi bi-clock-history me-1 text-warning"></i> Express Pickup Slots
                    </span>
                    <span class="badge bg-white text-dark border px-3 py-2 fw-semibold shadow-sm" style="color: #000000 !important;">
                        <i class="bi bi-ticket-perforated me-1 text-warning"></i> Instant Food Token
                    </span>
                    <span class="badge bg-white text-dark border px-3 py-2 fw-semibold shadow-sm" style="color: #000000 !important;">
                        <i class="bi bi-cash-stack me-1 text-warning"></i> Pay at Counter or UPI
                    </span>
                </div>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
                <div class="p-3 bg-white bg-opacity-10 rounded-4 border border-white border-opacity-20 text-white">
                    <div class="display-5 text-warning mb-2"><i class="bi bi-cup-hot-fill"></i></div>
                    <div class="fw-bold fs-5">Express Pickups Open</div>
                    <small class="text-white text-opacity-75">Counters 1, 2 & 3 operating</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Tray Notification Callout (if items in tray) -->
    <?php if ($tray_total_qty > 0): ?>
        <div class="alert alert-warning border-warning shadow-sm rounded-4 p-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-warning text-dark rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-cart-check-fill fs-5"></i>
                </div>
                <div>
                    <strong class="text-dark d-block">Items in your Canteen Tray: <?= $tray_total_qty ?> item<?= $tray_total_qty > 1 ? 's' : '' ?></strong>
                    <span class="text-muted small">Tray Total: <strong class="text-primary"><?= format_price($tray_total_amount) ?></strong></span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="canteen-cart.php" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                    View Tray
                </a>
                <a href="canteen-checkout.php" class="btn btn-warning text-dark fw-bold btn-sm rounded-pill px-3">
                    <i class="bi bi-clock-history me-1"></i> Select Pickup Time & Order
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Category Filter Bar (Zero JS Links) -->
    <div class="d-flex align-items-center gap-2 mb-4 overflow-auto pb-2 flex-nowrap flex-md-wrap">
        <a href="canteen.php<?= $search ? '?q=' . urlencode($search) : '' ?>" 
           class="btn btn-sm rounded-pill px-3 canteen-cat-tab <?= empty($category) ? 'active' : '' ?>">
            <i class="bi bi-grid-fill me-1"></i> All Menu Items
        </a>
        <a href="canteen.php?category=Snacks<?= $search ? '&q=' . urlencode($search) : '' ?>" 
           class="btn btn-sm rounded-pill px-3 canteen-cat-tab <?= $category === 'Snacks' ? 'active' : '' ?>">
            <i class="bi bi-egg-fried me-1"></i> Snacks
        </a>
        <a href="canteen.php?category=Meals<?= $search ? '&q=' . urlencode($search) : '' ?>" 
           class="btn btn-sm rounded-pill px-3 canteen-cat-tab <?= $category === 'Meals' ? 'active' : '' ?>">
            <i class="bi bi-basket2-fill me-1"></i> Meals & Thalis
        </a>
        <a href="canteen.php?category=Beverages<?= $search ? '&q=' . urlencode($search) : '' ?>" 
           class="btn btn-sm rounded-pill px-3 canteen-cat-tab <?= $category === 'Beverages' ? 'active' : '' ?>">
            <i class="bi bi-cup-straw me-1"></i> Beverages & Chai
        </a>
        <a href="canteen.php?category=Fast+Food<?= $search ? '&q=' . urlencode($search) : '' ?>" 
           class="btn btn-sm rounded-pill px-3 canteen-cat-tab <?= $category === 'Fast Food' ? 'active' : '' ?>">
            <i class="bi bi-fire me-1"></i> Fast Food & Burgers
        </a>
    </div>

    <!-- Search & Secondary Filter Form (HTML Only) -->
    <div class="bg-white rounded-4 border p-3 mb-4 shadow-sm">
        <form action="canteen.php" method="GET" class="row g-2 align-items-center">
            <?php if (!empty($category)): ?>
                <input type="hidden" name="category" value="<?= e($category) ?>">
            <?php endif; ?>

            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q" value="<?= e($search) ?>" class="form-control border-start-0" placeholder="Search dishes (e.g. Chai, Thali, Maggi, Burger)...">
                </div>
            </div>

            <div class="col-md-3 col-6">
                <select name="food_type" class="form-select">
                    <option value="">Diet: All Types</option>
                    <option value="Veg" <?= $food_type === 'Veg' ? 'selected' : '' ?>>Pure Veg</option>
                    <option value="Non-Veg" <?= $food_type === 'Non-Veg' ? 'selected' : '' ?>>Non-Veg</option>
                </select>
            </div>

            <div class="col-md-2 col-6">
                <select name="sort" class="form-select">
                    <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Sort: Featured</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Name: A-Z</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    Filter
                </button>
                <?php if ($search !== '' || $food_type !== '' || $sort !== 'default' || $category !== ''): ?>
                    <a href="canteen.php" class="btn btn-outline-secondary" title="Clear Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Active Filter Tags Display -->
    <?php if ($category || $food_type || $search): ?>
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
            <small class="text-muted fw-bold">Active Filters:</small>
            <?php if ($category): ?>
                <span class="badge bg-light text-dark border px-2 py-1">
                    Category: <?= e($category) ?>
                    <a href="canteen.php?<?= http_build_query(array_merge($_GET, ['category' => ''])) ?>" class="text-danger ms-1 text-decoration-none">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($food_type): ?>
                <span class="badge bg-light text-dark border px-2 py-1">
                    Diet: <?= e($food_type) ?>
                    <a href="canteen.php?<?= http_build_query(array_merge($_GET, ['food_type' => ''])) ?>" class="text-danger ms-1 text-decoration-none">✕</a>
                </span>
            <?php endif; ?>
            <?php if ($search): ?>
                <span class="badge bg-light text-dark border px-2 py-1">
                    "<?= e($search) ?>"
                    <a href="canteen.php?<?= http_build_query(array_merge($_GET, ['q' => ''])) ?>" class="text-danger ms-1 text-decoration-none">✕</a>
                </span>
            <?php endif; ?>
            <a href="canteen.php" class="small text-danger ms-2 text-decoration-none fw-bold">Clear All</a>
        </div>
    <?php endif; ?>

    <!-- Menu Items Grid -->
    <?php if ($items_result->num_rows === 0): ?>
        <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-sm">
            <div class="display-3 text-warning opacity-75 mb-3"><i class="bi bi-cup-hot"></i></div>
            <h4 class="fw-bold">No Menu Items Found</h4>
            <p class="text-muted">No dishes matched your selected criteria. Try adjusting your search or category.</p>
            <a href="canteen.php" class="btn btn-primary rounded-pill px-4 mt-2">
                <i class="bi bi-arrow-left me-1"></i> View Full Menu
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($dish = $items_result->fetch_assoc()): ?>
                <?php 
                $dish_id = (int)$dish['id'];
                $in_tray_qty = $tray_item_counts[$dish_id] ?? 0;
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="canteen-card shadow-sm">
                        <!-- Food Image Container -->
                        <div class="canteen-card-img-wrap">
                            <img src="<?= product_img($dish['image']) ?>" alt="<?= e($dish['title']) ?>" loading="lazy">
                            
                            <!-- Badges Overlay -->
                            <div class="position-absolute top-0 start-0 m-3">
                                <?= food_type_badge($dish['food_type']) ?>
                            </div>
                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-white text-dark border shadow-sm px-2 py-1">
                                    <i class="bi bi-clock me-1 text-warning"></i><?= e($dish['prep_time']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-light text-primary border small"><?= e($dish['category']) ?></span>
                                <?php if ($in_tray_qty > 0): ?>
                                    <span class="badge bg-warning text-dark small fw-bold">
                                        <i class="bi bi-check2 me-1"></i><?= $in_tray_qty ?> in tray
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h5 class="fw-bold text-dark mb-1 mt-1 fs-6 card-title-clamp"><?= e($dish['title']) ?></h5>
                            <p class="text-muted small mb-3 card-desc-clamp" style="line-height: 1.45;">
                                <?= e($dish['description']) ?>
                            </p>

                            <!-- Price and Add to Tray Form -->
                            <div class="pt-2 border-top mt-auto d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">Student Price</small>
                                    <span class="h5 fw-bold text-primary mb-0"><?= format_price($dish['price']) ?></span>
                                </div>

                                <!-- Pure HTML Form for Add to Canteen Cart -->
                                <form action="canteen-cart.php" method="POST" class="d-inline m-0">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="item_id" value="<?= $dish_id ?>">
                                    <input type="hidden" name="qty" value="1">
                                    <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI'] ?? 'canteen.php') ?>">
                                    <button type="submit" class="btn btn-warning text-dark fw-bold btn-sm rounded-pill px-3 shadow-sm text-nowrap">
                                        <i class="bi bi-plus-lg me-1"></i> Add to Tray
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <!-- Express Pickup Information Section -->
    <div class="mt-5 p-4 bg-white rounded-4 border shadow-sm">
        <h4 class="fw-bold mb-3"><i class="bi bi-info-circle-fill text-primary me-2"></i>How Campus Canteen Express Pre-Order Works</h4>
        <div class="row g-4 text-center text-md-start">
            <div class="col-md-3">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="badge bg-primary text-white rounded-circle p-2 fs-6" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">1</span>
                    <h6 class="fw-bold mb-0">Browse & Select</h6>
                </div>
                <p class="text-muted small mb-0">Add your favorite meals, chai, and snacks to your express food tray.</p>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="badge bg-primary text-white rounded-circle p-2 fs-6" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">2</span>
                    <h6 class="fw-bold mb-0">Pick Break Slot</h6>
                </div>
                <p class="text-muted small mb-0">Select your preferred lecture break pickup time (e.g., 1:00 PM Lunch Shift 1).</p>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="badge bg-primary text-white rounded-circle p-2 fs-6" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">3</span>
                    <h6 class="fw-bold mb-0">Receive Token</h6>
                </div>
                <p class="text-muted small mb-0">Get your digital token number instantly on your phone screen.</p>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="badge bg-primary text-white rounded-circle p-2 fs-6" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">4</span>
                    <h6 class="fw-bold mb-0">Skip the Queue</h6>
                </div>
                <p class="text-muted small mb-0">Walk directly to Counter 1, 2, or 3, show token, grab hot food & enjoy!</p>
            </div>
        </div>
    </div>
</div>

<?php
$stmt->close();
require_once __DIR__ . '/includes/footer.php';

