<?php
session_start();
include "db.php";

// Admin guard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Create offers table
$conn->query("CREATE TABLE IF NOT EXISTS offers (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    name     VARCHAR(100)  NOT NULL,
    category ENUM('Fruit','Vegetable') NOT NULL,
    image    VARCHAR(150)  NOT NULL DEFAULT '',
    description VARCHAR(255) NOT NULL DEFAULT '',
    price    DECIMAL(10,2) NOT NULL,
    old_price DECIMAL(10,2) NOT NULL,
    unit     VARCHAR(30)   NOT NULL DEFAULT 'kg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Seed default offers if empty
$cr = $conn->query("SELECT COUNT(*) AS c FROM offers");
if ($cr->fetch_assoc()['c'] == 0) {
    $defs = [
        ["Watermelon", "Fruit",     "images/watermelon.jpg", "Cool & refreshing summer fruit",   25,  40,  "kg"],
        ["Onion",      "Vegetable", "images/onion.jpg",      "Farm fresh onions",                28,  40,  "kg"],
        ["Potato",     "Vegetable", "images/pototo.jpg",     "Fresh farm potatoes",              22,  35,  "kg"],
        ["Tomato",     "Vegetable", "images/tomoto.jpg",     "Ripe juicy tomatoes",              30,  45,  "kg"],
        ["Carrot",     "Vegetable", "images/carrot.jpg",     "Crunchy fresh carrots",            40,  55,  "kg"],
        ["Beetroot",   "Vegetable", "images/beetroot.jpg",   "Earthy & nutritious beetroot",     35,  50,  "kg"],
        ["Apple",      "Fruit",     "images/apple.jpg",      "Fresh & juicy apples",             99,  130, "kg"],
        ["Mango",      "Fruit",     "images/mango.jpg",      "Sweet Alphonso mangoes",           120, 160, "kg"],
        ["Banana",     "Fruit",     "images/banana.jpg",     "Ripe & energy-packed bananas",     45,  60,  "dozen"],
        ["Grapes",     "Fruit",     "images/grapes.jpg",     "Seedless green grapes",            70,  95,  "kg"],
        ["Papaya",     "Fruit",     "images/papaya.jpg",     "Tropical ripe papaya",             40,  55,  "piece"],
        ["Pineapple",  "Fruit",     "images/pineapple.jpg",  "Sweet & tangy pineapple",          55,  75,  "piece"],
    ];
    $s = $conn->prepare("INSERT INTO offers (name,category,image,description,price,old_price,unit) VALUES (?,?,?,?,?,?,?)");
    foreach ($defs as $d) {
        $n = $d[0]; $cat = $d[1]; $img = $d[2]; $desc = $d[3];
        $p = (float)$d[4]; $op = (float)$d[5]; $u = $d[6];
        $s->bind_param("ssssdds", $n,$cat,$img,$desc,$p,$op,$u);
        $s->execute();
    }
    $s->close();
}

$msg = ""; $error = "";

// DELETE
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $s = $conn->prepare("DELETE FROM offers WHERE id=?");
    $s->bind_param("i", $did);
    $s->execute();
    $s->close();
    $msg = "Offer deleted.";
}

// ADD / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eid   = (int)($_POST['edit_id'] ?? 0);
    $name  = trim($_POST['name']);
    $cat   = $_POST['category'];
    $desc  = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $old   = (float)$_POST['old_price'];
    $unit  = trim($_POST['unit']);
    $image = trim($_POST['image_current']);

    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $fname = time() . '_' . preg_replace('/[^a-z0-9._-]/i','_',$_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], "images/$fname")) {
                $image = "images/$fname";
            } else { $error = "Image upload failed."; }
        } else { $error = "Invalid image type."; }
    }

    if (!$error) {
        if ($eid > 0) {
            $s = $conn->prepare("UPDATE offers SET name=?,category=?,image=?,description=?,price=?,old_price=?,unit=? WHERE id=?");
            $s->bind_param("ssssddsi", $name, $cat, $image, $desc, $price, $old, $unit, $eid);
            $s->execute();
            $s->close();
            $msg = "Offer updated successfully.";
        } else {
            $s = $conn->prepare("INSERT INTO offers (name,category,image,description,price,old_price,unit) VALUES (?,?,?,?,?,?,?)");
            $s->bind_param("ssssdds", $name, $cat, $image, $desc, $price, $old, $unit);
            $s->execute();
            $s->close();
            $msg = "Offer added successfully.";
        }
    }
}

// Fetch for edit
$edit = null;
if (isset($_GET['edit'])) {
    $eid_fetch = (int)$_GET['edit'];
    $s = $conn->prepare("SELECT * FROM offers WHERE id=?");
    $s->bind_param("i", $eid_fetch);
    $s->execute();
    $edit = $s->get_result()->fetch_assoc();
    $s->close();
}

// Fetch all
$filter = $_GET['cat'] ?? 'all';
if ($filter === 'Fruit' || $filter === 'Vegetable') {
    $s = $conn->prepare("SELECT * FROM offers WHERE category=? ORDER BY name");
    $s->bind_param("s", $filter);
    $s->execute();
    $offers = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    $s->close();
} else {
    $offers = $conn->query("SELECT * FROM offers ORDER BY category,name")->fetch_all(MYSQLI_ASSOC);
}

$cart_count = 0;
if (isset($_SESSION['cart'])) foreach ($_SESSION['cart'] as $i) $cart_count += (int)$i['qty'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Offers – Admin</title>
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
.page-hero{background:linear-gradient(135deg,#e74c3c,#f39c12);color:white;padding:30px;text-align:center}
.page-hero h1{font-size:28px}
.page-hero p{font-size:13px;opacity:.9;margin-top:6px}
.breadcrumb{padding:10px 30px;font-size:13px;color:#777}
.breadcrumb a{color:#27ae60;text-decoration:none}
.layout{display:flex;gap:24px;padding:20px 30px 50px;flex:1;align-items:flex-start;flex-wrap:wrap}
.form-panel{background:white;border-radius:14px;box-shadow:0 3px 14px rgba(0,0,0,.09);padding:28px;width:360px;flex-shrink:0;position:sticky;top:20px}
.form-panel h2{font-size:18px;margin-bottom:20px;display:flex;align-items:center;gap:8px}
.form-panel h2 i{color:#e74c3c}
.form-group{margin-bottom:15px}
.form-group label{display:block;font-size:13px;font-weight:bold;color:#555;margin-bottom:5px}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;background:white;transition:border-color .2s}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#e74c3c;box-shadow:0 0 0 3px rgba(231,76,60,.1)}
.form-group textarea{resize:vertical;min-height:65px}
.price-row-inputs{display:flex;gap:10px}
.price-row-inputs .form-group{flex:1}
.img-preview{width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid #eee;margin-top:8px;display:none}
.img-preview.show{display:block}
.btn-submit{width:100%;padding:12px;background:#e74c3c;color:white;border:none;border-radius:8px;font-size:15px;font-weight:bold;cursor:pointer;transition:background .2s;display:flex;align-items:center;justify-content:center;gap:8px}
.btn-submit:hover{background:#c0392b}
.btn-cancel{width:100%;padding:10px;margin-top:10px;background:#ecf0f1;color:#555;border:none;border-radius:8px;font-size:14px;font-weight:bold;cursor:pointer;text-align:center;text-decoration:none;display:block}
.btn-cancel:hover{background:#dfe6e9}
.list-panel{flex:1;min-width:300px}
.stats-bar{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:20px}
.stat-card{background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.07);padding:16px 22px;flex:1;min-width:120px;display:flex;align-items:center;gap:14px}
.stat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px}
.stat-icon.red{background:#fdecea;color:#e74c3c}
.stat-icon.orange{background:#fef3e2;color:#e67e22}
.stat-icon.green{background:#e8f8f0;color:#27ae60}
.stat-num{font-size:24px;font-weight:bold}
.stat-label{font-size:12px;color:#888}
.filter-bar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.filter-bar a{padding:7px 18px;border-radius:20px;font-size:13px;font-weight:bold;text-decoration:none;border:2px solid #ddd;color:#555;transition:all .2s}
.filter-bar a.active,.filter-bar a:hover{background:#e74c3c;border-color:#e74c3c;color:white}
.filter-bar a.veg.active,.filter-bar a.veg:hover{background:#27ae60;border-color:#27ae60}
.alert{padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.alert-success{background:#e8f8f0;border-left:4px solid #2ecc71;color:#1e8449}
.alert-error{background:#fdecea;border-left:4px solid #e74c3c;color:#c0392b}
.table-wrap{overflow-x:auto;border-radius:12px;box-shadow:0 3px 14px rgba(0,0,0,.08)}
table{width:100%;border-collapse:collapse;background:white}
thead th{background:#e74c3c;color:white;padding:13px 14px;font-size:13px;text-align:left;white-space:nowrap}
thead th:first-child{border-radius:12px 0 0 0}
thead th:last-child{border-radius:0 12px 0 0}
tbody tr{border-bottom:1px solid #f0f0f0;transition:background .15s}
tbody tr:hover{background:#fff8f8}
tbody td{padding:11px 14px;font-size:13px;vertical-align:middle}
.prod-img{width:50px;height:50px;object-fit:cover;border-radius:8px;border:1px solid #eee}
.badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:bold}
.badge-fruit{background:#fef3e2;color:#e67e22}
.badge-veg{background:#e8f8f0;color:#27ae60}
.disc-badge{background:#fdecea;color:#e74c3c;font-weight:bold;padding:3px 8px;border-radius:8px;font-size:12px}
.action-btns{display:flex;gap:6px}
.btn-edit{padding:6px 14px;background:#3498db;color:white;border:none;border-radius:6px;font-size:12px;font-weight:bold;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:background .2s}
.btn-edit:hover{background:#2980b9}
.btn-delete{padding:6px 14px;background:#e74c3c;color:white;border:none;border-radius:6px;font-size:12px;font-weight:bold;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:background .2s}
.btn-delete:hover{background:#c0392b}
.empty-state{text-align:center;padding:50px 20px;background:white;border-radius:12px;box-shadow:0 3px 14px rgba(0,0,0,.08)}
.empty-state i{font-size:50px;color:#ddd;margin-bottom:12px}
.empty-state p{color:#aaa;font-size:15px}
footer{background:#2ecc71;color:white;text-align:center;padding:14px;font-size:13px}
@media(max-width:768px){.layout{padding:14px}.form-panel{width:100%;position:static}}
</style>
</head>
<body>

<header>
    <h1><i class="fa-solid fa-leaf"></i> Fresh Grocery Store</h1>
    <p>Admin Panel</p>
</header>

<nav>
    <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
    <a href="product.php"><i class="fa-solid fa-store"></i> Products</a>
    <a href="manage_product.php"><i class="fa-solid fa-boxes-stacked"></i> Manage Products</a>
    <a href="manage_offer.php"><i class="fa-solid fa-tag"></i> Manage Offers</a>
    <a href="manage_contact.php"><i class="fa-solid fa-envelope"></i> Messages</a>
    <div class="nav-icons">
        <a href="account.php"><i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($_SESSION['name']); ?></a>
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
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

<div class="page-hero">
    <h1><i class="fa-solid fa-tag"></i> Manage Offers</h1>
    <p>Add, edit or remove special discount offers</p>
</div>

<div class="breadcrumb">
    <a href="index.php">Home</a> &rsaquo;
    <a href="offer.php">Offers</a> &rsaquo;
    <strong>Manage Offers</strong>
</div>

<div class="layout">

    <!-- Form -->
    <div class="form-panel">
        <h2>
            <i class="fa-solid <?php echo $edit ? 'fa-pen-to-square' : 'fa-plus-circle'; ?>"></i>
            <?php echo $edit ? 'Edit Offer' : 'Add New Offer'; ?>
        </h2>

        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?php if ($edit): ?>
                <input type="hidden" name="edit_id" value="<?php echo $edit['id']; ?>">
                <input type="hidden" name="image_current" value="<?php echo htmlspecialchars($edit['image']); ?>">
            <?php else: ?>
                <input type="hidden" name="image_current" value="">
            <?php endif; ?>

            <div class="form-group">
                <label><i class="fa-solid fa-tag"></i> Product Name *</label>
                <input type="text" name="name" placeholder="e.g. Mango"
                       value="<?php echo $edit ? htmlspecialchars($edit['name']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-layer-group"></i> Category *</label>
                <select name="category" required>
                    <option value="">-- Select --</option>
                    <option value="Fruit"     <?php echo ($edit && $edit['category']==='Fruit')     ? 'selected' : ''; ?>>🍎 Fruit</option>
                    <option value="Vegetable" <?php echo ($edit && $edit['category']==='Vegetable') ? 'selected' : ''; ?>>🥦 Vegetable</option>
                </select>
            </div>

            <div class="price-row-inputs">
                <div class="form-group">
                    <label><i class="fa-solid fa-indian-rupee-sign"></i> Offer Price *</label>
                    <input type="number" name="price" placeholder="e.g. 99" min="0" step="0.01"
                           value="<?php echo $edit ? $edit['price'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-indian-rupee-sign"></i> Original Price *</label>
                    <input type="number" name="old_price" placeholder="e.g. 130" min="0" step="0.01"
                           value="<?php echo $edit ? $edit['old_price'] : ''; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-weight-scale"></i> Unit *</label>
                <input type="text" name="unit" placeholder="e.g. kg, piece, dozen"
                       value="<?php echo $edit ? htmlspecialchars($edit['unit']) : 'kg'; ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-align-left"></i> Description</label>
                <textarea name="description" placeholder="Short description…"><?php echo $edit ? htmlspecialchars($edit['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-image"></i> Image</label>
                <input type="file" name="image" accept="image/*" onchange="previewImg(this)">
                <?php if ($edit && $edit['image']): ?>
                    <img src="<?php echo htmlspecialchars($edit['image']); ?>" class="img-preview show" id="imgPreview">
                <?php else: ?>
                    <img src="" class="img-preview" id="imgPreview">
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-<?php echo $edit ? 'floppy-disk' : 'plus'; ?>"></i>
                <?php echo $edit ? 'Update Offer' : 'Add Offer'; ?>
            </button>
            <?php if ($edit): ?>
            <a href="manage_offer.php" class="btn-cancel"><i class="fa-solid fa-xmark"></i> Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- List -->
    <div class="list-panel">

        <?php if ($msg): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <?php
        $total_o = $conn->query("SELECT COUNT(*) AS c FROM offers")->fetch_assoc()['c'];
        $fruit_o = $conn->query("SELECT COUNT(*) AS c FROM offers WHERE category='Fruit'")->fetch_assoc()['c'];
        $veg_o   = $conn->query("SELECT COUNT(*) AS c FROM offers WHERE category='Vegetable'")->fetch_assoc()['c'];
        ?>
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fa-solid fa-fire"></i></div>
                <div><div class="stat-num"><?php echo $total_o; ?></div><div class="stat-label">Total Offers</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fa-solid fa-apple-whole"></i></div>
                <div><div class="stat-num"><?php echo $fruit_o; ?></div><div class="stat-label">Fruit Offers</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-seedling"></i></div>
                <div><div class="stat-num"><?php echo $veg_o; ?></div><div class="stat-label">Vegetable Offers</div></div>
            </div>
        </div>

        <div class="filter-bar">
            <a href="manage_offer.php"              class="<?php echo $filter==='all'       ? 'active' : ''; ?>">All</a>
            <a href="manage_offer.php?cat=Fruit"    class="<?php echo $filter==='Fruit'     ? 'active' : ''; ?>">🍎 Fruits</a>
            <a href="manage_offer.php?cat=Vegetable" class="veg <?php echo $filter==='Vegetable' ? 'active' : ''; ?>">🥦 Vegetables</a>
        </div>

        <?php if (empty($offers)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-tag"></i>
            <p>No offers found. Add your first offer using the form.</p>
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
                        <th>Offer Price</th>
                        <th>Original</th>
                        <th>Discount</th>
                        <th>Unit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($offers as $i => $o):
                    $disc = round((($o['old_price'] - $o['price']) / $o['old_price']) * 100);
                ?>
                <tr>
                    <td><?php echo $i+1; ?></td>
                    <td>
                        <?php if ($o['image']): ?>
                            <img src="<?php echo htmlspecialchars($o['image']); ?>" class="prod-img">
                        <?php else: ?>
                            <div style="width:50px;height:50px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#ccc"><i class="fa-solid fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo htmlspecialchars($o['name']); ?></strong></td>
                    <td>
                        <span class="badge <?php echo $o['category']==='Fruit' ? 'badge-fruit' : 'badge-veg'; ?>">
                            <?php echo $o['category']==='Fruit' ? '🍎 Fruit' : '🥦 Vegetable'; ?>
                        </span>
                    </td>
                    <td style="color:#e74c3c;font-weight:bold">₹<?php echo number_format($o['price'],2); ?></td>
                    <td style="text-decoration:line-through;color:#aaa">₹<?php echo number_format($o['old_price'],2); ?></td>
                    <td><span class="disc-badge">-<?php echo $disc; ?>%</span></td>
                    <td><?php echo htmlspecialchars($o['unit']); ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="manage_offer.php?edit=<?php echo $o['id']; ?>" class="btn-edit">
                                <i class="fa-solid fa-pen"></i> Edit
                            </a>
                            <a href="manage_offer.php?delete=<?php echo $o['id']; ?>"
                               class="btn-delete"
                               onclick="return confirm('Delete \'<?php echo addslashes($o['name']); ?>\'?')">
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

    </div>
</div>

<footer><p>© 2026 Fresh Grocery Store | Admin Panel</p></footer>

<script>
function previewImg(input) {
    const preview = document.getElementById('imgPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.classList.add('show'); };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
