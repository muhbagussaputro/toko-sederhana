<?php
// Set zona waktu default ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Informasi koneksi database
$host = 'localhost';
$username = 'root';
$password = ''; // Password default Laragon biasanya kosong
$database = 'db_toko';

// Koneksi ke MySQL dengan database
$conn = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

echo "<h2>Update Database untuk Fitur Remember Me</h2>";

// Cek apakah kolom remember_token sudah ada
$check_column_query = "SHOW COLUMNS FROM users LIKE 'remember_token'";
$check_column_result = mysqli_query($conn, $check_column_query);

if (mysqli_num_rows($check_column_result) == 0) {
    // Kolom belum ada, tambahkan kolom remember_token
    $add_column_query = "ALTER TABLE users ADD COLUMN remember_token VARCHAR(64) NULL";
    
    if (mysqli_query($conn, $add_column_query)) {
        echo "Kolom remember_token berhasil ditambahkan ke tabel users<br>";
    } else {
        echo "Error menambahkan kolom remember_token: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Kolom remember_token sudah ada di tabel users<br>";
}

mysqli_close($conn);

echo "<br>Update database selesai!<br>";
echo "<a href='login.php'>Kembali ke halaman login</a>";

// Tambahkan script refresh otomatis ke halaman login setelah 3 detik
echo "<script>
    setTimeout(function() {
        window.location.href = 'login.php';
    }, 3000);
</script>";
?> 