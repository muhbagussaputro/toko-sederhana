<?php
// Include koneksi database
require_once '../db.php';

// Inisialisasi variabel
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = "";

// Ambil data transaksi
if ($id > 0) {
    // Query untuk mendapatkan data transaksi
    $query_transaksi = "SELECT t.id, t.tanggal, t.total, u.nama as user 
                       FROM transaksi t 
                       JOIN users u ON t.user_id = u.id 
                       WHERE t.id = ?";
                       
    $stmt_transaksi = mysqli_prepare($conn, $query_transaksi);
    mysqli_stmt_bind_param($stmt_transaksi, "i", $id);
    mysqli_stmt_execute($stmt_transaksi);
    $result_transaksi = mysqli_stmt_get_result($stmt_transaksi);
    
    if (mysqli_num_rows($result_transaksi) == 0) {
        $error = "Transaksi tidak ditemukan!";
    } else {
        $transaksi = mysqli_fetch_assoc($result_transaksi);
        
        // Query untuk mendapatkan detail transaksi
        $query_detail = "SELECT td.jumlah, td.harga, td.subtotal, b.kode, b.nama 
                        FROM transaksi_detail td 
                        JOIN barang b ON td.barang_id = b.id 
                        WHERE td.transaksi_id = ?";
                        
        $stmt_detail = mysqli_prepare($conn, $query_detail);
        mysqli_stmt_bind_param($stmt_detail, "i", $id);
        mysqli_stmt_execute($stmt_detail);
        $result_detail = mysqli_stmt_get_result($stmt_detail);
    }
} else {
    $error = "ID transaksi tidak valid!";
}

// Ambil pesan sukses dari session jika ada
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
} else {
    $success = "";
}

// Include header
$title = "Detail Transaksi";
include '../header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Detail Transaksi #<?php echo $id; ?></h1>
        <a href="list.php" class="btn btn-warning">Kembali</a>
    </div>
    
    <div class="card-content">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>
            <div class="mb-20">
                <table style="width: 50%;">
                    <tr>
                        <td><strong>ID Transaksi</strong></td>
                        <td>: <?php echo $transaksi['id']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tanggal</strong></td>
                        <td>: <?php echo date('d/m/Y H:i:s', strtotime($transaksi['tanggal'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Kasir</strong></td>
                        <td>: <?php echo $transaksi['user']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total</strong></td>
                        <td>: Rp <?php echo number_format($transaksi['total'], 0, ',', '.'); ?></td>
                    </tr>
                </table>
            </div>
            
            <h3>Detail Barang</h3>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result_detail) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result_detail)): ?>
                                <tr>
                                    <td><?php echo $row['kode']; ?></td>
                                    <td><?php echo $row['nama']; ?></td>
                                    <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                    <td><?php echo $row['jumlah']; ?></td>
                                    <td>Rp <?php echo number_format($row['subtotal'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                <td><strong>Rp <?php echo number_format($transaksi['total'], 0, ',', '.'); ?></strong></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada detail transaksi</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Tombol Cetak -->
            <div class="mt-20">
                <button onclick="window.print()" class="btn btn-info">Cetak</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include '../footer.php';
?> 