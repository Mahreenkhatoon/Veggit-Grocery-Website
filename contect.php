<?php
session_start();
include "db.php";

// Create contacts table
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

$sent  = false;
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $rating  = (int)($_POST['rating'] ?? 0);

    if ($name && $email && $subject && $message) {
        $s = $conn->prepare("INSERT INTO contacts (name,email,phone,subject,message,rating) VALUES (?,?,?,?,?,?)");
        $s->bind_param("sssssi", $name, $email, $phone, $subject, $message, $rating);
        if ($s->execute()) {
            $sent = true;
        } else {
            $error = "Something went wrong. Please try again.";
        }
        $s->close();
    } else {
        $error = "Please fill in all required fields.";
    }
}

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
    <title>Contact Us – Fresh Grocery Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; color: #333; min-height: 100vh; display: flex; flex-direction: column; }
        header { background: #2ecc71; color: white; padding: 16px 30px; text-align: center; }
        header h1 { font-size: 24px; }
        header p  { font-size: 13px; opacity: .85; margin-top: 3px; }
        nav { background: #27ae60; padding: 10px 30px; display: flex; align-items: center; flex-wrap: wrap; gap: 6px; }
        nav > a { color: white; text-decoration: none; font-weight: bold; font-size: 14px; margin-right: 10px; }
        nav > a:hover { text-decoration: underline; }
        .nav-icons { display: flex; gap: 10px; margin-left: auto; align-items: center; }
        .nav-icons a { color: white; display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,.2); padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: bold; text-decoration: none; transition: background .2s; }
        .nav-icons a:hover { background: rgba(255,255,255,.35); }
        .nav-icons a.logout-btn { background: rgba(231,76,60,.5); }
        .nav-icons a.logout-btn:hover { background: rgba(231,76,60,.8); }
        .cart-badge { background: #e74c3c; color: white; border-radius: 50%; font-size: 11px; padding: 1px 6px; font-weight: bold; }
        .page-hero { background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; text-align: center; padding: 50px 20px; position: relative; overflow: hidden; }
        .page-hero::before { content: '📞'; font-size: 180px; opacity: .07; position: absolute; right: -20px; top: -20px; }
        .page-hero h1 { font-size: 34px; }
        .page-hero p  { font-size: 15px; opacity: .9; margin-top: 8px; }
        .breadcrumb { padding: 12px 30px; font-size: 13px; color: #777; }
        .breadcrumb a { color: #27ae60; text-decoration: none; }
        .contact-wrapper { display: flex; gap: 28px; flex-wrap: wrap; padding: 10px 30px 50px; flex: 1; align-items: flex-start; max-width: 1100px; margin: 0 auto; width: 100%; }
        .info-panel { width: 300px; min-width: 260px; display: flex; flex-direction: column; gap: 16px; }
        .info-card { background: white; border-radius: 14px; box-shadow: 0 3px 12px rgba(0,0,0,.08); overflow: hidden; }
        .info-card-header { background: #2ecc71; color: white; padding: 14px 18px; font-size: 15px; font-weight: bold; display: flex; align-items: center; gap: 8px; }
        .info-card-body { padding: 6px 0; }
        .info-item { display: flex; align-items: flex-start; gap: 14px; padding: 14px 18px; border-bottom: 1px solid #f5f5f5; }
        .info-item:last-child { border-bottom: none; }
        .info-icon { width: 40px; height: 40px; border-radius: 10px; background: #e8f8f0; color: #2ecc71; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
        .info-text strong { display: block; font-size: 13px; color: #333; margin-bottom: 2px; }
        .info-text span   { font-size: 13px; color: #777; }
        .info-text a      { color: #27ae60; text-decoration: none; font-size: 13px; }
        .hours-row { display: flex; justify-content: space-between; padding: 10px 18px; border-bottom: 1px solid #f5f5f5; font-size: 13px; }
        .hours-row:last-child { border-bottom: none; }
        .hours-row .day { color: #555; font-weight: bold; }
        .hours-row .time { color: #27ae60; font-weight: bold; }
        .hours-row .closed { color: #e74c3c; }
        .social-row { display: flex; gap: 10px; padding: 16px 18px; }
        .social-btn { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; color: white; text-decoration: none; transition: transform .2s; }
        .social-btn:hover { transform: translateY(-3px); }
        .social-btn.fb { background: #1877f2; }
        .social-btn.ig { background: linear-gradient(135deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888); }
        .social-btn.tw { background: #1da1f2; }
        .social-btn.wa { background: #25d366; }
        .form-panel { flex: 1; min-width: 300px; }
        .form-card { background: white; border-radius: 14px; box-shadow: 0 3px 12px rgba(0,0,0,.08); overflow: hidden; }
        .form-card-header { background: #f0fdf4; padding: 18px 24px; border-bottom: 1px solid #e8f8f0; font-size: 17px; font-weight: bold; color: #27ae60; display: flex; align-items: center; gap: 8px; }
        .form-card-body { padding: 24px; }
        .form-row { display: flex; gap: 16px; flex-wrap: wrap; }
        .form-group { margin-bottom: 18px; flex: 1; min-width: 200px; }
        .form-group.full { flex: 100%; min-width: 100%; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; color: #555; margin-bottom: 6px; }
        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 13px; }
        .input-wrap.textarea-wrap i { top: 14px; transform: none; }
        .input-wrap input, .input-wrap select, .input-wrap textarea { width: 100%; padding: 11px 12px 11px 36px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; font-family: Arial, sans-serif; transition: border-color .2s, box-shadow .2s; }
        .input-wrap input:focus, .input-wrap select:focus, .input-wrap textarea:focus { outline: none; border-color: #2ecc71; box-shadow: 0 0 0 3px rgba(46,204,113,.12); }
        .input-wrap textarea { resize: vertical; min-height: 120px; }
        .rating-group { margin-bottom: 18px; }
        .rating-group label { display: block; font-size: 13px; font-weight: bold; color: #555; margin-bottom: 8px; }
        .stars-input { display: flex; gap: 6px; }
        .stars-input i { font-size: 24px; color: #ddd; cursor: pointer; transition: color .15s; }
        .stars-input i.active { color: #f39c12; }
        .btn-submit { width: 100%; padding: 14px; background: #2ecc71; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background .2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-submit:hover { background: #27ae60; }
        .alert-error { background: #fdecea; border-left: 4px solid #e74c3c; color: #c0392b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .success-box { background: #e8f8f0; border: 1px solid #a8e6c3; border-radius: 12px; padding: 40px 20px; text-align: center; }
        .success-box i { font-size: 52px; color: #2ecc71; margin-bottom: 12px; display: block; }
        .success-box h3 { color: #27ae60; font-size: 22px; margin-bottom: 6px; }
        .success-box p  { font-size: 14px; color: #555; margin-bottom: 18px; }
        .success-box a  { display: inline-block; padding: 10px 24px; background: #2ecc71; color: white; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; }
        .faq-section { padding: 0 30px 50px; max-width: 1100px; margin: 0 auto; width: 100%; }
        .faq-title { font-size: 22px; font-weight: bold; margin-bottom: 16px; color: #222; }
        .faq-item { background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 10px; overflow: hidden; }
        .faq-q { padding: 16px 20px; font-size: 14px; font-weight: bold; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .faq-q i { color: #2ecc71; transition: transform .25s; }
        .faq-q.open i { transform: rotate(180deg); }
        .faq-a { display: none; padding: 0 20px 16px; font-size: 13px; color: #666; line-height: 1.6; }
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
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="account.php"><i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($_SESSION['name']); ?></a>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <?php else: ?>
            <a href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
            <a href="register.php"><i class="fa-solid fa-user-plus"></i> Register</a>
        <?php endif; ?>
        <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
        <a href="cart.php">
            <i class="fa-solid fa-cart-shopping"></i> Cart
            <?php if ($cart_count > 0): ?><span class="cart-badge"><?php echo $cart_count; ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
    </div>
</nav>

<div class="page-hero">
    <h1><i class="fa-solid fa-headset"></i> Contact Us</h1>
    <p>We're here to help — reach out anytime!</p>
</div>

<div class="breadcrumb">
    <a href="index.php">Home</a> &rsaquo; <strong>Contact Us</strong>
</div>

<div class="contact-wrapper">

    <!-- Info Panel -->
    <div class="info-panel">
        <div class="info-card">
            <div class="info-card-header"><i class="fa-solid fa-circle-info"></i> Get In Touch</div>
            <div class="info-card-body">
                <div class="info-item">
                    <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="info-text"><strong>Address</strong><span>123 Market Road, Mumbai, Maharashtra – 400001</span></div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
                    <div class="info-text"><strong>Phone</strong><a href="tel:+919876543210">+91 98765 43210</a></div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div class="info-text"><strong>Email</strong><a href="mailto:support@freshgrocery.com">support@freshgrocery.com</a></div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="fa-brands fa-whatsapp"></i></div>
                    <div class="info-text"><strong>WhatsApp</strong><a href="#">+91 98765 43210</a></div>
                </div>
            </div>
        </div>
        <div class="info-card">
            <div class="info-card-header"><i class="fa-solid fa-clock"></i> Store Hours</div>
            <div class="info-card-body">
                <div class="hours-row"><span class="day">Mon – Fri</span><span class="time">8:00 AM – 10:00 PM</span></div>
                <div class="hours-row"><span class="day">Saturday</span><span class="time">8:00 AM – 11:00 PM</span></div>
                <div class="hours-row"><span class="day">Sunday</span><span class="time">9:00 AM – 9:00 PM</span></div>
                <div class="hours-row"><span class="day">Public Holidays</span><span class="closed">Closed</span></div>
            </div>
        </div>
        <div class="info-card">
            <div class="info-card-header"><i class="fa-solid fa-share-nodes"></i> Follow Us</div>
            <div class="social-row">
                <a href="#" class="social-btn fb"><i class="fa-brands fa-facebook-f"></i></a>
                <a href="#" class="social-btn ig"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" class="social-btn tw"><i class="fa-brands fa-twitter"></i></a>
                <a href="#" class="social-btn wa"><i class="fa-brands fa-whatsapp"></i></a>
            </div>
        </div>
    </div>

    <!-- Form Panel -->
    <div class="form-panel">
        <div class="form-card">
            <div class="form-card-header"><i class="fa-solid fa-paper-plane"></i> Send Us a Message</div>
            <div class="form-card-body">

                <?php if ($sent): ?>
                <div class="success-box">
                    <i class="fa-solid fa-circle-check"></i>
                    <h3>Message Sent!</h3>
                    <p>Thank you for reaching out. We'll get back to you within 24 hours.</p>
                    <a href="index.php"><i class="fa-solid fa-house"></i> Back to Home</a>
                </div>

                <?php else: ?>

                <?php if ($error): ?>
                <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="contect.php">
                    <input type="hidden" name="rating" id="ratingInput" value="0">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-user"></i>
                                <input type="text" name="name" placeholder="Your full name"
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : (isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-envelope"></i>
                                <input type="email" name="email" placeholder="your@email.com"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-phone"></i>
                                <input type="tel" name="phone" placeholder="+91 00000 00000"
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Subject *</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-tag"></i>
                                <select name="subject" required>
                                    <option value="">Select a topic</option>
                                    <?php
                                    $subjects = ["Order Issue","Delivery Problem","Product Quality","Payment Issue","General Enquiry","Feedback","Complaint"];
                                    foreach ($subjects as $sub):
                                        $sel = (isset($_POST['subject']) && $_POST['subject']===$sub) ? 'selected' : '';
                                    ?>
                                    <option <?php echo $sel; ?>><?php echo $sub; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full">
                        <label>Message *</label>
                        <div class="input-wrap textarea-wrap">
                            <i class="fa-solid fa-comment"></i>
                            <textarea name="message" placeholder="Tell us how we can help you…" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>
                    </div>

                    <div class="rating-group">
                        <label>Rate Your Experience</label>
                        <div class="stars-input" id="starRating">
                            <i class="fa-regular fa-star" data-val="1"></i>
                            <i class="fa-regular fa-star" data-val="2"></i>
                            <i class="fa-regular fa-star" data-val="3"></i>
                            <i class="fa-regular fa-star" data-val="4"></i>
                            <i class="fa-regular fa-star" data-val="5"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-paper-plane"></i> Send Message
                    </button>
                </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- FAQ -->
<div class="faq-section">
    <div class="faq-title"><i class="fa-solid fa-circle-question" style="color:#2ecc71"></i> Frequently Asked Questions</div>
    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">What are your delivery timings? <i class="fa-solid fa-chevron-down"></i></div>
        <div class="faq-a">We deliver from 8:00 AM to 9:00 PM every day. Same-day delivery is available for orders placed before 12:00 PM.</div>
    </div>
    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Is there a minimum order value? <i class="fa-solid fa-chevron-down"></i></div>
        <div class="faq-a">No minimum order. Free delivery on orders above ₹500. A ₹40 delivery charge applies to smaller orders.</div>
    </div>
    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">How do I return a product? <i class="fa-solid fa-chevron-down"></i></div>
        <div class="faq-a">Contact us within 24 hours of receiving a damaged or incorrect product. We'll arrange a replacement or refund promptly.</div>
    </div>
    <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">How can I track my order? <i class="fa-solid fa-chevron-down"></i></div>
        <div class="faq-a">You can track your delivery status by calling our support line or via WhatsApp after placing your order.</div>
    </div>
</div>

<footer><p>© 2026 Fresh Grocery Store | All Rights Reserved</p></footer>

<script>
let selectedRating = 0;
const stars = document.querySelectorAll('#starRating i');

stars.forEach(s => {
    s.addEventListener('click', () => {
        selectedRating = +s.dataset.val;
        document.getElementById('ratingInput').value = selectedRating;
        stars.forEach((x, i) => {
            x.className = i < selectedRating ? 'fa-solid fa-star active' : 'fa-regular fa-star';
        });
    });
    s.addEventListener('mouseover', () => {
        const v = +s.dataset.val;
        stars.forEach((x, i) => { x.style.color = i < v ? '#f39c12' : '#ddd'; });
    });
    s.addEventListener('mouseout', () => {
        stars.forEach((x, i) => { x.style.color = i < selectedRating ? '#f39c12' : '#ddd'; });
    });
});

function toggleFaq(el) {
    const answer = el.nextElementSibling;
    const isOpen = el.classList.contains('open');
    document.querySelectorAll('.faq-q').forEach(q => { q.classList.remove('open'); q.nextElementSibling.style.display = 'none'; });
    if (!isOpen) { el.classList.add('open'); answer.style.display = 'block'; }
}
</script>
</body>
</html>
