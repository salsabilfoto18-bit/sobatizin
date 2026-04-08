<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Pastikan hanya pemohon (siswa) yang bisa akses
requireRole('pemohon');

$page_title = 'Buat Permohonan';
$current_user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Ambil daftar penerima (Guru/Staff)
$query = "SELECT id, name, institution FROM users WHERE role = 'penerima' ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$penerima_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $penerima_id = $_POST['penerima_id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $image_url = null;
    
    if (empty($penerima_id) || empty($title) || empty($description) || empty($location)) {
        $error = 'Harap isi semua kolom yang bertanda bintang (*)';
    } else {
        // Logika Upload File
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext) && $_FILES['image']['size'] <= 5000000) {
                $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
                $target_file = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = $target_file;
                }
            }
        }
        
        $query = "INSERT INTO applications 
                  (pemohon_id, penerima_id, title, description, location, image_url, status) 
                  VALUES 
                  (:pemohon_id, :penerima_id, :title, :description, :location, :image_url, 'pending')";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':pemohon_id' => $current_user['id'],
            ':penerima_id' => $penerima_id,
            ':title' => $title,
            ':description' => $description,
            ':location' => $location,
            ':image_url' => $image_url
        ]);
        
        if ($stmt) {
            $success = 'Permohonan berhasil dikirim ke guru tujuan!';
            $_POST = []; // Reset form
        } else {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}

include 'includes/header_user.php'; // Gunakan header yang sama dengan dashboard
?>

<style>
    :root {
        --student-grad: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        --soft-shadow: 0 10px 30px -10px rgba(0,0,0,0.1);
    }
    body { background-color: #f1f5f9; }

    .header-banner {
        background: var(--student-grad);
        border-radius: 24px;
        padding: 2.5rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 20px 40px -15px rgba(79, 70, 229, 0.3);
    }

    .glass-card {
        background: white;
        border: none;
        border-radius: 24px;
        box-shadow: var(--soft-shadow);
        overflow: hidden;
    }

    .form-label {
        font-weight: 700;
        font-size: 0.85rem;
        color: #475569;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-control, .form-select {
        border-radius: 12px;
        padding: 0.75rem 1rem;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
    }

    .form-control:focus, .form-select:focus {
        background-color: white;
        border-color: #4f46e5;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }

    .image-preview-container {
        width: 100%;
        max-width: 300px;
        border-radius: 16px;
        overflow: hidden;
        margin-top: 1rem;
        border: 2px dashed #e2e8f0;
        display: none;
    }

    .btn-submit {
        background: var(--student-grad);
        border: none;
        border-radius: 12px;
        padding: 0.8rem 2rem;
        font-weight: 700;
        color: white;
        transition: 0.3s;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        color: white;
    }

    .sidebar-info {
        border-radius: 20px;
        border: none;
    }

    .info-item {
        display: flex;
        gap: 12px;
        margin-bottom: 1.2rem;
    }

    .info-icon {
        width: 32px;
        height: 32px;
        background: rgba(79, 70, 229, 0.1);
        color: #4f46e5;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
</style>

<div class="container py-4">
    <div class="header-banner">
        <div class="d-flex align-items-center gap-3 mb-2">
            <a href="pemohon-dashboard.php" class="btn btn-sm btn-white bg-white bg-opacity-20 text-white rounded-circle">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h2 class="fw-bold m-0">Buat Permohonan</h2>
        </div>
        <p class="opacity-75 mb-0 ms-5">Isi formulir di bawah untuk mengajukan izin atau bantuan kepada guru.</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="glass-card p-4 p-md-5">
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 rounded-4 mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success border-0 rounded-4 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-check-circle-fill fs-4"></i>
                            <div>
                                <div class="fw-bold">Berhasil!</div>
                                <?php echo $success; ?>
                            </div>
                        </div>
                        <hr>
                        <a href="pemohon-dashboard.php" class="btn btn-sm btn-success rounded-pill px-3">Ke Dashboard</a>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label"><i class="bi bi-person-badge-fill text-primary"></i> Pilih Guru Tujuan *</label>
                            <select class="form-select" name="penerima_id" required>
                                <option value="">-- Pilih Guru --</option>
                                <?php foreach ($penerima_list as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo (isset($_POST['penerima_id']) && $_POST['penerima_id'] == $p['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['name']); ?> (<?php echo htmlspecialchars($p['institution']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label class="form-label"><i class="bi bi-bookmark-star-fill text-primary"></i> Judul Permohonan *</label>
                            <input type="text" class="form-control" name="title" placeholder="Misal: Izin Sakit 2 Hari" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                        </div>

                        <div class="col-12 mb-4">
                            <label class="form-label"><i class="bi bi-chat-left-text-fill text-primary"></i> Deskripsi / Alasan Lengkap *</label>
                            <textarea class="form-control" name="description" rows="5" placeholder="Tuliskan detail permohonan Anda..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-12 mb-4">
                            <label class="form-label"><i class="bi bi-geo-alt-fill text-primary"></i> Lokasi / Alamat Saat Ini *</label>
                            <input type="text" class="form-control" name="location" placeholder="Masukkan alamat lengkap Anda" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" required>
                        </div>

                        <div class="col-12 mb-4">
                            <label class="form-label"><i class="bi bi-image-fill text-primary"></i> Lampiran Bukti (Opsional)</label>
                            <div class="p-3 border rounded-4 bg-light">
                                <input type="file" class="form-control border-0 bg-transparent" name="image" accept="image/*" onchange="previewImage(this)">
                                <div id="previewContainer" class="image-preview-container">
                                    <img id="imagePreview" src="#" alt="Preview" class="img-fluid">
                                </div>
                                <small class="text-muted d-block mt-2">Format: JPG, PNG (Maks 5MB)</small>
                            </div>
                        </div>

                        <div class="col-12 text-end">
                            <a href="pemohon-dashboard.php" class="btn btn-link text-muted fw-bold text-decoration-none me-3">Batal</a>
                            <button type="submit" class="btn btn-submit">
                                Kirim Permohonan <i class="bi bi-send-fill ms-2"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card glass-card sidebar-info p-4">
                <h5 class="fw-bold mb-4">Panduan Pengajuan</h5>
                
                <div class="info-item">
                    <div class="info-icon"><i class="bi bi-1-circle"></i></div>
                    <p class="small mb-0 text-muted">Pastikan memilih <b>Guru/Staff</b> yang tepat sesuai keperluan Anda.</p>
                </div>

                <div class="info-item">
                    <div class="info-icon"><i class="bi bi-2-circle"></i></div>
                    <p class="small mb-0 text-muted">Gunakan bahasa yang <b>sopan dan jelas</b> pada bagian deskripsi.</p>
                </div>

                <div class="info-item">
                    <div class="info-icon"><i class="bi bi-3-circle"></i></div>
                    <p class="small mb-0 text-muted">Lampirkan foto (surat keterangan dokter/foto lokasi) untuk <b>mempercepat persetujuan</b>.</p>
                </div>

                <div class="alert bg-warning bg-opacity-10 border-0 rounded-4 mt-3">
                    <div class="d-flex gap-2">
                        <i class="bi bi-info-circle-fill text-warning"></i>
                        <p class="small text-dark mb-0">Permohonan yang sudah dikirim <b>tidak dapat diubah</b>. Cek kembali sebelum mengirim.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    const container = document.getElementById('previewContainer');
    const preview = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        container.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>