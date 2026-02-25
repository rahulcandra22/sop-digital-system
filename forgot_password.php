<?php
session_start();
require_once 'config/database.php';

$message = '';
$msg_type = '';

$base_url = "http://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER["REQUEST_URI"] . '?');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $sql = "SELECT id, nama_lengkap FROM users WHERE username = '$email'";    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $token = bin2hex(random_bytes(50));
        
        date_default_timezone_set('Asia/Jakarta');
        $expire_time = date("Y-m-d H:i:s", strtotime('+1 hours'));
        
        $update_sql = "UPDATE users SET reset_token = '$token', token_expire = '$expire_time' WHERE username = '$email'";
        mysqli_query($conn, $update_sql);
        
        $reset_link = $base_url . "/reset_password.php?token=" . $token;
        
        $to = $email;
        $subject = "Permintaan Reset Password - SOP Digital";
        
        $email_body = "Halo " . $user['nama_lengkap'] . ",\n\n";
        $email_body .= "Kami menerima permintaan untuk mereset password akun SOP Digital Anda.\n";
        $email_body .= "Silakan klik tautan di bawah ini untuk membuat password baru:\n\n";
        $email_body .= $reset_link . "\n\n";
        $email_body .= "Tautan ini hanya berlaku selama 1 jam.\n";
        $email_body .= "Jika Anda tidak meminta reset password, abaikan email ini.\n\n";
        $email_body .= "Terima kasih,\nTim IT Sinergi Nusantara Integrasi";
        
        $headers = "From: no-reply@sinergi.co.id\r\n";
        $headers .= "Reply-To: it@sinergi.co.id\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        if (@mail($to, $subject, $email_body, $headers)) {
            $msg_type = 'success';
            $message = 'Tautan reset password telah dikirim ke email Anda. Silakan cek Inbox atau folder Spam.';
        } else {
            $msg_type = 'danger';
            $message = 'Gagal mengirim email. Sistem sedang berjalan di Localhost.';
            $message .= '<br><br><b>[MODE TESTING LOCALHOST]</b> Link Anda: <a href="'.$reset_link.'">Klik Disini</a>';
        }
    } else {
        $msg_type = 'danger';
        $message = 'Jika email tersebut terdaftar, tautan reset telah dikirimkan.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - SOP Digital System</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-glow: #3b82f6; --secondary-glow: #8b5cf6; --accent-orange: #f97316;
            --bg-main: #020617; --bg-dark: #0f172a; --glass-bg: rgba(15, 23, 42, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1); --text-main: #f8fafc;
            --text-muted: #94a3b8; --input-bg: rgba(0, 0, 0, 0.4);
            --grid-color: rgba(255, 255, 255, 0.03);
        }
        [data-theme="light"] {
            --bg-main: #f1f5f9; --bg-dark: #e2e8f0; --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(0, 0, 0, 0.1); --text-main: #0f172a;
            --text-muted: #64748b; --input-bg: rgba(255, 255, 255, 0.9);
            --grid-color: rgba(0, 0, 0, 0.04);
        }
        body {
            font-family: 'Outfit', sans-serif !important; background-color: var(--bg-main) !important;
            color: var(--text-main); overflow-x: hidden; min-height: 100vh;
            display: flex; justify-content: center; align-items: center; margin: 0;
            transition: background-color 0.5s ease, color 0.5s ease;
        }
        .ambient-light { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; 
            background: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.15), transparent 25%),
                        radial-gradient(circle at 85% 30%, rgba(249, 115, 22, 0.08), transparent 25%);
        }
        .orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.6; animation: moveOrb 20s infinite alternate; }
        .orb-1 { width: 400px; height: 400px; background: var(--primary-glow); top: -100px; left: -100px; }
        .orb-2 { width: 500px; height: 500px; background: var(--accent-orange); bottom: -150px; right: -150px; animation-delay: -5s; }
        @keyframes moveOrb { 0% { transform: translate(0, 0); } 100% { transform: translate(50px, 30px); } }
        
        .grid-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(var(--grid-color) 1px, transparent 1px), linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 40px 40px; z-index: -1;
            mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
            -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        }
        .login-container { width: 100%; padding: 20px; display: flex; justify-content: center; align-items: center; z-index: 10; }
        .login-box {
            background: var(--glass-bg) !important; backdrop-filter: blur(25px) !important;
            -webkit-backdrop-filter: blur(25px) !important; border: 1px solid var(--glass-border) !important;
            border-radius: 24px !important; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            width: 100% !important; overflow: hidden;
            animation: cardEntrance 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        @keyframes cardEntrance { from { opacity: 0; transform: scale(0.95) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .login-right { padding: 40px 35px !important; display: flex; flex-direction: column; justify-content: center; }
        .form-header h2 { color: var(--text-main) !important; font-size: 24px !important; margin-bottom: 8px !important; }
        .form-header p { color: var(--text-muted) !important; font-size: 14px !important; margin: 0 !important; }
        .form-group { margin-bottom: 22px !important; position: relative; }
        .form-group label { color: var(--text-main) !important; font-size: 12px !important; font-weight: 600 !important; text-transform: uppercase; margin-bottom: 8px !important; display: block; margin-left: 5px; }
        .input-group { position: relative; width: 100%; }
        .input-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 2; }
        .form-control {
            width: 100%; padding: 14px 14px 14px 45px !important; background: var(--input-bg) !important;
            border: 1px solid var(--glass-border) !important; border-radius: 12px !important; color: var(--text-main) !important;
            font-size: 14px !important; box-sizing: border-box; font-family: 'Outfit', sans-serif !important;
        }
        .form-control:focus { outline: none !important; border-color: var(--primary-glow) !important; background: var(--glass-bg) !important; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important; }
        .form-control:focus + i { color: var(--primary-glow); transform: translateY(-50%) scale(1.1); }
        .btn-primary {
            width: 100% !important; padding: 14px !important; background: linear-gradient(90deg, #3b82f6, #8b5cf6) !important;
            color: white !important; border: none !important; border-radius: 12px !important; font-size: 15px !important; font-weight: 600 !important; cursor: pointer !important;
        }
        .btn-primary:hover { transform: translateY(-2px) !important; box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4) !important; }
        .alert { border-radius: 10px; padding: 12px 15px; margin-bottom: 20px !important; font-size: 13px; display: flex; align-items: center; gap: 10px; }
        .alert-danger { background: rgba(239, 68, 68, 0.1) !important; color: #ef4444 !important; border: 1px solid rgba(239, 68, 68, 0.2) !important; }
        .alert-success { background: rgba(16, 185, 129, 0.1) !important; color: #10b981 !important; border: 1px solid rgba(16, 185, 129, 0.2) !important; }
    </style>
</head>
<body>

    <div class="grid-overlay"></div>
    <div class="ambient-light">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="login-container">
        <div class="login-box" style="grid-template-columns: 1fr !important; max-width: 450px !important;">
            
            <div class="login-right">
                <div class="form-header" style="text-align: center;">
                    <i class="fas fa-unlock-alt" style="font-size: 40px; color: var(--primary-glow); margin-bottom: 15px;"></i>
                    <h2>Lupa Password?</h2>
                    <p style="margin-top: 10px !important; line-height: 1.5;">Masukkan email akun Anda. Kami akan mengirimkan instruksi untuk mengatur ulang password.</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $msg_type; ?>">
                        <i class="fas <?php echo $msg_type == 'success' ? 'fa-check-circle' : 'fa-info-circle'; ?>"></i>
                        <div><?php echo $message; ?></div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Alamat Email Terdaftar</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="contoh: karyawan@sinergi.co.id" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="margin-top: 10px;">
                        <i class="fas fa-paper-plane"></i> Kirim Link Reset
                    </button>
                    
                    <div style="text-align: center; margin-top: 25px;">
                        <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 14px; transition: 0.3s; font-weight: 500;">
                            <i class="fas fa-arrow-left"></i> Kembali ke Login
                        </a>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        });
    </script>
</body>
</html>