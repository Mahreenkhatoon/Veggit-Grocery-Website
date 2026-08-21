<?php
session_start();
include "db.php";

// security check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// fetch user data
$stmt = $conn->prepare("SELECT name, email, role FROM user WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile</title>

<style>
body {
    font-family: Arial;
    background: #f4f4f4;
    margin: 0;
}

.container {
    width: 420px;
    margin: 80px auto;
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    text-align: center;
}

h2 {
    color: #28a745;
}

.profile-box {
    text-align: left;
    margin-top: 20px;
}

.profile-box p {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.badge {
    display: inline-block;
    padding: 5px 10px;
    background: #28a745;
    color: white;
    border-radius: 5px;
    font-size: 12px;
}

button {
    width: 100%;
    padding: 10px;
    margin-top: 15px;
    border: none;
    background: #28a745;
    color: white;
    border-radius: 5px;
    cursor: pointer;
}

button:hover {
    background: #218838;
}
</style>
</head>

<body>

<div class="container">

    <h2>👤 My Profile</h2>

    <div class="profile-box">
        <p><b>Name:</b> <?php echo $user['name']; ?></p>
        <p><b>Email:</b> <?php echo $user['email']; ?></p>
    </div>

    <a href="edit_profile.php">
        <button>Edit Profile</button>
    </a>

    <a href="logout.php">
        <button style="background:red;">Logout</button>
    </a>

</div>

</body>
</html>