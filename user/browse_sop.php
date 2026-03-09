<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireUser();

$user_id = getUserId();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $judul = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $kid   = (int)$_POST['kategori_id'];
    $desk  = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
    $lk    = mysqli_real_escape_string($conn, trim($_POST['langkah_kerja']));
    $st    = 'Review';
    $cb    = $user_id;
    $fa    = '';
    
    if (empty($judul) || empty($lk) || $kid == 0) {
        setFlashMessage('danger', 'Field wajib tidak boleh kosong!');
        header('Location: browse_sop.php'); exit();
    }
    if (isset($_FILES['file_attachment']) && $_FILES['file_attachment']['error'] == 0) {
        $dir = "../assets/uploads/";
        $ext = pathinfo($_FILES['file_attachment']['name'], PATHINFO_EXTENSION);
        $fn  = time().'_'.uniqid().'.'.$ext;
        if (move_uploaded_file($_FILES['file_attachment']['tmp_name'], $dir.$fn)) $fa = $fn;
    }
    if (mysqli_query($conn, "INSERT INTO sop (judul,kategori_id,deskripsi,langkah_kerja,file_attachment,created_by,status) VALUES ('$judul',$kid,'$desk','$lk','$fa',$cb,'$st')"))
        setFlashMessage('success', 'SOP berhasil diajukan dan sedang menunggu Review Admin!');
    else
        setFlashMessage('danger', 'Gagal mengajukan SOP!');
    header('Location: browse_sop.php'); exit();
}

$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search          = isset($_GET['search'])   ? $_GET['search']   : '';
$where           = "WHERE (s.status = 'Disetujui' OR s.created_by = $user_id)";
if ($kategori_filter) $where .= " AND s.kategori_id = ".intval($kategori_filter);
if ($search) {
    $ss = mysqli_real_escape_string($conn, $search);
    $where .= " AND (s.judul LIKE '%$ss%' OR s.deskripsi LIKE '%$ss%' OR c.nama_kategori LIKE '%$ss%')";
}
$result     = mysqli_query($conn, "SELECT s.*,c.nama_kategori FROM sop s LEFT JOIN categories c ON s.kategori_id=c.id $where ORDER BY s.created_at DESC");
$result_cat = mysqli_query($conn, "SELECT * FROM categories ORDER BY nama_kategori ASC");
$flash      = getFlashMessage();
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
        :root {
            --bg:#020617; --sb:rgba(15,23,42,.97); --tb:rgba(15,23,42,.87); --cb:rgba(30,41,59,.75);
            --gb:rgba(255,255,255,.08); --tm:#f8fafc; --tmut:#94a3b8; --tsub:#cbd5e1; --ibg:rgba(0,0,0,.30);
            --mbg:#1e293b; --mbor:rgba(255,255,255,.10); --lf:brightness(0) invert(1);
            --lbg:rgba(239,68,68,.18); --lc:#fca5a5; --lbor:rgba(239,68,68,.30);
            --sl:#94a3b8; --sa:rgba(59,130,246,.12); --togbg:rgba(30,41,59,.80); --togc:#94a3b8;
        }
        [data-theme="light"] {
            --bg:#f0f4f8; --sb:rgba(255,255,255,.98); --tb:rgba(255,255,255,.96); --cb:rgba(255,255,255,.95);
            --gb:rgba(0,0,0,.09); --tm:#0f172a; --tmut:#64748b; --tsub:#334155; --ibg:rgba(255,255,255,.95);
            --mbg:#ffffff; --mbor:rgba(0,0,0,.10); --lf:none;
            --lbg:rgba(239,68,68,.07); --lc:#dc2626; --lbor:rgba(239,68,68,.18);
            --sl:#64748b; --sa:rgba(59,130,246,.08); --togbg:rgba(241,245,249,.95); --togc:#475569;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family:'Outfit',sans-serif!important; background-color:var(--bg)!important; color:var(--tm)!important; margin:0; overflow-x:hidden; transition:background-color .35s,color .35s; }
        body::before { content:''; position:fixed; inset:0; z-index:-1; background:radial-gradient(circle at 15% 50%,rgba(59,130,246,.07),transparent 30%); pointer-events:none; }
        .sidebar { background:var(--sb)!important; border-right:1px solid var(--gb)!important; backdrop-filter:blur(12px); }
        .sidebar-header { border-bottom:1px solid var(--gb)!important; padding:20px; }
        .sidebar-header p { color:var(--tmut)!important; margin:0; font-size:12px; }
        .sidebar-menu { list-style:none; margin:0; padding:12px 0; }
        .sidebar-menu li a { display:flex; align-items:center; gap:10px; padding:12px 20px; color:var(--sl)!important; text-decoration:none; border-left:3px solid transparent; font-size:14px; font-weight:500; transition:.25s; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background:var(--sa)!important; color:#3b82f6!important; border-left-color:#3b82f6; }
        .main-content { background:transparent!important; }
        .topbar { background:var(--tb)!important; border-bottom:1px solid var(--gb)!important; backdrop-filter:blur(12px); display:flex; align-items:center; justify-content:space-between; padding:0 24px; height:64px; }
        .topbar-left h2 { color:var(--tm)!important; font-size:20px; font-weight:700; margin:0; display:flex; align-items:center; gap:8px; }
        .topbar-right { display:flex; align-items:center; gap:12px; }
        #theme-toggle-btn { all:unset; cursor:pointer; width:40px; height:40px; border-radius:50%; background:var(--togbg)!important; border:1px solid var(--gb)!important; color:var(--togc)!important; display:flex!important; align-items:center; justify-content:center; font-size:17px; box-shadow:0 2px 8px rgba(0,0,0,.15); flex-shrink:0; transition:all .25s; }
        #theme-toggle-btn:hover { color:#3b82f6!important; transform:scale(1.1); }
        #theme-toggle-btn i { pointer-events:none; color:inherit!important; font-size:17px; }
        .user-info { display:flex; align-items:center; gap:10px; }
        .user-info strong { color:var(--tm)!important; font-size:14px; display:block; }
        .user-info p { color:var(--tmut)!important; margin:0; font-size:11px; }
        .user-avatar { width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg,#3b82f6,#8b5cf6)!important; color:#fff!important; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px; flex-shrink:0; }
        .btn-logout { padding:8px 18px; background:var(--lbg)!important; color:var(--lc)!important; border:1px solid var(--lbor)!important; border-radius:8px; text-decoration:none; font-size:13px; font-weight:500; white-space:nowrap; display:flex; align-items:center; gap:6px; }
        .content-wrapper { padding:24px; }
        .card { background:var(--cb)!important; border:1px solid var(--gb)!important; border-radius:16px!important; box-shadow:0 4px 24px rgba(0,0,0,.10); margin-bottom:24px; }
        .card-header { padding:18px 22px; border-bottom:1px solid var(--gb); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
        .card-header h3 { color:var(--tm)!important; margin:0; font-size:16px; font-weight:600; }
        .card-body { padding:22px; }
        .form-control { width:100%; padding:11px 14px; background:var(--ibg)!important; border:1px solid var(--gb)!important; border-radius:8px; color:var(--tm)!important; font-family:'Outfit',sans-serif; font-size:14px; transition:.3s; }
        .form-control:focus { outline:none; border-color:#3b82f6!important; box-shadow:0 0 0 3px rgba(59,130,246,.15); }
        .form-group { margin-bottom:15px; }
        .form-group label { color:var(--tsub)!important; margin-bottom:8px; display:block; font-weight:600; font-size:13px; }
        textarea.form-control { resize:vertical; }
        select.form-control option { background:var(--mbg); color:var(--tm); }
        .btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:9px!important; border:none!important; color:#fff!important; font-weight:600; font-size:13px; cursor:pointer; text-decoration:none; transition:.25s; }
        .btn:hover { filter:brightness(1.1); transform:translateY(-2px); }
        .btn-success { background:linear-gradient(135deg,#10b981,#059669)!important; box-shadow:0 4px 12px rgba(16,185,129,.3); }
        .btn-danger { background:linear-gradient(135deg,#ef4444,#dc2626)!important; box-shadow:0 4px 12px rgba(239,68,68,.3); }
        .btn-info { background:linear-gradient(135deg,#3b82f6,#2563eb)!important; box-shadow:0 4px 12px rgba(59,130,246,.3); }
        .btn-sm { padding:6px 12px!important; font-size:12px!important; }
        .alert { border-radius:10px!important; padding:12px 18px; margin-bottom:20px; display:flex; align-items:center; gap:10px; font-size:14px; }
        .alert-success { background:rgba(16,185,129,.12)!important; color:#059669!important; border:1px solid rgba(16,185,129,.25)!important; }
        .alert-danger { background:rgba(239,68,68,.12)!important; color:#dc2626!important; border:1px solid rgba(239,68,68,.25)!important; }
        .badge { padding:5px 12px; border-radius:20px; font-size:12px; background:var(--sa); color:#3b82f6; border:1px solid rgba(59,130,246,.3); }
        .s-badge { padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; float:right; }
        .sop-card { background:var(--cb); border:1px solid var(--gb); border-radius:12px; padding:20px; transition:all .3s ease; position:relative; overflow:hidden; }
        .sop-card:hover { border-color:#3b82f6; transform:translateY(-5px); box-shadow:0 10px 20px rgba(0,0,0,.1); }
        .sop-card h4 { color:var(--tm); margin-bottom:10px; font-size:16px; font-weight:600; padding-right:60px; }
        .sop-card p { color:var(--tsub); font-size:13px; line-height:1.6; margin-bottom:15px; }
        .sop-meta { display:flex; justify-content:space-between; align-items:center; padding-top:15px; border-top:1px solid var(--gb); }
        .sop-meta small { color:var(--tmut); }
        .empty-state { text-align:center; padding:60px 20px; color:var(--tmut); }
        .empty-state h3 { color:var(--tm); margin-top:10px; }

        /* ─── Modal ─── */
        .modal { display:none; position:fixed; z-index:9999; inset:0; background:rgba(0,0,0,.65); backdrop-filter:blur(6px); }
        .modal-content { background:var(--mbg)!important; border:1px solid var(--mbor)!important; border-radius:16px; width:90%; max-width:700px; margin:3% auto; box-shadow:0 20px 50px rgba(0,0,0,.4); max-height:92vh; overflow-y:auto; }
        .modal-header { padding:16px 22px; border-bottom:1px solid var(--mbor); display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; background:var(--mbg); z-index:2; border-radius:16px 16px 0 0; }
        .modal-header h3 { color:var(--tm)!important; margin:0; font-size:15px; font-weight:700; display:flex; align-items:center; gap:8px; }
        .modal-close { background:none; border:none; color:var(--tmut); font-size:22px; cursor:pointer; line-height:1; padding:0; }
        .modal-close:hover { color:var(--tm); }
        .modal-body { padding:22px; }

        /* ─── Form labels dengan badge ─── */
        .form-row-label { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
        .form-row-label .field-name { font-size:13px; font-weight:600; color:var(--tsub)!important; display:flex; align-items:center; gap:5px; }
        .form-row-label .field-name i { font-size:11px; color:#3b82f6; }
        .badge-req  { font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:rgba(239,68,68,.15);  color:#f87171; border:1px solid rgba(239,68,68,.25); }
        .badge-opt  { font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:rgba(148,163,184,.12); color:#94a3b8; border:1px solid rgba(148,163,184,.20); }
        .badge-info { font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:rgba(59,130,246,.12);  color:#60a5fa; border:1px solid rgba(59,130,246,.25); }

        /* ─── Hint kecil di bawah field ─── */
        .field-hint { font-size:11.5px; color:var(--tmut); margin-top:5px; line-height:1.5; display:flex; align-items:flex-start; gap:5px; }
        .field-hint i { font-size:11px; margin-top:2px; flex-shrink:0; }
        .field-hint.blue   i { color:#60a5fa; }
        .field-hint.yellow i { color:#f59e0b; }

        /* ─── Banner peringatan ─── */
        .warn-banner { display:flex; gap:11px; align-items:flex-start; background:rgba(245,158,11,.10); border:1px solid rgba(245,158,11,.28); border-radius:10px; padding:12px 14px; margin-bottom:18px; }
        .warn-banner .wi { color:#f59e0b; font-size:14px; margin-top:2px; flex-shrink:0; }
        .warn-banner .wt { font-size:12px; color:#fbbf24; line-height:1.65; }
        .warn-banner .wt strong { display:block; font-size:12.5px; color:#fcd34d; margin-bottom:3px; }

        /* ─── Panduan langkah ─── */
        .steps-guide { background:rgba(59,130,246,.07); border:1px solid rgba(59,130,246,.18); border-radius:8px; padding:11px 14px; margin-bottom:8px; }
        .steps-guide p { color:var(--tmut)!important; font-size:12px; margin:0 0 6px; font-weight:600; }
        .steps-guide ol { margin:0; padding-left:16px; }
        .steps-guide ol li { color:#93c5fd; font-size:12px; line-height:1.7; }

        /* ─── Kotak konfirmasi ─── */
        .confirm-box { background:rgba(239,68,68,.08); border:1px solid rgba(239,68,68,.22); border-radius:10px; padding:12px 14px; margin-bottom:16px; }
        .confirm-box label { display:flex; align-items:flex-start; gap:9px; cursor:pointer; margin:0; }
        .confirm-box input[type="checkbox"] { margin-top:3px; accent-color:#ef4444; flex-shrink:0; }
        .confirm-box .confirm-text { font-size:12px; color:#fca5a5; line-height:1.6; }
        .confirm-box .confirm-text strong { color:#f87171; }

        .form-actions { display:flex; gap:10px; }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo.png" alt="Logo" style="width:220px">
            <p>SOP Digital System</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="browse_sop.php" class="active"><i class="fas fa-file-alt"></i><span>Daftar SOP</span></a></li>
            <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Kategori SOP</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h2><i class="fas fa-file-alt" style="color:#3b82f6"></i> Daftar SOP</h2>
            </div>
            <div class="topbar-right">
                <button type="button" id="theme-toggle-btn" title="Ganti Tema">
                    <i class="fas fa-moon" id="theme-icon"></i>
                </button>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr(getNamaLengkap(), 0, 1)); ?></div>
                    <div>
                        <strong><?php echo getNamaLengkap(); ?></strong>
                        <p>User</p>
                    </div>
                </div>
                <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="content-wrapper">

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas <?php echo $flash['type']=='success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <!-- Filter & Tombol Ajukan -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="" style="display:grid;grid-template-columns:1fr 1fr auto;gap:15px;align-items:end">
                        <div class="form-group" style="margin:0">
                            <label>Cari Judul SOP</label>
                            <input type="text" name="search" class="form-control" placeholder="Cari SOP..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label>Kategori</label>
                            <select name="kategori" class="form-control">
                                <option value="">Semua Kategori</option>
                                <?php
                                mysqli_data_seek($result_cat, 0);
                                while ($cat = mysqli_fetch_assoc($result_cat)):
                                ?>
                                <option value="<?php echo $cat['id']; ?>"
                                    <?php echo ($kategori_filter == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div style="display:flex;gap:10px">
                            <button type="submit" class="btn btn-info"><i class="fas fa-search"></i> Cari</button>
                            <a href="browse_sop.php" class="btn btn-danger"><i class="fas fa-redo"></i> Reset</a>
                            <button type="button" onclick="openModal('addModal')" class="btn btn-success">
                                <i class="fas fa-plus"></i> Ajukan SOP Baru
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Daftar Kartu SOP -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-file-alt"></i> Daftar Seluruh Dokumen SOP</h3>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px">
                        <?php
                        $ss = [
                            'Draft'     => 'background:rgba(71,85,105,.25);color:#94a3b8;border:1px solid rgba(71,85,105,.4)',
                            'Review'    => 'background:rgba(245,158,11,.20);color:#f59e0b;border:1px solid rgba(245,158,11,.4)',
                            'Disetujui' => 'background:rgba(16,185,129,.20);color:#10b981;border:1px solid rgba(16,185,129,.4)',
                            'Revisi'    => 'background:rgba(239,68,68,.20);color:#ef4444;border:1px solid rgba(239,68,68,.4)'
                        ];
                        while ($row = mysqli_fetch_assoc($result)):
                            $s     = trim($row['status']);
                            $style = $ss[$s] ?? $ss['Revisi'];
                        ?>
                        <div class="sop-card">
                            <div style="margin-bottom:10px">
                                <span class="badge"><?php echo htmlspecialchars($row['nama_kategori']); ?></span>
                                <span class="s-badge" style="<?php echo $style; ?>"><?php echo htmlspecialchars($s); ?></span>
                            </div>
                            <h4><?php echo htmlspecialchars($row['judul']); ?></h4>
                            <p><?php echo substr(htmlspecialchars($row['deskripsi']), 0, 100).'...'; ?></p>
                            <div class="sop-meta">
                                <small><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($row['created_at'])); ?></small>
                                <a href="view_sop.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> Lihat Detail
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>

                    <?php if (mysqli_num_rows($result) == 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox" style="font-size:64px;margin-bottom:20px;opacity:.3"></i>
                        <h3>Tidak ada SOP ditemukan</h3>
                        <p>Belum ada SOP yang Disetujui atau Anda belum pernah mengajukan SOP.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- ═══════════ MODAL AJUKAN SOP BARU ═══════════ -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-paper-plane"></i> Ajukan SOP Baru</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <div class="modal-body">

            <!-- Banner Peringatan -->
            <div class="warn-banner">
                <i class="fas fa-exclamation-triangle wi"></i>
                <div class="wt">
                    <strong>Perhatian! Baca Sebelum Mengajukan SOP!</strong>
                    SOP yang Anda ajukan akan masuk ke notifikasi Admin dan harus disetujui sebelum dapat digunakan.
                    <strong>Pastikan dokumen belum tersedia di sistem, ditulis dengan lengkap dan akurat, serta sesuai dengan standar penulisan yang berlaku.<strong>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">

                <!-- Judul + Kategori -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin-bottom:0">
                        <div class="form-row-label">
                            <span class="field-name"><i class="fas fa-heading"></i> Judul SOP</span>
                            <span class="badge-req">Wajib di isi!</span>
                        </div>
                        <input type="text" name="judul" class="form-control"
                               placeholder="Contoh: SOP Pengajuan Cuti" required>
                        <div class="field-hint yellow">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Judul harus spesifik. Hindari nama terlalu umum.</span>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0">
                        <div class="form-row-label">
                            <span class="field-name"><i class="fas fa-folder"></i> Kategori</span>
                            <span class="badge-req">Wajib di isi!</span>
                        </div>
                        <select name="kategori_id" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php
                            mysqli_data_seek($result_cat, 0);
                            while ($cat = mysqli_fetch_assoc($result_cat)):
                            ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nama_kategori']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="field-hint blue">
                            <i class="fas fa-info-circle"></i>
                            <span>Jika kategori belum ada, hubungi Admin.</span>
                        </div>
                    </div>
                </div>

                <!-- Deskripsi -->
                <div class="form-group" style="margin-top:14px">
                    <div class="form-row-label">
                        <span class="field-name"><i class="fas fa-align-left"></i> Deskripsi Singkat</span>
                        <span class="badge-opt">Opsional</span>
                    </div>
                    <textarea name="deskripsi" class="form-control" rows="3"
                              placeholder="Jelaskan tujuan dan ruang lingkup SOP ini..."></textarea>
                    <div class="field-hint blue">
                        <i class="fas fa-lightbulb"></i>
                        <span>Tuliskan tujuan SOP, siapa yang terlibat, dan kapan prosedur ini digunakan.</span>
                    </div>
                </div>

                <!-- Langkah Kerja -->
                <div class="form-group">
                    <div class="form-row-label">
                        <span class="field-name"><i class="fas fa-list-ol"></i> Langkah-langkah Kerja</span>
                        <span class="badge-req">Wajib</span>
                    </div>
                    <div class="steps-guide">
                        <p><i class="fas fa-book-open" style="margin-right:5px"></i> Panduan Penulisan:</p>
                        <ol>
                            <li>Tulis setiap langkah secara berurutan, satu baris per langkah</li>
                            <li>Awali dengan nomor atau kata kerja aktif</li>
                            <li>Gunakan bahasa yang jelas, singkat, dan mudah dipahami</li>
                        </ol>
                    </div>
                    <textarea name="langkah_kerja" class="form-control" rows="7" required
                              placeholder="Silahkan isi langkah-langkah pembuatan SOP ini..."></textarea>
                    <div class="field-hint yellow">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Langkah yang tidak lengkap akan dikembalikan Admin untuk Revisi.</span>
                    </div>
                </div>

                <!-- File Lampiran -->
                <div class="form-group">
                    <div class="form-row-label">
                        <span class="field-name"><i class="fas fa-paperclip"></i> File Lampiran</span>
                        <span class="badge-opt">Opsional</span>
                    </div>
                    <input type="file" name="file_attachment" class="form-control"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg">
                    <div class="field-hint blue">
                        <i class="fas fa-file-alt"></i>
                        <span>Format yang diterima: PDF, Word, Excel, atau Gambar (JPG/PNG).</span>
                    </div>
                </div>

                <!-- Konfirmasi -->
                <div class="confirm-box">
                    <label>
                        <input type="checkbox" id="confirmSOP">
                        <span class="confirm-text">
                            Saya menyatakan SOP ini <strong>belum tersedia</strong> di sistem,
                            ditulis dengan lengkap dan akurat, serta <strong>siap untuk di-review</strong> oleh Admin.
                        </span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" id="btnAjukan" class="btn btn-success"
                            disabled style="opacity:.45;cursor:not-allowed">
                        <i class="fas fa-paper-plane"></i> Ajukan SOP
                    </button>
                    <button type="button" onclick="closeModal('addModal')" class="btn btn-danger">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
(function(){
    if(localStorage.getItem('theme')==='light')
        document.documentElement.setAttribute('data-theme','light');
})();

document.addEventListener('DOMContentLoaded', function(){
    var btn  = document.getElementById('theme-toggle-btn'),
        icon = document.getElementById('theme-icon');
    function sync(){
        icon.className = document.documentElement.getAttribute('data-theme')==='light'
            ? 'fas fa-sun' : 'fas fa-moon';
    }
    sync();
    if(btn){
        btn.addEventListener('click', function(){
            var isLight = document.documentElement.getAttribute('data-theme')==='light';
            if(isLight){ document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme','dark'); }
            else { document.documentElement.setAttribute('data-theme','light'); localStorage.setItem('theme','light'); }
            sync();
        });
    }

    // Aktifkan tombol Ajukan setelah checkbox dicentang
    var chk  = document.getElementById('confirmSOP'),
        save = document.getElementById('btnAjukan');
    if(chk && save){
        chk.addEventListener('change', function(){
            save.disabled      = !this.checked;
            save.style.opacity = this.checked ? '1' : '.45';
            save.style.cursor  = this.checked ? 'pointer' : 'not-allowed';
        });
    }
});

function openModal(id){  document.getElementById(id).style.display='block'; }
function closeModal(id){ document.getElementById(id).style.display='none';  }
window.onclick = function(e){
    var m = document.getElementById('addModal');
    if(e.target == m) m.style.display='none';
};
</script>
</body>
</html>