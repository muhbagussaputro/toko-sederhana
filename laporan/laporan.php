<?php
// Include koneksi database
require_once '../db.php';

// Cek apakah user adalah admin
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Inisialisasi variabel filter dengan sanitasi input
$tipe_laporan = isset($_GET['tipe']) ? filter_var($_GET['tipe'], FILTER_SANITIZE_SPECIAL_CHARS) : 'harian';
$tanggal = isset($_GET['tanggal']) ? filter_var($_GET['tanggal'], FILTER_SANITIZE_SPECIAL_CHARS) : date('Y-m-d');
$bulan = isset($_GET['bulan']) ? filter_var($_GET['bulan'], FILTER_SANITIZE_SPECIAL_CHARS) : date('Y-m');
$tahun = isset($_GET['tahun']) ? filter_var($_GET['tahun'], FILTER_SANITIZE_SPECIAL_CHARS) : date('Y');

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    $tanggal = date('Y-m-d');
}
if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
    $bulan = date('Y-m');
}
if (!preg_match('/^\d{4}$/', $tahun)) {
    $tahun = date('Y');
}

// Set judul dan filter SQL berdasarkan tipe laporan - Menggunakan prepared statement
$judul_laporan = '';
$sql_filter = '';

if ($tipe_laporan == 'harian') {
    $judul_laporan = 'Laporan Penjualan Harian: ' . date('d/m/Y', strtotime($tanggal));
    $sql_filter = "WHERE DATE(t.tanggal) = ?";
    $sql_params = [$tanggal];
} elseif ($tipe_laporan == 'bulanan') {
    $judul_laporan = 'Laporan Penjualan Bulanan: ' . date('F Y', strtotime($bulan));
    $sql_filter = "WHERE DATE_FORMAT(t.tanggal, '%Y-%m') = ?";
    $sql_params = [$bulan];
} elseif ($tipe_laporan == 'tahunan') {
    $judul_laporan = 'Laporan Penjualan Tahunan: ' . $tahun;
    $sql_filter = "WHERE YEAR(t.tanggal) = ?";
    $sql_params = [$tahun];
} else {
    // Default ke harian jika tipe tidak valid
    $tipe_laporan = 'harian';
    $judul_laporan = 'Laporan Penjualan Harian: ' . date('d/m/Y', strtotime($tanggal));
    $sql_filter = "WHERE DATE(t.tanggal) = ?";
    $sql_params = [$tanggal];
}

// Query untuk mendapatkan data transaksi - Menggunakan prepared statement
try {
    $query = "SELECT t.id, t.tanggal, t.total, u.nama as user,
            COUNT(td.id) as jumlah_item
            FROM transaksi t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN transaksi_detail td ON td.transaksi_id = t.id
            $sql_filter
            GROUP BY t.id
            ORDER BY t.tanggal DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($sql_params)), ...$sql_params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        throw new Exception("Error dalam query transaksi: " . mysqli_error($conn));
    }

    // Hitung total penjualan dan rata-rata - Menggunakan prepared statement
    $query_total = "SELECT COUNT(DISTINCT t.id) as total_transaksi, 
                   SUM(t.total) as total_penjualan,
                   AVG(t.total) as rata_rata_penjualan
                   FROM transaksi t
                   $sql_filter";
    
    $stmt_total = mysqli_prepare($conn, $query_total);
    mysqli_stmt_bind_param($stmt_total, str_repeat('s', count($sql_params)), ...$sql_params);
    mysqli_stmt_execute($stmt_total);
    $result_total = mysqli_stmt_get_result($stmt_total);
    
    if (!$result_total) {
        throw new Exception("Error dalam query total: " . mysqli_error($conn));
    }
    
    $row_total = mysqli_fetch_assoc($result_total);
} catch (Exception $e) {
    // Log error
    error_log("[" . date('Y-m-d H:i:s') . "] Laporan Error: " . $e->getMessage());
    $error_message = "Terjadi kesalahan saat memuat data. Silakan coba lagi atau hubungi administrator.";
}

// Include header
$title = "Laporan";
include '../header.php';
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="flex flex-col md:flex-row justify-between items-center p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800 mb-3 md:mb-0"><?php echo htmlspecialchars($judul_laporan); ?></h1>
        <?php if ($tipe_laporan != 'harian'): ?>
            <a href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']; ?>/laporan/export_excel.php?tipe=<?php echo urlencode($tipe_laporan); ?>&<?php echo $tipe_laporan == 'bulanan' ? 'bulan=' . urlencode($bulan) : 'tahun=' . urlencode($tahun); ?>" 
               class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 w-full md:w-auto text-center">
                <i class="fas fa-file-excel mr-2"></i>Export Excel
            </a>
        <?php else: ?>
            <a href="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']; ?>/laporan/export_excel.php?tipe=harian&tanggal=<?php echo urlencode($tanggal); ?>" 
               class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 w-full md:w-auto text-center">
                <i class="fas fa-file-excel mr-2"></i>Export Excel
            </a>
        <?php endif; ?>
    </div>
    
    <div class="p-4">
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filter Form -->
        <form action="" method="get" class="mb-8" id="filterForm">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="tipe" class="block text-gray-700 text-sm font-medium mb-2">Tipe Laporan:</label>
                    <select id="tipe" name="tipe" onchange="changeLaporanType(this.value); submitForm();"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="harian" <?php echo $tipe_laporan == 'harian' ? 'selected' : ''; ?>>Harian</option>
                        <option value="bulanan" <?php echo $tipe_laporan == 'bulanan' ? 'selected' : ''; ?>>Bulanan</option>
                        <option value="tahunan" <?php echo $tipe_laporan == 'tahunan' ? 'selected' : ''; ?>>Tahunan</option>
                    </select>
                </div>
                
                <div id="filter-harian" class="<?php echo $tipe_laporan != 'harian' ? 'hidden' : ''; ?>">
                    <label for="tanggal" class="block text-gray-700 text-sm font-medium mb-2">Tanggal:</label>
                    <input type="date" id="tanggal" name="tanggal" value="<?php echo htmlspecialchars($tanggal); ?>" onchange="submitForm();"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div id="filter-bulanan" class="<?php echo $tipe_laporan != 'bulanan' ? 'hidden' : ''; ?>">
                    <label for="bulan" class="block text-gray-700 text-sm font-medium mb-2">Bulan:</label>
                    <input type="month" id="bulan" name="bulan" value="<?php echo htmlspecialchars($bulan); ?>" onchange="submitForm();"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div id="filter-tahunan" class="<?php echo $tipe_laporan != 'tahunan' ? 'hidden' : ''; ?>">
                    <label for="tahun" class="block text-gray-700 text-sm font-medium mb-2">Tahun:</label>
                    <select id="tahun" name="tahun" onchange="submitForm();"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="bg-info hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 w-full">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Statistik -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 flex flex-col items-center justify-center hover:shadow-lg transition duration-300">
                <div class="text-gray-600 mb-2">Total Transaksi</div>
                <div class="text-3xl font-bold text-primary"><?php echo number_format($row_total['total_transaksi'] ?? 0, 0, ',', '.'); ?></div>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kasir</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah Item</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (isset($result) && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">TRX-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap"><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap"><?php echo htmlspecialchars($row['user']); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-center"><?php echo $row['jumlah_item']; ?></td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a href="../transaksi/detail.php?id=<?php echo $row['id']; ?>" 
                                       class="bg-info hover:bg-blue-600 text-white px-3 py-1 rounded-md text-sm transition duration-300 inline-block">
                                       <i class="fas fa-eye mr-1"></i>Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-center text-gray-500">Tidak ada data transaksi</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Loading Overlay untuk indikator loading saat filter -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 hidden">
    <div class="bg-white p-5 rounded-lg shadow-lg flex flex-col items-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mb-3"></div>
        <p>Memuat data...</p>
    </div>
</div>

<script>
function changeLaporanType(type) {
    document.getElementById('filter-harian').classList.add('hidden');
    document.getElementById('filter-bulanan').classList.add('hidden');
    document.getElementById('filter-tahunan').classList.add('hidden');
    
    document.getElementById('filter-' + type).classList.remove('hidden');
}

function submitForm() {
    // Tampilkan loading overlay
    const overlay = document.getElementById('loadingOverlay');
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    
    // Delay sedikit untuk efek visual
    setTimeout(function() {
        document.getElementById('filterForm').submit();
    }, 300);
}

// Jika ada error, scroll ke error message
document.addEventListener('DOMContentLoaded', function() {
    const errorMessage = document.querySelector('.bg-red-100');
    if (errorMessage) {
        errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
</script>

<?php
// Include footer
include '../footer.php';
?>