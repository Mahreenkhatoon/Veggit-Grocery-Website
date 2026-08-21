<?php
session_start();

$id = $_GET['id'];

foreach ($_SESSION['cart'] as $key => $item) {
    if ($item['id'] == $id) {
        unset($_SESSION['cart'][$key]);
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        break;
    }
}

header("Location: cart.php");
exit;
?>