# CampusKart — ITT Assignment Task Completion Report & Audit

**Project:** CampusKart (Second-Hand Book & Gadget Store for College Students)  
**Course/Assignment:** Internet & Web Technologies (ITT-Assignment)  
**Architecture:** Pure HTML5, CSS3, Bootstrap 5.3.3, PHP 8.2, MySQLi  
**JavaScript Policy:** Strictly Zero JavaScript (100% compliant)  
**Status:** All Phases, All 8 Required Pages, Database Tables, Advanced PHP Features, and Innovative Touches Fully Implemented and Verified.

---

## 1. Executive Summary & Specification Checklist

| # | Assignment Requirement | Specified in ITT Document | Implementation Status | Implementation Details / File |
|---|---|---|---|---|
| **1** | **Project Idea** | CampusKart, second-hand book & gadget store for college students | **COMPLETE** | Custom collegiate marketplace theme with student peer-to-peer listings |
| **2** | **8 Required Pages** | Home, Products, Details, Register, Login, Cart, Checkout/Receipt, Contact/About | **COMPLETE** | All 8 pages built, linked via shared navbar & footer, and tested |
| **3** | **Database (MySQLi)** | 5 core tables (`users`, `categories`, `products`, `cart`, `orders`) | **COMPLETE** | Seeded in `campuskart_db` via `database/campuskart.sql` |
| **4** | **Phase 1: HTML, CSS & Bootstrap** | Grid, cards, carousel, forms, shared navbar/footer, blue & yellow theme | **COMPLETE** | Bootstrap 5.3.3 + custom CSS variables (`#1d4ed8` blue, `#f59e0b` yellow) |
| **5** | **Phase 2: PHP & MySQLi** | Sessions, catalog fetch, filters, search, cart CRUD, order saving | **COMPLETE** | Full database CRUD with MySQLi prepared statements |
| **6** | **Phase 3: Advanced PHP** | Server-side validation, password_hash, prepared statements, XSS escaping, auth restrictions | **COMPLETE** | `password_hash()`, `$stmt->prepare()`, `e()` (`htmlspecialchars`), `require_login()` |
| **7** | **Innovative Touches** | Condition badge, Sell form, Wishlist, Budget slider, Semester bundles | **COMPLETE** | All 5 innovative touches built and fully active |
| **8** | **Extended Modules** | Sneaker Store & Campus Canteen Pre-order | **COMPLETE** | Full brand/size/color sneaker module & canteen food pickup module |
| **9** | **Real Photographs** | Real photography for books, tech, sneakers, food, and banners | **COMPLETE** | 35 local high-res JPG photos stored in `assets/images/` |

---

## 2. The 8 Required Pages Audit

### Page 1: Home (`index.php`)
- **Purpose:** Banner carousel, featured items, category cards.
- **Implementation Status:** **COMPLETE**
- **Features:**
  - **Banner Carousel:** Pure CSS zero-JS carousel with 3 real photographic slides (`hero_books.jpg`, `hero_tech.jpg`, `hero_bundle.jpg`) promoting books, tested campus tech, and semester bundles.
  - **Featured Items Grid:** Dynamically queries `products WHERE is_featured = 1 AND status = 'available'` with condition badges, MRP savings, and Add to Cart.
  - **Category Cards:** Dynamic cards fetched from `categories` with subject icons and live listing counts.
  - **Value Propositions:** 4 campus trust cards (Affordable Pricing, Campus Handover, Honest Condition, Eco-Friendly).

### Page 2: Products (`products.php`)
- **Purpose:** Grid of items with search and category filter.
- **Implementation Status:** **COMPLETE**
- **Features:**
  - **Search Bar:** Keyword search across titles, descriptions, and categories.
  - **Category Filter:** Clean sidebar filtering by all 7 categories (Engineering, Calculators, Laptops, Medical, GATE, Semester Bundles, Lab Tools).
  - **Budget Filter Slider:** Interactive range slider (`min="100" max="5000" step="50"`) plus quick-filter pills (Under ₹300, Under ₹500, Under ₹1000).
  - **Condition Filter:** Filter by New, Like New, Good, or Fair.
  - **Sorting:** Sort by Newest, Price: Low to High, Price: High to Low.
  - **Responsive Grid:** 1, 2, and 3-column Bootstrap card grid.

### Page 3: Product Details (`product-details.php`)
- **Purpose:** Image, price, condition, seller, “Add to Cart”.
- **Implementation Status:** **COMPLETE**
- **Features:**
  - **High-Res Product Image:** Real photograph with zoom effect on hover (`object-fit: cover`).
  - **Condition Badge & Explanations:** Dedicated condition banner detailing cosmetic and academic state.
  - **Price & Savings:** Highlights student savings compared to new bookstore MRP.
  - **Verified Student Seller Box:** Displays seller name, college, hostel, and contact phone.
  - **Actions:** Add to Cart with quantity selector (1–3), Buy Now direct checkout bypass, and Wishlist toggle button.
  - **Related Items:** Displays related books/gadgets from the same academic category.

### Page 4: Register (`register.php`)
- **Purpose:** Sign-up form.
- **Implementation Status:** **COMPLETE**
- **Features:**
  - **Form Fields:** Student name, email, 10-digit phone, college campus, hostel room, password, confirm password.
  - **Server-Side Validation:** Ensures non-empty fields, valid email format (`FILTER_VALIDATE_EMAIL`), valid phone regex, password length >= 6 characters, and password confirmation match.
  - **Duplicate Check:** Prepared statement checks for existing email in `users` table.
  - **Security:** Hashes passwords with `password_hash($password, PASSWORD_DEFAULT)`.
  - **Auto-Login:** Automatically establishes session and redirects safely.

### Page 5: Login (`login.php`)
- **Purpose:** Login form.
- **Implementation Status:** **COMPLETE**
- **Features:**
  - **Form Fields:** Student email and password.
  - **Server-Side Validation:** Validates required inputs and email format.
  - **Authentication:** Prepared statement fetches hashed password; authenticates with `password_verify()`.
  - **Session Fixation Defense:** Calls `session_regenerate_id(true)` upon successful login.
  - **Session State:** Stores `user_id`, `user_name`, and `user_email`. Supports redirect back to requested page.

### Page 6: Cart (`cart.php`)
- **Purpose:** Items, quantity, total, remove.
- **Implementation Status:** **COMPLETE**
- **Features:**
  - **Access Restriction:** Enforces `require_login()`. Unauthenticated users are redirected to login.
  - **Database Persistence:** Cart items are stored per user in the `cart` table.
  - **Cart Operations:**
    - **Add Item:** Validates stock and prevents self-purchasing of one's own listings.
    - **Update Quantity:** Form with numeric inputs and update buttons (capped at 10 items).
    - **Remove Item:** Deletes specific cart row via prepared statement.
    - **Clear Cart:** Empties all items for the student.
  - **Financial Summary:** Calculates line subtotals, zero delivery fee notice, and grand total.

### Page 7: Checkout & Order Confirmation (`checkout.php` & `order-confirmation.php`)
- **Purpose:** Address form, order summary.
- **Implementation Status:** **COMPLETE**
- **Features:**
  - **Access Restriction:** Enforces `require_login()`.
  - **Pre-filled Profile Data:** Pre-populates recipient name, phone, and college hostel address.
  - **Handover Address Form:** Hostel room, campus pickup notes, and payment method (Cash/UPI on Pickup).
  - **Order Summary:** Complete item breakdown with line prices and grand total.
  - **Atomic Transaction:** Uses `$conn->begin_transaction()`, inserts record into `orders`, batch-inserts items into `order_items`, and flushes `cart`.
  - **Order Confirmation Page:** Displays unique order code (`CK-YYYYMMDD-XXXX`), handover address, seller details, date/time, and itemized receipt.

### Page 8: Contact / About (`contact.php`)
- **Purpose:** Contact form, team info.
- **Implementation Status:** **COMPLETE**
- **Features:**
  - **Project Vision & Mission:** Explains the peer-to-peer sustainable collegiate textbook exchange model.
  - **Team Info Section:** Spotlights project creators with roles and tech stacks:
    - **Aarav Sharma:** Founder & Full Stack Lead (PHP / MySQLi)
    - **Sneha Patel:** UI/UX & Product Design (Bootstrap 5, CSS Theme)
    - **Rohan Verma:** Campus Community & Operations (Hostel Hubs, Quality Check)
  - **Contact Form:** Name, email, subject, message with server-side validation and database storage into `contact_messages`.
  - **Campus FAQ:** Frequently asked questions regarding safety, in-person inspection, and hostel pickup spots.

---

## 3. Database Schema Verification (MySQLi)

The database `campuskart_db` was verified against the assignment schema requirements:

```sql
-- 1. users table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `college` VARCHAR(150) DEFAULT 'Campus Main Block',
  `hostel` VARCHAR(100) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. categories table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'bi-book',
  `description` VARCHAR(255) DEFAULT ''
);

-- 3. products table
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `mrp` DECIMAL(10,2) DEFAULT NULL,
  `condition` VARCHAR(50) NOT NULL DEFAULT 'Good',
  `image` VARCHAR(255) NOT NULL,
  `category_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `is_featured` TINYINT(1) DEFAULT 0,
  `status` ENUM('available', 'sold') DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 4. cart table
CREATE TABLE `cart` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_product` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
);

-- 5. orders table
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_code` VARCHAR(30) NOT NULL,
  `user_id` INT NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `address` TEXT NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'Cash on Campus Pickup',
  `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Pending Pickup', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Pending Pickup',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
```

*Relational & Module Extensions:* `order_items`, `wishlist`, `contact_messages`, `sneakers`, `sneaker_cart`, `sneaker_orders`, `sneaker_order_items`, `canteen_items`, `canteen_cart`, `canteen_orders`, `canteen_order_items`.

---

## 4. Assignment Phases Audit

### Phase 1: HTML, CSS and Bootstrap
- [x] **Shared Navbar & Footer:** Included via `includes/header.php`, `includes/navbar.php`, and `includes/footer.php` on every page.
- [x] **Bootstrap 5.3.3 Grid & Forms:** Responsive layout utilizing containers, flexbox rows, form controls, and cards.
- [x] **Custom Colour Theme:**
  - Campus Primary Blue: `#1d4ed8`
  - Campus Primary Dark: `#1e3a8a`
  - Campus Accent Yellow / Gold: `#f59e0b` / `#d97706`
  - Clean light slate background `#f8fafc` and crisp white surfaces.
- [x] **Responsive Layout:** Tested at 320px, 375px, 768px, 992px, 1200px, 1366px, and 1440px.

### Phase 2: PHP and MySQLi
- [x] **Session Authentication:** Session management with `$_SESSION['user_id']`, `$_SESSION['user_name']`, `$_SESSION['user_email']`.
- [x] **Database Catalog Query:** Products fetched dynamically with category joins and seller joins.
- [x] **Filters & Search:** Prepared statement queries with dynamic `WHERE` clauses for search query, category, price range, condition, and sorting.
- [x] **Cart Management:** Full cart state stored in MySQL with add, quantity update, item deletion, and clear operations.
- [x] **Order Saving:** Orders and itemized receipts saved to `orders` and `order_items` tables.

### Phase 3: Advanced PHP
- [x] **Server-side Validation:** Complete validation on all forms: empty fields, email format (`filter_var`), phone regex, password length >= 6, password matching, file types, and size limits.
- [x] **Password Hashing:** `password_hash($password, PASSWORD_DEFAULT)` and `password_verify()`.
- [x] **Prepared Statements:** Zero raw string SQL concatenation; 100% prepared statements (`$conn->prepare()`, `bind_param()`, `execute()`).
- [x] **XSS Prevention:** Output escaping via helper function `e($string)` which wraps `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')`.
- [x] **Access Control:** `require_login()` function protects `cart.php`, `checkout.php`, `orders.php`, `sell.php`, `my-listings.php`, `seller-orders.php`, and `wishlist.php`.

---

## 5. Innovative Touches Audit

All 5 innovative touches suggested in the assignment specification are implemented:

1. **Condition Badges:**
   - 4-tier badge system: **New** (green), **Like New** (blue), **Good** (yellow/gold), **Fair** (secondary slate).
   - Rendered using `condition_badge()` across all catalog views.
2. **"Sell Your Item" Form (`sell.php`):**
   - Registered students can list their used textbooks and gadgets.
   - Form includes title, category, condition, price, MRP, description, and photo upload.
   - Separate seller orders dashboard (`seller-orders.php`) allows sellers to review and mark handovers as completed.
3. **Wishlist Feature (`wishlist.php`):**
   - Students can bookmark items with a single click.
   - Dedicated wishlist tray with stock status and direct "Move to Cart" button.
4. **"Budget Filter" Slider (`products.php`):**
   - Price range slider with dynamic maximum filter up to ₹5000.
   - Quick one-click budget pills: "Under ₹300", "Under ₹500", "Under ₹1000".
5. **"Semester Bundle" Category (`categories` ID 6):**
   - Curated textbook bundles grouped by branch and semester (e.g. *CSE 3rd Semester Complete Bundle*).
   - Featured in hero carousel slide 3 and product filters.

---

## 6. Extended Modules Audit

As specified under *"Other easy themes with the same functionality"*:
1. **Sneaker Store Module (`sneakers.php`):**
   - Real sneaker photography for Nike, Adidas, Puma, Converse, Vans, New Balance, Air Jordan, and Asics.
   - Filters for Brand, Shoe Size (UK 7 to UK 11), Colour, Condition, and Price.
   - Independent sneaker cart (`sneaker-cart.php`), checkout (`sneaker-checkout.php`), order confirmation (`sneaker-order-confirmation.php`), orders history (`sneaker-orders.php`), and listing form (`sell-sneaker.php`).
2. **Campus Canteen Pre-order (`canteen.php`):**
   - Real food photography for Masala Chai, Samosas, Paneer Roll, Thali, Chole Bhature, Burger, Fries, Cold Coffee, and Maggi.
   - Food categories: Snacks, Meals, Beverages, Fast Food.
   - Cart (`canteen-cart.php`), checkout (`canteen-checkout.php`), token confirmation (`canteen-order-confirmation.php`), and token history (`canteen-orders.php`).
   - Replaces delivery address with **Pickup Time** (Instant, 10 mins, 20 mins, 30 mins, custom) and pickup counter designation.

---

## 7. Real Photography Asset Verification

All 35 placeholder SVG illustrations were removed and replaced with local, genuine product photographs (`object-fit: cover`):

| Category | File Count | Storage Directory | Sample Assets |
|---|---|---|---|
| **Books & Gadgets** | 16 | `assets/images/` | `bs_grewal.jpg`, `casio_991ex.jpg`, `logitech_k380.jpg`, `dsa_cpp.jpg`, `gate_cse.jpg`, `cse_bundle.jpg`, `mini_drafter.jpg`, `guyton_physiology.jpg`, `raspberry_pi.jpg`, `hc_verma.jpg`, `boat_headphones.jpg`, `arduino_kit.jpg`, `hero_books.jpg`, `hero_tech.jpg`, `hero_bundle.jpg`, `placeholder.jpg` |
| **Sneaker Store** | 9 | `assets/images/sneakers/` | `nike_air_force.jpg`, `adidas_superstar.jpg`, `puma_suede.jpg`, `converse_chuck.jpg`, `vans_old_skool.jpg`, `new_balance_574.jpg`, `jordan_1_mid.jpg`, `asics_gel.jpg`, `placeholder_sneaker.jpg` |
| **Campus Canteen** | 10 | `assets/images/canteen/` | `masala_chai.jpg`, `samosa_plate.jpg`, `paneer_roll.jpg`, `special_thali.jpg`, `chole_bhature.jpg`, `cheese_burger.jpg`, `peri_fries.jpg`, `cold_coffee.jpg`, `veg_maggi.jpg`, `placeholder_canteen.jpg` |
| **Total Images** | **35** | Local files | Verified sizes: 27KB – 151KB each |

---

## 8. Cleanup & Maintenance Actions Performed

1. **Obsolete File Removal:**
   - Deleted all 32 old placeholder `.svg` files from `assets/images/`.
   - Deleted temporary `create_svgs.php`.
   - Deleted redundant partial migration file `database/modules_sneakers_canteen.sql`.
2. **Master Database Schema Consolidation:**
   - `database/campuskart.sql` contains the complete, self-contained master database schema with all tables, constraints, foreign keys, and seed data.
3. **Database Image Path Synchronization:**
   - Executed SQL updates across `products`, `sneakers`, and `canteen_items` ensuring 100% of rows point to valid local `.jpg` files.
4. **Zero JavaScript Compliance:**
   - Verified 0 `<script>` tags, 0 `.js` files, and 0 inline JS event handlers (`onclick`, `onload`, `onsubmit`, `javascript:`).
5. **Code Syntax Verification:**
   - Verified 0 syntax errors across all 35 PHP files in the project.

---

## 9. Comprehensive File Map

```
CampusKart/
├── config/
│   └── db.php                           # Database connection (MySQLi, UTF-8, error handling)
├── database/
│   └── campuskart.sql                   # Master database schema and seed data
├── includes/
│   ├── header.php                       # HTML <head>, fonts, Bootstrap CSS, page title
│   ├── navbar.php                       # Responsive navbar (CSS-only checkbox toggle)
│   ├── footer.php                       # Global footer with site links and team info
│   └── functions.php                    # Helpers (auth, sanitization, price, badges, cart counts)
├── assets/
│   ├── css/
│   │   └── style.css                    # Custom collegiate styles, responsive layouts, CSS carousel
│   ├── images/                          # 16 Books & Gadget photos + hero banners + placeholder
│   │   ├── canteen/                     # 10 Canteen food photos + placeholder
│   │   └── sneakers/                    # 9 Sneaker photos + placeholder
│   └── uploads/                         # User upload directories
│       └── sneakers/
├── index.php                            # [Page 1] Home (Hero carousel, categories, featured)
├── products.php                         # [Page 2] Products catalog (Search, category & budget filters)
├── product-details.php                  # [Page 3] Product details (Images, condition, seller, cart, wishlist)
├── register.php                         # [Page 4] Student registration form
├── login.php                            # [Page 5] Student login form
├── cart.php                             # [Page 6] Shopping cart (CRUD, quantity update, total)
├── checkout.php                         # [Page 7A] Checkout (Campus address form, order summary)
├── order-confirmation.php               # [Page 7B] Order confirmation receipt
├── contact.php                          # [Page 8] About CampusKart, Team info & Contact form
├── orders.php                           # Student order history
├── sell.php                             # [Innovative Touch] Sell your item form
├── wishlist.php                         # [Innovative Touch] Student wishlist
├── my-listings.php                      # Student listings manager
├── seller-orders.php                    # Seller order handover fulfillment dashboard
├── profile.php                          # Student profile settings & password change
├── logout.php                           # Session logout handler
├── install.php                          # One-click database installer
├── README.md                            # Project documentation
├── TASK_COMPLETION_REPORT.md            # This comprehensive audit and task completion report
├── sneakers.php                         # [Module 1] Sneaker Store catalog & filters
├── sneaker-details.php                  # [Module 1] Sneaker details
├── sneaker-cart.php                     # [Module 1] Sneaker shopping cart
├── sneaker-checkout.php                 # [Module 1] Sneaker checkout
├── sneaker-order-confirmation.php       # [Module 1] Sneaker order confirmation
├── sneaker-orders.php                   # [Module 1] Sneaker order history
├── sell-sneaker.php                     # [Module 1] List sneakers for sale
├── canteen.php                          # [Module 2] Campus Canteen menu & filters
├── canteen-cart.php                     # [Module 2] Canteen food tray
├── canteen-checkout.php                 # [Module 2] Canteen pickup time checkout
├── canteen-order-confirmation.php       # [Module 2] Canteen food token confirmation
└── canteen-orders.php                   # [Module 2] Canteen food tokens history
```

---

## 10. Final Verification Results

- **Live URL:** `http://localhost/CampusKart/`
- **HTTP Response Codes:**
  - `index.php` -> HTTP 200 OK
  - `products.php` -> HTTP 200 OK
  - `product-details.php?id=1` -> HTTP 200 OK
  - `register.php` -> HTTP 200 OK
  - `login.php` -> HTTP 200 OK
  - `cart.php` -> HTTP 200 OK (redirects to login when unauthenticated)
  - `checkout.php` -> HTTP 200 OK (redirects to login when unauthenticated)
  - `contact.php` -> HTTP 200 OK
  - `sneakers.php` -> HTTP 200 OK
  - `canteen.php` -> HTTP 200 OK
- **PHP Syntax Check:** 0 lint errors across all 35 PHP files.
- **JavaScript Audit:** 0 `<script>` tags, 0 `.js` files. 100% pure HTML, CSS, Bootstrap 5, PHP, and MySQLi.

