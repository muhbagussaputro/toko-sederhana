<?php
session_start();

// Include koneksi database
require_once __DIR__ . '/../../db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: /toko-sederhana/login.php");
    exit();
}

// Inisialisasi variabel
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = "";

// Ambil data transaksi
if ($id > 0) {
    try {
        // Query untuk mendapatkan data transaksi
        $stmt = $pdo->prepare("SELECT t.id, t.tanggal, t.total, t.created_at, u.nama as user, u.role 
                              FROM transaksi t 
                              JOIN users u ON t.user_id = u.id 
                              WHERE t.id = ?");
        $stmt->execute([$id]);
        $transaksi = $stmt->fetch();
        
        if (!$transaksi) {
            $error = "Transaksi tidak ditemukan!";
        } else {
            // Query untuk mendapatkan detail transaksi
            $stmt = $pdo->prepare("SELECT td.id, td.jumlah, td.harga, td.subtotal, b.kode, b.nama, b.id as barang_id 
                                 FROM transaksi_detail td 
                                 JOIN barang b ON td.barang_id = b.id 
                                 WHERE td.transaksi_id = ?
                                 ORDER BY td.id ASC");
            $stmt->execute([$id]);
            $detail_items = $stmt->fetchAll();
            
            // Hitung statistik pesanan
            $total_items = 0;
            $total_jenis = count($detail_items);
            $items_terbanyak = '';
            $jumlah_terbanyak = 0;
            
            foreach ($detail_items as $item) {
                $total_items += $item['jumlah'];
                
                if ($item['jumlah'] > $jumlah_terbanyak) {
                    $jumlah_terbanyak = $item['jumlah'];
                    $items_terbanyak = $item['nama'];
                }
            }
        }
    } catch (PDOException $e) {
        handleError("Error pada pengambilan data transaksi: " . $e->getMessage());
        $error = "Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.";
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
include __DIR__ . '/../../header.php';
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
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
                        <span class="font-medium"><?php echo htmlspecialchars($transaksi['user']); ?> 
                            <span class="inline-block ml-1 px-2 py-0.5 text-xs rounded-full <?php echo $transaksi['role'] == 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo htmlspecialchars($transaksi['role']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Item:</span>
                        <span class="font-medium"><?php echo $total_items; ?> item (<?php echo $total_jenis; ?> jenis)</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Item Terbanyak:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($items_terbanyak); ?> (<?php echo $jumlah_terbanyak; ?>)</span>
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
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">No</th>
                            <th class="py-2 px-4 border-b">Kode</th>
                            <th class="py-2 px-4 border-b">Nama Barang</th>
                            <th class="py-2 px-4 border-b text-right">Harga</th>
                            <th class="py-2 px-4 border-b text-right">Jumlah</th>
                            <th class="py-2 px-4 border-b text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($detail_items)): ?>
                            <?php $no = 1; foreach ($detail_items as $item): ?>
                                <tr>
                                    <td class="py-2 px-4 border-b"><?php echo $no++; ?></td>
                                    <td class="py-2 px-4 border-b font-medium"><?php echo htmlspecialchars($item['kode']); ?></td>
                                    <td class="py-2 px-4 border-b">
                                        <a href="../barang/edit.php?id=<?php echo $item['barang_id']; ?>" class="text-blue-600 hover:text-blue-800">
                                            <?php echo htmlspecialchars($item['nama']); ?>
                                        </a>
                                    </td>
                                    <td class="py-2 px-4 border-b text-right">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                    <td class="py-2 px-4 border-b text-right"><?php echo $item['jumlah']; ?></td>
                                    <td class="py-2 px-4 border-b text-right font-semibold">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="4" class="py-2 px-4 border-b text-right font-semibold">Total:</td>
                                <td class="py-2 px-4 border-b text-right font-semibold"><?php echo $total_items; ?></td>
                                <td class="py-2 px-4 border-b text-right font-bold text-primary">Rp <?php echo number_format($transaksi['total'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-2 px-4 border-b text-center text-gray-500">Tidak ada detail transaksi</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../footer.php'; ?> 