<?php
$pageTitle  = 'User Management';
$activePage = 'users';
require_once '../includes/admin_middleware.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action  = $_POST['action']  ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id > 0) {
        switch ($action) {

            case 'suspend':
                $s = $conn->prepare("UPDATE users SET status='suspended' WHERE id=? AND role != 'admin'");
                $s->bind_param("i", $user_id); $s->execute(); $s->close();
                $_SESSION['flash'] = ['type'=>'success','msg'=>'User suspended.'];
                break;

            case 'reactivate':
                $s = $conn->prepare("UPDATE users SET status='active' WHERE id=?");
                $s->bind_param("i", $user_id); $s->execute(); $s->close();
                $_SESSION['flash'] = ['type'=>'success','msg'=>'User reactivated.'];
                break;

            case 'ban':
                $s = $conn->prepare("UPDATE users SET status='banned' WHERE id=? AND role != 'admin'");
                $s->bind_param("i", $user_id); $s->execute(); $s->close();
                $_SESSION['flash'] = ['type'=>'success','msg'=>'User banned.'];
                break;

            case 'delete':
                // Don't allow deleting admins or yourself
                if ($user_id !== (int)$_SESSION['user_id']) {
                    $s = $conn->prepare("DELETE FROM users WHERE id=? AND role != 'admin'");
                    $s->bind_param("i", $user_id); $s->execute(); $s->close();
                    $_SESSION['flash'] = ['type'=>'success','msg'=>'User deleted.'];
                }
                break;

            case 'promote':
                $s = $conn->prepare("UPDATE users SET role='admin' WHERE id=?");
                $s->bind_param("i", $user_id); $s->execute(); $s->close();
                $_SESSION['flash'] = ['type'=>'success','msg'=>'User promoted to Admin.'];
                break;

            case 'demote':
                if ($user_id !== (int)$_SESSION['user_id']) {
                    $s = $conn->prepare("UPDATE users SET role='user' WHERE id=?");
                    $s->bind_param("i", $user_id); $s->execute(); $s->close();
                    $_SESSION['flash'] = ['type'=>'success','msg'=>'Admin privileges removed.'];
                }
                break;

            case 'reset_password':
                // Generate a temporary 10-char password
                $tempPass = bin2hex(random_bytes(5)); // 10 hex chars
                $hashed   = password_hash($tempPass, PASSWORD_DEFAULT);
                $s = $conn->prepare("UPDATE users SET password=?, must_change_password=1 WHERE id=?");
                $s->bind_param("si", $hashed, $user_id); $s->execute(); $s->close();
                $_SESSION['flash'] = ['type'=>'info','msg'=>"Temporary password set: <strong>{$tempPass}</strong> — share this with the user."];
                break;
        }
    }
    header('Location: users.php');
    exit();
}

$search     = trim($_GET['search']   ?? '');
$filterRole = $_GET['role']          ?? '';
$filterStat = $_GET['status']        ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 15;
$offset     = ($page - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($search !== '') {
    $where   .= " AND (username LIKE ? OR email LIKE ?)";
    $like     = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $types   .= "ss";
}
if ($filterRole !== '') {
    $where   .= " AND role = ?";
    $params[] = $filterRole;
    $types   .= "s";
}
if ($filterStat !== '') {
    $where   .= " AND status = ?";
    $params[] = $filterStat;
    $types   .= "s";
}

// Total count
$cntStmt = $conn->prepare("SELECT COUNT(*) AS c FROM users $where");
if ($params) $cntStmt->bind_param($types, ...$params);
$cntStmt->execute();
$totalRows  = (int)$cntStmt->get_result()->fetch_assoc()['c'];
$cntStmt->close();
$totalPages = (int)ceil($totalRows / $perPage);

// Fetch users
$params[] = $perPage;
$params[] = $offset;
$types   .= "ii";

$stmt = $conn->prepare("SELECT id, username, email, role, status, created_at, last_login, must_change_password FROM users $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result();
$stmt->close();

// Flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['msg'] ?></div>
    <?php endif; ?>

    <!-- FILTER BAR -->
    <div class="admin-card" style="margin-bottom:20px;">
        <div class="admin-card-header">
            <h2>All Users <span style="color:var(--text-muted);font-weight:400;font-size:14px">(<?= $totalRows ?>)</span></h2>
        </div>
        <div style="padding:18px 22px;">
            <form method="GET" class="filter-bar">
                <input  class="filter-input"  type="text"   name="search" placeholder="Search name or email…" value="<?= htmlspecialchars($search) ?>">
                <select class="filter-select" name="role">
                    <option value="">All Roles</option>
                    <option value="user"  <?= $filterRole==='user'  ?'selected':'' ?>>User</option>
                    <option value="admin" <?= $filterRole==='admin' ?'selected':'' ?>>Admin</option>
                </select>
                <select class="filter-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="active"    <?= $filterStat==='active'    ?'selected':'' ?>>Active</option>
                    <option value="suspended" <?= $filterStat==='suspended' ?'selected':'' ?>>Suspended</option>
                    <option value="banned"    <?= $filterStat==='banned'    ?'selected':'' ?>>Banned</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="users.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>
    </div>

    <!-- USERS TABLE -->
    <div class="admin-card">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($users->num_rows > 0):
                    while ($u = $users->fetch_assoc()):
                        $isSelf  = ($u['id'] == $_SESSION['user_id']);
                        $isAdmin = ($u['role'] === 'admin');
                ?>
                <tr>
                    <td style="font-family:'DM Mono',monospace;color:var(--text-muted)"><?= $u['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($u['username']) ?></strong>
                        <?php if ($isSelf): ?> <span class="badge badge-admin" style="font-size:10px">You</span><?php endif; ?>
                        <?php if ($u['must_change_password']): ?> <span class="badge badge-pending" style="font-size:10px">Temp PW</span><?php endif; ?>
                        <br><small style="color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></small>
                    </td>
                    <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                    <td><span class="badge badge-<?= $u['status'] ?>"><?= $u['status'] ?></span></td>
                    <td style="color:var(--text-muted);font-size:12px"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td style="color:var(--text-muted);font-size:12px"><?= $u['last_login'] ? date('d M Y H:i', strtotime($u['last_login'])) : '—' ?></td>
                    <td>
                        <div class="actions-cell">
                            <!-- Reset Password -->
                            <button class="btn btn-secondary btn-xs" onclick="confirmAction('reset_password', <?= $u['id'] ?>, 'Reset password for <?= addslashes($u['username']) ?>?')">
                                <span class="material-symbols-outlined" style="font-size:14px">lock_reset</span> Reset PW
                            </button>

                            <?php if (!$isSelf && !$isAdmin): ?>
                                <!-- Status actions -->
                                <?php if ($u['status'] !== 'suspended'): ?>
                                <button class="btn btn-warning btn-xs" onclick="confirmAction('suspend', <?= $u['id'] ?>, 'Suspend <?= addslashes($u['username']) ?>?')">Suspend</button>
                                <?php endif; ?>
                                <?php if ($u['status'] !== 'active'): ?>
                                <button class="btn btn-success btn-xs" onclick="confirmAction('reactivate', <?= $u['id'] ?>, 'Reactivate <?= addslashes($u['username']) ?>?')">Reactivate</button>
                                <?php endif; ?>
                                <?php if ($u['status'] !== 'banned'): ?>
                                <button class="btn btn-danger btn-xs" onclick="confirmAction('ban', <?= $u['id'] ?>, 'Permanently ban <?= addslashes($u['username']) ?>?')">Ban</button>
                                <?php endif; ?>
                                <!-- Role actions -->
                                <button class="btn btn-secondary btn-xs" onclick="confirmAction('promote', <?= $u['id'] ?>, 'Promote <?= addslashes($u['username']) ?> to Admin?')">
                                    <span class="material-symbols-outlined" style="font-size:14px">admin_panel_settings</span>
                                </button>
                                <!-- Delete -->
                                <button class="btn btn-danger btn-xs" onclick="confirmAction('delete', <?= $u['id'] ?>, 'PERMANENTLY delete <?= addslashes($u['username']) ?>? This cannot be undone.')">
                                    <span class="material-symbols-outlined" style="font-size:14px">delete</span>
                                </button>
                            <?php elseif ($isAdmin && !$isSelf): ?>
                                <button class="btn btn-warning btn-xs" onclick="confirmAction('demote', <?= $u['id'] ?>, 'Remove admin privileges from <?= addslashes($u['username']) ?>?')">Demote</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <span class="material-symbols-outlined">person_search</span>
                        <p>No users found.</p>
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
            $qs = http_build_query(['search'=>$search,'role'=>$filterRole,'status'=>$filterStat]);
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
        <h3 id="modalTitle">Confirm Action</h3>
        <p  id="modalMessage">Are you sure?</p>
        <form method="POST" id="actionForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action"  id="modalAction">
            <input type="hidden" name="user_id" id="modalUserId">
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-danger"   id="modalConfirmBtn">Confirm</button>
            </div>
        </form>
    </div>
</div>

</div><!-- /.admin-content -->
</div><!-- /.admin-main -->

<script>
function toggleSidebar() { document.getElementById('adminSidebar').classList.toggle('open'); }

function confirmAction(action, userId, message) {
    document.getElementById('modalTitle').textContent   = 'Confirm Action';
    document.getElementById('modalMessage').innerHTML   = message;
    document.getElementById('modalAction').value        = action;
    document.getElementById('modalUserId').value        = userId;

    // Adjust confirm button colour per action
    const btn = document.getElementById('modalConfirmBtn');
    btn.className = 'btn ' + (['delete','ban'].includes(action) ? 'btn-danger' : 'btn-primary');
    btn.textContent = 'Confirm';

    document.getElementById('confirmModal').classList.add('open');
}

function closeModal() { document.getElementById('confirmModal').classList.remove('open'); }

// Close on overlay click
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>