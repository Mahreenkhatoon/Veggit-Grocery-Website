<?php
session_start();
include "db.php";

// ── Create products table with UNIQUE name (safe to re-run) ──
$conn->query("CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL UNIQUE,
    category    ENUM('fruit','vegetable') NOT NULL,
    price       DECIMAL(10,2) NOT NULL,
    unit        VARCHAR(30)   NOT NULL DEFAULT 'kg',
    description VARCHAR(255)  NOT NULL DEFAULT '',
    image       VARCHAR(150)  NOT NULL DEFAULT '',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");


// ── Seed all default products (INSERT IGNORE skips duplicates) ──
$_defaults = [
    ["Tomatoes",    "tomoto.jpg",        40,  "kg",     "Fresh ripe tomatoes",        "vegetable"],
    ["Potatoes",    "pototo.jpg",        30,  "kg",     "Farm fresh potatoes",        "vegetable"],
    ["Carrots",     "carrot.jpg",        50,  "kg",     "Crunchy orange carrots",     "vegetable"],
    ["Onion",       "onion.jpg",         35,  "kg",     "Pungent farm onions",        "vegetable"],
    ["Garlic",      "garlic.jpg",       120,  "250g",   "Aromatic fresh garlic",      "vegetable"],
    ["Beetroot",    "beetroot.jpg",      45,  "kg",     "Earthy nutritious beetroot", "vegetable"],
    ["Paneer",      "panner.jpg",       280,  "500g",   "Soft fresh cottage cheese",  "vegetable"],
    ["Cabbage",     "cabbage.jpg",       30,  "piece",  "Fresh green cabbage",        "vegetable"],
    ["Apple",       "apple.jpg",        120,  "kg",     "Crisp & juicy apples",       "fruit"],
    ["Banana",      "banana.jpg",        60,  "dozen",  "Ripe energy-packed bananas", "fruit"],
    ["Mango",       "mango.jpg",        150,  "kg",     "Sweet Alphonso mangoes",     "fruit"],
    ["Orange",      "orange.jpg",        80,  "kg",     "Tangy fresh oranges",        "fruit"],
    ["Grapes",      "grapes.jpg",        90,  "kg",     "Seedless green grapes",      "fruit"],
    ["Pineapple",   "pineapple.jpg",     70,  "piece",  "Sweet & tangy pineapple",    "fruit"],
    ["Watermelon",  "watermelon.jpg",    40,  "kg",     "Cool refreshing watermelon", "fruit"],
    ["Papaya",      "papaya.jpg",        50,  "piece",  "Tropical ripe papaya",       "fruit"],
    ["Pomegranate", "pomegranate.jpg",  140,  "piece",  "Ruby red pomegranate",       "fruit"],
    ["Kiwi",        "kiwi.jpg",         200,  "6 pcs",  "Tangy vitamin-rich kiwi",    "fruit"],
];
$_s = $conn->prepare("INSERT IGNORE INTO products (name, image, price, unit, description, category) VALUES (?,?,?,?,?,?)");
foreach ($_defaults as $_d) {
    $_price = (float)$_d[2];
    $_s->bind_param("ssdsss", $_d[0], $_d[1], $_price, $_d[3], $_d[4], $_d[5]);
    $_s->execute();
}
$_s->close();

// ── Add to cart handler ──
if (isset($_GET['name'])) {
    $name  = $_GET['name'];
    $price = $_GET['price'];
    $image = $_GET['image'];

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['name'] == $name) { $item['qty'] += 1; $found = true; break; }
    }
    if (!$found) {
        $_SESSION['cart'][] = ["name" => $name, "price" => $price, "image" => $image, "qty" => 1];
    }

    header("Location: cart.php");
    exit();
}

// Cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) $cart_count += (int)$item['qty'];
}

// ── Load products from DB ──
$result     = $conn->query("SELECT * FROM products ORDER BY category, name");
$all_products = $result->fetch_all(MYSQLI_ASSOC);

$vegetables = array_filter($all_products, fn($p) => $p['category'] === 'vegetable');
$fruits     = array_filter($all_products, fn($p) => $p['category'] === 'fruit');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products – Fresh Grocery Store</title>
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

        /* ── Page Hero ── */
        .page-hero {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .page-hero h1 { font-size: 32px; }
        .page-hero p  { font-size: 14px; opacity: .9; margin-top: 8px; }

        /* ── Breadcrumb ── */
        .breadcrumb { padding: 12px 30px; font-size: 13px; color: #777; }
        .breadcrumb a { color: #27ae60; text-decoration: none; }

        /* ── Toolbar ── */
        .toolbar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding: 10px 30px 16px;
            background: white;
            border-bottom: 1px solid #eee;
        }

        /* Search */
        .search-wrap { position: relative; flex: 1; min-width: 200px; max-width: 340px; }
        .search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 14px; }
        .search-wrap input {
            width: 100%; padding: 9px 12px 9px 36px;
            border: 1px solid #ddd; border-radius: 8px; font-size: 14px;
        }
        .search-wrap input:focus { outline: none; border-color: #2ecc71; box-shadow: 0 0 0 3px rgba(46,204,113,.12); }

        /* Filter tabs */
        .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
        .tab-btn {
            padding: 8px 18px; border: 2px solid #ddd; background: white;
            border-radius: 20px; font-size: 13px; font-weight: bold; cursor: pointer; transition: all .2s;
        }
        .tab-btn:hover, .tab-btn.active { background: #2ecc71; border-color: #2ecc71; color: white; }

        /* Sort */
        .sort-wrap { margin-left: auto; }
        .sort-wrap select {
            padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 13px; cursor: pointer; background: white;
        }
        .sort-wrap select:focus { outline: none; border-color: #2ecc71; }

        /* Result count */
        .result-count { font-size: 13px; color: #888; padding: 8px 30px 0; }

        /* ── Main ── */
        .main { flex: 1; padding: 16px 30px 50px; }

        /* Section heading */
        .section-heading {
            display: flex; align-items: center; gap: 12px;
            margin: 28px 0 18px;
        }
        .section-heading h2 { font-size: 22px; color: #222; }
        .section-heading .line { flex: 1; height: 2px; background: linear-gradient(to right, #2ecc71, transparent); }
        .section-heading .count {
            background: #e8f8f0; color: #27ae60;
            font-size: 12px; font-weight: bold;
            padding: 3px 10px; border-radius: 20px;
        }

        /* ── Product Grid ── */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 20px;
        }

        /* ── Product Card ── */
        .product-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 3px 12px rgba(0,0,0,.08);
            overflow: hidden;
            transition: transform .25s, box-shadow .25s;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 14px 28px rgba(0,0,0,.14);
        }

        /* Wishlist btn */
        .wish-btn {
            position: absolute; top: 10px; right: 10px;
            width: 32px; height: 32px;
            background: white; border: none; border-radius: 50%;
            box-shadow: 0 2px 6px rgba(0,0,0,.15);
            cursor: pointer; font-size: 15px; color: #ccc;
            display: flex; align-items: center; justify-content: center;
            transition: all .2s; z-index: 2;
        }
        .wish-btn:hover, .wish-btn.active { color: #e74c3c; }

        /* Image */
        .card-img {
            height: 160px; overflow: hidden; background: #f9f9f9;
        }
        .card-img img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform .3s;
        }
        .product-card:hover .card-img img { transform: scale(1.07); }

        /* Body */
        .card-body { padding: 14px; flex: 1; display: flex; flex-direction: column; }
        .card-cat {
            display: inline-block; background: #e8f8f0; color: #27ae60;
            font-size: 10px; font-weight: bold; padding: 2px 8px;
            border-radius: 10px; margin-bottom: 5px;
        }
        .card-body h3 { font-size: 15px; margin-bottom: 3px; }
        .card-body p  { font-size: 12px; color: #999; margin-bottom: 10px; flex: 1; }

        .price-row { display: flex; align-items: baseline; gap: 6px; margin-bottom: 12px; }
        .price     { font-size: 18px; font-weight: bold; color: #2ecc71; }
        .unit      { font-size: 11px; color: #aaa; }

        /* Stars */
        .stars { color: #f39c12; font-size: 11px; margin-bottom: 10px; }
        .stars span { color: #aaa; font-size: 11px; margin-left: 4px; }

        /* Buttons */
        .card-actions { display: flex; gap: 8px; }
        .btn-cart {
            flex: 1; padding: 9px 6px;
            background: #2ecc71; color: white; border: none;
            border-radius: 8px; font-size: 12px; font-weight: bold;
            cursor: pointer; transition: background .2s;
            display: flex; align-items: center; justify-content: center; gap: 5px;
        }
        .btn-cart:hover { background: #27ae60; }
        .btn-buy {
            flex: 1; padding: 9px 6px;
            background: #f39c12; color: white; border: none;
            border-radius: 8px; font-size: 12px; font-weight: bold;
            cursor: pointer; transition: background .2s;
            display: flex; align-items: center; justify-content: center; gap: 5px;
        }
        .btn-buy:hover { background: #e67e22; }

        /* ── Empty state ── */
        .empty-state {
            text-align: center; padding: 60px 20px; display: none;
        }
        .empty-state i { font-size: 56px; color: #ddd; margin-bottom: 14px; }
        .empty-state p { color: #aaa; font-size: 15px; }

        /* ── Admin bar ── */
        .admin-bar {
            background: #fff8e1; border: 1px solid #ffe082;
            border-radius: 10px; padding: 14px 20px;
            display: flex; align-items: center; gap: 14px;
            margin-bottom: 20px;
        }
        .admin-bar i { color: #f39c12; font-size: 20px; }
        .admin-bar span { font-size: 14px; color: #555; flex: 1; }
        .btn-admin {
            padding: 9px 20px; background: #e67e22; color: white;
            border: none; border-radius: 8px; font-size: 13px;
            font-weight: bold; cursor: pointer; text-decoration: none;
            transition: background .2s;
        }
        .btn-admin:hover { background: #d35400; }

        /* ── Toast ── */
        #toast {
            position: fixed; bottom: 28px; right: 28px;
            background: #2ecc71; color: white;
            padding: 13px 20px; border-radius: 10px;
            font-size: 14px; font-weight: bold;
            box-shadow: 0 4px 16px rgba(0,0,0,.2);
            display: none; z-index: 999;
            align-items: center; gap: 8px;
        }

        /* ── Footer ── */
        footer { background: #2ecc71; color: white; text-align: center; padding: 14px; font-size: 13px; }

        /* ── Responsive ── */
        @media (max-width: 600px) {
            .product-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
            .main { padding: 12px 14px 40px; }
            .toolbar { padding: 10px 14px; }
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

<!-- Page Hero -->
<div class="page-hero">
    <h1><i class="fa-solid fa-store"></i> Our Products</h1>
    <p>Fresh fruits &amp; vegetables sourced directly from farms</p>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="index.php">Home</a> &rsaquo; <strong>Products</strong>
</div>

<!-- Toolbar -->
<div class="toolbar">
    <div class="search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search products…" oninput="applyFilters()">
    </div>

    <div class="filter-tabs">
        <button class="tab-btn active" onclick="setTab('all', this)">All</button>
        <button class="tab-btn" onclick="setTab('vegetable', this)">🥦 Vegetables</button>
        <button class="tab-btn" onclick="setTab('fruit', this)">🍎 Fruits</button>
    </div>

    <div class="sort-wrap">
        <select id="sortSelect" onchange="applyFilters()">
            <option value="default">Sort: Default</option>
            <option value="low">Price: Low to High</option>
            <option value="high">Price: High to Low</option>
            <option value="name">Name: A–Z</option>
        </select>
    </div>
</div>

<div class="result-count" id="resultCount"></div>

<!-- Main -->
<div class="main">

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="admin-bar">
        <i class="fa-solid fa-crown"></i>
        <span>You are logged in as <strong>Admin</strong>. Manage your product catalogue below.</span>
        <a href="manage_product.php" class="btn-admin">
            <i class="fa-solid fa-plus"></i> Add Product
        </a>
    </div>
    <?php endif; ?>

    <!-- Vegetables -->
    <div class="section-heading" id="veg-heading" <?php echo empty($vegetables) ? 'style="display:none"' : ''; ?>>
        <h2>🥦 Vegetables</h2>
        <div class="line"></div>
        <span class="count"><?php echo count($vegetables); ?> items</span>
    </div>

    <div class="product-grid" id="vegGrid">
        <?php foreach ($vegetables as $p): ?>
        <div class="product-card"
             data-name="<?php echo strtolower(htmlspecialchars($p['name'])); ?>"
             data-cat="vegetable"
             data-price="<?php echo $p['price']; ?>">

            <button class="wish-btn" onclick="toggleWish(this)" title="Wishlist">
                <i class="fa-regular fa-heart"></i>
            </button>

            <div class="card-img">
                <?php if ($p['image']): ?>
                    <img src="images/<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                <?php else: ?>
                    <img src="images/vegetable.jpg" alt="<?php echo htmlspecialchars($p['name']); ?>">
                <?php endif; ?>
            </div>

            <div class="card-body">
                <span class="card-cat">Vegetable</span>
                <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                <p><?php echo htmlspecialchars($p['description']); ?></p>
                <div class="stars">
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-regular fa-star"></i>
                    <span>(4.0)</span>
                </div>
                <div class="price-row">
                    <span class="price">₹<?php echo $p['price']; ?></span>
                    <span class="unit">/ <?php echo htmlspecialchars($p['unit']); ?></span>
                </div>
                <div class="card-actions">
                    <button class="btn-cart"
                        onclick="addToCart('<?php echo addslashes($p['name']); ?>', <?php echo $p['price']; ?>, 'images/<?php echo addslashes($p['image'] ?: 'vegetable.jpg'); ?>')">
                        <i class="fa-solid fa-cart-plus"></i> Add
                    </button>
                    <button class="btn-buy"
                        onclick="buyNow('<?php echo addslashes($p['name']); ?>', <?php echo $p['price']; ?>, 'images/<?php echo addslashes($p['image'] ?: 'vegetable.jpg'); ?>')">
                        <i class="fa-solid fa-bolt"></i> Buy
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Fruits -->
    <div class="section-heading" id="fruit-heading" <?php echo empty($fruits) ? 'style="display:none"' : ''; ?>>
        <h2>🍎 Fruits</h2>
        <div class="line"></div>
        <span class="count"><?php echo count($fruits); ?> items</span>
    </div>

    <div class="product-grid" id="fruitGrid">
        <?php foreach ($fruits as $f): ?>
        <div class="product-card"
             data-name="<?php echo strtolower(htmlspecialchars($f['name'])); ?>"
             data-cat="fruit"
             data-price="<?php echo $f['price']; ?>">

            <button class="wish-btn" onclick="toggleWish(this)" title="Wishlist">
                <i class="fa-regular fa-heart"></i>
            </button>

            <div class="card-img">
                <?php if ($f['image']): ?>
                    <img src="images/<?php echo htmlspecialchars($f['image']); ?>" alt="<?php echo htmlspecialchars($f['name']); ?>">
                <?php else: ?>
                    <img src="fruits.jpg" alt="<?php echo htmlspecialchars($f['name']); ?>">
                <?php endif; ?>
            </div>

            <div class="card-body">
                <span class="card-cat">Fruit</span>
                <h3><?php echo htmlspecialchars($f['name']); ?></h3>
                <p><?php echo htmlspecialchars($f['description']); ?></p>
                <div class="stars">
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-regular fa-star"></i>
                    <span>(4.0)</span>
                </div>
                <div class="price-row">
                    <span class="price">₹<?php echo $f['price']; ?></span>
                    <span class="unit">/ <?php echo htmlspecialchars($f['unit']); ?></span>
                </div>
                <div class="card-actions">
                    <button class="btn-cart"
                        onclick="addToCart('<?php echo addslashes($f['name']); ?>', <?php echo $f['price']; ?>, 'images/<?php echo addslashes($f['image'] ?: 'fruits.jpg'); ?>')">
                        <i class="fa-solid fa-cart-plus"></i> Add
                    </button>
                    <button class="btn-buy"
                        onclick="buyNow('<?php echo addslashes($f['name']); ?>', <?php echo $f['price']; ?>, 'images/<?php echo addslashes($f['image'] ?: 'fruits.jpg'); ?>')">
                        <i class="fa-solid fa-bolt"></i> Buy
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty state -->
    <div class="empty-state" id="emptyState">
        <i class="fa-solid fa-magnifying-glass"></i>
        <p>No products found for "<span id="emptyTerm"></span>"</p>
    </div>

</div>

<!-- Footer -->
<footer>
    <p>© 2026 Fresh Grocery Store | All Rights Reserved</p>
</footer>

<!-- Toast -->
<div id="toast">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMsg">Added to cart!</span>
</div>

<script>
let activeTab = 'all';

// ── Add to cart ──
function addToCart(name, price, image) {
    showToast(name + ' added to cart!', '#2ecc71');
    setTimeout(() => {
        window.location.href =
            'cart.php?name=' + encodeURIComponent(name) +
            '&price=' + price +
            '&image=' + encodeURIComponent(image);
    }, 800);
}

// ── Buy now ──
function buyNow(name, price, image) {
    window.location.href =
        'cart.php?name=' + encodeURIComponent(name) +
        '&price=' + price +
        '&image=' + encodeURIComponent(image);
}

// ── Wishlist toggle ──
function toggleWish(btn) {
    btn.classList.toggle('active');
    const icon = btn.querySelector('i');
    if (btn.classList.contains('active')) {
        icon.className = 'fa-solid fa-heart';
        showToast('Added to wishlist!', '#e74c3c');
    } else {
        icon.className = 'fa-regular fa-heart';
    }
}

// ── Tab filter ──
function setTab(tab, btn) {
    activeTab = tab;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}

// ── Apply search + tab + sort ──
function applyFilters() {
    const query = document.getElementById('searchInput').value.toLowerCase().trim();
    const sort  = document.getElementById('sortSelect').value;

    const allCards = Array.from(document.querySelectorAll('.product-card'));

    // Filter
    let visible = allCards.filter(card => {
        const name = card.dataset.name;
        const cat  = card.dataset.cat;
        const matchSearch = !query || name.includes(query);
        const matchTab    = activeTab === 'all' || cat === activeTab;
        return matchSearch && matchTab;
    });

    // Sort
    visible.sort((a, b) => {
        if (sort === 'low')  return +a.dataset.price - +b.dataset.price;
        if (sort === 'high') return +b.dataset.price - +a.dataset.price;
        if (sort === 'name') return a.dataset.name.localeCompare(b.dataset.name);
        return 0;
    });

    // Show/hide cards
    allCards.forEach(c => c.style.display = 'none');
    visible.forEach(c => c.style.display = '');

    // Re-order in DOM
    const vegGrid   = document.getElementById('vegGrid');
    const fruitGrid = document.getElementById('fruitGrid');
    visible.forEach(c => {
        if (c.dataset.cat === 'vegetable') vegGrid.appendChild(c);
        else fruitGrid.appendChild(c);
    });

    // Section headings visibility
    const vegVisible   = visible.filter(c => c.dataset.cat === 'vegetable').length;
    const fruitVisible = visible.filter(c => c.dataset.cat === 'fruit').length;
    document.getElementById('veg-heading').style.display   = vegVisible   ? '' : 'none';
    document.getElementById('fruit-heading').style.display = fruitVisible ? '' : 'none';

    // Empty state
    const empty = document.getElementById('emptyState');
    if (visible.length === 0) {
        empty.style.display = 'block';
        document.getElementById('emptyTerm').textContent = query || activeTab;
    } else {
        empty.style.display = 'none';
    }

    // Result count
    document.getElementById('resultCount').textContent =
        visible.length + ' product' + (visible.length !== 1 ? 's' : '') + ' found';
}

// ── Toast ──
function showToast(msg, color) {
    const toast = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    toast.style.background = color || '#2ecc71';
    toast.style.display = 'flex';
    clearTimeout(toast._t);
    toast._t = setTimeout(() => toast.style.display = 'none', 2200);
}

// Init count
applyFilters();
</script>

</body>
</html>
