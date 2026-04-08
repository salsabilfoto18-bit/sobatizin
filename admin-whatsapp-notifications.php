<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireRole('admin');

$page_title = 'Notifikasi WhatsApp';
$current_user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();

// Get statistics
$query = "SELECT COUNT(*) as total FROM applications WHERE status = 'pending'";
$pending_apps = $db->query($query)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$query = "SELECT COUNT(*) as total FROM applications WHERE status = 'approved'";
$approved_apps = $db->query($query)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$query = "SELECT COUNT(*) as total FROM users WHERE role = 'pemohon'";
$total_pemohon = $db->query($query)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

include 'includes/header.php';
?>

<style>
    .stat-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        border-radius: 15px;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .template-card {
        border-radius: 12px;
        border-left: 5px solid #198754;
        transition: all 0.2s;
    }
    .template-card:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
    .stat-icon {
        font-size: 2.5rem;
        opacity: 0.3;
    }
    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
    }
    .breadcrumb {
        background: transparent;
        padding: 0;
    }
</style>

<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin-dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">WhatsApp Panel</li>
                </ol>
            </nav>
            <h2 class="fw-bold">
                <i class="bi bi-whatsapp text-success me-2"></i>Notifikasi WhatsApp
            </h2>
        </div>
        <div class="col-md-6 text-md-end">
             <span class="badge bg-light text-dark border p-2">
                <i class="bi bi-calendar3 me-1"></i> <?php echo date('d M Y'); ?>
             </span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-white shadow-sm border-start border-warning border-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 fw-medium">Permohonan Pending</p>
                        <h3 class="stat-value text-warning mb-0"><?php echo $pending_apps; ?></h3>
                    </div>
                    <i class="bi bi-hourglass-split stat-icon text-warning"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-white shadow-sm border-start border-success border-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 fw-medium">Permohonan Disetujui</p>
                        <h3 class="stat-value text-success mb-0"><?php echo $approved_apps; ?></h3>
                    </div>
                    <i class="bi bi-check2-circle stat-icon text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-white shadow-sm border-start border-info border-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 fw-medium">Total Pemohon</p>
                        <h3 class="stat-value text-info mb-0"><?php echo $total_pemohon; ?></h3>
                    </div>
                    <i class="bi bi-people stat-icon text-info"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i class="bi bi-card-checklist me-2 text-success"></i>Pilih Template Pesan
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div onclick="sendNotificationTemplate('pending')" class="list-group-item p-4 template-card border-start-warning">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold mb-1">Konfirmasi Penerimaan</h6>
                                    <p class="text-muted small mb-0">Beritahu pemohon bahwa berkas sedang diperiksa oleh tim admin.</p>
                                </div>
                                <span class="badge rounded-pill bg-warning text-dark px-3"><?php echo $pending_apps; ?> Data</span>
                            </div>
                        </div>

                        <div onclick="sendNotificationTemplate('approved')" class="list-group-item p-4 template-card border-start-success">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold mb-1 text-success">Pengumuman Kelulusan/Persetujuan</h6>
                                    <p class="text-muted small mb-0">Kirim kabar gembira bahwa permohonan telah disetujui sistem.</p>
                                </div>
                                <span class="badge rounded-pill bg-success px-3"><?php echo $approved_apps; ?> Data</span>
                            </div>
                        </div>

                        <div onclick="sendNotificationTemplate('reminder')" class="list-group-item p-4 template-card border-start-info">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold mb-1 text-info">Internal Reminder (Petugas)</h6>
                                    <p class="text-muted small mb-0">Ingatkan petugas/penerima untuk segera memproses antrean.</p>
                                </div>
                                <i class="bi bi-bell-fill text-info"></i>
                            </div>
                        </div>

                        <div onclick="sendCustomNotification()" class="list-group-item p-4 template-card border-start-primary" style="border-left-color: #0d6efd !important;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold mb-1 text-primary">Pesan Kustom Mandiri</h6>
                                    <p class="text-muted small mb-0">Tulis pesan bebas sesuai kebutuhan mendesak lainnya.</p>
                                </div>
                                <i class="bi bi-pencil-square text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Panduan Pengiriman</h5>
                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <span class="badge bg-success rounded-circle" style="width: 25px; height: 25px;">1</span>
                        </div>
                        <div>
                            <p class="mb-0 small">Pilih template yang sesuai dengan status permohonan.</p>
                        </div>
                    </div>
                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <span class="badge bg-success rounded-circle" style="width: 25px; height: 25px;">2</span>
                        </div>
                        <div>
                            <p class="mb-0 small">Ganti teks di dalam kurung siku seperti <strong>[Nama Pemohon]</strong> secara manual di WhatsApp.</p>
                        </div>
                    </div>
                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <span class="badge bg-success rounded-circle" style="width: 25px; height: 25px;">3</span>
                        </div>
                        <div>
                            <p class="mb-0 small">Gunakan <strong>WhatsApp Web</strong> untuk pengalaman yang lebih stabil di desktop.</p>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning border-0 shadow-sm mt-4">
                        <div class="d-flex">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div>
                                <h6 class="alert-heading fw-bold mb-1">Penting!</h6>
                                <p class="mb-0 small">Sistem ini menggunakan metode <em>Deep Link</em>. Pastikan nomor tujuan menyertakan kode negara (contoh: 62812...).</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Logic JavaScript tetap sama dengan sedikit perbaikan format template agar lebih rapi
function sendNotificationTemplate(type) {
    let message = '';
    const dateStr = new Date().toLocaleDateString('id-ID', { 
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
    });

    switch(type) {
        case 'pending':
            message = `*🔔 NOTIFIKASI SISTEM PERMOHONAN*\n\n` +
                      `Halo *[Nama Pemohon]**,\n\n` +
                      `Permohonan Anda telah kami terima pada:\n` +
                      `📅 Tgl: ${dateStr}\n` +
                      `📊 Status: *DALAM PROSES REVIEW*\n\n` +
                      `Mohon menunggu informasi selanjutnya. Anda dapat mengecek detail di dashboard sistem.\n\n` +
                      `Terima kasih.`;
            break;

        case 'approved':
            message = `*✅ PERMOHONAN DISETUJUI*\n\n` +
                      `Selamat *[Nama Pemohon]**,\n\n` +
                      `Berdasarkan hasil verifikasi, permohonan Anda dinyatakan:\n` +
                      `🏆 *DISETUJUI / LOLOS*\n\n` +
                      `Silakan lengkapi langkah selanjutnya melalui tautan di dashboard kami.\n\n` +
                      `Salam Hangat,\n` +
                      `*Admin Sistem*`;
            break;

        case 'reminder':
            message = `*⏰ PENGINGAT PETUGAS*\n\n` +
                      `Halo *[Nama Petugas]*,\n\n` +
                      `Terdapat antrean permohonan yang perlu diproses:\n` +
                      `⚠️ Total Pending: *<?php echo $pending_apps; ?> berkas*\n\n` +
                      `Mohon segera selesaikan sebelum batas waktu harian.\n` +
                      `Link Dashboard: [LinkSistem Anda]`;
            break;
    }

    if(message) {
        const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank');
    }
}

function sendCustomNotification() {
    const customMessage = prompt('Tulis pesan kustom:');
    if (customMessage && customMessage.trim() !== '') {
        const message = `*📢 INFO SISTEM*\n\n${customMessage}\n\n_Diteruskan secara otomatis oleh sistem_`;
        const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank');
    }
}
</script>

<?php include 'includes/footer.php'; ?>