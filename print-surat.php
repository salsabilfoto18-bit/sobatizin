<?php
require_once 'config/database.php';
require_once 'includes/session.php';

requireLogin();

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID Data permohonan tidak ditemukan.");
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT a.*, 
          u1.name as nama_siswa, u1.email as email_siswa,
          u2.name as nama_petugas
          FROM applications a 
          JOIN users u1 ON a.pemohon_id = u1.id 
          LEFT JOIN users u2 ON a.penerima_id = u2.id 
          WHERE a.id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Data permohonan tidak ditemukan di database.");
}

$nama_petugas = $data['nama_petugas'] ?? '( ................................... )';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Keterangan - <?php echo htmlspecialchars($data['nama_siswa']); ?></title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body { 
            font-family: 'Times New Roman', Times, serif; 
            margin: 0; 
            padding: 0; 
            background: #e0e0e0; 
            color: #000;
        }
        
        .paper { 
            width: 210mm; 
            min-height: 297mm; 
            padding: 15mm 20mm 20mm 25mm; 
            margin: 10mm auto; 
            background: white; 
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            box-sizing: border-box;
            position: relative;
        }

        /* --- BAGIAN KOP SURAT YANG DISESUAIKAN --- */
        .kop-surat { 
            display: flex; 
            align-items: center; 
            /* Mengecilkan garis utama kop */
            border-bottom: 2px solid #000; 
            padding-bottom: 5px;
            margin-bottom: 2px;
        }
        .garis-bawah-kop {
            /* Garis tipis tambahan di bawah garis utama */
            border-bottom: 0.5pt solid #000;
            margin-bottom: 30px;
        }
        /* ----------------------------------------- */

        .logo-kop { 
            width: 95px; 
            height: auto; 
            margin-right: 20px; 
        }
        .text-kop { 
            text-align: center; 
            flex: 1; 
        }
        .text-kop h1 { 
            margin: 0; 
            font-size: 18pt; 
            text-transform: uppercase; 
            line-height: 1.2; 
            color: #166534; 
            font-weight: bold;
        }
        .text-kop p { margin: 2px 0; font-size: 10pt; line-height: 1.2; }
        .text-kop .alamat { font-size: 9.5pt; font-style: normal; }
        .text-kop .kontak { font-size: 8pt; font-style: italic; border-top:  padding-top: 3px; margin-top: 3px; }

        .judul-surat { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        .judul-surat h3 { 
            margin: 0; 
            text-transform: uppercase; 
            text-decoration: underline; 
            font-size: 14pt; 
            font-weight: bold;
        }
        .judul-surat p { margin: 5px 0 0 0; font-size: 12pt; font-weight: bold; }

        .pembuka { margin-bottom: 20px; font-size: 12pt; text-align: justify; line-height: 1.5; }
        
        .tabel-identitas { 
            margin-left: 10mm; 
            margin-bottom: 25px; 
            width: 90%;
        }
        .tabel-identitas td {
            padding: 5px 0;
            vertical-align: top;
            font-size: 12pt;
        }
        .label { width: 160px; }
        .titik { width: 20px; text-align: center; }

        .paragraf-isi { 
            text-align: justify; 
            text-indent: 45px; 
            font-size: 12pt;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .footer-ttd { 
            margin-top: 50px; 
            display: flex; 
            justify-content: flex-end; 
        }
        .ttd-box { 
            text-align: center; 
            width: 75mm; 
        }
        .ttd-box p { margin: 0; font-size: 12pt; line-height: 1.4; }
        .spacer { height: 85px; }
        .nama-pejabat { 
            font-weight: bold; 
            text-decoration: underline; 
            text-transform: uppercase; 
        }

        .no-print {
            text-align: center;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .btn-cetak {
            padding: 10px 25px;
            background: #166534;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }

        @media print {
            body { background: none; }
            .paper { margin: 0; box-shadow: none; border: none; }
            .no-print { display: none; }
            * { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="admin-applications.php" class="btn-cetak" style="background: #666;">&larr; Kembali</a>
    <button class="btn-cetak" onclick="window.print()">CETAK SURAT</button>
</div>

<div class="paper">
    <div class="kop-surat">
        <img src="smk.png" alt="Logo SMK" class="logo-kop">
        <div class="text-kop">
            <h1>SMKS RIYADLUL QUR'AN NGAJUM</h1>
            <p style="font-weight: bold;">TERAKREDITASI 'B'</p>
            <p>NPSN: 69786543 | NSS: 342051810001</p>
            <p class="alamat">Jl. Sunan Ampel 52 C Desa Ngasem Kec. Ngajum Kab. Malang 65164</p>
            <p class="kontak">Email: pprqsmk@yahoo.com | Web: www.smksrqngajum.sch.id | Telp: 082139008567</p>
        </div>
    </div>
    <div class="garis-bawah-kop"></div>

    <div class="judul-surat">
        <h3>SURAT KETERANGAN</h3>
        <p>Nomor: <?php echo str_pad($data['id'], 3, '0', STR_PAD_LEFT); ?>/421.5/SMK-RQ/<?php echo date('m'); ?>/<?php echo date('Y'); ?></p>
    </div>

    <div class="pembuka">
        Kepala Sekolah Menengah Kejuruan Swasta (SMKS) Riyadlul Qur'an Ngajum, dengan ini menerangkan bahwa:
    </div>

    <table class="tabel-identitas">
        <tr>
            <td class="label">Nama Lengkap</td>
            <td class="titik">:</td>
            <td><strong><?php echo htmlspecialchars($data['nama_siswa']); ?></strong></td>
        </tr>
        <tr>
            <td class="label">Identitas / Email</td>
            <td class="titik">:</td>
            <td><?php echo htmlspecialchars($data['email_siswa']); ?></td>
        </tr>
        <tr>
            <td class="label">Perihal</td>
            <td class="titik">:</td>
            <td><?php echo htmlspecialchars($data['title']); ?></td>
        </tr>
    </table>

    <div class="paragraf-isi">
        Nama tersebut di atas adalah benar merupakan bagian dari civitas akademika SMKS Riyadlul Qur'an Ngajum. Berdasarkan permohonan yang diajukan, yang bersangkutan telah menyampaikan keperluan dengan keterangan sebagai berikut:
    </div>

    <div class="paragraf-isi" style="text-indent: 0; padding-left: 45px; font-style: italic;">
        "<?php echo htmlspecialchars($data['description']); ?>"
    </div>

    <div class="paragraf-isi">
        Demikian surat keterangan ini dibuat dengan sebenarnya sesuai dengan data yang ada, untuk dapat dipergunakan sebagaimana mestinya dan agar pihak yang berkepentingan menjadi maklum.
    </div>

    <div class="footer-ttd">
        <div class="ttd-box">
            <p>Ngajum, <?php echo date('d F Y'); ?></p>
            <p>Kepala Sekolah,</p>
            <div class="spacer"></div>
            <p class="nama-pejabat"><?php echo htmlspecialchars($nama_petugas); ?></p>
            <p>NIP/NIY. ...................................</p>
        </div>
    </div>
</div>

</body>
</html>