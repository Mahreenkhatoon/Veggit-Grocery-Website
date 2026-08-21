<?php
session_start();

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// ── Handle order placement ──
$errors = [];
if (isset($_POST['place_order'])) {

    $name    = trim($_POST['full_name']  ?? '');
    $phone   = trim($_POST['phone']      ?? '');
    $email   = trim($_POST['email']      ?? '');
    $address = trim($_POST['address']    ?? '');
    $city    = trim($_POST['city']       ?? '');
    $pincode = trim($_POST['pincode']    ?? '');
    $payment = $_POST['payment']         ?? '';

    if (!$name)    $errors[] = "Full name is required.";
    if (!$phone || !preg_match('/^[0-9]{10}$/', $phone)) $errors[] = "Enter a valid 10-digit phone number.";
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email address.";
    if (!$address) $errors[] = "Address is required.";
    if (!$city)    $errors[] = "City is required.";
    if (!$pincode || !preg_match('/^[0-9]{6}$/', $pincode)) $errors[] = "Enter a valid 6-digit PIN code.";
    if (!$payment) $errors[] = "Please select a payment method.";

    if (empty($errors)) {
        // Build order ID
        $order_id = 'FGS' . strtoupper(substr(md5(uniqid()), 0, 8));

        // Calculate totals
        $subtotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += (int)$item['price'] * (int)$item['qty'];
        }
        $delivery    = ($subtotal < 500) ? 40 : 0;
        $grand_total = $subtotal + $delivery;

        // Build order object
        $new_order = [
            'order_id'    => $order_id,
            'user_id'     => $_SESSION['user_id'] ?? 0,
            'user_name'   => $_SESSION['name']    ?? $name,
            'name'        => $name,
            'phone'       => $phone,
            'email'       => $email,
            'address'     => $address . ', ' . $city . ' – ' . $pincode,
            'payment'     => $payment,
            'items'       => $_SESSION['cart'],
            'subtotal'    => $subtotal,
            'delivery'    => $delivery,
            'grand_total' => $grand_total,
            'date'        => date('d M Y, h:i A'),
            'status'      => 'Pending',
        ];

        // Save for confirm page
        $_SESSION['last_order'] = $new_order;

        // Save to global orders list (for admin)
        if (!isset($_SESSION['all_orders'])) $_SESSION['all_orders'] = [];
        array_unshift($_SESSION['all_orders'], $new_order);

        // Clear cart
        $_SESSION['cart'] = [];

        header("Location: order_confirm.php");
        exit();
    }
}

// Totals for display
$subtotal    = 0;
$item_count  = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal   += (int)$item['price'] * (int)$item['qty'];
    $item_count += (int)$item['qty'];
}
$delivery    = ($subtotal < 500) ? 40 : 0;
$grand_total = $subtotal + $delivery;

// Pre-fill from session
$prefill_name  = htmlspecialchars($_SESSION['name']  ?? '');
$prefill_email = htmlspecialchars($_SESSION['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout – Fresh Grocery Store</title>
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

        /* Breadcrumb */
        .breadcrumb { padding: 12px 30px; font-size: 13px; color: #777; }
        .breadcrumb a { color: #27ae60; text-decoration: none; }

        /* Steps bar */
        .steps {
            display: flex; justify-content: center; align-items: center;
            gap: 0; padding: 20px 30px; background: white;
            border-bottom: 1px solid #eee; flex-wrap: wrap;
        }
        .step {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; font-weight: bold; color: #bbb;
        }
        .step .num {
            width: 30px; height: 30px; border-radius: 50%;
            background: #eee; color: #bbb;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: bold;
        }
        .step.done .num  { background: #2ecc71; color: white; }
        .step.done       { color: #2ecc71; }
        .step.active .num { background: #27ae60; color: white; }
        .step.active      { color: #27ae60; }
        .step-line { width: 60px; height: 2px; background: #eee; margin: 0 6px; }
        .step-line.done { background: #2ecc71; }

        /* Page title */
        .page-title { padding: 20px 30px 10px; font-size: 22px; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        .page-title i { color: #2ecc71; }

        /* Layout */
        .checkout-wrapper { display: flex; gap: 24px; padding: 0 30px 50px; flex: 1; align-items: flex-start; flex-wrap: wrap; }

        /* ── Form side ── */
        .checkout-form { flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 20px; }

        .form-card { background: white; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); overflow: hidden; }
        .form-card-header {
            background: #f0fdf4; padding: 14px 20px;
            border-bottom: 1px solid #e8f8f0;
            font-size: 15px; font-weight: bold; color: #27ae60;
            display: flex; align-items: center; gap: 8px;
        }
        .form-card-body { padding: 20px; }

        /* Error box */
        .error-box {
            background: #fdecea; border-left: 4px solid #e74c3c;
            border-radius: 6px; padding: 12px 16px; margin-bottom: 16px;
        }
        .error-box p { font-size: 13px; color: #c0392b; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
        .error-box p:last-child { margin-bottom: 0; }

        /* Form rows */
        .form-row { display: flex; gap: 14px; flex-wrap: wrap; }
        .form-group { margin-bottom: 16px; flex: 1; min-width: 200px; }
        .form-group.full { flex: 100%; min-width: 100%; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; color: #555; margin-bottom: 6px; }
        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 13px; }
        .input-wrap input,
        .input-wrap select,
        .input-wrap textarea {
            width: 100%; padding: 11px 12px 11px 36px;
            border: 1px solid #ddd; border-radius: 8px;
            font-size: 14px; font-family: Arial, sans-serif;
            transition: border-color .2s, box-shadow .2s;
        }
        .input-wrap input:focus,
        .input-wrap select:focus,
        .input-wrap textarea:focus {
            outline: none; border-color: #2ecc71;
            box-shadow: 0 0 0 3px rgba(46,204,113,.12);
        }
        .input-wrap textarea { resize: vertical; min-height: 80px; padding-top: 10px; }
        .input-wrap.ta-wrap i { top: 14px; transform: none; }

        /* Payment options */
        .payment-options { display: flex; flex-direction: column; gap: 10px; }
        .pay-option {
            display: flex; align-items: center; gap: 14px;
            border: 2px solid #ddd; border-radius: 10px; padding: 14px 16px;
            cursor: pointer; transition: all .2s;
        }
        .pay-option:hover { border-color: #2ecc71; background: #f9fffe; }
        .pay-option input[type="radio"] { display: none; }
        .pay-option.selected { border-color: #2ecc71; background: #f0fdf4; }
        .pay-icon {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .pay-icon.cod   { background: #fff8e1; color: #f39c12; }
        .pay-icon.upi   { background: #e8f4fd; color: #3498db; }
        .pay-icon.card  { background: #fdecea; color: #e74c3c; }
        .pay-icon.net   { background: #e8f8f0; color: #2ecc71; }
        .pay-label strong { display: block; font-size: 14px; color: #333; }
        .pay-label span   { font-size: 12px; color: #888; }
        .pay-check {
            margin-left: auto; width: 22px; height: 22px;
            border-radius: 50%; border: 2px solid #ddd;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 12px; flex-shrink: 0;
        }
        .pay-option.selected .pay-check { background: #2ecc71; border-color: #2ecc71; }

        /* ── Summary side ── */
        .checkout-summary { width: 320px; min-width: 280px; }
        .summary-card { background: white; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); overflow: hidden; }
        .summary-header { background: #2ecc71; color: white; padding: 16px 20px; font-size: 16px; font-weight: bold; display: flex; align-items: center; gap: 8px; }
        .summary-body { padding: 16px 20px; }

        .order-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f5f5f5; }
        .order-item:last-of-type { border-bottom: none; }
        .order-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; }
        .order-item-info { flex: 1; }
        .order-item-info strong { font-size: 13px; display: block; }
        .order-item-info span   { font-size: 12px; color: #888; }
        .order-item-price { font-weight: bold; color: #27ae60; font-size: 13px; }

        .summary-divider { border: none; border-top: 1px solid #eee; margin: 12px 0; }

        .summary-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
        .summary-row .lbl { color: #666; }
        .summary-row .val { font-weight: bold; }
        .summary-total {
            display: flex; justify-content: space-between;
            background: #f0fdf4; margin: 10px -20px -16px;
            padding: 14px 20px; font-size: 16px; font-weight: bold;
        }
        .summary-total .val { color: #27ae60; font-size: 20px; }

        /* Place order btn */
        .btn-place {
            display: block; width: 100%; padding: 15px;
            background: #2ecc71; color: white; border: none;
            border-radius: 8px; font-size: 16px; font-weight: bold;
            cursor: pointer; transition: background .2s; margin-top: 20px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-place:hover { background: #27ae60; }

        .secure-note { text-align: center; font-size: 12px; color: #aaa; margin-top: 10px; display: flex; align-items: center; justify-content: center; gap: 5px; }

        /* Footer */
        footer { background: #2ecc71; color: white; text-align: center; padding: 14px; font-size: 13px; margin-top: auto; }

        @media (max-width: 768px) {
            .checkout-wrapper { padding: 0 14px 40px; }
            .checkout-summary { width: 100%; }
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

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="index.php">Home</a> &rsaquo;
    <a href="cart.php">Cart</a> &rsaquo;
    <strong>Checkout</strong>
</div>

<!-- Steps -->
<div class="steps">
    <div class="step done">
        <div class="num"><i class="fa-solid fa-check"></i></div> Cart
    </div>
    <div class="step-line done"></div>
    <div class="step active">
        <div class="num">2</div> Checkout
    </div>
    <div class="step-line"></div>
    <div class="step">
        <div class="num">3</div> Confirmed
    </div>
</div>

<div class="page-title">
    <i class="fa-solid fa-truck-fast"></i> Delivery &amp; Payment
</div>

<form method="POST" action="checkout.php">
<div class="checkout-wrapper">

    <!-- Left: Form -->
    <div class="checkout-form">

        <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $e): ?>
                <p><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Delivery Address -->
        <div class="form-card">
            <div class="form-card-header">
                <i class="fa-solid fa-location-dot"></i> Delivery Address
            </div>
            <div class="form-card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-user"></i>
                            <input type="text" name="full_name" placeholder="Your full name"
                                   value="<?php echo $prefill_name ?: (htmlspecialchars($_POST['full_name'] ?? '')); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-phone"></i>
                            <input type="tel" name="phone" placeholder="10-digit mobile number"
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-group full">
                    <label>Email Address *</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" placeholder="your@email.com"
                               value="<?php echo $prefill_email ?: (htmlspecialchars($_POST['email'] ?? '')); ?>" required>
                    </div>
                </div>
                <div class="form-group full">
                    <label>Full Address *</label>
                    <div class="input-wrap ta-wrap">
                        <i class="fa-solid fa-house"></i>
                        <textarea name="address" placeholder="House no., Street, Area, Landmark…" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>City *</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-city"></i>
                            <input type="text" name="city" placeholder="City"
                                   value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>PIN Code *</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-map-pin"></i>
                            <input type="text" name="pincode" placeholder="6-digit PIN"
                                   maxlength="6"
                                   value="<?php echo htmlspecialchars($_POST['pincode'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Method -->
        <div class="form-card">
            <div class="form-card-header">
                <i class="fa-solid fa-credit-card"></i> Payment Method
            </div>
            <div class="form-card-body">
                <div class="payment-options" id="payOptions">

                    <label class="pay-option <?php echo (($_POST['payment'] ?? '') === 'cod') ? 'selected' : ''; ?>" onclick="selectPay(this, 'cod')">
                        <input type="radio" name="payment" value="cod" <?php echo (($_POST['payment'] ?? '') === 'cod') ? 'checked' : ''; ?>>
                        <div class="pay-icon cod"><i class="fa-solid fa-money-bill-wave"></i></div>
                        <div class="pay-label">
                            <strong>Cash on Delivery</strong>
                            <span>Pay when your order arrives</span>
                        </div>
                        <div class="pay-check"><i class="fa-solid fa-check"></i></div>
                    </label>

                    <label class="pay-option <?php echo (($_POST['payment'] ?? '') === 'upi') ? 'selected' : ''; ?>" onclick="selectPay(this, 'upi')">
                        <input type="radio" name="payment" value="upi" <?php echo (($_POST['payment'] ?? '') === 'upi') ? 'checked' : ''; ?>>
                        <div class="pay-icon upi"><i class="fa-solid fa-mobile-screen"></i></div>
                        <div class="pay-label">
                            <strong>UPI / QR Code</strong>
                            <span>Google Pay, PhonePe, Paytm</span>
                        </div>
                        <div class="pay-check"><i class="fa-solid fa-check"></i></div>
                    </label>

                    <label class="pay-option <?php echo (($_POST['payment'] ?? '') === 'card') ? 'selected' : ''; ?>" onclick="selectPay(this, 'card')">
                        <input type="radio" name="payment" value="card" <?php echo (($_POST['payment'] ?? '') === 'card') ? 'checked' : ''; ?>>
                        <div class="pay-icon card"><i class="fa-solid fa-credit-card"></i></div>
                        <div class="pay-label">
                            <strong>Credit / Debit Card</strong>
                            <span>Visa, Mastercard, RuPay</span>
                        </div>
                        <div class="pay-check"><i class="fa-solid fa-check"></i></div>
                    </label>

                    <label class="pay-option <?php echo (($_POST['payment'] ?? '') === 'net') ? 'selected' : ''; ?>" onclick="selectPay(this, 'net')">
                        <input type="radio" name="payment" value="net" <?php echo (($_POST['payment'] ?? '') === 'net') ? 'checked' : ''; ?>>
                        <div class="pay-icon net"><i class="fa-solid fa-building-columns"></i></div>
                        <div class="pay-label">
                            <strong>Net Banking</strong>
                            <span>All major banks supported</span>
                        </div>
                        <div class="pay-check"><i class="fa-solid fa-check"></i></div>
                    </label>

                </div>
            </div>
        </div>

    </div>

    <!-- Right: Order Summary -->
    <div class="checkout-summary">
        <div class="summary-card">
            <div class="summary-header">
                <i class="fa-solid fa-receipt"></i> Order Summary
                <span style="margin-left:auto; font-size:13px; opacity:.85;"><?php echo $item_count; ?> item<?php echo $item_count > 1 ? 's' : ''; ?></span>
            </div>
            <div class="summary-body">

                <?php foreach ($_SESSION['cart'] as $item):
                    $line = (int)$item['price'] * (int)$item['qty'];
                ?>
                <div class="order-item">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <div class="order-item-info">
                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                        <span>Qty: <?php echo $item['qty']; ?> × ₹<?php echo $item['price']; ?></span>
                    </div>
                    <div class="order-item-price">₹<?php echo $line; ?></div>
                </div>
                <?php endforeach; ?>

                <hr class="summary-divider">

                <div class="summary-row">
                    <span class="lbl">Subtotal</span>
                    <span class="val">₹<?php echo $subtotal; ?></span>
                </div>
                <div class="summary-row">
                    <span class="lbl">Delivery</span>
                    <span class="val" style="color:<?php echo $delivery ? '#333' : '#2ecc71'; ?>">
                        <?php echo $delivery ? '₹' . $delivery : 'FREE'; ?>
                    </span>
                </div>

                <div class="summary-total">
                    <span>Grand Total</span>
                    <span class="val">₹<?php echo $grand_total; ?></span>
                </div>

                <button type="submit" name="place_order" class="btn-place">
                    <i class="fa-solid fa-check-circle"></i> Place Order
                </button>

                <div class="secure-note">
                    <i class="fa-solid fa-shield-halved"></i> 100% Secure Checkout
                </div>

            </div>
        </div>
    </div>

</div>
</form>

<footer>
    <p>© 2026 Fresh Grocery Store | All Rights Reserved</p>
</footer>

<script>
function selectPay(el, val) {
    document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input[type="radio"]').checked = true;
}
</script>

</body>
</html>
