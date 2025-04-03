<?php
session_start();

// Include koneksi database
require_once __DIR__ . '/../../db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

// Inisialisasi variabel filter
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-d', strtotime('-7 days'));
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');

try {
    // Query untuk mendapatkan daftar transaksi
    $query = "SELECT t.id, t.tanggal, t.total, u.username as user 
              FROM transaksi t 
              JOIN users u ON t.user_id = u.id 
              WHERE DATE(t.tanggal) BETWEEN ? AND ?
              ORDER BY t.tanggal DESC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $tgl_mulai, $tgl_selesai);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $transactions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
    
    // Hitung total pendapatan dalam periode
    $query_total = "SELECT SUM(total) as total_pendapatan 
                    FROM transaksi 
                    WHERE DATE(tanggal) BETWEEN ? AND ?";
    $stmt_total = mysqli_prepare($conn, $query_total);
    mysqli_stmt_bind_param($stmt_total, "ss", $tgl_mulai, $tgl_selesai);
    mysqli_stmt_execute($stmt_total);
    $result_total = mysqli_stmt_get_result($stmt_total);
    $row_total = mysqli_fetch_assoc($result_total);
    $total_pendapatan = $row_total['total_pendapatan'] ?? 0;
    
    // Hitung jumlah transaksi dalam periode
    $query_count = "SELECT COUNT(*) as jumlah_transaksi 
                    FROM transaksi 
                    WHERE DATE(tanggal) BETWEEN ? AND ?";
    $stmt_count = mysqli_prepare($conn, $query_count);
    mysqli_stmt_bind_param($stmt_count, "ss", $tgl_mulai, $tgl_selesai);
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $row_count = mysqli_fetch_assoc($result_count);
    $jumlah_transaksi = $row_count['jumlah_transaksi'] ?? 0;
} catch (Exception $e) {
    error_log("Error pada pengambilan data transaksi: " . $e->getMessage());
    $error = "Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.";
}

// Include header
include __DIR__ . '/../../header.php';
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Daftar Transaksi</h1>
        <div class="flex space-x-2">
            <a href="../export/export_transaksi.php?tgl_mulai=<?php echo urlencode($tgl_mulai); ?>&tgl_selesai=<?php echo urlencode($tgl_selesai); ?>" class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 inline-flex items-center">
                <i class="fas fa-file-excel mr-2"></i> Export Excel
            </a>
            <a href="tambah.php" class="bg-success hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 inline-flex items-center">
                <i class="fas fa-plus mr-2"></i> Tambah Transaksi Baru
            </a>
        </div>
    </div>
    
    <!-- Filter Form (Tanpa tombol) -->
    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="tgl_mulai" class="block text-gray-700 text-sm font-medium mb-2">Tanggal Mulai:</label>
                <input type="date" id="tgl_mulai" name="tgl_mulai" value="<?php echo htmlspecialchars($tgl_mulai); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label for="tgl_selesai" class="block text-gray-700 text-sm font-medium mb-2">Tanggal Selesai:</label>
                <input type="date" id="tgl_selesai" name="tgl_selesai" value="<?php echo htmlspecialchars($tgl_selesai); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
        </div>
        
        <!-- Ringkasan Informasi -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 p-3 bg-blue-50 rounded-lg">
            <div>
                <p class="text-gray-700"><span class="font-semibold">Periode:</span> <?php echo date('d/m/Y', strtotime($tgl_mulai)); ?> - <?php echo date('d/m/Y', strtotime($tgl_selesai)); ?></p>
                <p class="text-gray-700"><span class="font-semibold">Jumlah Transaksi:</span> <?php echo number_format($jumlah_transaksi); ?></p>
            </div>
            <div>
                <p class="text-gray-700"><span class="font-semibold">Total Pendapatan:</span> 
                    <span class="text-green-600 font-bold">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></span>
                </p>
                <p class="text-gray-700"><span class="font-semibold">Rata-rata per Transaksi:</span> 
                    <span class="text-blue-600">Rp <?php echo $jumlah_transaksi > 0 ? number_format($total_pendapatan / $jumlah_transaksi, 0, ',', '.') : 0; ?></span>
                </p>
            </div>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- Tabel Transaksi dengan DataTables -->
    <div class="overflow-x-auto">
        <table id="tabelTransaksi" class="min-w-full bg-white stripe hover">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">ID</th>
                    <th class="py-2 px-4 border-b">Tanggal</th>
                    <th class="py-2 px-4 border-b">Kasir</th>
                    <th class="py-2 px-4 border-b text-right">Total</th>
                    <th class="py-2 px-4 border-b">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($transactions)): ?>
                    <?php foreach ($transactions as $row): ?>
                        <tr>
                            <td class="py-2 px-4 border-b">TRX-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['user']); ?></td>
                            <td class="py-2 px-4 border-b text-right">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                            <td class="py-2 px-4 border-b">
                                <a href="detail.php?id=<?php echo $row['id']; ?>" class="bg-info hover:bg-blue-600 text-white px-3 py-1 rounded-md text-sm transition duration-300 inline-block">
                                    <i class="fas fa-eye mr-1"></i> Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Load DataTables CSS & JS dari CDN -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
<script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

<script>
$(document).ready(function() {
    // Inisialisasi DataTables
    $('#tabelTransaksi').DataTable({
        responsive: true,
        language: {
            search: "Pencarian:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Tidak ada data transaksi yang ditemukan",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada data transaksi",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        },
        columnDefs: [
            { orderable: false, targets: 4 } // Kolom aksi tidak bisa diurutkan
        ],
        order: [[1, 'desc']] // Urutkan berdasarkan tanggal (kolom 1) - descending
    });
    
    // Event listener untuk perubahan tanggal
    $('#tgl_mulai, #tgl_selesai').on('change', function() {
        const tgl_mulai = $('#tgl_mulai').val();
        const tgl_selesai = $('#tgl_selesai').val();
        
        // Validasi tanggal
        if (tgl_mulai > tgl_selesai) {
            alert('Tanggal mulai tidak boleh lebih besar dari tanggal selesai!');
            return;
        }
        
        // Redirect ke halaman yang sama dengan parameter filter baru
        window.location.href = `list.php?tgl_mulai=${tgl_mulai}&tgl_selesai=${tgl_selesai}`;
    });
});
</script>

<style>
/* Styling untuk DataTables */
.dataTables_wrapper {
    margin-top: 1rem;
    font-size: 0.875rem;
}
.dataTables_filter input {
    padding: 0.375rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    margin-left: 0.5rem;
}
.dataTables_length select {
    padding: 0.375rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    margin: 0 0.5rem;
}
.paginate_button {
    padding: 0.375rem 0.75rem;
    margin: 0 0.25rem;
    border-radius: 0.375rem;
    cursor: pointer;
}
.paginate_button.current {
    background-color: #3B82F6;
    color: white;
}
.dataTables_info {
    margin-top: 1rem;
    margin-bottom: 1rem;
}
</style>

<?php include __DIR__ . '/../../footer.php'; ?>