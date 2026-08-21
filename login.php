<?php
session_start();
include "db.php";

// Already logged in → go home
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$info  = "";

if (isset($_GET['msg']) && $_GET['msg'] === 'login_required') {
    $info = "Please log in or register to add items to your cart.";
}

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, name, email, password, role FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            header("Location: index.php");
            exit();
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "No account found with that email.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Fresh Grocery Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #e8f8f0 0%, #d4f1e4 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Top bar ── */
        .topbar {
            background: #2ecc71;
            color: white;
            text-align: center;
            padding: 14px;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .topbar a { color: white; text-decoration: none; }
        .topbar span { font-size: 13px; font-weight: normal; opacity: .85; display: block; margin-top: 2px; }

        /* ── Card ── */
        .wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
        }

        .card {
            background: white;
            width: 100%;
            max-width: 420px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,.12);
            overflow: hidden;
        }

        .card-header {
            background: #2ecc71;
            color: white;
            text-align: center;
            padding: 30px 20px 24px;
        }
        .card-header .icon {
            width: 64px; height: 64px;
            background: rgba(255,255,255,.25);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px;
            font-size: 28px;
        }
        .card-header h2 { font-size: 22px; }
        .card-header p  { font-size: 13px; opacity: .85; margin-top: 4px; }

        .card-body { padding: 28px 30px 30px; }

        /* Error */
        .alert-error {
            background: #fdecea;
            border-left: 4px solid #e74c3c;
            color: #c0392b;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-info {
            background: #e8f4fd;
            border-left: 4px solid #3498db;
            color: #1a5276;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Form */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #555;
            margin-bottom: 6px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 14px;
        }
        .input-wrap input {
            width: 100%;
            padding: 11px 12px 11px 36px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color .2s, box-shadow .2s;
        }
        .input-wrap input:focus {
            outline: none;
            border-color: #2ecc71;
            box-shadow: 0 0 0 3px rgba(46,204,113,.15);
        }

        .forgot { text-align: right; font-size: 12px; margin-top: 6px; }
        .forgot a { color: #27ae60; text-decoration: none; }
        .forgot a:hover { text-decoration: underline; }

        /* Eye toggle */
        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            cursor: pointer;
            font-size: 14px;
            transition: color .2s;
        }
        .toggle-pw:hover { color: #27ae60; }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background .2s;
            margin-top: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-login:hover { background: #27ae60; }

        .divider {
            text-align: center;
            color: #bbb;
            font-size: 13px;
            margin: 20px 0;
            position: relative;
        }
        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background: #eee;
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        .register-link {
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .register-link a {
            color: #2ecc71;
            font-weight: bold;
            text-decoration: none;
        }
        .register-link a:hover { text-decoration: underline; }

        .back-home {
            text-align: center;
            margin-top: 14px;
            font-size: 13px;
        }
        .back-home a {
            color: #888;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .back-home a:hover { color: #27ae60; }

        /* Footer */
        footer {
            background: #2ecc71;
            color: white;
            text-align: center;
            padding: 12px;
            font-size: 13px;
        }
    </style>
</head>
<body>

<div class="topbar">
    <a href="index.php"><i class="fa-solid fa-leaf"></i> Fresh Grocery Store</a>
    <span>Your daily needs delivered fresh!</span>
</div>

<div class="wrapper">
    <div class="card">

        <div class="card-header">
            <div class="icon"><i class="fa-solid fa-user"></i></div>
            <h2>Welcome Back!</h2>
            <p>Sign in to your account to continue</p>
        </div>

        <div class="card-body">

            <?php if ($error): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($info): ?>
            <div class="alert-info">
                <i class="fa-solid fa-circle-info"></i>
                <?php echo htmlspecialchars($info); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="email" name="email"
                               placeholder="Enter your email"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password"
                               placeholder="Enter your password" required>
                        <i class="fa-solid fa-eye toggle-pw" onclick="togglePw('password', this)" title="Show/Hide password"></i>
                    </div>
                    <div class="forgot"><a href="#">Forgot password?</a></div>
                </div>

                <button type="submit" name="login" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In
                </button>

            </form>

            <div class="divider">or</div>

            <div class="register-link">
                Don't have an account? <a href="register.php">Create one</a>
            </div>

            <div class="back-home">
                <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
            </div>

        </div>
    </div>
</div>

<footer>
    <p>© 2026 Fresh Grocery Store | All Rights Reserved</p>
</footer>

<script>
function togglePw(fieldId, icon) {
    const input = document.getElementById(fieldId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash toggle-pw';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye toggle-pw';
    }
}
</script>
</body>
</html>
