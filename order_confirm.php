<?php
session_start();

// Redirect if no order in session
if (empty($_SESSION['last_order'])) {
    header("Location: index.php");
    exit();
}

$order = $_SESSION['last_order'];

// Payment method labels
$pay_labels = [
    'cod'  => 'Cash on Delivery',
    'upi'  => 'UPI / QR Code',
    'card' => 'Credit / Debit Card',
    'net'  => 'Net Banking',
];
$pay_label = $pay_labels[$order['payment']] ?? $order['payment'];

// Estimated delivery (2 days from now)
$est_delivery = date('d M Y', strtotime('+2 days'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed – Fresh Grocery Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body { font-family: Arial, sans-serif; background: #f4f4f4; color: #333; min-height: 100vh; display: flex; flex-direction: column; }

        /* Header */
        header { background: #2ecc71; color: white; padding: 15px 30px; text-align: center; }
        header h1 { font-size: 24px; }
        header p  { font-size: 13px; opacity: .85; margin-top: 3px; }

        /* Navbar */
        nav { background: #27ae60; padding: 10px 30px; display: flex; align-items: center; flex-wrap: wrap; gap: 6px; }
        nav > a { color: white; text-decoration: none; font-weight: bold; font-size: 14px; margin-right: 10px; }
        nav > a:hover { text-decoration: underline; }
        .nav-icons { display: flex; gap: 10px; margin-left: auto; align-items: center; }
        .nav-icons a {
            color: white; display: flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,.2); padding: 6px 12px; border-radius: 6px;
            font-size: 13px; font-weight: bold; text-decoration: none;
        }
        .nav-icons a:hover { background: rgba(255,255,255,.35); }
        .nav-icons a.logout-btn { background: rgba(231,76,60,.5); }

        /* Steps */
        .steps {
            display: flex; justify-content: center; align-items: center;
            gap: 0; padding: 20px 30px; background: white;
            border-bottom: 1px solid #eee; flex-wrap: wrap;
        }
        .step { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: bold; color: #bbb; }
        .step .num {
            width: 30px; height: 30px; border-radius: 50%;
            background: #eee; color: #bbb;
            display: flex; align-items: center; justify-content: center; font-size: 13px;
        }
        .step.done .num  { background: #2ecc71; color: white; }
        .step.done       { color: #2ecc71; }
        .step-line { width: 60px; height: 2px; background: #eee; margin: 0 6px; }
        .step-line.done { background: #2ecc71; }

        /* Main */
        .main { flex: 1; padding: 30px; max-width: 860px; margin: 0 auto; width: 100%; }

        /* ── Success Banner ── */
        .success-banner {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            border-radius: 16px; color: white;
            text-align: center; padding: 40px 30px;
            margin-bottom: 28px; position: relative; overflow: hidden;
        }
        .success-banner::before {
            content: '✓'; font-size: 200px; font-weight: 900;
            position: absolute; opacity: .06;
            top: -40px; right: -20px; line-height: 1;
        }
        .check-circle {
            width: 80px; height: 80px; border-radius: 50%;
            background: rgba(255,255,255,.25);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px; font-size: 36px;
            animation: popIn .5s ease;
        }
        @keyframes popIn {
            0%   { transform: scale(0); opacity: 0; }
            70%  { transform: scale(1.15); }
            100% { transform: scale(1); opacity: 1; }
        }
        .success-banner h1 { font-size: 28px; margin-bottom: 8px; }
        .success-banner p  { font-size: 15px; opacity: .9; }
        .order-id-badge {
            display: inline-block; margin-top: 16px;
            background: rgba(255,255,255,.2);
            padding: 8px 20px; border-radius: 20px;
            font-size: 14px; font-weight: bold; letter-spacing: 1px;
        }

        /* ── Cards grid ── */
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 24px; }

        .info-card { background: white; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); overflow: hidden; }
        .info-card-header {
            background: #f0fdf4; padding: 13px 18px;
            border-bottom: 1px solid #e8f8f0;
            font-size: 14px; font-weight: bold; color: #27ae60;
            display: flex; align-items: center; gap: 8px;
        }
        .info-card-body { padding: 4px 0; }
        .info-row {
            display: flex; justify-content: space-between; align-items: flex-start;
            padding: 11px 18px; border-bottom: 1px solid #f5f5f5; font-size: 13px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-row .lbl { color: #888; flex-shrink: 0; margin-right: 10px; }
        .info-row .val { font-weight: bold; text-align: right; }

        /* ── Delivery tracker ── */
        .tracker-card { background: white; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); padding: 22px 24px; margin-bottom: 24px; }
        .tracker-title { font-size: 15px; font-weight: bold; color: #27ae60; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .tracker {
            display: flex; align-items: flex-start;
            justify-content: space-between; position: relative;
            flex-wrap: wrap; gap: 10px;
        }
        .tracker::before {
            content: ''; position: absolute;
            top: 18px; left: 18px; right: 18px; height: 3px;
            background: #eee; z-index: 0;
        }
        .tracker-progress {
            position: absolute; top: 18px; left: 18px;
            height: 3px; background: #2ecc71; z-index: 1;
            width: 16%; transition: width 1s ease;
        }
        .track-step { display: flex; flex-direction: column; align-items: center; gap: 8px; z-index: 2; flex: 1; min-width: 70px; }
        .track-icon {
            width: 38px; height: 38px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; border: 3px solid #eee; background: white;
        }
        .track-step.done .track-icon  { background: #2ecc71; border-color: #2ecc71; color: white; }
        .track-step.active .track-icon { background: #f39c12; border-color: #f39c12; color: white; }
        .track-step span { font-size: 11px; color: #888; text-align: center; font-weight: bold; }
        .track-step.done span, .track-step.active span { color: #333; }

        /* ── Items table ── */
        .items-card { background: white; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
        .items-card-header {
            background: #f0fdf4; padding: 13px 18px;
            border-bottom: 1px solid #e8f8f0;
            font-size: 14px; font-weight: bold; color: #27ae60;
            display: flex; align-items: center; gap: 8px;
        }
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th { background: #2ecc71; color: white; padding: 11px 16px; text-align: left; font-size: 13px; }
        .items-table td { padding: 12px 16px; border-bottom: 1px solid #f5f5f5; font-size: 13px; vertical-align: middle; }
        .items-table tr:last-child td { border-bottom: none; }
        .items-table tr:hover td { background: #f9fffe; }
        .item-cell { display: flex; align-items: center; gap: 12px; }
        .item-cell img { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; }
        .item-cell strong { font-size: 14px; }
        .item-cell span   { font-size: 12px; color: #888; }
        .item-price { font-weight: bold; color: #27ae60; }

        /* Totals */
        .totals-section { padding: 0 16px 16px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; border-bottom: 1px solid #f5f5f5; }
        .total-row:last-child { border-bottom: none; }
        .total-row .tl { color: #666; }
        .total-row .tv { font-weight: bold; }
        .grand-row {
            display: flex; justify-content: space-between;
            background: #f0fdf4; margin: 0 -16px;
            padding: 14px 16px; font-size: 16px; font-weight: bold;
        }
        .grand-row .tv { color: #27ae60; font-size: 20px; }

        /* ── Action buttons ── */
        .action-btns { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 40px; }
        .btn-primary {
            flex: 1; min-width: 180px; padding: 14px 20px;
            background: #2ecc71; color: white; border: none;
            border-radius: 10px; font-size: 15px; font-weight: bold;
            cursor: pointer; text-decoration: none; text-align: center;
            transition: background .2s; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-primary:hover { background: #27ae60; }
        .btn-outline {
            flex: 1; min-width: 180px; padding: 14px 20px;
            background: white; color: #27ae60;
            border: 2px solid #27ae60; border-radius: 10px;
            font-size: 15px; font-weight: bold; cursor: pointer;
            text-decoration: none; text-align: center;
            transition: all .2s; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-outline:hover { background: #27ae60; color: white; }

        /* Footer */
        footer { background: #2ecc71; color: white; text-align: center; padding: 14px; font-size: 13px; margin-top: auto; }

        @media (max-width: 600px) {
            .main { padding: 16px; }
            .items-table th:nth-child(3),
            .items-table td:nth-child(3) { display: none; }
        }
    </style>
</head>
<body>

<header>
    <h1><i class="fa-solid fa-leaf"></i> Fresh Grocery Store</h1>
    <p>Your daily needs delivered fresh!</p>
</header>

<nav>
    <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
    <a href="product.php"><i class="fa-solid fa-store"></i> Products</a>
    <a href="offer.php"><i class="fa-solid fa-tag"></i> Offers</a>
    <a href="contect.php"><i class="fa-solid fa-envelope"></i> Contact</a>
    <div class="nav-icons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="account.php"><i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($_SESSION['name']); ?></a>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <?php else: ?>
            <a href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
        <?php endif; ?>
        <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
        <a href="cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Steps -->
<div class="steps">
    <div class="step done">
        <div class="num"><i class="fa-solid fa-check"></i></div> Cart
    </div>
    <div class="step-line done"></div>
    <div class="step done">
        <div class="num"><i class="fa-solid fa-check"></i></div> Checkout
    </div>
    <div class="step-line done"></div>
    <div class="step done">
        <div class="num"><i class="fa-solid fa-check"></i></div> Confirmed
    </div>
</div>

<div class="main">

    <!-- Success Banner -->
    <div class="success-banner">
        <div class="check-circle">
            <i class="fa-solid fa-check"></i>
        </div>
        <h1>Order Placed Successfully! 🎉</h1>
        <p>Thank you, <strong><?php echo htmlspecialchars($order['name']); ?></strong>! Your order is confirmed and will be delivered soon.</p>
        <div class="order-id-badge">
            <i class="fa-solid fa-hashtag"></i> Order ID: <?php echo $order['order_id']; ?>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="cards-grid">

        <!-- Delivery Info -->
        <div class="info-card">
            <div class="info-card-header">
                <i class="fa-solid fa-location-dot"></i> Delivery Details
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <span class="lbl">Name</span>
                    <span class="val"><?php echo htmlspecialchars($order['name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Phone</span>
                    <span class="val"><?php echo htmlspecialchars($order['phone']); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Email</span>
                    <span class="val"><?php echo htmlspecialchars($order['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Address</span>
                    <span class="val"><?php echo htmlspecialchars($order['address']); ?></span>
                </div>
            </div>
        </div>

        <!-- Order Info -->
        <div class="info-card">
            <div class="info-card-header">
                <i class="fa-solid fa-receipt"></i> Order Info
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <span class="lbl">Order ID</span>
                    <span class="val" style="color:#27ae60;"><?php echo $order['order_id']; ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Date &amp; Time</span>
                    <span class="val"><?php echo $order['date']; ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Payment</span>
                    <span class="val"><?php echo htmlspecialchars($pay_label); ?></span>
                </div>
                <div class="info-row">
                    <span class="lbl">Est. Delivery</span>
                    <span class="val" style="color:#2ecc71;"><?php echo $est_delivery; ?></span>
                </div>
            </div>
        </div>

    </div>

    <!-- Delivery Tracker -->
    <div class="tracker-card">
        <div class="tracker-title">
            <i class="fa-solid fa-truck-fast"></i> Order Tracking
        </div>
        <div class="tracker">
            <div class="tracker-progress" id="trackerBar"></div>

            <div class="track-step done">
                <div class="track-icon"><i class="fa-solid fa-check"></i></div>
                <span>Order<br>Placed</span>
            </div>
            <div class="track-step active">
                <div class="track-icon"><i class="fa-solid fa-box"></i></div>
                <span>Being<br>Packed</span>
            </div>
            <div class="track-step">
                <div class="track-icon"><i class="fa-solid fa-truck"></i></div>
                <span>Out for<br>Delivery</span>
            </div>
            <div class="track-step">
                <div class="track-icon"><i class="fa-solid fa-house"></i></div>
                <span>Delivered</span>
            </div>
        </div>
    </div>

    <!-- Ordered Items -->
    <div class="items-card">
        <div class="items-card-header">
            <i class="fa-solid fa-basket-shopping"></i>
            Ordered Items (<?php echo count($order['items']); ?> product<?php echo count($order['items']) > 1 ? 's' : ''; ?>)
        </div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $item):
                    $line = (int)$item['price'] * (int)$item['qty'];
                ?>
                <tr>
                    <td>
                        <div class="item-cell">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                <span>Fresh &amp; Natural</span>
                            </div>
                        </div>
                    </td>
                    <td>₹<?php echo $item['price']; ?></td>
                    <td><?php echo $item['qty']; ?></td>
                    <td class="item-price">₹<?php echo $line; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals-section">
            <div class="total-row">
                <span class="tl">Subtotal</span>
                <span class="tv">₹<?php echo $order['subtotal']; ?></span>
            </div>
            <div class="total-row">
                <span class="tl">Delivery Charges</span>
                <span class="tv" style="color:<?php echo $order['delivery'] ? '#333' : '#2ecc71'; ?>">
                    <?php echo $order['delivery'] ? '₹' . $order['delivery'] : 'FREE'; ?>
                </span>
            </div>
            <div class="grand-row">
                <span>Grand Total</span>
                <span class="tv">₹<?php echo $order['grand_total']; ?></span>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-btns">
        <a href="product.php" class="btn-primary">
            <i class="fa-solid fa-store"></i> Continue Shopping
        </a>
        <a href="index.php" class="btn-outline">
            <i class="fa-solid fa-house"></i> Back to Home
        </a>
        <a href="javascript:window.print()" class="btn-outline">
            <i class="fa-solid fa-print"></i> Print Receipt
        </a>
    </div>

</div>

<footer>
    <p>© 2026 Fresh Grocery Store | All Rights Reserved</p>
</footer>

<script>
// Animate tracker bar to "Being Packed" position (~33%)
setTimeout(() => {
    document.getElementById('trackerBar').style.width = '33%';
}, 300);
</script>

</body>
</html>
