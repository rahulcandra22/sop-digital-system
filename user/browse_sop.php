<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireUser();

 $kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
 $search = isset($_GET['search']) ? $_GET['search'] : '';

 $where = "WHERE s.status = 'Disetujui'";

if ($kategori_filter) { 
    $where .= " AND s.kategori_id = " . intval($kategori_filter); 
}

if ($search) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $where .= " AND (
        s.judul LIKE '%$search_safe%' 
        OR s.deskripsi LIKE '%$search_safe%'
        OR c.nama_kategori LIKE '%$search_safe%'
    )";
}

 $sql = "SELECT s.*, c.nama_kategori FROM sop s LEFT JOIN categories c ON s.kategori_id = c.id $where ORDER BY s.created_at DESC";
 $result = mysqli_query($conn, $sql);

 $sql_cat = "SELECT * FROM categories ORDER BY nama_kategori ASC";
 $result_cat = mysqli_query($conn, $sql_cat);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari SOP - SOP Digital</title>
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
        
        /* Form */
        .form-control { width: 100%; padding: 12px; background: rgba(0,0,0,0.3) !important; border: 1px solid rgba(255,255,255,0.1) !important; border-radius: 8px !important; color: #fff !important; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 10px rgba(59,130,246,0.3); }
        .form-group label { color: #cbd5e1; margin-bottom: 8px; display: block; font-weight: 500; }
        
        /* Buttons */
        .btn { border-radius: 8px !important; border: none !important; color: white; transition: 0.3s; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669) !important; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626) !important; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); }
        .btn-info { background: linear-gradient(135deg, #3b82f6, #2563eb) !important; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }

        /* SOP Grid Card */
        .sop-card {
            background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 20px;
            transition: all 0.3s ease; position: relative; overflow: hidden;
        }
        .sop-card:hover { border-color: rgba(59, 130, 246, 0.5); transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); background: rgba(30, 41, 59, 0.8); }
        .sop-card h4 { color: #fff; margin-bottom: 10px; font-size: 1.1rem; }
        .sop-card p { color: #94a3b8; font-size: 0.9rem; line-height: 1.6; margin-bottom: 15px; }
        .sop-meta { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sop-meta small { color: #64748b; }
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
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
                <li><a href="browse_sop.php" class="active"><i class="fas fa-search"></i><span>Cari SOP</span></a></li>
                <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Kategori</span></a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left"><h2><i class="fas fa-search"></i> Cari SOP</h2></div>
                <div class="topbar-right">
                    <div class="user-info"><div class="user-avatar"><?php echo strtoupper(substr(getNamaLengkap(), 0, 1)); ?></div><div><strong style="color:white"><?php echo getNamaLengkap(); ?></strong><p style="margin:0;font-size:12px;color:#94a3b8">User</p></div></div>
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <div class="content-wrapper">
                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-filter"></i> Filter Pencarian</h3></div>
                    <div class="card-body">
                        <form method="GET" action="" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                            <div class="form-group" style="margin: 0;">
                                <label>Cari Judul</label>
                                <input type="text" name="search" class="form-control" placeholder="Cari SOP..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label>Kategori</label>
                                <select name="kategori" class="form-control">
                                    <option value="">Semua Kategori</option>
                                    <?php while ($cat = mysqli_fetch_assoc($result_cat)): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($kategori_filter == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['nama_kategori']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-success"><i class="fas fa-search"></i> Cari</button>
                                <a href="browse_sop.php" class="btn btn-danger"><i class="fas fa-redo"></i> Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-list"></i> Hasil Pencarian</h3></div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <div class="sop-card">
                                <div style="margin-bottom: 10px;">
                                    <span class="badge"><?php echo htmlspecialchars($row['nama_kategori']); ?></span>
                                </div>
                                <h4><?php echo htmlspecialchars($row['judul']); ?></h4>
                                <p><?php echo substr(htmlspecialchars($row['deskripsi']), 0, 100) . '...'; ?></p>
                                <div class="sop-meta">
                                    <small><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($row['created_at'])); ?></small>
                                    <a href="view_sop.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Lihat Detail</a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php if (mysqli_num_rows($result) == 0): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox" style="font-size: 64px; margin-bottom: 20px; opacity: 0.3;"></i>
                                <h3>Tidak ada SOP ditemukan</h3>
                                <p>Coba ubah kata kunci atau filter pencarian Anda</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/script.js"></script>
</body>