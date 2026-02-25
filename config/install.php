<?php
// Database Installation Script
// Run this file once to create database and tables

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'sop_digital_db';

// Create connection without database
$conn = mysqli_connect($host, $user, $pass);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (mysqli_query($conn, $sql)) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . mysqli_error($conn) . "<br>";
}

// Select database
mysqli_select_db($conn, $dbname);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'users' created successfully<br>";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "<br>";
}

// Create categories table
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'categories' created successfully<br>";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "<br>";
}

// Create sop table
$sql = "CREATE TABLE IF NOT EXISTS sop (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    kategori_id INT NOT NULL,
    deskripsi TEXT,
    langkah_kerja TEXT NOT NULL,
    file_attachment VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'sop' created successfully<br>";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "<br>";
}

// Insert default admin user (password: sinergi)
$password_admin = password_hash('sinergi', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (username, password, role, nama_lengkap) VALUES 
        ('admin@sinergi.co.id', '$password_admin', 'admin', 'Administrator')
        ON DUPLICATE KEY UPDATE username=username";

if (mysqli_query($conn, $sql)) {
    echo "Admin user created successfully<br>";
} else {
    echo "Error creating admin: " . mysqli_error($conn) . "<br>";
}

// Insert default regular user (password: rahul)
$password_user = password_hash('rahul', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (username, password, role, nama_lengkap) VALUES 
        ('rahulcandra@sinergi.co.id', '$password_user', 'user', 'Rahul Candra')
        ON DUPLICATE KEY UPDATE username=username";

if (mysqli_query($conn, $sql)) {
    echo "Regular user created successfully<br>";
} else {
    echo "Error creating user: " . mysqli_error($conn) . "<br>";
}

// Insert sample categories
$categories = [
    ['IT & Teknologi', 'Kategori untuk proses kerja terkait IT dan teknologi'],
    ['Administrasi', 'Kategori untuk proses kerja administrasi'],
    ['Keuangan', 'Kategori untuk proses kerja keuangan'],
    ['SDM', 'Kategori untuk proses kerja sumber daya manusia']
];

foreach ($categories as $cat) {
    $sql = "INSERT INTO categories (nama_kategori, deskripsi) VALUES ('{$cat[0]}', '{$cat[1]}')
            ON DUPLICATE KEY UPDATE nama_kategori=nama_kategori";
    mysqli_query($conn, $sql);
}

echo "Sample categories created successfully<br>";

// Insert sample SOP
$sql = "INSERT INTO sop (judul, kategori_id, deskripsi, langkah_kerja, created_by, status) VALUES 
        ('Prosedur Instalasi Software', 1, 'Panduan lengkap untuk instalasi software di perusahaan', 
        '1. Pastikan komputer memenuhi spesifikasi minimum\n2. Download software dari sumber resmi\n3. Jalankan installer\n4. Ikuti wizard instalasi\n5. Restart komputer jika diperlukan\n6. Verifikasi instalasi berhasil', 1, 'Disetujui')
        ON DUPLICATE KEY UPDATE judul=judul";

if (mysqli_query($conn, $sql)) {
    echo "Sample SOP created successfully<br>";
}

echo "<br><strong>Installation completed!</strong><br>";
echo "Admin Login: admin@sinergi.co.id / sinergi<br>";
echo "User Login: rahulcandra@sinergi.co.id / rahul<br>";
echo "<br><a href='../index.php'>Go to Login Page</a>";

mysqli_close($conn);
?>
