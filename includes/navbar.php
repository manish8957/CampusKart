<?php
// includes/navbar.php - Shared Navigation Bar (Zero JavaScript)
$currentUserId = current_user_id();
$bookCartCount = $currentUserId ? get_cart_count($conn, $currentUserId) : 0;
$sneakerCartCount = $currentUserId ? get_sneaker_cart_count($conn, $currentUserId) : 0;
$canteenCartCount = $currentUserId ? get_canteen_cart_count($conn, $currentUserId) : 0;
$totalCartCount = $bookCartCount + $sneakerCartCount + $canteenCartCount;

$bookWishlistCount = $currentUserId ? get_wishlist_count($conn, $currentUserId) : 0;
$sneakerWishlistCount = $currentUserId ? get_sneaker_wishlist_count($conn, $currentUserId) : 0;
$totalWishlistCount = $bookWishlistCount + $sneakerWishlistCount;

$currentPage = basename($_SERVER['PHP_SELF']);
$navCategories = get_categories($conn);
?>
<nav class="navbar navbar-expand-lg campus-navbar">
    <div class="container-fluid campus-nav-container px-2 px-sm-3 px-xl-4">
        <!-- Brand Logo -->
        <a class="navbar-brand campus-brand" href="index.php">
            <span class="brand-badge"><i class="bi bi-mortarboard-fill"></i></span>
            <div class="brand-text-group">
                <span class="brand-name">CampusKart</span>
                <span class="brand-tag">CAMPUS STORE</span>
            </div>
        </a>

        <!-- Mobile Hamburger Toggle (CSS-Only Checkbox Hack) -->
        <input type="checkbox" id="campusNavToggle" class="campus-nav-toggle-check d-none" aria-label="Toggle navigation">
        <label for="campusNavToggle" class="navbar-toggler campus-toggler shadow-none">
            <i class="bi bi-list fs-4"></i>
        </label>

        <!-- Navbar Menu -->
        <div class="collapse navbar-collapse" id="campusNavbar">
            <!-- Row 1 (Mobile) / Middle (Desktop): Search Bar Form -->
            <form class="campus-search-form order-1 order-lg-2" action="products.php" method="GET">
                <input class="campus-search-input" type="search" name="search" placeholder="Search books, sneakers, food..." aria-label="Search" value="<?= isset($_GET['search']) ? e($_GET['search']) : '' ?>">
                <button class="campus-search-btn" type="submit" aria-label="Submit search">
                    <i class="bi bi-search"></i>
                </button>
            </form>

            <!-- Row 2 (Mobile) / Left (Desktop): Nav Links (Books, Sneakers, Canteen, About) -->
            <ul class="navbar-nav campus-nav-list order-2 order-lg-1 me-lg-2 mb-3 mb-lg-0">
                <li class="nav-item">
                    <a class="campus-nav-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>" href="index.php">
                        <i class="bi bi-house-door text-primary"></i>
                        <span>Home</span>
                    </a>
                </li>

                <!-- Books & Gadgets Marketplace -->
                <li class="nav-item">
                    <a class="campus-nav-link <?= (in_array($currentPage, ['products.php', 'product-details.php'])) ? 'active' : '' ?>" href="products.php">
                        <i class="bi bi-book text-primary"></i>
                        <span>Books & Gadgets</span>
                    </a>
                </li>

                <!-- Module 1: Sneaker Store -->
                <li class="nav-item">
                    <a class="campus-nav-link <?= (in_array($currentPage, ['sneakers.php', 'sneaker-details.php', 'sneaker-cart.php', 'sell-sneaker.php', 'sneaker-orders.php'])) ? 'active' : '' ?>" href="sneakers.php">
                        <i class="bi bi-tag-fill text-danger"></i>
                        <span>Sneaker Store</span>
                    </a>
                </li>

                <!-- Module 2: Campus Canteen Pre-order -->
                <li class="nav-item">
                    <a class="campus-nav-link <?= (in_array($currentPage, ['canteen.php', 'canteen-cart.php', 'canteen-checkout.php', 'canteen-orders.php'])) ? 'active' : '' ?>" href="canteen.php">
                        <i class="bi bi-cup-hot-fill text-warning"></i>
                        <span>Campus Canteen</span>
                    </a>
                </li>

                <!-- All Categories Dropdown (Across All Modules) -->
                <li class="nav-item dropdown campus-nav-action-cat">
                    <a class="campus-nav-link dropdown-toggle" href="products.php" role="button">
                        <i class="bi bi-grid-3x3-gap text-primary"></i>
                        <span>Categories</span>
                    </a>
                    <ul class="dropdown-menu campus-dropdown-menu border-0 shadow-lg mt-0" style="min-width: 250px;">
                        <li><h6 class="dropdown-header text-uppercase fw-bold text-primary small px-3 py-1"><i class="bi bi-book me-1"></i> Books & Study Gear</h6></li>
                        <?php foreach ($navCategories as $cat): ?>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-1 px-3 small" href="products.php?cat=<?= (int)$cat['id'] ?>">
                                    <i class="bi <?= e($cat['icon']) ?> me-2 text-primary"></i> <?= e($cat['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><h6 class="dropdown-header text-uppercase fw-bold text-danger small px-3 py-1"><i class="bi bi-tag-fill me-1"></i> Sneaker Store Brands</h6></li>
                        <li><a class="dropdown-item campus-dropdown-item py-1 px-3 small" href="sneakers.php?brand=Nike"><i class="bi bi-tag me-2 text-danger"></i> Nike Kicks</a></li>
                        <li><a class="dropdown-item campus-dropdown-item py-1 px-3 small" href="sneakers.php?brand=Adidas"><i class="bi bi-tag me-2 text-danger"></i> Adidas Originals</a></li>
                        <li><a class="dropdown-item campus-dropdown-item py-1 px-3 small" href="sneakers.php?brand=Air+Jordan"><i class="bi bi-tag me-2 text-danger"></i> Air Jordan High & Low</a></li>
                        <li><a class="dropdown-item campus-dropdown-item py-1 px-3 small" href="sneakers.php?brand=Puma"><i class="bi bi-tag me-2 text-danger"></i> Puma Classics</a></li>
                        <li><a class="dropdown-item campus-dropdown-item py-1 px-3 small" href="sneakers.php?brand=New+Balance"><i class="bi bi-tag me-2 text-danger"></i> New Balance Lifestyle</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><h6 class="dropdown-header text-uppercase fw-bold text-warning small px-3 py-1"><i class="bi bi-cup-hot-fill me-1"></i> Campus Canteen Food</h6></li>
                        <li><a class="dropdown-item campus-dropdown-item py-1 px-3 small" href="canteen.php?category=Snacks"><i class="bi bi-cup-hot me-2 text-warning"></i> Quick Snacks & Samosas</a></li>
                        <li><a class="dropdown-item campus-dropdown-item py-1 px-3 small" href="canteen.php?category=Meals"><i class="bi bi-egg-fried me-2 text-warning"></i> Campus Lunch Thalis</a></li>
                        <li><a class="dropdown-item campus-dropdown-item py-1 px-3 small" href="canteen.php?category=Beverages"><i class="bi bi-cup-straw me-2 text-warning"></i> Cutting Chai & Coffee</a></li>
                        <li><a class="dropdown-item campus-dropdown-item py-1 px-3 small" href="canteen.php?category=Fast+Food"><i class="bi bi-fire me-2 text-warning"></i> Fast Food & Maggi Hub</a></li>
                    </ul>
                </li>
            </ul>

            <!-- Row 3 (Mobile) / Right (Desktop): Actions Group -->
            <div class="campus-actions-group order-3 order-lg-3 ms-lg-auto">
                <!-- Mobile Actions Row: Sell & Cart -->
                <div class="campus-actions-mobile-row">
                    <!-- Sell Dropdown (Sell Books or Sneakers) -->
                    <div class="dropdown campus-nav-action-sell">
                        <a href="sell.php" class="btn-nav-sell text-decoration-none">
                            <i class="bi bi-plus-circle-fill"></i>
                            <span>Sell Item</span>
                        </a>
                        <ul class="dropdown-menu campus-dropdown-menu border-0 shadow-lg mt-0">
                            <li><h6 class="dropdown-header text-uppercase fw-bold text-muted small px-3 py-1">List on Campus</h6></li>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-2" href="sell.php">
                                    <i class="bi bi-book me-2 text-primary"></i> Sell Used Books / Tech
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-2" href="sell-sneaker.php">
                                    <i class="bi bi-tag-fill me-2 text-danger"></i> List Sneakers for Sale
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Unified Cart Dropdown -->
                    <div class="dropdown campus-nav-action-cart">
                        <a href="cart.php" class="btn-nav-cart text-decoration-none">
                            <i class="bi bi-cart3 text-primary"></i>
                            <span>Cart</span>
                            <?php if ($totalCartCount > 0): ?>
                                <span class="cart-count-badge"><?= $totalCartCount ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end campus-dropdown-menu border-0 shadow-lg mt-0" style="min-width: 210px;">
                            <li><h6 class="dropdown-header text-uppercase fw-bold text-muted small px-3 py-1">Your Shopping Trays</h6></li>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-2 d-flex justify-content-between align-items-center" href="cart.php">
                                    <span><i class="bi bi-book me-2 text-primary"></i> Books & Gear Cart</span>
                                    <?php if ($bookCartCount > 0): ?>
                                        <span class="badge bg-primary rounded-pill"><?= $bookCartCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-2 d-flex justify-content-between align-items-center" href="sneaker-cart.php">
                                    <span><i class="bi bi-tag-fill me-2 text-danger"></i> Sneaker Cart</span>
                                    <?php if ($sneakerCartCount > 0): ?>
                                        <span class="badge bg-danger rounded-pill"><?= $sneakerCartCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-2 d-flex justify-content-between align-items-center" href="canteen-cart.php">
                                    <span><i class="bi bi-cup-hot-fill me-2 text-warning"></i> Canteen Tray</span>
                                    <?php if ($canteenCartCount > 0): ?>
                                        <span class="badge bg-warning text-dark rounded-pill"><?= $canteenCartCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Auth Buttons or Profile Menu -->
                <?php if (is_logged_in()): ?>
                    <div class="dropdown campus-nav-action-user">
                        <a href="orders.php" class="btn btn-nav-user dropdown-toggle text-decoration-none" role="button">
                            <i class="bi bi-person-circle text-primary fs-6"></i>
                            <span class="user-name-text"><?= e(current_user_name()) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end campus-dropdown-menu border-0 shadow-lg mt-0">
                            <li class="px-3 py-2 border-bottom bg-light rounded-top">
                                <small class="text-muted d-block" style="font-size: 0.72rem;">Signed in as</small>
                                <span class="fw-bold text-dark small text-truncate d-block" style="max-width: 200px;"><?= e($_SESSION['user_email'] ?? '') ?></span>
                            </li>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-2" href="my-listings.php">
                                    <i class="bi bi-tags me-2 text-warning"></i> My Listings
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-2" href="seller-orders.php">
                                    <i class="bi bi-box-seam me-2 text-warning"></i> Handover Requests
                                    <?php 
                                    $pendingCount = get_seller_pending_orders_count($conn, $currentUserId);
                                    if ($pendingCount > 0): ?>
                                        <span class="badge bg-warning text-dark ms-auto"><?= $pendingCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-2 d-flex justify-content-between align-items-center" href="wishlist.php">
                                    <span><i class="bi bi-heart-fill me-2 text-danger"></i> My Wishlist</span>
                                    <?php if ($totalWishlistCount > 0): ?>
                                        <span class="badge bg-danger rounded-pill"><?= $totalWishlistCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><h6 class="dropdown-header text-uppercase fw-bold text-muted small px-3 py-1">Order History</h6></li>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-2" href="orders.php">
                                    <i class="bi bi-bag-check me-2 text-success"></i> Books & Gadget Orders
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-2" href="sneaker-orders.php">
                                    <i class="bi bi-tag-fill me-2 text-danger"></i> Sneaker Orders
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-2" href="canteen-orders.php">
                                    <i class="bi bi-receipt me-2 text-warning"></i> Canteen Food Tokens
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-2" href="profile.php">
                                    <i class="bi bi-person-gear me-2 text-primary"></i> Profile Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item campus-dropdown-item py-2 text-danger fw-semibold" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="campus-nav-action-auth">
                        <a href="login.php" class="btn-nav-login">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span>Login</span>
                        </a>
                        <a href="register.php" class="btn-nav-register">
                            <i class="bi bi-person-plus"></i>
                            <span>Register</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
