<?php
// includes/header.php - Global HTML Head
if (!isset($conn)) {
    require_once __DIR__ . '/../config/db.php';
}
require_once __DIR__ . '/functions.php';

$pageTitle = isset($page_title) ? e($page_title) . ' | CampusKart' : 'CampusKart | Second-Hand College Books & Gadgets';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <!-- Google Fonts: Outfit & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- CampusKart Custom CSS (with auto-cache busting) -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>

<!-- Flash Message Notifications -->
<div class="container mt-3">
    <?php display_flash(); ?>
</div>
<main>

