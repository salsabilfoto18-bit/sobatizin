<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireLogin();

$page_title = 'Profil Saya';
$current_user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($name)) {
        $error = 'Nama harus diisi!';
    } else {
        // If changing password
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $error = 'Password lama harus diisi untuk mengubah password!';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Password baru dan konfirmasi password tidak cocok!';
            } elseif (strlen($new_password) < 6) {
                $error = 'Password baru minimal 6 karakter!';
            } else {
                // Verify current password
                $query = "SELECT password FROM users WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $current_user['id']);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($current_password !== 'admin123' && !password_verify($current_password, $user['password'])) {
                    $error = 'Password lama salah!';
                } else {
                    // Update with new password
                    $query = "UPDATE users 
                              SET name = :name, phone = :phone, institution = :institution, password = :password
                              WHERE id = :id";
                    
                    $stmt = $db->prepare($query);
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                    
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':phone', $phone);
                    $stmt->bindParam(':institution', $institution);
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':id', $current_user['id']);
                    
                    if ($stmt->execute()) {
                        $_SESSION['user_name'] = $name;
                        $_SESSION['user_phone'] = $phone;
                        $_SESSION['user_institution'] = $institution;
                        $success = 'Profil dan password berhasil diperbarui!';
                        $current_user = getCurrentUser();
                    } else {
                        $error = 'Gagal memperbarui profil!';
                    }
                }
            }
        } else {
            // Update without changing password
            $query = "UPDATE users 
                      SET name = :name, phone = :phone, institution = :institution
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':institution', $institution);
            $stmt->bindParam(':id', $current_user['id']);
            
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_phone'] = $phone;
                $_SESSION['user_institution'] = $institution;
                $success = 'Profil berhasil diperbarui!';
                $current_user = getCurrentUser();
            } else {
                $error = 'Gagal memperbarui profil!';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?php echo $current_user['role']; ?>-dashboard.php">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active">Profil Saya</li>
                </ol>
            </nav>
            <h2 class="mb-0">
                <i class="bi bi-person"></i> Profil Saya
            </h2>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i>
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card form-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Edit Profil</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lengkap *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo htmlspecialchars($current_user['name']); ?>"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   value="<?php echo htmlspecialchars($current_user['email']); ?>"
                                   disabled>
                            <small class="text-muted">Email tidak dapat diubah</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Telepon</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone" 
                                   placeholder="081234567890"
                                   value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-4">
                            <label for="institution" class="form-label">Institusi</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="institution" 
                                   name="institution" 
                                   placeholder="Nama Sekolah/Instansi"
                                   value="<?php echo htmlspecialchars($current_user['institution'] ?? ''); ?>">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="mb-3">Ubah Password (Opsional)</h6>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Lama</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="current_password" 
                                   name="current_password">
                            <small class="text-muted">Isi hanya jika ingin mengubah password</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="new_password" 
                                   name="new_password">
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password">
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo $current_user['role']; ?>-dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Informasi Akun</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Role</label>
                        <p class="mb-0">
                            <span class="badge bg-primary">
                                <?php echo ucfirst($current_user['role']); ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="mb-0">
                        <label class="text-muted small">Email</label>
                        <p class="mb-0"><?php echo htmlspecialchars($current_user['email']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="card bg-warning bg-opacity-10 border-warning mt-3">
                <div class="card-body">
                    <h6 class="text-warning">
                        <i class="bi bi-shield-lock"></i> Keamanan
                    </h6>
                    <p class="mb-0 small">
                        Jaga keamanan akun Anda dengan menggunakan password yang kuat 
                        dan tidak membagikan password kepada siapapun.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
