<?php
// sell-sneaker.php - Student Sneaker Listing Form (Zero JavaScript - Pure HTML/PHP)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user_id = current_user_id();

$errors = [];
$title = '';
$brand = 'Nike';
$size = 'UK 8';
$colour = 'White';
$condition = 'Good';
$price = '';
$mrp = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $brand = trim($_POST['brand'] ?? 'Nike');
    $size = trim($_POST['size'] ?? 'UK 8');
    $colour = trim($_POST['colour'] ?? 'White');
    $condition = trim($_POST['condition'] ?? 'Good');
    $price = trim($_POST['price'] ?? '');
    $mrp = trim($_POST['mrp'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Server-side validation
    if (empty($title)) {
        $errors[] = 'Sneaker title / model is required.';
    } elseif (strlen($title) < 4) {
        $errors[] = 'Sneaker title must be at least 4 characters long.';
    }

    if (empty($price) || !is_numeric($price) || (float)$price <= 0) {
        $errors[] = 'Please specify a valid price in ₹.';
    } else {
        $price = (float)$price;
    }

    $mrp_val = null;
    if (!empty($mrp)) {
        if (!is_numeric($mrp) || (float)$mrp < $price) {
            $errors[] = 'Original MRP must be greater than or equal to selling price.';
        } else {
            $mrp_val = (float)$mrp;
        }
    }

    if (empty($description)) {
        $errors[] = 'Please provide notes on sizing, fit, and condition.';
    }

    // Photo upload handling
    $image_path = 'assets/images/sneakers/placeholder_sneaker.jpg';
    if (isset($_FILES['sneaker_image']) && $_FILES['sneaker_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['sneaker_image']['tmp_name'];
        $file_name = $_FILES['sneaker_image']['name'];
        $file_size = $_FILES['sneaker_image']['size'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Only JPG, PNG, WEBP, or SVG images are allowed.';
        } elseif ($file_size > 5 * 1024 * 1024) {
            $errors[] = 'Image file size must be under 5MB.';
        } else {
            $new_name = 'snk_' . uniqid() . '.' . $ext;
            $dest = __DIR__ . '/assets/uploads/sneakers/' . $new_name;
            if (move_uploaded_file($file_tmp, $dest)) {
                $image_path = 'assets/uploads/sneakers/' . $new_name;
            }
        }
    }

    // Insert into sneakers table with Prepared Statement
    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO sneakers (title, brand, size, colour, price, mrp, `condition`, image, description, seller_id, is_featured, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'available')
        ");
        $stmt->bind_param("ssssddsssi", $title, $brand, $size, $colour, $price, $mrp_val, $condition, $image_path, $description, $user_id);

        if ($stmt->execute()) {
            $new_snk_id = $stmt->insert_id;
            $stmt->close();
            set_flash('success', 'Your sneaker listing "' . $title . '" is now live on CampusKart Sneaker Store!');
            header("Location: sneaker-details.php?id=" . $new_snk_id);
            exit;
        } else {
            $errors[] = 'Failed to publish sneaker listing: ' . $conn->error;
            $stmt->close();
        }
    }
}

$page_title = 'List Sneakers for Sale';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="sneakers.php" class="text-decoration-none">Sneaker Store</a></li>
            <li class="breadcrumb-item active" aria-current="page">List Sneakers</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-card shadow-sm">
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                    <span class="brand-badge bg-danger text-white"><i class="bi bi-tag-fill"></i></span>
                    <div>
                        <h2 class="fw-bold mb-0">List Sneakers for Sale</h2>
                        <small class="text-muted">Turn your clean, pre-loved kicks into cash. Meet peers safely on campus.</small>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="sell-sneaker.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Sneaker Model / Name <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Nike Air Force 1 '07 Triple White" value="<?= e($title) ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Brand <span class="text-danger">*</span></label>
                            <select name="brand" class="form-select" required>
                                <?php 
                                $brand_opts = ['Nike', 'Adidas', 'Jordan', 'Puma', 'Vans', 'Converse', 'New Balance', 'Asics', 'Reebok', 'Other'];
                                foreach ($brand_opts as $bo): ?>
                                    <option value="<?= $bo ?>" <?= ($brand === $bo) ? 'selected' : '' ?>><?= $bo ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Shoe Size (UK) <span class="text-danger">*</span></label>
                            <select name="size" class="form-select" required>
                                <?php 
                                $size_opts = ['UK 6', 'UK 6.5', 'UK 7', 'UK 7.5', 'UK 8', 'UK 8.5', 'UK 9', 'UK 9.5', 'UK 10', 'UK 11', 'UK 12'];
                                foreach ($size_opts as $so): ?>
                                    <option value="<?= $so ?>" <?= ($size === $so) ? 'selected' : '' ?>><?= $so ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Primary Colour <span class="text-danger">*</span></label>
                            <select name="colour" class="form-select" required>
                                <?php 
                                $col_opts = ['White', 'Black', 'Red', 'Blue', 'Grey', 'Green', 'Yellow', 'Beige', 'Multi-colour'];
                                foreach ($col_opts as $co): ?>
                                    <option value="<?= $co ?>" <?= ($colour === $co) ? 'selected' : '' ?>><?= $co ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Physical Condition <span class="text-danger">*</span></label>
                            <select name="condition" class="form-select" required>
                                <option value="New" <?= ($condition === 'New') ? 'selected' : '' ?>>New / Deadstock (Unworn)</option>
                                <option value="Like New" <?= ($condition === 'Like New') ? 'selected' : '' ?>>Like New (Worn 1-3 times)</option>
                                <option value="Good" <?= ($condition === 'Good') ? 'selected' : '' ?>>Good (Clean, minor wear)</option>
                                <option value="Fair" <?= ($condition === 'Fair') ? 'selected' : '' ?>>Fair (Noticeable creasing)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Your Selling Price (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="10" name="price" class="form-control" placeholder="e.g. 2400" value="<?= e($price) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Original MRP (₹) <span class="text-muted small">(Optional)</span></label>
                            <input type="number" step="10" name="mrp" class="form-control" placeholder="e.g. 7495" value="<?= e($mrp) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description & Sizing Notes <span class="text-danger">*</span></label>
                        <textarea name="description" rows="4" class="form-control" placeholder="Mention whether original shoe box is included, how the shoes fit (true to size or runs small), and hygiene care..." required><?= e($description) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Sneaker Photo</label>
                        <input class="form-control" type="file" name="sneaker_image" accept="image/*">
                        <small class="text-muted" style="font-size: 0.75rem;">Upload a clear photo of both shoes and the outsole. Supported: JPG, PNG, WEBP (Max 5MB).</small>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-campus-yellow btn-lg flex-grow-1 fw-bold">
                            <i class="bi bi-cloud-upload-fill me-1"></i> Publish Sneaker Listing
                        </button>
                        <a href="sneakers.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

