<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $status = $_POST['status'] === 'Completed' ? 'Completed' : 'Pending';
    $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status, $id]);
    flash('Order status updated.');
}

header('Location: index.php#orders');
exit;
