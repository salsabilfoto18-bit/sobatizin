<?php
/**
 * Dashboard Pemohon - SOBATIZIN
 * Fitur: Statistik, Filter Status via Card, & Integrasi WhatsApp Admin
 */

require_once 'config/database.php';
require_once 'includes/session.php';

// 1. PROTEKSI AKSES
requireRole('pemohon');

$current_user = getCurrentUser();
$userId = $current_user['id'];

$database = new Database();
$db = $database->getConnection();

// 2. LOGIKA FILTER
// Mengambil filter dari URL parameter 'status'. Jika tidak ada, default ke 'all'
$filter = isset($_GET['status']) ? $_GET['status'] : 'all';

/**
 * Fungsi Helper untuk mengambil jumlah data (Statistik)
 */
function getStatCount($db, $userId, $status = null) {
    $sql = "SELECT COUNT(*) as total FROM applications WHERE pemohon_id = :user_id";
    if ($status && $status !== 'all') {
        $sql .= " AND status = :status";
    }
    
    $stmt = $db->prepare($sql);
    $params = [':user_id' => $userId];
    if ($status && $status !== 'all') $params[':status'] = $status;
    
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

$stats = [
    'all'      => getStatCount($db, $userId, 'all'),
    'pending'  => getStatCount($db, $userId, 'pending'),
    'approved' => getStatCount($db, $userId, 'approved'),
    'rejected' => getStatCount($db, $userId, 'rejected')
];

/**
 * 3. QUERY DATA TABEL (Dinamis berdasarkan filter)
 */
$queryStr = "SELECT a.*, u.name as penerima_name 
             FROM applications a
             JOIN users u ON a.penerima_id = u.id
             WHERE a.pemohon_id = :user_id";

if ($filter !== 'all') {
    $queryStr .= " AND a.status = :status";
}

$queryStr .= " ORDER BY a.created_at DESC LIMIT 10";

$stmt = $db->prepare($queryStr);
$queryParams = [':user_id' => $userId];
if ($filter !== 'all') $queryParams[':status'] = $filter;

$stmt->execute($queryParams);
$recent_apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * 4. KONFIGURASI WHATSAPP
 */
$admin_wa = "6281234567890"; // Ganti dengan nomor WhatsApp Admin sekolah Anda
$wa_text = urlencode("Halo Admin SOBATIZIN, saya " . $current_user['name'] . " ingin bertanya mengenai status permohonan saya.");

include 'includes/header_user.php';
?>

<style>
    :root {
        --student-grad: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        --soft-shadow: 0 10px 30px -10px rgba(0,0,0,0.1);
    }

    body { background-color: #f8fafc; }

    /* Filter Card Interactivity */
    .stat-link { text-decoration: none; color: inherit; display: block; }
    
    .stat-card-modern {
        border: 2px solid transparent !important;
        border-radius: 20px;
        background: white;
        transition: all 0.3s ease;
        box-shadow: var(--soft-shadow);
        cursor: pointer;
    }

    .stat-card-modern:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08);
    }

    /* Penanda Card Aktif */
    .stat-link.active .stat-card-modern {
        border-color: #4f46e5 !important;
        background-color: #f5f3ff;
    }

    /* Floating WhatsApp Button */
    .wa-float {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background-color: #25d366;
        color: white;
        padding: 12px 24px;
        border-radius: 50px;
        font-weight: 700;
        box-shadow: 0 10px 20px rgba(37, 211, 102, 0.3);
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        transition: 0.3s;
    }

    .wa-float:hover {
        background-color: #128c7e;
        color: white;
        transform: scale(1.05);
    }

    /* Welcome Banner & Others */
    .welcome-banner {
        background: var(--student-grad);
        border-radius: 24px;
        padding: 3rem 2.5rem;
        color: white;
        margin-bottom: 2.5rem;
        position: relative;
        overflow: hidden;
    }

    .status-pill {
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
</style>

<div class="container py-4">
    <div class="welcome-banner">
        <div class="row align-items-center position-relative" style="z-index: 2;">
            <div class="col-md-7">
                <span class="badge bg-white bg-opacity-25 text-white mb-3 px-3 py-2 rounded-pill">Panel Siswa</span>
                <h1 class="fw-bold mb-2">Semangat Belajar, <?= explode(' ', trim(htmlspecialchars($current_user['name'])))[0]; ?>! ✨</h1>
                <p class="opacity-75 mb-0">Cek status permohonan izin atau bantuan belajarmu di bawah ini.</p>
            </div>
            <div class="col-md-5 text-md-end mt-4 mt-md-0">
                <a href="pemohon-new.php" class="btn btn-light rounded-pill px-4 py-2 fw-bold text-primary shadow-sm">
                    <i class="bi bi-plus-circle-fill me-2"></i> Buat Permohonan
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <?php
        $card_config = [
            ['all', 'TOTAL', $stats['all'], 'primary', 'bi-folder2-open'],
            ['pending', 'PROSES', $stats['pending'], 'warning', 'bi-clock-history'],
            ['approved', 'DISETUJUI', $stats['approved'], 'success', 'bi-patch-check'],
            ['rejected', 'DITOLAK', $stats['rejected'], 'danger', 'bi-x-circle']
        ];

        foreach ($card_config as $c):
            $isActive = ($filter === $c[0]);
        ?>
        <div class="col-md-3 col-6">
            <a href="?status=<?= $c[0] ?>" class="stat-link <?= $isActive ? 'active' : '' ?>">
                <div class="card stat-card-modern p-2 h-100">
                    <div class="card-body">
                        <div class="icon-box bg-<?= $c[3] ?> bg-opacity-10 text-<?= $c[3] ?> mb-3" style="width:50px; height:50px; display:flex; align-items:center; justify-content:center; border-radius:12px; font-size:1.5rem;">
                            <i class="bi <?= $c[4] ?>"></i>
                        </div>
                        <p class="text-muted small fw-bold mb-1"><?= $c[1] ?></p>
                        <h3 class="fw-bold m-0"><?= $c[2] ?></h3>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="fw-bold m-0">Riwayat <?= $filter !== 'all' ? ucfirst($filter) : 'Terakhir' ?></h4>
                <?php if($filter !== 'all'): ?>
                    <a href="?" class="btn btn-sm btn-light rounded-pill px-3 border text-muted small">Hapus Filter</a>
                <?php endif; ?>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3 text-muted small fw-bold">DETAIL PERMOHONAN</th>
                                <th class="text-muted small fw-bold">PENERIMA</th>
                                <th class="text-muted small fw-bold">STATUS</th>
                                <th class="text-end pe-4 text-muted small fw-bold">OPSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_apps)): foreach ($recent_apps as $app): 
                                $status_cfg = [
                                    'pending'  => ['warning', 'Menunggu', 'bi-hourglass-split'],
                                    'approved' => ['success', 'Disetujui', 'bi-check-all'],
                                    'rejected' => ['danger', 'Ditolak', 'bi-x-lg'],
                                    'reviewed' => ['info', 'Ditinjau', 'bi-search']
                                ];
                                $s = $status_cfg[$app['status']] ?? ['secondary', 'N/A', 'bi-question'];
                            ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($app['title']); ?></div>
                                    <div class="text-muted small">
                                        <i class="bi bi-calendar-event me-1"></i> <?= date('d M Y', strtotime($app['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-semibold">
                                        <i class="bi bi-person-badge me-1 text-primary"></i> <?= htmlspecialchars($app['penerima_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-pill bg-<?= $s[0]; ?> bg-opacity-10 text-<?= $s[0]; ?>">
                                        <i class="bi <?= $s[2]; ?>"></i> <?= $s[1]; ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="pemohon-detail.php?id=<?= $app['id']; ?>" class="btn btn-sm btn-light rounded-pill px-3 fw-bold border text-primary">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <i class="bi bi-inbox text-muted fs-1 opacity-25"></i>
                                    <h6 class="mt-3 text-muted">Tidak ada data untuk status ini</h6>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<a href="https://wa.me/<?= $admin_wa ?>?text=<?= $wa_text ?>" target="_blank" class="wa-float">
    <i class="bi bi-whatsapp"></i>
    <span>Hubungi Admin</span>
</a>

<?php include 'includes/footer.php'; ?>