<?php
// Set zona waktu default ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Informasi koneksi database
$host = 'localhost';
$username = 'root';
$password = ''; // Password default Laragon biasanya kosong
$database = 'db_toko';

// Koneksi ke MySQL tanpa database
$conn = mysqli_connect($host, $username, $password);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

echo "<h2>Setup Database untuk Sistem Manajemen Toko</h2>";

// Buat database jika belum ada
$sql_create_db = "CREATE DATABASE IF NOT EXISTS $database";

if (mysqli_query($conn, $sql_create_db)) {
    echo "Database berhasil dibuat atau sudah ada<br>";
} else {
    die("Error membuat database: " . mysqli_error($conn));
}

// Pilih database
mysqli_select_db($conn, $database);

// Buat tabel users
$sql_create_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('admin', 'kasir') NOT NULL,
    remember_token VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql_create_users)) {
    echo "Tabel users berhasil dibuat<br>";
} else {
    die("Error membuat tabel users: " . mysqli_error($conn));
}

// Buat tabel barang
$sql_create_barang = "CREATE TABLE IF NOT EXISTS barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    harga DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql_create_barang)) {
    echo "Tabel barang berhasil dibuat<br>";
    
    // Tambahkan data contoh untuk barang
    $insert_barang = "INSERT INTO barang (kode, nama, stok, harga) VALUES 
        ('BRG001', 'Buku Tulis', 100, 5000.00),
        ('BRG002', 'Pensil', 200, 2000.00),
        ('BRG003', 'Penghapus', 150, 1500.00),
        ('BRG004', 'Rautan', 100, 3000.00),
        ('BRG005', 'Penggaris', 50, 4000.00)";
    
    if (mysqli_query($conn, $insert_barang)) {
        echo "Data barang contoh berhasil ditambahkan<br>";
    }
} else {
    die("Error membuat tabel barang: " . mysqli_error($conn));
}

// Buat tabel transaksi
$sql_create_transaksi = "CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATETIME NOT NULL,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (mysqli_query($conn, $sql_create_transaksi)) {
    echo "Tabel transaksi berhasil dibuat<br>";
} else {
    die("Error membuat tabel transaksi: " . mysqli_error($conn));
}

// Buat tabel transaksi_detail
$sql_create_transaksi_detail = "CREATE TABLE IF NOT EXISTS transaksi_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    barang_id INT NOT NULL,
    jumlah INT NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id),
    FOREIGN KEY (barang_id) REFERENCES barang(id)
)";

if (mysqli_query($conn, $sql_create_transaksi_detail)) {
    echo "Tabel transaksi_detail berhasil dibuat<br>";
} else {
    die("Error membuat tabel transaksi_detail: " . mysqli_error($conn));
}

// Buat tabel log_aktivitas
$sql_create_log = "CREATE TABLE IF NOT EXISTS log_aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    aktivitas VARCHAR(255) NOT NULL,
    timestamp DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (mysqli_query($conn, $sql_create_log)) {
    echo "Tabel log_aktivitas berhasil dibuat<br>";
} else {
    die("Error membuat tabel log_aktivitas: " . mysqli_error($conn));
}

// Cek apakah user admin sudah ada
$sql_check_admin = "SELECT * FROM users WHERE username = 'admin'";
$result = mysqli_query($conn, $sql_check_admin);

if (mysqli_num_rows($result) == 0) {
    // Buat user admin default jika belum ada
    $admin_username = 'admin';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin_nama = 'Administrator';
    $admin_role = 'admin';
    
    $sql_create_admin = "INSERT INTO users (username, password, nama, role) 
                         VALUES ('$admin_username', '$admin_password', '$admin_nama', '$admin_role')";
    
    if (mysqli_query($conn, $sql_create_admin)) {
        echo "User admin default berhasil dibuat<br>";
    } else {
        die("Error membuat user admin: " . mysqli_error($conn));
    }
}

// Cek apakah user kasir sudah ada
$sql_check_kasir = "SELECT * FROM users WHERE username = 'kasir'";
$result = mysqli_query($conn, $sql_check_kasir);

if (mysqli_num_rows($result) == 0) {
    // Buat user kasir default jika belum ada
    $kasir_username = 'kasir';
    $kasir_password = password_hash('kasir123', PASSWORD_DEFAULT);
    $kasir_nama = 'Kasir Toko';
    $kasir_role = 'kasir';
    
    $sql_create_kasir = "INSERT INTO users (username, password, nama, role)
    VALUES ('$kasir_username', '$kasir_password', '$kasir_nama', '$kasir_role')";
    
    if (mysqli_query($conn, $sql_create_kasir)) {
        echo "User kasir default berhasil dibuat<br>";
    } else {
        die("Error membuat user kasir: " . mysqli_error($conn));
    }
}

mysqli_close($conn);

echo "<br>Setup database berhasil!<br>";
echo "<a href='index.php'>Kembali ke halaman utama</a>";

// Tambahkan script refresh otomatis ke halaman utama setelah 3 detik
echo "<script>
    setTimeout(function() {
        window.location.href = 'index.php';
    }, 3000);
</script>";
?> 