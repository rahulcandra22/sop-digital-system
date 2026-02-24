<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

if(isset($_GET['action']) && isset($_GET['id'])){
  $id = (int)$_GET['id'];

  if($_GET['action'] == 'edit'){
    $res = mysqli_query($conn, "SELECT * FROM categories WHERE id=$id");
    $row = mysqli_fetch_assoc($res);
    
    if($row){
      echo '<form method="POST" action="kategori.php">';
      echo '<input type="hidden" name="action" value="edit">';
      echo '<input type="hidden" name="id" value="'.$row['id'].'">';
      
      echo '<div class="form-group">';
      echo '<label>Nama Kategori *</label>';
      echo '<input type="text" name="nama_kategori" class="form-control" value="'.htmlspecialchars($row['nama_kategori']).'" required>';
      echo '</div>';
      
      echo '<div class="form-group">';
      echo '<label>Deskripsi</label>';
      echo '<textarea name="deskripsi" class="form-control" rows="4">'.htmlspecialchars($row['deskripsi']).'</textarea>';
      echo '</div>';
      
      echo '<div style="display:flex;gap:10px;margin-top:20px;">';
      echo '<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Update</button>';
      echo '<button type="button" onclick="closeModal(\'editModal\')" class="btn btn-danger"><i class="fas fa-times"></i> Batal</button>';
      echo '</div>';
      echo '</form>';
    } else {
        echo '<p style="color:red">Data Kategori tidak ditemukan.</p>';
    }
  }
}
?>