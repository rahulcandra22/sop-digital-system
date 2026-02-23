<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['action'])) {

        // ================= TAMBAH =================
        if ($_POST['action'] == 'add') {

            $nama = trim($_POST['nama_kategori']);
            $deskripsi = trim($_POST['deskripsi']);
            
            $cek = mysqli_query($conn, "SELECT id FROM categories WHERE nama_kategori='$nama'");
            if (mysqli_num_rows($cek) > 0) {
                setFlashMessage('danger', 'Nama kategori sudah ada!');
                header('Location: kategori.php');
            exit();
            }

            if (empty($nama)) {
                setFlashMessage('danger', 'Nama kategori wajib diisi!');
                header('Location: kategori.php');
                exit();
            }

            $nama = mysqli_real_escape_string($conn, $nama);
            $deskripsi = mysqli_real_escape_string($conn, $deskripsi);

            $sql = "INSERT INTO categories (nama_kategori, deskripsi) 
                    VALUES ('$nama', '$deskripsi')";

            if (mysqli_query($conn, $sql)) {
                setFlashMessage('success', 'Kategori berhasil ditambahkan!');
            } else {
                setFlashMessage('danger', 'Gagal menambahkan kategori!');
            }

            header('Location: kategori.php');
            exit();
        }

        // ================= EDIT =================
        elseif ($_POST['action'] == 'edit') {

            $id = (int) $_POST['id'];
            $nama = trim($_POST['nama_kategori']);
            $deskripsi = trim($_POST['deskripsi']);

            if (empty($nama)) {
                setFlashMessage('danger', 'Nama kategori wajib diisi!');
                header('Location: kategori.php');
                exit();
            }

            $nama = mysqli_real_escape_string($conn, $nama);
            $deskripsi = mysqli_real_escape_string($conn, $deskripsi);

            $sql = "UPDATE categories 
                    SET nama_kategori='$nama', deskripsi='$deskripsi' 
                    WHERE id=$id";

            if (mysqli_query($conn, $sql)) {
                setFlashMessage('success', 'Kategori berhasil diupdate!');
            } else {
                setFlashMessage('danger', 'Gagal mengupdate kategori!');
            }

            header('Location: kategori.php');
            exit();
        }
    }
}

// ================= DELETE =================
if (isset($_GET['delete'])) {

    $id = (int) $_GET['delete'];

    // CEK apakah kategori masih dipakai di tabel sop
    $cek = mysqli_query($conn, "SELECT COUNT(*) as total FROM sop WHERE kategori_id=$id");
    $data = mysqli_fetch_assoc($cek);

    if ($data['total'] > 0) {
        setFlashMessage('danger', 'Kategori tidak bisa dihapus karena masih digunakan oleh SOP!');
        header('Location: kategori.php');
        exit();
    }

    // Kalau tidak dipakai, baru hapus
    $sql = "DELETE FROM categories WHERE id=$id";

    if (mysqli_query($conn, $sql)) {
        setFlashMessage('success', 'Kategori berhasil dihapus!');
    } else {
        setFlashMessage('danger', 'Gagal menghapus kategori!');
    }

    header('Location: kategori.php');
    exit();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM categories WHERE id=$id";
    if (mysqli_query($conn, $sql)) { setFlashMessage('success', 'Kategori berhasil dihapus!'); } else { setFlashMessage('danger', 'Gagal menghapus kategori!'); }
    header('Location: kategori.php'); exit();
}
 $sql = "SELECT * FROM categories ORDER BY nama_kategori ASC";
 $result = mysqli_query($conn, $sql);
 $flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - SOP Digital</title>
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
        
        .card { background: var(--glass-bg) !important; border: 1px solid rgba(255,255,255,0.08) !important; border-radius: 16px !important; }
        .card-header h3 { color: #fff !important; }
        
        table { width: 100%; border-collapse: collapse; color: #cbd5e1; }
        th { background: rgba(0,0,0,0.3) !important; color: #94a3b8 !important; padding: 15px; font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        
        .btn { border-radius: 8px !important; border: none !important; color: white; transition: 0.3s; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669) !important; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706) !important; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626) !important; }
        .search-box input { background: rgba(0,0,0,0.3) !important; border: 1px solid rgba(255,255,255,0.1) !important; color: #fff !important; border-radius: 10px !important; }
        .search-box i { color: #94a3b8 !important; }
        .alert { border-radius: 10px !important; color: #fff !important; border: none !important; }
        .alert-success { background: rgba(16, 185, 129, 0.15) !important; color: #6ee7b7 !important; }
        .alert-danger { background: rgba(239, 68, 68, 0.15) !important; color: #fca5a5 !important; }
        .user-avatar { background: linear-gradient(135deg, #3b82f6, #8b5cf6) !important; color: white; }
        .btn-logout { padding: 8px 20px; background: rgba(239,68,68,0.2); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; text-decoration: none; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); }
        .modal-content { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); margin: 5% auto; border-radius: 16px; width: 90%; max-width: 600px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .modal-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { color: #fff; margin: 0; }
        .close { color: #94a3b8; font-size: 28px; cursor: pointer; }
        .modal-body { padding: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #cbd5e1; font-weight: 500; }
        .form-control { width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 10px rgba(59,130,246,0.3); }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo">
                <h3 style="color: white;">SOP Digital</h3><p style="color: #94a3b8;">Admin Panel</p>
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
                <div class="topbar-left"><h2><i class="fas fa-folder"></i> Manajemen Kategori</h2></div>
                <div class="topbar-right">
                    <div class="user-info"><div class="user-avatar"><?php echo strtoupper(substr(getNamaLengkap(), 0, 1)); ?></div><div><strong style="color:white"><?php echo getNamaLengkap(); ?></strong><p style="margin:0;font-size:12px;color:#94a3b8">Administrator</p></div></div>
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            <div class="content-wrapper">
                <?php if ($flash): ?><div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div><?php endif; ?>
                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-list"></i> Daftar Kategori</h3><button onclick="openModal('addModal')" class="btn btn-success"><i class="fas fa-plus"></i> Tambah Kategori</button></div>
                    <div class="card-body">
                        <div class="search-box"><i class="fas fa-search"></i><input type="text" id="searchInput" onkeyup="searchTable('searchInput', 'categoryTable')" placeholder="Cari kategori..."></div>
                        <div class="table-responsive"><table id="categoryTable">
                            <thead><tr><th width="5%">No</th><th width="25%">Nama</th><th width="45%">Deskripsi</th><th width="15%">Tanggal</th><th width="10%">Aksi</th></tr></thead>
                            <tbody>
                                <?php $no=1; while($row=mysqli_fetch_assoc($result)): ?>
                                <tr><td><?php echo $no++; ?></td><td><strong><?php echo htmlspecialchars($row['nama_kategori']); ?></strong></td><td><?php echo htmlspecialchars($row['deskripsi']); ?></td><td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <button onclick="editCategory(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama_kategori'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($row['deskripsi'], ENT_QUOTES); ?>')" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                                    <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirmDelete(<?php echo $row['id']; ?>, 'kategori')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                                </td></tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($result)==0): ?><tr><td colspan="6" style="text-align:center;padding:20px;">Belum ada kategori</td></tr><?php endif; ?>
                            </tbody>
                        </table></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <div id="addModal" class="modal"><div class="modal-content">
        <div class="modal-header"><h3><i class="fas fa-plus"></i> Tambah Kategori</h3><span class="close" onclick="closeModal('addModal')">&times;</span></div>
        <div class="modal-body"><form method="POST" action=""><input type="hidden" name="action" value="add">
            <div class="form-group"><label>Nama Kategori *</label><input type="text" name="nama_kategori" class="form-control" required></div>
            <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" class="form-control" rows="4"></textarea></div>
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
            <button type="button" onclick="closeModal('addModal')" class="btn btn-danger"><i class="fas fa-times"></i> Batal</button>
        </form></div>
    </div></div>
    
    <div id="editModal" class="modal"><div class="modal-content">
        <div class="modal-header"><h3><i class="fas fa-edit"></i> Edit Kategori</h3><span class="close" onclick="closeModal('editModal')">&times;</span></div>
        <div class="modal-body"><form method="POST" action=""><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id">
            <div class="form-group"><label>Nama Kategori *</label><input type="text" name="nama_kategori" id="edit_nama_kategori" class="form-control" required></div>
            <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="4"></textarea></div>
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update</button>
            <button type="button" onclick="closeModal('editModal')" class="btn btn-danger"><i class="fas fa-times"></i> Batal</button>
        </form></div>
    </div></div>
    
    <script src="../assets/js/script.js"></script>
    <script>function editCategory(id, nama, deskripsi){document.getElementById('edit_id').value=id;document.getElementById('edit_nama_kategori').value=nama;document.getElementById('edit_deskripsi').value=deskripsi;openModal('editModal');}</script>
</body>
</html>