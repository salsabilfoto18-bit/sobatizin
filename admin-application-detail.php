<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireRole('admin');

$page_title = 'Detail Permohonan';
$current_user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();

$id = $_GET['id'] ?? 0;

// Get application
$query = "SELECT a.*,
          u1.name as pemohon_name,
          u1.email as pemohon_email,
          u1.phone as pemohon_phone,
          u1.institution as pemohon_institution,
          u2.name as penerima_name,
          u2.email as penerima_email
          FROM applications a
          JOIN users u1 ON a.pemohon_id = u1.id
          JOIN users u2 ON a.penerima_id = u2.id
          WHERE a.id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    header('Location: admin-applications.php');
    exit;
}

// Fix Undefined Key & Empty Path
$application['evidence_path'] = $application['evidence_path'] ?? null;

include 'includes/header.php';

$status_class = [
    'pending' => 'warning',
    'reviewed' => 'info',
    'approved' => 'success',
    'rejected' => 'danger'
];
$status_text = [
    'pending' => 'Menunggu',
    'reviewed' => 'Ditinjau',
    'approved' => 'Disetujui',
    'rejected' => 'Ditolak'
];
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        --glass-bg: rgba(255, 255, 255, 0.8);
    }

    body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }

    /* Modern Card Styling */
    .main-card { 
        border: none; 
        border-radius: 24px; 
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);
        background: white;
        overflow: hidden;
    }

    .card-header-modern {
        background: white;
        border-bottom: 1px solid #f1f5f9;
        padding: 1.5rem 2rem;
    }

    /* Soft Badge Styling */
    .badge-soft {
        padding: 8px 16px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .bg-soft-warning { background: #fffbeb; color: #92400e; }
    .bg-soft-success { background: #f0fdf4; color: #166534; }
    .bg-soft-info { background: #eff6ff; color: #1e40af; }
    .bg-soft-danger { background: #fef2f2; color: #991b1b; }

    /* Typography & Info Grid */
    .info-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 6px;
        letter-spacing: 0.5px;
    }
    .info-content {
        color: #1e293b;
        font-weight: 500;
        margin-bottom: 24px;
    }

    /* Buttons */
    .btn-action {
        border-radius: 12px;
        padding: 10px 24px;
        font-weight: 600;
        transition: all 0.2s;
    }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

    /* Avatar & Sidebar */
    .user-avatar-circle {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        background: #f1f5f9;
        color: #475569;
        font-size: 1.2rem;
    }
    
    .evidence-container {
        background: #f8fafc;
        border: 2px dashed #e2e8f0;
        border-radius: 16px;
        padding: 12px;
        transition: border-color 0.3s;
    }
    .evidence-container:hover { border-color: #3b82f6; }
</style>

<div class="container py-5 px-4">
    <div class="row align-items-center mb-5">
        <div class="col-md-7">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="admin-dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="admin-applications.php" class="text-decoration-none text-muted">Permohonan</a></li>
                    <li class="breadcrumb-item active fw-600">Detail #<?php echo $application['id']; ?></li>
                </ol>
            </nav>
            <h2 class="fw-800 text-dark mb-0">Rincian Permohonan</h2>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <div class="d-flex gap-2 justify-content-md-end">
                <button class="btn btn-white btn-action border shadow-sm" onclick="shareWhatsApp()">
                    <i class="bi bi-whatsapp text-success me-2"></i>Bagikan
                </button>
                <a href="print-application.php?id=<?php echo $application['id']; ?>" class="btn btn-primary btn-action shadow-sm text-white" target="_blank">
                    <i class="bi bi-printer me-2"></i>Cetak Data
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card main-card mb-4">
                <div class="card-header-modern d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-light rounded-3 p-2 me-3">
                            <i class="bi bi-file-earmark-text text-primary fs-4"></i>
                        </div>
                        <h5 class="mb-0 fw-bold text-dark">Data Utama</h5>
                    </div>
                    <span class="badge-soft bg-soft-<?php echo $status_class[$application['status']]; ?>">
                        <?php echo $status_text[$application['status']]; ?>
                    </span>
                </div>
                <div class="card-body p-4 p-md-5">
                    <div class="info-label">Judul Permohonan</div>
                    <h3 class="fw-bold text-dark mb-4"><?php echo htmlspecialchars($application['title']); ?></h3>

                    <div class="info-label">Deskripsi Permasalahan / Kebutuhan</div>
                    <p class="text-secondary leading-relaxed fs-5 mb-5" style="white-space: pre-line;">
                        <?php echo htmlspecialchars($application['description']); ?>
                    </p>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="p-4 rounded-4 bg-light border-0">
                                <div class="info-label">Lokasi Kejadian</div>
                                <div class="info-content fs-5 mb-0">
                                    <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                                    <?php echo htmlspecialchars($application['location']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 rounded-4 bg-light border-0">
                                <div class="info-label">Waktu Pengajuan</div>
                                <div class="info-content fs-5 mb-0">
                                    <i class="bi bi-clock-history text-primary me-2"></i>
                                    <?php echo date('d M Y, H:i', strtotime($application['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($application['evidence_path'])): ?>
                    <div class="mt-5">
                        <div class="info-label mb-3">Dokumen Bukti Pendukung</div>
                        <div class="evidence-container text-center">
                            <img src="<?php echo htmlspecialchars($application['evidence_path']); ?>" 
                                 alt="Lampiran" class="img-fluid rounded-4 shadow-sm" style="max-height: 500px;">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card main-card mb-4 border-start border-4 border-primary">
                <div class="card-header-modern pb-0 border-0">
                    <h6 class="info-label">Profil Pemohon</h6>
                </div>
                <div class="card-body pt-2">
                    <div class="d-flex align-items-center mb-4">
                        <div class="user-avatar-circle me-3 bg-primary text-white">
                            <?php echo strtoupper(substr($application['pemohon_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($application['pemohon_name']); ?></h6>
                            <small class="text-muted fw-500"><?php echo htmlspecialchars($application['pemohon_institution'] ?: 'Perorangan'); ?></small>
                        </div>
                    </div>
                    
                    <div class="border-top pt-3">
                        <div class="info-label">Email</div>
                        <p class="info-content small"><i class="bi bi-envelope-at me-2 text-primary"></i><?php echo htmlspecialchars($application['pemohon_email']); ?></p>
                        
                        <?php if ($application['pemohon_phone']): ?>
                        <div class="info-label">Nomor Telepon</div>
                        <p class="info-content small"><i class="bi bi-phone me-2 text-primary"></i><?php echo htmlspecialchars($application['pemohon_phone']); ?></p>
                        
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $application['pemohon_phone']); ?>" 
                           class="btn btn-success btn-sm w-100 rounded-pill py-2 fw-bold shadow-sm" target="_blank">
                            <i class="bi bi-whatsapp me-2"></i>Chat WhatsApp
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card main-card mb-4">
                <div class="card-header-modern pb-0 border-0">
                    <h6 class="info-label">Petugas Penerima</h6>
                </div>
                <div class="card-body pt-2">
                    <div class="d-flex align-items-center p-3 bg-light rounded-4">
                        <div class="user-avatar-circle me-3">
                            <i class="bi bi-person-check-fill text-info"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($application['penerima_name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($application['penerima_email']); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <a href="admin-applications.php" class="btn btn-link text-decoration-none text-muted w-100 text-center fw-600">
                <i class="bi bi-arrow-left me-2"></i>Kembali ke Semua Daftar
            </a>
        </div>
    </div>
</div>

<script>
function shareWhatsApp() {
    const appId = '<?php echo $application['id']; ?>';
    const title = '<?php echo addslashes($application['title']); ?>';
    const pemohon = '<?php echo addslashes($application['pemohon_name']); ?>';
    const status = '<?php echo $status_text[$application['status']]; ?>';

    const message = `*DETAIL PERMOHONAN #${appId}*\n` +
                    `--------------------------\n` +
                    `📌 *Judul:* ${title}\n` +
                    `👤 *Pemohon:* ${pemohon}\n` +
                    `📊 *Status:* ${status}\n\n` +
                    `Cek detail selengkapnya pada Portal Admin.`;

    window.open(`https://wa.me/?text=${encodeURIComponent(message)}`, '_blank');
}
</script>

<?php include 'includes/footer.php'; ?>