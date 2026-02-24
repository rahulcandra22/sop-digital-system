<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $nama = trim($_POST['nama_kategori']);
    $desk = trim($_POST['deskripsi']);
    
    if (empty($nama)) {
        setFlashMessage('danger', 'Nama Kategori tidak boleh kosong!');
        header('Location: kategori.php');
        exit();
    }
    
    $nama = mysqli_real_escape_string($conn, $nama);
    $desk = mysqli_real_escape_string($conn, $desk);
    
    if ($_POST['action'] == 'add') {
        if (mysqli_query($conn, "INSERT INTO categories (nama_kategori,deskripsi) VALUES ('$nama','$desk')")) {
            setFlashMessage('success', 'Kategori berhasil ditambahkan!');
        } else {
            setFlashMessage('danger', 'Gagal menambahkan kategori!');
        }
    } elseif ($_POST['action'] == 'edit') {
        $id = (int)$_POST['id'];
        if (mysqli_query($conn, "UPDATE categories SET nama_kategori='$nama',deskripsi='$desk' WHERE id=$id")) {
            setFlashMessage('success', 'Kategori berhasil diupdate!');
        } else {
            setFlashMessage('danger', 'Gagal mengupdate kategori!');
        }
    }
    
    header('Location: kategori.php');
    exit();
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Opsional: Anda bisa menambahkan pengecekan apakah kategori sedang digunakan di tabel SOP sebelum menghapus
    if (mysqli_query($conn, "DELETE FROM categories WHERE id=$id")) {
        setFlashMessage('success', 'Kategori berhasil dihapus!');
    } else {
        setFlashMessage('danger', 'Gagal menghapus kategori!');
    }
    header('Location: kategori.php');
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");
$flash  = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #020617; --sb: rgba(15, 23, 42, .97); --tb: rgba(15, 23, 42, .87); --cb: rgba(30, 41, 59, .75); 
            --gb: rgba(255, 255, 255, .08); --tm: #f8fafc; --tmut: #94a3b8; --tsub: #cbd5e1; --thbg: rgba(0, 0, 0, .35); 
            --trodd: rgba(15, 23, 42, .55); --treven: rgba(15, 23, 42, .35); --trhov: rgba(59, 130, 246, .09); 
            --tbor: rgba(255, 255, 255, .06); --ibg: rgba(0, 0, 0, .30); --mbg: #1e293b; --mbor: rgba(255, 255, 255, .10); 
            --lf: brightness(0) invert(1); --lbg: rgba(239, 68, 68, .18); --lc: #fca5a5; --lbor: rgba(239, 68, 68, .30); 
            --sl: #94a3b8; --sa: rgba(59, 130, 246, .12); --togbg: rgba(30, 41, 59, .80); --togc: #94a3b8; 
            --sbg: rgba(0, 0, 0, .30); --sbor: rgba(255, 255, 255, .10);
        }
        [data-theme="light"] {
            --bg: #f0f4f8; --sb: rgba(255, 255, 255, .98); --tb: rgba(255, 255, 255, .96); --cb: rgba(255, 255, 255, .95); 
            --gb: rgba(0, 0, 0, .09); --tm: #0f172a; --tmut: #64748b; --tsub: #334155; --thbg: #e9eef5; 
            --trodd: #ffffff; --treven: #f8fafc; --trhov: #eff6ff; --tbor: rgba(0, 0, 0, .07); --ibg: rgba(255, 255, 255, .95); 
            --mbg: #ffffff; --mbor: rgba(0, 0, 0, .10); --lf: none; --lbg: rgba(239, 68, 68, .07); --lc: #dc2626; 
            --lbor: rgba(239, 68, 68, .18); --sl: #64748b; --sa: rgba(59, 130, 246, .08); --togbg: rgba(241, 245, 249, .95); 
            --togc: #475569; --sbg: rgba(255, 255, 255, .95); --sbor: rgba(0, 0, 0, .10);
        }
        
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif !important; background-color: var(--bg) !important; color: var(--tm) !important; margin: 0; overflow-x: hidden; transition: background-color .35s, color .35s; }
        body::before { content: ''; position: fixed; inset: 0; z-index: -1; background: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, .07), transparent 30%); pointer-events: none; }
        
        .sidebar { background: var(--sb) !important; border-right: 1px solid var(--gb) !important; backdrop-filter: blur(12px); }
        .sidebar-header { border-bottom: 1px solid var(--gb) !important; padding: 20px; }
        .sidebar-header h3 { color: var(--tm) !important; margin: 4px 0 2px; font-size: 16px; font-weight: 700; }
        .sidebar-header p { color: var(--tmut) !important; margin: 0; font-size: 12px; }
        .sidebar-logo { filter: var(--lf); max-width: 80px; }
        .sidebar-menu { list-style: none; margin: 0; padding: 12px 0; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: var(--sl) !important; text-decoration: none; border-left: 3px solid transparent; font-size: 14px; font-weight: 500; transition: .25s; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: var(--sa) !important; color: #3b82f6 !important; border-left-color: #3b82f6; }
        
        .main-content { background: transparent !important; }
        .topbar { background: var(--tb) !important; border-bottom: 1px solid var(--gb) !important; backdrop-filter: blur(12px); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; height: 64px; }
        .topbar-left h2 { color: var(--tm) !important; font-size: 20px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        
        #theme-toggle-btn { all: unset; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; background: var(--togbg) !important; border: 1px solid var(--gb) !important; color: var(--togc) !important; display: flex !important; align-items: center; justify-content: center; font-size: 17px; box-shadow: 0 2px 8px rgba(0, 0, 0, .15); flex-shrink: 0; transition: all .25s; }
        #theme-toggle-btn:hover { color: #3b82f6 !important; transform: scale(1.1); }
        #theme-toggle-btn i { pointer-events: none; color: inherit !important; font-size: 17px; }
        
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-info strong { color: var(--tm) !important; font-size: 14px; display: block; }
        .user-info p { color: var(--tmut) !important; margin: 0; font-size: 11px; }
        .user-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6) !important; color: #fff !important; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; }
        .btn-logout { padding: 8px 18px; background: var(--lbg) !important; color: var(--lc) !important; border: 1px solid var(--lbor) !important; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; white-space: nowrap; display: flex; align-items: center; gap: 6px; }
        
        .content-wrapper { padding: 24px; }
        .card { background: var(--cb) !important; border: 1px solid var(--gb) !important; border-radius: 16px !important; box-shadow: 0 4px 24px rgba(0, 0, 0, .10); margin-bottom: 24px; overflow: hidden; }
        .card-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid var(--gb); }
        .card-header h3 { color: var(--tm) !important; margin: 0; font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .card-body { padding: 22px; }
        
        .table-responsive { overflow-x: auto; border-radius: 10px; overflow: hidden; }
        table { width: 100% !important; border-collapse: collapse !important; }
        thead tr { background: var(--thbg) !important; }
        thead th { background: var(--thbg) !important; color: var(--tmut) !important; padding: 13px 16px !important; font-size: .75rem !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: .6px !important; border: none !important; }
        tbody tr:nth-child(odd) td { background: var(--trodd) !important; }
        tbody tr:nth-child(even) td { background: var(--treven) !important; }
        tbody tr:hover td { background: var(--trhov) !important; }
        tbody td { color: var(--tsub) !important; padding: 14px 16px !important; border-bottom: 1px solid var(--tbor) !important; border-top: none !important; border-left: none !important; border-right: none !important; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none !important; }
        
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 9px !important; border: none !important; color: #fff !important; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; transition: .25s; }
        .btn:hover { filter: brightness(1.1); transform: translateY(-2px); }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669) !important; box-shadow: 0 4px 12px rgba(16, 185, 129, .3); }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706) !important; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626) !important; box-shadow: 0 4px 12px rgba(239, 68, 68, .3); }
        .btn-sm { padding: 6px 12px !important; font-size: 12px !important; }
        
        .alert { border-radius: 10px !important; padding: 12px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; }
        .alert-success { background: rgba(16, 185, 129, .12) !important; color: #059669 !important; border: 1px solid rgba(16, 185, 129, .25) !important; }
        .alert-danger { background: rgba(239, 68, 68, .12) !important; color: #dc2626 !important; border: 1px solid rgba(239, 68, 68, .25) !important; }
        
        .search-wrap { position: relative; margin-bottom: 18px; }
        .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--tmut); }
        .search-wrap input { width: 100%; padding: 11px 14px 11px 40px; background: var(--sbg) !important; border: 1px solid var(--sbor) !important; border-radius: 10px; color: var(--tm) !important; font-family: 'Outfit', sans-serif; font-size: 14px; outline: none; transition: .3s; }
        .search-wrap input:focus { border-color: #3b82f6 !important; box-shadow: 0 0 0 3px rgba(59, 130, 246, .15); }
        .search-wrap input::placeholder { color: var(--tmut); }
        
        .modal { display: none; position: fixed; z-index: 9999; inset: 0; background: rgba(0, 0, 0, .65); backdrop-filter: blur(6px); }
        .modal-content { background: var(--mbg) !important; border: 1px solid var(--mbor) !important; border-radius: 16px; width: 90%; max-width: 500px; margin: 5% auto; box-shadow: 0 20px 50px rgba(0, 0, 0, .4); max-height: 92vh; overflow-y: auto; }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--mbor); display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; background: var(--mbg); z-index: 2; }
        .modal-header h3 { color: var(--tm) !important; margin: 0; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .close { color: var(--tmut); font-size: 26px; cursor: pointer; line-height: 1; }
        .close:hover { color: var(--tm); }
        .modal-body { padding: 24px; color: var(--tsub) !important; }
        
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 7px; color: var(--tsub) !important; font-weight: 600; font-size: 13px; }
        .form-control { width: 100%; padding: 11px 14px; background: var(--ibg) !important; border: 1px solid var(--gb) !important; border-radius: 8px; color: var(--tm) !important; font-family: 'Outfit', sans-serif; font-size: 14px; transition: .3s; }
        .form-control:focus { outline: none; border-color: #3b82f6 !important; box-shadow: 0 0 0 3px rgba(59, 130, 246, .15); }
        .form-control::placeholder { color: var(--tmut); }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2991/2991148.png'">
            <h3>SOP Digital</h3>
            <p>Admin Panel</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
            <li><a href="kategori.php" class="active"><i class="fas fa-folder"></i><span>Kategori SOP</span></a></li>
            <li><a href="sop.php"><i class="fas fa-file-alt"></i><span>Manajemen SOP</span></a></li>
            <li><a href="users.php"><i class="fas fa-users"></i><span>Manajemen User</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h2><i class="fas fa-folder" style="color:#3b82f6"></i> Manajemen Kategori</h2>
            </div>
            <div class="topbar-right">
                <button type="button" id="theme-toggle-btn" title="Ganti Tema">
                    <i class="fas fa-moon" id="theme-icon"></i>
                </button>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr(getNamaLengkap(), 0, 1)); ?></div>
                    <div>
                        <strong><?php echo getNamaLengkap(); ?></strong>
                        <p>Administrator</p>
                    </div>
                </div>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="content-wrapper">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-info-circle"></i> <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar Kategori</h3>
                    <button onclick="openModal('addModal')" class="btn btn-success"><i class="fas fa-plus"></i> Tambah Kategori</button>
                </div>
                <div class="card-body">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" onkeyup="searchTable('searchInput', 'kategoriTable')" placeholder="Cari Kategori...">
                    </div>
                    <div class="table-responsive">
                        <table id="kategoriTable">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="25%">Nama</th>
                                    <th width="45%">Deskripsi</th>
                                    <th width="15%">Tanggal</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($result)): 
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td style="font-weight:600;color:var(--tm)!important"><?php echo htmlspecialchars($row['nama_kategori']); ?></td>
                                        <td style="color:var(--tmut)!important"><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                                        <td><?php echo isset($row['created_at']) ? date('d/m/Y', strtotime($row['created_at'])) : '-'; ?></td>
                                        <td>
                                            <button onclick="editKategori(<?php echo $row['id']; ?>)" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                                            <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirmDelete(<?php echo $row['id']; ?>, 'Kategori')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php 
                                endwhile; 
                                if (mysqli_num_rows($result) == 0): 
                                ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center;padding:24px;color:var(--tmut)">Belum ada Kategori</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Tambah Kategori</h3>
            <span class="close" onclick="closeModal('addModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama_kategori" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="4" placeholder="Deskripsi kategori..."></textarea>
                </div>
                
                <div style="display:flex;gap:10px;margin-top:20px;">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
                    <button type="button" onclick="closeModal('addModal')" class="btn btn-danger"><i class="fas fa-times"></i> Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Kategori</h3>
            <span class="close" onclick="closeModal('editModal')">&times;</span>
        </div>
        <div class="modal-body" id="editContent">
            <div style="text-align:center;padding:30px;color:var(--tmut)">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
    (function() {
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();

    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('theme-toggle-btn'),
            icon = document.getElementById('theme-icon');

        function sync() {
            icon.className = document.documentElement.getAttribute('data-theme') === 'light' ? 'fas fa-sun' : 'fas fa-moon';
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

    function editKategori(id) {
        document.getElementById('editContent').innerHTML = '<div style="text-align:center;padding:30px;color:var(--tmut)"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
        openModal('editModal');
        
        fetch('kategori_ajax.php?action=edit&id=' + id)
            .then(r => r.text())
            .then(d => {
                document.getElementById('editContent').innerHTML = d;
            });
    }
</script>
</body>
</html>