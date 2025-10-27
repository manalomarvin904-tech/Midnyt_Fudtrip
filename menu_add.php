<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? 'Solo';
    $price = floatval($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($name === '' || $price <= 0) {
        flash('Please provide a valid name and price.','danger');
        header('Location: index.php#admin');
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO menu (name, category, price, description, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $category, $price, $description, null]);
    flash('Menu item added.');
    header('Location: index.php#admin');
    exit;
}
