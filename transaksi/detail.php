<?php
// Include koneksi database
require_once '../db.php';

// Inisialisasi variabel
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = "";

// Ambil data transaksi
if ($id > 0) {
    // Query untuk mendapatkan data transaksi
    $query_transaksi = "SELECT t.id, t.tanggal, t.total, t.created_at, u.nama as user, u.role 
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
        $query_detail = "SELECT td.id, td.jumlah, td.harga, td.subtotal, b.kode, b.nama, b.id as barang_id 
                        FROM transaksi_detail td 
                        JOIN barang b ON td.barang_id = b.id 
                        WHERE td.transaksi_id = ?
                        ORDER BY td.id ASC";
                        
        $stmt_detail = mysqli_prepare($conn, $query_detail);
        mysqli_stmt_bind_param($stmt_detail, "i", $id);
        mysqli_stmt_execute($stmt_detail);
        $result_detail = mysqli_stmt_get_result($stmt_detail);
        
        // Hitung statistik pesanan
        $total_items = 0;
        $total_jenis = mysqli_num_rows($result_detail);
        $items_terbanyak = '';
        $jumlah_terbanyak = 0;
        
        // Simpan posisi awal result set agar bisa di-reset
        mysqli_data_seek($result_detail, 0);
        
        // Hitung total items dan cari item terbanyak
        while ($row = mysqli_fetch_assoc($result_detail)) {
            $total_items += $row['jumlah'];
            
            if ($row['jumlah'] > $jumlah_terbanyak) {
                $jumlah_terbanyak = $row['jumlah'];
                $items_terbanyak = $row['nama'];
            }
        }
        
        // Reset result set ke posisi awal
        mysqli_data_seek($result_detail, 0);
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

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="flex justify-between items-center p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800">
            Detail Transaksi <span class="text-primary">#<?php echo str_pad($id, 4, '0', STR_PAD_LEFT); ?></span>
        </h1>
        <div class="flex space-x-2">
            <button onclick="window.print()" class="bg-info hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">
                <i class="fas fa-print mr-2"></i> Cetak
            </button>
            <a href="list.php" class="bg-warning hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </div>
    
    <div class="p-6">
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php elseif (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php else: ?>
            <!-- Info Transaksi -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gray-50 rounded-lg p-5 border border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Informasi Transaksi</h2>
                    <div class="grid grid-cols-1 gap-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">ID Transaksi:</span>
                            <span class="font-medium">TRX-<?php echo str_pad($transaksi['id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tanggal & Waktu:</span>
                            <span class="font-medium"><?php echo date('d/m/Y H:i:s', strtotime($transaksi['tanggal'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Kasir:</span>
                            <span class="font-medium"><?php echo $transaksi['user']; ?> 
                                <span class="inline-block ml-1 px-2 py-0.5 text-xs rounded-full <?php echo $transaksi['role'] == 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo $transaksi['role']; ?>
                                </span>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Item:</span>
                            <span class="font-medium"><?php echo $total_items; ?> item (<?php echo $total_jenis; ?> jenis)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Item Terbanyak:</span>
                            <span class="font-medium"><?php echo $items_terbanyak; ?> (<?php echo $jumlah_terbanyak; ?>)</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-blue-50 rounded-lg p-5 border border-blue-100">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-blue-200">Ringkasan Pembayaran</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-medium">Rp <?php echo number_format($transaksi['total'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Pajak/PPN:</span>
                            <span class="font-medium">Rp 0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Diskon:</span>
                            <span class="font-medium">Rp 0</span>
                        </div>
                        <div class="pt-2 mt-2 border-t border-blue-200">
                            <div class="flex justify-between">
                                <span class="text-gray-800 font-semibold">Total Pembayaran:</span>
                                <span class="font-bold text-xl text-primary">Rp <?php echo number_format($transaksi['total'], 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detail Barang -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Detail Barang</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (mysqli_num_rows($result_detail) > 0): ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($result_detail)): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 whitespace-nowrap"><?php echo $no++; ?></td>
                                        <td class="px-6 py-3 whitespace-nowrap font-medium"><?php echo $row['kode']; ?></td>
                                        <td class="px-6 py-3">
                                            <a href="../barang/edit.php?id=<?php echo $row['barang_id']; ?>" class="text-blue-600 hover:text-blue-800">
                                                <?php echo $row['nama']; ?>
                                            </a>
                                        </td>
                                        <td class="px-6 py-3 text-right whitespace-nowrap">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                        <td class="px-6 py-3 text-right whitespace-nowrap"><?php echo $row['jumlah']; ?></td>
                                        <td class="px-6 py-3 text-right whitespace-nowrap font-semibold">Rp <?php echo number_format($row['subtotal'], 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                <tr class="bg-gray-50">
                                    <td colspan="4" class="px-6 py-4 text-right font-semibold">Total:</td>
                                    <td class="px-6 py-4 text-right font-semibold"><?php echo $total_items; ?></td>
                                    <td class="px-6 py-4 text-right font-bold text-primary">Rp <?php echo number_format($transaksi['total'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada detail transaksi</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Catatan -->
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100 text-sm">
                <h3 class="font-semibold mb-2">Catatan:</h3>
                <ul class="list-disc pl-4 space-y-1">
                    <li>Transaksi ini dibuat pada <?php echo date('d/m/Y H:i:s', strtotime($transaksi['created_at'])); ?></li>
                    <li>Transaksi yang sudah selesai tidak dapat dibatalkan</li>
                    <li>Untuk pertanyaan atau bantuan, hubungi administrator</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .bg-white, .bg-white * {
        visibility: visible;
    }
    .bg-white {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .bg-info, .bg-warning, header, footer, .bg-yellow-50 {
        display: none;
    }
}
</style>

<?php
// Include footer
include '../footer.php';
?> 