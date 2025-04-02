<?php
// Include koneksi database
require_once '../db.php';

// Cek apakah user adalah admin
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Inisialisasi variabel filter
$tipe_laporan = isset($_GET['tipe']) ? $_GET['tipe'] : 'harian';
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Set judul dan filter SQL berdasarkan tipe laporan
$judul_laporan = '';
$sql_filter = '';

if ($tipe_laporan == 'harian') {
    $judul_laporan = 'Laporan Penjualan Harian: ' . date('d/m/Y', strtotime($tanggal));
    $sql_filter = "WHERE DATE(t.tanggal) = '$tanggal'";
    $filename = 'Laporan_Harian_' . date('Y-m-d', strtotime($tanggal));
} elseif ($tipe_laporan == 'bulanan') {
    $judul_laporan = 'Laporan Penjualan Bulanan: ' . date('F Y', strtotime($bulan));
    $sql_filter = "WHERE DATE_FORMAT(t.tanggal, '%Y-%m') = '$bulan'";
    $filename = 'Laporan_Bulanan_' . date('Y-m', strtotime($bulan));
} elseif ($tipe_laporan == 'tahunan') {
    $judul_laporan = 'Laporan Penjualan Tahunan: ' . $tahun;
    $sql_filter = "WHERE YEAR(t.tanggal) = '$tahun'";
    $filename = 'Laporan_Tahunan_' . $tahun;
}

// Query untuk mendapatkan data transaksi
$query = "SELECT t.id, t.tanggal, t.total, u.nama as user,
          (SELECT COUNT(*) FROM transaksi_detail WHERE transaksi_id = t.id) as jumlah_item
          FROM transaksi t
          JOIN users u ON t.user_id = u.id
          $sql_filter
          ORDER BY t.tanggal DESC";
$result = mysqli_query($conn, $query);

// Hitung total penjualan dan rata-rata
$query_total = "SELECT COUNT(*) as total_transaksi, 
               SUM(total) as total_penjualan,
               AVG(total) as rata_rata_penjualan
               FROM transaksi t
               $sql_filter";
$result_total = mysqli_query($conn, $query_total);
$row_total = mysqli_fetch_assoc($result_total);

// Membuat output Excel sederhana menggunakan HTML
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="'.$filename.'.xls"');
?>

<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $judul_laporan; ?></title>
    <style>
        table { border-collapse: collapse; }
        table, th, td { border: 1px solid black; padding: 5px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1><?php echo $judul_laporan; ?></h1>
    
    <table>
        <tr>
            <th>Total Transaksi</th>
            <td><?php echo $row_total['total_transaksi']; ?></td>
        </tr>
        <tr>
            <th>Total Penjualan</th>
            <td>Rp <?php echo number_format($row_total['total_penjualan'] ?? 0, 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <th>Rata-rata Penjualan</th>
            <td>Rp <?php echo number_format($row_total['rata_rata_penjualan'] ?? 0, 0, ',', '.'); ?></td>
        </tr>
    </table>
    
    <br>
    
    <h2>Detail Transaksi</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tanggal</th>
                <th>Kasir</th>
                <th>Jumlah Item</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                        <td><?php echo $row['user']; ?></td>
                        <td><?php echo $row['jumlah_item']; ?></td>
                        <td>Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Tidak ada data transaksi</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <p>Laporan ini dihasilkan pada <?php echo date('d/m/Y H:i:s'); ?> oleh <?php echo $_SESSION['nama']; ?></p>
</body>
</html>

<?php
// Tutup koneksi database
mysqli_close($conn);
?> 