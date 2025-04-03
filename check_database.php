<?php
// Set zona waktu default ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Tambahkan header untuk mencegah caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Informasi koneksi database
$host = 'localhost';
$username = 'root';
$password = ''; // Password default Laragon biasanya kosong
$database = 'db_toko';

// Cek file konfigurasi kustom jika ada
$config_file = dirname(__FILE__) . '/db_config.php';
if (file_exists($config_file)) {
    include($config_file);
}

echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengecekan Database Toko Sederhana</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1, h2 {
            color: #2563eb;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .warning {
            color: #856404;
            background-color: #fff3cd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .info {
            color: #0c5460;
            background-color: #d1ecf1;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        button, a.button {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px 10px 0;
        }
        button:hover, a.button:hover {
            background-color: #1d4ed8;
        }
        .step {
            margin-bottom: 10px;
            padding: 10px;
            border-left: 3px solid #2563eb;
            background-color: #f0f7ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-database"></i> Pengecekan Database Toko Sederhana</h1>
        <p>Halaman ini akan memeriksa status database dan akan otomatis memperbaikinya jika ada masalah.</p>
    </div>';

// Jika ada action untuk membuat database baru
if (isset($_GET['action']) && $_GET['action'] == 'create_db') {
    try {
        $conn = mysqli_connect($host, $username, $password);
        
        if (!$conn) {
            throw new Exception("Koneksi MySQL gagal: " . mysqli_connect_error());
        }
        
        // Buat database baru
        if (mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            echo '<div class="container success">
                <p><i class="fas fa-check-circle"></i> Database <strong>' . $database . '</strong> berhasil dibuat!</p>
                <p>Selanjutnya Anda perlu menjalankan setup untuk membuat tabel-tabel yang diperlukan.</p>
                <a href="setup.php" class="button">Lanjutkan ke Setup</a>
            </div>';
        } else {
            echo '<div class="container error">
                <p><i class="fas fa-times-circle"></i> Gagal membuat database. Error: ' . mysqli_error($conn) . '</p>
                <p>Silakan cek konfigurasi MySQL atau buat database secara manual.</p>
            </div>';
        }
        
        mysqli_close($conn);
    } catch (Exception $e) {
        echo '<div class="container error">
            <p><i class="fas fa-times-circle"></i> Error: ' . $e->getMessage() . '</p>
        </div>';
    }
}
// Cek status database
else {
    try {
        // Koneksi ke MySQL tanpa database
        $conn = mysqli_connect($host, $username, $password);
        
        if (!$conn) {
            throw new Exception("Koneksi MySQL gagal: " . mysqli_connect_error());
        }
        
        // Cek apakah database sudah ada
        $db_exists = mysqli_select_db($conn, $database);
        
        if ($db_exists) {
            echo '<div class="container success">
                <p><i class="fas fa-check-circle"></i> Database <strong>' . $database . '</strong> sudah ada dan siap digunakan!</p>';
            
            // Cek apakah tabel users ada
            $table_exists = mysqli_query($conn, "SHOW TABLES FROM `$database` LIKE 'users'");
            
            if (mysqli_num_rows($table_exists) > 0) {
                echo '<p>Tabel users juga sudah ada.</p>
                <p>Status database: <strong>OK</strong></p>
                <a href="index.php" class="button">Kembali ke Aplikasi</a>';
            } else {
                echo '<p>Database ada tetapi tidak ditemukan tabel users.</p>
                <p>Anda perlu menjalankan setup untuk membuat tabel-tabel.</p>
                <a href="setup.php" class="button">Lanjutkan ke Setup</a>';
            }
            
            echo '</div>';
        } else {
            echo '<div class="container warning">
                <p><i class="fas fa-exclamation-triangle"></i> Database <strong>' . $database . '</strong> tidak ditemukan.</p>
                <p>Anda perlu membuat database baru.</p>
                <a href="?action=create_db" class="button">Buat Database Baru</a>
            </div>';
        }
        
        mysqli_close($conn);
    } catch (Exception $e) {
        echo '<div class="container error">
            <p><i class="fas fa-times-circle"></i> Error: ' . $e->getMessage() . '</p>
            <p>Silakan cek konfigurasi koneksi database Anda.</p>
        </div>';
    }
}

echo '<div class="container">
    <h2>Bantuan & Informasi</h2>
    <div class="info">
        <p><strong>Konfigurasi Database:</strong></p>
        <ul>
            <li>Host: ' . $host . '</li>
            <li>Username: ' . $username . '</li>
            <li>Password: ' . (empty($password) ? '(kosong)' : '****') . '</li>
            <li>Database: ' . $database . '</li>
        </ul>
        <p>Jika Anda perlu mengubah konfigurasi database, edit file <code>db_config.php</code>.</p>
    </div>
    
    <div class="step">
        <p><strong>Langkah untuk memulai ulang dari awal:</strong></p>
        <ol>
            <li>Buka phpMyAdmin (http://localhost/phpmyadmin)</li>
            <li>Drop database <code>' . $database . '</code> jika ada</li>
            <li>Kembali ke halaman ini dan klik "Buat Database Baru"</li>
            <li>Ikuti proses setup</li>
        </ol>
    </div>
    
    <div class="step">
        <p><strong>Links:</strong></p>
        <a href="index.php" class="button">Halaman Utama</a>
        <a href="setup.php" class="button">Setup Database</a>
        <a href="migrate.php" class="button">Tool Migrasi</a>
    </div>
</div>

<div style="text-align: center; margin-top: 30px; color: #666;">
    <p>Toko Sederhana &copy; ' . date('Y') . ' - Tool Pengecekan Database</p>
</div>
</body>
</html>';
?> 