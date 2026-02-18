<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    
    if ($_GET['action'] == 'view') {
        $sql = "SELECT s.*, c.nama_kategori, u.nama_lengkap as creator FROM sop s 
                LEFT JOIN categories c ON s.kategori_id = c.id 
                LEFT JOIN users u ON s.created_by = u.id WHERE s.id = $id";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        
        if ($row) {
            echo '<div>';
            echo '<h3 style="color:#60a5fa; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px; margin-bottom:15px;">' . htmlspecialchars($row['judul']) . '</h3>';
            echo '<p style="color:#94a3b8; margin:5px 0;"><strong style="color:#fff">Kategori:</strong> <span style="background:rgba(59,130,246,0.2);color:#60a5fa;padding:4px 10px;border-radius:15px;font-size:0.8rem">' . htmlspecialchars($row['nama_kategori']) . '</span></p>';
            echo '<p style="color:#94a3b8; margin:5px 0;"><strong style="color:#fff">Status:</strong> ' . htmlspecialchars($row['status']) . '</p>';
            echo '<p style="color:#94a3b8; margin:5px 0;"><strong style="color:#fff">Dibuat oleh:</strong> ' . htmlspecialchars($row['creator']) . ' | <strong style="color:#fff">Tanggal:</strong> ' . date('d F Y, H:i', strtotime($row['created_at'])) . '</p>';
            
            if ($row['deskripsi']) {
                echo '<div style="margin:20px 0;padding:15px;background:rgba(0,0,0,0.2);border-radius:8px;border:1px solid rgba(255,255,255,0.05)">
                    <h4 style="color:#e2e8f0;margin:0 0 10px 0;">Deskripsi</h4>
                    <p style="color:#cbd5e1;margin:0">' . nl2br(htmlspecialchars($row['deskripsi'])) . '</p>
                </div>';
            }
            
            echo '<div style="margin-bottom:20px">
                <h4 style="color:#e2e8f0;margin-bottom:15px;">Langkah-langkah Kerja</h4>
                <pre style="background:rgba(0,0,0,0.3);padding:15px;border-radius:8px;border-left:4px solid #3b82f6;white-space:pre-wrap;font-family:\'Outfit\',sans-serif;color:#cbd5e1">' . htmlspecialchars($row['langkah_kerja']) . '</pre>
            </div>';
            
            if ($row['file_attachment']) {
                echo '<div style="margin-top:20px;padding:15px;background:rgba(59,130,246,0.1);border-radius:8px;border:1px solid rgba(59,130,246,0.2)">
                    <h4 style="color:#e2e8f0;margin:0 0 10px 0;">File Lampiran</h4>
                    <a href="../assets/uploads/' . $row['file_attachment'] . '" target="_blank" style="color:#60a5fa;text-decoration:none"><i class="fas fa-download"></i> Download File</a>
                </div>';
            }
            echo '</div>';
        }

    } elseif ($_GET['action'] == 'edit') {
        $sql = "SELECT * FROM sop WHERE id = $id";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        $sql_cat = "SELECT * FROM categories ORDER BY nama_kategori ASC";
        $result_cat = mysqli_query($conn, $sql_cat);
        
        if ($row) {
            echo '<form method="POST" action="sop.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="' . $row['id'] . '">';
            
            // Judul
            echo '<div class="form-group">
                <label style="display:block;margin-bottom:8px;color:#cbd5e1;">Judul SOP *</label>
                <input type="text" name="judul" class="form-control" value="' . htmlspecialchars($row['judul']) . '" required>
            </div>';
            
            // Kategori
            echo '<div class="form-group">
                <label style="display:block;margin-bottom:8px;color:#cbd5e1;">Kategori *</label>
                <select name="kategori_id" class="form-control" required>';
            while ($cat = mysqli_fetch_assoc($result_cat)) {
                $selected = ($cat['id'] == $row['kategori_id']) ? 'selected' : '';
                echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['nama_kategori']) . '</option>';
            }
            echo '</select></div>';
            
            // Deskripsi
            echo '<div class="form-group">
                <label style="display:block;margin-bottom:8px;color:#cbd5e1;">Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="3">' . htmlspecialchars($row['deskripsi']) . '</textarea>
            </div>';
            
            // Langkah
            echo '<div class="form-group">
                <label style="display:block;margin-bottom:8px;color:#cbd5e1;">Langkah-langkah *</label>
                <textarea name="langkah_kerja" class="form-control" rows="8" required>' . htmlspecialchars($row['langkah_kerja']) . '</textarea>
            </div>';
            
            // Status (sebelum tombol)
            echo '<div class="form-group">
                <label style="display:block;margin-bottom:8px;color:#cbd5e1;">Status *</label>
                <select name="status" class="form-control" required>';
            $statuses = ['Draft', 'Review', 'Disetujui', 'Revisi'];
            foreach ($statuses as $st) {
                $selected = ($row['status'] == $st) ? 'selected' : '';
                echo '<option value="' . $st . '" ' . $selected . '>' . $st . '</option>';
            }
            echo '</select></div>';
            
            // File
            echo '<div class="form-group">
                <label style="display:block;margin-bottom:8px;color:#cbd5e1;">File Lampiran</label>';
            if ($row['file_attachment']) {
                echo '<p style="font-size:13px;color:#94a3b8;margin-bottom:10px">File saat ini: <strong>' . $row['file_attachment'] . '</strong></p>';
            }
            echo '<input type="file" name="file_attachment" class="form-control"></div>';
            
            // Tombol
            echo '<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update</button> 
            <button type="button" onclick="closeModal(\'editModal\')" class="btn btn-danger"><i class="fas fa-times"></i> Batal</button>
            </form>';
        }
    }
}
?>