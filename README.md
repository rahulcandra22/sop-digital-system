# SOP Digital System - PT. Sinergi Nusantara Integrasi

Sistem Manajemen Dokumen Standard Operating Procedure (SOP) berbasis web yang dirancang untuk mempermudah akses, pengelolaan, dan standarisasi operasional di lingkungan perusahaan.

## 📁 Struktur Proyek
- `/admin` : Modul khusus admin untuk manajemen data.
- `/user` : Modul akses untuk karyawan atau user.
- `/config` : Konfigurasi database dan sistem.
- `/assets` : Kumpulan file HTML, CSS, JS, dan (Logo/Gambar).
- `/includes` : File logic yang digunakan berulang (seperti session).

## ✨ Fitur Utama
- **Dashboard Interaktif**: Ringkasan data SOP yang tersedia.
- **Authentication**: Login yang aman dengan hak akses (Admin & User).
- **Forgot Password**: Fitur pemulihan akun melalui token.
- **Preview & Print**: Membaca SOP langsung di web dan mencetaknya secara rapi.
- **Manajemen Kategori**: Pengelompokan SOP berdasarkan departemen.

## 🛠️ Teknologi yang Digunakan
- **Backend**: PHP 8.2 (Native)
- **Database**: MySQL / MariaDB
- **Frontend**: HTML, CSS & JavaScript
- **Server**: XAMPP (Apache dan MySQL)

## 🚀 Cara Instalasi
1. Pindahkan folder ini ke direktori `htdocs` Anda.
2. Import database `database.sql` melalui phpMyAdmin.
3. Sesuaikan username dan password database di file `config/database.php`.
4. Jalankan `localhost/sop-digital-system` pada browser Anda.

## 🔑 Akun Demo
Gunakan akun berikut untuk masuk ke dalam sistem:

| Role | Email | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin@sinergi.co.id` | `sinergi` |
| **User (Rahul)** | `rahul@sinergi.co.id` | `rahul` |
| **User (Bulan)** | `bulanhidayatul@sinergi.co.id` | `bulan` |

## 👨‍💻 Kontributor
**Internship Project 2026** Dibuat oleh **Rahul Candra** sebagai mahasiswa bagian dari program magang di PT. Sinergi Nusantara Integrasi.