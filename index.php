<?php
session_start();
require_once 'config/database.php';

 $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] == 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: user/dashboard.php');
            }
            exit();
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
    <title>Login - SOP Digital System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-glow: #3b82f6;
            --secondary-glow: #8b5cf6;
            --accent-orange: #f97316;
            --bg-dark: #0f172a;
            --glass-bg: rgba(15, 23, 42, 0.7); /* Sedikit lebih solid agar jelas */
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --input-bg: rgba(0, 0, 0, 0.4);
        }

        body {
            font-family: 'Outfit', sans-serif !important;
            background-color: #020617 !important;
            color: var(--text-main);
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin: 0;
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
            background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: -1;
            mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        }

        /* --- CONTAINER (UKURAN DIKECILKAN) --- */
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
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 40px rgba(59, 130, 246, 0.1) !important;
            
            /* UKURAN BARU: Lebih Kompak */
            max-width: 850px !important; 
            width: 100% !important;
            
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            overflow: hidden;
            animation: cardEntrance 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* --- LEFT SIDE (BRANDING) --- */
        .login-left {
            background: linear-gradient(160deg, rgba(30, 58, 138, 0.5) 0%, rgba(15, 23, 42, 0.7) 100%) !important;
            /* Padding Diperkecil */
            padding: 40px 30px !important; 
            
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            text-align: center !important;
            color: white;
            position: relative;
            border-right: 1px solid rgba(255,255,255,0.05) !important;
        }

        .login-logo {
            width: 70px; /* Logo lebih kecil */
            height: auto;
            margin-bottom: 20px !important;
            filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.4));
            animation: floatLogo 6s ease-in-out infinite;
            position: relative;
            z-index: 5;
        }

        @keyframes floatLogo {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .login-title {
            font-size: 26px !important; /* Font judul disesuaikan agar pas */
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            margin-bottom: 15px !important;
            line-height: 1.2 !important;
            background: linear-gradient(135deg, #ffffff 0%, #60a5fa 50%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 0 8px rgba(59, 130, 246, 0.4));
            position: relative;
            z-index: 5;
        }

        .login-subtitle {
            font-size: 13px !important;
            color: var(--text-muted) !important;
            line-height: 1.6 !important;
            margin-bottom: 25px !important;
            font-weight: 300;
            max-width: 300px; /* Lebar teks dibatasi biar rapi */
            position: relative;
            z-index: 5;
        }

        /* --- STYLE SVG (DISESUAIKAN UKURANNYA) --- */
        .custom-illustration {
            width: 100%;
            /* Batasi tinggi agar tidak mendorong kotak menjadi besar */
            max-height: 220px; 
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
            /* Padding diperkecil biar pas */
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
            color: white !important;
            font-size: 24px !important; /* Judul lebih kecil */
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
            margin-bottom: 22px !important; /* Jarak input dipersempit */
            position: relative;
        }

        .form-group label {
            color: #e2e8f0 !important;
            font-size: 12px !important; /* Label sedikit lebih kecil */
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
            color: #64748b;
            font-size: 16px;
            transition: 0.3s;
            z-index: 2;
        }

        .form-control {
            width: 100%;
            padding: 14px 14px 14px 45px !important; /* Padding input disesuaikan */
            background: var(--input-bg) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 12px !important;
            color: white !important;
            font-size: 14px !important;
            transition: all 0.3s ease !important;
            font-family: 'Outfit', sans-serif !important;
            box-sizing: border-box;
        }

        .form-control::placeholder {
            color: #475569;
            font-weight: 300;
        }

        .form-control:focus {
            outline: none !important;
            border-color: var(--primary-glow) !important;
            background: rgba(0, 0, 0, 0.6) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        .form-control:focus + i {
            color: var(--primary-glow);
            transform: translateY(-50%) scale(1.1);
        }

        .input-hint {
            display: none;
            margin-top: 8px;
            padding: 8px 12px;
            background: rgba(59, 130, 246, 0.1);
            border-left: 3px solid var(--primary-glow);
            border-radius: 6px;
            font-size: 11px;
            color: #bfdbfe;
            animation: fadeInHint 0.3s ease forwards;
        }

        .input-hint i {
            margin-right: 5px;
            color: var(--primary-glow);
        }

        .input-hint strong {
            color: white;
            font-weight: 600;
            background: rgba(255,255,255,0.1);
            padding: 1px 5px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .input-hint strong:hover {
            background: rgba(255,255,255,0.2);
        }

        @keyframes fadeInHint {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .btn-primary {
            width: 100% !important;
            padding: 14px !important; /* Tombol sedikit lebih pendek */
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
        }

        .btn-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4) !important;
        }

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
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1) !important;
            color: #fca5a5 !important;
            border: 1px solid rgba(239, 68, 68, 0.2) !important;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.1) !important;
            color: #6ee7b7 !important;
            border: 1px solid rgba(16, 185, 129, 0.2) !important;
        }

        .demo-info {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            color: #64748b;
            font-size: 11px;
        }

        /* Mobile Responsive */
        @media (max-width: 850px) {
            .login-box {
                grid-template-columns: 1fr !important;
                max-width: 450px !important;
                margin: 15px;
            }
            .login-left { display: none !important; }
            .login-right { padding: 30px 25px !important; }
        }
    </style>
</head>
<body>

    <div class="grid-overlay"></div>
    <div class="ambient-light">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="login-container">
        <div class="login-box">
            <div class="login-left">
                <img src="assets/images/logo.png" alt="Logo" class="login-logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2991/2991148.png'">
                
                <h1 class="login-title">SINERGI<br>NUSANTARA<br>INTEGRASI</h1>
                <p class="login-subtitle">
                    Platform digital yang terintegrasi untuk solusi bisnis di masa depan.
                </p>
                
                <!-- SVG Custom Kompak -->
                <svg class="custom-illustration" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="grad-body" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:0.8" />
                            <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:0.8" />
                        </linearGradient>
                        <linearGradient id="grad-screen" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#1e293b;stop-opacity:0.9" />
                            <stop offset="100%" style="stop-color:#0f172a;stop-opacity:1" />
                        </linearGradient>
                        <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="4" result="blur"/>
                            <feComposite in="SourceGraphic" in2="blur" operator="over"/>
                        </filter>
                    </defs>

                    <g>
                        <animateTransform attributeName="transform" type="translate" values="0 0; 0 -8; 0 0" dur="4s" repeatCount="indefinite" />

                        <!-- Laptop Base -->
                        <path d="M40 260 L200 320 L360 260 L200 200 Z" fill="rgba(255,255,255,0.05)" stroke="url(#grad-body)" stroke-width="2" />
                        <!-- Laptop Screen Back -->
                        <path d="M70 100 L70 260 L330 260 L330 100 Z" fill="rgba(15, 23, 42, 0.4)" stroke="url(#grad-body)" stroke-width="2" />
                        <!-- Screen Content -->
                        <rect x="85" y="120" width="230" height="120" rx="4" fill="url(#grad-screen)" stroke="rgba(255,255,255,0.1)" />
                        
                        <!-- Code Lines -->
                        <rect x="100" y="140" width="80" height="6" rx="3" fill="#3b82f6" opacity="0.6" />
                        <rect x="100" y="155" width="120" height="6" rx="3" fill="#64748b" opacity="0.5" />
                        <rect x="100" y="170" width="100" height="6" rx="3" fill="#64748b" opacity="0.5" />
                        <rect x="100" y="185" width="60" height="6" rx="3" fill="#64748b" opacity="0.5" />

                        <!-- Shield Icon -->
                        <g transform="translate(180, 140)">
                            <path d="M0 0 L20 -5 L40 0 V15 C40 25 20 40 20 40 C20 40 0 25 0 15 Z" fill="url(#grad-body)" filter="url(#glow)" opacity="0.9">
                                <animate attributeName="opacity" values="0.6;1;0.6" dur="3s" repeatCount="indefinite" />
                            </path>
                            <path d="M12 15 L18 22 L28 10" stroke="white" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                        
                        <!-- Floating Particles -->
                        <circle cx="100" cy="80" r="3" fill="#3b82f6" opacity="0.8">
                            <animate attributeName="cy" values="80;70;80" dur="2s" repeatCount="indefinite" />
                            <animate attributeName="opacity" values="0;1;0" dur="2s" repeatCount="indefinite" />
                        </circle>
                        <circle cx="300" cy="150" r="2" fill="#8b5cf6" opacity="0.8">
                            <animate attributeName="cy" values="150;130;150" dur="2.5s" repeatCount="indefinite" />
                            <animate attributeName="opacity" values="0;1;0" dur="2.5s" repeatCount="indefinite" />
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
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['logout'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Anda telah berhasil logout.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" class="form-control" 
                                   placeholder="Masukkan username Anda" required autocomplete="off">
                        </div>
                        <div class="input-hint" id="hint-username">
                            <i class="fas fa-lightbulb"></i> Saran: <strong>admin</strong> atau <strong>user</strong>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Masukkan password Anda" required>
                        </div>
                        <div class="input-hint" id="hint-password">
                            <i class="fas fa-lightbulb"></i> Saran: <strong>123456</strong>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-rocket"></i> Login System
                    </button>
                </form>
                
                <div class="demo-info">
                    <p>&copy; <?php echo date('Y'); ?> PT. Sinergi Nusantara Integrasi</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const usernameHint = document.getElementById('hint-username');
            const passwordHint = document.getElementById('hint-password');

            function showHint(hintElement) {
                hintElement.style.display = 'block';
                setTimeout(() => {
                    hintElement.style.opacity = '1';
                }, 10);
            }

            function hideHint(hintElement) {
                hintElement.style.opacity = '0';
                setTimeout(() => {
                    hintElement.style.display = 'none';
                }, 300);
            }

            usernameInput.addEventListener('focus', function() {
                showHint(usernameHint);
            });

            usernameInput.addEventListener('blur', function() {
                hideHint(usernameHint);
            });

            passwordInput.addEventListener('focus', function() {
                showHint(passwordHint);
            });

            passwordInput.addEventListener('blur', function() {
                hideHint(passwordHint);
            });
            
            document.querySelectorAll('.input-hint strong').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.stopPropagation(); 
                    const suggestion = this.innerText;
                    
                    if (this.parentElement.id === 'hint-username') {
                        usernameInput.value = suggestion;
                    } else if (this.parentElement.id === 'hint-password') {
                        passwordInput.value = suggestion;
                    }
                });
            });
        });
    </script>
</body>
</html>