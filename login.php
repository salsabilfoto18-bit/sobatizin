<?php
require_once 'config/database.php';
require_once 'includes/session.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    header("Location: {$role}-dashboard.php");
    exit;
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, name, email, password, role, institution, phone 
                  FROM users 
                  WHERE email = :email 
                  LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password (untuk demo, terima "admin123" atau password yang di-hash)
            if ($password === 'admin123' || password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_institution'] = $user['institution'];
                $_SESSION['user_phone'] = $user['phone'];
                
                // Redirect based on role
                header("Location: {$user['role']}-dashboard.php");
                exit;
            } else {
                $error = 'Email atau password salah!';
            }
        } else {
            $error = 'Email atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - SOBATIZIN Sekolah</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: rgba(99, 102, 241, 0.1);
            --slate-900: #0f172a;
            --slate-600: #475569;
            --slate-400: #94a3b8;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            background-color: #f8fafc;
        }

        /* Tombol Kembali Mengambang */
        .back-to-home {
            position: fixed;
            top: 25px;
            left: 25px;
            z-index: 100;
            text-decoration: none;
            color: var(--slate-600);
            font-weight: 700;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }

        .back-to-home:hover {
            color: var(--primary);
            transform: translateX(-5px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
        }

        #tsparticles {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .login-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            backdrop-filter: blur(16px);
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            transition: transform 0.3s ease;
            position: relative;
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        .brand-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            color: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        }

        .login-header h2 { font-weight: 800; letter-spacing: -0.03em; color: var(--slate-900); }
        .login-header p { color: var(--slate-600); font-size: 0.9rem; margin-bottom: 2rem; }

        .form-label { font-weight: 600; font-size: 0.85rem; margin-bottom: 0.5rem; display: block; color: var(--slate-600); }

        .input-group-modern {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .input-group-modern i {
            position: absolute;
            left: 18px;
            top: 42px; /* Disesuaikan karena ada label */
            transform: translateY(0);
            color: var(--slate-400);
            z-index: 5;
        }

        .form-control {
            height: 54px;
            padding-left: 3rem;
            border-radius: 14px;
            border: 1.5px solid #e2e8f0;
            background: white;
            font-weight: 500;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .btn-login {
            height: 54px;
            border-radius: 14px;
            background: var(--primary);
            color: white;
            border: none;
            font-weight: 700;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: var(--primary-hover);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
            color: white;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f1f5f9;
        }

        .btn-quick {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 5px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--slate-600);
            transition: all 0.2s;
        }

        .btn-quick:hover {
            border-color: var(--primary);
            background: white;
            color: var(--primary);
        }

        @media (max-width: 480px) {
            .back-to-home { top: 15px; left: 15px; padding: 8px 12px; }
            .login-card { border-radius: 0; padding: 2rem; background: white; backdrop-filter: none; height: 100vh; max-width: 100%; }
            body { align-items: flex-start; }
        }
    </style>
</head>
<body>

    <a href="index.php" class="back-to-home">
        <i class="bi bi-house-door-fill"></i>
        <span>Beranda</span>
    </a>

    <div id="tsparticles"></div>

    <div class="login-card">
        <div class="text-center">
            <div class="brand-logo">
                <i class="bi bi-rocket-takeoff-fill"></i>
            </div>
            <div class="login-header">
                <h2>Masuk SOBATIZIN</h2>
                <p>Silakan masuk untuk akses dashboard</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 small text-center" style="border-radius: 12px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="input-group-modern">
                <label class="form-label" for="email">Email</label>
                <i class="bi bi-envelope"></i>
                <input type="email" class="form-control" id="email" name="email" placeholder="email@sekolah.com" required>
            </div>

            <div class="input-group-modern">
                <label class="form-label" for="password">Password</label>
                <i class="bi bi-lock"></i>
                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-login">
                Masuk Dashboard <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </form>

        <div class="demo-grid">
            <button class="btn-quick" onclick="fillLogin('admin@sekolah.com')">ADMIN</button>
            <button class="btn-quick" onclick="fillLogin('budi@student.com')">PEMOHON</button>
            <button class="btn-quick" onclick="fillLogin('suryanto@sekolah.com')">PENERIMA</button>
        </div>
        
        <p class="text-center mt-4 mb-0 small text-muted">
            &copy; <?php echo date('Y'); ?> SOBATIZIN v2.0
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/tsparticles-confetti@2.12.0/tsparticles.confetti.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2.12.0/tsparticles.bundle.min.js"></script>
    
    <script>
        // Inisialisasi Background Interaktif
        tsParticles.load("tsparticles", {
            background: { color: { value: "transparent" } },
            fpsLimit: 60,
            interactivity: {
                events: {
                    onClick: { enable: true, mode: "push" },
                    onHover: { enable: true, mode: "grab" },
                    resize: true,
                },
                modes: {
                    grab: { distance: 140, links: { opacity: 1 } },
                    push: { quantity: 4 },
                },
            },
            particles: {
                color: { value: "#6366f1" },
                links: {
                    color: "#6366f1",
                    distance: 150,
                    enable: true,
                    opacity: 0.3,
                    width: 1,
                },
                move: {
                    enable: true,
                    speed: 1.5,
                    direction: "none",
                    outModes: { default: "out" },
                },
                number: {
                    density: { enable: true, area: 800 },
                    value: 60,
                },
                opacity: { value: 0.5 },
                shape: { type: "circle" },
                size: { value: { min: 1, max: 3 } },
            },
            detectRetina: true,
        });

        function fillLogin(email) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = 'admin123';
            
            // Efek feedback visual
            const card = document.querySelector('.login-card');
            card.style.transform = 'scale(1.02)';
            setTimeout(() => {
                card.style.transform = 'scale(1)';
            }, 200);
        }
    </script>
</body>
</html>