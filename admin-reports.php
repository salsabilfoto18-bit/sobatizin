<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireRole('admin');

$page_title = 'Laporan Permohonan';
$current_user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$status = $_GET['status'] ?? '';
$search_user = $_GET['search_user'] ?? '';

// Build query
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
          WHERE 1=1";

$params = [];

if ($date_from) {
    $query .= " AND DATE(a.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(a.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

if ($status) {
    $query .= " AND a.status = :status";
    $params[':status'] = $status;
}

if ($search_user) {
    $query .= " AND (u1.name LIKE :search OR u1.email LIKE :search OR u1.phone LIKE :search OR u1.institution LIKE :search)";
    $params[':search'] = "%{$search_user}%";
}

$query .= " ORDER BY a.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_apps = count($applications);
$stats_by_status = [
    'pending' => 0,
    'reviewed' => 0,
    'approved' => 0,
    'rejected' => 0
];

foreach ($applications as $app) {
    $stats_by_status[$app['status']]++;
}

include 'includes/header.php';
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
    }

    body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }

    /* Stat Cards Modern */
    .stat-card {
        border: none;
        border-radius: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 2px solid transparent;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
    }
    .stat-card.active-total { border-color: #4f46e5; background: #eef2ff; }
    .stat-card.active-pending { border-color: #f59e0b; background: #fffbeb; }
    .stat-card.active-approved { border-color: #10b981; background: #ecfdf5; }
    .stat-card.active-rejected { border-color: #ef4444; background: #fef2f2; }

    /* Filter & Search Box */
    .filter-card {
        border: none;
        border-radius: 24px;
        background: white;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }

    /* Table Customization */
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
        border-top: none;
    }
    .table tbody td {
        padding: 1.25rem;
        vertical-align: middle;
        color: #334155;
    }

    /* Status Badges Soft */
    .badge-soft {
        padding: 6px 12px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.8rem;
    }
    .bg-soft-warning { background: #fef3c7; color: #92400e; }
    .bg-soft-info { background: #e0f2fe; color: #075985; }
    .bg-soft-success { background: #dcfce7; color: #166534; }
    .bg-soft-danger { background: #fee2e2; color: #991b1b; }

    .form-control, .form-select {
        border-radius: 12px;
        padding: 0.6rem 1rem;
        border: 1px solid #e2e8f0;
    }
    .form-control:focus { border-color: #4f46e5; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
    
    .btn-rounded { border-radius: 12px; }
</style>

<div class="container-fluid py-4 px-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="admin-dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Laporan</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-dark mb-0">Laporan Permohonan</h2>
            <p class="text-muted small">Analisis data dan cetak rekapitulasi permohonan.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <?php if (count($applications) > 0): ?>
                <button type="button" class="btn btn-success px-4 py-2 fw-600 rounded-pill shadow-sm" onclick="printReport()">
                    <i class="bi bi-printer-fill me-2"></i> Cetak Laporan
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card p-3 active-total">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded-4 me-3">
                        <i class="bi bi-files text-primary fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Total Laporan</h6>
                        <h4 class="fw-bold mb-0 text-dark"><?php echo $total_apps; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card p-3 active-pending">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded-4 me-3">
                        <i class="bi bi-clock-history text-warning fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Menunggu</h6>
                        <h4 class="fw-bold mb-0 text-dark"><?php echo $stats_by_status['pending']; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card p-3 active-approved">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success bg-opacity-10 p-3 rounded-4 me-3">
                        <i class="bi bi-check-circle text-success fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Disetujui</h6>
                        <h4 class="fw-bold mb-0 text-dark"><?php echo $stats_by_status['approved']; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card p-3 active-rejected">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-danger bg-opacity-10 p-3 rounded-4 me-3">
                        <i class="bi bi-x-circle text-danger fs-4"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Ditolak</h6>
                        <h4 class="fw-bold mb-0 text-dark"><?php echo $stats_by_status['rejected']; ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card filter-card mb-4">
        <div class="card-body p-4">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Dari Tanggal</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        <div class="btn-group w-100 mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setToday()">Hari Ini</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setThisMonth()">Bulan Ini</button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="reviewed" <?php echo $status === 'reviewed' ? 'selected' : ''; ?>>Ditinjau</option>
                            <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Disetujui</option>
                            <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Cari Pemohon</label>
                        <div class="input-group">
                            <input type="text" name="search_user" class="form-control" placeholder="Nama, email, atau institusi..." value="<?php echo htmlspecialchars($search_user); ?>">
                            <button class="btn btn-primary px-3" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            <a href="admin-reports.php" class="btn btn-outline-secondary px-3">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        </div>
                    </div>
                </div>
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
                                <th>ID</th>
                                <th>Waktu Masuk</th>
                                <th>Informasi Pemohon</th>
                                <th>Judul Permohonan</th>
                                <th>Penerima</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><span class="text-muted fw-bold">#<?php echo $app['id']; ?></span></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo date('d M Y', strtotime($app['created_at'])); ?></div>
                                        <div class="text-muted small"><?php echo date('H:i', strtotime($app['created_at'])); ?> WIB</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($app['pemohon_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($app['pemohon_institution'] ?? '-'); ?></div>
                                    </td>
                                    <td>
                                        <div class="text-dark fw-600" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($app['title']); ?>
                                        </div>
                                        <div class="text-muted small">Klik detail untuk info lengkap</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($app['penerima_name']); ?></div>
                                        <div class="text-muted small">Admin Panel</div>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $s_class = [
                                            'pending' => 'warning',
                                            'reviewed' => 'info',
                                            'approved' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $s_text = [
                                            'pending' => 'Menunggu',
                                            'reviewed' => 'Ditinjau',
                                            'approved' => 'Disetujui',
                                            'rejected' => 'Ditolak'
                                        ];
                                        ?>
                                        <span class="badge-soft bg-soft-<?php echo $s_class[$app['status']]; ?>">
                                            <?php echo $s_text[$app['status']]; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-x text-muted display-1"></i>
                    <h5 class="text-muted mt-3">Tidak Ada Data Ditemukan</h5>
                    <p class="text-secondary small">Sesuaikan filter tanggal atau kata kunci pencarian Anda.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function printReport() {
    const params = new URLSearchParams(window.location.search);
    const printUrl = 'admin-print-report.php?' + params.toString();
    window.open(printUrl, '_blank');
}

function setToday() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('date_from').value = today;
    document.getElementById('date_to').value = today;
}

function setThisMonth() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    document.getElementById('date_from').value = firstDay.toISOString().split('T')[0];
    document.getElementById('date_to').value = lastDay.toISOString().split('T')[0];
}
</script>

<?php include 'includes/footer.php'; ?>