<?php
session_start();
include "db.php";

// Already logged in → go home
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error   = "";
$success = "";

if (isset($_POST['register'])) {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role     = "user";

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $check = $conn->prepare("SELECT user_id FROM user WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $conn->prepare("INSERT INTO user (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed, $role);

            if ($stmt->execute()) {
                $success = "Account created successfully! You can now log in.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – Fresh Grocery Store</title>
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

        .topbar {
            background: #2ecc71;
            color: white;
            text-align: center;
            padding: 14px;
            font-size: 20px;
            font-weight: bold;
        }
        .topbar a { color: white; text-decoration: none; }
        .topbar span { font-size: 13px; font-weight: normal; opacity: .85; display: block; margin-top: 2px; }

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
            max-width: 440px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,.12);
            overflow: hidden;
        }

        .card-header {
            background: #2ecc71;
            color: white;
            text-align: center;
            padding: 28px 20px 22px;
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
        .alert-success {
            background: #e8f8f0;
            border-left: 4px solid #2ecc71;
            color: #1a7a45;
            padding: 14px 16px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 18px;
            text-align: center;
        }
        .alert-success i { font-size: 28px; display: block; margin-bottom: 8px; }

        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #555;
            margin-bottom: 6px;
        }
        .input-wrap { position: relative; }
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

        .btn-register {
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
        .btn-register:hover { background: #27ae60; }

        .btn-login-link {
            display: block;
            width: 100%;
            padding: 12px;
            background: white;
            color: #2ecc71;
            border: 2px solid #2ecc71;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            margin-top: 10px;
            transition: all .2s;
        }
        .btn-login-link:hover { background: #2ecc71; color: white; }

        .divider {
            text-align: center;
            color: #bbb;
            font-size: 13px;
            margin: 18px 0;
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

        .login-link {
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .login-link a {
            color: #2ecc71;
            font-weight: bold;
            text-decoration: none;
        }
        .login-link a:hover { text-decoration: underline; }

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
            <div class="icon"><i class="fa-solid fa-user-plus"></i></div>
            <h2>Create Account</h2>
            <p>Join us and shop fresh every day</p>
        </div>

        <div class="card-body">

            <?php if ($error): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <a href="login.php" class="btn-login-link">
                <i class="fa-solid fa-right-to-bracket"></i> Go to Login
            </a>
            <div class="back-home">
                <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
            </div>

            <?php else: ?>

            <form method="POST" action="register.php">

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" id="name" name="name"
                               placeholder="Enter your full name"
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                               required>
                    </div>
                </div>

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
                               placeholder="Min. 6 characters" required>
                        <i class="fa-solid fa-eye toggle-pw" onclick="togglePw('password', this)" title="Show/Hide password"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password"
                               placeholder="Re-enter your password" required>
                        <i class="fa-solid fa-eye toggle-pw" onclick="togglePw('confirm_password', this)" title="Show/Hide password"></i>
                    </div>
                </div>

                <button type="submit" name="register" class="btn-register">
                    <i class="fa-solid fa-user-plus"></i> Create Account
                </button>

            </form>

            <div class="divider">or</div>

            <div class="login-link">
                Already have an account? <a href="login.php">Sign in</a>
            </div>

            <div class="back-home">
                <a href="index.php"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
            </div>

            <?php endif; ?>

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
