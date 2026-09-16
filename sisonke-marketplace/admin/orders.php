<?php
$pageTitle  = 'Order Management';
$activePage = 'orders';
require_once '../includes/admin_middleware.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $order_id  = (int)($_POST['order_id']  ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $allowed   = ['pending','paid','processing','shipped','delivered','cancelled'];

    if ($order_id > 0 && in_array($newStatus, $allowed)) {
        $s = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $s->bind_param("si", $newStatus, $order_id);
        $s->execute();
        $s->close();
        $_SESSION['flash'] = ['type'=>'success','msg'=>"Order #$order_id updated to <strong>$newStatus</strong>."];
    }
    header('Location: orders.php');
    exit();
}

$search      = trim($_GET['search']   ?? '');
$filterStat  = $_GET['status']        ?? '';
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 15;
$offset      = ($page - 1) * $perPage;
$where  = "WHERE 1=1";
$params = [];
$types  = "";
if ($search !== '') {
    $where   .= " AND (u.username LIKE ? OR o.id LIKE ?)";
    $like     = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $types   .= "ss";
}
if ($filterStat !== '') {
    $where   .= " AND o.status = ?";
    $params[] = $filterStat;
    $types   .= "s";
}

// Total count
$cntStmt = $conn->prepare("SELECT COUNT(*) AS c FROM orders o JOIN users u ON u.id=o.user_id $where");
if ($params) $cntStmt->bind_param($types, ...$params);
$cntStmt->execute();
$totalRows  = (int)$cntStmt->get_result()->fetch_assoc()['c'];
$cntStmt->close();
$totalPages = (int)ceil($totalRows / $perPage);

$params[] = $perPage;
$params[] = $offset;
$types   .= "ii";

$stmt = $conn->prepare("
    SELECT
        o.id, o.total_amount, o.order_status, o.payment_method, o.created_at,
        u.username  AS customer,
        u.email     AS customer_email,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count
    FROM orders o
    JOIN users u ON u.id = o.user_id
    $where
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['msg'] ?></div>
    <?php endif; ?>
    <!-- FILTER BAR -->
    <div class="admin-card" style="margin-bottom:20px;">
        <div class="admin-card-header">
            <h2>All Orders <span style="color:var(--text-muted);font-weight:400;font-size:14px">(<?= $totalRows ?>)</span></h2>
        </div>
        <div style="padding:18px 22px;">
            <form method="GET" class="filter-bar">
                <input class="filter-input" type="text" name="search" placeholder="Search customer or order ID…" value="<?= htmlspecialchars($search) ?>">
                <select class="filter-select" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','paid','processing','shipped','delivered','cancelled'] as $st): ?>
                    <option value="<?= $st ?>" <?= $filterStat===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="orders.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>
    </div>
    <!-- ORDERS TABLE -->
    <div class="admin-card">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($orders && $orders->num_rows > 0):
                    while ($o = $orders->fetch_assoc()): ?>
                <tr>
                    <td><span style="font-family:'DM Mono',monospace;font-weight:700">#<?= $o['id'] ?></span></td>
                    <td>
                        <strong><?= htmlspecialchars($o['customer']) ?></strong><br>
                        <small style="color:var(--text-muted)"><?= htmlspecialchars($o['customer_email']) ?></small>
                    </td>
                    <td style="color:var(--text-muted)"><?= $o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
                    <td style="font-weight:700;color:var(--blue-light)">R<?= number_format($o['total_amount'], 2) ?></td>
                    <td>
                        <?php
                        $ps = strtolower($o['payment_status'] ?? 'unpaid');
                        echo "<span class=\"badge badge-{$ps}\">" . ucfirst($ps) . "</span>";
                        ?>
                    </td>
                    <td>
                        <?php
                        $os = strtolower($o['order_status'] ?? 'pending');
                        echo "<span class=\"badge badge-{$os}\">" . ucfirst($os) . "</span>";
                        ?>
                    </td>
                    <td style="color:var(--text-muted);font-size:12px"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-secondary btn-xs" onclick="openStatusModal(<?= $o['id'] ?>, '<?= $o['order_status'] ?>')">
                            <span class="material-symbols-outlined" style="font-size:13px">edit</span>
                            Change
                        </button>
                    </td>
                </tr>
                <!-- ORDER ITEMS sub-row (expandable) -->
                <tr id="items-<?= $o['id'] ?>" style="display:none;background:var(--surface2)">
                    <td colspan="8" style="padding:0">
                        <div style="padding:12px 22px" id="items-content-<?= $o['id'] ?>">
                            Loading…
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="8">
                    <div class="empty-state">
                        <span class="material-symbols-outlined">receipt_long</span>
                        <p>No orders found.</p>
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
            $qs = http_build_query(['search'=>$search,'status'=>$filterStat]);
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

<!-- STATUS UPDATE MODAL -->
<div class="modal-overlay" id="statusModal">
    <div class="modal">
        <h3>Update Order Status</h3>
        <p>Order <strong id="modalOrderId"></strong></p>
        <form method="POST" id="statusForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="order_id"   id="statusOrderId">
            <div class="modal-form-group">
                <label>New Status</label>
                <select name="new_status" id="statusSelect">
                    <?php foreach (['pending','paid','processing','shipped','delivered','cancelled'] as $st): ?>
                    <option value="<?= $st ?>"><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeStatusModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

</div></div>
<script>
function toggleSidebar() { document.getElementById('adminSidebar').classList.toggle('open'); }

function openStatusModal(orderId, currentStatus) {
    document.getElementById('modalOrderId').textContent  = '#' + orderId;
    document.getElementById('statusOrderId').value       = orderId;
    document.getElementById('statusSelect').value        = currentStatus;
    document.getElementById('statusModal').classList.add('open');
}
function closeStatusModal() { document.getElementById('statusModal').classList.remove('open'); }
document.getElementById('statusModal').addEventListener('click', e => {
    if (e.target === document.getElementById('statusModal')) closeStatusModal();
});
</script>
</body>
</html>