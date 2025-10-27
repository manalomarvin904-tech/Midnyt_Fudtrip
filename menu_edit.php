<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);

    $stmt = $pdo->prepare("UPDATE menu SET name=?, category=?, price=?, description=? WHERE id=?");
    $stmt->execute([$name, $category, $price, $description, $id]);

    flash('Menu updated successfully.');
    header('Location: index.php#admin');
    exit;
}
