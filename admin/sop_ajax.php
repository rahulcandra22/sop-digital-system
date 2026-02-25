<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    if ($_GET['action'] == 'view') {
        $res = mysqli_query($conn, "SELECT s.*,c.nama_kategori,u.nama_lengkap as creator FROM sop s LEFT JOIN categories c ON s.kategori_id=c.id LEFT JOIN users u ON s.created_by=u.id WHERE s.id=$id");
        $row = mysqli_fetch_assoc($res);
        
        if ($row) {
            $s = trim($row['status']);
            $ss = [
                'Draft'     => 'background:rgba(71,85,105,.25);color:#94a3b8;border:1px solid rgba(71,85,105,.4)',
                'Review'    => 'background:rgba(245,158,11,.20);color:#f59e0b;border:1px solid rgba(245,158,11,.4)',
                'Disetujui' => 'background:rgba(16,185,129,.20);color:#10b981;border:1px solid rgba(16,185,129,.4)',
                'Revisi'    => 'background:rgba(239,68,68,.20);color:#ef4444;border:1px solid rgba(239,68,68,.4)'
            ];
            $style = $ss[$s] ?? $ss['Revisi'];
            
            echo '<h3>' . htmlspecialchars($row['judul']) . '</h3>';
            echo '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px">';
            echo '<span style="background:rgba(59,130,246,.18);color:#60a5fa;border:1px solid rgba(59,130,246,.30);padding:4px 12px;border-radius:20px;font-size:.8rem"><i class="fas fa-folder" style="margin-right:5px"></i>' . htmlspecialchars($row['nama_kategori']) . '</span>';
            echo '<span class="s-badge" style="' . $style . '">' . $s . '</span>';
            echo '</div>';
            echo '<p><strong>Dibuat oleh:</strong> ' . htmlspecialchars($row['creator']) . ' &nbsp;|&nbsp; <strong>Tanggal:</strong> ' . date('d F Y, H:i', strtotime($row['created_at'])) . '</p>';
            
            if ($row['deskripsi']) {
                echo '<div class="info-block"><h4 style="margin:0 0 8px;font-size:14px;color:inherit">Deskripsi</h4><p style="margin:0">' . nl2br(htmlspecialchars($row['deskripsi'])) . '</p></div>';
            }
            
            echo '<h4>Langkah-langkah Kerja</h4><pre>' . htmlspecialchars($row['langkah_kerja']) . '</pre>';
            
            if ($row['file_attachment']) {
                echo '<div class="file-block"><h4 style="margin:0 0 8px;font-size:14px">File Lampiran</h4><a href="../assets/uploads/' . htmlspecialchars($row['file_attachment']) . '" target="_blank" style="color:#60a5fa;text-decoration:none"><i class="fas fa-download" style="margin-right:6px"></i>Download File</a></div>';
            }
        }
        
    } elseif ($_GET['action'] == 'edit') {
        $res  = mysqli_query($conn, "SELECT * FROM sop WHERE id=$id");
        $row  = mysqli_fetch_assoc($res);
        $rcat = mysqli_query($conn, "SELECT * FROM categories ORDER BY nama_kategori ASC");
        
        if ($row) {
            echo '<form method="POST" action="sop.php" enctype="multipart/form-data"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="' . $row['id'] . '">';
            echo '<div class="form-group"><label>Judul SOP</label><input type="text" name="judul" class="form-control" value="' . htmlspecialchars($row['judul']) . '" required></div>';
            echo '<div class="form-group"><label>Kategori</label><select name="kategori_id" class="form-control" required>';
            
            while ($cat = mysqli_fetch_assoc($rcat)) {
                $sel = ($cat['id'] == $row['kategori_id']) ? 'selected' : '';
                echo '<option value="' . $cat['id'] . '" ' . $sel . '>' . htmlspecialchars($cat['nama_kategori']) . '</option>';
            }
            
            echo '</select></div>';
            echo '<div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3">' . htmlspecialchars($row['deskripsi']) . '</textarea></div>';
            echo '<div class="form-group"><label>Langkah-langkah</label><textarea name="langkah_kerja" class="form-control" rows="8" required>' . htmlspecialchars($row['langkah_kerja']) . '</textarea></div>';
            echo '<div class="form-group"><label>Status</label><select name="status" class="form-control">';
            
            foreach (['Draft', 'Review', 'Disetujui', 'Revisi'] as $st) {
                $sel = ($row['status'] == $st) ? 'selected' : '';
                echo '<option value="' . $st . '" ' . $sel . '>' . $st . '</option>';
            }
            
            echo '</select></div>';
            echo '<div class="form-group"><label>File Lampiran</label>';
            
            if ($row['file_attachment']) {
                echo '<p style="font-size:12px;margin-bottom:8px;color:var(--tmut)">File saat ini: <strong>' . htmlspecialchars($row['file_attachment']) . '</strong></p>';
            }
            
            echo '<input type="file" name="file_attachment" class="form-control"></div>';
            echo '<div style="display:flex;gap:10px"><button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update</button><button type="button" onclick="closeModal(\'editModal\')" class="btn btn-danger"><i class="fas fa-times"></i> Batal</button></div></form>';
        }
    }
}
?>