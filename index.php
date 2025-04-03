<?php
session_start();

// Auto check database, buat otomatis jika belum ada
include_once 'auto_check_db.php';

require_once 'db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include header
include 'header.php';

try {
    // 1. Jumlah barang
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang");
    $total_barang = $stmt->fetch()['total'];

    // 2. Jumlah transaksi hari ini
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM transaksi WHERE DATE(tanggal) = ?");
    $stmt->execute([$today]);
    $transaksi_hari_ini = $stmt->fetch()['total'];

    // 3. Total penjualan hari ini
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as total_penjualan FROM transaksi WHERE DATE(tanggal) = ?");
    $stmt->execute([$today]);
    $total_penjualan = $stmt->fetch()['total_penjualan'];

    // 4. Transaksi terbaru
    $stmt = $pdo->query("SELECT t.id, t.tanggal, t.total, u.username as user
                        FROM transaksi t
                        JOIN users u ON t.user_id = u.id
                        ORDER BY t.tanggal DESC
                        LIMIT 5");
    $transaksi_terbaru = $stmt->fetchAll();

    // 5. Stok barang yang hampir habis
    $stmt = $pdo->query("SELECT kode, nama, stok FROM barang WHERE stok < 10 ORDER BY stok ASC LIMIT 5");
    $stok_menipis = $stmt->fetchAll();

} catch (PDOException $e) {
    handleError("Error pada dashboard: " . $e->getMessage());
    $error = "Terjadi kesalahan saat memuat data dashboard. Silakan coba beberapa saat lagi.";
}
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6 pb-2 border-b border-gray-200">Dashboard</h1>

<?php if (isset($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-center justify-center hover:shadow-lg transition duration-300">
        <div class="text-3xl font-bold text-primary mb-2"><?php echo number_format($total_barang); ?></div>
        <div class="text-gray-600">Total Barang</div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-center justify-center hover:shadow-lg transition duration-300">
        <div class="text-3xl font-bold text-primary mb-2"><?php echo number_format($transaksi_hari_ini); ?></div>
        <div class="text-gray-600">Transaksi Hari Ini</div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-center justify-center hover:shadow-lg transition duration-300">
        <div class="text-3xl font-bold text-primary mb-2">Rp <?php echo number_format($total_penjualan, 0, ',', '.'); ?></div>
        <div class="text-gray-600">Total Penjualan Hari Ini</div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Transaksi Terbaru -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Transaksi Terbaru</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 text-left">Tanggal</th>
                        <th class="px-4 py-2 text-left">Kasir</th>
                        <th class="px-4 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transaksi_terbaru as $transaksi): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2"><?php echo date('d/m/Y H:i', strtotime($transaksi['tanggal'])); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($transaksi['user']); ?></td>
                        <td class="px-4 py-2 text-right">Rp <?php echo number_format($transaksi['total'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stok Menipis -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Stok Menipis</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 text-left">Kode</th>
                        <th class="px-4 py-2 text-left">Nama Barang</th>
                        <th class="px-4 py-2 text-right">Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stok_menipis as $barang): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2"><?php echo htmlspecialchars($barang['kode']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($barang['nama']); ?></td>
                        <td class="px-4 py-2 text-right"><?php echo number_format($barang['stok']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?> 