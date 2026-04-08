<?php
require_once 'includes/header_user.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Tentukan link kembali berdasarkan role (opsional, agar lebih dinamis)
$back_link = ($current_user['role'] === 'admin') ? 'admin-dashboard.php' : 'pemohon-dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // Logika Update
    if (!empty($new_password)) {
        // Jika ganti password, verifikasi password lama
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$current_user['id']]);
        $user_data = $stmt->fetch();

        if (!password_verify($current_password, $user_data['password'])) {
            $error = 'Password saat ini salah!';
        } else {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $query = "UPDATE users SET name=?, phone=?, institution=?, password=? WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->execute([$name, $phone, $institution, $hashed, $current_user['id']]);
            $success = 'Pengaturan dan password berhasil diperbarui!';
        }
    } else {
        // Update tanpa ganti password
        $query = "UPDATE users SET name=?, phone=?, institution=? WHERE id=?";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $phone, $institution, $current_user['id']]);
        $success = 'Pengaturan berhasil disimpan!';
    }

    if (empty($error)) {
        $_SESSION['user_name'] = $name;
        $current_user = getCurrentUser(); // Refresh data user
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center">
                    <a href="<?php echo $back_link; ?>" class="btn btn-white shadow-sm rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border: 1px solid #eee;">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <h4 class="fw-bold m-0">Pengaturan Akun</h4>
                </div>
                <nav aria-label="breadcrumb" class="d-none d-md-block">
                    <ol class="breadcrumb m-0" style="font-size: 0.8rem;">
                        <li class="breadcrumb-item"><a href="<?php echo $back_link; ?>">Home</a></li>
                        <li class="breadcrumb-item active">Settings</li>
                    </ol>
                </nav>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success border-0 shadow-sm rounded-4 py-3 mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-4 py-3 mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-4"><i class="bi bi-person me-2 text-primary"></i>Informasi Umum</h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control rounded-3 bg-light border-0" value="<?php echo htmlspecialchars($current_user['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Email (Tidak dapat diubah)</label>
                                <input type="email" class="form-control rounded-3 bg-light border-0" value="<?php echo htmlspecialchars($current_user['email']); ?>" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nomor Telepon</label>
                                <input type="text" name="phone" class="form-control rounded-3 bg-light border-0" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Institusi / Perusahaan</label>
                                <input type="text" name="institution" class="form-control rounded-3 bg-light border-0" value="<?php echo htmlspecialchars($current_user['institution'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-4"><i class="bi bi-shield-lock me-2 text-primary"></i>Keamanan Akun</h6>
                        <p class="text-muted small mb-4">Kosongkan kolom password baru jika Anda tidak ingin menggantinya.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Password Saat Ini</label>
                                <input type="password" name="current_password" class="form-control rounded-3 bg-light border-0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Password Baru</label>
                                <input type="password" name="new_password" class="form-control rounded-3 bg-light border-0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo $back_link; ?>" class="btn btn-light rounded-pill px-4 text-decoration-none">Kembali</a>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>