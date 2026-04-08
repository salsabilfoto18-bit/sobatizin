<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Pastikan hanya pemohon (Siswa) yang bisa akses
requireRole('pemohon');

$page_title = 'Detail Permohonan Saya';
$current_user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();

$app_id = $_GET['id'] ?? 0;

// Ambil detail permohonan milik user yang sedang login
$query = "SELECT a.*, 
          u.name as penerima_name, u.institution as penerima_instansi
          FROM applications a
          JOIN users u ON a.penerima_id = u.id
          WHERE a.id = :id AND a.pemohon_id = :pemohon_id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $app_id);
$stmt->bindParam(':pemohon_id', $current_user['id']);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    // Jika ID tidak ditemukan atau bukan milik siswa tersebut, lempar ke dashboard
    header('Location: pemohon-dashboard.php');
    exit;
}

$app = $stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header_user.php';
?>

<style>
    :root {
        --student-grad: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
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
    }

    .detail-label {
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #94a3b8;
        margin-bottom: 4px;
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

    .feedback-box {
        background: #fffbeb;
        border-left: 5px solid #f59e0b;
        border-radius: 12px;
        padding: 1.5rem;
    }

    .status-pill-lg {
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 800;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .image-preview {
        border-radius: 16px;
        max-height: 400px;
        object-fit: contain;
        background: #eee;
        width: 100%;
    }
</style>

<div class="container py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="pemohon-dashboard.php" class="text-decoration-none fw-bold" style="color: #4f46e5;">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detail Permohonan</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-dark m-0">Detail Permohonan Saya</h2>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="pemohon-dashboard.php" class="btn btn-white bg-white rounded-pill px-4 fw-bold shadow-sm border">
                <i class="bi bi-arrow-left me-2"></i>Kembali
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-modern">
                <div class="card-header-modern d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-info-circle-fill text-primary me-2"></i>Informasi Pengiriman</span>
                    <span class="text-muted small fw-normal">ID: #<?php echo $app['id']; ?></span>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <span class="detail-label">Judul Permohonan</span>
                        <h4 class="fw-bold text-dark"><?php echo htmlspecialchars($app['title']); ?></h4>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <span class="detail-label">Dikirim Kepada</span>
                            <div class="detail-value text-primary">
                                <i class="bi bi-person-workspace me-1"></i> 
                                <?php echo htmlspecialchars($app['penerima_name']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($app['penerima_instansi']); ?></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <span class="detail-label">Waktu Pengiriman</span>
                            <div class="detail-value text-secondary">
                                <i class="bi bi-clock-history me-1"></i>
                                <?php echo date('d M Y, H:i', strtotime($app['created_at'])); ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <span class="detail-label">Isi / Deskripsi Permohonan</span>
                        <div class="description-box">
                            <?php echo nl2br(htmlspecialchars($app['description'])); ?>
                        </div>
                    </div>

                    <?php if ($app['location']): ?>
                    <div class="mb-4">
                        <span class="detail-label">Lokasi Terkait</span>
                        <div class="detail-value"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?php echo htmlspecialchars($app['location']); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($app['image_url']): ?>
                    <div class="mb-2">
                        <span class="detail-label">Lampiran Gambar</span>
                        <img src="<?php echo htmlspecialchars($app['image_url']); ?>" class="image-preview mt-2 border shadow-sm" alt="Lampiran Siswa">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-modern p-4 mb-4 text-center">
                <span class="detail-label mb-3">Status Keputusan</span>
                <?php
                $status_cfg = [
                    'pending'  => ['bg-warning', 'MENUNGGU', 'bi-hourglass-split'],
                    'reviewed' => ['bg-info', 'DITINJAU', 'bi-search'],
                    'approved' => ['bg-success', 'DISETUJUI', 'bi-check-all'],
                    'rejected' => ['bg-danger', 'DITOLAK', 'bi-x-lg']
                ];
                $curr = $status_cfg[$app['status']] ?? ['bg-secondary', 'UNKNOWN', 'bi-question'];
                ?>
                <div class="status-pill-lg bg-<?php echo $curr[0]; ?> bg-opacity-10 text-<?php echo $curr[0]; ?> mb-3">
                    <i class="bi <?php echo $curr[2]; ?>"></i> <?php echo $curr[1]; ?>
                </div>
                <p class="small text-muted m-0">Terakhir diperbarui:<br><b><?php echo date('d/m/Y H:i', strtotime($app['updated_at'])); ?></b></p>
            </div>

            <div class="card-modern">
                <div class="card-header-modern bg-white">
                    <i class="bi bi-chat-left-dots-fill text-warning me-2"></i> Tanggapan / Feedback
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($app['notes'])): ?>
                        <div class="feedback-box shadow-sm">
                            <p class="fw-bold mb-2 small text-warning text-uppercase">Catatan dari Guru:</p>
                            <div class="text-dark italic" style="font-style: italic;">
                                "<?php echo nl2br(htmlspecialchars($app['notes'])); ?>"
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="bi bi-chat-square-dots text-muted fs-2"></i>
                            <p class="text-muted small mt-2">Belum ada tanggapan atau catatan tambahan dari Guru.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light border-0 p-3">
                    <small class="text-muted d-block text-center italic">
                        <i class="bi bi-info-circle me-1"></i> Jika permohonan ditolak, silakan baca catatan di atas untuk melakukan perbaikan.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>