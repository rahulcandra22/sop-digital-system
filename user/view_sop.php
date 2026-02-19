<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireLogin();

if (!isset($_GET['id'])) {
    header('Location: browse_sop.php');
    exit();
}

 $id = intval($_GET['id']);
 $sql = "SELECT s.*, c.nama_kategori, u.nama_lengkap as creator FROM sop s 
        LEFT JOIN categories c ON s.kategori_id = c.id 
        LEFT JOIN users u ON s.created_by = u.id 
        WHERE s.id = $id";
 $result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: browse_sop.php');
    exit();
}

 $sop = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($sop['judul']); ?> - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- DARK THEME CSS --- */
        :root { --primary-glow: #3b82f6; --glass-bg: rgba(30, 41, 59, 0.7); --text-main: #f8fafc; }
        body { font-family: 'Outfit', sans-serif !important; background-color: #020617 !important; color: var(--text-main); overflow-x: hidden; }
        body::before { content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.08), transparent 25%); }
        
        .sidebar { background: rgba(15, 23, 42, 0.95) !important; border-right: 1px solid rgba(255,255,255,0.08) !important; backdrop-filter: blur(10px); }
        .sidebar-menu a { color: #94a3b8 !important; transition: 0.3s; border-left: 3px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(59, 130, 246, 0.1) !important; color: #fff !important; border-left-color: var(--primary-glow); }
        .sidebar-logo { filter: brightness(0) invert(1); }
        .main-content { background: transparent !important; }
        .topbar { background: rgba(15, 23, 42, 0.8) !important; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.08) !important; }
        .topbar-left h2 { color: #fff !important; }
        .user-avatar { background: linear-gradient(135deg, #3b82f6, #8b5cf6) !important; color: white; }
        .btn-logout { padding: 8px 20px; background: rgba(239,68,68,0.2); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; text-decoration: none; }

        /* Card & Buttons */
        .card { background: var(--glass-bg) !important; border: 1px solid rgba(255,255,255,0.08) !important; border-radius: 16px !important; }
        .btn { border-radius: 8px !important; border: none !important; color: white; transition: 0.3s; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669) !important; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
        .btn-info { background: linear-gradient(135deg, #3b82f6, #2563eb) !important; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706) !important; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3); }

        /* Custom View Sections */
        .meta-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; 
            padding: 20px; background: rgba(0,0,0,0.3); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); 
        }
        .meta-label { color: #94a3b8; font-size: 13px; margin: 0; }
        .meta-value { color: #fff; font-weight: 600; margin: 5px 0 0 0; text-shadow: 0 0 10px rgba(255,255,255,0.1); }

        .section-box { 
            padding: 25px; border-radius: 12px; margin-bottom: 30px; 
            backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.05);
        }
        .section-blue { background: rgba(59, 130, 246, 0.1); border-left: 5px solid #3b82f6; box-shadow: 0 0 20px rgba(59, 130, 246, 0.1); }
        .section-green { background: rgba(16, 185, 129, 0.1); border-left: 5px solid #10b981; box-shadow: 0 0 20px rgba(16, 185, 129, 0.1); }
        .section-violet { background: rgba(139, 92, 246, 0.1); border-left: 5px solid #8b5cf6; box-shadow: 0 0 20px rgba(139, 92, 246, 0.1); }

        .section-title { color: #fff; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 1.2rem; }
        .section-box p { color: #cbd5e1; line-height: 1.8; margin: 0; }
        .section-box pre { font-family: 'Outfit', monospace; white-space: pre-wrap; margin: 0; line-height: 2; font-size: 15px; color: #d1fae5; }
        
        .card-header-glow {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6); 
            color: white; padding: 25px; border-radius: 16px 16px 0 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Print Override */
@media print {

    body {
        background: white !important;
    }

    .sidebar,
    .topbar,
    .btn-logout {
        display: none !important;
        min-height: auto !important;
    }

    .dashboard-wrapper {
        display: block !important;
    }

    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .content-wrapper {
        padding: 0 !important;
    }

    .card {
        box-shadow: none !important;
        border: 1px solid #000 !important;
        background: white !important;
        color: black !important;
    }

    .card-header-glow {
        background: #f2f2f2 !important;
        color: black !important;
    }

    .section-box {
        background: #fff !important;
        border: 1px solid #000 !important;
        box-shadow: none !important;
        backdrop-filter: none !important;
        page-break-inside: auto !important;
    }

    .section-green,
    .section-blue,
    .section-violet {
        background: #fff !important;
        border-left: none !important;
        box-shadow: none !important;
    }

    .section-box pre {
        display: block !important;
        white-space: pre-wrap !important;
        color: #000 !important;
    }

}
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo">
                <h3 style="color: white;">SOP Digital</h3>
                <p style="color: #94a3b8;">User</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="browse_sop.php"><i class="fas fa-search"></i><span>Cari SOP</span></a></li>
                <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Kategori</span></a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <h2><i class="fas fa-file-alt"></i> Detail SOP</h2>
                </div>
                <div class="topbar-right">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getNamaLengkap(), 0, 1)); ?></div>
                        <div>
                            <strong><?php echo getNamaLengkap(); ?></strong>
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">User</p>
                        </div>
                    </div>
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <div class="content-wrapper">
                <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
<a href="print_sop.php?id=<?php echo $sop['id']; ?>" class="btn btn-success">
    <i class="fas fa-print"></i> Cetak
</a>
                </div>
                
                <div class="card">
                    <!-- Header with Glow -->
                    <div class="card-header-glow">
                        <h2 style="color: white; margin: 0 0 10px 0; text-shadow: 0 0 10px rgba(0,0,0,0.3);"><?php echo htmlspecialchars($sop['judul']); ?></h2>
                        <span style="display: inline-block; padding: 5px 15px; background: rgba(255,255,255,0.2); border-radius: 20px; font-size: 0.9rem; backdrop-filter: blur(5px);">
                            <?php echo htmlspecialchars($sop['nama_kategori']); ?>
                        </span>
                    </div>
                    
                    <div class="card-body" style="padding: 30px;">
                        <!-- Meta Info Grid -->
                        <div class="meta-grid">
                            <div>
                                <p class="meta-label">Dibuat oleh</p>
                                <p class="meta-value"><?php echo htmlspecialchars($sop['creator']); ?></p>
                            </div>
                            <div>
                                <p class="meta-label">Tanggal Dibuat</p>
                                <p class="meta-value"><?php echo date('d F Y, H:i', strtotime($sop['created_at'])); ?> WIB</p>
                            </div>
                            <div>
                                <p class="meta-label">Terakhir Diupdate</p>
                                <p class="meta-value"><?php echo date('d F Y, H:i', strtotime($sop['updated_at'])); ?> WIB</p>
                            </div>
                        </div>
                        
                        <!-- Description Section (Blue Glow) -->
                        <?php if ($sop['deskripsi']): ?>
                        <div class="section-box section-blue">
                            <h3 class="section-title"><i class="fas fa-info-circle" style="color: #60a5fa;"></i> Deskripsi</h3>
                            <p><?php echo nl2br(htmlspecialchars($sop['deskripsi'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Steps Section (Green Glow) -->
                        <div class="section-box section-green">
                            <h3 class="section-title"><i class="fas fa-tasks" style="color: #34d399;"></i> Langkah-langkah Kerja</h3>
                            <pre><?php echo htmlspecialchars($sop['langkah_kerja']); ?></pre>
                        </div>
                        
                        <!-- Attachment Section (Yellow Glow) -->
                        <?php if ($sop['file_attachment']): ?>
                        <div class="section-box section-violet">
                            <h3 class="section-title"><i class="fas fa-paperclip" style="color: #a78bfa;"></i> File Lampiran</h3>
                            <p style="margin-bottom: 15px; color: #fcd34d;">
                                <strong>File:</strong> <?php echo htmlspecialchars($sop['file_attachment']); ?>
                            </p>
                            <a href="../assets/uploads/<?php echo $sop['file_attachment']; ?>" target="_blank" class="btn btn-warning">
                                <i class="fas fa-download"></i> Download File
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/script.js"></script>
</body>
</html>