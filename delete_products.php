<?php
// Set informasi koneksi database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'db_toko';

// Buat koneksi ke database
$conn = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

// Set execution time to unlimited
set_time_limit(0);

echo "<h2>Menghapus Barang dari Database</h2>";

// Cek apakah ada transaksi yang terkait dengan barang
$query_check = "SELECT COUNT(*) as total FROM transaksi_detail";
$result_check = mysqli_query($conn, $query_check);
$row = mysqli_fetch_assoc($result_check);

if ($row['total'] > 0) {
    echo "<div style='color: red; margin-bottom: 15px;'>PERINGATAN: Terdapat " . $row['total'] . " detail transaksi yang terkait dengan barang.</div>";
    echo "<div style='margin-bottom: 15px;'>Menghapus data transaksi terlebih dahulu...</div>";
    
    // Matikan foreign key check
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    
    // Hapus data transaksi detail
    $query_delete_details = "TRUNCATE TABLE transaksi_detail";
    if (mysqli_query($conn, $query_delete_details)) {
        echo "Berhasil menghapus data detail transaksi.<br>";
    } else {
        echo "Error menghapus detail transaksi: " . mysqli_error($conn) . "<br>";
    }
    
    // Hapus data transaksi
    $query_delete_trans = "TRUNCATE TABLE transaksi";
    if (mysqli_query($conn, $query_delete_trans)) {
        echo "Berhasil menghapus data transaksi.<br>";
    } else {
        echo "Error menghapus transaksi: " . mysqli_error($conn) . "<br>";
    }
    
    // Aktifkan kembali foreign key check
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
}

// Dapatkan jumlah barang sebelum dihapus
$query_count = "SELECT COUNT(*) as total FROM barang";
$result_count = mysqli_query($conn, $query_count);
$row = mysqli_fetch_assoc($result_count);
$total_before = $row['total'];

echo "Jumlah barang sebelum dihapus: " . $total_before . "<br>";

// Simpan 100 barang pertama
echo "Menyimpan 100 barang terbaru...<br>";
$query_keep = "DELETE FROM barang WHERE id NOT IN (SELECT id FROM (SELECT id FROM barang ORDER BY id DESC LIMIT 100) as temp)";

if (mysqli_query($conn, $query_keep)) {
    $deleted_count = mysqli_affected_rows($conn);
    echo "Berhasil menghapus " . $deleted_count . " barang dari database.<br>";
} else {
    echo "Error menghapus barang: " . mysqli_error($conn) . "<br>";
}

// Dapatkan jumlah barang sesudah dihapus
$query_count = "SELECT COUNT(*) as total FROM barang";
$result_count = mysqli_query($conn, $query_count);
$row = mysqli_fetch_assoc($result_count);
$total_after = $row['total'];

echo "Jumlah barang setelah dihapus: " . $total_after . "<br>";
echo "Total barang yang dihapus: " . ($total_before - $total_after) . "<br>";

// Tutup koneksi
mysqli_close($conn);

echo "<br>Proses selesai!<br>";
echo "<a href='index.php'>Kembali ke halaman utama</a>";
?> 