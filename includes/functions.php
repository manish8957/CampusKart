<?php
// includes/functions.php - Helper Functions & Security Utilities

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escape string for safe HTML output (XSS protection)
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Check if a user is currently logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user ID
 */
function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current logged in user name
 */
function current_user_name() {
    return $_SESSION['user_name'] ?? 'Student';
}

/**
 * Enforce login on protected pages (Cart, Checkout, Sell Item, Orders)
 */
function require_login($redirect_target = null) {
    if (!is_logged_in()) {
        $target = $redirect_target ?: $_SERVER['REQUEST_URI'];
        $_SESSION['redirect_after_login'] = $target;
        set_flash('warning', 'Please sign in to access that page.');
        header("Location: login.php");
        exit;
    }
}

/**
 * Set a flash alert message (success, danger, warning, info)
 */
function set_flash($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Render flash messages as Bootstrap alerts (CSS/HTML only)
 */
function display_flash() {
    if (!empty($_SESSION['flash_messages'])) {
        foreach ($_SESSION['flash_messages'] as $msg) {
            $alertType = htmlspecialchars($msg['type']);
            $alertText = htmlspecialchars($msg['message']);
            echo '<div class="alert alert-' . $alertType . ' campus-alert shadow-sm" role="alert">';
            if ($alertType === 'success') {
                echo '<i class="bi bi-check-circle-fill me-2"></i>';
            } elseif ($alertType === 'danger') {
                echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
            } elseif ($alertType === 'warning') {
                echo '<i class="bi bi-exclamation-circle-fill me-2"></i>';
            } else {
                echo '<i class="bi bi-info-circle-fill me-2"></i>';
            }
            echo $alertText;
            echo '</div>';
        }
        unset($_SESSION['flash_messages']);
    }
}

/**
 * Return valid product image path or fallback (zero JS)
 */
function product_img($path) {
    if (!empty($path) && file_exists(__DIR__ . '/../' . $path)) {
        return e($path);
    }
    return 'assets/images/placeholder.jpg';
}

/**
 * Format currency in Indian Rupees
 */
function format_price($amount) {
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Generate condition badge HTML with custom colors
 */
function condition_badge($condition) {
    $cond = strtolower(trim($condition));
    $badgeClass = 'bg-secondary';
    $icon = 'bi-tag';

    if ($cond === 'new') {
        $badgeClass = 'badge-condition-new';
        $icon = 'bi-stars';
    } elseif ($cond === 'like new') {
        $badgeClass = 'badge-condition-likenew';
        $icon = 'bi-shield-check';
    } elseif ($cond === 'good') {
        $badgeClass = 'badge-condition-good';
        $icon = 'bi-hand-thumbs-up';
    } elseif ($cond === 'fair') {
        $badgeClass = 'badge-condition-fair';
        $icon = 'bi-bookmark-check';
    }

    return '<span class="badge ' . $badgeClass . '"><i class="bi ' . $icon . ' me-1"></i>' . e($condition) . '</span>';
}

/**
 * Calculate discount percentage
 */
function discount_percentage($mrp, $price) {
    if ($mrp > 0 && $mrp > $price) {
        $discount = (($mrp - $price) / $mrp) * 100;
        return round($discount);
    }
    return 0;
}

/**
 * Get total items in cart for a user
 */
function get_cart_count($conn, $user_id) {
    if (!$user_id) return 0;
    $stmt = $conn->prepare("SELECT SUM(qty) as total_qty FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int)($row['total_qty'] ?? 0);
}

/**
 * Get all active categories
 */
function get_categories($conn) {
    $categories = [];
    $res = $conn->query("SELECT * FROM categories ORDER BY name ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    return $categories;
}

/**
 * Get total items in wishlist for a user (Books & Gadgets)
 */
function get_wishlist_count($conn, $user_id) {
    if (!$user_id) return 0;
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

/**
 * Check if a product is in user's wishlist
 */
function is_in_wishlist($conn, $user_id, $product_id) {
    if (!$user_id || !$product_id) return false;
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Get total sneakers in wishlist for a user
 */
function get_sneaker_wishlist_count($conn, $user_id) {
    if (!$user_id) return 0;
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM sneaker_wishlist WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

/**
 * Check if a sneaker is in user's wishlist
 */
function is_in_sneaker_wishlist($conn, $user_id, $sneaker_id) {
    if (!$user_id || !$sneaker_id) return false;
    $stmt = $conn->prepare("SELECT id FROM sneaker_wishlist WHERE user_id = ? AND sneaker_id = ?");
    $stmt->bind_param("ii", $user_id, $sneaker_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Count pending pickup requests for items listed by this seller (Books & Sneakers)
 */
function get_seller_pending_orders_count($conn, $seller_id) {
    if (!$seller_id) return 0;
    
    // Count pending book orders
    $stmt1 = $conn->prepare("
        SELECT COUNT(DISTINCT o.id) AS pending_count
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.seller_id = ? AND o.status = 'Pending Pickup'
    ");
    $stmt1->bind_param("i", $seller_id);
    $stmt1->execute();
    $res1 = $stmt1->get_result()->fetch_assoc();
    $stmt1->close();

    // Count pending sneaker orders
    $stmt2 = $conn->prepare("
        SELECT COUNT(DISTINCT so.id) AS pending_count
        FROM sneaker_orders so
        JOIN sneaker_order_items soi ON so.id = soi.order_id
        JOIN sneakers s ON soi.sneaker_id = s.id
        WHERE s.seller_id = ? AND so.status = 'Pending Pickup'
    ");
    $stmt2->bind_param("i", $seller_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    return (int)($res1['pending_count'] ?? 0) + (int)($res2['pending_count'] ?? 0);
}

/**
 * Get total items in Sneaker Cart
 */
function get_sneaker_cart_count($conn, $user_id) {
    if (!$user_id) return 0;
    $stmt = $conn->prepare("SELECT SUM(qty) as total_qty FROM sneaker_cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($res['total_qty'] ?? 0);
}

/**
 * Get total items in Canteen Cart
 */
function get_canteen_cart_count($conn, $user_id) {
    if (!$user_id) return 0;
    $stmt = $conn->prepare("SELECT SUM(qty) as total_qty FROM canteen_cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($res['total_qty'] ?? 0);
}

/**
 * Render Veg / Non-Veg dietary indicator badge
 */
function food_type_badge($type) {
    if (strtolower(trim($type)) === 'non-veg') {
        return '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 0.6rem;"></i>Non-Veg</span>';
    }
    return '<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1"><i class="bi bi-circle-fill me-1" style="font-size: 0.6rem;"></i>Pure Veg</span>';
}

/**
 * Fetch distinct brands from sneakers table
 */
function get_sneaker_brands($conn) {
    $brands = [];
    $res = $conn->query("SELECT DISTINCT brand FROM sneakers ORDER BY brand ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $brands[] = $row['brand'];
        }
    }
    return $brands;
}

/**
 * Fetch distinct sizes from sneakers table
 */
function get_sneaker_sizes($conn) {
    $sizes = [];
    $res = $conn->query("SELECT DISTINCT size FROM sneakers ORDER BY CAST(SUBSTRING(size, 4) AS UNSIGNED) ASC, size ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $sizes[] = $row['size'];
        }
    }
    return $sizes;
}

/**
 * Fetch distinct colours from sneakers table
 */
function get_sneaker_colours($conn) {
    $colours = [];
    $res = $conn->query("SELECT DISTINCT colour FROM sneakers ORDER BY colour ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $colours[] = $row['colour'];
        }
    }
    return $colours;
}



