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
} elseif ($tipe_laporan == 'bulanan') {
    $judul_laporan = 'Laporan Penjualan Bulanan: ' . date('F Y', strtotime($bulan));
    $sql_filter = "WHERE DATE_FORMAT(t.tanggal, '%Y-%m') = '$bulan'";
} elseif ($tipe_laporan == 'tahunan') {
    $judul_laporan = 'Laporan Penjualan Tahunan: ' . $tahun;
    $sql_filter = "WHERE YEAR(t.tanggal) = '$tahun'";
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

// Include header
$title = "Laporan";
include '../header.php';
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="flex justify-between items-center p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800"><?php echo $judul_laporan; ?></h1>
        <?php if ($tipe_laporan != 'harian'): ?>
            <a href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'https://') . $_SERVER['HTTP_HOST']; ?>/laporan/export_excel.php?tipe=<?php echo $tipe_laporan; ?>&<?php echo $tipe_laporan == 'bulanan' ? 'bulan=' . $bulan : 'tahun=' . $tahun; ?>" 
               class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">
                Export Excel
            </a>
        <?php else: ?>
            <a href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'https://') . $_SERVER['HTTP_HOST']; ?>/laporan/export_excel.php?tipe=harian&tanggal=<?php echo $tanggal; ?>" 
               class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">
                Export Excel
            </a>
        <?php endif; ?>
    </div>
    
    <div class="p-4">
        <!-- Filter Form -->
        <form action="" method="get" class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="tipe" class="block text-gray-700 text-sm font-medium mb-2">Tipe Laporan:</label>
                    <select id="tipe" name="tipe" onchange="changeLaporanType(this.value)"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="harian" <?php echo $tipe_laporan == 'harian' ? 'selected' : ''; ?>>Harian</option>
                        <option value="bulanan" <?php echo $tipe_laporan == 'bulanan' ? 'selected' : ''; ?>>Bulanan</option>
                        <option value="tahunan" <?php echo $tipe_laporan == 'tahunan' ? 'selected' : ''; ?>>Tahunan</option>
                    </select>
                </div>
                
                <div id="filter-harian" class="<?php echo $tipe_laporan != 'harian' ? 'hidden' : ''; ?>">
                    <label for="tanggal" class="block text-gray-700 text-sm font-medium mb-2">Tanggal:</label>
                    <input type="date" id="tanggal" name="tanggal" value="<?php echo $tanggal; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div id="filter-bulanan" class="<?php echo $tipe_laporan != 'bulanan' ? 'hidden' : ''; ?>">
                    <label for="bulan" class="block text-gray-700 text-sm font-medium mb-2">Bulan:</label>
                    <input type="month" id="bulan" name="bulan" value="<?php echo $bulan; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div id="filter-tahunan" class="<?php echo $tipe_laporan != 'tahunan' ? 'hidden' : ''; ?>">
                    <label for="tahun" class="block text-gray-700 text-sm font-medium mb-2">Tahun:</label>
                    <select id="tahun" name="tahun"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="bg-info hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 w-full">Filter</button>
                </div>
            </div>
        </form>
        
        <!-- Statistik -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-center justify-center hover:shadow-lg transition duration-300">
                <div class="text-gray-600 mb-2">Total Transaksi</div>
                <div class="text-3xl font-bold text-primary"><?php echo number_format($row_total['total_transaksi'], 0, ',', '.'); ?></div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-center justify-center hover:shadow-lg transition duration-300">
                <div class="text-gray-600 mb-2">Total Penjualan</div>
                <div class="text-3xl font-bold text-primary">Rp <?php echo number_format($row_total['total_penjualan'] ?? 0, 0, ',', '.'); ?></div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-center justify-center hover:shadow-lg transition duration-300">
                <div class="text-gray-600 mb-2">Rata-rata Penjualan</div>
                <div class="text-3xl font-bold text-primary">Rp <?php echo number_format($row_total['rata_rata_penjualan'] ?? 0, 0, ',', '.'); ?></div>
            </div>
        </div>
        
        <!-- Tabel Transaksi -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kasir</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['user']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['jumlah_item']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="../transaksi/detail.php?id=<?php echo $row['id']; ?>" 
                                       class="bg-info hover:bg-blue-600 text-white px-3 py-1 rounded-md text-sm transition duration-300 inline-block">Detail</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada data transaksi</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function changeLaporanType(type) {
    document.getElementById('filter-harian').classList.add('hidden');
    document.getElementById('filter-bulanan').classList.add('hidden');
    document.getElementById('filter-tahunan').classList.add('hidden');
    
    document.getElementById('filter-' + type).classList.remove('hidden');
}
</script>

<?php
// Include footer
include '../footer.php';
?>