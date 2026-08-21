<?php
session_start();

// Count cart items
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += (int)$item['qty'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fresh Grocery Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            color: #333;
        }

        /* ── Header ── */
        header {
            background: #2ecc71;
            color: white;
            padding: 18px 30px;
            text-align: center;
        }
        header h1 { font-size: 28px; }
        header p  { font-size: 13px; opacity: .85; margin-top: 4px; }

        /* ── Navbar ── */
        nav {
            background: #27ae60;
            padding: 10px 30px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }
        nav > a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            margin-right: 10px;
            padding: 6px 4px;
        }
        nav > a:hover { text-decoration: underline; }

        .nav-icons {
            display: flex;
            gap: 10px;
            margin-left: auto;
            align-items: center;
        }
        .nav-icons a {
            color: white;
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,.2);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            transition: background .2s;
        }
        .nav-icons a:hover { background: rgba(255,255,255,.35); }
        .cart-badge {
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            font-size: 11px;
            padding: 1px 6px;
            font-weight: bold;
        }
        .nav-icons a.logout-btn { background: rgba(231,76,60,.5); }
        .nav-icons a.logout-btn:hover { background: rgba(231,76,60,.8); }

        /* ── Hero ── */
        .hero {
            background: url('https://images.unsplash.com/photo-1542838132-92c53300491e') no-repeat center/cover;
            color: white;
            padding: 110px 20px;
            text-align: center;
            position: relative;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,.35);
        }
        .hero-content { position: relative; z-index: 1; }
        .hero h1 { font-size: 48px; text-shadow: 0 2px 8px rgba(0,0,0,.4); }
        .hero p  { font-size: 18px; margin-top: 12px; opacity: .9; }
        .hero-btns { margin-top: 28px; display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
        .btn-hero {
            padding: 13px 30px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-hero.primary { background: #2ecc71; color: white; }
        .btn-hero.primary:hover { background: #27ae60; }
        .btn-hero.secondary { background: white; color: #27ae60; }
        .btn-hero.secondary:hover { background: #f0fdf4; }

        /* ── Features strip ── */
        .features {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0;
            background: white;
            border-bottom: 1px solid #eee;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 18px 30px;
            font-size: 13px;
            color: #555;
            border-right: 1px solid #eee;
        }
        .feature-item:last-child { border-right: none; }
        .feature-item i { color: #2ecc71; font-size: 20px; }
        .feature-item strong { display: block; color: #333; font-size: 14px; }

        /* ── Offers Banner ── */
        .offer-banner {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            text-align: center;
            padding: 16px 20px;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: .5px;
        }
        .offer-banner a { color: white; text-decoration: underline; }

        /* ── Section ── */
        .section { padding: 40px 30px; }
        .section-title {
            text-align: center;
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #222;
        }
        .section-sub {
            text-align: center;
            color: #888;
            font-size: 14px;
            margin-bottom: 30px;
        }

        /* ── Category Cards ── */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
            max-width: 900px;
            margin: 0 auto;
        }
        .category-card {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(0,0,0,.08);
            text-align: center;
            transition: transform .25s, box-shadow .25s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 28px rgba(0,0,0,.15);
        }
        .category-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .category-card-body { padding: 16px; }
        .category-card h3 { font-size: 18px; margin-bottom: 6px; }
        .category-card p  { font-size: 13px; color: #888; margin-bottom: 14px; }
        .btn-more {
            display: inline-block;
            padding: 9px 24px;
            background: #2ecc71;
            color: white;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            transition: background .2s;
        }
        .btn-more:hover { background: #27ae60; }

        /* ── Special Offers Preview ── */
        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .offer-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,.08);
            transition: transform .2s;
            position: relative;
        }
        .offer-card:hover { transform: translateY(-5px); }
        .offer-card img {
            width: 90px; height: 90px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #e8f8f0;
            margin-bottom: 10px;
        }
        .offer-card h4 { font-size: 15px; margin-bottom: 4px; }
        .offer-card .new-price { color: #2ecc71; font-size: 17px; font-weight: bold; }
        .offer-card .old-price { color: #aaa; font-size: 12px; text-decoration: line-through; }
        .offer-badge {
            position: absolute;
            top: 10px; right: 10px;
            background: #e74c3c;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 20px;
        }
        .btn-grab {
            display: inline-block;
            margin-top: 10px;
            padding: 7px 18px;
            background: #f39c12;
            color: white;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            transition: background .2s;
        }
        .btn-grab:hover { background: #e67e22; }

        /* ── Why Us ── */
        .why-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            max-width: 900px;
            margin: 0 auto;
        }
        .why-card {
            background: white;
            border-radius: 12px;
            padding: 24px 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,.07);
        }
        .why-card i { font-size: 36px; color: #2ecc71; margin-bottom: 12px; }
        .why-card h4 { font-size: 16px; margin-bottom: 6px; }
        .why-card p  { font-size: 13px; color: #888; }

        /* ── CTA ── */
        .cta-section {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            text-align: center;
            padding: 60px 20px;
        }
        .cta-section h2 { font-size: 30px; margin-bottom: 10px; }
        .cta-section p  { font-size: 15px; opacity: .9; margin-bottom: 24px; }
        .btn-cta {
            display: inline-block;
            padding: 14px 36px;
            background: white;
            color: #27ae60;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-cta:hover { background: #f0fdf4; }

        /* ── Footer ── */
        footer {
            background: #222;
            color: #ccc;
            padding: 40px 30px 20px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto 30px;
        }
        .footer-col h4 { color: white; font-size: 15px; margin-bottom: 12px; }
        .footer-col p, .footer-col a {
            font-size: 13px;
            color: #aaa;
            text-decoration: none;
            display: block;
            margin-bottom: 6px;
        }
        .footer-col a:hover { color: #2ecc71; }
        .footer-col i { color: #2ecc71; margin-right: 6px; }
        .footer-bottom {
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 16px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>

<!-- Header -->
<header>
    <h1><i class="fa-solid fa-leaf"></i> Fresh Grocery Store</h1>
    <p>Your daily needs delivered fresh!</p>
</header>

<!-- Navbar -->
<nav>
    <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
    <a href="product.php"><i class="fa-solid fa-store"></i> Products</a>
    <a href="offer.php"><i class="fa-solid fa-tag"></i> Offers</a>
    <a href="contect.php"><i class="fa-solid fa-envelope"></i> Contact</a>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="manage_product.php" style="background:rgba(230,126,34,.4);border-radius:6px;padding:6px 10px"><i class="fa-solid fa-crown"></i> Admin</a>
    <?php endif; ?>

    <div class="nav-icons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="account.php">
                <i class="fa-solid fa-circle-user"></i>
                <?php echo htmlspecialchars($_SESSION['name']); ?>
            </a>
            <a href="logout.php" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        <?php else: ?>
            <a href="login.php">
                <i class="fa-solid fa-right-to-bracket"></i> Login
            </a>
            <a href="register.php">
                <i class="fa-solid fa-user-plus"></i> Register
            </a>
        <?php endif; ?>

        <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
        <a href="cart.php">
            <i class="fa-solid fa-cart-shopping"></i> Cart
            <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?php echo $cart_count; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
    </div>
</nav>

<!-- Offer Banner -->
<?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
<div class="offer-banner">
    🔥 Special Offer: Up to 30% OFF on Fresh Fruits &amp; Vegetables!
    <a href="offer.php">Shop Now →</a>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<!-- Admin Quick Access Bar -->
<div style="background:#fff8e1;border-bottom:2px solid #ffe082;padding:12px 30px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
    <span style="font-size:14px;font-weight:bold;color:#e67e22"><i class="fa-solid fa-crown"></i> Admin Panel:</span>
    <a href="manage_product.php" style="padding:7px 16px;background:#e67e22;color:white;border-radius:6px;text-decoration:none;font-size:13px;font-weight:bold"><i class="fa-solid fa-boxes-stacked"></i> Products</a>
    <a href="manage_offer.php"   style="padding:7px 16px;background:#e74c3c;color:white;border-radius:6px;text-decoration:none;font-size:13px;font-weight:bold"><i class="fa-solid fa-tag"></i> Offers</a>
    <a href="manage_contact.php" style="padding:7px 16px;background:#3498db;color:white;border-radius:6px;text-decoration:none;font-size:13px;font-weight:bold"><i class="fa-solid fa-envelope"></i> Messages</a>
</div>
<?php endif; ?>

<!-- Hero -->
<section class="hero">
    <div class="hero-content">
        <h1>Fresh Fruits &amp; Vegetables</h1>
        <p>Farm to table — delivered to your door every day</p>
        <div class="hero-btns">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="manage_product.php" class="btn-hero primary">
                    <i class="fa-solid fa-crown"></i> Manage Products
                </a>
                <a href="manage_offer.php" class="btn-hero secondary">
                    <i class="fa-solid fa-tag"></i> Manage Offers
                </a>
            <?php else: ?>
                <a href="product.php" class="btn-hero primary">
                    <i class="fa-solid fa-store"></i> Shop Now
                </a>
                <a href="offer.php" class="btn-hero secondary">
                    <i class="fa-solid fa-tag"></i> View Offers
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Features Strip -->
<div class="features">
    <div class="feature-item">
        <i class="fa-solid fa-truck-fast"></i>
        <div><strong>Free Delivery</strong> On orders above ₹500</div>
    </div>
    <div class="feature-item">
        <i class="fa-solid fa-leaf"></i>
        <div><strong>100% Fresh</strong> Farm-sourced daily</div>
    </div>
    <div class="feature-item">
        <i class="fa-solid fa-shield-halved"></i>
        <div><strong>Secure Payment</strong> Safe &amp; encrypted</div>
    </div>
    <div class="feature-item">
        <i class="fa-solid fa-rotate-left"></i>
        <div><strong>Easy Returns</strong> Hassle-free policy</div>
    </div>
</div>

<!-- Categories -->
<?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
<section class="section" style="background:#f4f4f4;">
    <div class="section-title">Shop by Category</div>
    <div class="section-sub">Pick what you need — fresh and ready</div>
    <div class="category-grid">
        <a href="product.php" class="category-card">
            <img src="vegetable.jpg" alt="Vegetables">
            <div class="category-card-body">
                <h3>🥦 Vegetables</h3>
                <p>Farm fresh veggies delivered daily</p>
                <span class="btn-more">Shop Vegetables</span>
            </div>
        </a>
        <a href="product.php" class="category-card">
            <img src="fruits.jpg" alt="Fruits">
            <div class="category-card-body">
                <h3>🍎 Fruits</h3>
                <p>Sweet, juicy &amp; seasonal fruits</p>
                <span class="btn-more">Shop Fruits</span>
            </div>
        </a>
    </div>
</section>
<?php endif; ?>

<!-- Special Offers Preview -->
<?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
<section class="section" style="background:white;">
    <div class="section-title">🔥 Today's Special Offers</div>
    <div class="section-sub">Limited time deals — grab them before they're gone!</div>

    <div class="offers-grid">
        <?php
        $preview_offers = [
            ["Watermelon", "images/watermelon.jpg", 25, 40],
            ["Apple",      "images/apple.jpg",      99, 130],
            ["Onion",      "images/onion.jpg",       28, 40],
            ["Mango",      "images/mango.jpg",      120, 150],
        ];
        foreach ($preview_offers as $o):
            $disc = round((($o[3] - $o[2]) / $o[3]) * 100);
        ?>
        <div class="offer-card">
            <span class="offer-badge"><?php echo $disc; ?>% OFF</span>
            <img src="<?php echo $o[1]; ?>" alt="<?php echo $o[0]; ?>">
            <h4><?php echo $o[0]; ?></h4>
            <div class="new-price">₹<?php echo $o[2]; ?></div>
            <div class="old-price">₹<?php echo $o[3]; ?></div>
            <a href="cart.php?name=<?php echo urlencode($o[0]); ?>&price=<?php echo $o[2]; ?>&image=<?php echo urlencode($o[1]); ?>"
               class="btn-grab">
                <i class="fa-solid fa-bolt"></i> Grab Deal
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="text-align:center; margin-top:28px;">
        <a href="offer.php" class="btn-more" style="font-size:15px; padding:12px 32px;">
            View All Offers <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</section>
<?php endif; ?>
    </div>

    <div style="text-align:center; margin-top:28px;">
        <a href="offer.php" class="btn-more" style="font-size:15px; padding:12px 32px;">
            View All Offers <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</section>

<!-- Why Choose Us -->
<section class="section" style="background:#f4f4f4;">
    <div class="section-title">Why Choose Us?</div>
    <div class="section-sub">We make grocery shopping simple and fresh</div>

    <div class="why-grid">
        <div class="why-card">
            <i class="fa-solid fa-seedling"></i>
            <h4>Farm Fresh</h4>
            <p>Sourced directly from local farms every morning</p>
        </div>
        <div class="why-card">
            <i class="fa-solid fa-truck-fast"></i>
            <h4>Fast Delivery</h4>
            <p>Same-day delivery on orders placed before 12 PM</p>
        </div>
        <div class="why-card">
            <i class="fa-solid fa-indian-rupee-sign"></i>
            <h4>Best Prices</h4>
            <p>Competitive prices with daily deals and discounts</p>
        </div>
        <div class="why-card">
            <i class="fa-solid fa-headset"></i>
            <h4>24/7 Support</h4>
            <p>Our team is always here to help you</p>
        </div>
    </div>
</section>

<!-- CTA -->
<?php if (!isset($_SESSION['user_id'])): ?>
<section class="cta-section">
    <h2>Start Shopping Fresh Today!</h2>
    <p>Create a free account and get exclusive member deals</p>
    <a href="register.php" class="btn-cta">
        <i class="fa-solid fa-user-plus"></i> Create Free Account
    </a>
</section>
<?php endif; ?>

<!-- Footer -->
<footer>
    <div class="footer-grid">
        <div class="footer-col">
            <h4><i class="fa-solid fa-leaf"></i> Fresh Grocery</h4>
            <p>Your trusted source for fresh fruits, vegetables, and daily essentials.</p>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <a href="index.php"><i class="fa-solid fa-chevron-right"></i> Home</a>
            <a href="product.php"><i class="fa-solid fa-chevron-right"></i> Products</a>
            <a href="offer.php"><i class="fa-solid fa-chevron-right"></i> Offers</a>
            <a href="contect.php"><i class="fa-solid fa-chevron-right"></i> Contact</a>
        </div>
        <div class="footer-col">
            <h4>Account</h4>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="account.php"><i class="fa-solid fa-chevron-right"></i> My Account</a>
                <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                <a href="cart.php"><i class="fa-solid fa-chevron-right"></i> My Cart</a>
                <?php endif; ?>
                <a href="logout.php"><i class="fa-solid fa-chevron-right"></i> Logout</a>
            <?php else: ?>
                <a href="login.php"><i class="fa-solid fa-chevron-right"></i> Login</a>
                <a href="register.php"><i class="fa-solid fa-chevron-right"></i> Register</a>
            <?php endif; ?>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <p><i class="fa-solid fa-phone"></i> +91 98765 43210</p>
            <p><i class="fa-solid fa-envelope"></i> support@freshgrocery.com</p>
            <p><i class="fa-solid fa-location-dot"></i> Mumbai, India</p>
        </div>
    </div>
    <div class="footer-bottom">
        © 2026 Fresh Grocery Store | All Rights Reserved
    </div>
</footer>

</body>
</html>
