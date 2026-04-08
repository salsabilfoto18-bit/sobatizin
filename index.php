<?php
require_once 'includes/session.php';

// Cek apakah pengguna sudah login
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'user';
    header("Location: {$role}-dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOBATIZIN | Portal Layanan Aspirasi Siswa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    
    <style>
        :root {
            --brand-color: #4f46e5;
            --brand-grad: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --bg-pure: #ffffff;
            --bg-subtle: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --card-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04);
            --card-shadow-hover: 0 20px 40px -10px rgba(79, 70, 229, 0.12);
        }

        body { 
            background-color: var(--bg-pure); 
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            line-height: 1.6;
        }

        /* --- Navbar --- */
        .navbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #f1f5f9;
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--brand-color) !important;
        }

        /* --- Hero Section --- */
        .hero-section {
            padding: 140px 0 80px;
            background: radial-gradient(circle at 90% 10%, rgba(99, 102, 241, 0.04), transparent),
                        radial-gradient(circle at 10% 90%, rgba(168, 85, 247, 0.04), transparent);
        }
        .hero-title {
            font-size: clamp(2.5rem, 5vw, 3.8rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -2px;
            margin-bottom: 1.5rem;
        }
        .hero-title span {
            background: var(--brand-grad);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* --- About Card --- */
        .about-card {
            background: var(--bg-subtle);
            border-radius: 32px;
            padding: 4rem 3rem;
            border: 1px solid #f1f5f9;
            margin-bottom: 6rem;
        }
        
        .feature-box {
            background: white;
            padding: 2rem;
            border-radius: 24px;
            height: 100%;
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
        }
        .feature-box:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
            border-color: #e2e8f0;
        }

        /* --- Process Cards --- */
        .process-card {
            background: white;
            border: 1px solid #f1f5f9;
            border-radius: 28px;
            padding: 2.5rem;
            height: 100%;
            transition: all 0.3s ease;
            text-align: center;
        }
        .process-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-shadow-hover);
        }
        
        .icon-circle {
            width: 64px;
            height: 64px;
            background: #f5f3ff;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: var(--brand-color);
            margin: 0 auto 1.5rem;
        }

        /* --- Buttons --- */
        .btn-cta {
            background: var(--brand-grad);
            color: white;
            padding: 18px 42px;
            border-radius: 18px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
        }
        .btn-cta:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.3);
        }

        footer {
            padding: 4rem 0 2rem;
            background: #fafafa;
            border-top: 1px solid #f1f5f9;
        }

        /* Utility */
        .fw-800 { font-weight: 800; }
        .text-justify { text-align: justify; }
        
        @media (max-width: 768px) {
            .about-card { padding: 2.5rem 1.5rem; }
            .hero-section { padding: 100px 0 50px; }
        }
    </style>
</head>
<body>

<nav class="navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <div style="background: var(--brand-grad); width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-megaphone-fill text-white fs-6"></i>
            </div>
            <span>SOBATIZIN</span>
        </a>
        <a href="login.php" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm" style="font-size: 0.85rem;">Masuk</a>
    </div>
</nav>

<section class="hero-section text-center">
    <div class="container" data-aos="fade-up">
        <div class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 mb-4 fw-bold" style="font-size: 0.8rem; letter-spacing: 0.5px;">
            DASHBOARD ASPIRASI TERPADU
        </div>
        <h1 class="hero-title">Wadah Aspirasi untuk <br><span>Sekolah yang Lebih Baik.</span></h1>
        <p class="text-muted fs-5 mb-5 mx-auto" style="max-width: 650px;">
            Sampaikan laporan pengaduan atau ide kreatifmu secara aman. "Solusi Bersahabat, Urusan Izin Jadi Singkat".
        </p>
        <a href="login.php" class="btn-cta">
            Buat Laporan Sekarang <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</section>

<div class="container">
    <div class="about-card shadow-sm" data-aos="fade-up">
        <div class="row gy-5 align-items-center mb-5">
            <div class="col-lg-7">
                <span class="text-primary fw-bold small text-uppercase mb-2 d-block" style="letter-spacing: 1px;">Efisien & Transparan</span>
                <h2 class="fw-800 mb-4 display-6">Tentang SOBATIZIN</h2>
                <p class="text-muted text-justify pe-lg-4">
                    SOBATIZIN adalah platform digital berbasis web yang dirancang untuk mentransformasi sistem administrasi sekolah menjadi lebih modern dan efisien. Kami hadir sebagai jembatan digital yang menghubungkan siswa dan pihak sekolah dalam satu ekosistem yang harmonis, memastikan setiap suara didengar dan setiap urusan diselesaikan dengan cepat.
                </p>
            </div>
            <div class="col-lg-5">
                <div class="p-4 bg-white rounded-4 border-start border-4 border-primary shadow-sm">
                    <h5 class="fw-bold mb-2"><i class="bi bi-rocket-takeoff text-primary me-2"></i>Visi Kami</h5>
                    <p class="text-muted small mb-0">Membangun budaya sekolah digital dengan menyederhanakan birokrasi, agar fokus utama tetap pada kualitas pendidikan.</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <?php
            $benefits = [
                ['icon' => 'shield-check', 'title' => 'Aman & Rahasia', 'desc' => 'Identitas pelapor terlindungi dengan sistem enkripsi data.'],
                ['icon' => 'clock-history', 'title' => 'Pantau Real-Time', 'desc' => 'Cek status laporan Anda kapan saja melalui dashboard.'],
                ['icon' => 'lightning-charge', 'title' => 'Proses Cepat', 'desc' => 'Notifikasi otomatis mempercepat respons dari pihak sekolah.'],
                ['icon' => 'cursor-fill', 'title' => 'Mudah Digunakan', 'desc' => 'Desain antarmuka yang ramah untuk semua kalangan siswa.']
            ];
            foreach ($benefits as $b) : ?>
            <div class="col-md-6 col-lg-3">
                <div class="feature-box">
                    <i class="bi bi-<?= $b['icon'] ?> text-primary fs-3 mb-3 d-block"></i>
                    <h6 class="fw-bold mb-2"><?= $b['title'] ?></h6>
                    <p class="small text-muted mb-0"><?= $b['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="container mb-5 pb-5">
    <div class="text-center mb-5" data-aos="fade-up">
        <h3 class="fw-800">Bagaimana Cara Kerja Kami?</h3>
        <p class="text-muted">Hanya butuh tiga langkah sederhana untuk menyampaikan aspirasimu.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
            <div class="process-card">
                <div class="icon-circle"><i class="bi bi-pencil-square"></i></div>
                <h5 class="fw-bold mb-3">1. Tulis Laporan</h5>
                <p class="text-muted small mb-0">Ceritakan kejadian atau aspirasi Anda secara mendetail dan jujur melalui formulir kami.</p>
            </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
            <div class="process-card">
                <div class="icon-circle" style="background: #ecfdf5; color: #10b981;"><i class="bi bi-patch-check"></i></div>
                <h5 class="fw-bold mb-3">2. Verifikasi Data</h5>
                <p class="text-muted small mb-0">Tim sekolah akan meninjau laporan Anda untuk memastikan validitas informasi yang diberikan.</p>
            </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
            <div class="process-card">
                <div class="icon-circle" style="background: #fff7ed; color: #f59e0b;"><i class="bi bi-check-all"></i></div>
                <h5 class="fw-bold mb-3">3. Selesai</h5>
                <p class="text-muted small mb-0">Laporan Anda ditindaklanjuti dan Anda dapat melihat hasilnya langsung di dashboard.</p>
            </div>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-6 text-center text-md-start">
                <h5 class="fw-800 text-primary mb-2">SOBATIZIN</h5>
                <p class="text-muted small mb-0">Sistem Informasi Pengaduan & Layanan Sekolah Terpadu.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="text-muted small mb-0">&copy; <?php echo date("Y"); ?> SOBATIZIN Team. All Rights Reserved.</p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
</script>
</body>
</html>