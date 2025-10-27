<?php
require 'db.php';

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $del = $pdo->prepare("DELETE FROM menu WHERE id=?");
    $del->execute([$id]);
    flash('Menu item deleted.');
}

header('Location: index.php#admin');
exit;
