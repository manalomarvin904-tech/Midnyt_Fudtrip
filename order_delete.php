<?php
require 'db.php';

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $pdo->prepare("DELETE FROM orders WHERE id=?")->execute([$id]);
    flash('Order removed.');
}

header('Location: index.php#orders');
exit;
