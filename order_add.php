<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? 'Walk-in');
    $menu_id = intval($_POST['menu_id']);
    $quantity = max(1, intval($_POST['quantity'] ?? 1));

    $stmt = $pdo->prepare("SELECT price FROM menu WHERE id=?");
    $stmt->execute([$menu_id]);
    $price = $stmt->fetchColumn();

    if ($price === false) {
        flash('Menu not found.','danger');
    } else {
        $total = $price * $quantity;
        $pdo->prepare("INSERT INTO orders (customer_name, menu_id, quantity, total_price) VALUES (?, ?, ?, ?)")
            ->execute([$customer_name, $menu_id, $quantity, $total]);
        flash('Order placed successfully.');
    }

    header('Location: index.php');
    exit;
}
