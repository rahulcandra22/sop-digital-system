<?php
session_start();

require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $username     = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email        = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password     = $_POST['password'];
    $konfirmasi   = $_POST['konfirmasi_password'];

    // Validasi input kosong
    if (empty($nama_lengkap) || empty($username) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi!';
    }
    // Validasi format email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    }
    // Validasi panjang password
    elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    }
    // Validasi konfirmasi password
    elseif ($password !== $konfirmasi) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } else {
        // Cek apakah username sudah ada
        $cek_username = "SELECT id FROM users WHERE username = '$username'";
        $res_username = mysqli_query($conn, $cek_username);
        if ($res_username && mysqli_num_rows($res_username) > 0) {
            $error = 'Username sudah digunakan, pilih username lain!';
        } else {
            // Cek apakah email sudah ada
            $cek_email = "SELECT id FROM users WHERE email = '$email'";
            $res_email = mysqli_query($conn, $cek_email);
            if ($res_email && mysqli_num_rows($res_email) > 0) {
                $error = 'Email sudah terdaftar!';
            } else {
                // Simpan ke database
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'user'; // default role
                $sql = "INSERT INTO users (nama_lengkap, username, email, password, role) 
                        VALUES ('$nama_lengkap', '$username', '$email', '$hashed_password', '$role')";

                if (mysqli_query($conn, $sql)) {
                    header('Location: index.php?registered=1');
                    exit();
                } else {
                    $error = 'Terjadi kesalahan saat menyimpan data. Silahkan coba lagi.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - SOP Digital System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- TEMA GELAP (DEFAULT) --- */
        :root {
            --primary-glow: #3b82f6;
            --secondary-glow: #8b5cf6;
            --accent-orange: #f97316;

            --bg-main: #020617;
            --bg-dark: #0f172a;
            --glass-bg: rgba(15, 23, 42, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --input-bg: rgba(0, 0, 0, 0.4);

            --left-panel-bg: linear-gradient(160deg, rgba(30, 58, 138, 0.5) 0%, rgba(15, 23, 42, 0.7) 100%);
            --grid-color: rgba(255, 255, 255, 0.03);
            --svg-screen-top: #1e293b;
            --svg-screen-bottom: #0f172a;
        }

        /* --- TEMA TERANG --- */
        [data-theme="light"] {
            --bg-main: #f1f5f9;
            --bg-dark: #e2e8f0;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(0, 0, 0, 0.1);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --input-bg: rgba(255, 255, 255, 0.9);

            --left-panel-bg: linear-gradient(160deg, rgba(219, 234, 254, 0.7) 0%, rgba(241, 245, 249, 0.9) 100%);
            --grid-color: rgba(0, 0, 0, 0.04);
            --svg-screen-top: #cbd5e1;
            --svg-screen-bottom: #94a3b8;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Outfit', sans-serif !important;
            background-color: var(--bg-main) !important;
            color: var(--text-main);
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin: 0;
            padding: 20px 0;
            transition: background-color 0.5s ease, color 0.5s ease;
        }

        /* --- TOGGLE BUTTON --- */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-main);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
            z-index: 100;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .theme-toggle:hover {
            transform: scale(1.1);
            color: var(--primary-glow);
        }

        /* --- BACKGROUND --- */
        .ambient-light {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            overflow: hidden;
            background: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.15), transparent 25%),
                        radial-gradient(circle at 85% 30%, rgba(249, 115, 22, 0.08), transparent 25%);
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            animation: moveOrb 20s infinite alternate;
        }
        .orb-1 { width: 400px; height: 400px; background: var(--primary-glow); top: -100px; left: -100px; }
        .orb-2 { width: 500px; height: 500px; background: var(--accent-orange); bottom: -150px; right: -150px; animation-delay: -5s; }

        @keyframes moveOrb {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 30px); }
        }

        .grid-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(var(--grid-color) 1px, transparent 1px),
            linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: -1;
            mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
            -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        }

        /* --- CONTAINER --- */
        .register-container {
            width: 100%;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }

        .register-box {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(25px) !important;
            -webkit-backdrop-filter: blur(25px) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 24px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3), 0 0 40px rgba(59, 130, 246, 0.1) !important;
            max-width: 900px !important;
            width: 100% !important;
            display: grid !important;
            grid-template-columns: 1fr 1.3fr !important;
            overflow: hidden;
            animation: cardEntrance 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
            transition: background 0.5s ease, border-color 0.5s ease;
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* --- LEFT SIDE --- */
        .register-left {
            background: var(--left-panel-bg) !important;
            padding: 40px 30px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            text-align: center !important;
            color: var(--text-main);
            position: relative;
            border-right: 1px solid var(--glass-border) !important;
            transition: background 0.5s ease;
        }

        .register-logo {
            width: 160px;
            height: auto;
            margin-bottom: 5px !important;
            filter: drop-shadow(0 0 15px rgba(59, 130, 246, 0.4));
            animation: floatLogo 6s ease-in-out infinite;
        }

        @keyframes floatLogo {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .register-brand-title {
            font-size: 24px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            margin-bottom: 15px !important;
            line-height: 1.2 !important;
            background: linear-gradient(135deg, var(--text-main) 0%, #60a5fa 50%, #3b82f6 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .register-subtitle {
            font-size: 13px !important;
            color: var(--text-muted) !important;
            line-height: 1.6 !important;
            margin-bottom: 25px !important;
            font-weight: 400;
            max-width: 280px;
        }

        /* Ilustrasi SVG Kanan */
        .custom-illustration {
            width: 100%;
            max-height: 200px;
            height: auto;
            opacity: 0.95;
            filter: drop-shadow(0 0 15px rgba(59, 130, 246, 0.2));
            transition: all 0.5s ease;
        }

        .register-left:hover .custom-illustration {
            transform: translateY(-10px) scale(1.02);
            opacity: 1;
            filter: drop-shadow(0 0 25px rgba(59, 130, 246, 0.4));
        }

        /* Keuntungan daftar */
        .benefit-list {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: left;
            width: 100%;
            max-width: 260px;
        }

        .benefit-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            color: var(--text-muted);
            padding: 6px 0;
            border-bottom: 1px solid var(--glass-border);
        }

        .benefit-list li:last-child { border-bottom: none; }

        .benefit-list li i {
            color: #10b981;
            font-size: 13px;
            flex-shrink: 0;
        }

        /* --- RIGHT SIDE (FORM) --- */
        .register-right {
            padding: 35px 38px !important;
            background: transparent !important;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 22px !important;
        }

        .form-header h2 {
            color: var(--text-main) !important;
            font-size: 24px !important;
            margin-bottom: 6px !important;
            font-weight: 600;
            letter-spacing: -0.5px;
        }

        .form-header p {
            color: var(--text-muted) !important;
            font-size: 13px !important;
            margin: 0 !important;
        }

        /* Grid 2 kolom untuk field */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 18px;
        }

        .form-grid .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group {
            margin-bottom: 18px !important;
            position: relative;
        }

        .form-group label {
            color: var(--text-main) !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 7px !important;
            display: block;
            margin-left: 4px;
        }

        .input-group {
            position: relative;
            width: 100%;
        }

        .input-group i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
            transition: 0.3s;
            z-index: 2;
        }

        .input-group i.toggle-password {
            left: auto !important;
            right: 14px;
            cursor: pointer;
            z-index: 10;
        }

        .input-group i.toggle-password:hover { color: var(--primary-glow); }

        .form-control {
            width: 100%;
            padding: 12px 42px !important;
            background: var(--input-bg) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 12px !important;
            color: var(--text-main) !important;
            font-size: 13px !important;
            transition: all 0.3s ease !important;
            font-family: 'Outfit', sans-serif !important;
        }

        .form-control::placeholder { color: var(--text-muted); font-weight: 300; }

        .form-control:focus {
            outline: none !important;
            border-color: var(--primary-glow) !important;
            background: var(--glass-bg) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        .form-control:focus ~ i:not(.toggle-password),
        .form-control:focus + i:not(.toggle-password) {
            color: var(--primary-glow);
        }

        /* Password strength bar */
        .strength-bar-wrap {
            margin-top: 8px;
            height: 4px;
            background: var(--glass-border);
            border-radius: 10px;
            overflow: hidden;
            display: none;
        }

        .strength-bar {
            height: 100%;
            border-radius: 10px;
            width: 0%;
            transition: width 0.4s ease, background 0.4s ease;
        }

        .strength-label {
            font-size: 10px;
            margin-top: 4px;
            display: none;
            color: var(--text-muted);
        }

        /* Alert */
        .alert {
            border-radius: 10px;
            padding: 11px 14px;
            margin-bottom: 18px !important;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(10px);
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1) !important;
            color: #ef4444 !important;
            border: 1px solid rgba(239, 68, 68, 0.2) !important;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1) !important;
            color: #10b981 !important;
            border: 1px solid rgba(16, 185, 129, 0.2) !important;
        }

        /* Tombol */
        .btn-primary {
            width: 100% !important;
            padding: 13px !important;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6) !important;
            color: white !important;
            border: none !important;
            border-radius: 12px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3) !important;
            margin-top: 4px !important;
            font-family: 'Outfit', sans-serif !important;
        }

        .btn-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4) !important;
        }

        /* Sudah punya akun */
        .login-redirect {
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .login-redirect a {
            color: var(--primary-glow);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }

        .login-redirect a:hover {
            color: var(--secondary-glow);
            text-decoration: underline;
        }

        /* Divider */
        .divider {
            border: none;
            border-top: 1px solid var(--glass-border);
            margin: 16px 0 14px;
        }

        .demo-info {
            text-align: center;
            color: var(--text-muted);
            font-size: 11px;
            padding-top: 2px;
        }

        /* Validasi inline */
        .field-valid i { color: #10b981 !important; }
        .field-invalid i { color: #ef4444 !important; }

        /* Mobile */
        @media (max-width: 860px) {
            .register-box {
                grid-template-columns: 1fr !important;
                max-width: 480px !important;
                margin: 15px;
            }
            .register-left { display: none !important; }
            .register-right { padding: 28px 22px !important; }
            .form-grid { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body>

    <button class="theme-toggle" id="theme-toggle" title="Ubah Tema">
        <i class="fas fa-moon"></i>
    </button>

    <div class="grid-overlay"></div>
    <div class="ambient-light">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="register-container">
        <div class="register-box">

            <!-- LEFT SIDE -->
            <div class="register-left">
                <img src="assets/images/logo.png" alt="Logo" class="register-logo"
                     onerror="this.src='https://cdn-icons-png.flaticon.com/512/2991/2991148.png'">

                <p class="register-subtitle">
                    Daftarkan akun Anda dan mulai kelola SOP digital dengan lebih efisien dan terstruktur.
                </p>

                <!-- Keuntungan -->
                <ul class="benefit-list">
                    <li><i class="fas fa-check-circle"></i> Akses penuh ke semua modul SOP</li>
                    <li><i class="fas fa-check-circle"></i> Monitoring & tracking real-time</li>
                    <li><i class="fas fa-check-circle"></i> Notifikasi pembaruan dokumen</li>
                    <li><i class="fas fa-check-circle"></i> Keamanan data terjamin</li>
                </ul>

                <!-- Ilustrasi SVG -->
                <svg class="custom-illustration" style="margin-top:22px;" viewBox="0 0 400 280" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="grad-reg" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:0.8"/>
                            <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:0.8"/>
                        </linearGradient>
                        <linearGradient id="grad-card" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:var(--svg-screen-top);stop-opacity:0.9"/>
                            <stop offset="100%" style="stop-color:var(--svg-screen-bottom);stop-opacity:1"/>
                        </linearGradient>
                    </defs>
                    <g>
                        <animateTransform attributeName="transform" type="translate" values="0 0; 0 -6; 0 0" dur="4s" repeatCount="indefinite"/>
                        <!-- Body kartu -->
                        <rect x="60" y="30" width="280" height="200" rx="16" fill="url(#grad-card)" stroke="url(#grad-reg)" stroke-width="1.5" opacity="0.9"/>
                        <!-- Header kartu -->
                        <rect x="60" y="30" width="280" height="45" rx="16" fill="url(#grad-reg)" opacity="0.8"/>
                        <rect x="60" y="55" width="280" height="20" fill="url(#grad-reg)" opacity="0.8"/>
                        <!-- Avatar lingkaran -->
                        <circle cx="200" cy="52" r="20" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
                        <circle cx="200" cy="47" r="7" fill="rgba(255,255,255,0.7)"/>
                        <path d="M185 65 Q200 58 215 65" stroke="rgba(255,255,255,0.7)" stroke-width="2" fill="none" stroke-linecap="round"/>
                        <!-- Baris form dalam kartu -->
                        <rect x="85" y="95" width="230" height="8" rx="4" fill="url(#grad-reg)" opacity="0.4"/>
                        <rect x="85" y="95" width="80" height="8" rx="4" fill="url(#grad-reg)" opacity="0.8"/>
                        <rect x="85" y="115" width="230" height="8" rx="4" fill="rgba(100,116,139,0.4)" rx="4"/>
                        <rect x="85" y="135" width="230" height="8" rx="4" fill="rgba(100,116,139,0.4)" rx="4"/>
                        <rect x="85" y="155" width="230" height="8" rx="4" fill="rgba(100,116,139,0.4)" rx="4"/>
                        <!-- Tombol register -->
                        <rect x="85" y="180" width="230" height="30" rx="10" fill="url(#grad-reg)" opacity="0.9"/>
                        <rect x="150" y="189" width="100" height="8" rx="4" fill="rgba(255,255,255,0.8)"/>
                        <!-- Partikel animasi -->
                        <circle cx="90" cy="50" r="3" fill="#3b82f6" opacity="0.7">
                            <animate attributeName="cy" values="50;35;50" dur="2.5s" repeatCount="indefinite"/>
                            <animate attributeName="opacity" values="0;1;0" dur="2.5s" repeatCount="indefinite"/>
                        </circle>
                        <circle cx="320" cy="120" r="2" fill="#8b5cf6" opacity="0.7">
                            <animate attributeName="cy" values="120;100;120" dur="3s" repeatCount="indefinite"/>
                            <animate attributeName="opacity" values="0;1;0" dur="3s" repeatCount="indefinite"/>
                        </circle>
                        <circle cx="300" cy="50" r="4" fill="#f97316" opacity="0.5">
                            <animate attributeName="cx" values="300;315;300" dur="4s" repeatCount="indefinite"/>
                        </circle>
                    </g>
                </svg>
            </div>

            <!-- RIGHT SIDE -->
            <div class="register-right">
                <div class="form-header">
                    <h2>Buat Akun Baru</h2>
                    <p>Isi formulir di bawah ini untuk mendaftarkan diri Anda.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="registerForm">
                    <div class="form-grid">

                        <!-- Nama Lengkap -->
                        <div class="form-group full-width">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <div class="input-group" id="wrap-nama">
                                <i class="fas fa-id-card"></i>
                                <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control"
                                       placeholder="Masukkan nama lengkap Anda"
                                       value="<?php echo isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : ''; ?>" required>
                            </div>
                        </div>

                        <!-- Username -->
                        <div class="form-group">
                            <label for="username">Username</label>
                            <div class="input-group" id="wrap-username">
                                <i class="fas fa-user"></i>
                                <input type="text" id="username" name="username" class="form-control"
                                       placeholder="karyawan@sinergi.co.id"
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required autocomplete="off">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email">Email</label>
                            <div class="input-group" id="wrap-email">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="email" name="email" class="form-control"
                                       placeholder="karyawan@sinergi.co.id"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required autocomplete="off">
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-group">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="password" name="password" class="form-control"
                                       placeholder="Min. 6 karakter" required>
                                <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                            </div>
                            <div class="strength-bar-wrap" id="strengthBarWrap">
                                <div class="strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="strength-label" id="strengthLabel"></div>
                        </div>

                        <!-- Konfirmasi Password -->
                        <div class="form-group">
                            <label for="konfirmasi_password">Konfirmasi Password</label>
                            <div class="input-group" id="wrap-konfirmasi">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="form-control"
                                       placeholder="Ulangi password Anda" required>
                                <i class="fas fa-eye toggle-password" id="toggleKonfirmasi"></i>
                            </div>
                        </div>

                    </div><!-- end form-grid -->

                    <button type="submit" class="btn-primary">
                        <i class="fas fa-user-plus"></i> Daftar Sekarang
                    </button>
                </form>

                <div class="login-redirect">
                    Sudah punya akun?
                    <a href="index.php">Masuk di sini</a>
                </div>

                <hr class="divider">
                <div class="demo-info">
                    <p style="margin:0;">&copy; <?php echo date('Y'); ?> PT. Sinergi Nusantara Integrasi</p>
                    <p style="margin: 5px 0 0 0; font-size: 10px; color: #aaa; letter-spacing: 0.5px;">
                        Developed by <span style="color: #666;">Rahul Candra</span>
                    </p>
                </div>
            </div>

        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // --- Toggle Tema ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = themeToggleBtn.querySelector('i');
        const htmlEl = document.documentElement;

        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') {
            htmlEl.setAttribute('data-theme', 'light');
            themeIcon.classList.replace('fa-moon', 'fa-sun');
        }

        themeToggleBtn.addEventListener('click', function () {
            if (htmlEl.getAttribute('data-theme') === 'light') {
                htmlEl.removeAttribute('data-theme');
                themeIcon.classList.replace('fa-sun', 'fa-moon');
                localStorage.setItem('theme', 'dark');
            } else {
                htmlEl.setAttribute('data-theme', 'light');
                themeIcon.classList.replace('fa-moon', 'fa-sun');
                localStorage.setItem('theme', 'light');
            }
        });

        // --- Show/Hide Password ---
        function setupToggle(toggleId, inputId) {
            const btn = document.getElementById(toggleId);
            const inp = document.getElementById(inputId);
            if (!btn || !inp) return;
            btn.addEventListener('click', function () {
                const t = inp.getAttribute('type') === 'password' ? 'text' : 'password';
                inp.setAttribute('type', t);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
        setupToggle('togglePassword', 'password');
        setupToggle('toggleKonfirmasi', 'konfirmasi_password');

        // --- Validasi Konfirmasi Password (real-time) ---
        const konfInput = document.getElementById('konfirmasi_password');
        const wrapKonf = document.getElementById('wrap-konfirmasi');

        konfInput.addEventListener('input', function () {
            if (this.value.length === 0) {
                wrapKonf.classList.remove('field-valid', 'field-invalid');
                return;
            }
            if (this.value === passInput.value) {
                wrapKonf.classList.add('field-valid');
                wrapKonf.classList.remove('field-invalid');
            } else {
                wrapKonf.classList.add('field-invalid');
                wrapKonf.classList.remove('field-valid');
            }
        });

        // --- Validasi Email (real-time) ---
        const emailInput = document.getElementById('email');
        const wrapEmail = document.getElementById('wrap-email');

        emailInput.addEventListener('blur', function () {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value.length === 0) return;
            if (re.test(this.value)) {
                wrapEmail.classList.add('field-valid');
                wrapEmail.classList.remove('field-invalid');
            } else {
                wrapEmail.classList.add('field-invalid');
                wrapEmail.classList.remove('field-valid');
            }
        });

    });
    </script>
</body>
</html>