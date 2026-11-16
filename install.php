<?php
// install.php - One-Click Database Setup & Sample Data Migrator for CampusKart
$host = 'localhost';
$user = 'root';
$pass = '';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mysqli = new mysqli($host, $user, $pass);
        if ($mysqli->connect_error) {
            throw new Exception("Connection failed: " . $mysqli->connect_error);
        }

        $sql_file = __DIR__ . '/database/campuskart.sql';
        if (!file_exists($sql_file)) {
            throw new Exception("SQL file not found at " . $sql_file);
        }

        $sql_content = file_get_contents($sql_file);
        $mysqli->multi_query($sql_content);

        // Flush all multi-query results
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());

        $message = "Database 'campuskart_db' and tables created and seeded successfully!";
        $mysqli->close();
    } catch (Exception $e) {
        $error = "Setup failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CampusKart Database Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 p-4 rounded-4">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">CampusKart Setup</h2>
                        <p class="text-muted small">Initialize MySQL Database and Sample College Listings</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($message) ?>
                            <div class="mt-2">
                                <a href="index.php" class="btn btn-success btn-sm">Go to CampusKart Store</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Database Host</label>
                            <input type="text" class="form-control" value="localhost" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Database User</label>
                            <input type="text" class="form-control" value="root" readonly>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Database Name</label>
                            <input type="text" class="form-control" value="campuskart_db" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            Run Database Setup & Seed Data
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="index.php" class="small text-muted text-decoration-none">Back to CampusKart</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

