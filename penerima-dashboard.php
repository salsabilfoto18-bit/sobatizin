<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireRole('penerima');

$page_title = 'Dashboard Guru';
$current_user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();

// --- LOGIKA STATISTIK (TIDAK DIUBAH) ---
$query_total = "SELECT COUNT(*) FROM applications WHERE penerima_id = :user_id";
$stmt = $db->prepare($query_total);
$stmt->execute([':user_id' => $current_user['id']]);
$total = $stmt->fetchColumn() ?: 0;

$query_pending = "SELECT COUNT(*) FROM applications WHERE penerima_id = :user_id AND status = 'pending'";
$stmt = $db->prepare($query_pending);
$stmt->execute([':user_id' => $current_user['id']]);
$pending = $stmt->fetchColumn() ?: 0;

$query_reviewed = "SELECT COUNT(*) FROM applications WHERE penerima_id = :user_id AND status = 'reviewed'";
$stmt = $db->prepare($query_reviewed);
$stmt->execute([':user_id' => $current_user['id']]);
$reviewed = $stmt->fetchColumn() ?: 0;

$query_approved = "SELECT COUNT(*) FROM applications WHERE penerima_id = :user_id AND status = 'approved'";
$stmt = $db->prepare($query_approved);
$stmt->execute([':user_id' => $current_user['id']]);
$approved = $stmt->fetchColumn() ?: 0;

// --- TAMBAHAN LOGIKA FILTER & PENCARIAN ---
$f_status = $_GET['status'] ?? '';
$f_search = $_GET['search'] ?? '';

$query = "SELECT a.*, u.name as pemohon_name, u.phone as pemohon_phone
          FROM applications a
          JOIN users u ON a.pemohon_id = u.id
          WHERE a.penerima_id = :user_id";

if (!empty($f_status)) { $query .= " AND a.status = :status"; }
if (!empty($f_search)) { $query .= " AND (u.name LIKE :search OR a.title LIKE :search)"; }

$query .= " ORDER BY a.created_at DESC LIMIT 6";

$stmt = $db->prepare($query);
$params = [':user_id' => $current_user['id']];
if (!empty($f_status)) $params[':status'] = $f_status;
if (!empty($f_search)) $params[':search'] = "%$f_search%";
$stmt->execute($params);
$recent_apps = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header_user.php';
?>

<style>
    /* Desain Awal Anda */
    :root {
        --teacher-grad: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        --soft-shadow: 0 10px 30px -10px rgba(0,0,0,0.1);
    }
    body { background-color: #f1f5f9; font-family: 'Plus Jakarta Sans', sans-serif; }

    .welcome-banner {
        background: var(--teacher-grad);
        border-radius: 24px;
        padding: 3rem 2.5rem;
        color: white;
        margin-bottom: 2.5rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 40px -15px rgba(99, 102, 241, 0.3);
    }

    .welcome-banner::after {
        content: '👨‍🏫';
        position: absolute;
        right: -10px;
        bottom: -20px;
        font-size: 10rem;
        opacity: 0.15;
        transform: rotate(-15deg);
    }

    /* Modifikasi Ringan: Tambahkan pointer agar card terlihat bisa diklik */
    .stat-card-modern {
        border: none;
        border-radius: 20px;
        background: white;
        transition: all 0.3s ease;
        box-shadow: var(--soft-shadow);
        height: 100%;
        cursor: pointer; /* Tambahan */
    }

    /* Indikator Card Aktif */
    .stat-card-modern.active {
        border: 2px solid #6366f1 !important;
    }

    .stat-card-modern:hover {
        transform: translateY(-5px);
    }

    .icon-box {
        width: 54px;
        height: 54px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .table-modern {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: var(--soft-shadow);
    }

    .table-modern thead th {
        background: #fdfdfd;
        text-transform: uppercase;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 1px;
        color: #94a3b8;
        padding: 1.2rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .table-modern tbody td {
        padding: 1.2rem;
        vertical-align: middle;
        border-bottom: 1px solid #f8fafc;
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

    .btn-action-main {
        background: white;
        color: #6366f1;
        font-weight: 700;
        border: none;
        padding: 12px 24px;
        border-radius: 14px;
        transition: 0.3s;
        text-decoration: none;
    }

    .btn-action-main:hover {
        background: #f8fafc;
        transform: scale(1.05);
        color: #4f46e5;
    }

    .avatar-circle {
        width: 40px;
        height: 40px;
        background: #eee;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #6366f1;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    /* Style Search Box agar menyatu dengan desain */
    .search-wrapper {
        position: relative;
        max-width: 400px;
    }
    .search-wrapper i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    .search-control {
        padding: 12px 15px 12px 45px;
        border-radius: 15px;
        border: 1px solid #e2e8f0;
        width: 100%;
        outline: none;
        transition: 0.3s;
    }
    .search-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
</style>

<div class="container py-4">
    <form action="" method="GET" id="mainFilterForm">
        <input type="hidden" name="status" id="statusHidden" value="<?php echo htmlspecialchars($f_status); ?>">

        <div class="welcome-banner">
            <div class="row align-items-center position-relative" style="z-index: 2;">
                <div class="col-md-7">
                    <span class="badge bg-white bg-opacity-25 text-white mb-3 px-3 py-2 rounded-pill">Panel Guru</span>
                    <h1 class="fw-bold mb-2">Halo, Bapak/Ibu <?php echo explode(' ', trim(htmlspecialchars($current_user['name'])))[0]; ?>! 📚</h1>
                    <p class="opacity-75 mb-0">Kelola permohonan dengan sistem filter cepat.</p>
                </div>
                <div class="col-md-5 text-md-end mt-4 mt-md-0">
                    <div class="search-wrapper ms-md-auto">
                        <i class="bi bi-search"></i>
                        <input type="text" name="search" id="autoSearch" class="search-control shadow-sm" placeholder="Cari nama atau judul..." value="<?php echo htmlspecialchars($f_search); ?>" autocomplete="off">
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3 col-6">
                <div class="card stat-card-modern p-2 <?php echo $f_status == '' ? 'active' : ''; ?>" onclick="setFilter('')">
                    <div class="card-body">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary mb-3">
                            <i class="bi bi-inboxes-fill"></i>
                        </div>
                        <p class="text-muted small fw-bold mb-1">TOTAL MASUK</p>
                        <h3 class="fw-bold m-0"><?php echo $total; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card-modern p-2 <?php echo $f_status == 'pending' ? 'active' : ''; ?>" onclick="setFilter('pending')">
                    <div class="card-body">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning mb-3">
                            <i class="bi bi-clock-fill"></i>
                        </div>
                        <p class="text-muted small fw-bold mb-1">MENUNGGU</p>
                        <h3 class="fw-bold m-0"><?php echo $pending; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card-modern p-2 <?php echo $f_status == 'reviewed' ? 'active' : ''; ?>" onclick="setFilter('reviewed')">
                    <div class="card-body">
                        <div class="icon-box bg-info bg-opacity-10 text-info mb-3">
                            <i class="bi bi-eye-fill"></i>
                        </div>
                        <p class="text-muted small fw-bold mb-1">DITINJAU</p>
                        <h3 class="fw-bold m-0"><?php echo $reviewed; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card-modern p-2 <?php echo $f_status == 'approved' ? 'active' : ''; ?>" onclick="setFilter('approved')">
                    <div class="card-body">
                        <div class="icon-box bg-success bg-opacity-10 text-success mb-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <p class="text-muted small fw-bold mb-1">DISETUJUI</p>
                        <h3 class="fw-bold m-0"><?php echo $approved; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="fw-bold m-0 text-dark">Daftar Permohonan</h4>
                <?php if($f_status || $f_search): ?>
                    <a href="penerima-dashboard.php" class="text-decoration-none small fw-bold text-danger">
                        <i class="bi bi-x-circle me-1"></i> Reset Filter
                    </a>
                <?php endif; ?>
            </div>

            <div class="table-modern">
                <div class="table-responsive">
                    <?php if (count($recent_apps) > 0): ?>
                        <table class="table mb-0 table-hover">
                            <thead>
                                <tr>
                                    <th>Informasi Siswa</th>
                                    <th>Kategori / Judul</th>
                                    <th>Status Berkas</th>
                                    <th class="text-end">Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_apps as $app): 
                                    $status_map = [
                                        'pending'  => ['warning', 'Antrean Baru', 'bi-hourglass-split'],
                                        'approved' => ['success', 'Disetujui', 'bi-check-all'],
                                        'rejected' => ['danger', 'Ditolak', 'bi-x-lg'],
                                        'reviewed' => ['info', 'Ditinjau', 'bi-search']
                                    ];
                                    $s = $status_map[$app['status']] ?? ['secondary', 'Unknown', 'bi-question'];
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-3 bg-primary bg-opacity-10">
                                                    <?php echo strtoupper(substr($app['pemohon_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($app['pemohon_name']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($app['pemohon_phone'] ?? '-'); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($app['title']); ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <i class="bi bi-clock me-1"></i> <?php echo date('d M Y', strtotime($app['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-pill bg-<?php echo $s[0]; ?> bg-opacity-10 text-<?php echo $s[0]; ?>">
                                                <i class="bi <?php echo $s[2]; ?>"></i> <?php echo $s[1]; ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="penerima-detail.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-light rounded-pill px-4 fw-bold border text-primary">
                                                Periksa
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 fw-bold">Data tidak ditemukan</h5>
                            <p class="text-muted small">Coba sesuaikan kata kunci atau filter status Anda.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi Klik Card (Filter Status)
function setFilter(val) {
    document.getElementById('statusHidden').value = val;
    document.getElementById('mainFilterForm').submit();
}

// Fungsi Pencarian Otomatis (Debounce 500ms)
let timer;
document.getElementById('autoSearch').addEventListener('input', function() {
    clearTimeout(timer);
    timer = setTimeout(() => {
        document.getElementById('mainFilterForm').submit();
    }, 500);
});

// Menjaga fokus kursor di akhir teks saat mengetik (setelah refresh)
const searchInput = document.getElementById('autoSearch');
if (searchInput.value.length > 0) {
    searchInput.focus();
    searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
}
</script>

<?php include 'includes/footer.php'; ?>