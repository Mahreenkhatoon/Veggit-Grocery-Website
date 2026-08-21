<?php
session_start();

// Handle remove item
if (isset($_GET['remove'])) {
    $remove = $_GET['remove'];
    if (isset($_SESSION['cart'][$remove])) {
        unset($_SESSION['cart'][$remove]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
    header("Location: cart.php");
    exit();
}

// Handle add to cart from product page
if (isset($_GET['name'])) {

    // Guard: must be logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?redirect=cart&msg=login_required");
        exit();
    }

    $name  = $_GET['name'];
    $price = $_GET['price'];
    $image = $_GET['image'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['name'] == $name) {
            $item['qty'] += 1;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['cart'][] = [
            "name"  => $name,
            "price" => $price,
            "image" => $image,
            "qty"   => 1
        ];
    }

    header("Location: cart.php");
    exit();
}

// Handle quantity update
if (isset($_POST['update_qty'])) {
    $index = (int)$_POST['index'];
    $qty   = (int)$_POST['qty'];
    if ($qty > 0 && isset($_SESSION['cart'][$index])) {
        $_SESSION['cart'][$index]['qty'] = $qty;
    }
    header("Location: cart.php");
    exit();
}

// Calculate totals
$total = 0;
$item_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total      += (int)$item['price'] * (int)$item['qty'];
        $item_count += (int)$item['qty'];
    }
}
$delivery = ($total > 0 && $total < 500) ? 40 : 0;
$grand_total = $total + $delivery;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart – Fresh Grocery Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── Reset ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Header ── */
        header {
            background: #2ecc71;
            color: white;
            padding: 15px 30px;
            text-align: center;
        }
        header h1 { font-size: 26px; }
        header p  { font-size: 13px; opacity: .85; margin-top: 3px; }

        /* ── Navbar ── */
        nav {
            background: #27ae60;
            padding: 10px 30px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            margin-right: 15px;
            font-size: 14px;
        }
        nav a:hover { text-decoration: underline; }
        .nav-icons {
            display: flex;
            gap: 12px;
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
            margin: 0;
        }
        .nav-icons a:hover { background: rgba(255,255,255,.35); text-decoration: none; }
        .cart-badge {
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            font-size: 11px;
            padding: 1px 6px;
            font-weight: bold;
        }

        /* ── Breadcrumb ── */
        .breadcrumb {
            padding: 12px 30px;
            font-size: 13px;
            color: #777;
        }
        .breadcrumb a { color: #27ae60; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }

        /* ── Page Title ── */
        .page-title {
            padding: 10px 30px 20px;
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-title i { color: #2ecc71; }

        /* ── Main Layout ── */
        .cart-wrapper {
            display: flex;
            gap: 24px;
            padding: 0 30px 40px;
            flex: 1;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        /* ── Cart Items ── */
        .cart-items { flex: 1; min-width: 300px; }

        .cart-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            overflow: hidden;
        }
        .cart-table thead {
            background: #2ecc71;
            color: white;
        }
        .cart-table th {
            padding: 14px 16px;
            text-align: left;
            font-size: 14px;
            font-weight: 600;
        }
        .cart-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
            font-size: 14px;
        }
        .cart-table tr:last-child td { border-bottom: none; }
        .cart-table tr:hover td { background: #f9fffe; }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .product-cell img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .product-name { font-weight: bold; font-size: 15px; }
        .product-sub  { font-size: 12px; color: #888; margin-top: 3px; }

        /* Qty control */
        .qty-form { display: flex; align-items: center; gap: 6px; }
        .qty-btn {
            width: 28px; height: 28px;
            border: 1px solid #ddd;
            background: #f9f9f9;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            display: flex; align-items: center; justify-content: center;
            transition: background .2s;
        }
        .qty-btn:hover { background: #2ecc71; color: white; border-color: #2ecc71; }
        .qty-input {
            width: 44px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 4px;
            font-size: 14px;
        }
        .qty-input:focus { outline: none; border-color: #2ecc71; }

        .item-total { font-weight: bold; color: #27ae60; font-size: 15px; }

        .remove-btn {
            background: none;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            font-size: 18px;
            padding: 4px 8px;
            border-radius: 5px;
            transition: background .2s;
        }
        .remove-btn:hover { background: #fdecea; }

        /* Empty cart */
        .empty-cart {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            text-align: center;
            padding: 60px 20px;
        }
        .empty-cart i { font-size: 64px; color: #ddd; margin-bottom: 16px; }
        .empty-cart h3 { font-size: 22px; color: #555; margin-bottom: 8px; }
        .empty-cart p  { color: #888; margin-bottom: 24px; }
        .btn-shop {
            display: inline-block;
            padding: 12px 28px;
            background: #2ecc71;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background .2s;
        }
        .btn-shop:hover { background: #27ae60; }

        /* Continue shopping */
        .cart-actions {
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .btn-continue {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            color: #27ae60;
            border: 2px solid #27ae60;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            transition: all .2s;
        }
        .btn-continue:hover { background: #27ae60; color: white; }
        .btn-clear {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            color: #e74c3c;
            border: 2px solid #e74c3c;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-clear:hover { background: #e74c3c; color: white; }

        /* ── Order Summary ── */
        .order-summary {
            width: 320px;
            min-width: 280px;
        }
        .summary-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            overflow: hidden;
        }
        .summary-header {
            background: #2ecc71;
            color: white;
            padding: 16px 20px;
            font-size: 17px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .summary-body { padding: 20px; }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        .summary-row:last-of-type { border-bottom: none; }
        .summary-row .label { color: #666; }
        .summary-row .value { font-weight: bold; }
        .summary-row.total-row {
            background: #f0fdf4;
            margin: 10px -20px -20px;
            padding: 16px 20px;
            font-size: 16px;
        }
        .summary-row.total-row .value { color: #27ae60; font-size: 20px; }

        .free-delivery-note {
            background: #e8f8f0;
            border-left: 4px solid #2ecc71;
            padding: 10px 14px;
            border-radius: 0 6px 6px 0;
            font-size: 12px;
            color: #27ae60;
            margin: 14px 0;
        }

        .btn-checkout {
            display: block;
            width: 100%;
            padding: 14px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            margin-top: 18px;
            transition: background .2s;
        }
        .btn-checkout:hover { background: #27ae60; }
        .btn-checkout i { margin-right: 8px; }

        .secure-note {
            text-align: center;
            font-size: 12px;
            color: #aaa;
            margin-top: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        /* Promo code */
        .promo-section { margin-top: 20px; }
        .promo-section label { font-size: 13px; font-weight: bold; color: #555; display: block; margin-bottom: 6px; }
        .promo-row { display: flex; gap: 8px; }
        .promo-input {
            flex: 1;
            padding: 9px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
        }
        .promo-input:focus { outline: none; border-color: #2ecc71; }
        .btn-promo {
            padding: 9px 16px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
        }
        .btn-promo:hover { background: #219a52; }

        /* ── Footer ── */
        footer {
            background: #2ecc71;
            color: white;
            text-align: center;
            padding: 14px;
            font-size: 13px;
            margin-top: auto;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .cart-wrapper { padding: 0 15px 30px; }
            .order-summary { width: 100%; }
            .cart-table th:nth-child(3),
            .cart-table td:nth-child(3) { display: none; }
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
        <a href="account.php">
            <i class="fa-solid fa-user"></i> Account
        </a>
        <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
        <a href="cart.php">
            <i class="fa-solid fa-cart-shopping"></i> Cart
            <?php if ($item_count > 0): ?>
                <span class="cart-badge"><?php echo $item_count; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
    </div>
</nav>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="index.php">Home</a> &rsaquo;
    <a href="product.php">Products</a> &rsaquo;
    <strong>Shopping Cart</strong>
</div>

<!-- Page Title -->
<div class="page-title">
    <i class="fa-solid fa-cart-shopping"></i>
    Shopping Cart
    <?php if ($item_count > 0): ?>
        <span style="font-size:15px; color:#888; font-weight:normal;">(<?php echo $item_count; ?> item<?php echo $item_count > 1 ? 's' : ''; ?>)</span>
    <?php endif; ?>
</div>

<!-- Main Content -->
<div class="cart-wrapper">

    <!-- Left: Cart Items -->
    <div class="cart-items">

        <?php if (!empty($_SESSION['cart'])): ?>

        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['cart'] as $index => $item):
                    $price      = (int)$item['price'];
                    $qty        = (int)$item['qty'];
                    $item_total = $price * $qty;
                ?>
                <tr>
                    <!-- Product -->
                    <td>
                        <div class="product-cell">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div>
                                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="product-sub">Fresh &amp; Natural</div>
                            </div>
                        </div>
                    </td>

                    <!-- Unit Price -->
                    <td>₹<?php echo $price; ?></td>

                    <!-- Quantity -->
                    <td>
                        <form method="POST" action="cart.php" class="qty-form">
                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                            <button type="button" class="qty-btn"
                                onclick="changeQty(this, -1)">−</button>
                            <input type="number" name="qty" class="qty-input"
                                   value="<?php echo $qty; ?>" min="1" max="99"
                                   onchange="this.form.submit()">
                            <button type="button" class="qty-btn"
                                onclick="changeQty(this, 1)">+</button>
                            <input type="hidden" name="update_qty" value="1">
                        </form>
                    </td>

                    <!-- Item Total -->
                    <td class="item-total">₹<?php echo $item_total; ?></td>

                    <!-- Remove -->
                    <td>
                        <a href="cart.php?remove=<?php echo $index; ?>"
                           onclick="return confirm('Remove this item?')"
                           title="Remove">
                            <button class="remove-btn"><i class="fa-solid fa-trash"></i></button>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Cart Actions -->
        <div class="cart-actions">
            <a href="product.php" class="btn-continue">
                <i class="fa-solid fa-arrow-left"></i> Continue Shopping
            </a>
            <a href="cart.php?clear=1" class="btn-clear"
               onclick="return confirm('Clear entire cart?')">
                <i class="fa-solid fa-trash-can"></i> Clear Cart
            </a>
        </div>

        <?php else: ?>

        <!-- Empty Cart -->
        <div class="empty-cart">
            <i class="fa-solid fa-cart-shopping"></i>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added anything yet. Start shopping!</p>
            <a href="product.php" class="btn-shop">
                <i class="fa-solid fa-store"></i> Browse Products
            </a>
        </div>

        <?php endif; ?>
    </div>

    <!-- Right: Order Summary -->
    <?php if (!empty($_SESSION['cart'])): ?>
    <div class="order-summary">
        <div class="summary-card">
            <div class="summary-header">
                <i class="fa-solid fa-receipt"></i> Order Summary
            </div>
            <div class="summary-body">

                <div class="summary-row">
                    <span class="label">Subtotal (<?php echo $item_count; ?> items)</span>
                    <span class="value">₹<?php echo $total; ?></span>
                </div>
                <div class="summary-row">
                    <span class="label">Delivery Charges</span>
                    <span class="value" style="color:<?php echo $delivery == 0 ? '#2ecc71' : '#333'; ?>">
                        <?php echo $delivery == 0 ? 'FREE' : '₹' . $delivery; ?>
                    </span>
                </div>
                <div class="summary-row">
                    <span class="label">Discount</span>
                    <span class="value" style="color:#e74c3c;">− ₹0</span>
                </div>

                <?php if ($delivery > 0): ?>
                <div class="free-delivery-note">
                    <i class="fa-solid fa-truck"></i>
                    Add ₹<?php echo 500 - $total; ?> more for <strong>FREE delivery</strong>!
                </div>
                <?php else: ?>
                <div class="free-delivery-note">
                    <i class="fa-solid fa-circle-check"></i>
                    You've unlocked <strong>FREE delivery</strong>!
                </div>
                <?php endif; ?>

                <div class="summary-row total-row">
                    <span class="label" style="font-weight:bold;">Grand Total</span>
                    <span class="value">₹<?php echo $grand_total; ?></span>
                </div>

                <!-- Promo Code -->
                <div class="promo-section">
                    <label><i class="fa-solid fa-ticket"></i> Promo Code</label>
                    <div class="promo-row">
                        <input type="text" class="promo-input" placeholder="Enter code">
                        <button class="btn-promo">Apply</button>
                    </div>
                </div>

                <a href="checkout.php" class="btn-checkout">
                    <i class="fa-solid fa-lock"></i> Proceed to Checkout
                </a>

                <div class="secure-note">
                    <i class="fa-solid fa-shield-halved"></i>
                    Secure &amp; Encrypted Checkout
                </div>

            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Footer -->
<footer>
    <p>© 2026 Fresh Grocery Store | All Rights Reserved</p>
</footer>

<script>
// Inline qty +/- buttons
function changeQty(btn, delta) {
    const form  = btn.closest('form');
    const input = form.querySelector('.qty-input');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > 99) val = 99;
    input.value = val;
    form.submit();
}

// Clear cart handler
(function () {
    const url = new URL(window.location.href);
    if (url.searchParams.get('clear') === '1') {
        // handled server-side below, just clean URL
    }
})();
</script>

<?php
// Handle clear cart
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header("Location: cart.php");
    exit();
}
?>
</body>
</html>
