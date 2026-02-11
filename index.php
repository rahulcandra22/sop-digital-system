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
    <!-- Import Font Keren: Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- CORE SETUP --- */
        :root {
            --primary-glow: #3b82f6; /* Electric Blue */
            --secondary-glow: #8b5cf6; /* Violet */
            --bg-dark: #0f172a;
            --glass-bg: rgba(15, 23, 42, 0.7); /* Sedikit lebih solid */
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

        /* --- BACKGROUND ANIMATION --- */
        .ambient-light {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            overflow: hidden;
            background: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.15), transparent 25%),
                        radial-gradient(circle at 85% 30%, rgba(139, 92, 246, 0.15), transparent 25%);
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            animation: moveOrb 20s infinite alternate;
        }
        .orb-1 { width: 400px; height: 400px; background: var(--primary-glow); top: -100px; left: -100px; }
        .orb-2 { width: 500px; height: 500px; background: var(--secondary-glow); bottom: -150px; right: -150px; animation-delay: -5s; }

        @keyframes moveOrb {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 30px); }
        }

        /* Grid Pattern Overlay */
        .grid-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: -1;
            mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        }

        /* --- LOGIN CONTAINER --- */
        .login-container {
            width: 100%;
            padding: 20px; /* Padding container luar */
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
            
            max-width: 1000px !important;
            width: 100% !important;
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            overflow: hidden;
            animation: cardEntrance 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: scale(0.95) translateY(30px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* --- LEFT SIDE (BRANDING) --- */
        .login-left {
            background: linear-gradient(160deg, rgba(30, 58, 138, 0.6) 0%, rgba(15, 23, 42, 0.8) 100%) !important;
            padding: 60px 40px !important;
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
            width: 100px; /* Diperkecil sedikit agar proporsional */
            height: auto;
            margin-bottom: 30px !important;
            filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.4));
            animation: floatLogo 6s ease-in-out infinite;
        }

        @keyframes floatLogo {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .login-title {
            font-size: 30px !important;
            font-weight: 700 !important;
            margin-bottom: 20px !important;
            line-height: 1.3 !important;
            background: linear-gradient(to right, #fff, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-subtitle {
            font-size: 14px !important;
            color: var(--text-muted) !important;
            line-height: 1.7 !important; /* Line height diperbesar agar nyaman dibaca */
            margin-bottom: 40px !important;
            font-weight: 300;
        }

        .login-illustration {
            width: 100% !important;
            max-width: 300px !important;
            filter: drop-shadow(0 0 20px rgba(37, 99, 235, 0.4));
            transition: transform 0.5s ease;
        }
        .login-left:hover .login-illustration {
            transform: scale(1.02);
        }

        /* --- RIGHT SIDE (FORM) --- */
        .login-right {
            padding: 60px 50px !important; /* Padding form diperbesar agar lega */
            background: transparent !important;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 35px !important; /* Jarak header ke form */
        }

        .form-header h2 {
            color: white !important;
            font-size: 28px !important;
            margin-bottom: 10px !important; /* Jarak judul ke subjudul */
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        .form-header p {
            color: var(--text-muted) !important;
            font-size: 15px !important; /* Font size diperbesar sedikit */
            margin: 0 !important;
        }

        /* Form Group & Input Spacing */
        .form-group {
            margin-bottom: 35px !important; /* Jarak antar field input diperjauh */
            position: relative;
        }

        .form-group label {
            color: #e2e8f0 !important;
            font-size: 13px !important;
            font-weight: 600 !important; /* Lebih tebal agar jelas */
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 12px !important;
            display: block;
            margin-left: 5px; /* Sedikit indentasi label */
        }

        .input-group {
            position: relative;
            width: 100%;
        }

        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 18px;
            transition: 0.3s;
            z-index: 2;
        }

        .form-control {
            width: 100%;
            padding: 16px 16px 16px 50px !important; /* Padding dalam input */
            background: var(--input-bg) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 12px !important;
            color: white !important;
            font-size: 15px !important;
            transition: all 0.3s ease !important;
            font-family: 'Outfit', sans-serif !important;
            box-sizing: border-box; /* Penting agar padding tidak merusak lebar */
        }

        .form-control::placeholder {
            color: #475569;
            font-weight: 300;
        }

        .form-control:focus {
            outline: none !important;
            border-color: var(--primary-glow) !important;
            background: rgba(0, 0, 0, 0.6) !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1) !important; /* Focus ring halus */
        }

        .form-control:focus + i {
            color: var(--primary-glow);
            transform: translateY(-50%) scale(1.1); /* Ikon sedikit membesar saat focus */
        }

        /* --- HINT BOX (Saran Login) --- */
        .input-hint {
            display: none; /* Hidden default */
            margin-top: 10px;
            padding: 10px 14px;
            background: rgba(59, 130, 246, 0.1);
            border-left: 3px solid var(--primary-glow);
            border-radius: 6px;
            font-size: 12px;
            color: #bfdbfe;
            animation: fadeInHint 0.3s ease forwards;
        }

        .input-hint i {
            margin-right: 6px;
            color: var(--primary-glow);
        }

        .input-hint strong {
            color: white;
            font-weight: 600;
            background: rgba(255,255,255,0.1);
            padding: 2px 6px;
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

        /* Button */
        .btn-primary {
            width: 100% !important;
            padding: 16px !important;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6) !important;
            color: white !important;
            border: none !important;
            border-radius: 12px !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3) !important;
            margin-top: 10px !important;
        }

        .btn-primary:hover {
            transform: translateY(-3px) !important;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.5) !important;
        }

        /* Alert Boxes */
        .alert {
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 30px !important;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
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
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }

        /* Mobile Responsive */
        @media (max-width: 900px) {
            .login-box {
                grid-template-columns: 1fr !important;
                max-width: 500px !important;
                margin: 20px;
            }
            .login-left { display: none !important; }
            .login-right { padding: 40px 30px !important; }
        }
    </style>
</head>
<body>

    <!-- Background Elements -->
    <div class="grid-overlay"></div>
    <div class="ambient-light">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="login-container">
        <div class="login-box">
            <!-- Left Side -->
            <div class="login-left">
                <img src="assets/images/logo.png" alt="Logo" class="login-logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2991/2991148.png'">
                
                <h1 class="login-title">SINERGI<br>NUSANTARA<br>INTEGRASI</h1>
                <p class="login-subtitle">
                    PT. Sinergi Nusantara Integrasi adalah perusahaan dengan solusi teknologi kelas
                    dunia yang memberikan berbagai solusi inovatif berdasarkan teknologi yang terintegrasi berdasarkan
                    pada produk perangkat lunak dengan kinerja terbaik bagi dunia usaha.
                </p>
                
                <svg class="login-illustration" viewBox="0 0 500 400" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <rect x="100" y="80" width="300" height="240" rx="20" fill="rgba(255,255,255,0.05)" stroke="url(#grad1)" stroke-width="2"/>
                    <circle cx="250" cy="200" r="60" fill="rgba(59, 130, 246, 0.2)" />
                    <path d="M250 150 L280 180 L250 230 L220 180 Z" fill="#3b82f6" opacity="0.8"/>
                    <line x1="250" y1="150" x2="250" y2="100" stroke="#3b82f6" stroke-width="2" stroke-dasharray="5,5"/>
                    <line x1="280" y1="180" x2="380" y2="180" stroke="#8b5cf6" stroke-width="2" stroke-dasharray="5,5"/>
                    <line x1="220" y1="180" x2="120" y2="180" stroke="#8b5cf6" stroke-width="2" stroke-dasharray="5,5"/>
                    <circle cx="120" cy="180" r="5" fill="#fff"/>
                    <circle cx="380" cy="180" r="5" fill="#fff"/>
                    <circle cx="250" cy="100" r="5" fill="#fff"/>
                </svg>
            </div>
            
            <!-- Right Side -->
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
                    <!-- Username Input -->
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" class="form-control" 
                                   placeholder="Masukkan username Anda" required autocomplete="off">
                        </div>
                        <!-- Saran Login (Hint) -->
                        <div class="input-hint" id="hint-username">
                            <i class="fas fa-lightbulb"></i> Saran login: Coba username <strong>admin</strong> atau <strong>user</strong>
                        </div>
                    </div>
                    
                    <!-- Password Input -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Masukkan password Anda" required>
                        </div>
                        <!-- Saran Login (Hint) -->
                        <div class="input-hint" id="hint-password">
                            <i class="fas fa-lightbulb"></i> Saran password: Coba <strong>123456</strong>
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

    <!-- Script untuk Interaksi Saran Login -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const usernameHint = document.getElementById('hint-username');
            const passwordHint = document.getElementById('hint-password');

            // Fungsi untuk menampilkan hint dengan animasi
            function showHint(hintElement) {
                hintElement.style.display = 'block';
                setTimeout(() => {
                    hintElement.style.opacity = '1';
                }, 10);
            }

            // Fungsi untuk menyembunyikan hint
            function hideHint(hintElement) {
                hintElement.style.opacity = '0';
                setTimeout(() => {
                    hintElement.style.display = 'none';
                }, 300); // Tunggu transisi opacity selesai
            }

            // Event Listener untuk Username
            usernameInput.addEventListener('focus', function() {
                showHint(usernameHint);
            });

            usernameInput.addEventListener('blur', function() {
                // Sembunyikan jika user meninggalkan input
                hideHint(usernameHint);
            });

            // Event Listener untuk Password
            passwordInput.addEventListener('focus', function() {
                showHint(passwordHint);
            });

            passwordInput.addEventListener('blur', function() {
                hideHint(passwordHint);
            });
            
            // Fitur Tambahan: Klik pada saran teks (strong) akan mengisi otomatis
            document.querySelectorAll('.input-hint strong').forEach(item => {
                item.addEventListener('click', function(e) {
                    // Mencegah event bubbling agar input tidak keblur langsung jika diperlukan
                    e.stopPropagation(); 
                    const suggestion = this.innerText;
                    
                    // Cek elemen mana yang diklik (berdasarkan parent ID)
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