<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email, role FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) $cart_count += (int)$item['qty'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account – Fresh Grocery Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; min-height: 100vh; display: flex; flex-direction: column; }

        header { background: #2ecc71; color: white; padding: 16px 30px; text-align: center; }
        header h1 { font-size: 24px; }
        header p  { font-size: 13px; opacity: .85; margin-top: 3px; }

        nav {
            background: #27ae60; padding: 10px 30px;
            display: flex; align-items: center; flex-wrap: wrap; gap: 6px;
        }
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

        .breadcrumb { padding: 12px 30px; font-size: 13px; color: #777; }
        .breadcrumb a { color: #27ae60; text-decoration: none; }

        .page-wrapper { flex: 1; padding: 20px 30px 50px; max-width: 900px; margin: 0 auto; width: 100%; }

        /* Profile hero */
        .profile-hero {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            border-radius: 16px;
            padding: 36px 30px;
            color: white;
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }
        .avatar {
            width: 90px; height: 90px;
            background: rgba(255,255,255,.25);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 40px;
            flex-shrink: 0;
        }
        .profile-info h2 { font-size: 24px; }
        .profile-info p  { font-size: 14px; opacity: .85; margin-top: 4px; }
        .role-badge {
            display: inline-block;
            background: rgba(255,255,255,.25);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 8px;
            text-transform: capitalize;
        }

        /* Cards grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }
        .info-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            overflow: hidden;
        }
        .info-card-header {
            background: #f0fdf4;
            padding: 14px 20px;
            font-weight: bold;
            font-size: 15px;
            color: #27ae60;
            border-bottom: 1px solid #e8f8f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-card-body { padding: 16px 20px; }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 14px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: #888; }
        .info-row .value { font-weight: bold; color: #333; }

        /* Action buttons */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-top: 24px;
        }
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            transition: all .2s;
            border: none;
            cursor: pointer;
        }
        .action-btn.green  { background: #2ecc71; color: white; }
        .action-btn.green:hover  { background: #27ae60; }
        .action-btn.blue   { background: #3498db; color: white; }
        .action-btn.blue:hover   { background: #2980b9; }
        .action-btn.orange { background: #f39c12; color: white; }
        .action-btn.orange:hover { background: #e67e22; }
        .action-btn.red    { background: #e74c3c; color: white; }
        .action-btn.red:hover    { background: #c0392b; }

        footer { background: #2ecc71; color: white; text-align: center; padding: 14px; font-size: 13px; margin-top: auto; }
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
        <a href="account.php">
            <i class="fa-solid fa-circle-user"></i>
            <?php echo htmlspecialchars($user['name']); ?>
        </a>
        <a href="logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
        <?php if ($user['role'] !== 'admin'): ?>
        <a href="cart.php">
            <i class="fa-solid fa-cart-shopping"></i> Cart
            <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?php echo $cart_count; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
    </div>
</nav>

<div class="breadcrumb">
    <a href="index.php">Home</a> &rsaquo; <strong>My Account</strong>
</div>

<div class="page-wrapper">

    <!-- Profile Hero -->
    <div class="profile-hero">
        <div class="avatar"><i class="fa-solid fa-user"></i></div>
        <div class="profile-info">
            <h2>👋 Hello, <?php echo htmlspecialchars($user['name']); ?>!</h2>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
            <span class="role-badge">
                <i class="fa-solid fa-<?php echo $user['role'] === 'admin' ? 'crown' : 'user'; ?>"></i>
                <?php echo ucfirst($user['role']); ?>
            </span>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="cards-grid">
        <div class="info-card">
            <div class="info-card-header">
                <i class="fa-solid fa-circle-info"></i> Account Details
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <span class="label">Full Name</span>
                    <span class="value"><?php echo htmlspecialchars($user['name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Email</span>
                    <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Role</span>
                    <span class="value"><?php echo ucfirst($user['role']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Member Since</span>
                    <span class="value">2026</span>
                </div>
            </div>
        </div>

        <?php if ($user['role'] !== 'admin'): ?>
        <div class="info-card">
            <div class="info-card-header">
                <i class="fa-solid fa-cart-shopping"></i> Cart Summary
            </div>
            <div class="info-card-body">
                <div class="info-row">
                    <span class="label">Items in Cart</span>
                    <span class="value"><?php echo $cart_count; ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Cart Status</span>
                    <span class="value" style="color:<?php echo $cart_count > 0 ? '#2ecc71' : '#aaa'; ?>">
                        <?php echo $cart_count > 0 ? 'Has Items' : 'Empty'; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="label">Free Delivery</span>
                    <span class="value">On orders ₹500+</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <div class="action-grid">
        <?php if ($user['role'] === 'admin'): ?>
            <a href="manage_product.php" class="action-btn green">
                <i class="fa-solid fa-boxes-stacked"></i> Manage Products
            </a>
            <a href="manage_offer.php" class="action-btn orange">
                <i class="fa-solid fa-tag"></i> Manage Offers
            </a>
            <a href="manage_contact.php" class="action-btn blue">
                <i class="fa-solid fa-envelope"></i> Messages
            </a>
        <?php else: ?>
            <a href="cart.php" class="action-btn green">
                <i class="fa-solid fa-cart-shopping"></i> View Cart
            </a>
            <a href="product.php" class="action-btn blue">
                <i class="fa-solid fa-store"></i> Shop Now
            </a>
            <a href="offer.php" class="action-btn orange">
                <i class="fa-solid fa-tag"></i> View Offers
            </a>
        <?php endif; ?>
        <a href="logout.php" class="action-btn red">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>

</div>

<footer>
    <p>© 2026 Fresh Grocery Store | All Rights Reserved</p>
</footer>

</body>
</html>
