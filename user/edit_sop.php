<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

$user_id = getUserId();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data SOP yang perlu diperbaiki
$query = mysqli_query($conn, "SELECT * FROM sop WHERE id = $id AND created_by = $user_id");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    setFlashMessage('danger', 'SOP tidak ditemukan atau Anda tidak memiliki akses.');
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $desk  = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
    $lk    = mysqli_real_escape_string($conn, trim($_POST['langkah_kerja']));
    
    // Update status kembali ke 'Review' setelah diperbaiki
    $query_update = "UPDATE sop SET 
                    judul = '$judul', 
                    deskripsi = '$desk', 
                    langkah_kerja = '$lk', 
                    status = 'Review', 
                    updated_at = NOW() 
                    WHERE id = $id";

    if (mysqli_query($conn, $query_update)) {
        setFlashMessage('success', 'SOP berhasil diperbaiki dan dikirim ulang!');
        header('Location: dashboard.php');
        exit();
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbaiki SOP - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* CSS IDENTIK DARI DASHBOARD.PHP */
        :root {
            --bg: #020617; --sb: rgba(15, 23, 42, .97); --tb: rgba(15, 23, 42, .87); --cb: rgba(30, 41, 59, .75);
            --gb: rgba(255, 255, 255, .08); --tm: #f8fafc; --tmut: #94a3b8; --tsub: #cbd5e1; 
            --thbg: rgba(0, 0, 0, .35); --trodd: rgba(15, 23, 42, .55); --treven: rgba(15, 23, 42, .35); 
            --trhov: rgba(59, 130, 246, .09); --tbor: rgba(255, 255, 255, .06); --lf: brightness(0) invert(1); 
            --lbg: rgba(239, 68, 68, .18); --lc: #fca5a5; --lbor: rgba(239, 68, 68, .30); 
            --sl: #94a3b8; --sa: rgba(59, 130, 246, .12); --togbg: rgba(30, 41, 59, .80); --togc: #94a3b8;
        }
        [data-theme="light"] {
            --bg: #f0f4f8; --sb: rgba(255, 255, 255, .98); --tb: rgba(255, 255, 255, .96); --cb: rgba(255, 255, 255, .95);
            --gb: rgba(0, 0, 0, .09); --tm: #0f172a; --tmut: #64748b; --tsub: #334155; 
            --thbg: #e9eef5; --trodd: #ffffff; --treven: #f8fafc; --trhov: #eff6ff; 
            --tbor: rgba(0, 0, 0, .07); --lf: none; --lbg: rgba(239, 68, 68, .07); --lc: #dc2626; 
            --lbor: rgba(239, 68, 68, .18); --sl: #64748b; --sa: rgba(59, 130, 246, .08); 
            --togbg: rgba(241, 245, 249, .95); --togc: #475569;
        }

        body { font-family: 'Outfit', sans-serif !important; background-color: var(--bg) !important; color: var(--tm) !important; margin: 0; }
        .sidebar { background: var(--sb) !important; border-right: 1px solid var(--gb) !important; backdrop-filter: blur(12px); }
        .sidebar-header { border-bottom: 1px solid var(--gb) !important; padding: 20px; }
        .sidebar-header h3 { color: var(--tm) !important; margin: 4px 0 2px; font-size: 16px; font-weight: 700; }
        .sidebar-logo { filter: var(--lf); max-width: 80px; }
        .sidebar-menu { list-style: none; margin: 0; padding: 12px 0; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: var(--sl) !important; text-decoration: none; border-left: 3px solid transparent; font-size: 14px; font-weight: 500; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: var(--sa) !important; color: #3b82f6 !important; border-left-color: #3b82f6; }

        .main-content { background: transparent !important; }
        .topbar { background: var(--tb) !important; border-bottom: 1px solid var(--gb) !important; backdrop-filter: blur(12px); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; height: 64px; }
        
        .content-wrapper { padding: 24px; }
        .card { background: var(--cb) !important; border: 1px solid var(--gb) !important; border-radius: 16px !important; box-shadow: 0 4px 24px rgba(0, 0, 0, .10); margin-bottom: 24px; }
        .card-header { padding: 18px 22px; border-bottom: 1px solid var(--gb); }
        .card-header h3 { color: var(--tm) !important; margin: 0; font-size: 16px; font-weight: 600; }
        .card-body { padding: 22px; }

        /* FORM STYLING */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; color: var(--tsub); font-weight: 500; }
        .form-control { 
            width: 100%; 
            padding: 12px 16px; 
            background: rgba(0,0,0,0.2) !important; 
            border: 1px solid var(--gb) !important; 
            border-radius: 10px; 
            color: var(--tm) !important; 
            font-family: inherit;
        }
        .form-control:focus { outline: none; border-color: #3b82f6 !important; background: rgba(0,0,0,0.3) !important; }

        .btn-submit { background: linear-gradient(135deg, #10b981, #059669) !important; color: #fff !important; padding: 12px 25px; border-radius: 10px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); filter: brightness(1.1); }

        #theme-toggle-btn { all: unset; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; background: var(--togbg) !important; border: 1px solid var(--gb) !important; color: var(--togc) !important; display: flex !important; align-items: center; justify-content: center; font-size: 17px; transition: all .25s; }
        
        .catatan-revisi { background: var(--lbg); border: 1px solid var(--lbor); color: var(--lc); padding: 15px; border-radius: 12px; margin-bottom: 25px; }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2991/2991148.png'">
                <h3>SOP Digital</h3>
                <p>User Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="browse_sop.php"><i class="fas fa-search"></i><span>Cari SOP</span></a></li>
                <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Kategori</span></a></li>
                <li><a href="#" class="active"><i class="fas fa-edit"></i><span>Perbaiki SOP</span></a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <h2 style="font-size: 20px; font-weight: 700; margin: 0; color: var(--tm); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-edit" style="color:#3b82f6"></i> Perbaiki SOP
                    </h2>
                </div>
                <div class="topbar-right">
                    <button type="button" id="theme-toggle-btn"><i class="fas fa-moon" id="theme-icon"></i></button>
                    <div class="user-info" style="display: flex; align-items: center; gap: 10px; margin: 0 15px;">
                         <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                            <?php echo strtoupper(substr(getNamaLengkap(), 0, 1)); ?>
                         </div>
                         <div class="user-text">
                            <strong style="font-size: 14px; color: var(--tm);"><?php echo getNamaLengkap(); ?></strong>
                         </div>
                    </div>
                </div>
            </div>
            
            <div class="content-wrapper">
                <div class="card">
                    <div class="card-header">
                        <h3>Form Perbaikan Dokumen</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($data['catatan_admin']): ?>
                            <div class="catatan-revisi">
                                <strong style="display:block; margin-bottom:5px;"><i class="fas fa-exclamation-circle"></i> Catatan Admin:</strong>
                                <span>"<?php echo htmlspecialchars($data['catatan_admin']); ?>"</span>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="form-group">
                                <label>Judul SOP</label>
                                <input type="text" name="judul" class="form-control" value="<?php echo htmlspecialchars($data['judul']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="3"><?php echo htmlspecialchars($data['deskripsi']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Langkah-langkah Kerja</label>
                                <textarea name="langkah_kerja" class="form-control" rows="10" required><?php echo htmlspecialchars($data['langkah_kerja']); ?></textarea>
                            </div>

                            <div style="display:flex; gap:10px; margin-top:20px;">
                                <button type="submit" class="btn-submit">
                                    <i class="fas fa-paper-plane"></i> Kirim Perbaikan
                                </button>
                                <a href="dashboard.php" style="text-decoration:none; color:var(--tmut); padding:12px 20px; font-size:14px;">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // SCRIPT TEMA IDENTIK
        (function() {
            if (localStorage.getItem('theme') === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();

        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('theme-toggle-btn'),
                icon = document.getElementById('theme-icon');

            function sync() {
                if (icon) {
                    icon.className = document.documentElement.getAttribute('data-theme') === 'light' ? 'fas fa-sun' : 'fas fa-moon';
                }
            }
            sync();
            
            if (btn) {
                btn.addEventListener('click', function() {
                    var light = document.documentElement.getAttribute('data-theme') === 'light';
                    if (light) {
                        document.documentElement.removeAttribute('data-theme');
                        localStorage.setItem('theme', 'dark');
                    } else {
                        document.documentElement.setAttribute('data-theme', 'light');
                        localStorage.setItem('theme', 'light');
                    }
                    sync();
                });
            }
        });
    </script>
</body>
</html>