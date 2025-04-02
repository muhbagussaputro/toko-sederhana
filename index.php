<?php
// Include koneksi database
require_once 'db.php';

// Include header
include 'header.php';

// Ambil statistik untuk dashboard
// 1. Jumlah barang
$query_barang = "SELECT COUNT(*) as total FROM barang";
$result_barang = mysqli_query($conn, $query_barang);
$total_barang = mysqli_fetch_assoc($result_barang)['total'];

// 2. Jumlah transaksi hari ini
$today = date('Y-m-d');
$query_transaksi = "SELECT COUNT(*) as total FROM transaksi WHERE DATE(tanggal) = '$today'";
$result_transaksi = mysqli_query($conn, $query_transaksi);
$transaksi_hari_ini = mysqli_fetch_assoc($result_transaksi)['total'];

// 3. Total penjualan hari ini
$query_total = "SELECT SUM(total) as total_penjualan FROM transaksi WHERE DATE(tanggal) = '$today'";
$result_total = mysqli_query($conn, $query_total);
$total_penjualan = mysqli_fetch_assoc($result_total)['total_penjualan'];
if (!$total_penjualan) $total_penjualan = 0;

// 4. Transaksi terbaru
$query_terbaru = "SELECT t.id, t.tanggal, t.total, u.nama as user
                 FROM transaksi t
                 JOIN users u ON t.user_id = u.id
                 ORDER BY t.tanggal DESC
                 LIMIT 5";
$result_terbaru = mysqli_query($conn, $query_terbaru);

// 5. Stok barang yang hampir habis (kurang dari 10)
$query_stok = "SELECT kode, nama, stok FROM barang WHERE stok < 10 ORDER BY stok ASC LIMIT 5";
$result_stok = mysqli_query($conn, $query_stok);
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6 pb-2 border-b border-gray-200">Dashboard</h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-center justify-center hover:shadow-lg transition duration-300">
        <div class="text-3xl font-bold text-primary mb-2"><?php echo $total_barang; ?></div>
        <div class="text-gray-600">Total Barang</div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-center justify-center hover:shadow-lg transition duration-300">
        <div class="text-3xl font-bold text-primary mb-2"><?php echo $transaksi_hari_ini; ?></div>
        <div class="text-gray-600">Transaksi Hari Ini</div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-center justify-center hover:shadow-lg transition duration-300">
        <div class="text-3xl font-bold text-primary mb-2">Rp <?php echo number_format($total_penjualan, 0, ',', '.'); ?></div>
        <div class="text-gray-600">Penjualan Hari Ini</div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
    <div class="flex justify-between items-center p-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Transaksi Terbaru</h2>
        <a href="transaksi/list.php" class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">Lihat Semua</a>
    </div>
    <div class="p-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (mysqli_num_rows($result_terbaru) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result_terbaru)): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['user']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="transaksi/detail.php?id=<?php echo $row['id']; ?>" class="bg-info hover:bg-blue-600 text-white px-3 py-1 rounded-md text-sm transition duration-300">Detail</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada transaksi</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
    <div class="flex justify-between items-center p-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Stok Hampir Habis</h2>
        <a href="barang/list.php" class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">Lihat Semua Barang</a>
    </div>
    <div class="p-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (mysqli_num_rows($result_stok) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result_stok)): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['kode']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['nama']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="<?php echo $row['stok'] <= 5 ? 'text-red-600 font-medium' : 'text-yellow-600'; ?>">
                                    <?php echo $row['stok']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="barang/edit.php?id=<?php 
                                    // Ambil ID barang berdasarkan kode
                                    $kode_barang = $row['kode'];
                                    $query_id = "SELECT id FROM barang WHERE kode = '$kode_barang'";
                                    $result_id = mysqli_query($conn, $query_id);
                                    $id_barang = mysqli_fetch_assoc($result_id)['id'];
                                    echo $id_barang; 
                                ?>" class="bg-warning hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm transition duration-300">Edit</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Tidak ada barang dengan stok menipis</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include footer
include 'footer.php';
?> 