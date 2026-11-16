<?php
// my-listings.php - Student's Personal Listings Management (Books & Sneakers)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user_id = current_user_id();
$active_tab = trim($_GET['tab'] ?? 'books');
if ($active_tab !== 'sneakers') {
    $active_tab = 'books';
}

// Handle Mark Sold / Delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? 'product'; // 'product' or 'sneaker'
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

    if ($item_id > 0) {
        if ($type === 'sneaker') {
            if ($action === 'toggle_status') {
                $new_status = ($_POST['status'] === 'sold') ? 'available' : 'sold';
                $stmt = $conn->prepare("UPDATE sneakers SET status = ? WHERE id = ? AND seller_id = ?");
                $stmt->bind_param("sii", $new_status, $item_id, $user_id);
                $stmt->execute();
                $stmt->close();
                set_flash('info', 'Sneaker listing status updated to "' . $new_status . '".');
            } elseif ($action === 'delete') {
                $stmt = $conn->prepare("DELETE FROM sneakers WHERE id = ? AND seller_id = ?");
                $stmt->bind_param("ii", $item_id, $user_id);
                $stmt->execute();
                $stmt->close();
                set_flash('warning', 'Sneaker listing deleted.');
            }
            header("Location: my-listings.php?tab=sneakers");
            exit;
        } else {
            if ($action === 'toggle_status') {
                $new_status = ($_POST['status'] === 'sold') ? 'available' : 'sold';
                $stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ? AND seller_id = ?");
                $stmt->bind_param("sii", $new_status, $item_id, $user_id);
                $stmt->execute();
                $stmt->close();
                set_flash('info', 'Listing status updated to "' . $new_status . '".');
            } elseif ($action === 'delete') {
                $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
                $stmt->bind_param("ii", $item_id, $user_id);
                $stmt->execute();
                $stmt->close();
                set_flash('warning', 'Listing deleted.');
            }
            header("Location: my-listings.php?tab=books");
            exit;
        }
    }
}

// Fetch user's books/gadget listings
$stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.seller_id = ?
    ORDER BY p.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_products = $stmt->get_result();

// Fetch user's sneaker listings
$s_stmt = $conn->prepare("
    SELECT * FROM sneakers
    WHERE seller_id = ?
    ORDER BY id DESC
");
$s_stmt->bind_param("i", $user_id);
$s_stmt->execute();
$my_sneakers = $s_stmt->get_result();

$page_title = 'My Listed Items';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">My Listings</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-tags text-warning me-2"></i>My Listed Items</h1>
            <p class="text-muted small mb-0">Manage the used books, tech gear, and sneakers you have listed for sale.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="sell.php" class="btn btn-campus-yellow btn-sm px-3">
                <i class="bi bi-plus-circle-fill me-1"></i> Sell Book / Tech
            </a>
            <a href="sell-sneaker.php" class="btn btn-danger btn-sm rounded-pill px-3">
                <i class="bi bi-tag-fill me-1"></i> List Sneakers
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="d-flex align-items-center gap-2 mb-4 border-bottom pb-3">
        <a href="my-listings.php?tab=books" class="btn btn-sm rounded-pill px-3 fw-bold <?= $active_tab === 'books' ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <i class="bi bi-book me-1"></i> Books & Gadgets (<?= $my_products->num_rows ?>)
        </a>
        <a href="my-listings.php?tab=sneakers" class="btn btn-sm rounded-pill px-3 fw-bold <?= $active_tab === 'sneakers' ? 'btn-danger' : 'btn-outline-secondary' ?>">
            <i class="bi bi-tag-fill me-1"></i> Sneakers (<?= $my_sneakers->num_rows ?>)
        </a>
    </div>

    <!-- Tab 1: Books & Gadgets -->
    <?php if ($active_tab === 'books'): ?>
        <?php if ($my_products->num_rows === 0): ?>
            <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-sm">
                <div class="display-2 text-muted mb-3"><i class="bi bi-book-half"></i></div>
                <h4 class="fw-bold">No Books or Tech Listed Yet</h4>
                <p class="text-muted">You haven't listed any used textbooks or calculators. Have items from last semester?</p>
                <a href="sell.php" class="btn btn-campus mt-2">
                    <i class="bi bi-plus-circle me-1"></i> Create Book Listing
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php while ($item = $my_products->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="campus-card h-100">
                            <div class="card-img-wrapper">
                                <a href="product-details.php?id=<?= (int)$item['id'] ?>">
                                    <img src="<?= product_img($item['image']) ?>" alt="<?= e($item['title']) ?>">
                                </a>
                                <div class="badge-top-left">
                                    <?= condition_badge($item['condition']) ?>
                                </div>
                                <div class="badge-top-right">
                                    <?php if ($item['status'] === 'sold'): ?>
                                        <span class="badge bg-secondary">SOLD</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">ACTIVE</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-tag me-1"></i> <?= e($item['category_name']) ?>
                                    </small>
                                    <h5 class="card-title fw-bold fs-6">
                                        <a href="product-details.php?id=<?= (int)$item['id'] ?>" class="text-dark text-decoration-none">
                                            <?= e($item['title']) ?>
                                        </a>
                                    </h5>
                                    <div class="price-tag mb-2"><?= format_price($item['price']) ?></div>
                                </div>

                                <div class="pt-2 border-top d-flex gap-2">
                                    <form action="my-listings.php" method="POST" class="m-0 flex-grow-1">
                                        <input type="hidden" name="type" value="product">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                        <input type="hidden" name="status" value="<?= e($item['status']) ?>">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                            <?= ($item['status'] === 'sold') ? 'Mark Available' : 'Mark as Sold' ?>
                                        </button>
                                    </form>

                                    <form action="my-listings.php" method="POST" class="m-0">
                                        <input type="hidden" name="type" value="product">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete Listing">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    <!-- Tab 2: Sneakers -->
    <?php else: ?>
        <?php if ($my_sneakers->num_rows === 0): ?>
            <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-sm">
                <div class="display-2 text-danger opacity-50 mb-3"><i class="bi bi-tag"></i></div>
                <h4 class="fw-bold">No Sneakers Listed Yet</h4>
                <p class="text-muted">You haven't put up any sneakers for sale. Have kicks you've outgrown?</p>
                <a href="sell-sneaker.php" class="btn btn-danger rounded-pill px-4 mt-2">
                    <i class="bi bi-plus-circle me-1"></i> List Sneakers for Sale
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php while ($snk = $my_sneakers->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="campus-card h-100">
                            <div class="card-img-wrapper" style="height: 190px;">
                                <a href="sneaker-details.php?id=<?= (int)$snk['id'] ?>" class="w-100 h-100 d-block">
                                    <img src="<?= product_img($snk['image']) ?>" alt="<?= e($snk['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                </a>
                                <div class="badge-top-left">
                                    <?= condition_badge($snk['condition']) ?>
                                </div>
                                <div class="badge-top-right">
                                    <?php if ($snk['status'] === 'sold'): ?>
                                        <span class="badge bg-secondary">SOLD</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">ACTIVE</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <small class="text-muted d-block mb-1">
                                        <span class="badge bg-light text-dark border"><?= e($snk['brand']) ?></span>
                                        <span class="badge bg-light text-dark border ms-1"><?= e($snk['size']) ?></span>
                                        <span class="badge bg-light text-dark border ms-1"><?= e($snk['colour']) ?></span>
                                    </small>
                                    <h5 class="card-title fw-bold fs-6">
                                        <a href="sneaker-details.php?id=<?= (int)$snk['id'] ?>" class="text-dark text-decoration-none">
                                            <?= e($snk['title']) ?>
                                        </a>
                                    </h5>
                                    <div class="price-tag text-danger mb-2"><?= format_price($snk['price']) ?></div>
                                </div>

                                <div class="pt-2 border-top d-flex gap-2">
                                    <form action="my-listings.php" method="POST" class="m-0 flex-grow-1">
                                        <input type="hidden" name="type" value="sneaker">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="item_id" value="<?= (int)$snk['id'] ?>">
                                        <input type="hidden" name="status" value="<?= e($snk['status']) ?>">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                            <?= ($snk['status'] === 'sold') ? 'Mark Available' : 'Mark as Sold' ?>
                                        </button>
                                    </form>

                                    <form action="my-listings.php" method="POST" class="m-0">
                                        <input type="hidden" name="type" value="sneaker">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item_id" value="<?= (int)$snk['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete Listing">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$stmt->close();
$s_stmt->close();
require_once __DIR__ . '/includes/footer.php';
