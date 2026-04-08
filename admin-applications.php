<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireRole('admin');

$page_title = 'Semua Permohonan';
$current_user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();

// --- LOGIKA FILTER & PENCARIAN ---
$filter_status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Perbaikan Query: Menggunakan LEFT JOIN agar data tetap tampil meski penerima_id kosong
$query = "SELECT a.*, 
u1.name as pemohon_name, 
u2.name as penerima_name 
FROM applications a 
JOIN users u1 ON a.pemohon_id = u1.id 
LEFT JOIN users u2 ON a.penerima_id = u2.id 
WHERE 1=1";

if ($filter_status) {
    $query .= " AND a.status = :status";
}

if ($search) {
    $query .= " AND (a.title LIKE :search OR a.description LIKE :search OR u1.name LIKE :search)";
}

$query .= " ORDER BY a.created_at DESC";

$stmt = $db->prepare($query);

if ($filter_status) {
    $stmt->bindParam(':status', $filter_status);
}

if ($search) {
    $search_term = "%{$search}%";
    $stmt->bindParam(':search', $search_term);
}

$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- HITUNG STATISTIK STATUS ---
$query_count = "SELECT status, COUNT(*) as count FROM applications GROUP BY status";
$status_counts = [];
$raw_counts = $db->query($query_count)->fetchAll(PDO::FETCH_ASSOC);
foreach ($raw_counts as $row) {
    $status_counts[$row['status']] = $row['count'];
}
$total_all = array_sum($status_counts);

include 'includes/header.php';
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
    }

    body { 
        background-color: #f8fafc; 
        font-family: 'Inter', sans-serif; 
    }

    .stat-card {
        border: none;
        border-radius: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 2px solid transparent;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
    }
    .stat-card.active-all { border-color: #4f46e5; background: #eef2ff; }
    .stat-card.active-pending { border-color: #f59e0b; background: #fffbeb; }
    .stat-card.active-approved { border-color: #10b981; background: #ecfdf5; }
    .stat-card.active-rejected { border-color: #ef4444; background: #fef2f2; }

    .search-container {
        background: white;
        border-radius: 15px;
        padding: 8px 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .search-container input {
        border: none;
        box-shadow: none !important;
        font-size: 0.95rem;
        background: transparent;
    }

    .card-table {
        border: none;
        border-radius: 24px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);
        background: white;
        overflow: hidden;
    }
    .table thead th {
        background: #f8fafc;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        color: #64748b;
        padding: 1.25rem;
        border: none;
    }
    .table tbody td {
        padding: 1.25rem;
        vertical-align: middle;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }

    .badge-soft {
        padding: 6px 12px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.8rem;
        display: inline-block;
    }
    .bg-soft-warning { background: #fef3c7; color: #92400e; }
    .bg-soft-success { background: #dcfce7; color: #166534; }
    .bg-soft-danger { background: #fee2e2; color: #991b1b; }
    .bg-soft-info { background: #e0f2fe; color: #075985; }

    .btn-action {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
        background: #f8fafc;
        color: #64748b;
        border: 1px solid #e2e8f0;
        text-decoration: none;
    }
    .btn-action:hover {
        background: #4f46e5;
        color: white;
        border-color: #4f46e5;
        transform: scale(1.05);
    }
</style>

<div class="container-fluid py-4 px-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="admin-dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Permohonan</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-dark mb-0">Manajemen Permohonan</h2>
            <p class="text-muted small">Pantau dan kelola semua status surat izin keluar.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <a href="admin-applications.php" class="text-decoration-none">
                <div class="stat-card p-3 <?php echo !$filter_status ? 'active-all' : ''; ?>">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded-4 me-3">
                            <i class="bi bi-collection text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted small mb-1">Semua </h6>
                            <h4 class="fw-bold mb-0 text-dark"><?php echo $total_all; ?></h4>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="admin-applications.php?status=pending" class="text-decoration-none">
                <div class="stat-card p-3 <?php echo $filter_status === 'pending' ? 'active-pending' : ''; ?>">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded-4 me-3">
                            <i class="bi bi-hourglass-split text-warning fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted small mb-1">Menunggu</h6>
                            <h4 class="fw-bold mb-0 text-dark"><?php echo $status_counts['pending'] ?? 0; ?></h4>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="admin-applications.php?status=approved" class="text-decoration-none">
                <div class="stat-card p-3 <?php echo $filter_status === 'approved' ? 'active-approved' : ''; ?>">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-success bg-opacity-10 p-3 rounded-4 me-3">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted small mb-1">Disetujui</h6>
                            <h4 class="fw-bold mb-0 text-dark"><?php echo $status_counts['approved'] ?? 0; ?></h4>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="admin-applications.php?status=rejected" class="text-decoration-none">
                <div class="stat-card p-3 <?php echo $filter_status === 'rejected' ? 'active-rejected' : ''; ?>">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-danger bg-opacity-10 p-3 rounded-4 me-3">
                            <i class="bi bi-x-circle text-danger fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted small mb-1">Ditolak</h6>
                            <h4 class="fw-bold mb-0 text-dark"><?php echo $status_counts['rejected'] ?? 0; ?></h4>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" class="search-container ms-auto">
                <i class="bi bi-search text-muted"></i>
                <?php if ($filter_status): ?>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                <?php endif; ?>
                <input type="text" name="search" class="form-control px-0" placeholder="Cari pemohon atau judul permohonan..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>
    </div>

    <div class="card card-table shadow-sm">
        <div class="card-body p-0">
            <?php if (count($applications) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">ID & Tanggal</th>
                                <th>Informasi Pemohon</th>
                                <th>Detail </th>
                                <th>Penerima</th>
                                <th>Status</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): 
                                $status_style = [
                                    'pending' => ['bg' => 'bg-soft-warning', 'text' => 'Menunggu'],
                                    'approved' => ['bg' => 'bg-soft-success', 'text' => 'Disetujui'],
                                    'rejected' => ['bg' => 'bg-soft-danger', 'text' => 'Ditolak'],
                                    'reviewed' => ['bg' => 'bg-soft-info', 'text' => 'Ditinjau']
                                ];
                                $s = $status_style[$app['status']] ?? ['bg' => 'bg-light', 'text' => $app['status']];
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark">#<?php echo $app['id']; ?></div>
                                        <div class="text-muted small"><?php echo date('d M Y', strtotime($app['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                <i class="bi bi-person text-secondary"></i>
                                            </div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($app['pemohon_name']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small"><?php echo htmlspecialchars($app['title']); ?></div>
                                        <div class="text-muted small text-truncate" style="max-width: 180px;">
                                            <?php echo htmlspecialchars($app['description']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small"><i class="bi bi-person-check me-1"></i><?php echo htmlspecialchars($app['penerima_name'] ?? 'Belum Diproses'); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge-soft <?php echo $s['bg']; ?>">
                                            <?php echo $s['text']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="admin-application-detail.php?id=<?php echo $app['id']; ?>" class="btn-action" title="Lihat Detail">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                            <a href="javascript:void(0);" onclick="print(<?php echo $app['id']; ?>)" class="btn-action" title="Cetak ">
                                                <i class="bi bi-printer-fill text-success"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <img src="assets/img/empty-data.svg" alt="Empty" style="width: 120px; opacity: 0.5;">
                    <h5 class="text-muted mt-3">Tidak Ada Permohonan</h5>
                    <p class="text-secondary small">Data permohonan tidak ditemukan dengan filter saat ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function print(id) {
    // PERUBAHAN: Mengarahkan ke print-surat.php sesuai permintaan Anda
    const printWindow = window.open('print-surat.php?id=' + id, '_blank');
    
    printWindow.onload = function() {
        // Otomatis memicu dialog print di tab baru
        printWindow.print();
    };
}
</script>

<?php include 'includes/footer.php'; ?>