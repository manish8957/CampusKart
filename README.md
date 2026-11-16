# CampusKart 🎓
**Second-Hand Book, Gadget & Campus Life Super App for College Students**

A full-stack PHP & MySQL web application designed specifically for college campuses. Students can buy and sell used textbooks, scientific calculators, and tech gear at student-friendly prices with safe, zero-shipping in-person campus handover, plus two fully integrated campus modules: the **Campus Sneaker Store** and the **Campus Canteen Express Pre-Order**.

---

## 🚀 Live Local URL
When XAMPP (Apache + MySQL) is running:
```
http://localhost/CampusKart/
```

One-Click Database Setup (if importing on a fresh machine):
```
http://localhost/CampusKart/install.php
```

---

## 📦 Core Modules

### 1. Second-Hand Books & Student Gadgets (Core Marketplace)
- **8 Core Pages:** Home, Products, Product Details, Register, Login, Cart, Checkout, Contact/About.
- **Innovative Features:** Condition badges (New / Like New / Good / Fair), Semester Bundle sets, live budget price filter, seller management portal, peer-to-peer campus delivery coordination.

### 2. Sneaker Store Module 👟
A dedicated student sneaker marketplace tailored for campus sneakerheads and student sellers:
- **Sneaker Catalog ([`sneakers.php`](file:///c:/xampp/htdocs/CampusKart/sneakers.php)):** Comprehensive kicks catalog with filters for **Brand** (Nike, Jordan, Adidas, Puma, Vans, Converse, New Balance, Asics), **Shoe Size** (UK 6 to UK 11), **Colour** (White, Black, Red, Grey, Blue), **Condition**, and **Budget Price Cap**.
- **Sneaker Details ([`sneaker-details.php`](file:///c:/xampp/htdocs/CampusKart/sneaker-details.php)):** Detailed specs table (Brand, UK Size, Colour, Condition, Seller), MRP savings badge, student seller profile, and instant "Add to Sneaker Cart" or "Buy Now".
- **Sneaker Cart ([`sneaker-cart.php`](file:///c:/xampp/htdocs/CampusKart/sneaker-cart.php)):** Dedicated shopping cart with pure HTML form increment/decrement quantity updates, item deletion, and subtotal calculation.
- **Sneaker Checkout ([`sneaker-checkout.php`](file:///c:/xampp/htdocs/CampusKart/sneaker-checkout.php)):** Coordinate safe campus meetup location (Hostel room, library quadrangle), try-on time notes, and payment preference.
- **Sneaker Confirmation ([`sneaker-order-confirmation.php`](file:///c:/xampp/htdocs/CampusKart/sneaker-order-confirmation.php)):** Handover receipt with unique order code (`SNK-YYYYMMDD-XXXX`) and try-on instructions.
- **Sneaker Orders History ([`sneaker-orders.php`](file:///c:/xampp/htdocs/CampusKart/sneaker-orders.php)):** Track past sneaker purchases and meetup statuses.
- **List Sneakers for Sale ([`sell-sneaker.php`](file:///c:/xampp/htdocs/CampusKart/sell-sneaker.php)):** Allows any authenticated student to list new or used sneakers with brand, size, color, condition, pricing, and photo upload.

### 3. Campus Canteen Express Pre-Order Module 🍛
An express food pre-order system allowing students to beat long lunchtime lines and collect fresh food right as their lectures end:
- **Canteen Menu ([`canteen.php`](file:///c:/xampp/htdocs/CampusKart/canteen.php)):** Full college canteen food menu categorized by **Snacks**, **Meals**, **Beverages**, and **Fast Food**. Features instant diet toggle (**Pure Veg** / **Non-Veg**), preparation time badges (e.g. `Instant`, `3 mins`, `8 mins`), and single-click "Add to Tray" forms.
- **Canteen Food Tray / Cart ([`canteen-cart.php`](file:///c:/xampp/htdocs/CampusKart/canteen-cart.php)):** Real-time tray management with dish previews, prep time calculation, and pure HTML quantity adjustments.
- **Express Checkout with Pickup Time Selection ([`canteen-checkout.php`](file:///c:/xampp/htdocs/CampusKart/canteen-checkout.php)):** **Replaces physical delivery address with college break pickup time slots**:
  - `10:30 AM — Morning Short Break` (Tea & Samosas)
  - `1:00 PM — Lunch Break (Shift 1 - Engineering & Tech)`
  - `1:30 PM — Lunch Break (Shift 2 - Commerce & Arts)`
  - `4:30 PM — Evening Snacks & Post-Lab Break`
  - `6:00 PM — Evening Canteen Pack & Hostel Dinner`
  - `Immediate Express (Next 15 Minutes)` or `Custom Pickup Time`
  - Express Counter Selection (`Counter 1: Meals & Thali`, `Counter 2: Quick Service & Beverages`, `Counter 3: Fast Food & Burgers`).
- **Digital Food Token & Confirmation ([`canteen-order-confirmation.php`](file:///c:/xampp/htdocs/CampusKart/canteen-order-confirmation.php)):** Prominently displays the student's unique digital **TOKEN #** (e.g. `TOKEN #101`), scheduled pickup time, counter number, and kitchen preparation status.
- **Canteen Orders / Token Hub ([`canteen-orders.php`](file:///c:/xampp/htdocs/CampusKart/canteen-orders.php)):** Easy reference for all active and past tokens so students can quickly show their number at the counter.

---

## 🗄️ Database Architecture (MySQLi)

The database `campuskart_db` is configured in [`config/db.php`](file:///c:/xampp/htdocs/CampusKart/config/db.php) and contains 15 tables:

### Core Tables
1. **`users`**: `(id, name, email, password, phone, college, hostel, created_at)`
2. **`categories`**: `(id, name, icon, description)`
3. **`products`**: `(id, title, description, price, mrp, condition, image, category_id, seller_id, is_featured, status, created_at)`
4. **`cart`**: `(id, user_id, product_id, qty, created_at)`
5. **`orders`**: `(id, order_code, user_id, total, address, phone, payment_method, date, status)`
6. **`order_items`**: `(id, order_id, product_id, title, price, qty)`
7. **`wishlist`**: `(id, user_id, product_id, created_at)`
8. **`contact_messages`**: `(id, name, email, subject, message, created_at)`

### Sneaker Store Tables
9. **`sneakers`**: `(id, title, brand, size, colour, price, mrp, condition, image, description, seller_id, is_featured, status, created_at)`
10. **`sneaker_cart`**: `(id, user_id, sneaker_id, qty, created_at)`
11. **`sneaker_orders`**: `(id, order_code, user_id, total, address, phone, payment_method, date, status)`
12. **`sneaker_order_items`**: `(id, order_id, sneaker_id, title, brand, size, colour, price, qty)`

### Campus Canteen Tables
13. **`canteen_items`**: `(id, title, category, food_type, price, image, description, prep_time, is_available, created_at)`
14. **`canteen_cart`**: `(id, user_id, item_id, qty, created_at)`
15. **`canteen_orders`**: `(id, order_code, token_number, user_id, total, pickup_time, pickup_counter, phone, special_notes, payment_method, date, status)`
16. **`canteen_order_items`**: `(id, order_id, item_id, title, category, food_type, price, qty)`

---

## 🔒 Tech Stack & Architecture (Strictly Zero JavaScript)

- **Languages:** HTML5, CSS3, Bootstrap 5.3 (CSS only), PHP 8.2 & MySQLi.
- **Strictly Zero JavaScript:** No Vanilla JS, no jQuery, and no Bootstrap JavaScript bundle. Every interaction (search, multi-criteria filters, cart additions, quantity updates, CSS-only hover dropdowns, CSS carousel, and order placements) is handled completely with pure HTML5 forms and server-side PHP.
- **SQL Injection Prevention:** 100% prepared statements (`$stmt->bind_param(...)`) on all dynamic queries.
- **Password Security:** `password_hash()` and `password_verify()` using standard bcrypt.
- **XSS Prevention:** Full sanitization and escaping using `e()` helper wrapping `htmlspecialchars()`.
- **Session Authentication:** Secured session lifecycle with `require_login()` and login redirects.

---

## 🔑 Demo Student Credentials
Pre-seeded in the database for immediate testing:
- **Email:** `aarav.sharma@campus.edu` | **Password:** `password123`
- **Email:** `sneha.patel@campus.edu` | **Password:** `password123`
- **Email:** `rohan.verma@campus.edu` | **Password:** `password123`
