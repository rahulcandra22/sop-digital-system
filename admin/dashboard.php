<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

$total_sop      = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM sop"))['t'];
$total_kategori = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM categories"))['t'];
$total_user     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM users WHERE role='user'"))['t'];
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard Admin - SOP Digital</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* === VARIABLES === */
:root{--bg:#020617;--sb:rgba(15,23,42,.97);--tb:rgba(15,23,42,.87);--cb:rgba(30,41,59,.75);--stb:rgba(30,41,59,.60);--gb:rgba(255,255,255,.08);--tm:#f8fafc;--tmut:#94a3b8;--tsub:#cbd5e1;--thbg:rgba(0,0,0,.35);--trodd:rgba(15,23,42,.55);--treven:rgba(15,23,42,.35);--trhov:rgba(59,130,246,.09);--tbor:rgba(255,255,255,.06);--ibg:rgba(0,0,0,.30);--mbg:#1e293b;--mbor:rgba(255,255,255,.10);--lf:brightness(0) invert(1);--lbg:rgba(239,68,68,.18);--lc:#fca5a5;--lbor:rgba(239,68,68,.30);--sl:#94a3b8;--sa:rgba(59,130,246,.12);--togbg:rgba(30,41,59,.80);--togc:#94a3b8;--sbg:rgba(0,0,0,.30);--sbor:rgba(255,255,255,.10);}
[data-theme="light"]{--bg:#f0f4f8;--sb:rgba(255,255,255,.98);--tb:rgba(255,255,255,.96);--cb:rgba(255,255,255,.95);--stb:rgba(255,255,255,.90);--gb:rgba(0,0,0,.09);--tm:#0f172a;--tmut:#64748b;--tsub:#334155;--thbg:#e9eef5;--trodd:#ffffff;--treven:#f8fafc;--trhov:#eff6ff;--tbor:rgba(0,0,0,.07);--ibg:rgba(255,255,255,.95);--mbg:#ffffff;--mbor:rgba(0,0,0,.10);--lf:none;--lbg:rgba(239,68,68,.07);--lc:#dc2626;--lbor:rgba(239,68,68,.18);--sl:#64748b;--sa:rgba(59,130,246,.08);--togbg:rgba(241,245,249,.95);--togc:#475569;--sbg:rgba(255,255,255,.95);--sbor:rgba(0,0,0,.10);}

/* === BASE === */
*,*::before,*::after{box-sizing:border-box;}
body{font-family:'Outfit',sans-serif!important;background-color:var(--bg)!important;color:var(--tm)!important;margin:0;overflow-x:hidden;transition:background-color .35s,color .35s;}
body::before{content:'';position:fixed;inset:0;z-index:-1;background:radial-gradient(circle at 15% 50%,rgba(59,130,246,.07),transparent 30%),radial-gradient(circle at 85% 20%,rgba(139,92,246,.06),transparent 30%);pointer-events:none;}

/* === SIDEBAR === */
.sidebar{background:var(--sb)!important;border-right:1px solid var(--gb)!important;backdrop-filter:blur(12px);}
.sidebar-header{border-bottom:1px solid var(--gb)!important;padding:20px;}
.sidebar-header h3{color:var(--tm)!important;margin:4px 0 2px;font-size:16px;font-weight:700;}
.sidebar-header p{color:var(--tmut)!important;margin:0;font-size:12px;}
.sidebar-header strong{color:var(--tm)!important;font-size:13px;}
.sidebar-logo{filter:var(--lf);max-width:80px;}
.sidebar-menu{list-style:none;margin:0;padding:12px 0;}
.sidebar-menu li a{display:flex;align-items:center;gap:10px;padding:12px 20px;color:var(--sl)!important;text-decoration:none;border-left:3px solid transparent;font-size:14px;font-weight:500;transition:.25s;}
.sidebar-menu li a:hover,.sidebar-menu li a.active{background:var(--sa)!important;color:#3b82f6!important;border-left-color:#3b82f6;}

/* === TOPBAR === */
.main-content{background:transparent!important;}
.topbar{background:var(--tb)!important;border-bottom:1px solid var(--gb)!important;backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:space-between;padding:0 24px;height:64px;}
.topbar-left h2{color:var(--tm)!important;font-size:20px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;}
.topbar-right{display:flex;align-items:center;gap:12px;}

/* === THEME TOGGLE (unique id to avoid style.css conflict) === */
#theme-toggle-btn{all:unset;cursor:pointer;width:40px;height:40px;border-radius:50%;background:var(--togbg)!important;border:1px solid var(--gb)!important;color:var(--togc)!important;display:flex!important;align-items:center;justify-content:center;font-size:17px;box-shadow:0 2px 8px rgba(0,0,0,.15);flex-shrink:0;transition:all .25s;}
#theme-toggle-btn:hover{color:#3b82f6!important;transform:scale(1.1);}
#theme-toggle-btn i{pointer-events:none;color:inherit!important;font-size:17px;}

/* === USER / LOGOUT === */
.user-info{display:flex;align-items:center;gap:10px;}
.user-info strong{color:var(--tm)!important;font-size:14px;display:block;}
.user-info p{color:var(--tmut)!important;margin:0;font-size:11px;}
.user-avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#8b5cf6)!important;color:#fff!important;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;flex-shrink:0;}
.btn-logout{padding:8px 18px;background:var(--lbg)!important;color:var(--lc)!important;border:1px solid var(--lbor)!important;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;white-space:nowrap;display:flex;align-items:center;gap:6px;}

/* === CONTENT === */
.content-wrapper{padding:24px;}

/* === CARDS === */
.card{background:var(--cb)!important;border:1px solid var(--gb)!important;border-radius:16px!important;box-shadow:0 4px 24px rgba(0,0,0,.10);margin-bottom:24px;overflow:hidden;}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--gb);}
.card-header h3{color:var(--tm)!important;margin:0;font-size:16px;font-weight:600;display:flex;align-items:center;gap:8px;}
.card-body{padding:22px;}

/* === STATS === */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin-bottom:24px;}
.stat-card{background:var(--stb)!important;border:1px solid var(--gb)!important;border-radius:16px!important;padding:20px;display:flex;align-items:center;gap:16px;transition:.3s;}
.stat-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(59,130,246,.12);}
.stat-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.stat-icon.blue{background:rgba(59,130,246,.20);color:#60a5fa;}
.stat-icon.green{background:rgba(16,185,129,.20);color:#34d399;}
.stat-icon.orange{background:rgba(249,115,22,.20);color:#fb923c;}
.stat-icon.purple{background:rgba(139,92,246,.20);color:#a78bfa;}
.stat-info h3{color:var(--tm)!important;font-size:28px;font-weight:700;margin:0 0 2px;}
.stat-info p{color:var(--tmut)!important;margin:0;font-size:13px;}

/* === TABLE — KEY FIX === */
.table-responsive{overflow-x:auto;border-radius:10px;overflow:hidden;}
table{width:100%!important;border-collapse:collapse!important;}
thead tr{background:var(--thbg)!important;}
thead th{background:var(--thbg)!important;color:var(--tmut)!important;padding:13px 16px!important;font-size:.75rem!important;font-weight:600!important;text-transform:uppercase!important;letter-spacing:.6px!important;border:none!important;}
tbody tr:nth-child(odd) td{background:var(--trodd)!important;}
tbody tr:nth-child(even) td{background:var(--treven)!important;}
tbody tr:hover td{background:var(--trhov)!important;}
tbody td{color:var(--tsub)!important;padding:14px 16px!important;border-bottom:1px solid var(--tbor)!important;border-top:none!important;border-left:none!important;border-right:none!important;vertical-align:middle;}
tbody tr:last-child td{border-bottom:none!important;}

/* === BADGE / STATUS === */
.badge-cat{display:inline-block;padding:4px 12px;border-radius:20px;font-size:.75rem;background:rgba(59,130,246,.18);color:#60a5fa;border:1px solid rgba(59,130,246,.30);}
[data-theme="light"] .badge-cat{background:rgba(59,130,246,.10);color:#2563eb;border-color:rgba(59,130,246,.20);}
.s-badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:500;}

/* === BUTTONS === */
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:9px!important;border:none!important;color:#fff!important;font-weight:600;font-size:13px;cursor:pointer;text-decoration:none;transition:.25s;}
.btn:hover{filter:brightness(1.1);transform:translateY(-2px);}
.btn-success{background:linear-gradient(135deg,#10b981,#059669)!important;box-shadow:0 4px 12px rgba(16,185,129,.3);}
.btn-info{background:linear-gradient(135deg,#3b82f6,#2563eb)!important;box-shadow:0 4px 12px rgba(59,130,246,.3);}
.btn-warning{background:linear-gradient(135deg,#f59e0b,#d97706)!important;}
.btn-danger{background:linear-gradient(135deg,#ef4444,#dc2626)!important;box-shadow:0 4px 12px rgba(239,68,68,.3);}
.btn-sm{padding:6px 12px!important;font-size:12px!important;}

/* === ALERTS === */
.alert{border-radius:10px!important;padding:12px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;}
.alert-success{background:rgba(16,185,129,.12)!important;color:#059669!important;border:1px solid rgba(16,185,129,.25)!important;}
.alert-danger{background:rgba(239,68,68,.12)!important;color:#dc2626!important;border:1px solid rgba(239,68,68,.25)!important;}

/* === QUICK ACTIONS === */
.grid-buttons{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;}
.grid-buttons a{display:flex;align-items:center;justify-content:center;gap:10px;padding:15px;text-decoration:none;border-radius:12px;color:#fff;font-weight:600;font-size:14px;transition:.25s;box-shadow:0 4px 14px rgba(0,0,0,.15);}
.grid-buttons a:hover{transform:translateY(-3px);filter:brightness(1.08);}
</style>
</head>
<body>
<div class="dashboard-wrapper">
<aside class="sidebar">
  <div class="sidebar-header">
    <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2991/2991148.png'">
    <h3>SOP Digital</h3><p>Admin Panel</p>
    <strong><?php echo getNamaLengkap(); ?></strong>
  </div>
  <ul class="sidebar-menu">
    <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
    <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Kategori SOP</span></a></li>
    <li><a href="sop.php"><i class="fas fa-file-alt"></i><span>Manajemen SOP</span></a></li>
    <li><a href="users.php"><i class="fas fa-users"></i><span>Manajemen User</span></a></li>
  </ul>
</aside>

<main class="main-content">
  <div class="topbar">
    <div class="topbar-left"><h2><i class="fas fa-chart-line" style="color:#3b82f6"></i> Dashboard</h2></div>
    <div class="topbar-right">
      <button type="button" id="theme-toggle-btn" title="Ganti Tema">
        <i class="fas fa-moon" id="theme-icon"></i>
      </button>
      <div class="user-info">
        <div class="user-avatar"><?php echo strtoupper(substr(getNamaLengkap(),0,1)); ?></div>
        <div><strong><?php echo getNamaLengkap(); ?></strong><p>Administrator</p></div>
      </div>
      <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>

  <div class="content-wrapper">
    <?php if($flash):?><div class="alert alert-<?php echo $flash['type'];?>"><i class="fas fa-info-circle"></i> <?php echo $flash['message'];?></div><?php endif;?>

    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-file-alt"></i></div><div class="stat-info"><h3><?php echo $total_sop;?></h3><p>Total SOP</p></div></div>
      <div class="stat-card"><div class="stat-icon green"><i class="fas fa-folder"></i></div><div class="stat-info"><h3><?php echo $total_kategori;?></h3><p>Total Kategori</p></div></div>
      <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-users"></i></div><div class="stat-info"><h3><?php echo $total_user;?></h3><p>Total User</p></div></div>
      <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-clock"></i></div><div class="stat-info"><h3><?php echo date('H:i');?></h3><p><?php echo date('d M Y');?></p></div></div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fas fa-history"></i> SOP Terbaru</h3><a href="sop.php" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Lihat Semua</a></div>
      <div class="card-body"><div class="table-responsive"><table>
        <thead><tr><th>No</th><th>Judul SOP</th><th>Kategori</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php
        $res=mysqli_query($conn,"SELECT s.*,c.nama_kategori FROM sop s LEFT JOIN categories c ON s.kategori_id=c.id ORDER BY s.created_at DESC LIMIT 5");
        $no=1; $ss=['Draft'=>'background:rgba(71,85,105,.25);color:#94a3b8;border:1px solid rgba(71,85,105,.4)','Review'=>'background:rgba(245,158,11,.20);color:#f59e0b;border:1px solid rgba(245,158,11,.4)','Disetujui'=>'background:rgba(16,185,129,.20);color:#10b981;border:1px solid rgba(16,185,129,.4)','Revisi'=>'background:rgba(239,68,68,.20);color:#ef4444;border:1px solid rgba(239,68,68,.4)'];
        if($res&&mysqli_num_rows($res)>0):while($row=mysqli_fetch_assoc($res)):$s=trim($row['status']);$style=$ss[$s]??$ss['Revisi'];?>
        <tr>
          <td><?php echo $no++;?></td>
          <td style="font-weight:600;color:var(--tm)!important"><?php echo htmlspecialchars($row['judul']);?></td>
          <td><span class="badge-cat"><?php echo htmlspecialchars($row['nama_kategori']);?></span></td>
          <td><span class="s-badge" style="<?php echo $style;?>"><?php echo $s;?></span></td>
          <td><?php echo date('d/m/Y',strtotime($row['created_at']));?></td>
          <td><a href="sop.php" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a></td>
        </tr>
        <?php endwhile;else:?><tr><td colspan="6" style="text-align:center;padding:24px;color:var(--tmut)">Belum ada data</td></tr><?php endif;?>
        </tbody>
      </table></div></div>
    </div>

    <div class="card">
      <div class="card-header"><h3><i class="fas fa-bolt"></i> Quick Actions</h3></div>
      <div class="card-body"><div class="grid-buttons">
        <a href="sop.php"      style="background:linear-gradient(135deg,#10b981,#059669)"><i class="fas fa-plus"></i> Tambah SOP</a>
        <a href="kategori.php" style="background:linear-gradient(135deg,#3b82f6,#2563eb)"><i class="fas fa-folder-plus"></i> Tambah Kategori</a>
        <a href="users.php"    style="background:linear-gradient(135deg,#f59e0b,#d97706)"><i class="fas fa-user-plus"></i> Tambah User</a>
      </div></div>
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
  btn.addEventListener('click',function(){
    var light=document.documentElement.getAttribute('data-theme')==='light';
    if(light){document.documentElement.removeAttribute('data-theme');localStorage.setItem('theme','dark');}
    else{document.documentElement.setAttribute('data-theme','light');localStorage.setItem('theme','light');}
    sync();
  });
});
</script>
</body></html>