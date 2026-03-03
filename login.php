<?php
session_start();

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy(); 
    header('Location: login.php?logout=1'); 
    exit();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit();
}

require_once 'config/database.php';

$error = '';
// --- TAMBAHAN UNTUK JEDA LOGIN ---
$login_success = false;
$redirect_url = '';
// ---------------------------------

$saved_username = isset($_COOKIE['remember_username']) ? $_COOKIE['remember_username'] : '';
$registered = isset($_GET['registered']) ? true : false;
$reset_success = isset($_GET['reset']) ? true : false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']) ? true : false;
    
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            
            if ($remember_me) {
                setcookie('remember_username', $username, time() + (86400 * 30), "/"); 
            } else {
                setcookie('remember_username', '', time() - 3600, "/"); 
            }
            
            // --- MODIFIKASI REDIRECT ---
            // Kita set variabel true, redirect akan diurus oleh JavaScript di bawah
            $login_success = true;
            if ($user['role'] == 'admin') {
                $redirect_url = 'admin/dashboard.php';
            } else {
                $redirect_url = 'user/dashboard.php';
            }
            // ---------------------------
            
        } else {
            $error = 'Password salah!';
        }
    } else {
        $error = 'Username tidak ditemukan!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SOP Digital System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- TEMA GELAP (DEFAULT) --- */
        :root {
            --primary-glow:   #3b82f6;
            --secondary-glow: #8b5cf6;
            --accent-orange:  #f97316;
            
            --bg-main:       #020617;
            --bg-dark:       #0f172a;
            --glass-bg:      rgba(15, 23, 42, 0.7);
            --glass-border:  rgba(255, 255, 255, 0.1);
            --text-main:     #f8fafc;
            --text-muted:    #94a3b8;
            --input-bg:      rgba(0, 0, 0, 0.4);
            
            --left-panel-bg: linear-gradient(160deg, rgba(30, 58, 138, 0.5) 0%, rgba(15, 23, 42, 0.7) 100%);
            --grid-color:    rgba(255, 255, 255, 0.03);
            --svg-screen-top:    #1e293b;
            --svg-screen-bottom: #0f172a;
        }

        /* --- TEMA TERANG --- */
        [data-theme="light"] {
            --bg-main:       #f1f5f9;
            --bg-dark:       #e2e8f0;
            --glass-bg:      rgba(255, 255, 255, 0.85);
            --glass-border:  rgba(0, 0, 0, 0.1);
            --text-main:     #0f172a;
            --text-muted:    #64748b;
            --input-bg:      rgba(255, 255, 255, 0.9);
            
            --left-panel-bg: linear-gradient(160deg, rgba(219, 234, 254, 0.7) 0%, rgba(241, 245, 249, 0.9) 100%);
            --grid-color:    rgba(0, 0, 0, 0.04);
            --svg-screen-top:    #cbd5e1;
            --svg-screen-bottom: #94a3b8;
        }

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
            transition: background-color 0.5s ease, color 0.5s ease;
        }

        /* --- TOGGLE BUTTON --- */
        .theme-toggle {
            position: absolute;
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

        /* --- BACK TO HOME BUTTON --- */
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            height: 45px;
            padding: 0 18px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 500;
            z-index: 100;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .back-home:hover {
            color: var(--primary-glow);
            border-color: var(--primary-glow);
            transform: translateX(-2px);
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
            0%   { transform: translate(0, 0); }
            100% { transform: translate(50px, 30px); }
        }

        .grid-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image:
                linear-gradient(var(--grid-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: -1;
            mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
            -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
            transition: background-image 0.5s ease;
        }

        /* --- CONTAINER --- */
        .login-container {
            width: 100%;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }

        .login-box {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(25px) !important;
            -webkit-backdrop-filter: blur(25px) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 24px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3), 0 0 40px rgba(59, 130, 246, 0.1) !important;
            max-width: 850px !important;
            width: 100% !important;
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            overflow: hidden;
            animation: cardEntrance 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
            transition: background 0.5s ease, border-color 0.5s ease;
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* --- LEFT SIDE (BRANDING) --- */
        .login-left {
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

        /* --- LOGO IMAGE --- */
        .login-logo-img {
            height: 52px;
            width: auto;
            max-width: 220px;
            object-fit: contain;
            margin-bottom: 18px;
            filter: drop-shadow(0 0 12px rgba(59, 130, 246, 0.3));
            animation: floatLogo 6s ease-in-out infinite;
            position: relative;
            z-index: 5;
            transition: filter 0.3s ease;
        }
        [data-theme="light"] .login-logo-img {
            filter: drop-shadow(0 0 8px rgba(29, 78, 216, 0.2));
        }
        .login-logo-img:hover {
            filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.5));
        }

        @keyframes floatLogo {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-8px); }
        }

        .login-title {
            font-size: 22px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            margin-bottom: 12px !important;
            line-height: 1.2 !important;
            background: linear-gradient(135deg, var(--text-main) 0%, #60a5fa 50%, #3b82f6 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-subtitle {
            font-size: 12.5px !important;
            color: var(--text-muted) !important;
            line-height: 1.65 !important;
            margin-bottom: 22px !important;
            font-weight: 400;
            max-width: 280px;
            position: relative;
            z-index: 5;
        }

        /* --- SVG ILLUSTRATION --- */
        .custom-illustration {
            width: 100%;
            max-height: 200px;
            height: auto;
            opacity: 0.95;
            filter: drop-shadow(0 0 15px rgba(59, 130, 246, 0.2));
            transition: all 0.5s ease;
        }
        .login-left:hover .custom-illustration {
            transform: translateY(-10px) scale(1.02);
            opacity: 1;
            filter: drop-shadow(0 0 25px rgba(59, 130, 246, 0.4));
        }

        /* --- RIGHT SIDE (FORM) --- */
        .login-right {
            padding: 40px 35px !important;
            background: transparent !important;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 25px !important;
        }
        .form-header h2 {
            color: var(--text-main) !important;
            font-size: 24px !important;
            margin-bottom: 8px !important;
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        .form-header p {
            color: var(--text-muted) !important;
            font-size: 14px !important;
            margin: 0 !important;
        }

        .form-group {
            margin-bottom: 22px !important;
            position: relative;
        }
        .form-group label {
            color: var(--text-main) !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px !important;
            display: block;
            margin-left: 5px;
        }

        .input-group {
            position: relative;
            width: 100%;
        }
        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
            transition: 0.3s;
            z-index: 2;
        }
        .input-group i.toggle-password {
            left: auto !important;
            right: 16px;
            cursor: pointer;
            z-index: 10;
        }
        .input-group i.toggle-password:hover {
            color: var(--primary-glow);
        }

        .form-control {
            width: 100%;
            padding: 14px 45px 14px 45px !important;
            background: var(--input-bg) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 12px !important;
            color: var(--text-main) !important;
            font-size: 14px !important;
            transition: all 0.3s ease !important;
            font-family: 'Outfit', sans-serif !important;
            box-sizing: border-box;
        }
        .form-control::placeholder {
            color: var(--text-muted);
            font-weight: 300;
        }
        .form-control:focus {
            outline: none !important;
            border-color: var(--primary-glow) !important;
            background: var(--glass-bg) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        /* --- FORM OPTIONS --- */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: -10px;
            margin-bottom: 22px;
            font-size: 13px;
        }
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.3s;
            margin: 0;
            text-transform: none;
            letter-spacing: normal;
            font-weight: 400 !important;
        }
        .remember-me:hover { color: var(--text-main); }
        .remember-me input[type="checkbox"] {
            accent-color: var(--primary-glow);
            width: 15px;
            height: 15px;
            cursor: pointer;
        }
        .forgot-password {
            color: var(--primary-glow);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s, text-decoration 0.3s;
        }
        .forgot-password:hover {
            color: var(--secondary-glow);
            text-decoration: underline;
        }

        .btn-primary {
            width: 100% !important;
            padding: 14px !important;
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
            margin-top: 5px !important;
            font-family: 'Outfit', sans-serif !important;
        }
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4) !important;
        }
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed !important;
        }

        /* --- ALERTS --- */
        .alert {
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 20px !important;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(10px);
            animation: slideDown 0.4s ease;
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
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
        .alert-info {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #3b82f6 !important;
            border: 1px solid rgba(59, 130, 246, 0.2) !important;
        }

        /* --- FOOTER INFO --- */
        .demo-info {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--glass-border);
            text-align: center;
            color: var(--text-muted);
            font-size: 11px;
        }

        /* Mobile Responsive */
        @media (max-width: 850px) {
            .login-box {
                grid-template-columns: 1fr !important;
                max-width: 450px !important;
                margin: 15px;
            }
            .login-left  { display: none !important; }
            .login-right { padding: 30px 25px !important; }
            .back-home   { display: none; }
        }
    </style>
</head>
<body>

    <a href="index.php" class="back-home">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <button class="theme-toggle" id="theme-toggle" title="Ubah Tema">
        <i class="fas fa-moon"></i>
    </button>

    <div class="grid-overlay"></div>
    <div class="ambient-light">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="login-container">
        <div class="login-box">

            <div class="login-left">
                <img src="assets/images/logo.png"
                     alt="Sinergi Nusantara Integrasi"
                     class="login-logo-img"
                     onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='block'">

                <h1 class="login-title" id="logo-fallback" style="display:none;">
                    SINERGI<br>NUSANTARA<br>INTEGRASI
                </h1>

                <p class="login-subtitle">
                    Sinergi Nusantara Integrasi adalah penyedia solusi teknologi terintegrasi kelas dunia yang menghadirkan perangkat lunak inovatif untuk membantu efisiensi dan efektivitas bisnis.
                </p>

                <svg class="custom-illustration" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="grad-body" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%"   style="stop-color:#3b82f6;stop-opacity:0.8" />
                            <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:0.8" />
                        </linearGradient>
                        <linearGradient id="grad-screen" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%"   style="stop-color:var(--svg-screen-top);stop-opacity:0.9" />
                            <stop offset="100%" style="stop-color:var(--svg-screen-bottom);stop-opacity:1" />
                        </linearGradient>
                        <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="4" result="blur"/>
                            <feComposite in="SourceGraphic" in2="blur" operator="over"/>
                        </filter>
                    </defs>

                    <g>
                        <animateTransform attributeName="transform" type="translate" values="0 0; 0 -8; 0 0" dur="4s" repeatCount="indefinite" />

                        <path d="M40 260 L200 320 L360 260 L200 200 Z" fill="rgba(150,150,150,0.1)" stroke="url(#grad-body)" stroke-width="2" />
                        <path d="M70 100 L70 260 L330 260 L330 100 Z" fill="rgba(15, 23, 42, 0.2)" stroke="url(#grad-body)" stroke-width="2" />
                        <rect x="85" y="120" width="230" height="120" rx="4" fill="url(#grad-screen)" stroke="var(--glass-border)" />

                        <rect x="100" y="140" width="80" height="6" rx="3" fill="#3b82f6" opacity="0.6" />
                        <rect x="100" y="155" width="120" height="6" rx="3" fill="#64748b" opacity="0.5" />
                        <rect x="100" y="170" width="100" height="6" rx="3" fill="#64748b" opacity="0.5" />
                        <rect x="100" y="185" width="60"  height="6" rx="3" fill="#64748b" opacity="0.5" />

                        <g transform="translate(180, 140)">
                            <path d="M0 0 L20 -5 L40 0 V15 C40 25 20 40 20 40 C20 40 0 25 0 15 Z" fill="url(#grad-body)" filter="url(#glow)" opacity="0.9">
                                <animate attributeName="opacity" values="0.6;1;0.6" dur="3s" repeatCount="indefinite" />
                            </path>
                            <path d="M12 15 L18 22 L28 10" stroke="white" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>

                        <circle cx="100" cy="80" r="3" fill="#3b82f6" opacity="0.8">
                            <animate attributeName="cy"      values="80;70;80"  dur="2s"   repeatCount="indefinite" />
                            <animate attributeName="opacity" values="0;1;0"     dur="2s"   repeatCount="indefinite" />
                        </circle>
                        <circle cx="300" cy="150" r="2" fill="#8b5cf6" opacity="0.8">
                            <animate attributeName="cy"      values="150;130;150" dur="2.5s" repeatCount="indefinite" />
                            <animate attributeName="opacity" values="0;1;0"       dur="2.5s" repeatCount="indefinite" />
                        </circle>
                        <circle cx="280" cy="90" r="4" fill="#3b82f6" opacity="0.6">
                            <animate attributeName="cx" values="280;290;280" dur="4s" repeatCount="indefinite" />
                        </circle>
                    </g>
                </svg>
            </div>

            <div class="login-right">
                <div class="form-header">
                    <h2>Selamat Datang!</h2>
                    <p>Silahkan masuk untuk mengakses akun Anda.</p>
                </div>

                <?php if ($login_success): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-circle-notch fa-spin"></i> Login berhasil! Silahkan tunggu...
                    </div>
                <?php endif; ?>
                <?php if ($error && !$login_success): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($registered && !$login_success): ?>
                    <div class="alert alert-success" id="alert-registered">
                        <i class="fas fa-check-circle"></i> Akun berhasil dibuat! Silahkan login.
                    </div>
                <?php endif; ?>

                <?php if ($reset_success && !$login_success): ?>
                    <div class="alert alert-success" id="alert-reset">
                        <i class="fas fa-check-circle"></i> Password berhasil diubah! Silahkan login.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['logout']) && !$login_success): ?>
                    <div class="alert alert-success" id="alert-logout">
                        <i class="fas fa-check-circle"></i> Anda telah berhasil logout.
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Email / Username</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" class="form-control"
                                   placeholder="Masukkan username Anda"
                                   value="<?php echo htmlspecialchars($saved_username); ?>"
                                   required autocomplete="off" <?php echo $login_success ? 'readonly' : ''; ?>>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="Masukkan password Anda" required <?php echo $login_success ? 'readonly' : ''; ?>>
                            <i class="fas fa-eye toggle-password" id="togglePassword" title="Show/Hide Password"></i>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember_me" id="remember_me"
                                   <?php echo ($saved_username != '') ? 'checked' : ''; ?> <?php echo $login_success ? 'disabled' : ''; ?>>
                            <span>Ingat Saya</span>
                        </label>
                        <a href="forgot_password.php" class="forgot-password">Lupa Password?</a>
                    </div>

                    <button type="submit" class="btn-primary" id="btn-login">
                        <i class="fas fa-rocket"></i> Login System
                    </button>

                    <div style="text-align:center; margin-top:18px; font-size:13px; color:var(--text-muted);">
                        Belum punya akun?
                        <a href="register.php" style="color:var(--primary-glow); font-weight:600; text-decoration:none; transition:color 0.3s;">
                            Daftar Sekarang
                        </a>
                    </div>
                </form>

                <div class="demo-info">
                    <p>&copy; <?php echo date('Y'); ?> PT. Sinergi Nusantara Integrasi</p>
                    <p style="margin:5px 0 0 0; font-size:10px; color:#aaa; letter-spacing:0.5px;">
                        Developed by <span style="color:#666;">Rahul Candra</span>
                    </p>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // --- JAVASCRIPT UNTUK JEDA REDIRECT (2 DETIK) ---
            <?php if ($login_success): ?>
                const btnLogin = document.getElementById('btn-login');
                // Ubah teks dan matikan tombol agar tidak di-klik 2x
                btnLogin.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Mengalihkan...';
                btnLogin.disabled = true;

                // Hitung mundur 2000 milidetik (2 detik) lalu redirect
                setTimeout(function() {
                    window.location.href = '<?php echo $redirect_url; ?>';
                }, 2000);
            <?php endif; ?>
            // ------------------------------------------------

            // --- SINKRON TEMA DENGAN LANDING PAGE ---
            const themeToggleBtn = document.getElementById('theme-toggle');
            const themeIcon      = themeToggleBtn.querySelector('i');
            const htmlEl         = document.documentElement;

            // Baca preferensi yang sama dengan index.php (key: sni-theme)
            const savedTheme = localStorage.getItem('sni-theme');
            if (savedTheme === 'light') {
                htmlEl.setAttribute('data-theme', 'light');
                themeIcon.classList.replace('fa-moon', 'fa-sun');
            }

            themeToggleBtn.addEventListener('click', function () {
                if (htmlEl.getAttribute('data-theme') === 'light') {
                    htmlEl.removeAttribute('data-theme');
                    themeIcon.classList.replace('fa-sun', 'fa-moon');
                    localStorage.setItem('sni-theme', 'dark');
                } else {
                    htmlEl.setAttribute('data-theme', 'light');
                    themeIcon.classList.replace('fa-moon', 'fa-sun');
                    localStorage.setItem('sni-theme', 'light');
                }
            });

            // --- AUTO HIDE ALERTS (3 DETIK) ---
            ['alert-logout', 'alert-registered', 'alert-reset'].forEach(function(id) {
                const el = document.getElementById(id);
                if (!el) return;
                setTimeout(function () {
                    el.style.opacity   = '0';
                    el.style.transform = 'translateY(-10px)';
                    setTimeout(function () { el.style.display = 'none'; }, 500);
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 3000);
            });

            // --- SHOW / HIDE PASSWORD ---
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput  = document.getElementById('password');

            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>