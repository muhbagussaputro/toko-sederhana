<?php
// Include koneksi database
require_once __DIR__ . '/../../db.php';

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
$nama_toko = "Toko Sederhana";

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

// Query untuk mendapatkan data transaksi - Menggunakan PDO
try {
    $query = "SELECT t.id, t.tanggal, t.total, u.nama as user,
            COUNT(td.id) as jumlah_item
            FROM transaksi t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN transaksi_detail td ON td.transaksi_id = t.id
            $sql_filter
            GROUP BY t.id
            ORDER BY t.tanggal DESC";
    
    $stmt = $pdo->prepare($query);
    for ($i = 0; $i < count($sql_params); $i++) {
        $stmt->bindValue($i+1, $sql_params[$i]);
    }
    $stmt->execute();
    $result = $stmt->fetchAll();
    
    // Hitung total penjualan dan rata-rata
    $query_total = "SELECT COUNT(DISTINCT t.id) as total_transaksi, 
                   SUM(t.total) as total_penjualan,
                   AVG(t.total) as rata_rata_penjualan
                   FROM transaksi t
                   $sql_filter";
    
    $stmt_total = $pdo->prepare($query_total);
    for ($i = 0; $i < count($sql_params); $i++) {
        $stmt_total->bindValue($i+1, $sql_params[$i]);
    }
    $stmt_total->execute();
    $row_total = $stmt_total->fetch();
} catch (PDOException $e) {
    // Log error
    handleError("Error pada laporan: " . $e->getMessage());
    $error_message = "Terjadi kesalahan saat memuat data. Silakan coba lagi atau hubungi administrator.";
}

// Include header
$title = "Laporan";
include __DIR__ . '/../../header.php';
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="flex flex-col md:flex-row justify-between items-center p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800 mb-3 md:mb-0"><?php echo htmlspecialchars($judul_laporan); ?></h1>
        <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-2 w-full md:w-auto">
            <?php if ($tipe_laporan != 'harian'): ?>
                <a href="../export/export_transaksi.php?tipe=<?php echo urlencode($tipe_laporan); ?>&<?php echo $tipe_laporan == 'bulanan' ? 'bulan=' . urlencode($bulan) : 'tahun=' . urlencode($tahun); ?>" 
                   class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 w-full md:w-auto text-center">
                    <i class="fas fa-file-excel mr-2"></i>Export Excel
                </a>
            <?php else: ?>
                <a href="../export/export_transaksi.php?tipe=harian&tanggal=<?php echo urlencode($tanggal); ?>" 
                   class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 w-full md:w-auto text-center">
                    <i class="fas fa-file-excel mr-2"></i>Export Excel
                </a>
            <?php endif; ?>
            
            <div class="relative inline-block w-full md:w-auto">
                <button id="printOptions" class="bg-info hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 inline-flex items-center w-full md:w-auto justify-center">
                    <i class="fas fa-print mr-2"></i> Cetak <i class="fas fa-chevron-down ml-2"></i>
                </button>
                <div id="printDropdown" class="hidden absolute z-10 right-0 mt-2 bg-white rounded-md shadow-lg py-1 w-48">
                    <a href="#" onclick="printLaporan('receipt'); return false;" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-300">
                        <i class="fas fa-receipt mr-2"></i> Format Struk Kasir
                    </a>
                    <a href="#" onclick="printLaporan('full'); return false;" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-300">
                        <i class="fas fa-file-alt mr-2"></i> Format Laporan
                    </a>
                </div>
            </div>
        </div>
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
                    <?php 
                    if (!empty($result)): 
                        $no = 1;
                        foreach ($result as $row): 
                    ?>
                        <tr>
                            <td class="px-4 py-3"><?php echo $no++; ?></td>
                            <td class="px-4 py-3">TRX-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td class="px-4 py-3"><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($row['user']); ?></td>
                            <td class="px-4 py-3 text-center"><?php echo $row['jumlah_item']; ?></td>
                            <td class="px-4 py-3 text-right">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                            <td class="px-4 py-3">
                                <a href="../transaksi/detail.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                        <tr>
                            <td colspan="6" style="text-align:center">Tidak ada data transaksi</td>
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

<!-- Styling untuk print -->
<style>
/* Style dasar untuk semua jenis cetak */
@media print {
    header, footer, .no-print, form, button, a, .flex:not(.print-flex), .rounded-lg {
        display: none !important;
    }
    
    body {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Reset semua style container */
    .container, .bg-white, div:not(.struk-header):not(.struk-footer):not(.print-info):not(#print-template) {
        background: white !important;
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
        color: black !important;
    }
    
    /* Template selalu tampil */
    #print-template {
        display: block !important;
    }
}

/* Khusus mode struk kasir */
@media print {
    body.struk-mode {
        font-family: monospace !important;
        font-size: 10px !important;
        width: 80mm !important;
    }
    
    body.struk-mode .container, 
    body.struk-mode .bg-white, 
    body.struk-mode div:not(.struk-header):not(.struk-footer):not(.print-info):not(#print-template) {
        max-width: 80mm !important;
        width: 80mm !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    body.struk-mode .print-template-full {
        display: none !important;
    }
    
    body.struk-mode .print-template-struk {
        display: block !important;
    }
    
    /* Tampilan judul struk */
    body.struk-mode h1.struk-title {
        display: block !important;
        text-align: center;
        font-size: 14px !important;
        font-weight: bold;
        margin: 5px 0 !important;
        padding: 0 !important;
    }
    
    body.struk-mode .struk-header {
        display: block !important;
        text-align: center;
        border-bottom: 1px dashed black;
        padding-bottom: 10px !important;
        margin-bottom: 10px !important;
    }
    
    body.struk-mode .struk-footer {
        display: block !important;
        text-align: center;
        border-top: 1px dashed black;
        padding-top: 10px !important;
        margin-top: 10px !important;
    }
    
    body.struk-mode .print-table {
        width: 100% !important;
        border-collapse: collapse;
    }
    
    body.struk-mode .print-table td {
        padding: 3px 2px !important;
        line-height: 1.2 !important;
        border: none !important;
        font-size: 9px !important;
    }
    
    body.struk-mode .print-table tr.item-row td {
        border-bottom: 1px dotted #ccc !important;
    }
    
    body.struk-mode .print-info {
        display: block !important;
        margin: 5px 0 !important;
        font-size: 9px !important;
    }
    
    /* Warna tidak digunakan dalam struk, semua hitam-putih */
    body.struk-mode .bg-yellow-50, 
    body.struk-mode .bg-red-100, 
    body.struk-mode .bg-purple-100, 
    body.struk-mode .bg-green-100, 
    body.struk-mode .bg-gray-100 {
        background-color: white !important;
    }
    
    /* Remove coloring dan hanya display text */
    body.struk-mode span.inline-flex {
        background: none !important;
        padding: 0 !important;
        margin: 0 !important;
        color: black !important;
    }
    
    /* Page size */
    @page {
        size: 80mm 297mm;
        margin: 0;
        padding: 0;
    }
}

/* Khusus mode laporan penuh */
@media print {
    body.full-mode {
        font-family: Arial, sans-serif !important;
        font-size: 11px !important;
    }
    
    body.full-mode .print-template-struk {
        display: none !important;
    }
    
    body.full-mode .print-template-full {
        display: block !important;
    }
    
    body.full-mode .report-title {
        text-align: center;
        font-size: 18px !important;
        font-weight: bold;
        margin-bottom: 15px !important;
    }
    
    body.full-mode .report-header {
        margin-bottom: 20px !important;
    }
    
    body.full-mode .report-info {
        margin-bottom: 5px !important;
    }
    
    body.full-mode .report-table {
        width: 100% !important;
        border-collapse: collapse;
        margin-bottom: 20px !important;
    }
    
    body.full-mode .report-table th,
    body.full-mode .report-table td {
        border: 1px solid #ddd !important;
        padding: 8px !important;
        text-align: left;
    }
    
    body.full-mode .report-table th {
        background-color: #f2f2f2 !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    body.full-mode .report-table tr:nth-child(even) {
        background-color: #f9f9f9 !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    body.full-mode .report-footer {
        margin-top: 20px !important;
        font-size: 10px !important;
        text-align: center;
    }
    
    body.full-mode .report-signature {
        margin-top: 40px !important;
        display: flex !important;
        justify-content: flex-end !important;
    }
    
    body.full-mode .report-signature-box {
        text-align: center;
        margin-left: 20px !important;
    }
    
    body.full-mode .report-signature-line {
        margin-top: 50px !important;
        border-top: 1px solid #000 !important;
        width: 150px !important;
    }
    
    /* Page size for full report */
    @page {
        size: A4;
        margin: 1.5cm;
    }
}
</style>

<!-- Template untuk cetak -->
<div id="printTemplate" style="display: none;">
    <div class="print-mode-<?php echo $tipe_laporan == 'harian' ? 'receipt' : 'full'; ?>">
        <div class="header">
            <div class="store-name"><?php echo $nama_toko; ?></div>
            <div class="store-info">Jl. Contoh No. 123, Kota</div>
            <div class="store-info">Telp: (021) 1234567</div>
            <div class="report-title"><?php echo $judul_laporan; ?></div>
            <div class="report-period">
                <?php if ($tipe_laporan == 'harian'): ?>
                    Tanggal: <?php echo date('d/m/Y', strtotime($tanggal)); ?>
                <?php elseif ($tipe_laporan == 'bulanan'): ?>
                    Bulan: <?php echo date('F Y', strtotime($bulan)); ?>
                <?php else: ?>
                    Tahun: <?php echo $tahun; ?>
                <?php endif; ?>
            </div>
            <div class="report-period">Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></div>
            <div class="report-period">Dibuat oleh: <?php echo $_SESSION['nama']; ?></div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID Transaksi</th>
                    <th>Tanggal & Jam</th>
                    <th>Kasir</th>
                    <th>Jumlah Item</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($result)): 
                    $no = 1;
                    $grand_total = 0;
                    foreach ($result as $row): 
                        $grand_total += $row['total'];
                ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td>TRX-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                        <td><?php echo htmlspecialchars($row['user']); ?></td>
                        <td style="text-align:center"><?php echo $row['jumlah_item']; ?></td>
                        <td style="text-align:right">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                    </tr>
                <?php 
                    endforeach;
                else: 
                ?>
                    <tr>
                        <td colspan="6" style="text-align:center">Tidak ada data transaksi</td>
                    </tr>
                <?php endif; ?>
                
                <?php if (!empty($result)): ?>
                <tr>
                    <td colspan="5" style="text-align:right; font-weight:bold">TOTAL</td>
                    <td style="text-align:right; font-weight:bold">Rp <?php echo number_format($grand_total, 0, ',', '.'); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p>Laporan ini dibuat secara otomatis oleh sistem. Total <?php echo count($result); ?> transaksi dengan nilai Rp <?php echo number_format($grand_total ?? 0, 0, ',', '.'); ?></p>
        </div>
        
        <div class="signature">
            <div class="signature-box">
                <div>Disetujui oleh:</div>
                <div class="signature-line"></div>
                <div>Manager</div>
            </div>
            <div class="signature-box">
                <div>Dibuat oleh:</div>
                <div class="signature-line"></div>
                <div><?php echo $_SESSION['nama']; ?></div>
            </div>
        </div>
    </div>
</div>

<script>
function printLaporan(mode = 'full') {
    // Buat elemen iframe untuk preview
    const printFrame = document.createElement('iframe');
    printFrame.style.display = 'none';
    document.body.appendChild(printFrame);
    
    // Tulis konten ke iframe
    const printContent = document.getElementById('printTemplate').innerHTML;
    printFrame.contentWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cetak Laporan Transaksi</title>
            <style>
                @media print {
                    @page {
                        size: ${mode === 'receipt' ? '80mm 297mm' : 'A4'};
                        margin: 0;
                    }
                    body {
                        margin: 0;
                        padding: 0;
                    }
                    .no-print {
                        display: none !important;
                    }
                }
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                }
                .print-mode-receipt {
                    width: 80mm;
                    margin: 0 auto;
                }
                .print-mode-full {
                    width: 100%;
                    max-width: 210mm;
                    margin: 0 auto;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .store-name {
                    font-size: 18px;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                .store-info {
                    font-size: 12px;
                    margin-bottom: 5px;
                }
                .report-title {
                    font-size: 14px;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                .report-period {
                    font-size: 12px;
                    margin-bottom: 15px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f8f9fa;
                }
                .text-right {
                    text-align: right;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                }
                .signature {
                    margin-top: 50px;
                    display: flex;
                    justify-content: space-between;
                }
                .signature-box {
                    text-align: center;
                    width: 45%;
                }
                .signature-line {
                    border-top: 1px solid #000;
                    margin-top: 50px;
                    width: 100%;
                }
            </style>
        </head>
        <body>
            ${printContent}
        </body>
        </html>
    `);
    
    // Tunggu sampai konten dimuat
    printFrame.onload = function() {
        // Cetak
        printFrame.contentWindow.print();
        
        // Hapus iframe setelah mencetak
        setTimeout(() => {
            document.body.removeChild(printFrame);
        }, 1000);
    };
}

function changeLaporanType(type) {
    // Sembunyikan semua filter
    document.getElementById('filter-harian').classList.add('hidden');
    document.getElementById('filter-bulanan').classList.add('hidden');
    document.getElementById('filter-tahunan').classList.add('hidden');
    
    // Tampilkan filter yang sesuai
    document.getElementById('filter-' + type).classList.remove('hidden');
}

function submitForm() {
    document.getElementById('filterForm').submit();
}

// Tambahkan event listener untuk input tanggal dan bulan
document.getElementById('tanggal').addEventListener('change', submitForm);
document.getElementById('bulan').addEventListener('change', submitForm);

// Toggle dropdown menu cetak
document.getElementById('printOptions').addEventListener('click', function(e) {
    e.preventDefault();
    const dropdown = document.getElementById('printDropdown');
    dropdown.classList.toggle('hidden');
});

// Klik di luar dropdown akan menutupnya
document.addEventListener('click', function(e) {
    const printOptions = document.getElementById('printOptions');
    const printDropdown = document.getElementById('printDropdown');
    
    if (!printOptions.contains(e.target) && !printDropdown.contains(e.target)) {
        printDropdown.classList.add('hidden');
    }
});

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
include __DIR__ . '/../../footer.php';
?>