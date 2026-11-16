<?php
// contact.php - About CampusKart, Team Info, Student FAQ & Contact Form
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$name = '';
$email = '';
$subject = '';
$message = '';

if (is_logged_in()) {
    $name = current_user_name();
    $email = $_SESSION['user_email'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Server-side validation
    if (empty($name)) {
        $errors[] = 'Your name is required.';
    }

    if (empty($email)) {
        $errors[] = 'Your email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email format.';
    }

    if (empty($subject)) {
        $errors[] = 'Message subject is required.';
    }

    if (empty($message)) {
        $errors[] = 'Please enter your message or query.';
    } elseif (strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters long.';
    }

    // Insert message into contact_messages table
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        if ($stmt->execute()) {
            set_flash('success', 'Thank you, ' . $name . '! Your message has been sent to the CampusKart team. We will get back to you shortly.');
            $stmt->close();
            header("Location: contact.php");
            exit;
        } else {
            $errors[] = 'Failed to submit message. Please try again.';
            $stmt->close();
        }
    }
}

$page_title = 'About & Contact Us';
require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero Section -->
<div class="bg-primary text-white py-5 mb-5" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%) !important;">
    <div class="container text-center py-4">
        <span class="badge bg-warning text-dark fw-bold px-3 py-2 text-uppercase mb-3">Our Mission</span>
        <h1 class="display-4 fw-bold">Connecting Campus Students</h1>
        <p class="lead col-lg-8 mx-auto text-light opacity-90">
            CampusKart was born in college hostels to solve a universal student challenge: buying expensive textbooks for one semester and discarding them afterwards.
        </p>
    </div>
</div>

<div class="container mb-5">
    <!-- 2. About CampusKart / Vision -->
    <div class="row align-items-center g-5 mb-5 pb-4 border-bottom">
        <div class="col-lg-6">
            <span class="text-primary fw-bold text-uppercase small">Why We Built CampusKart</span>
            <h2 class="fw-bold text-dark mt-1 mb-3">Affordable, Sustainable & Peer-to-Peer</h2>
            <p class="text-secondary" style="line-height: 1.8;">
                Every semester, college students spend thousands on technical references, engineering handbooks, and calculators that are rarely used after final exams. Meanwhile, incoming juniors struggle to afford those very same books.
            </p>
            <p class="text-secondary" style="line-height: 1.8;">
                CampusKart eliminates middlemen and costly courier fees. We connect students within the same campus or nearby colleges for safe, in-person handovers right inside the library or cafeteria.
            </p>
            <div class="row g-3 mt-2">
                <div class="col-sm-6">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-patch-check-fill text-success fs-4"></i>
                        <span class="fw-bold text-dark">Verified College Peers</span>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-wallet-fill text-warning fs-4"></i>
                        <span class="fw-bold text-dark">Zero Commission</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="row g-3">
                <div class="col-6">
                    <div class="p-4 bg-white rounded-4 border shadow-sm text-center">
                        <h2 class="fw-bold text-primary mb-1">70%</h2>
                        <small class="text-muted fw-semibold">Average Student Savings on Books</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-4 bg-white rounded-4 border shadow-sm text-center">
                        <h2 class="fw-bold text-warning mb-1">0 ₹</h2>
                        <small class="text-muted fw-semibold">Zero Courier or Delivery Fees</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-4 bg-white rounded-4 border shadow-sm text-center">
                        <h2 class="fw-bold text-success mb-1">100%</h2>
                        <small class="text-muted fw-semibold">Physical Inspection Before Cash</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-4 bg-white rounded-4 border shadow-sm text-center">
                        <h2 class="fw-bold text-info mb-1">5+</h2>
                        <small class="text-muted fw-semibold">Campus Pickup Spots</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Team Info (Requirement: Team Info) -->
    <div class="mb-5 pb-4 border-bottom">
        <div class="text-center mb-5">
            <span class="text-primary fw-bold text-uppercase small">The Minds Behind The Project</span>
            <h2 class="fw-bold text-dark">Meet The CampusKart Team</h2>
            <p class="text-muted col-lg-6 mx-auto">
                A team of passionate student developers building practical solutions for college life.
            </p>
        </div>

        <div class="row g-4 justify-content-center">
            <!-- Member 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="bg-white rounded-4 border p-4 text-center shadow-sm h-100">
                    <div class="brand-badge bg-primary text-white mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Aarav Sharma</h5>
                    <p class="text-primary fw-semibold small mb-2">Founder & Full Stack Lead</p>
                    <p class="text-muted small mb-3">
                        Computer Science Senior. Passionate about web architecture, database optimization, and making college resources accessible.
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <span class="badge bg-light text-dark border">PHP / MySQLi</span>
                        <span class="badge bg-light text-dark border">Bootstrap 5</span>
                    </div>
                </div>
            </div>

            <!-- Member 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="bg-white rounded-4 border p-4 text-center shadow-sm h-100">
                    <div class="brand-badge bg-warning text-dark mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Sneha Patel</h5>
                    <p class="text-warning fw-bold small mb-2">UI/UX & Product Design</p>
                    <p class="text-muted small mb-3">
                        IT Engineering 3rd Year. Designed the campus blue & yellow theme, mobile responsive layouts, and condition evaluation system.
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <span class="badge bg-light text-dark border">CSS Theme</span>
                        <span class="badge bg-light text-dark border">UX Flow</span>
                    </div>
                </div>
            </div>

            <!-- Member 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="bg-white rounded-4 border p-4 text-center shadow-sm h-100">
                    <div class="brand-badge bg-success text-white mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Rohan Verma</h5>
                    <p class="text-success fw-bold small mb-2">Campus Community & Operations</p>
                    <p class="text-muted small mb-3">
                        Electronics Junior. Oversees hostel hub pickup spots, seller verification, and semester textbook bundle collections.
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <span class="badge bg-light text-dark border">Campus Ops</span>
                        <span class="badge bg-light text-dark border">Quality Check</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Contact Form & FAQ -->
    <div class="row g-5">
        <!-- Contact Form -->
        <div class="col-lg-6">
            <div class="form-card shadow-sm h-100">
                <h4 class="fw-bold mb-2"><i class="bi bi-envelope-paper-fill text-primary me-2"></i>Send Us a Message</h4>
                <p class="text-muted small mb-4">Have questions about a book, selling your gadgets, or want to suggest a campus hub? Drop a note below.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="contact.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Your Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= e($name) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Your Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g. Question about Semester Bundles" value="<?= e($subject) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Message Details <span class="text-danger">*</span></label>
                        <textarea name="message" rows="4" class="form-control" placeholder="Type your query or suggestion..." required><?= e($message) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-campus w-100 py-2 fw-bold">
                        <i class="bi bi-send-fill me-1"></i> Send Message
                    </button>
                </form>
            </div>
        </div>

        <!-- Campus Safety FAQ Accordion -->
        <div class="col-lg-6" id="faq">
            <h4 class="fw-bold mb-2"><i class="bi bi-question-circle-fill text-warning me-2"></i>Frequently Asked Questions</h4>
            <p class="text-muted small mb-4">Everything you need to know about buying and selling on campus.</p>

            <div class="d-flex flex-column gap-2" id="campusFaqAccordion">
                <!-- FAQ 1 (Pure HTML5 Zero JS) -->
                <details class="bg-white border rounded-3 p-3 shadow-sm" open>
                    <summary class="fw-bold text-dark" style="cursor: pointer; list-style: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>How does in-person campus handover work?</span>
                            <i class="bi bi-chevron-down text-primary small"></i>
                        </div>
                    </summary>
                    <div class="text-secondary small mt-3 pt-2 border-top">
                        When you place an order, the seller receives your designated pickup point (e.g. Central Library, Hostel Common Room). You both coordinate a convenient time via phone/WhatsApp, meet in person, inspect the item, and settle payment with physical cash or UPI.
                    </div>
                </details>

                <!-- FAQ 2 -->
                <details class="bg-white border rounded-3 p-3 shadow-sm">
                    <summary class="fw-bold text-dark" style="cursor: pointer; list-style: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>What do the Condition badges mean?</span>
                            <i class="bi bi-chevron-down text-primary small"></i>
                        </div>
                    </summary>
                    <div class="text-secondary small mt-3 pt-2 border-top">
                        <ul class="mb-0 ps-3">
                            <li><strong>New:</strong> Unopened, unread, clean mint condition.</li>
                            <li><strong>Like New:</strong> Used for a couple of lectures, no markings or stains.</li>
                            <li><strong>Good:</strong> Solid condition, all pages intact, few pencil notes.</li>
                            <li><strong>Fair:</strong> Visible highlighter marks, bent corners, but completely readable.</li>
                        </ul>
                    </div>
                </details>

                <!-- FAQ 3 -->
                <details class="bg-white border rounded-3 p-3 shadow-sm">
                    <summary class="fw-bold text-dark" style="cursor: pointer; list-style: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Can I list my own books and gadgets?</span>
                            <i class="bi bi-chevron-down text-primary small"></i>
                        </div>
                    </summary>
                    <div class="text-secondary small mt-3 pt-2 border-top">
                        Yes! Just log into your student account and click the yellow <strong>"Sell Your Item"</strong> button in the top navbar. Upload a photo, pick the condition, set your price, and your listing goes live immediately to college peers.
                    </div>
                </details>

                <!-- FAQ 4 -->
                <details class="bg-white border rounded-3 p-3 shadow-sm">
                    <summary class="fw-bold text-dark" style="cursor: pointer; list-style: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Is there any fee or commission for selling?</span>
                            <i class="bi bi-chevron-down text-primary small"></i>
                        </div>
                    </summary>
                    <div class="text-secondary small mt-3 pt-2 border-top">
                        Zero! CampusKart is completely free for college students. You keep 100% of the price you charge for your textbooks or electronic gadgets.
                    </div>
                </details>
            </div>

            <!-- Campus Support Direct Contact Box -->
            <div class="mt-4 p-3 bg-white rounded-4 border shadow-sm">
                <div class="d-flex align-items-center gap-3">
                    <div class="brand-badge bg-primary text-white"><i class="bi bi-geo-alt-fill"></i></div>
                    <div>
                        <span class="fw-bold text-dark d-block">CampusKart Helpdesk Hub</span>
                        <small class="text-muted">Student Activity Center, Room 102 • Open Mon–Sat 10 AM to 6 PM</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

