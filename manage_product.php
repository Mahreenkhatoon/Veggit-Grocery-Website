<?php
session_start();
include "db.php";

// ── Admin guard ──
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

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
$defaults = [
    // [name,          image,              price, unit,     description,                  category]
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
$s = $conn->prepare("INSERT IGNORE INTO products (name, image, price, unit, description, category) VALUES (?,?,?,?,?,?)");
foreach ($defaults as $d) {
    $price = (float)$d[2];
    $s->bind_param("ssdsss", $d[0], $d[1], $price, $d[3], $d[4], $d[5]);
    $s->execute();
}
$s->close();

$msg   = "";
$error = "";

// ── DELETE product ──
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $stmt->close();
    $msg = "Product deleted successfully.";
}

// ── ADD / EDIT product ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id     = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;
    $name        = trim($_POST['name']);
    $category    = $_POST['category'];
    $price       = (float)$_POST['price'];
    $unit        = trim($_POST['unit']);
    $description = trim($_POST['description']);
    $image       = trim($_POST['image_current']); // keep existing by default

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_name = time() . '_' . preg_replace('/[^a-z0-9._-]/i', '_', $_FILES['image']['name']);
            $dest     = "images/" . $new_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $image = $new_name;
            } else {
                $error = "Image upload failed. Check folder permissions.";
            }
        } else {
            $error = "Invalid image type. Allowed: jpg, jpeg, png, gif, webp.";
        }
    }

    if (!$error) {
        if ($edit_id > 0) {
            $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, unit=?, description=?, image=? WHERE id=?");
            $stmt->bind_param("ssdsssi", $name, $category, $price, $unit, $description, $image, $edit_id);
            $stmt->execute();
            $stmt->close();
            $msg = "Product updated successfully.";
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, category, price, unit, description, image) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssdsss", $name, $category, $price, $unit, $description, $image);
            $stmt->execute();
            $stmt->close();
            $msg = "Product added successfully.";
        }
    }
}

// ── Fetch product for editing ──
$edit_product = null;
if (isset($_GET['edit'])) {
    $eid  = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $edit_product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ── Fetch all products ──
$filter_cat = isset($_GET['cat']) ? $_GET['cat'] : 'all';
if ($filter_cat === 'fruit' || $filter_cat === 'vegetable') {
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? ORDER BY category, name");
    $stmt->bind_param("s", $filter_cat);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result   = $conn->query("SELECT * FROM products ORDER BY category, name");
    $products = $result->fetch_all(MYSQLI_ASSOC);
}

// Cart count
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
    <title>Manage Products – Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body { font-family: Arial, sans-serif; background: #f4f4f4; color: #333; min-height: 100vh; display: flex; flex-direction: column; }

        /* ── Header ── */
        header { background: #2ecc71; color: white; padding: 16px 30px; text-align: center; }
        header h1 { font-size: 22px; }
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
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: white; padding: 30px; text-align: center;
        }
        .page-hero h1 { font-size: 28px; }
        .page-hero p  { font-size: 13px; opacity: .9; margin-top: 6px; }

        /* ── Breadcrumb ── */
        .breadcrumb { padding: 10px 30px; font-size: 13px; color: #777; }
        .breadcrumb a { color: #27ae60; text-decoration: none; }

        /* ── Layout ── */
        .layout { display: flex; gap: 24px; padding: 20px 30px 50px; flex: 1; align-items: flex-start; flex-wrap: wrap; }

        /* ── Form Panel ── */
        .form-panel {
            background: white; border-radius: 14px;
            box-shadow: 0 3px 14px rgba(0,0,0,.09);
            padding: 28px; width: 360px; flex-shrink: 0;
            position: sticky; top: 20px;
        }
        .form-panel h2 { font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .form-panel h2 i { color: #e67e22; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; color: #555; margin-bottom: 5px; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%; padding: 10px 12px;
            border: 1px solid #ddd; border-radius: 8px;
            font-size: 14px; transition: border-color .2s, box-shadow .2s;
            background: white;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none; border-color: #e67e22;
            box-shadow: 0 0 0 3px rgba(230,126,34,.12);
        }
        .form-group textarea { resize: vertical; min-height: 70px; }

        /* Image preview */
        .img-preview {
            width: 80px; height: 80px; object-fit: cover;
            border-radius: 8px; border: 2px solid #eee;
            margin-top: 8px; display: none;
        }
        .img-preview.show { display: block; }

        .btn-submit {
            width: 100%; padding: 12px;
            background: #e67e22; color: white; border: none;
            border-radius: 8px; font-size: 15px; font-weight: bold;
            cursor: pointer; transition: background .2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover { background: #d35400; }
        .btn-cancel {
            width: 100%; padding: 10px; margin-top: 10px;
            background: #ecf0f1; color: #555; border: none;
            border-radius: 8px; font-size: 14px; font-weight: bold;
            cursor: pointer; transition: background .2s; text-align: center;
            text-decoration: none; display: block;
        }
        .btn-cancel:hover { background: #dfe6e9; }

        /* ── Product List Panel ── */
        .list-panel { flex: 1; min-width: 300px; }

        /* Stats bar */
        .stats-bar {
            display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 20px;
        }
        .stat-card {
            background: white; border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,.07);
            padding: 16px 22px; flex: 1; min-width: 120px;
            display: flex; align-items: center; gap: 14px;
        }
        .stat-card .stat-icon {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .stat-card .stat-icon.green  { background: #e8f8f0; color: #27ae60; }
        .stat-card .stat-icon.orange { background: #fef3e2; color: #e67e22; }
        .stat-card .stat-icon.blue   { background: #eaf4fd; color: #2980b9; }
        .stat-card .stat-num  { font-size: 24px; font-weight: bold; }
        .stat-card .stat-label{ font-size: 12px; color: #888; }

        /* Filter bar */
        .filter-bar {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 16px; flex-wrap: wrap;
        }
        .filter-bar a {
            padding: 7px 18px; border-radius: 20px; font-size: 13px;
            font-weight: bold; text-decoration: none; border: 2px solid #ddd;
            color: #555; transition: all .2s;
        }
        .filter-bar a:hover, .filter-bar a.active {
            background: #27ae60; border-color: #27ae60; color: white;
        }
        .filter-bar a.fruit-tab:hover, .filter-bar a.fruit-tab.active {
            background: #e67e22; border-color: #e67e22;
        }
        .filter-bar a.veg-tab:hover, .filter-bar a.veg-tab.active {
            background: #27ae60; border-color: #27ae60;
        }

        /* Alert */
        .alert {
            padding: 12px 16px; border-radius: 8px; font-size: 14px;
            margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
        }
        .alert-success { background: #e8f8f0; border-left: 4px solid #2ecc71; color: #1e8449; }
        .alert-error   { background: #fdecea; border-left: 4px solid #e74c3c; color: #c0392b; }

        /* Table */
        .table-wrap { overflow-x: auto; border-radius: 12px; box-shadow: 0 3px 14px rgba(0,0,0,.08); }
        table { width: 100%; border-collapse: collapse; background: white; }
        thead th {
            background: #27ae60; color: white; padding: 13px 14px;
            font-size: 13px; text-align: left; white-space: nowrap;
        }
        thead th:first-child { border-radius: 12px 0 0 0; }
        thead th:last-child  { border-radius: 0 12px 0 0; }
        tbody tr { border-bottom: 1px solid #f0f0f0; transition: background .15s; }
        tbody tr:hover { background: #f9fffe; }
        tbody td { padding: 12px 14px; font-size: 13px; vertical-align: middle; }

        .prod-img {
            width: 52px; height: 52px; object-fit: cover;
            border-radius: 8px; border: 1px solid #eee;
        }
        .prod-img-placeholder {
            width: 52px; height: 52px; background: #f0f0f0;
            border-radius: 8px; display: flex; align-items: center;
            justify-content: center; color: #ccc; font-size: 20px;
        }

        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 12px;
            font-size: 11px; font-weight: bold;
        }
        .badge-fruit { background: #fef3e2; color: #e67e22; }
        .badge-veg   { background: #e8f8f0; color: #27ae60; }

        .action-btns { display: flex; gap: 6px; }
        .btn-edit {
            padding: 6px 14px; background: #3498db; color: white;
            border: none; border-radius: 6px; font-size: 12px;
            font-weight: bold; cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; gap: 4px;
            transition: background .2s;
        }
        .btn-edit:hover { background: #2980b9; }
        .btn-delete {
            padding: 6px 14px; background: #e74c3c; color: white;
            border: none; border-radius: 6px; font-size: 12px;
            font-weight: bold; cursor: pointer; text-decoration: none;
            display: inline-flex; align-items: center; gap: 4px;
            transition: background .2s;
        }
        .btn-delete:hover { background: #c0392b; }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 50px 20px;
            background: white; border-radius: 12px;
            box-shadow: 0 3px 14px rgba(0,0,0,.08);
        }
        .empty-state i { font-size: 50px; color: #ddd; margin-bottom: 12px; }
        .empty-state p { color: #aaa; font-size: 15px; }

        /* Footer */
        footer { background: #2ecc71; color: white; text-align: center; padding: 14px; font-size: 13px; }

        @media (max-width: 768px) {
            .layout { padding: 14px; }
            .form-panel { width: 100%; position: static; }
        }
    </style>
</head>
<body>

<!-- Header -->
<header>
    <h1><i class="fa-solid fa-leaf"></i> Fresh Grocery Store</h1>
    <p>Admin Panel</p>
</header>

<!-- Navbar -->
<nav>
    <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
    <a href="product.php"><i class="fa-solid fa-store"></i> Products</a>
    <a href="manage_product.php"><i class="fa-solid fa-crown"></i> Manage Products</a>
    <a href="manage_offer.php"><i class="fa-solid fa-tag"></i> Manage Offers</a>
    <a href="manage_contact.php"><i class="fa-solid fa-envelope"></i> Messages</a>
    <div class="nav-icons">
        <a href="account.php">
            <i class="fa-solid fa-circle-user"></i>
            <?php echo htmlspecialchars($_SESSION['name']); ?>
        </a>
        <a href="logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
        <a href="manage_contact.php">
            <i class="fa-solid fa-envelope"></i> Messages
            <?php
            $mc = $conn->query("SELECT COUNT(*) AS c FROM contacts WHERE status='unread'");
            $mc_count = $mc ? $mc->fetch_assoc()['c'] : 0;
            if ($mc_count > 0): ?>
                <span class="cart-badge"><?php echo $mc_count; ?></span>
            <?php endif; ?>
        </a>
    </div>
</nav>

<!-- Page Hero -->
<div class="page-hero">
    <h1><i class="fa-solid fa-crown"></i> Manage Products</h1>
    <p>Add, edit, or remove products by category</p>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="index.php">Home</a> &rsaquo;
    <a href="product.php">Products</a> &rsaquo;
    <strong>Manage Products</strong>
</div>

<!-- Layout -->
<div class="layout">

    <!-- ── Left: Add / Edit Form ── -->
    <div class="form-panel">
        <h2>
            <i class="fa-solid <?php echo $edit_product ? 'fa-pen-to-square' : 'fa-plus-circle'; ?>"></i>
            <?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?>
        </h2>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="manage_product.php" enctype="multipart/form-data">
            <?php if ($edit_product): ?>
                <input type="hidden" name="edit_id" value="<?php echo $edit_product['id']; ?>">
                <input type="hidden" name="image_current" value="<?php echo htmlspecialchars($edit_product['image']); ?>">
            <?php else: ?>
                <input type="hidden" name="image_current" value="">
            <?php endif; ?>

            <div class="form-group">
                <label for="name"><i class="fa-solid fa-tag"></i> Product Name *</label>
                <input type="text" id="name" name="name" placeholder="e.g. Mango"
                       value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="category"><i class="fa-solid fa-layer-group"></i> Category *</label>
                <select id="category" name="category" required>
                    <option value="">-- Select Category --</option>
                    <option value="fruit"     <?php echo ($edit_product && $edit_product['category']==='fruit')     ? 'selected' : ''; ?>>🍎 Fruit</option>
                    <option value="vegetable" <?php echo ($edit_product && $edit_product['category']==='vegetable') ? 'selected' : ''; ?>>🥦 Vegetable</option>
                </select>
            </div>

            <div class="form-group">
                <label for="price"><i class="fa-solid fa-indian-rupee-sign"></i> Price (₹) *</label>
                <input type="number" id="price" name="price" placeholder="e.g. 120"
                       min="0" step="0.01"
                       value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="unit"><i class="fa-solid fa-weight-scale"></i> Unit *</label>
                <input type="text" id="unit" name="unit" placeholder="e.g. kg, dozen, piece"
                       value="<?php echo $edit_product ? htmlspecialchars($edit_product['unit']) : 'kg'; ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="description"><i class="fa-solid fa-align-left"></i> Description</label>
                <textarea id="description" name="description" placeholder="Short product description…"><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="image"><i class="fa-solid fa-image"></i> Product Image</label>
                <input type="file" id="image" name="image" accept="image/*" onchange="previewImg(this)">
                <?php if ($edit_product && $edit_product['image']): ?>
                    <img src="images/<?php echo htmlspecialchars($edit_product['image']); ?>"
                         class="img-preview show" id="imgPreview" alt="Current image">
                <?php else: ?>
                    <img src="" class="img-preview" id="imgPreview" alt="Preview">
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-<?php echo $edit_product ? 'floppy-disk' : 'plus'; ?>"></i>
                <?php echo $edit_product ? 'Update Product' : 'Add Product'; ?>
            </button>

            <?php if ($edit_product): ?>
            <a href="manage_product.php" class="btn-cancel">
                <i class="fa-solid fa-xmark"></i> Cancel Edit
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- ── Right: Product List ── -->
    <div class="list-panel">

        <?php if ($msg): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?php echo htmlspecialchars($msg); ?>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <?php
        $total_res  = $conn->query("SELECT COUNT(*) AS c FROM products");
        $total      = $total_res->fetch_assoc()['c'];
        $fruit_res  = $conn->query("SELECT COUNT(*) AS c FROM products WHERE category='fruit'");
        $fruit_cnt  = $fruit_res->fetch_assoc()['c'];
        $veg_res    = $conn->query("SELECT COUNT(*) AS c FROM products WHERE category='vegetable'");
        $veg_cnt    = $veg_res->fetch_assoc()['c'];
        // Unread messages count
        $contacts_tbl = $conn->query("SHOW TABLES LIKE 'contacts'");
        $unread_msgs  = 0;
        if ($contacts_tbl->num_rows > 0) {
            $unread_msgs = $conn->query("SELECT COUNT(*) AS c FROM contacts WHERE status='unread'")->fetch_assoc()['c'];
        }
        ?>
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div>
                    <div class="stat-num"><?php echo $total; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fa-solid fa-apple-whole"></i></div>
                <div>
                    <div class="stat-num"><?php echo $fruit_cnt; ?></div>
                    <div class="stat-label">Fruits</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-seedling"></i></div>
                <div>
                    <div class="stat-num"><?php echo $veg_cnt; ?></div>
                    <div class="stat-label">Vegetables</div>
                </div>
            </div>
            <!-- Messages shortcut card -->
            <a href="manage_contact.php" style="text-decoration:none;flex:1;min-width:120px">
                <div class="stat-card" style="cursor:pointer;border:2px solid <?php echo $unread_msgs > 0 ? '#e74c3c' : '#eee'; ?>">
                    <div class="stat-icon" style="background:#fdecea;color:#e74c3c"><i class="fa-solid fa-envelope"></i></div>
                    <div>
                        <div class="stat-num" style="color:<?php echo $unread_msgs > 0 ? '#e74c3c' : '#333'; ?>"><?php echo $unread_msgs; ?></div>
                        <div class="stat-label">Unread Messages</div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Category filter -->
        <div class="filter-bar">
            <a href="manage_product.php" class="<?php echo $filter_cat==='all' ? 'active' : ''; ?>">
                All
            </a>
            <a href="manage_product.php?cat=fruit" class="fruit-tab <?php echo $filter_cat==='fruit' ? 'active' : ''; ?>">
                🍎 Fruits
            </a>
            <a href="manage_product.php?cat=vegetable" class="veg-tab <?php echo $filter_cat==='vegetable' ? 'active' : ''; ?>">
                🥦 Vegetables
            </a>
        </div>

        <!-- Product table -->
        <?php if (empty($products)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-box-open"></i>
            <p>No products found. Add your first product using the form.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Unit</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $i => $p): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td>
                            <?php if ($p['image']): ?>
                                <img src="images/<?php echo htmlspecialchars($p['image']); ?>"
                                     class="prod-img" alt="<?php echo htmlspecialchars($p['name']); ?>">
                            <?php else: ?>
                                <div class="prod-img-placeholder">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                        <td>
                            <span class="badge badge-<?php echo $p['category']==='fruit' ? 'fruit' : 'veg'; ?>">
                                <?php echo $p['category']==='fruit' ? '🍎 Fruit' : '🥦 Vegetable'; ?>
                            </span>
                        </td>
                        <td>₹<?php echo number_format($p['price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($p['unit']); ?></td>
                        <td style="max-width:160px; color:#777;">
                            <?php echo htmlspecialchars(mb_strimwidth($p['description'], 0, 50, '…')); ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="manage_product.php?edit=<?php echo $p['id']; ?><?php echo $filter_cat!=='all' ? '&cat='.$filter_cat : ''; ?>"
                                   class="btn-edit">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </a>
                                <a href="manage_product.php?delete=<?php echo $p['id']; ?><?php echo $filter_cat!=='all' ? '&cat='.$filter_cat : ''; ?>"
                                   class="btn-delete"
                                   onclick="return confirm('Delete \'<?php echo addslashes($p['name']); ?>\'? This cannot be undone.')">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div><!-- /list-panel -->
</div><!-- /layout -->

<footer>
    <p>© 2026 Fresh Grocery Store | Admin Panel</p>
</footer>

<script>
function previewImg(input) {
    const preview = document.getElementById('imgPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.add('show');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>
