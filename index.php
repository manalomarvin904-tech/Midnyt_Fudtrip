<?php
// -------------------- CONFIG --------------------
$db_host = 'localhost';
$db_name = 'midnyt_fudtrip_db';
$db_user = 'root';
$db_pass = '';

$upload_dir = __DIR__ . '/uploads';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

// -------------------- DB CONNECTION --------------------
try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("DB error: " . $e->getMessage());
}

// -------------------- HELPERS --------------------
session_start();
function flash($msg, $type='success') {
    $_SESSION['flash'] = ['msg'=>$msg,'type'=>$type];
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES); }

// -------------------- MANUAL IMAGE MAP --------------------
$manual_images = [
    'Liemposilog'=>'liemposilog.jpg','Tapsilog'=>'tapsilog.jpg','Porksilog'=>'porksilog.jpg',
    'Bulaklaksilog'=>'bulaklaksilog.jpg','Hungariansilog'=>'hungariansilog.jpg',
    'Shanghaisilog'=>'shanghaisilog.jpg','Chicksilog'=>'chicksilog.jpg','Liemshalak'=>'liemshalak.jpg',
    'Porkshalak'=>'porkshalak.jpg','Tapshalak'=>'tapshalak.jpg','Chickshalak'=>'chickshalak.jpg'
];

// -------------------- ORDER HANDLERS --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
   if ($_POST['action'] === 'place_order') {
    $customer_name = trim($_POST['customer_name'] ?? 'Walk-in');
    $address = trim($_POST['address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    $menu_id = intval($_POST['menu_id']);
    $quantity = max(1, intval($_POST['quantity'] ?? 1));

    // ✅ Fetch menu details with status
    $stmt = $pdo->prepare("SELECT name, price, status FROM menu WHERE id=?");
    $stmt->execute([$menu_id]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$menu) {
        flash('Invalid menu item.','danger');
    } elseif ($menu['status'] === 'Sold Out') {
        // 🛑 Prevent order if sold out
        flash('Sorry, ' . e($menu['name']) . ' is currently sold out.','danger');
    } elseif ($customer_name === '' || $address === '' || $payment_method === '') {
        flash('Please complete all order fields.','danger');
    } else {
        $total = $menu['price'] * $quantity;
        $pdo->prepare("INSERT INTO orders (customer_name,address,payment_method,menu_id,quantity,total_price) VALUES (?,?,?,?,?,?)")
            ->execute([$customer_name,$address,$payment_method,$menu_id,$quantity,$total]);
        flash('Order placed successfully!');
    }
    header('Location: index.php');
    exit;
}


    if ($_POST['action'] === 'send_chat') {
        $customer_name = $_SESSION['customer_name'] ?? 'Guest';
        $message = trim($_POST['chat_message'] ?? '');
        if ($message !== '') {
            $pdo->prepare("INSERT INTO chat_messages (customer_name,message) VALUES (?,?)")
                ->execute([$customer_name,$message]);
            flash('Message sent!');
        } else {
            flash('Message cannot be empty.','danger');
        }
        header('Location: index.php#chat-container');
        exit;
    }

    if ($_POST['action'] === 'update_order_status') {
        $id = intval($_POST['id']);
        $status = $_POST['status'] === 'Completed' ? 'Completed' : 'Pending';
        $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status,$id]);
        flash('Order status updated.');
        header('Location: index.php');
        exit;
    }

    if ($_POST['action'] === 'delete_order') {
        $id = intval($_POST['id']);
        $pdo->prepare("DELETE FROM orders WHERE id=?")->execute([$id]);
        flash('Order deleted.');
        header('Location: index.php');
        exit;
    }
}

// -------------------- FETCH DATA --------------------
$menu_items = $pdo->query("SELECT * FROM menu ORDER BY FIELD(category,'Solo','Combo','Platter'), name")->fetchAll(PDO::FETCH_ASSOC);
$orders = $pdo->query("SELECT o.*, m.name as menu_name FROM orders o JOIN menu m ON o.menu_id = m.id ORDER BY o.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$totals = $pdo->query("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_price),0) as total_revenue FROM orders")->fetch(PDO::FETCH_ASSOC);
$chat_messages = $pdo->query("SELECT * FROM chat_messages ORDER BY created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Midnyt Fudtrip — Premium Silog Meals</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --primary: #1e3a8a; /* Deep blue for professionalism */
    --secondary: #10b981; /* Green for freshness */
    --accent: #f59e0b; /* Warm accent */
    --light-bg: #f8fafc;
    --dark-text: #1f2937;
    --shadow: 0 4px 12px rgba(0,0,0,0.1);
    --transition: all 0.3s ease;
}
body {
    font-family: 'Inter', sans-serif;
    background: var(--light-bg);
    color: var(--dark-text);
    line-height: 1.6;
}
.header {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    padding: 2rem 0;
    border-radius: 0 0 20px 20px;
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
}
.logo { display: flex; align-items: center; gap: 1rem; }
.logo img { width: 70px; height: 70px; border-radius: 50%; border: 3px solid white; }
.brand { font-weight: 700; font-size: 1.5rem; }
.card-pro {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow);
    border: none;
    overflow: hidden;
}
.menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}
.food-card {
    border-radius: 16px;
    overflow: hidden;
    background: white;
    transition: var(--transition);
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.food-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
.food-thumb {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: var(--transition);
}
.food-thumb:hover { transform: scale(1.05); }
.food-body { padding: 1.5rem; }
.food-title { font-weight: 600; font-size: 1.2rem; }
.price { font-weight: 700; color: var(--primary); font-size: 1.1rem; }
.cat-badge {
    background: var(--secondary);
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
}
.btn-primary { background: var(--primary); border: none; }
.btn-primary:hover { background: #1d4ed8; }
.footer {
    background: white;
    border-top: 1px solid #e5e7eb;
    padding: 1rem;
    text-align: center;
    box-shadow: 0 -2px 8px rgba(0,0,0,0.05);
}
/* Chatbox */
#chat-container { position: fixed; bottom: 20px; right: 20px; z-index: 1000; }
#chat-button {
    width: 60px; height: 60px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; box-shadow: var(--shadow);
    transition: var(--transition);
}
#chat-button:hover { transform: scale(1.1); }
#chat-box {
    display: none; flex-direction: column;
    width: 350px; height: 450px;
    background: white; border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    overflow: hidden;
}
#chat-header {
    background: var(--primary); color: white;
    padding: 1rem; font-weight: 600;
    display: flex; justify-content: space-between; align-items: center;
}
#chat-messages {
    flex: 1; padding: 1rem; overflow-y: auto;
    background: var(--light-bg);
}
.chat-msg { margin-bottom: 0.5rem; padding: 0.5rem; border-radius: 8px; background: white; }
.chat-reply { margin-left: 1rem; color: var(--secondary); font-weight: 500; }
.search-bar { margin-bottom: 1.5rem; }
</style>
</head>
<body>
<div class="container my-4">
    <div class="header text-center">
        <div class="logo justify-content-center">
            <img src="midnyt.jpg" alt="Midnyt Fudtrip Logo">
            <div>
                <div class="brand">Midnyt Fudtrip</div>
                <div>Premium Silog Meals — Fresh & Fast</div>
            </div>
        </div>
        <a href="admin.php" class="btn btn-light mt-3"><i class="fa fa-cog"></i> Admin Access</a>
    </div>

    <?php if($flash): ?>
        <script>
        Swal.fire({icon:'<?= $flash['type']=='success'?'success':'error' ?>',title:'<?= e($flash['msg']) ?>',toast:true,position:'top-end',timer:3000,showConfirmButton:false});
        </script>
    <?php endif; ?>

    <!-- Menu Section -->
    <div class="card-pro p-4 mb-4">
        <h4 class="mb-3"><i class="fa fa-utensils"></i> Our Menu</h4>
        <div class="search-bar">
            <input type="text" id="menu-search" class="form-control" placeholder="Search menu items...">
        </div>
        <div class="menu-grid" id="menu-grid">
            <?php foreach($menu_items as $m): 
    $img_file = $manual_images[$m['name']] ?? null;
    $img_src = ($img_file && file_exists($upload_dir.'/'.$img_file)) ? 'uploads/'.$img_file : 'https://via.placeholder.com/400x300?text=No+Image';
    $is_sold_out = ($m['status'] ?? 'Available') === 'Sold Out';
?>
<div class="food-card" data-name="<?= strtolower(e($m['name'])) ?>">
    <img src="<?= $img_src ?>" class="food-thumb" loading="lazy" alt="<?= e($m['name']) ?>">
    <div class="food-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="food-title"><?= e($m['name']) ?></div>
                <small class="text-muted"><?= e($m['description']) ?></small>
            </div>
            <div class="text-end">
                <div class="price">₱<?= number_format($m['price'],2) ?></div>
                <div class="cat-badge mt-1"><?= e($m['category']) ?></div>
            </div>
        </div>

        <?php if($is_sold_out): ?>
            <button class="btn btn-secondary w-100 mt-3" disabled>
                <i class="fa fa-ban"></i> Sold Out
            </button>
        <?php else: ?>
            <form method="post" class="mt-3">
                <input type="hidden" name="action" value="place_order">
                <input type="hidden" name="menu_id" value="<?= $m['id'] ?>">
                <div class="row g-2">
                    <div class="col-3"><input type="number" name="quantity" value="1" min="1" class="form-control" required></div>
                    <div class="col-9"><input type="text" name="customer_name" class="form-control" placeholder="Your Name" required></div>
                </div>
                <input type="text" name="address" class="form-control mt-2" placeholder="Delivery Address" required>
                <select name="payment_method" class="form-select mt-2" required>
                    <option value="">Select Payment</option>
                    <option value="Cash">Cash</option>
                    <option value="GCash">GCash</option>
                </select>
                <button class="btn btn-primary w-100 mt-3"><i class="fa fa-shopping-cart"></i> Order Now</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

        </div>
    </div>

    <!-- Orders Section -->
    <div class="card-pro p-4 mb-4">
        <h4><i class="fa fa-list"></i> Recent Orders</h4>
        <div class="table-responsive mt-3">
            <table class="table table-hover">
                <thead class="table-dark"><tr><th>Customer</th><th>Item</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($orders as $o): ?>
                    <tr>
                        <td><?= e($o['customer_name']) ?></td>
                        <td><?= e($o['menu_name']) ?></td>
                        <td><?= e($o['quantity']) ?></td>
                        <td>₱<?= number_format($o['total_price'],2) ?></td>
                        <td><span class="badge bg-<?= $o['status']=='Completed'?'success':'warning' ?>"><?= e($o['status']) ?></span></td>
                        <td><?= e($o['created_at']) ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="update_order_status">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option <?= $o['status']=='Pending'?'selected':'' ?>>Pending</option>
                                    <option <?= $o['status']=='Completed'?'selected':'' ?>>Completed</option>
                                </select>
                            </form>
                            <form method="post" class="d-inline ms-2" onsubmit="return confirm('Delete this order?')">
                                <input type="hidden" name="action" value="delete_order">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <button class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chatbox -->
    <div id="chat-container">
        <div id="chat-button" title="Chat with us"><i class="fa fa-comments"></i></div>
        <div id="chat-box">
            <div id="chat-header">Live Chat <span id="chat-close">&times;</span></div>
            <div id="chat-messages">
                <?php foreach(array_reverse($chat_messages) as $c): ?>
                    <div class="chat-msg">
                        <strong><?= e($c['customer_name']) ?>:</strong> <?= e($c['message']) ?>
                        <?php if(!empty($c['reply'])): ?>
                            <div class="chat-reply"><strong>Admin:</strong> <?= e($c['reply']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="post" class="p-3 border-top">
                <input type="hidden" name="action" value="send_chat">
                <div class="input-group">
                    <input type="text" name="chat_message" id="chat-input" class="form-control" placeholder="Type your message..." required>
                    <button class="btn btn-primary"><i class="fa fa-paper-plane"></i></button>
                </div>
            </form>
        </div>
    </div>
<script>
// Chatbox toggle logic
document.addEventListener("DOMContentLoaded", function() {
    const chatButton = document.getElementById("chat-button");
    const chatBox = document.getElementById("chat-box");
    const chatClose = document.getElementById("chat-close");

    chatButton.addEventListener("click", () => {
        if (chatBox.style.display === "flex") {
            chatBox.style.display = "none";
        } else {
            chatBox.style.display = "flex";
        }
    });

    chatClose.addEventListener("click", () => {
        chatBox.style.display = "none";
    });
});
</script>

    <div class="footer">
        <strong>Midnyt Fudtrip</strong> — © <?= date('Y') ?> All