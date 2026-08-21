<?php
session_start();
include "db.php";

$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) $cart_count += (int)$item['qty'];
}

// Load offers from DB (auto-create table if missing)
$conn->query("CREATE TABLE IF NOT EXISTS offers (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(100)  NOT NULL,
    category  ENUM('Fruit','Vegetable') NOT NULL,
    image     VARCHAR(150)  NOT NULL DEFAULT '',
    description VARCHAR(255) NOT NULL DEFAULT '',
    price     DECIMAL(10,2) NOT NULL,
    old_price DECIMAL(10,2) NOT NULL,
    unit      VARCHAR(30)   NOT NULL DEFAULT 'kg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$result = $conn->query("SELECT * FROM offers ORDER BY category, name");
$offers = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offers – Fresh Grocery Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body { font-family: Arial, sans-serif; background: #f4f4f4; color: #333; min-height: 100vh; display: flex; flex-direction: column; }

        /* ── Header ── */
        header { background: #2ecc71; color: white; padding: 16px 30px; text-align: center; }
        header h1 { font-size: 24px; }
        header p  { font-size: 13px; opacity: .85; margin-top: 3px; }

        /* ── Navbar ── */
        nav { background: #27ae60; padding: 10px 30px; display: flex; align-items: center; flex-wrap: wrap; gap: 6px; }
        nav > a { color: white; text-decoration: none; font-weight: bold; font-size: 14px; margin-right: 10px; }
        nav > a:hover { text-decoration: underline; }
        .nav-icons { display: flex; gap: 10px; margin-left: auto; align-items: center; }
        .nav-icons a {
            color: white; display: flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,.2); padding: 6px 12px; border-radius: 6px;
            font-size: 13px; font-weight: bold; text-decoration: none; transition: background .2s;
        }
        .nav-icons a:hover { background: rgba(255,255,255,.35); }
        .nav-icons a.logout-btn { background: rgba(231,76,60,.5); }
        .nav-icons a.logout-btn:hover { background: rgba(231,76,60,.8); }
        .cart-badge { background: #e74c3c; color: white; border-radius: 50%; font-size: 11px; padding: 1px 6px; font-weight: bold; }

        /* ── Hero Banner ── */
        .offer-hero {
            background: linear-gradient(135deg, #f39c12 0%, #e74c3c 100%);
            color: white;
            text-align: center;
            padding: 60px 20px;
            position: relative;
            overflow: hidden;
        }
        .offer-hero::before {
            content: '🔥';
            position: absolute;
            font-size: 200px;
            opacity: .07;
            top: -30px; left: -20px;
        }
        .offer-hero::after {
            content: '🛒';
            position: absolute;
            font-size: 180px;
            opacity: .07;
            bottom: -30px; right: -10px;
        }
        .offer-hero h1 { font-size: 38px; text-shadow: 0 2px 8px rgba(0,0,0,.2); }
        .offer-hero p  { font-size: 16px; opacity: .9; margin-top: 10px; }
        .countdown-strip {
            display: inline-flex;
            gap: 16px;
            margin-top: 20px;
            background: rgba(0,0,0,.2);
            padding: 12px 24px;
            border-radius: 10px;
        }
        .countdown-item { text-align: center; }
        .countdown-item .num { font-size: 28px; font-weight: bold; display: block; }
        .countdown-item .lbl { font-size: 11px; opacity: .8; }

        /* ── Breadcrumb ── */
        .breadcrumb { padding: 12px 30px; font-size: 13px; color: #777; }
        .breadcrumb a { color: #27ae60; text-decoration: none; }

        /* ── Stats bar ── */
        .stats-bar {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0;
            background: white;
            border-bottom: 1px solid #eee;
        }
        .stat-item {
            display: flex; align-items: center; gap: 10px;
            padding: 16px 28px;
            border-right: 1px solid #eee;
            font-size: 13px; color: #555;
        }
        .stat-item:last-child { border-right: none; }
        .stat-item i { color: #e74c3c; font-size: 20px; }
        .stat-item strong { display: block; color: #333; font-size: 15px; }

        /* ── Filter tabs ── */
        .filter-bar {
            padding: 20px 30px 10px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-bar span { font-size: 14px; font-weight: bold; color: #555; margin-right: 4px; }
        .filter-btn {
            padding: 8px 20px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all .2s;
            font-weight: bold;
        }
        .filter-btn:hover, .filter-btn.active {
            background: #2ecc71;
            border-color: #2ecc71;
            color: white;
        }

        /* ── Offers Grid ── */
        .offers-section { padding: 10px 30px 50px; flex: 1; }
        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 22px;
        }

        /* ── Offer Card ── */
        .offer-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 3px 14px rgba(0,0,0,.09);
            overflow: hidden;
            transition: transform .25s, box-shadow .25s;
            position: relative;
        }
        .offer-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 14px 30px rgba(0,0,0,.15);
        }

        .discount-badge {
            position: absolute;
            top: 12px; left: 12px;
            background: #e74c3c;
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 20px;
            z-index: 2;
        }
        .hot-badge {
            position: absolute;
            top: 12px; right: 12px;
            background: #f39c12;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 20px;
            z-index: 2;
        }

        .card-img-wrap {
            height: 180px;
            overflow: hidden;
            background: #f9f9f9;
        }
        .card-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .3s;
        }
        .offer-card:hover .card-img-wrap img { transform: scale(1.06); }

        .card-body { padding: 16px; }
        .cat-tag {
            display: inline-block;
            background: #e8f8f0;
            color: #27ae60;
            font-size: 11px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 10px;
            margin-bottom: 6px;
        }
        .card-body h3 { font-size: 17px; margin-bottom: 4px; }
        .card-body p  { font-size: 12px; color: #888; margin-bottom: 10px; }

        .price-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }
        .new-price { font-size: 20px; font-weight: bold; color: #2ecc71; }
        .old-price { font-size: 13px; color: #bbb; text-decoration: line-through; }
        .unit-tag  { font-size: 11px; color: #aaa; }

        .savings-note {
            font-size: 12px;
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .card-actions { display: flex; gap: 8px; }
        .btn-add {
            flex: 1;
            padding: 10px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: background .2s;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-add:hover { background: #27ae60; }
        .btn-buy {
            flex: 1;
            padding: 10px;
            background: #f39c12;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: background .2s;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-buy:hover { background: #e67e22; }

        /* ── Bottom CTA ── */
        .bottom-cta {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            text-align: center;
            padding: 50px 20px;
        }
        .bottom-cta h2 { font-size: 26px; margin-bottom: 8px; }
        .bottom-cta p  { font-size: 14px; opacity: .9; margin-bottom: 20px; }
        .btn-shop-all {
            display: inline-block;
            padding: 13px 32px;
            background: white;
            color: #27ae60;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-shop-all:hover { background: #f0fdf4; }

        /* ── Footer ── */
        footer { background: #2ecc71; color: white; text-align: center; padding: 14px; font-size: 13px; }

        /* ── Toast ── */
        #toast {
            position: fixed;
            bottom: 30px; right: 30px;
            background: #2ecc71;
            color: white;
            padding: 14px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 4px 16px rgba(0,0,0,.2);
            display: none;
            z-index: 999;
            align-items: center;
            gap: 8px;
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
            <a href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
            <a href="register.php"><i class="fa-solid fa-user-plus"></i> Register</a>
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

<!-- Hero Banner -->
<div class="offer-hero">
    <h1>🔥 Today's Special Offers</h1>
    <p>Limited time deals on fresh fruits &amp; vegetables — save big every day!</p>
    <div class="countdown-strip">
        <div class="countdown-item">
            <span class="num" id="hours">00</span>
            <span class="lbl">Hours</span>
        </div>
        <div class="countdown-item">
            <span class="num" id="mins">00</span>
            <span class="lbl">Mins</span>
        </div>
        <div class="countdown-item">
            <span class="num" id="secs">00</span>
            <span class="lbl">Secs</span>
        </div>
    </div>
</div>

<!-- Stats Bar -->
<div class="stats-bar">
    <div class="stat-item">
        <i class="fa-solid fa-fire"></i>
        <div><strong><?php echo count($offers); ?>+</strong> Active Deals</div>
    </div>
    <div class="stat-item">
        <i class="fa-solid fa-percent"></i>
        <div><strong>Up to 38%</strong> Discount</div>
    </div>
    <div class="stat-item">
        <i class="fa-solid fa-truck-fast"></i>
        <div><strong>Free Delivery</strong> On ₹500+</div>
    </div>
    <div class="stat-item">
        <i class="fa-solid fa-clock"></i>
        <div><strong>Today Only</strong> Limited Stock</div>
    </div>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="index.php">Home</a> &rsaquo; <strong>Special Offers</strong>
</div>

<!-- Filter Tabs -->
<div class="filter-bar">
    <span><i class="fa-solid fa-filter"></i> Filter:</span>
    <button class="filter-btn active" onclick="filterOffers('All', this)">All</button>
    <button class="filter-btn" onclick="filterOffers('Vegetable', this)">🥦 Vegetables</button>
    <button class="filter-btn" onclick="filterOffers('Fruit', this)">🍎 Fruits</button>
</div>

<!-- Offers Grid -->
<div class="offers-section">
    <div class="offers-grid" id="offersGrid">

        <?php foreach ($offers as $item):
            $disc    = round((($item['old_price'] - $item['price']) / $item['old_price']) * 100);
            $savings = $item['old_price'] - $item['price'];
            $hot     = $disc >= 25;
        ?>
        <div class="offer-card" data-cat="<?php echo $item['category']; ?>">

            <span class="discount-badge">-<?php echo $disc; ?>%</span>
            <?php if ($hot): ?>
                <span class="hot-badge">🔥 HOT</span>
            <?php endif; ?>

            <div class="card-img-wrap">
                <img src="<?php echo htmlspecialchars($item['image']); ?>"
                     alt="<?php echo htmlspecialchars($item['name']); ?>">
            </div>

            <div class="card-body">
                <span class="cat-tag"><?php echo $item['category']; ?></span>
                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                <p><?php echo htmlspecialchars($item['description']); ?></p>

                <div class="price-row">
                    <span class="new-price">₹<?php echo $item['price']; ?></span>
                    <span class="old-price">₹<?php echo $item['old_price']; ?></span>
                    <span class="unit-tag">/ <?php echo htmlspecialchars($item['unit']); ?></span>
                </div>

                <div class="savings-note">
                    <i class="fa-solid fa-piggy-bank"></i>
                    You save ₹<?php echo $savings; ?>!
                </div>

                <div class="card-actions">
                    <a href="cart.php?name=<?php echo urlencode($item['name']); ?>&price=<?php echo $item['price']; ?>&image=<?php echo urlencode($item['image']); ?>"
                       class="btn-add"
                       onclick="showToast('<?php echo htmlspecialchars($item['name']); ?>')">
                        <i class="fa-solid fa-cart-plus"></i> Add
                    </a>
                    <a href="cart.php?name=<?php echo urlencode($item['name']); ?>&price=<?php echo $item['price']; ?>&image=<?php echo urlencode($item['image']); ?>"
                       class="btn-buy">
                        <i class="fa-solid fa-bolt"></i> Buy Now
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

<!-- Bottom CTA -->
<div class="bottom-cta">
    <h2>Want More Products?</h2>
    <p>Browse our full collection of fresh fruits and vegetables</p>
    <a href="product.php" class="btn-shop-all">
        <i class="fa-solid fa-store"></i> Shop All Products
    </a>
</div>

<!-- Footer -->
<footer>
    <p>© 2026 Fresh Grocery Store | Save More Everyday 🛒</p>
</footer>

<!-- Toast -->
<div id="toast">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toast-msg">Added to cart!</span>
</div>

<script>
// ── Countdown timer (ends midnight) ──
function updateCountdown() {
    const now  = new Date();
    const end  = new Date();
    end.setHours(23, 59, 59, 0);
    const diff = Math.max(0, end - now);

    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);

    document.getElementById('hours').textContent = String(h).padStart(2, '0');
    document.getElementById('mins').textContent  = String(m).padStart(2, '0');
    document.getElementById('secs').textContent  = String(s).padStart(2, '0');
}
updateCountdown();
setInterval(updateCountdown, 1000);

// ── Filter ──
function filterOffers(cat, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.offer-card').forEach(card => {
        if (cat === 'All' || card.dataset.cat === cat) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// ── Toast ──
function showToast(name) {
    const toast = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = name + ' added to cart!';
    toast.style.display = 'flex';
    setTimeout(() => { toast.style.display = 'none'; }, 2500);
}
</script>

</body>
</html>
