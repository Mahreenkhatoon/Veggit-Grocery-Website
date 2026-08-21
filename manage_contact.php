<?php
session_start();
include "db.php";

// Admin guard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Create table if missing
$conn->query("CREATE TABLE IF NOT EXISTS contacts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    phone      VARCHAR(20)  NOT NULL DEFAULT '',
    subject    VARCHAR(100) NOT NULL,
    message    TEXT         NOT NULL,
    rating     INT          NOT NULL DEFAULT 0,
    status     ENUM('unread','read') NOT NULL DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$msg = "";

// Mark as read
if (isset($_GET['read'])) {
    $rid = (int)$_GET['read'];
    $s = $conn->prepare("UPDATE contacts SET status='read' WHERE id=?");
    $s->bind_param("i", $rid);
    $s->execute();
    $s->close();
    header("Location: manage_contact.php");
    exit();
}

// Delete message
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $s = $conn->prepare("DELETE FROM contacts WHERE id=?");
    $s->bind_param("i", $did);
    $s->execute();
    $s->close();
    $msg = "Message deleted.";
}

// Filter
$filter = $_GET['filter'] ?? 'all';
if ($filter === 'unread') {
    $contacts = $conn->query("SELECT * FROM contacts WHERE status='unread' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
} elseif ($filter === 'read') {
    $contacts = $conn->query("SELECT * FROM contacts WHERE status='read' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
} else {
    $contacts = $conn->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

// Counts
$total_c  = $conn->query("SELECT COUNT(*) AS c FROM contacts")->fetch_assoc()['c'];
$unread_c = $conn->query("SELECT COUNT(*) AS c FROM contacts WHERE status='unread'")->fetch_assoc()['c'];
$read_c   = $conn->query("SELECT COUNT(*) AS c FROM contacts WHERE status='read'")->fetch_assoc()['c'];

$cart_count = 0;
if (isset($_SESSION['cart'])) foreach ($_SESSION['cart'] as $i) $cart_count += (int)$i['qty'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Messages – Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:#f4f4f4;color:#333;min-height:100vh;display:flex;flex-direction:column}
header{background:#2ecc71;color:white;padding:16px 30px;text-align:center}
header h1{font-size:22px}
nav{background:#27ae60;padding:10px 30px;display:flex;align-items:center;flex-wrap:wrap;gap:6px}
nav>a{color:white;text-decoration:none;font-weight:bold;font-size:14px;margin-right:10px}
nav>a:hover{text-decoration:underline}
.nav-icons{display:flex;gap:10px;margin-left:auto;align-items:center}
.nav-icons a{color:white;display:flex;align-items:center;gap:6px;background:rgba(255,255,255,.2);padding:6px 12px;border-radius:6px;font-size:13px;font-weight:bold;text-decoration:none;transition:background .2s}
.nav-icons a:hover{background:rgba(255,255,255,.35)}
.nav-icons a.logout-btn{background:rgba(231,76,60,.5)}
.nav-icons a.logout-btn:hover{background:rgba(231,76,60,.8)}
.cart-badge{background:#e74c3c;color:white;border-radius:50%;font-size:11px;padding:1px 6px;font-weight:bold}
.page-hero{background:linear-gradient(135deg,#3498db,#2980b9);color:white;padding:30px;text-align:center}
.page-hero h1{font-size:28px}
.page-hero p{font-size:13px;opacity:.9;margin-top:6px}
.breadcrumb{padding:10px 30px;font-size:13px;color:#777}
.breadcrumb a{color:#27ae60;text-decoration:none}
.main{flex:1;padding:20px 30px 50px}
.stats-bar{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:24px}
.stat-card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.07);padding:16px 22px;flex:1;min-width:130px;display:flex;align-items:center;gap:14px}
.stat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px}
.stat-icon.blue{background:#eaf4fd;color:#2980b9}
.stat-icon.red{background:#fdecea;color:#e74c3c}
.stat-icon.green{background:#e8f8f0;color:#27ae60}
.stat-num{font-size:24px;font-weight:bold}
.stat-label{font-size:12px;color:#888}
.filter-bar{display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;align-items:center}
.filter-bar a{padding:7px 18px;border-radius:20px;font-size:13px;font-weight:bold;text-decoration:none;border:2px solid #ddd;color:#555;transition:all .2s}
.filter-bar a.active,.filter-bar a:hover{background:#3498db;border-color:#3498db;color:white}
.filter-bar a.unread-tab.active,.filter-bar a.unread-tab:hover{background:#e74c3c;border-color:#e74c3c}
.filter-bar a.read-tab.active,.filter-bar a.read-tab:hover{background:#27ae60;border-color:#27ae60}
.alert{padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.alert-success{background:#e8f8f0;border-left:4px solid #2ecc71;color:#1e8449}
.empty-state{text-align:center;padding:60px 20px;background:white;border-radius:12px;box-shadow:0 3px 14px rgba(0,0,0,.08)}
.empty-state i{font-size:52px;color:#ddd;margin-bottom:14px;display:block}
.empty-state p{color:#aaa;font-size:15px}

/* Message cards */
.messages-list{display:flex;flex-direction:column;gap:14px}
.msg-card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.07);overflow:hidden;border-left:5px solid #3498db;transition:box-shadow .2s}
.msg-card.unread{border-left-color:#e74c3c}
.msg-card.read{border-left-color:#2ecc71}
.msg-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.12)}
.msg-header{display:flex;align-items:center;gap:14px;padding:16px 20px;border-bottom:1px solid #f5f5f5;flex-wrap:wrap}
.msg-avatar{width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#3498db,#2980b9);color:white;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:bold;flex-shrink:0}
.msg-meta{flex:1}
.msg-meta .msg-name{font-size:15px;font-weight:bold;color:#222}
.msg-meta .msg-email{font-size:12px;color:#888;margin-top:2px}
.msg-right{display:flex;flex-direction:column;align-items:flex-end;gap:6px}
.msg-time{font-size:11px;color:#aaa}
.status-badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:bold}
.status-badge.unread{background:#fdecea;color:#e74c3c}
.status-badge.read{background:#e8f8f0;color:#27ae60}
.msg-body{padding:14px 20px}
.msg-subject{font-size:13px;font-weight:bold;color:#333;margin-bottom:6px;display:flex;align-items:center;gap:6px}
.msg-subject .subject-tag{background:#eaf4fd;color:#2980b9;padding:2px 8px;border-radius:8px;font-size:11px}
.msg-text{font-size:13px;color:#555;line-height:1.6;margin-bottom:10px}
.msg-footer{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.msg-phone{font-size:12px;color:#888;display:flex;align-items:center;gap:5px}
.stars-display{color:#f39c12;font-size:12px}
.stars-display .no-rating{color:#ccc}
.action-btns{display:flex;gap:8px}
.btn-read{padding:6px 14px;background:#27ae60;color:white;border:none;border-radius:6px;font-size:12px;font-weight:bold;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:background .2s}
.btn-read:hover{background:#1e8449}
.btn-delete{padding:6px 14px;background:#e74c3c;color:white;border:none;border-radius:6px;font-size:12px;font-weight:bold;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:background .2s}
.btn-delete:hover{background:#c0392b}
footer{background:#2ecc71;color:white;text-align:center;padding:14px;font-size:13px}
@media(max-width:600px){.main{padding:14px}.msg-header{gap:10px}}
</style>
</head>
<body>

<header>
    <h1><i class="fa-solid fa-leaf"></i> Fresh Grocery Store</h1>
    <p>Admin Panel</p>
</header>

<nav>
    <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
    <a href="manage_product.php"><i class="fa-solid fa-boxes-stacked"></i> Products</a>
    <a href="manage_offer.php"><i class="fa-solid fa-tag"></i> Offers</a>
    <a href="manage_contact.php"><i class="fa-solid fa-envelope"></i> Messages
        <?php if ($unread_c > 0): ?>
            <span style="background:#e74c3c;color:white;border-radius:50%;font-size:11px;padding:1px 6px;font-weight:bold"><?php echo $unread_c; ?></span>
        <?php endif; ?>
    </a>
    <div class="nav-icons">
        <a href="account.php"><i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($_SESSION['name']); ?></a>
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <a href="manage_contact.php">
            <i class="fa-solid fa-envelope"></i> Messages
            <?php if ($unread_c > 0): ?>
                <span style="background:#e74c3c;color:white;border-radius:50%;font-size:11px;padding:1px 6px;font-weight:bold"><?php echo $unread_c; ?></span>
            <?php endif; ?>
        </a>
    </div>
</nav>

<div class="page-hero">
    <h1><i class="fa-solid fa-envelope-open-text"></i> Customer Messages</h1>
    <p>View and manage all customer complaints & enquiries</p>
</div>

<div class="breadcrumb">
    <a href="index.php">Home</a> &rsaquo; <strong>Customer Messages</strong>
</div>

<div class="main">

    <?php if ($msg): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-envelope"></i></div>
            <div><div class="stat-num"><?php echo $total_c; ?></div><div class="stat-label">Total Messages</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fa-solid fa-envelope-circle-check"></i></div>
            <div><div class="stat-num"><?php echo $unread_c; ?></div><div class="stat-label">Unread</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-check-double"></i></div>
            <div><div class="stat-num"><?php echo $read_c; ?></div><div class="stat-label">Read</div></div>
        </div>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
        <a href="manage_contact.php"               class="<?php echo $filter==='all'    ? 'active' : ''; ?>">All (<?php echo $total_c; ?>)</a>
        <a href="manage_contact.php?filter=unread" class="unread-tab <?php echo $filter==='unread' ? 'active' : ''; ?>">🔴 Unread (<?php echo $unread_c; ?>)</a>
        <a href="manage_contact.php?filter=read"   class="read-tab <?php echo $filter==='read'   ? 'active' : ''; ?>">✅ Read (<?php echo $read_c; ?>)</a>
    </div>

    <!-- Messages -->
    <?php if (empty($contacts)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-inbox"></i>
        <p>No messages found.</p>
    </div>
    <?php else: ?>
    <div class="messages-list">
        <?php foreach ($contacts as $c):
            $initials = strtoupper(substr($c['name'], 0, 1));
            $stars_html = '';
            if ($c['rating'] > 0) {
                for ($i = 1; $i <= 5; $i++) {
                    $stars_html .= $i <= $c['rating'] ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
                }
            }
        ?>
        <div class="msg-card <?php echo $c['status']; ?>">
            <div class="msg-header">
                <div class="msg-avatar"><?php echo $initials; ?></div>
                <div class="msg-meta">
                    <div class="msg-name"><?php echo htmlspecialchars($c['name']); ?></div>
                    <div class="msg-email"><i class="fa-solid fa-envelope" style="font-size:10px"></i> <?php echo htmlspecialchars($c['email']); ?></div>
                </div>
                <div class="msg-right">
                    <span class="msg-time"><i class="fa-regular fa-clock"></i> <?php echo date('d M Y, h:i A', strtotime($c['created_at'])); ?></span>
                    <span class="status-badge <?php echo $c['status']; ?>">
                        <?php echo $c['status'] === 'unread' ? '🔴 Unread' : '✅ Read'; ?>
                    </span>
                </div>
            </div>
            <div class="msg-body">
                <div class="msg-subject">
                    <i class="fa-solid fa-tag" style="color:#3498db"></i>
                    <span class="subject-tag"><?php echo htmlspecialchars($c['subject']); ?></span>
                </div>
                <div class="msg-text"><?php echo nl2br(htmlspecialchars($c['message'])); ?></div>
                <div class="msg-footer">
                    <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
                        <?php if ($c['phone']): ?>
                        <div class="msg-phone"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($c['phone']); ?></div>
                        <?php endif; ?>
                        <?php if ($c['rating'] > 0): ?>
                        <div class="stars-display"><?php echo $stars_html; ?> (<?php echo $c['rating']; ?>/5)</div>
                        <?php else: ?>
                        <div class="stars-display"><span class="no-rating">No rating given</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="action-btns">
                        <?php if ($c['status'] === 'unread'): ?>
                        <a href="manage_contact.php?read=<?php echo $c['id']; ?>" class="btn-read">
                            <i class="fa-solid fa-check"></i> Mark Read
                        </a>
                        <?php endif; ?>
                        <a href="manage_contact.php?delete=<?php echo $c['id']; ?>"
                           class="btn-delete"
                           onclick="return confirm('Delete this message from <?php echo addslashes($c['name']); ?>?')">
                            <i class="fa-solid fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<footer><p>© 2026 Fresh Grocery Store | Admin Panel</p></footer>
</body>
</html>
