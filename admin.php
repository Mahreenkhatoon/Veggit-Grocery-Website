<?php
require_once "config.php";
include "db.php";

$name     = ADMIN_NAME;
$email    = ADMIN_EMAIL;
$password = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);
$role     = "admin";

$stmt = $conn->prepare("INSERT INTO user (name, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $password, $role);

if ($stmt->execute()) {
    echo "Admin created successfully!";
} else {
    echo "Error: Admin may already exist or DB error — " . $stmt->error;
}
?>
