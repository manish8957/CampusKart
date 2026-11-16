<?php
// sell.php - "Sell Your Item" Student Marketplace Listing Form
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Phase 3: Restrict to logged-in students
require_login();

$user_id = current_user_id();
$categories = get_categories($conn);
$errors = [];

$title = '';
$category_id = '';
$price = '';
$mrp = '';
$condition = 'Good';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $price = trim($_POST['price'] ?? '');
    $mrp = trim($_POST['mrp'] ?? '');
    $condition = trim($_POST['condition'] ?? 'Good');
    $description = trim($_POST['description'] ?? '');

    // Server-side validation
    if (empty($title)) {
        $errors[] = 'Item title / textbook name is required.';
    } elseif (strlen($title) < 5) {
        $errors[] = 'Item title must be at least 5 characters long.';
    }

    if ($category_id <= 0) {
        $errors[] = 'Please select a valid subject/gadget category.';
    }

    if (empty($price) || !is_numeric($price) || (float)$price <= 0) {
        $errors[] = 'Please specify a valid selling price in ₹.';
    } else {
        $price = (float)$price;
    }

    $mrp_val = null;
    if (!empty($mrp)) {
        if (!is_numeric($mrp) || (float)$mrp < $price) {
            $errors[] = 'Original MRP should be a valid number greater than or equal to selling price.';
        } else {
            $mrp_val = (float)$mrp;
        }
    }

    $allowed_conditions = ['New', 'Like New', 'Good', 'Fair'];
    if (!in_array($condition, $allowed_conditions)) {
        $condition = 'Good';
    }

    if (empty($description)) {
        $errors[] = 'Please provide a brief description of the condition and edition.';
    }

    // Image Upload Handling
    $image_path = 'assets/images/placeholder.jpg';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['product_image']['tmp_name'];
        $file_name = $_FILES['product_image']['name'];
        $file_size = $_FILES['product_image']['size'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        if (!in_array($ext, $allowed_exts)) {
            $errors[] = 'Only JPG, PNG, WEBP, or SVG images are allowed.';
        } elseif ($file_size > 5 * 1024 * 1024) {
            $errors[] = 'Uploaded image must be under 5 MB.';
        } else {
            $new_name = 'prod_' . uniqid() . '.' . $ext;
            $upload_dest = __DIR__ . '/assets/uploads/' . $new_name;
            if (move_uploaded_file($file_tmp, $upload_dest)) {
                $image_path = 'assets/uploads/' . $new_name;
            } else {
                $errors[] = 'Could not save the uploaded image. Default placeholder used.';
            }
        }
    }

    // Insert into products table with Prepared Statement
    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO products (title, description, price, mrp, `condition`, image, category_id, seller_id, is_featured, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'available')
        ");
        $stmt->bind_param("ssddssii", $title, $description, $price, $mrp_val, $condition, $image_path, $category_id, $user_id);

        if ($stmt->execute()) {
            $new_prod_id = $stmt->insert_id;
            $stmt->close();

            set_flash('success', 'Awesome! Your listing "' . $title . '" is now live on CampusKart.');
            header("Location: product-details.php?id=" . $new_prod_id);
            exit;
        } else {
            $errors[] = 'Failed to create product listing. Database error: ' . $conn->error;
            $stmt->close();
        }
    }
}

$page_title = 'Sell Your College Item';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Sell Your Item</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-card shadow-sm">
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                    <span class="brand-badge bg-warning text-dark"><i class="bi bi-tag-fill"></i></span>
                    <div>
                        <h2 class="fw-bold mb-0">List an Item on CampusKart</h2>
                        <small class="text-muted">Pass your used textbooks, calculators, or electronics to fellow campus peers</small>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="sell.php" method="POST" enctype="multipart/form-data" novalidate>
                    <!-- Item Title -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Book / Gadget Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Higher Engineering Mathematics - B.S. Grewal 44th Edition" value="<?= e($title) ?>" required>
                        <small class="text-muted" style="font-size: 0.75rem;">Mention author, edition, or model number so juniors can identify it quickly.</small>
                    </div>

                    <!-- Category & Condition -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>" <?= ($category_id == $cat['id']) ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="condition" class="form-label">Physical Condition <span class="text-danger">*</span></label>
                            <select class="form-select" id="condition" name="condition" required>
                                <option value="New" <?= ($condition === 'New') ? 'selected' : '' ?>>New / Unopened</option>
                                <option value="Like New" <?= ($condition === 'Like New') ? 'selected' : '' ?>>Like New (Minimal study wear)</option>
                                <option value="Good" <?= ($condition === 'Good') ? 'selected' : '' ?>>Good (All pages intact, pencil marks)</option>
                                <option value="Fair" <?= ($condition === 'Fair') ? 'selected' : '' ?>>Fair (Highlighter marks, creased cover)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Price & MRP -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="price" class="form-label">Your Selling Price (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold">₹</span>
                                <input type="number" step="1" min="10" class="form-control" id="price" name="price" placeholder="e.g. 350" value="<?= e($price) ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="mrp" class="form-label">Original MRP (₹) <span class="text-muted small">(Optional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold">₹</span>
                                <input type="number" step="1" min="10" class="form-control" id="mrp" name="mrp" placeholder="e.g. 850 (shows % discount)" value="<?= e($mrp) ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description & Notes <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Detail any highlights, missing accessories, or which semester syllabus this covers..." required><?= e($description) ?></textarea>
                    </div>

                    <!-- Photo Upload -->
                    <div class="mb-4">
                        <label for="productImageInput" class="form-label">Item Photo</label>
                        <input class="form-control" type="file" id="productImageInput" name="product_image" accept="image/*">
                        <small class="text-muted" style="font-size: 0.75rem;">Upload a clear photo of the cover or gadget. Supported: JPG, PNG, WEBP (Max 5MB). Leave blank to use a default placeholder.</small>
                    </div>

                    <!-- Submit -->
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-campus-yellow btn-lg flex-grow-1 fw-bold">
                            <i class="bi bi-cloud-upload-fill me-1"></i> Publish Campus Listing
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary btn-lg">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

