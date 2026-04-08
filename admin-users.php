<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Proteksi halaman: Hanya admin yang boleh masuk
requireRole('admin');

$page_title = 'Kelola User';
$current_user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// --- HANDLE ADD USER ---
if (isset($_POST['add_user'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Semua field wajib harus diisi!';
    } else {
        $check_query = "SELECT id FROM users WHERE email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            $query = "INSERT INTO users (name, email, password, role, phone, institution) 
                      VALUES (:name, :email, :password, :role, :phone, :institution)";
            
            $stmt = $db->prepare($query);
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':institution', $institution);
            
            if ($stmt->execute()) {
                $success = 'User berhasil ditambahkan!';
            } else {
                $error = 'Gagal menambahkan user!';
            }
        }
    }
}

// --- HANDLE EDIT USER ---
if (isset($_POST['edit_user'])) {
    $id = $_POST['user_id'];
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($role)) {
        $error = 'Nama, Email, dan Role wajib diisi!';
    } else {
        // Cek jika email sudah digunakan user lain
        $check_query = "SELECT id FROM users WHERE email = :email AND id != :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->bindParam(':id', $id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $error = 'Email sudah digunakan oleh pengguna lain!';
        } else {
            $query = "UPDATE users SET name = :name, email = :email, role = :role, 
                      phone = :phone, institution = :institution";
            
            if (!empty($password)) {
                $query .= ", password = :password";
            }
            $query .= " WHERE id = :id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':institution', $institution);
            $stmt->bindParam(':id', $id);

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt->bindParam(':password', $hashed_password);
            }

            if ($stmt->execute()) {
                $success = 'Data user berhasil diperbarui!';
            } else {
                $error = 'Gagal memperbarui data user!';
            }
        }
    }
}

// --- HANDLE DELETE USER ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($id == $current_user['id']) {
        $error = 'Anda tidak dapat menghapus akun sendiri!';
    } else {
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $success = 'User berhasil dihapus!';
        } else {
            $error = 'Gagal menghapus user!';
        }
    }
}

// --- DATA FETCHING LOGIC ---
$filter_role = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT id, name, email, role, phone, institution, created_at FROM users WHERE 1=1";
if ($filter_role) $query .= " AND role = :role";
if ($search) $query .= " AND (name LIKE :search OR email LIKE :search OR phone LIKE :search)";
$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
if ($filter_role) $stmt->bindParam(':role', $filter_role);
if ($search) {
    $search_term = "%{$search}%";
    $stmt->bindParam(':search', $search_term);
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistik Role
$query_count = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$role_counts = [];
foreach ($db->query($query_count)->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $role_counts[$row['role']] = $row['count'];
}
$total_users = array_sum($role_counts);

include 'includes/header.php';
?>

<style>
    :root { --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); }
    body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    .stat-card { border: none; border-radius: 20px; transition: all 0.3s; background: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 2px solid transparent; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05); }
    .stat-card.active-all { border-color: #4f46e5; background: #eef2ff; }
    .stat-card.active-pemohon { border-color: #10b981; background: #ecfdf5; }
    .stat-card.active-penerima { border-color: #06b6d4; background: #ecfeff; }
    .stat-card.active-admin { border-color: #f59e0b; background: #fffbeb; }
    .search-container { background: white; border-radius: 15px; padding: 8px 15px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 10px; }
    .search-container input { border: none; box-shadow: none !important; font-size: 0.95rem; }
    .card-table { border: none; border-radius: 24px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04); background: white; overflow: hidden; }
    .table thead th { background: #f8fafc; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; color: #64748b; padding: 1.25rem; }
    .table tbody td { padding: 1.25rem; vertical-align: middle; color: #334155; }
    .badge-soft { padding: 6px 12px; border-radius: 10px; font-weight: 600; font-size: 0.8rem; }
    .bg-soft-success { background: #dcfce7; color: #166534; }
    .bg-soft-info { background: #e0f2fe; color: #075985; }
    .bg-soft-warning { background: #fef3c7; color: #92400e; }
    .modal-content { border-radius: 24px; border: none; }
    .form-control, .form-select { border-radius: 12px; padding: 0.6rem 1rem; border: 1px solid #e2e8f0; }
</style>

<div class="container-fluid py-4 px-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="admin-dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Kelola User</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-dark mb-0">Manajemen Pengguna</h2>
            <p class="text-muted small">kelolah user pengguna website</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <button type="button" class="btn btn-primary px-4 py-2 fw-600 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus-fill me-2"></i> Tambah User Baru
            </button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="row mb-4 g-3">
        <div class="col-xl-3 col-md-6">
            <a href="admin-users.php" class="text-decoration-none">
                <div class="stat-card p-3 <?php echo !$filter_role ? 'active-all' : ''; ?>">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded-4 me-3"><i class="bi bi-people text-primary fs-4"></i></div>
                        <div>
                            <h6 class="text-muted small mb-1">Total Pengguna</h6>
                            <h4 class="fw-bold mb-0 text-dark"><?php echo $total_users; ?></h4>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="admin-users.php?role=pemohon" class="text-decoration-none">
                <div class="stat-card p-3 <?php echo $filter_role === 'pemohon' ? 'active-pemohon' : ''; ?>">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-success bg-opacity-10 p-3 rounded-4 me-3"><i class="bi bi-person-up text-success fs-4"></i></div>
                        <div><h6 class="text-muted small mb-1">Pemohon</h6><h4 class="fw-bold mb-0 text-dark"><?php echo $role_counts['pemohon'] ?? 0; ?></h4></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="admin-users.php?role=penerima" class="text-decoration-none">
                <div class="stat-card p-3 <?php echo $filter_role === 'penerima' ? 'active-penerima' : ''; ?>">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-info bg-opacity-10 p-3 rounded-4 me-3"><i class="bi bi-person-down text-info fs-4"></i></div>
                        <div><h6 class="text-muted small mb-1">Penerima</h6><h4 class="fw-bold mb-0 text-dark"><?php echo $role_counts['penerima'] ?? 0; ?></h4></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="admin-users.php?role=admin" class="text-decoration-none">
                <div class="stat-card p-3 <?php echo $filter_role === 'admin' ? 'active-admin' : ''; ?>">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded-4 me-3"><i class="bi bi-shield-lock text-warning fs-4"></i></div>
                        <div><h6 class="text-muted small mb-1">Admin</h6><h4 class="fw-bold mb-0 text-dark"><?php echo $role_counts['admin'] ?? 0; ?></h4></div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" class="search-container">
                <i class="bi bi-search text-muted"></i>
                <?php if ($filter_role): ?><input type="hidden" name="role" value="<?php echo $filter_role; ?>"><?php endif; ?>
                <input type="text" name="search" class="form-control" placeholder="Cari nama, email..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>
    </div>

    <div class="card card-table shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Profil</th>
                            <th>Role</th>
                            <th>Telepon</th>
                            <th>Institusi</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($user['name']); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td>
                                    <?php $bc = ($user['role'] == 'pemohon') ? 'success' : (($user['role'] == 'penerima') ? 'info' : 'warning'); ?>
                                    <span class="badge-soft bg-soft-<?php echo $bc; ?>"><?php echo ucfirst($user['role']); ?></span>
                                </td>
                                <td><?php echo $user['phone'] ?: '-'; ?></td>
                                <td><?php echo $user['institution'] ?: '-'; ?></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary border-0 btn-edit" 
                                            data-bs-toggle="modal" data-bs-target="#editUserModal"
                                            data-id="<?php echo $user['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                            data-role="<?php echo $user['role']; ?>"
                                            data-phone="<?php echo htmlspecialchars($user['phone']); ?>"
                                            data-institution="<?php echo htmlspecialchars($user['institution']); ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <?php if ($user['id'] != $current_user['id']): ?>
                                            <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Hapus?')">
                                                <i class="bi bi-trash3"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary rounded-pill">Self</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5>Tambah User Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <div class="modal-body row g-3">
                    <div class="col-md-6"><label class="small fw-bold">Nama *</label><input type="text" class="form-control" name="name" required></div>
                    <div class="col-md-6"><label class="small fw-bold">Email *</label><input type="email" class="form-control" name="email" required></div>
                    <div class="col-md-6"><label class="small fw-bold">Password *</label><input type="password" class="form-control" name="password" required></div>
                    <div class="col-md-6"><label class="small fw-bold">Role *</label>
                        <select class="form-select" name="role" required>
                            <option value="pemohon">Pemohon</option>
                            <option value="penerima">Penerima</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="small fw-bold">Telepon</label><input type="text" class="form-control" name="phone"></div>
                    <div class="col-md-6"><label class="small fw-bold">Institusi</label><input type="text" class="form-control" name="institution"></div>
                </div>
                <div class="modal-footer"><button type="submit" name="add_user" class="btn btn-primary rounded-pill px-4">Simpan</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5>Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_id">
                <div class="modal-body row g-3">
                    <div class="col-md-6"><label class="small fw-bold">Nama *</label><input type="text" class="form-control" name="name" id="edit_name" required></div>
                    <div class="col-md-6"><label class="small fw-bold">Email *</label><input type="email" class="form-control" name="email" id="edit_email" required></div>
                    <div class="col-md-6"><label class="small fw-bold">Role *</label>
                        <select class="form-select" name="role" id="edit_role" required>
                            <option value="pemohon">Pemohon</option>
                            <option value="penerima">Penerima</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="small fw-bold">Password (Kosongkan jika tetap)</label><input type="password" class="form-control" name="password"></div>
                    <div class="col-md-6"><label class="small fw-bold">Telepon</label><input type="text" class="form-control" name="phone" id="edit_phone"></div>
                    <div class="col-md-6"><label class="small fw-bold">Institusi</label><input type="text" class="form-control" name="institution" id="edit_institution"></div>
                </div>
                <div class="modal-footer"><button type="submit" name="edit_user" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
</div>

<script>
// Script Edit Auto-fill
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_name').value = this.dataset.name;
        document.getElementById('edit_email').value = this.dataset.email;
        document.getElementById('edit_role').value = this.dataset.role;
        document.getElementById('edit_phone').value = this.dataset.phone;
        document.getElementById('edit_institution').value = this.dataset.institution;
    });
});
</script>

<?php include 'includes/footer.php'; ?>