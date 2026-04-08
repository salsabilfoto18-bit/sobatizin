<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireRole('admin');

$page_title = 'Dashboard Admin';
$current_user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();

// --- AMBIL DATA STATISTIK ---
$total_apps = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn() ?: 0;
$total_pemohon = $db->query("SELECT COUNT(*) FROM users WHERE role = 'pemohon'")->fetchColumn() ?: 0;
$total_penerima = $db->query("SELECT COUNT(*) FROM users WHERE role = 'penerima'")->fetchColumn() ?: 0;
$pending_apps = $db->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn() ?: 0;

// --- AMBIL PERMOHONAN TERBARU ---
$query = "SELECT a.id, a.title, a.status, u1.name as pemohon_name 
          FROM applications a
          JOIN users u1 ON a.pemohon_id = u1.id
          ORDER BY a.created_at DESC LIMIT 6";
$recent_apps = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// --- DISTRIBUSI STATUS ---
$status_stats = $db->query("SELECT status, COUNT(*) as count FROM applications GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
$counts = array_column($status_stats, 'count', 'status');

include 'includes/header.php';
?>

<style>
    :root {
        --primary-color: #4f46e5;
        --secondary-bg: #f8fafc;
    }

    body {
        background-color: var(--secondary-bg);
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #334155;
    }

    .stat-card {
        border: none;
        border-radius: 20px;
        transition: all 0.3s ease;
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        text-decoration: none !important;
        display: block;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .icon-shape {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }

    .main-card {
        border: none;
        border-radius: 24px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);
        background: white;
        overflow: hidden;
    }

    .table thead th {
        background: #f8fafc;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.05em;
        color: #64748b;
        padding: 1rem 1.25rem;
        border: none;
    }

    .table tbody td {
        padding: 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }

    .badge-soft {
        padding: 6px 12px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.7rem;
        display: inline-block;
    }

    /* Konsistensi Warna Status */
    .st-pending { background: #fffbeb; color: #92400e; }
    .st-reviewed { background: #eff6ff; color: #1e40af; }
    .st-approved { background: #f0fdf4; color: #166534; }
    .st-rejected { background: #fef2f2; color: #991b1b; }

    .btn-quick {
        border-radius: 12px;
        padding: 12px;
        font-weight: 600;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 10px;
        border: none;
        transition: 0.2s;
    }

    .progress-modern {
        height: 8px;
        border-radius: 10px;
        background: #f1f5f9;
    }
</style>

<div class="container-fluid py-4 px-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h2 class="fw-bold text-dark mb-1">Dashboard Overview</h2>
            <p class="text-muted small mb-0">Selamat datang kembali, <strong><?php echo htmlspecialchars(explode(' ', $current_user['name'])[0]); ?></strong>.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <div class="d-inline-flex align-items-center bg-white border rounded-pill px-3 py-2 shadow-sm">
                <i class="bi bi-calendar3 me-2 text-primary"></i>
                <span class="fw-bold small"><?php echo date('d F Y'); ?></span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-sm-6">
            <a href="admin-applications.php" class="stat-card p-4 text-decoration-none">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Permohonan</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($total_apps); ?></h3>
                    </div>
                    <div class="icon-shape bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-file-earmark-text fs-4"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-sm-6">
            <a href="admin-applications.php?status=pending" class="stat-card p-4 text-decoration-none">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Menunggu</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($pending_apps); ?></h3>
                    </div>
                    <div class="icon-shape bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card p-4">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Pemohon</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($total_pemohon); ?></h3>
                    </div>
                    <div class="icon-shape bg-success bg-opacity-10 text-success">
                        <i class="bi bi-person-badge fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card p-4">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Penerima</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($total_penerima); ?></h3>
                    </div>
                    <div class="icon-shape bg-info bg-opacity-10 text-info">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="main-card shadow-sm">
                <div class="card-header bg-transparent border-0 p-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Permohonan Terbaru</h5>
                    <a href="admin-applications.php" class="btn btn-light btn-sm rounded-pill px-3 border fw-bold small">Semua Data</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Pemohon</th>
                                <th>Judul</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_apps as $app): 
                                $status_map = [
                                    'pending'  => ['label' => 'Menunggu', 'class' => 'st-pending'],
                                    'reviewed' => ['label' => 'Ditinjau', 'class' => 'st-reviewed'],
                                    'approved' => ['label' => 'Disetujui', 'class' => 'st-approved'],
                                    'rejected' => ['label' => 'Ditolak', 'class' => 'st-rejected']
                                ];
                                $current_st = $status_map[$app['status']] ?? ['label' => $app['status'], 'class' => ''];
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary">#<?php echo $app['id']; ?></td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($app['pemohon_name']); ?></td>
                                <td>
                                    <div class="text-truncate text-muted small" style="max-width: 250px;">
                                        <?php echo htmlspecialchars($app['title']); ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge-soft <?php echo $current_st['class']; ?>">
                                        <?php echo $current_st['label']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="main-card p-4 mb-4">
                <h6 class="fw-bold text-uppercase small text-muted mb-3">Aksi Cepat</h6>
                <div class="d-grid gap-2">
                    <a href="admin-users.php?action=add&role=pemohon" class="btn btn-success btn-quick">
                        <i class="bi bi-person-plus"></i> Tambah Pemohon
                    </a>
                    <a href="admin-users.php?action=add&role=penerima" class="btn btn-primary btn-quick">
                        <i class="bi bi-shield-plus"></i> Tambah Penerima
                    </a>
                    <a href="admin-print-report.php" target="_blank" class="btn btn-outline-dark btn-quick border shadow-sm">
                        <i class="bi bi-printer"></i> Laporan Cepat
                    </a>
                </div>
            </div>

            <div class="main-card p-4">
                <h6 class="fw-bold text-uppercase small text-muted mb-4">Distribusi Status</h6>
                <?php 
                $status_labels = [
                    'pending' => ['label' => 'Menunggu', 'color' => 'bg-warning'],
                    'reviewed' => ['label' => 'Ditinjau', 'color' => 'bg-info'],
                    'approved' => ['label' => 'Disetujui', 'color' => 'bg-success'],
                    'rejected' => ['label' => 'Ditolak', 'color' => 'bg-danger']
                ];
                
                foreach ($status_labels as $key => $val):
                    $count = $counts[$key] ?? 0;
                    $percent = $total_apps > 0 ? ($count / $total_apps) * 100 : 0;
                ?>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold text-secondary"><?php echo $val['label']; ?></span>
                        <span class="small fw-bold"><?php echo $count; ?></span>
                    </div>
                    <div class="progress progress-modern">
                        <div class="progress-bar <?php echo $val['color']; ?>" style="width: <?php echo $percent; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>