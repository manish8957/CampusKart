<?php
// index.php - CampusKart Home Page (Zero JavaScript)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Home - Second-Hand College Books & Gadgets';

// Fetch all available marketplace products (Books & Tech)
$featured_stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name, u.name AS seller_name, u.college AS seller_college
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.seller_id = u.id
    WHERE p.status = 'available'
    ORDER BY p.is_featured DESC, p.id DESC
");
$featured_stmt->execute();
$featured_products = $featured_stmt->get_result();

// Fetch categories with product counts (Books & Gadgets)
$cat_res = $conn->query("
    SELECT c.*, COUNT(p.id) AS product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'available'
    GROUP BY c.id
    ORDER BY c.id ASC
");

// Fetch Sneaker Store products for homepage showcase
$snk_stmt = $conn->prepare("
    SELECT s.*, u.name AS seller_name, u.college AS seller_college
    FROM sneakers s
    JOIN users u ON s.seller_id = u.id
    WHERE s.status = 'available'
    ORDER BY s.id DESC
    LIMIT 4
");
$snk_stmt->execute();
$featured_sneakers = $snk_stmt->get_result();

// Fetch Campus Canteen food items for homepage showcase
$canteen_res = $conn->query("
    SELECT * FROM canteen_items 
    WHERE is_available = 1 
    ORDER BY id ASC 
    LIMIT 4
");

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero Banner Carousel (Zero JavaScript - Pure CSS Auto-Sliding with Manual Override) -->
<div class="container my-4">
    <div class="css-carousel-container shadow-sm">
        <!-- Pure CSS Radio Inputs (Zero JS) -->
        <input type="radio" name="campus_slide" id="cslide1" class="css-slide-radio">
        <input type="radio" name="campus_slide" id="cslide2" class="css-slide-radio">
        <input type="radio" name="campus_slide" id="cslide3" class="css-slide-radio">

        <!-- Slide Track Container (Auto-Sliding Pure CSS) -->
        <div class="css-carousel-track">
            <!-- Slide 1: General College Marketplace -->
            <div class="css-slide slide-1 hero-carousel-item" style="background-image: url('assets/images/hero_books.jpg');">
                <div class="hero-overlay">
                    <div class="col-lg-8 col-md-10 text-white">
                        <span class="badge bg-warning text-dark fw-bold mb-3 px-3 py-2 text-uppercase letter-spacing-1">
                            <i class="bi bi-mortarboard-fill me-1"></i> For College Students, By College Students
                        </span>
                        <h1 class="display-5 fw-bold text-white mb-3">
                            Buy & Sell Used Books <br><span class="text-warning">At Up to 70% Off</span>
                        </h1>
                        <p class="lead text-light mb-4">
                            Don't buy brand-new textbooks for just one semester. Grab pre-loved engineering, medical & exam prep books directly from campus seniors.
                        </p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="products.php" class="btn btn-campus-yellow btn-lg px-4">
                                <i class="bi bi-grid-fill me-2"></i> Browse All Items
                            </a>
                            <a href="sell.php" class="btn btn-outline-light btn-lg px-4">
                                <i class="bi bi-plus-circle me-2"></i> Sell Your Old Books
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 2: Gadgets & Calculators -->
            <div class="css-slide slide-2 hero-carousel-item" style="background-image: url('assets/images/hero_tech.jpg');">
                <div class="hero-overlay">
                    <div class="col-lg-8 col-md-10 text-white">
                        <span class="badge bg-info text-dark fw-bold mb-3 px-3 py-2 text-uppercase">
                            <i class="bi bi-cpu-fill me-1"></i> Tested Campus Tech
                        </span>
                        <h1 class="display-5 fw-bold text-white mb-3">
                            Scientific Calculators & <br><span class="text-info">Hostel Electronics</span>
                        </h1>
                        <p class="lead text-light mb-4">
                            Find Casio scientific calculators, Bluetooth keyboards, Raspberry Pi kits, and lab tools at student-friendly prices with in-person campus inspection.
                        </p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="products.php?cat=2" class="btn btn-info btn-lg px-4 text-dark fw-bold">
                                <i class="bi bi-calculator me-2"></i> View Calculators
                            </a>
                            <a href="products.php?cat=3" class="btn btn-outline-light btn-lg px-4">
                                <i class="bi bi-laptop me-2"></i> Explore Gadgets
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 3: Semester Bundles -->
            <div class="css-slide slide-3 hero-carousel-item" style="background-image: url('assets/images/hero_bundle.jpg');">
                <div class="hero-overlay">
                    <div class="col-lg-8 col-md-10 text-white">
                        <span class="badge bg-success fw-bold mb-3 px-3 py-2 text-uppercase">
                            <i class="bi bi-collection-fill me-1"></i> All-in-One Packs
                        </span>
                        <h1 class="display-5 fw-bold text-white mb-3">
                            Branch & Semester Bundles <br><span class="text-warning">Ready for Exams</span>
                        </h1>
                        <p class="lead text-light mb-4">
                            Grab curated textbook sets passed down from previous batches. Get your entire semester syllabus covered in one single affordable package!
                        </p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="products.php?cat=6" class="btn btn-campus-yellow btn-lg px-4">
                                <i class="bi bi-box-seam me-2"></i> Explore Semester Bundles
                            </a>
                            <a href="contact.php" class="btn btn-outline-light btn-lg px-4">
                                <i class="bi bi-chat-dots me-2"></i> Learn More
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pure CSS Slide Navigation Arrows (Prev / Next) -->
        <div class="css-carousel-arrows d-none d-md-block">
            <!-- Prev Arrows -->
            <label for="cslide3" class="css-arrow-btn css-arrow-prev arrow-for-slide-1" title="Previous Slide"><i class="bi bi-chevron-left"></i></label>
            <label for="cslide1" class="css-arrow-btn css-arrow-prev arrow-for-slide-2" title="Previous Slide"><i class="bi bi-chevron-left"></i></label>
            <label for="cslide2" class="css-arrow-btn css-arrow-prev arrow-for-slide-3" title="Previous Slide"><i class="bi bi-chevron-left"></i></label>

            <!-- Next Arrows -->
            <label for="cslide2" class="css-arrow-btn css-arrow-next arrow-for-slide-1" title="Next Slide"><i class="bi bi-chevron-right"></i></label>
            <label for="cslide3" class="css-arrow-btn css-arrow-next arrow-for-slide-2" title="Next Slide"><i class="bi bi-chevron-right"></i></label>
            <label for="cslide1" class="css-arrow-btn css-arrow-next arrow-for-slide-3" title="Next Slide"><i class="bi bi-chevron-right"></i></label>
        </div>

        <!-- Pure CSS Slide Selector Indicators -->
        <div class="css-carousel-indicators">
            <label for="cslide1" class="css-indicator-btn indicator-1" title="Slide 1: Books & Marketplace"></label>
            <label for="cslide2" class="css-indicator-btn indicator-2" title="Slide 2: Campus Tech & Calculators"></label>
            <label for="cslide3" class="css-indicator-btn indicator-3" title="Slide 3: Semester Exam Bundles"></label>
        </div>
    </div>
</div>

<!-- 2. Value Proposition Strip -->
<div class="container my-4 my-md-5">
    <div class="row g-2 g-md-4 text-center">
        <div class="col-6 col-md-3">
            <div class="feature-box">
                <div class="feature-icon-circle bg-primary bg-opacity-10 text-primary mx-auto">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <h6 class="fw-bold mb-1">Affordable Pricing</h6>
                <p class="text-muted small mb-0 d-none d-sm-block">Save 50% to 75% compared to brand-new bookstore MRPs.</p>
                <p class="text-muted small mb-0 d-sm-none">50% to 75% Off</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="feature-box">
                <div class="feature-icon-circle bg-warning bg-opacity-20 text-warning mx-auto">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <h6 class="fw-bold mb-1">Campus Handover</h6>
                <p class="text-muted small mb-0 d-none d-sm-block">Pick up at the library or canteen with zero shipping charges.</p>
                <p class="text-muted small mb-0 d-sm-none">Free In-Person</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="feature-box">
                <div class="feature-icon-circle bg-success bg-opacity-10 text-success mx-auto">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h6 class="fw-bold mb-1">Honest Condition</h6>
                <p class="text-muted small mb-0 d-none d-sm-block">Every item has an honest badge: New, Like New, Good, or Fair.</p>
                <p class="text-muted small mb-0 d-sm-none">Condition Badges</p>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="feature-box">
                <div class="feature-icon-circle bg-info bg-opacity-10 text-info mx-auto">
                    <i class="bi bi-recycle"></i>
                </div>
                <h6 class="fw-bold mb-1">Eco-Friendly</h6>
                <p class="text-muted small mb-0 d-none d-sm-block">Prevent book wastage and give functional electronics a 2nd life.</p>
                <p class="text-muted small mb-0 d-sm-none">Zero Book Waste</p>
            </div>
        </div>
    </div>
</div>

<!-- 3. Category Showcase: Across All 3 Sections -->
<div class="container my-4 my-md-5">
    <div class="d-flex justify-content-between align-items-end mb-3 mb-md-4 flex-wrap gap-2">
        <div>
            <span class="text-primary fw-bold text-uppercase small">Comprehensive Store Navigation</span>
            <h2 class="fw-bold mb-0 fs-3">Explore All Categories & Brands</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="products.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-book me-1"></i> Books
            </a>
            <a href="sneakers.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                <i class="bi bi-tag-fill me-1"></i> Sneakers
            </a>
            <a href="canteen.php" class="btn btn-outline-warning btn-sm rounded-pill px-3 text-dark">
                <i class="bi bi-cup-hot-fill me-1"></i> Canteen
            </a>
        </div>
    </div>

    <!-- Books & Gadgets Categories -->
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-book-fill text-primary"></i>
            <h6 class="fw-bold text-dark mb-0">Books & Campus Gadgets</h6>
        </div>
        <div class="row g-2 g-md-3">
            <?php while ($cat = $cat_res->fetch_assoc()): ?>
                <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                    <a href="products.php?cat=<?= (int)$cat['id'] ?>" class="category-card">
                        <div class="category-icon-box">
                            <i class="bi <?= e($cat['icon']) ?>"></i>
                        </div>
                        <h6 class="fw-bold mb-1"><?= e($cat['name']) ?></h6>
                        <small class="text-muted"><?= (int)$cat['product_count'] ?> <?= ($cat['product_count'] == 1) ? 'listing' : 'listings' ?></small>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Sneaker Brands & Canteen Categories Row -->
    <div class="row g-3">
        <!-- Sneaker Brands -->
        <div class="col-md-6">
            <div class="p-3 bg-white rounded-4 border shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-danger"><i class="bi bi-tag-fill me-1"></i> Sneaker Brands</span>
                    <a href="sneakers.php" class="small text-decoration-none fw-semibold">View All Kicks &rarr;</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="sneakers.php?brand=Nike" class="badge bg-light text-dark border p-2 text-decoration-none">Nike (Air Force, Dunk)</a>
                    <a href="sneakers.php?brand=Adidas" class="badge bg-light text-dark border p-2 text-decoration-none">Adidas (Superstar)</a>
                    <a href="sneakers.php?brand=Air+Jordan" class="badge bg-light text-dark border p-2 text-decoration-none">Air Jordan 1</a>
                    <a href="sneakers.php?brand=Puma" class="badge bg-light text-dark border p-2 text-decoration-none">Puma Suede</a>
                    <a href="sneakers.php?brand=New+Balance" class="badge bg-light text-dark border p-2 text-decoration-none">New Balance 574</a>
                    <a href="sneakers.php?brand=Converse" class="badge bg-light text-dark border p-2 text-decoration-none">Converse All Star</a>
                    <a href="sneakers.php?brand=Vans" class="badge bg-light text-dark border p-2 text-decoration-none">Vans Old Skool</a>
                    <a href="sneakers.php?brand=Asics" class="badge bg-light text-dark border p-2 text-decoration-none">Asics Gel-Kayano</a>
                </div>
            </div>
        </div>

        <!-- Canteen Food Categories -->
        <div class="col-md-6">
            <div class="p-3 bg-white rounded-4 border shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-warning"><i class="bi bi-cup-hot-fill me-1"></i> Campus Canteen Hub</span>
                    <a href="canteen.php" class="small text-decoration-none fw-semibold">Pre-order Menu &rarr;</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="canteen.php?category=Snacks" class="badge bg-light text-dark border p-2 text-decoration-none"><i class="bi bi-egg-fried me-1 text-warning"></i> Samosas & Vada Pav</a>
                    <a href="canteen.php?category=Meals" class="badge bg-light text-dark border p-2 text-decoration-none"><i class="bi bi-box2 me-1 text-warning"></i> Deluxe Lunch Thali & Chole</a>
                    <a href="canteen.php?category=Beverages" class="badge bg-light text-dark border p-2 text-decoration-none"><i class="bi bi-cup-straw me-1 text-warning"></i> Cutting Chai, Cold Coffee & Lassi</a>
                    <a href="canteen.php?category=Fast+Food" class="badge bg-light text-dark border p-2 text-decoration-none"><i class="bi bi-fire me-1 text-warning"></i> Cheese Maggi, Burgers & Pizza</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4. Books & Gadgets Store Section (All Available Items) -->
<div class="container my-4 my-md-5">
    <div class="d-flex justify-content-between align-items-end mb-3 mb-md-4 flex-wrap gap-2">
        <div>
            <span class="text-primary fw-bold text-uppercase small">Books & Gadgets Marketplace</span>
            <h2 class="fw-bold mb-0 fs-3">Student Listings & Semester Packs</h2>
        </div>
        <a href="products.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            Open Books Catalog (<?= $featured_products->num_rows ?> Items)
        </a>
    </div>

    <div class="row g-3 g-md-4">
        <?php while ($prod = $featured_products->fetch_assoc()): ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="campus-card h-100">
                    <!-- Image with Condition Badge & Wishlist Heart -->
                    <div class="card-img-wrapper">
                        <a href="product-details.php?id=<?= (int)$prod['id'] ?>" class="d-flex align-items-center justify-content-center w-100 h-100">
                            <img src="<?= product_img($prod['image']) ?>" alt="<?= e($prod['title']) ?>">
                        </a>
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

                        <!-- Price & Action -->
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
</div>

<!-- 5. Sneaker Store Featured Drops Section -->
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-end mb-3 mb-md-4 flex-wrap gap-2">
        <div>
            <span class="text-danger fw-bold text-uppercase small"><i class="bi bi-tag-fill me-1"></i> Authentic Campus Kicks</span>
            <h2 class="fw-bold mb-0 fs-3">Trending Sneakers On Campus</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="sneakers.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                View All Sneakers &rarr;
            </a>
            <a href="sell-sneaker.php" class="btn btn-danger btn-sm rounded-pill px-3">
                <i class="bi bi-plus-lg me-1"></i> List Sneakers
            </a>
        </div>
    </div>

    <div class="row g-3 g-md-4">
        <?php while ($snk = $featured_sneakers->fetch_assoc()): ?>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="campus-card h-100">
                    <div class="card-img-wrapper">
                        <a href="sneaker-details.php?id=<?= (int)$snk['id'] ?>" class="d-flex align-items-center justify-content-center w-100 h-100">
                            <img src="<?= product_img($snk['image']) ?>" alt="<?= e($snk['title']) ?>">
                        </a>
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
                                <span class="badge bg-light text-primary border fw-bold small"><?= e($snk['brand']) ?></span>
                                <small class="text-muted"><i class="bi bi-palette me-1"></i><?= e($snk['colour']) ?></small>
                            </div>
                            <h5 class="card-title fw-bold fs-6 mt-2 mb-2 card-title-clamp">
                                <a href="sneaker-details.php?id=<?= (int)$snk['id'] ?>" class="text-dark text-decoration-none">
                                    <?= e($snk['title']) ?>
                                </a>
                            </h5>
                        </div>

                        <div class="pt-2 border-top mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span class="price-tag"><?= format_price($snk['price']) ?></span>
                                    <?php if ($snk['mrp']): ?>
                                        <span class="mrp-strike"><?= format_price($snk['mrp']) ?></span>
                                    <?php endif; ?>
                                </div>
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
                                    <button type="submit" class="btn btn-danger btn-sm w-100">
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
</div>

<!-- 6. Campus Canteen Pre-Order Bites Section -->
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-end mb-3 mb-md-4 flex-wrap gap-2">
        <div>
            <span class="text-warning fw-bold text-uppercase small"><i class="bi bi-cup-hot-fill me-1"></i> Beat the Lunch Rush</span>
            <h2 class="fw-bold mb-0 fs-3">Popular Canteen Pre-Orders</h2>
        </div>
        <a href="canteen.php" class="btn btn-warning text-dark btn-sm rounded-pill px-3 fw-bold">
            Full Canteen Menu &rarr;
        </a>
    </div>

    <div class="row g-3 g-md-4">
        <?php while ($food = $canteen_res->fetch_assoc()): ?>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="campus-card h-100">
                    <div class="card-img-wrapper" style="height: 180px;">
                        <img src="<?= product_img($food['image']) ?>" alt="<?= e($food['title']) ?>">
                        <div class="badge-top-left">
                            <span class="badge <?= ($food['food_type'] === 'Veg') ? 'bg-success' : 'bg-danger' ?>">
                                <i class="bi bi-record-circle me-1"></i> <?= e($food['food_type']) ?>
                            </span>
                        </div>
                        <div class="badge-top-right">
                            <span class="badge bg-light text-dark border small">
                                <i class="bi bi-clock me-1 text-warning"></i> <?= e($food['prep_time']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-3 d-flex flex-column justify-content-between flex-grow-1">
                        <div>
                            <span class="badge bg-light text-secondary border small mb-1"><?= e($food['category']) ?></span>
                            <h5 class="card-title fw-bold fs-6 mb-1"><?= e($food['title']) ?></h5>
                            <p class="text-muted small mb-2 card-desc-clamp"><?= e(substr($food['description'], 0, 75)) ?>...</p>
                        </div>

                        <div class="pt-2 border-top mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="price-tag"><?= format_price($food['price']) ?></span>
                                <small class="text-success fw-semibold"><i class="bi bi-check2-circle me-1"></i>Available Now</small>
                            </div>
                            <form action="canteen-cart.php" method="POST" class="m-0">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="item_id" value="<?= (int)$food['id'] ?>">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit" class="btn btn-warning text-dark btn-sm w-100 fw-bold shadow-sm">
                                    <i class="bi bi-cart-plus me-1"></i> Add to Canteen Tray
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- 7. Call to Action Banner (Sell Your Item) -->
<div class="container my-5">
    <div class="p-5 rounded-4 shadow-sm text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #0f172a 100%);">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-warning text-dark fw-bold px-3 py-2 mb-3">SEMESTER PASSED?</span>
                <h2 class="display-6 fw-bold mb-3">Finished Your Exams? Turn Your Used Books into Cash!</h2>
                <p class="lead text-light mb-4 mb-lg-0">
                    Don't let valuable textbooks sit gathering dust on hostel shelves. List them on CampusKart in under 2 minutes. Your college juniors are looking for them right now!
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="d-flex flex-column flex-sm-row justify-content-lg-end gap-2">
                    <a href="sell.php" class="btn btn-campus-yellow btn-lg px-4 py-3 shadow">
                        <i class="bi bi-plus-circle-fill me-2"></i> Sell Used Books
                    </a>
                    <a href="sell-sneaker.php" class="btn btn-outline-light btn-lg px-4 py-3">
                        <i class="bi bi-tag-fill me-2"></i> Sell Sneakers
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$featured_stmt->close();
$snk_stmt->close();
require_once __DIR__ . '/includes/footer.php';
?>
