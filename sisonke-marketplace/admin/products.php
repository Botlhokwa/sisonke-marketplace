<?php
$pageTitle  = 'Product Moderation';
$activePage = 'products';
require_once '../includes/admin_middleware.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action     = $_POST['action']     ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);
    if ($product_id > 0) {
        switch ($action) {
            case 'approve':
                $s = $conn->prepare("UPDATE products SET status='approved' WHERE id=?");
                $s->bind_param("i", $product_id); $s->execute(); $s->close();
                $_SESSION['flash'] = ['type'=>'success','msg'=>'Product approved and is now publicly visible.'];
                break;
            case 'reject':
                $s = $conn->prepare("UPDATE products SET status='rejected' WHERE id=?");
                $s->bind_param("i", $product_id); $s->execute(); $s->close();
                $_SESSION['flash'] = ['type'=>'info','msg'=>'Product rejected.'];
                break;
            case 'fraudulent':
                $s = $conn->prepare("UPDATE products SET status='fraudulent' WHERE id=?");
                $s->bind_param("i", $product_id); $s->execute(); $s->close();
                $_SESSION['flash'] = ['type'=>'info','msg'=>'Product marked as fraudulent.'];
                break;
            case 'pending':
                $s = $conn->prepare("UPDATE products SET status='pending' WHERE id=?");
                $s->bind_param("i", $product_id); $s->execute(); $s->close();
                $_SESSION['flash'] = ['type'=>'info','msg'=>'Product set back to pending.'];
                break;
            case 'delete':
                $s = $conn->prepare("DELETE FROM products WHERE id=?");
                $s->bind_param("i", $product_id); $s->execute(); $s->close();
                $_SESSION['flash'] = ['type'=>'success','msg'=>'Product deleted.'];
                break;
        }
    }
    header('Location: products.php');
    exit();
}

$search      = trim($_GET['search']   ?? '');
$filterStat  = $_GET['status']        ?? '';
$filterCat   = $_GET['category']      ?? '';
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 15;
$offset      = ($page - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($search !== '') {
    $where   .= " AND (p.title LIKE ? OR u.username LIKE ?)";
    $like     = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $types   .= "ss";
}
if ($filterStat !== '') {
    $where   .= " AND p.status = ?";
    $params[] = $filterStat;
    $types   .= "s";
}
if ($filterCat !== '') {
    $where   .= " AND p.category = ?";
    $params[] = $filterCat;
    $types   .= "s";
}

// Total
$cntStmt = $conn->prepare("SELECT COUNT(*) AS c FROM products p JOIN users u ON u.id=p.user_id $where");
if ($params) $cntStmt->bind_param($types, ...$params);
$cntStmt->execute();
$totalRows  = (int)$cntStmt->get_result()->fetch_assoc()['c'];
$cntStmt->close();
$totalPages = (int)ceil($totalRows / $perPage);
$params[] = $perPage;
$params[] = $offset;
$types   .= "ii";
$stmt = $conn->prepare("
    SELECT p.id, p.title, p.category, p.price, p.stock, p.status, p.image, p.created_at,
           u.username AS seller, u.id AS seller_id
    FROM products p
    JOIN users u ON u.id = p.user_id
    $where
    ORDER BY
        CASE p.status WHEN 'pending' THEN 0 ELSE 1 END,
        p.created_at DESC
    LIMIT ? OFFSET ?
");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result();
$stmt->close();

// Categories for filter dropdown
$cats = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['msg'] ?></div>
    <?php endif; ?>
    <!-- FILTER BAR -->
    <div class="admin-card" style="margin-bottom:20px;">
        <div class="admin-card-header">
            <h2>All Products <span style="color:var(--text-muted);font-weight:400;font-size:14px">(<?= $totalRows ?>)</span></h2>
        </div>
        <div style="padding:18px 22px;">
            <form method="GET" class="filter-bar">
                <input  class="filter-input"  type="text" name="search"   placeholder="Search title or seller…" value="<?= htmlspecialchars($search) ?>">
                <select class="filter-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending"    <?= $filterStat==='pending'    ?'selected':'' ?>>Pending</option>
                    <option value="approved"   <?= $filterStat==='approved'   ?'selected':'' ?>>Approved</option>
                    <option value="rejected"   <?= $filterStat==='rejected'   ?'selected':'' ?>>Rejected</option>
                    <option value="fraudulent" <?= $filterStat==='fraudulent' ?'selected':'' ?>>Fraudulent</option>
                </select>
                <select class="filter-select" name="category">
                    <option value="">All Categories</option>
                    <?php while ($c = $cats->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($c['category']) ?>" <?= $filterCat===$c['category']?'selected':'' ?>>
                        <?= htmlspecialchars($c['category']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="products.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>
    </div>
    <!-- PRODUCTS TABLE -->
    <div class="admin-card">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Listed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($products->num_rows > 0):
                    while ($p = $products->fetch_assoc()): ?>
                <tr>
                    <td style="font-family:'DM Mono',monospace;color:var(--text-muted);font-size:12px"><?= $p['id'] ?></td>
                    <td>
                        <div class="product-thumb">
                            <img src="/uploads/<?= htmlspecialchars($p['image']) ?>"
                                 onerror="this.style.background='var(--surface3)';this.src=''"
                                 alt="">
                            <div>
                                <div class="product-thumb-name"><?= htmlspecialchars($p['title']) ?></div>
                                <div class="product-thumb-cat"><?= htmlspecialchars($p['category']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($p['seller']) ?></td>
                    <td style="font-weight:700;color:var(--blue-light)">R<?= number_format($p['price'], 2) ?></td>
                    <td><?= $p['stock'] ?></td>
                    <td><span class="badge badge-<?= $p['status'] ?>"><?= $p['status'] ?></span></td>
                    <td style="color:var(--text-muted);font-size:12px"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                    <td>
                        <div class="actions-cell">
                            <?php if ($p['status'] !== 'approved'): ?>
                            <button class="btn btn-success btn-xs"
                                onclick="productAction('approve', <?= $p['id'] ?>, 'Approve &quot;<?= addslashes(htmlspecialchars($p['title'])) ?>&quot;?')">
                                Approve
                            </button>
                            <?php endif; ?>
                            <?php if ($p['status'] !== 'rejected'): ?>
                            <button class="btn btn-warning btn-xs"
                                onclick="productAction('reject', <?= $p['id'] ?>, 'Reject &quot;<?= addslashes(htmlspecialchars($p['title'])) ?>&quot;?')">
                                Reject
                            </button>
                            <?php endif; ?>
                            <?php if ($p['status'] !== 'fraudulent'): ?>
                            <button class="btn btn-danger btn-xs"
                                onclick="productAction('fraudulent', <?= $p['id'] ?>, 'Mark &quot;<?= addslashes(htmlspecialchars($p['title'])) ?>&quot; as FRAUDULENT?')">
                                Fraud
                            </button>
                            <?php endif; ?>
                            <?php if ($p['status'] !== 'pending'): ?>
                            <button class="btn btn-secondary btn-xs"
                                onclick="productAction('pending', <?= $p['id'] ?>, 'Reset to pending?')">
                                Pending
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-danger btn-xs"
                                onclick="productAction('delete', <?= $p['id'] ?>, 'PERMANENTLY delete &quot;<?= addslashes(htmlspecialchars($p['title'])) ?>&quot;?')">
                                <span class="material-symbols-outlined" style="font-size:13px">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="8">
                    <div class="empty-state">
                        <span class="material-symbols-outlined">inventory_2</span>
                        <p>No products found.</p>
                    </div>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $qs = http_build_query(['search'=>$search,'status'=>$filterStat,'category'=>$filterCat]);
            for ($i = 1; $i <= $totalPages; $i++):
            ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?<?= $qs ?>&page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

<!-- CONFIRMATION MODAL -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal">
        <h3>Confirm Action</h3>
        <p id="modalMessage">Are you sure?</p>
        <form method="POST" id="actionForm">
            <input type="hidden" name="csrf_token"  value="<?= csrf_token() ?>">
            <input type="hidden" name="action"      id="modalAction">
            <input type="hidden" name="product_id"  id="modalProductId">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="confirmBtn">Confirm</button>
            </div>
        </form>
    </div>
</div>
</div></div>
<script>
function toggleSidebar() { document.getElementById('adminSidebar').classList.toggle('open'); }
function productAction(action, id, msg) {
    document.getElementById('modalMessage').innerHTML  = msg;
    document.getElementById('modalAction').value       = action;
    document.getElementById('modalProductId').value    = id;
    const btn = document.getElementById('confirmBtn');
    btn.className = 'btn ' + (['delete','fraudulent'].includes(action) ? 'btn-danger' : action === 'approve' ? 'btn-success' : 'btn-primary');
    document.getElementById('confirmModal').classList.add('open');
}
function closeModal() { document.getElementById('confirmModal').classList.remove('open'); }
document.getElementById('confirmModal').addEventListener('click', e => { if (e.target === document.getElementById('confirmModal')) closeModal(); });
</script>
</body>
</html>