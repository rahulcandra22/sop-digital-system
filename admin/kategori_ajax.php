<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    if ($_GET['action'] == 'edit') {
        $res = mysqli_query($conn, "SELECT * FROM categories WHERE id=$id");
        $row = mysqli_fetch_assoc($res);
        
        if ($row) {
            // Return form with validation elements
            ?>
            <form method="POST" action="kategori.php" id="formEditKategori">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

                <div class="form-group">
                    <div class="form-row-label">
                        <span class="field-name"><i class="fas fa-tag"></i> Nama Kategori</span>
                        <span class="badge-req">Wajib di isi!</span>
                    </div>
                    <input type="text" name="nama_kategori" id="edit_nama" class="form-control"
                           value="<?php echo htmlspecialchars($row['nama_kategori']); ?>" required>
                    <div class="field-error" id="error-edit-nama" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                    <div class="field-hint yellow">
                        <!-- hint optional -->
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-row-label">
                        <span class="field-name"><i class="fas fa-align-left"></i> Deskripsi</span>
                        <span class="badge-opt">Opsional</span>
                    </div>
                    <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="4"><?php echo htmlspecialchars($row['deskripsi']); ?></textarea>
                    <div class="field-hint blue">
                        <!-- hint optional -->
                    </div>
                </div>

                <div class="confirm-box">
                    <label>
                        <input type="checkbox" id="confirmEdit">
                        <span class="confirm-text">
                            <strong>Saya menyatakan perubahan data kategori ini sudah benar dan sesuai.</strong>
                        </span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" id="btnSimpanEdit" class="btn btn-success" disabled style="opacity:.45;cursor:not-allowed">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <button type="button" onclick="closeModal('editModal')" class="btn btn-danger">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
            <script>
                // Initialize validation for edit form after it's loaded
                (function() {
                    const editNama = document.getElementById('edit_nama');
                    const errorNama = document.getElementById('error-edit-nama');
                    const chkEdit = document.getElementById('confirmEdit');
                    const btnEdit = document.getElementById('btnSimpanEdit');
                    const editForm = document.getElementById('formEditKategori');

                    function validateEditForm() {
                        let isValid = true;
                        const namaVal = editNama.value.trim();

                        if (namaVal.length < 3) {
                            errorNama.style.display = 'flex';
                            errorNama.querySelector('span').textContent = 'Nama kategori minimal 3 karakter.';
                            isValid = false;
                        } else {
                            errorNama.style.display = 'none';
                        }
                        return isValid;
                    }

                    function updateEditButton() {
                        const valid = validateEditForm();
                        const enabled = chkEdit.checked && valid;
                        btnEdit.disabled = !enabled;
                        btnEdit.style.opacity = enabled ? '1' : '.45';
                        btnEdit.style.cursor = enabled ? 'pointer' : 'not-allowed';
                    }

                    editNama.addEventListener('input', updateEditButton);
                    chkEdit.addEventListener('change', updateEditButton);
                    updateEditButton(); // initial state

                    editForm.addEventListener('submit', function(e) {
                        if (!validateEditForm() || !chkEdit.checked) {
                            e.preventDefault();
                            alert('Harap periksa kembali: nama kategori minimal 3 karakter dan konfirmasi harus dicentang.');
                            return false;
                        }
                    });
                })();
            </script>
            <?php
        } else {
            echo '<p style="color:red">Data Kategori tidak ditemukan.</p>';
        }
    }
}
?>