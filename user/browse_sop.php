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
        :root{--bg:#020617;--sb:rgba(15,23,42,.97);--tb:rgba(15,23,42,.87);--cb:rgba(30,41,59,.75);--gb:rgba(255,255,255,.08);--tm:#f8fafc;--tmut:#94a3b8;--tsub:#cbd5e1;--ibg:rgba(0,0,0,.30);--lf:brightness(0) invert(1);--lbg:rgba(239,68,68,.18);--lc:#fca5a5;--lbor:rgba(239,68,68,.30);--sl:#94a3b8;--sa:rgba(59,130,246,.12);--togbg:rgba(30,41,59,.80);--togc:#94a3b8;}
        [data-theme="light"]{--bg:#f0f4f8;--sb:rgba(255,255,255,.98);--tb:rgba(255,255,255,.96);--cb:rgba(255,255,255,.95);--gb:rgba(0,0,0,.09);--tm:#0f172a;--tmut:#64748b;--tsub:#334155;--ibg:rgba(255,255,255,.95);--lf:none;--lbg:rgba(239,68,68,.07);--lc:#dc2626;--lbor:rgba(239,68,68,.18);--sl:#64748b;--sa:rgba(59,130,246,.08);--togbg:rgba(241,245,249,.95);--togc:#475569;}
        *,*::before,*::after{box-sizing:border-box;}
        body{font-family:'Outfit',sans-serif!important;background-color:var(--bg)!important;color:var(--tm)!important;margin:0;overflow-x:hidden;transition:background-color .35s,color .35s;}
        body::before{content:'';position:fixed;inset:0;z-index:-1;background:radial-gradient(circle at 15% 50%,rgba(59,130,246,.07),transparent 30%);pointer-events:none;}
        
        .sidebar{background:var(--sb)!important;border-right:1px solid var(--gb)!important;backdrop-filter:blur(12px);}
        .sidebar-header{border-bottom:1px solid var(--gb)!important;padding:20px;}
        .sidebar-header h3{color:var(--tm)!important;margin:4px 0 2px;font-size:16px;font-weight:700;}
        .sidebar-header p{color:var(--tmut)!important;margin:0;font-size:12px;}
        .sidebar-logo{filter:var(--lf);max-width:80px;}
        .sidebar-menu{list-style:none;margin:0;padding:12px 0;}
        .sidebar-menu li a{display:flex;align-items:center;gap:10px;padding:12px 20px;color:var(--sl)!important;text-decoration:none;border-left:3px solid transparent;font-size:14px;font-weight:500;transition:.25s;}
        .sidebar-menu li a:hover,.sidebar-menu li a.active{background:var(--sa)!important;color:#3b82f6!important;border-left-color:#3b82f6;}
        
        .main-content{background:transparent!important;}
        .topbar{background:var(--tb)!important;border-bottom:1px solid var(--gb)!important;backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:space-between;padding:0 24px;height:64px;}
        .topbar-left h2{color:var(--tm)!important;font-size:20px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;}
        .topbar-right{display:flex;align-items:center;gap:12px;}
        
        #theme-toggle-btn{all:unset;cursor:pointer;width:40px;height:40px;border-radius:50%;background:var(--togbg)!important;border:1px solid var(--gb)!important;color:var(--togc)!important;display:flex!important;align-items:center;justify-content:center;font-size:17px;box-shadow:0 2px 8px rgba(0,0,0,.15);flex-shrink:0;transition:all .25s;}
        #theme-toggle-btn:hover{color:#3b82f6!important;transform:scale(1.1);}
        
        .user-info{display:flex;align-items:center;gap:10px;}
        .user-info strong{color:var(--tm)!important;font-size:14px;display:block;}
        .user-info p{color:var(--tmut)!important;margin:0;font-size:11px;}
        .user-avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#8b5cf6)!important;color:#fff!important;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;flex-shrink:0;}
        .btn-logout{padding:8px 18px;background:var(--lbg)!important;color:var(--lc)!important;border:1px solid var(--lbor)!important;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;white-space:nowrap;display:flex;align-items:center;gap:6px;}
        
        .content-wrapper{padding:24px;}
        .card{background:var(--cb)!important;border:1px solid var(--gb)!important;border-radius:16px!important;box-shadow:0 4px 24px rgba(0,0,0,.10);margin-bottom:24px;}
        .card-header{padding:18px 22px;border-bottom:1px solid var(--gb);}
        .card-header h3{color:var(--tm)!important;margin:0;font-size:16px;font-weight:600;}
        .card-body{padding:22px;}

        .form-control{width:100%;padding:11px 14px;background:var(--ibg)!important;border:1px solid var(--gb)!important;border-radius:8px;color:var(--tm)!important;font-family:'Outfit',sans-serif;font-size:14px;transition:.3s;}
        .form-control:focus{outline:none;border-color:#3b82f6!important;box-shadow:0 0 0 3px rgba(59,130,246,.15);}
        .form-group label{color:var(--tsub)!important;margin-bottom:8px;display:block;font-weight:600;font-size:13px;}

        .btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:9px!important;border:none!important;color:#fff!important;font-weight:600;font-size:13px;cursor:pointer;text-decoration:none;transition:.25s;}
        .btn:hover{filter:brightness(1.1);transform:translateY(-2px);}
        .btn-success{background:linear-gradient(135deg,#10b981,#059669)!important;box-shadow:0 4px 12px rgba(16,185,129,.3);}
        .btn-danger{background:linear-gradient(135deg,#ef4444,#dc2626)!important;box-shadow:0 4px 12px rgba(239,68,68,.3);}
        .btn-info{background:linear-gradient(135deg,#3b82f6,#2563eb)!important;box-shadow:0 4px 12px rgba(59,130,246,.3);}
        .badge{padding:5px 12px;border-radius:20px;font-size:12px;background:var(--sa);color:#3b82f6;border:1px solid rgba(59,130,246,.3);}

        .sop-card{background:var(--cb);border:1px solid var(--gb);border-radius:12px;padding:20px;transition:all .3s ease;position:relative;overflow:hidden;}
        .sop-card:hover{border-color:#3b82f6;transform:translateY(-5px);box-shadow:0 10px 20px rgba(0,0,0,.1);}
        .sop-card h4{color:var(--tm);margin-bottom:10px;font-size:16px;font-weight:600;}
        .sop-card p{color:var(--tsub);font-size:13px;line-height:1.6;margin-bottom:15px;}
        .sop-meta{display:flex;justify-content:space-between;align-items:center;padding-top:15px;border-top:1px solid var(--gb);}
        .sop-meta small{color:var(--tmut);}
        .empty-state{text-align:center;padding:60px 20px;color:var(--tmut);}
        .empty-state h3{color:var(--tm);margin-top:10px;}
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2991/2991148.png'">
                <h3>SOP Digital</h3><p>User Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="browse_sop.php" class="active"><i class="fas fa-search"></i><span>Cari SOP</span></a></li>
                <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Kategori</span></a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left"><h2><i class="fas fa-search" style="color:#3b82f6"></i> Cari SOP</h2></div>
                <div class="topbar-right">
                    <button type="button" id="theme-toggle-btn" title="Ganti Tema"><i class="fas fa-moon" id="theme-icon"></i></button>
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getNamaLengkap(), 0, 1)); ?></div>
                        <div><strong><?php echo getNamaLengkap(); ?></strong><p>User</p></div>
                    </div>
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
    <script>
    (function(){if(localStorage.getItem('theme')==='light')document.documentElement.setAttribute('data-theme','light');})();
    document.addEventListener('DOMContentLoaded',function(){
      var btn=document.getElementById('theme-toggle-btn'),icon=document.getElementById('theme-icon');
      function sync(){icon.className=document.documentElement.getAttribute('data-theme')==='light'?'fas fa-sun':'fas fa-moon';}
      sync();
      if(btn){
        btn.addEventListener('click',function(){
          var light=document.documentElement.getAttribute('data-theme')==='light';
          if(light){document.documentElement.removeAttribute('data-theme');localStorage.setItem('theme','dark');}
          else{document.documentElement.setAttribute('data-theme','light');localStorage.setItem('theme','light');}
          sync();
        });
      }
    });
    </script>
</body>
</html>