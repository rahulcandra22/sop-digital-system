<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
    $judul = trim($_POST['judul']);
    $kategori_id = (int) $_POST['kategori_id'];
    $deskripsi = trim($_POST['deskripsi']);
    $langkah_kerja = trim($_POST['langkah_kerja']);

        if (empty($judul) || empty($langkah_kerja) || $kategori_id == 0) {
    setFlashMessage('danger', 'Field wajib tidak boleh kosong!');
    header('Location: sop.php');
    exit();
}
    $judul = mysqli_real_escape_string($conn, $judul);
    $deskripsi = mysqli_real_escape_string($conn, $deskripsi);
    $langkah_kerja = mysqli_real_escape_string($conn, $langkah_kerja);

            $created_by = getUserId();
            $file_attachment = '';
            if (isset($_FILES['file_attachment']) && $_FILES['file_attachment']['error'] == 0) {
                $target_dir = "../assets/uploads/"; $file_extension = pathinfo($_FILES['file_attachment']['name'], PATHINFO_EXTENSION);
                $file_name = time() . '_' . uniqid() . '.' . $file_extension; $target_file = $target_dir . $file_name;
                if (move_uploaded_file($_FILES['file_attachment']['tmp_name'], $target_file)) { $file_attachment = $file_name; }
            }
            $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Draft');

$sql = "INSERT INTO sop (judul, kategori_id, deskripsi, langkah_kerja, file_attachment, created_by, status) 
VALUES ('$judul', $kategori_id, '$deskripsi', '$langkah_kerja', '$file_attachment', $created_by, '$status')";

            if (mysqli_query($conn, $sql)) { setFlashMessage('success', 'SOP berhasil ditambahkan!'); } else { setFlashMessage('danger', 'Gagal menambahkan SOP!'); }
            header('Location: sop.php'); exit();
        } elseif ($_POST['action'] == 'edit') {
        $id = (int) $_POST['id'];
        $judul = trim($_POST['judul']);
        $kategori_id = (int) $_POST['kategori_id'];
        $deskripsi = trim($_POST['deskripsi']);
        $langkah_kerja = trim($_POST['langkah_kerja']);

    if (empty($judul) || empty($langkah_kerja) || $kategori_id == 0) {
setFlashMessage('danger', 'Field wajib tidak boleh kosong!');
header('Location: sop.php');
exit();
}

$judul = mysqli_real_escape_string($conn, $judul);
$deskripsi = mysqli_real_escape_string($conn, $deskripsi);
$langkah_kerja = mysqli_real_escape_string($conn, $langkah_kerja);

            $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']); $langkah_kerja = mysqli_real_escape_string($conn, $_POST['langkah_kerja']);
            $file_update = '';
            if (isset($_FILES['file_attachment']) && $_FILES['file_attachment']['error'] == 0) {
                $target_dir = "../assets/uploads/"; $file_extension = pathinfo($_FILES['file_attachment']['name'], PATHINFO_EXTENSION);
                $file_name = time() . '_' . uniqid() . '.' . $file_extension; $target_file = $target_dir . $file_name;
                if (move_uploaded_file($_FILES['file_attachment']['tmp_name'], $target_file)) {
                    $file_update = ", file_attachment='$file_name'";
                    $sql_old = "SELECT file_attachment FROM sop WHERE id=$id"; $result_old = mysqli_query($conn, $sql_old);
                    if ($row_old = mysqli_fetch_assoc($result_old)) { if ($row_old['file_attachment'] && file_exists($target_dir . $row_old['file_attachment'])) { unlink($target_dir . $row_old['file_attachment']); } }
                }
            }

            $status = $_POST['status'];
$status = mysqli_real_escape_string($conn, $status);
$sql = "UPDATE sop 
SET judul='$judul', 
    kategori_id=$kategori_id, 
    deskripsi='$deskripsi', 
    langkah_kerja='$langkah_kerja',
    status='$status'
    $file_update 
WHERE id=$id";

            if (mysqli_query($conn, $sql)) { setFlashMessage('success', 'SOP berhasil diupdate!'); } else { setFlashMessage('danger', 'Gagal mengupdate SOP!'); }
            header('Location: sop.php'); exit();
        }
    }
}
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete']; $sql_file = "SELECT file_attachment FROM sop WHERE id=$id"; $result_file = mysqli_query($conn, $sql_file);
    if ($row_file = mysqli_fetch_assoc($result_file)) { if ($row_file['file_attachment'] && file_exists("../assets/uploads/" . $row_file['file_attachment'])) { unlink("../assets/uploads/" . $row_file['file_attachment']); } }
    $sql = "DELETE FROM sop WHERE id=$id"; if (mysqli_query($conn, $sql)) { setFlashMessage('success', 'SOP berhasil dihapus!'); } else { setFlashMessage('danger', 'Gagal menghapus SOP!'); }
    header('Location: sop.php'); exit();
}
 $sql = "SELECT s.*, c.nama_kategori, u.nama_lengkap as creator FROM sop s LEFT JOIN categories c ON s.kategori_id = c.id LEFT JOIN users u ON s.created_by = u.id ORDER BY s.created_at DESC";
 $result = mysqli_query($conn, $sql); $sql_cat = "SELECT * FROM categories ORDER BY nama_kategori ASC"; $result_cat = mysqli_query($conn, $sql_cat); $flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen SOP - SOP Digital</title>
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
        td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
        
        .btn { border-radius: 8px !important; border: none !important; color: white; transition: 0.3s; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669) !important; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
        .btn-info { background: linear-gradient(135deg, #3b82f6, #2563eb) !important; }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706) !important; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626) !important; }
        
        .search-box input { background: rgba(0,0,0,0.3) !important; border: 1px solid rgba(255,255,255,0.1) !important; color: #fff !important; border-radius: 10px !important; }
        .search-box i { color: #94a3b8 !important; }
        .alert { border-radius: 10px !important; color: #fff !important; border: none !important; }
        .alert-success { background: rgba(16, 185, 129, 0.15) !important; color: #6ee7b7 !important; }
        .alert-danger { background: rgba(239, 68, 68, 0.15) !important; color: #fca5a5 !important; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .badge.status-draft { background: rgba(71, 85, 105, 0.3) !important; color: #94a3b8 !important; border: 1px solid rgba(71, 85, 105, 0.5) !important; }
        .badge.status-review { background: rgba(245, 158, 11, 0.2) !important; color: #fbbf24 !important; border: 1px solid rgba(245, 158, 11, 0.4) !important; }
        .badge.status-approved { background: rgba(16, 185, 129, 0.2) !important; color: #34d399 !important; border: 1px solid rgba(16, 185, 129, 0.4) !important; }
        .badge.status-revisi { background: rgba(239, 68, 68, 0.2) !important; color: #f87171 !important; border: 1px solid rgba(239, 68, 68, 0.4) !important; }
        .user-avatar { background: linear-gradient(135deg, #3b82f6, #8b5cf6) !important; color: white; }
        .btn-logout { padding: 8px 20px; background: rgba(239,68,68,0.2); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; text-decoration: none; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); }
        .modal-content { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; width: 90%; max-width: 900px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); color: #fff; }
        .modal-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { color: #fff; margin: 0; }
        .close { color: #94a3b8; font-size: 28px; cursor: pointer; }
        .modal-body { padding: 25px; line-height: 1.6; color: #cbd5e1; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #cbd5e1; font-weight: 500; }
        .form-control { width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 10px rgba(59,130,246,0.3); }
        
        /* Styling for AJAX content */
        .modal-body h3 { color: #3b82f6; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; margin-bottom: 20px; }
        .modal-body h4 { color: #e2e8f0; margin: 20px 0 10px 0; }
        .modal-body p { color: #94a3b8; margin-bottom: 10px; }
        .modal-body pre { background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6; white-space: pre-wrap; font-family: 'Outfit', sans-serif; }
        .modal-body strong { color: #fff; }
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
                <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Kategori SOP</span></a></li>
                <li><a href="sop.php" class="active"><i class="fas fa-file-alt"></i><span>Manajemen SOP</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i><span>Manajemen User</span></a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left"><h2><i class="fas fa-file-alt"></i> Manajemen SOP</h2></div>
                <div class="topbar-right">
                    <div class="user-info"><div class="user-avatar"><?php echo strtoupper(substr(getNamaLengkap(), 0, 1)); ?></div><div><strong style="color:white"><?php echo getNamaLengkap(); ?></strong><p style="margin:0;font-size:12px;color:#94a3b8">Administrator</p></div></div>
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            <div class="content-wrapper">
                <?php if ($flash): ?><div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div><?php endif; ?>
                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-list"></i> Daftar SOP</h3><button onclick="openModal('addModal')" class="btn btn-success"><i class="fas fa-plus"></i> Tambah SOP</button></div>
                    <div class="card-body">
                        <div class="search-box"><i class="fas fa-search"></i><input type="text" id="searchInput" onkeyup="searchTable('searchInput', 'sopTable')" placeholder="Cari SOP..."></div>
                        <div class="table-responsive"><table id="sopTable">
                            <thead><tr><th width="5%">No</th><th width="25%">Judul</th><th width="15%">Kategori</th><th width="25%">Deskripsi</th><th width="12%">Dibuat Oleh</th><th width="10%">Status</th><th width="10%">Tanggal</th><th width="8%">Aksi</th></tr></thead>
                            <tbody>
                                <?php $no=1; while($row=mysqli_fetch_assoc($result)): ?>
                                <tr><td><?php echo $no++; ?></td><td><strong><?php echo htmlspecialchars($row['judul']); ?></strong></td><td><span class="badge"><?php echo htmlspecialchars($row['nama_kategori']); ?></span></td><td><?php echo substr(htmlspecialchars($row['deskripsi']),0,60).'...'; ?></td><td><?php echo htmlspecialchars($row['creator']); ?></td>

        <!-- KOLOM STATUS -->
        <td>
        <?php
    $s = trim($row['status']);
    switch($s) {
        case 'Draft':
            echo '<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;background:rgba(71,85,105,0.5);color:#cbd5e1;border:1px solid #475569">Draft</span>';
                break;
        case 'Review':
            echo '<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;background:rgba(245,158,11,0.3);color:#fbbf24;border:1px solid #d97706">Review</span>';
            break;
        case 'Disetujui':
            echo '<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;background:rgba(16,185,129,0.3);color:#34d399;border:1px solid #059669">Disetujui</span>';
                break;
        default:
        echo '<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;background:rgba(239,68,68,0.3);color:#f87171;border:1px solid #dc2626">'.$s.'</span>';
        }
        ?>
    </td>
<td><?php echo $row['status']; ?></td>
                                <td>
                                    <button onclick="viewSOP(<?php echo $row['id']; ?>)" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                                    <button onclick="editSOP(<?php echo $row['id']; ?>)" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                                    <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirmDelete(<?php echo $row['id']; ?>, 'SOP')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                                </td></tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($result)==0): ?><tr><td colspan="7" style="text-align:center;padding:20px;">Belum ada SOP</td></tr><?php endif; ?>
                            </tbody>
                        </table></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <div id="addModal" class="modal"><div class="modal-content" style="max-width:800px">
        <div class="modal-header"><h3><i class="fas fa-plus"></i> Tambah SOP</h3><span class="close" onclick="closeModal('addModal')">&times;</span></div>
        <div class="modal-body"><form method="POST" action="" enctype="multipart/form-data"><input type="hidden" name="action" value="add">
            <div class="form-group"><label>Judul SOP *</label><input type="text" name="judul" class="form-control" required></div>
            <div class="form-group"><label>Kategori *</label><select name="kategori_id" class="form-control" required><option value="">-- Pilih --</option><?php mysqli_data_seek($result_cat,0); while($cat=mysqli_fetch_assoc($result_cat)): ?><option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nama_kategori']); ?></option><?php endwhile; ?></select></div>
            <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3"></textarea></div>
            <div class="form-group"><label>Langkah-langkah *</label><textarea name="langkah_kerja" class="form-control" rows="8" required></textarea></div>
            <div class="form-group"><label>Status</label>
<select name="status" class="form-control">
    <option value="Draft">Draft</option>
    <option value="Review">Review</option>
    <option value="Disetujui">Disetujui</option>
    <option value="Revisi">Revisi</option>
</select></div>
            <div class="form-group"><label>File Lampiran</label><input type="file" name="file_attachment" class="form-control"></div>
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
            <button type="button" onclick="closeModal('addModal')" class="btn btn-danger"><i class="fas fa-times"></i> Batal</button>
        </form></div>
    </div></div>
    
    <div id="viewModal" class="modal"><div class="modal-content" style="max-width:900px"><div class="modal-header"><h3>Detail SOP</h3><span class="close" onclick="closeModal('viewModal')">&times;</span></div><div class="modal-body" id="viewContent"></div></div></div>
    <div id="editModal" class="modal"><div class="modal-content" style="max-width:800px"><div class="modal-header"><h3>Edit SOP</h3><span class="close" onclick="closeModal('editModal')">&times;</span></div><div class="modal-body" id="editContent"></div></div></div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        function viewSOP(id){fetch('sop_ajax.php?action=view&id='+id).then(r=>r.text()).then(d=>{document.getElementById('viewContent').innerHTML=d;openModal('viewModal');});}
        function editSOP(id){fetch('sop_ajax.php?action=edit&id='+id).then(r=>r.text()).then(d=>{document.getElementById('editContent').innerHTML=d;openModal('editModal');});}
    </script>
</body>
</html>