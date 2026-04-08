<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireRole('penerima');

$page_title = 'Detail Permohonan Siswa';
$current_user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';
$app_id = $_GET['id'] ?? 0;

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($status)) {
        $error = 'Silakan pilih status keputusan terlebih dahulu.';
    } else {
        $query = "UPDATE applications 
                  SET status = :status, notes = :notes, updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id AND penerima_id = :penerima_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':id', $app_id);
        $stmt->bindParam(':penerima_id', $current_user['id']);
        
        if ($stmt->execute()) {
            $success = 'Keputusan berhasil disimpan dan diperbarui!';
        } else {
            $error = 'Terjadi kesalahan sistem saat memperbarui data.';
        }
    }
}

// Get application details
$query = "SELECT a.*, 
          u.name as pemohon_name, u.email as pemohon_email, u.phone as pemohon_phone
          FROM applications a
          JOIN users u ON a.pemohon_id = u.id
          WHERE a.id = :id AND a.penerima_id = :penerima_id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $app_id);
$stmt->bindParam(':penerima_id', $current_user['id']);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    header('Location: penerima-dashboard.php');
    exit;
}

$app = $stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header_user.php';
?>

<style>
    :root {
        --teacher-grad: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        --soft-shadow: 0 10px 30px -10px rgba(0,0,0,0.1);
    }

    body { background-color: #f1f5f9; font-family: 'Plus Jakarta Sans', sans-serif; }

    .card-modern {
        border: none;
        border-radius: 24px;
        background: white;
        box-shadow: var(--soft-shadow);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .card-header-modern {
        background: #fdfdfd;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 700;
        color: #475569;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .detail-label {
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #94a3b8;
        margin-bottom: 6px;
        display: block;
    }

    .detail-value {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1.5rem;
    }

    .description-box {
        background-color: #f8fafc;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid #f1f5f9;
        line-height: 1.7;
        color: #334155;
    }

    .image-container {
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        transition: 0.3s;
    }

    .image-container:hover {
        transform: scale(1.02);
    }

    .status-badge-lg {
        padding: 12px 24px;
        border-radius: 16px;
        font-weight: 800;
        font-size: 0.9rem;
        display: inline-block;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }

    .btn-save {
        background: var(--teacher-grad);
        color: white;
        border: none;
        border-radius: 14px;
        padding: 12px 30px;
        font-weight: 700;
        transition: 0.3s;
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        color: white;
    }

    .form-select-modern, .form-control-modern {
        border-radius: 14px;
        padding: 12px 16px;
        border: 2px solid #f1f5f9;
        font-weight: 600;
    }

    .form-select-modern:focus, .form-control-modern:focus {
        border-color: #6366f1;
        box-shadow: none;
    }

    .breadcrumb-item a { color: #6366f1; font-weight: 700; text-decoration: none; }
    
    .avatar-large {
        width: 60px;
        height: 60px;
        background: var(--teacher-grad);
        color: white;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
    }
</style>

<div class="container py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="penerima-dashboard.php">Beranda</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detail Berkas</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-dark m-0">Periksa Permohonan</h2>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="penerima-dashboard.php" class="btn btn-white bg-white rounded-pill px-4 fw-bold shadow-sm border">
                <i class="bi bi-arrow-left me-2"></i>Kembali
            </a>
        </div>
    </div>

    <?php if ($error || $success): ?>
        <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center p-3">
            <i class="bi <?php echo $success ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> fs-4 me-3"></i>
            <div class="fw-bold"><?php echo $success ?: $error; ?></div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card-modern">
                <div class="card-header-modern">
                    <i class="bi bi-file-text-fill text-primary"></i> Isi Permohonan
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <span class="detail-label">Judul Permohonan</span>
                        <h3 class="fw-bold text-dark"><?php echo htmlspecialchars($app['title']); ?></h3>
                    </div>

                    <div class="mb-4">
                        <span class="detail-label">Penjelasan Detail</span>
                        <div class="description-box">
                            <?php echo nl2br(htmlspecialchars($app['description'])); ?>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-sm-6">
                            <span class="detail-label"><i class="bi bi-geo-alt-fill me-1"></i>Lokasi Kejadian / Terkait</span>
                            <div class="detail-value"><?php echo htmlspecialchars($app['location']); ?></div>
                        </div>
                        <div class="col-sm-6">
                            <span class="detail-label"><i class="bi bi-calendar-check-fill me-1"></i>Waktu Pengiriman</span>
                            <div class="detail-value"><?php echo date('d F Y, H:i', strtotime($app['created_at'])); ?></div>
                        </div>
                    </div>

                    <?php if ($app['image_url']): ?>
                        <div class="mt-2">
                            <span class="detail-label"><i class="bi bi-camera-fill me-1"></i>Lampiran Foto</span>
                            <div class="image-container mt-2">
                                <img src="<?php echo htmlspecialchars($app['image_url']); ?>" alt="Lampiran" class="img-fluid w-100">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-modern border-top border-5 border-primary">
                <div class="card-header-modern">
                    <i class="bi bi-pencil-square text-primary"></i> Berikan Keputusan
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row g-4 mb-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-dark small">Pilih Status Berkas</label>
                                <select class="form-select form-select-modern" name="status" required>
                                    <option value="pending" <?php echo $app['status'] === 'pending' ? 'selected' : ''; ?>>🕒 Menunggu Antrean</option>
                                    <option value="reviewed" <?php echo $app['status'] === 'reviewed' ? 'selected' : ''; ?>>🔍 Sedang Ditinjau</option>
                                    <option value="approved" <?php echo $app['status'] === 'approved' ? 'selected' : ''; ?>>✅ Setujui Permohonan</option>
                                    <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>❌ Tolak Permohonan</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold text-dark small">Catatan Guru ( Feedback untuk Siswa )</label>
                                <textarea class="form-control form-control-modern" name="notes" rows="4" placeholder="Tuliskan arahan atau alasan keputusan Anda di sini agar siswa mengerti..."><?php echo htmlspecialchars($app['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-save w-100 w-md-auto">
                            <i class="bi bi-send-fill me-2"></i>Simpan Keputusan & Kirim Notifikasi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-modern p-4 text-center">
                <span class="detail-label mb-3">Status Saat Ini</span>
                <?php
                $status_cfg = [
                    'pending'  => ['bg-warning', 'MENUNGGU', 'bi-hourglass-split'],
                    'reviewed' => ['bg-info', 'DITINJAU', 'bi-search'],
                    'approved' => ['bg-success', 'DISETUJUI', 'bi-check-all'],
                    'rejected' => ['bg-danger', 'DITOLAK', 'bi-x-lg']
                ];
                $curr = $status_cfg[$app['status']] ?? ['bg-secondary', 'UNKNOWN', 'bi-question'];
                ?>
                <div class="status-badge-lg bg-<?php echo $curr[0]; ?> bg-opacity-10 text-<?php echo $curr[0]; ?>">
                    <i class="bi <?php echo $curr[2]; ?> me-2"></i><?php echo $curr[1]; ?>
                </div>
                <div class="mt-3 text-muted small">
                    <i class="bi bi-clock me-1"></i> Update terakhir:<br>
                    <span class="fw-bold"><?php echo date('d/m/Y H:i', strtotime($app['updated_at'])); ?></span>
                </div>
            </div>

            <div class="card-modern">
                <div class="card-header-modern">
                    <i class="bi bi-person-badge-fill text-primary"></i> Identitas Siswa
                </div>
                <div class="card-body p-4">
                    <div class="avatar-large">
                        <?php echo strtoupper(substr($app['pemohon_name'], 0, 1)); ?>
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($app['pemohon_name']); ?></h5>
                    <p class="text-muted small mb-4">ID Siswa: #<?php echo $app['pemohon_id']; ?></p>

                    <div class="mb-3">
                        <span class="detail-label">Alamat Email</span>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-envelope-at text-primary"></i>
                            <a href="mailto:<?php echo htmlspecialchars($app['pemohon_email']); ?>" class="text-decoration-none fw-bold small text-dark"><?php echo htmlspecialchars($app['pemohon_email']); ?></a>
                        </div>
                    </div>

                    <?php if ($app['pemohon_phone']): ?>
                    <div>
                        <span class="detail-label">Konten Telepon/WA</span>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-whatsapp text-success"></i>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $app['pemohon_phone']); ?>" target="_blank" class="text-decoration-none fw-bold small text-dark">
                                <?php echo htmlspecialchars($app['pemohon_phone']); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light border-0 p-3 text-center">
                    <small class="text-muted fw-bold">Pastikan data siswa sudah valid sebelum menyetujui.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>