<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireLogin();

 $sql = "SELECT c.*, COUNT(s.id) as jumlah_sop FROM categories c 
        LEFT JOIN sop s ON c.id = s.kategori_id 
        GROUP BY c.id ORDER BY c.nama_kategori ASC";
 $result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori SOP - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        .card { background: var(--glass-bg) !important; border: 1px solid rgba(255,255,255,0.08) !important; border-radius: 16px !important; }
        .card-header h3 { color: #fff !important; }

        /* Custom Category Card for User */
        .cat-card-lg {
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.6), rgba(15, 23, 42, 0.8));
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px; padding: 30px; color: white; position: relative; overflow: hidden;
            transition: all 0.3s ease; backdrop-filter: blur(5px);
        }
        .cat-card-lg:hover { transform: translateY(-10px) scale(1.02); box-shadow: 0 20px 40px rgba(0,0,0,0.4), 0 0 20px rgba(59, 130, 246, 0.2); border-color: rgba(59, 130, 246, 0.5); }
        .cat-icon-circle { width: 60px; height: 60px; background: rgba(255,255,255,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.1); }
        .cat-btn-cta { background: white; color: #1e3a8a; padding: 8px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 5px; }
        .cat-btn-cta:hover { background: #fbbf24; color: white; box-shadow: 0 0 15px rgba(251, 191, 36, 0.5); }
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
                <li><a href="kategori.php" class="active"><i class="fas fa-folder"></i><span>Kategori</span></a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left"><h2><i class="fas fa-folder"></i> Kategori SOP</h2></div>
                <div class="topbar-right">
                    <div class="user-info"><div class="user-avatar"><?php echo strtoupper(substr(getNamaLengkap(), 0, 1)); ?></div><div><strong style="color:white"><?php echo getNamaLengkap(); ?></strong><p style="margin:0;font-size:12px;color:#94a3b8">User</p></div></div>
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <div class="content-wrapper">
                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-book-open"></i> Semua Kategori</h3></div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <?php while ($cat = mysqli_fetch_assoc($result)): ?>
                            <div class="cat-card-lg">
                                <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
                                <div style="position: relative; z-index: 1;">
                                    <div class="cat-icon-circle"><i class="fas fa-folder-open" style="font-size: 28px; color: #60a5fa;"></i></div>
                                    <h3 style="color: white; margin-bottom: 10px; font-size: 22px;"><?php echo htmlspecialchars($cat['nama_kategori']); ?></h3>
                                    <p style="opacity: 0.9; margin-bottom: 20px; line-height: 1.6; color: #cbd5e1;"><?php echo htmlspecialchars($cat['deskripsi']); ?></p>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-size: 14px; opacity: 0.9; color: #94a3b8;"><i class="fas fa-file-alt"></i> <?php echo $cat['jumlah_sop']; ?> SOP</span>
                                        <a href="browse_sop.php?kategori=<?php echo $cat['id']; ?>" class="cat-btn-cta">Lihat SOP <i class="fas fa-arrow-right"></i></a>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/script.js"></script>
</body>
</html>