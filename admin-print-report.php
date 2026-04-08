<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireRole('admin');

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

$total_apps = count($applications);
$status_text = [
    'pending' => 'Menunggu',
    'reviewed' => 'Ditinjau',
    'approved' => 'Disetujui',
    'rejected' => 'Ditolak'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Resmi - SMKS Riyadlul Qur'an Ngajum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
            /* Standar dokumen resmi menggunakan Times New Roman */
            font-family: "Times New Roman", Times, serif;
            margin: 0;
            padding: 0;
        }

        .report-paper {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 15mm 15mm 20mm 20mm; /* Margin standar: Atas, Kanan, Bawah, Kiri */
            margin: 20px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }

        /* --- STYLING KOP SURAT --- */
        .kop-surat { 
            display: flex; 
            align-items: center; 
            border-bottom: 2.5pt solid #000; 
            padding-bottom: 5px;
            margin-bottom: 2px;
        }
        
        .logo-kop { 
            width: 90px; 
            height: auto; 
            margin-right: 15px; 
        }

        .text-kop { 
            text-align: center; 
            flex: 1; 
        }

        .text-kop h1 { 
            margin: 0; 
            font-size: 18pt; 
            text-transform: uppercase; 
            color: #166534; 
            font-weight: bold;
        }

        .text-kop p { margin: 1px 0; font-size: 10pt; font-weight: normal; }
        .text-kop .alamat { font-size: 9pt; }
        .text-kop .kontak { font-size: 8.5pt; font-style: italic;  padding-top: 2px; margin-top: 2px; }

        .garis-bawah-kop {
            border-bottom: 1pt solid #000;
            margin-bottom: 20px;
        }

        /* --- JUDUL LAPORAN --- */
        .report-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .report-header h2 {
            font-size: 14pt;
            text-decoration: underline;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .report-header p {
            font-size: 11pt;
            margin: 0;
        }

        /* --- INFORMASI FILTER --- */
        .info-filter {
            font-size: 11pt;
            margin-bottom: 15px;
            width: 100%;
        }

        /* --- TABEL DATA --- */
        .table-official {
            width: 100%;
            border: 1.5pt solid #000;
            border-collapse: collapse;
            font-size: 10.5pt;
        }

        .table-official th {
            border: 1pt solid #000;
            background-color: #f0f0f0 !important;
            padding: 8px;
            text-transform: uppercase;
            text-align: center;
        }

        .table-official td {
            border: 0.5pt solid #000;
            padding: 6px 8px;
            vertical-align: middle;
        }

        /* --- TANDA TANGAN --- */
        .signature-wrapper {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .sig-box {
            text-align: center;
            width: 200px;
            font-size: 11pt;
        }

        .sig-space { height: 75px; }

        .no-print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
        }

        @media print {
            body { background: none; }
            .report-paper { 
                margin: 0 auto; 
                box-shadow: none; 
                width: 100%;
                padding: 10mm 10mm 10mm 15mm;
            }
            .no-print-btn { display: none; }
            /* Memastikan warna kop tetap muncul saat diprint */
            .text-kop h1 { color: #166534 !important; -webkit-print-color-adjust: exact; }
            .table-official th { background-color: #f0f0f0 !important; -webkit-print-color-adjust: exact; }
        }
        .btn { transition: all 0.3s ease; }
        .btn:hover { transform: translateY(-1px); }
    </style>
</head>
<body>

    <div class="no-print-btn d-flex gap-2">
        <button onclick="window.print()" class="btn btn-dark px-4 shadow-sm d-inline-flex align-items-center">
            <i class="bi bi-printer me-2"></i> Cetak Laporan
        </button>

        <a href="admin-reports.php" class="btn btn-outline-secondary shadow-sm px-4">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    <style>
        /* Menambahkan sedikit efek transisi agar lebih user-friendly */
        .btn { transition: all 0.3s ease; }
        .btn:hover { transform: translateY(-1px); }
    </style>

    <div class="report-paper">
        <div class="kop-surat">
            <img src="smk.png" alt="Logo" class="logo-kop" onerror="this.src='https://via.placeholder.com/90'">
            <div class="text-kop">
                <h1>SMKS RIYADLUL QUR'AN NGAJUM</h1>
                <p style="font-weight: bold;">TERAKREDITASI 'B'</p>
                <p>NPSN: 69786543 | NSS: 342051810001</p>
                <p class="alamat">Jl. Sunan Ampel 52 C Desa Ngasem Kec. Ngajum Kab. Malang 65164</p>
                <p class="kontak">Email: pprqsmk@yahoo.com | Web: www.smksrqngajum.sch.id | Telp: 082139008567</p>
            </div>
        </div>
        <div class="garis-bawah-kop"></div>

        <div class="report-header">
            <h2>LAPORAN REKAPITULASI LAYANAN PERMOHONAN</h2>
            <p>Nomor: <?php echo date('Y/m'); ?>/ADM-RQ/<?php echo str_pad($total_apps, 3, '0', STR_PAD_LEFT); ?></p>
        </div>

        <table class="info-filter">
            <tr>
                <td width="15%">Periode</td>
                <td width="2%">:</td>
                <td width="40%"><?php echo $date_from ? date('d/m/Y', strtotime($date_from)) : 'Awal'; ?> s/d <?php echo $date_to ? date('d/m/Y', strtotime($date_to)) : date('d/m/Y'); ?></td>
                <td width="15%">Penyusun</td>
                <td width="2%">:</td>
                <td><?php echo htmlspecialchars($current_user['name']); ?></td>
            </tr>
            <tr>
                <td>Status</td>
                <td>:</td>
                <td><?php echo $status ? strtoupper($status_text[$status]) : 'SEMUA STATUS'; ?></td>
                <td>Tgl Cetak</td>
                <td>:</td>
                <td><?php echo date('d/m/Y H:i'); ?></td>
            </tr>
        </table>

        <table class="table-official">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Tanggal</th>
                    <th width="25%">Nama Pemohon</th>
                    <th width="35%">Perihal Layanan</th>
                    <th width="20%">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($applications as $app): ?>
                <tr>
                    <td style="text-align: center;"><?php echo $no++; ?></td>
                    <td style="text-align: center;"><?php echo date('d/m/Y', strtotime($app['created_at'])); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($app['pemohon_name']); ?></strong><br>
                        <small style="color: #444;"><?php echo htmlspecialchars($app['pemohon_institution']); ?></small>
                    </td>
                    <td>
                        <span style="font-weight: bold;"><?php echo htmlspecialchars($app['title']); ?></span><br>
                        <i style="font-size: 9pt;"><?php echo htmlspecialchars(substr($app['description'], 0, 70)); ?>...</i>
                    </td>
                    <td style="text-align: center; font-weight: bold;">
                        <?php echo strtoupper($status_text[$app['status']]); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($total_apps == 0): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px;">Data tidak ditemukan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <p style="margin-top: 15px; font-size: 10pt;"><i>* Total keseluruhan data permohonan yang tercatat dalam laporan ini adalah <?php echo $total_apps; ?> data.</i></p>

    <div class="signature-wrapper">
        <div class="sig-box">
            <p>Mengetahui,</p>
            <p><strong>Kepala Sekolah</strong></p>
            <div class="sig-space"></div>
            <p><strong><u>( .................................... )</u></strong></p>
            <p>NIY. ...........................</p>
        </div>
        <div class="sig-box">
            <p>Ngajum, <?php echo date('d F Y'); ?></p>
            <p><strong>Petugas Administrasi</strong></p>
            <div class="sig-space"></div>
            <p><strong><u><?php echo strtoupper(htmlspecialchars($current_user['name'])); ?></u></strong></p>
            <p>NIY. ...........................</p>
        </div>
    </div>
</div>

</body>
</html>