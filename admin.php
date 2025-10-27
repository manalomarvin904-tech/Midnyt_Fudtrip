<?php
// -------------------- CONFIG --------------------
$db_host='localhost'; 
$db_name='midnyt_fudtrip_db'; 
$db_user='root'; 
$db_pass='';

// -------------------- DB CONNECTION --------------------
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8",$db_user,$db_pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch(PDOException $e){ die("DB Error: ".$e->getMessage()); }

// -------------------- HELPERS --------------------
session_start();
function flash($m,$t='success'){ $_SESSION['flash']=['msg'=>$m,'type'=>$t]; }
$flash=$_SESSION['flash']??null; unset($_SESSION['flash']);
function e($s){ return htmlspecialchars($s??'',ENT_QUOTES); }

// -------------------- POST HANDLERS --------------------
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])){
    $action=$_POST['action'];

    // Menu CRUD
    if(in_array($action,['add_menu','edit_menu'])){
        $name=trim($_POST['name']); 
        $category=$_POST['category']; 
        $price=floatval($_POST['price']); 
        $desc=trim($_POST['description']);
        if($name==''||$price<=0){ flash('Invalid data','danger'); header('Location: admin.php'); exit; }
        if($action==='add_menu'){
            $pdo->prepare("INSERT INTO menu (name,category,price,description) VALUES (?,?,?,?)")->execute([$name,$category,$price,$desc]);
            flash('Menu item added.');
        } else {
            $id=intval($_POST['id']);
            $pdo->prepare("UPDATE menu SET name=?,category=?,price=?,description=? WHERE id=?")->execute([$name,$category,$price,$desc,$id]);
            flash('Menu updated.');
        }
    }

    if($action==='delete_menu'){
        $id=intval($_POST['id']);
        $pdo->prepare("DELETE FROM menu WHERE id=?")->execute([$id]);
        flash('Menu deleted.');
    }

    // Order status
    if($action==='update_order_status'){
        $id=intval($_POST['id']); 
        $status=$_POST['status']==='Completed'?'Completed':'Pending';
        $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status,$id]);
        flash('Order status updated.');
    }

    if($action==='delete_order'){
        $id=intval($_POST['id']);
        $pdo->prepare("DELETE FROM orders WHERE id=?")->execute([$id]);
        flash('Order deleted.');
    }

    // Chat reply
    if($action==='reply_chat'){
        $id=intval($_POST['id']); 
        $reply=trim($_POST['reply'] ?? '');
        if($reply!==''){
            $pdo->exec("ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS reply TEXT");
            $pdo->prepare("UPDATE chat_messages SET reply=? WHERE id=?")->execute([$reply,$id]);
            flash('Replied to customer.');
        }
    }

    header('Location: admin.php'); exit;
}

// -------------------- FETCH DATA --------------------
$menu_items = $pdo->query("SELECT * FROM menu ORDER BY FIELD(category,'Solo','Combo','Platter'), name")->fetchAll(PDO::FETCH_ASSOC);
$orders = $pdo->query("SELECT o.*, m.name as menu_name FROM orders o JOIN menu m ON o.menu_id=m.id ORDER BY o.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$chat_msgs = $pdo->query("SELECT * FROM chat_messages ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// -------------------- ANALYTICS --------------------
$analytics = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(total_price),0) as total_revenue,
        SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending_orders
    FROM orders
")->fetch(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard — Midnyt Fudtrip</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --primary: #1e3a8a;
    --secondary: #10b981;
    --accent: #f59e0b;
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
    margin: 0;
}
.sidebar {
    width: 260px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    display: flex;
    flex-direction: column;
    padding: 2rem 1.5rem;
    box-shadow: var(--shadow);
}
.sidebar h3 {
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 2rem;
    text-align: center;
}
.sidebar a {
    color: white;
    text-decoration: none;
    margin: 0.5rem 0;
    display: block;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    transition: var(--transition);
    font-weight: 500;
}
.sidebar a:hover {
    background: rgba(255,255,255,0.1);
    transform: translateX(5px);
}
.main {
    margin-left: 280px;
    padding: 2rem;
}
.card-pro {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow);
    border: none;
    padding: 2rem;
    margin-bottom: 2rem;
    transition: var(--transition);
}
.card-pro:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.15); }
.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}
.analytics-box {
    background: var(--primary);
    color: white;
    padding: 2rem;
    border-radius: 16px;
    text-align: center;
    transition: var(--transition);
}
.analytics-box:hover { transform: translateY(-5px); }
.analytics-box h3 { font-size: 2rem; margin-bottom: 0.5rem; }
.analytics-box small { color: var(--accent); font-weight: 600; }
.table thead {
    background: var(--primary);
    color: white;
}
.btn-primary { background: var(--primary); border: none; }
.btn-primary:hover { background: #1d4ed8; }
.search-bar { margin-bottom: 1.5rem; }
#chatBox {
    position: fixed;
    bottom: 90px;
    right: 20px;
    width: 380px;
    max-height: 500px;
    display: none;
    flex-direction: column;
    border-radius: 16px;
    overflow: hidden;
    background: white;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    z-index: 1000;
}
.chat-header {
    background: var(--primary);
    color: white;
    padding: 1rem;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.chat-messages {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    background: var(--light-bg);
}
.chat-item {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
    border-left: 4px solid var(--primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.chat-reply {
    margin-left: 1rem;
    color: var(--secondary);
    font-weight: 500;
}
footer {
    text-align: center;
    padding: 1rem;
    background: white;
    border-top: 1px solid #e5e7eb;
    box-shadow: 0 -2px 8px rgba(0,0,0,0.05);
}
</style>
</head>

<body>
<div class="sidebar">
    <h3><i class="fa fa-utensils"></i> Midnyt Fudtrip</h3>
    <a href="#analytics"><i class="fa fa-chart-line"></i> Analytics</a>
    <a href="#menu"><i class="fa fa-list"></i> Menu Manager</a>
    <a href="#orders"><i class="fa fa-box"></i> Orders</a>
    <a href="javascript:void(0)" onclick="toggleChatBox()"><i class="fa fa-comments"></i> Chat</a>
    <hr>
    <a href="index.php"><i class="fa fa-arrow-left"></i> Back to Website</a>
</div>

<div class="main">
    <h2 class="mb-4"><i class="fa fa-tachometer-alt"></i> Admin Dashboard</h2>

    <?php if($flash): ?>
    <script>
    Swal.fire({icon:'<?= $flash['type']=='success'?'success':'error' ?>',title:'<?= e($flash['msg']) ?>',toast:true,position:'top-end',timer:3000,showConfirmButton:false});
    </script>
    <?php endif; ?>

    <!-- Analytics -->
    <div id="analytics" class="card-pro">
        <h4 class="mb-3"><i class="fa fa-chart-bar"></i> Analytics Overview</h4>
        <div class="analytics-grid">
            <div class="analytics-box">
                <h3><?= $analytics['total_orders'] ?></h3>
                <small>Total Orders</small>
            </div>
            <div class="analytics-box">
                <h3>₱<?= number_format($analytics['total_revenue'],2) ?></h3>
                <small>Total Revenue</small>
            </div>
            <div class="analytics-box">
                <h3><?= $analytics['completed_orders'] ?></h3>
                <small>Completed Orders</small>
            </div>
            <div class="analytics-box">
                <h3><?= $analytics['pending_orders'] ?></h3>
                <small>Pending Orders</small>
            </div>
        </div>
    </div>

    <!-- Menu Manager -->
    <div id="menu" class="card-pro">
        <h4 class="mb-3"><i class="fa fa-utensils"></i> Menu Manager</h4>
        <form method="post" class="row g-3 mb-4">
            <input type="hidden" name="action" value="add_menu" id="form_action">
            <input type="hidden" name="id" id="menu_id">
            <div class="col-md-4"><input name="name" id="name" class="form-control" placeholder="Name" required></div>
            <div class="col-md-3"><select name="category" id="category" class="form-select"><option>Solo</option><option>Combo</option><option>Platter</option></select></div>
            <div class="col-md-2"><input type="number" name="price" id="price" class="form-control" step="0.01" placeholder="Price" required></div>
            <div class="col-md-3"><input type="text" name="description" id="description" class="form-control" placeholder="Description"></div>
            <div class="col-12">
                <button class="btn btn-primary" id="submitBtn"><i class="fa fa-plus"></i> Add Item</button>
                <button type="button" class="btn btn-secondary ms-2" onclick="resetForm()"><i class="fa fa-times"></i> Cancel</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Description</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($menu_items as $mi): ?>
                    <tr>
                        <td><?= e($mi['name']) ?></td>
                        <td><span class="badge bg-secondary"><?= e($mi['category']) ?></span></td>
                        <td>₱<?= number_format($mi['price'],2) ?></td>
                        <td><?= e($mi['description']) ?></td>
                        <td>
                            <button 
                                type="button"
                                class="btn btn-sm btn-outline-primary me-2 edit-btn"
                                data-id="<?= $mi['id'] ?>"
                                data-name="<?= e($mi['name']) ?>"
                                data-category="<?= e($mi['category']) ?>"
                                data-price="<?= $mi['price'] ?>"
                                data-description="<?= e($mi['description']) ?>">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this menu item?')">
                                <input type="hidden" name="action" value="delete_menu">
                                <input type="hidden" name="id" value="<?= $mi['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chat Box -->
    <div id="chatBox">
        <div class="chat-header">
            Customer Chats
            <button class="btn btn-sm btn-light" onclick="toggleChatBox()">✖</button>
        </div>
        <div class="chat-messages">
            <?php foreach($chat_msgs as $chat): ?>
                <div class="chat-item">
                    <strong><?= e($chat['customer_name']) ?>:</strong> <?= e($chat['message']) ?><br>
                    <?php if(!empty($chat['reply'])): ?>
                        <div class="chat-reply"><strong>Admin:</strong> <?= e($chat['reply']) ?></div>
                    <?php endif; ?>
                    <form method="post" class="mt-2">
                        <input type="hidden" name="action" value="reply_chat">
                        <input type="hidden" name="id" value="<?= $chat['id'] ?>">
                        <div class="input-group">
                            <input type="text" name="reply" class="form-control form-control-sm" placeholder="Reply...">
                            <button class="btn btn-primary btn-sm">Send</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- JS -->
<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function(){
        document.getElementById('form_action').value = 'edit_menu';
        document.getElementById('menu_id').value = this.dataset.id;
        document.getElementById('name').value = this.dataset.name;
        document.getElementById('category').value = this.dataset.category;
        document.getElementById('price').value = this.dataset.price;
        document.getElementById('description').value = this.dataset.description;
        document.getElementById('submitBtn').innerHTML = '<i class="fa fa-save"></i> Update Item';
    });
});

function resetForm(){
    document.getElementById('form_action').value = 'add_menu';
    document.getElementById('menu_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('category').value = 'Solo';
    document.getElementById('price').value = '';
    document.getElementById('description').value = '';
    document.getElementById('submitBtn').innerHTML = '<i class="fa fa-plus"></i> Add Item';
}

function toggleChatBox(){
    const box = document.getElementById('chatBox');
    box.style.display = (box.style.display === 'flex') ? 'none' : 'flex';
}
</script>

</body>
</html>
