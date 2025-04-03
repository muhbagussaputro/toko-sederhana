<?php
// Include koneksi database
require_once __DIR__ . '/../../db.php';

// Cek apakah user adalah admin
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Inisialisasi variabel filter
$tgl_mulai = isset($_GET['tgl_mulai']) ? cleanInput($_GET['tgl_mulai']) : date('Y-m-d', strtotime('-7 days'));
$tgl_selesai = isset($_GET['tgl_selesai']) ? cleanInput($_GET['tgl_selesai']) : date('Y-m-d');
$user_filter = isset($_GET['user_id']) ? cleanInput($_GET['user_id'], 'int') : '';
$level_filter = isset($_GET['level']) ? cleanInput($_GET['level']) : '';
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

// Set filter waktu dan kondisi lainnya
$where_conditions = ["DATE(l.timestamp) BETWEEN ? AND ?"];
$params = [$tgl_mulai, $tgl_selesai];
$types = "ss";

// Tambahkan filter user jika dipilih
if (!empty($user_filter)) {
    $where_conditions[] = "l.user_id = ?";
    $params[] = $user_filter;
    $types .= "i";
}

// Tambahkan filter level jika dipilih (jika kolom level ada)
try {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM log_aktivitas LIKE 'level'");
    $stmt->execute();
    $has_level_column = $stmt->rowCount() > 0;
    
    if ($has_level_column && !empty($level_filter)) {
        $where_conditions[] = "l.level = ?";
        $params[] = $level_filter;
    }
    
    // Tambahkan filter pencarian jika ada
    if (!empty($search)) {
        $where_conditions[] = "l.aktivitas LIKE ?";
        $params[] = "%$search%";
    }
    
    // Buat where clause
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    // Query untuk mendapatkan daftar log aktivitas menggunakan PDO
    $query = "SELECT l.id, l.aktivitas, l.timestamp, u.nama, u.username, u.role";
    
    // Tambahkan kolom level jika ada
    if ($has_level_column) {
        $query .= ", l.level";
    }
    
    $query .= " FROM log_aktivitas l
               JOIN users u ON l.user_id = u.id
               $where_clause
               ORDER BY l.timestamp DESC";
    
    // Eksekusi query dengan PDO
    $stmt = $pdo->prepare($query);
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i+1, $params[$i]);
    }
    $stmt->execute();
    $result = $stmt->fetchAll();
    
    // Query untuk mendapatkan pengguna untuk dropdown filter
    $stmt_users = $pdo->query("SELECT id, nama, username FROM users ORDER BY nama");
    $users_result = $stmt_users->fetchAll();
} catch (PDOException $e) {
    handleError("Error pada log aktivitas: " . $e->getMessage());
    $error_message = "Terjadi kesalahan saat memuat data log. Silakan coba lagi atau hubungi administrator.";
}

// Include header
$title = "Log Aktivitas";
include __DIR__ . '/../../header.php';
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="flex justify-between items-center p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800">Log Aktivitas Penting</h1>
        <div class="flex space-x-2">
            <button id="btnOptimasi" class="bg-warning hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 inline-flex items-center">
                <i class="fas fa-cogs mr-2"></i> Optimasi Log
            </button>
            <a href="../export/export_log.php?tgl_mulai=<?php echo urlencode($tgl_mulai); ?>&tgl_selesai=<?php echo urlencode($tgl_selesai); ?><?php echo !empty($user_filter) ? '&user_id=' . urlencode($user_filter) : ''; ?><?php echo !empty($level_filter) ? '&level=' . urlencode($level_filter) : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 inline-flex items-center">
                <i class="fas fa-file-excel mr-2"></i> Export Excel
            </a>
            <div class="relative inline-block">
                <button id="printOptions" class="bg-info hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 inline-flex items-center">
                    <i class="fas fa-print mr-2"></i> Cetak <i class="fas fa-chevron-down ml-2"></i>
                </button>
                <div id="printDropdown" class="hidden absolute z-10 right-0 mt-2 bg-white rounded-md shadow-lg py-1 w-48">
                    <a href="#" onclick="return printLog('struk');" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-300">
                        <i class="fas fa-receipt mr-2"></i> Format Struk Kasir
                    </a>
                    <a href="#" onclick="return printLog('full');" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-300">
                        <i class="fas fa-file-alt mr-2"></i> Format Laporan
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="p-4">
        <!-- Filter Form -->
        <form id="filterForm" action="" method="get" class="mb-6 bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <div>
                    <label for="tgl_mulai" class="block text-gray-700 text-sm font-medium mb-2">Tanggal Mulai:</label>
                    <input type="date" id="tgl_mulai" name="tgl_mulai" value="<?php echo $tgl_mulai; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary filter-input">
                </div>
                <div>
                    <label for="tgl_selesai" class="block text-gray-700 text-sm font-medium mb-2">Tanggal Selesai:</label>
                    <input type="date" id="tgl_selesai" name="tgl_selesai" value="<?php echo $tgl_selesai; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary filter-input">
                </div>
                <div>
                    <label for="user_id" class="block text-gray-700 text-sm font-medium mb-2">Pengguna:</label>
                    <select id="user_id" name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary filter-input">
                        <option value="">Semua Pengguna</option>
                        <?php foreach ($users_result as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo $user['nama'] . ' (' . $user['username'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($has_level_column): ?>
                <div>
                    <label for="level" class="block text-gray-700 text-sm font-medium mb-2">Tingkat Kepentingan:</label>
                    <select id="level" name="level" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary filter-input">
                        <option value="">Semua</option>
                        <option value="penting" <?php echo $level_filter == 'penting' ? 'selected' : ''; ?>>Penting</option>
                        <option value="normal" <?php echo $level_filter == 'normal' ? 'selected' : ''; ?>>Normal</option>
                    </select>
                </div>
                <?php endif; ?>
                <div>
                    <label for="search" class="block text-gray-700 text-sm font-medium mb-2">Cari Aktivitas:</label>
                    <input type="text" id="search" name="search" value="<?php echo $search; ?>" placeholder="Cari..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary filter-input">
                </div>
                <div class="flex space-x-2 col-span-1 md:col-span-2 lg:col-span-5">
                    <button type="button" id="resetFilter" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 text-center">
                        <i class="fas fa-undo mr-2"></i> Reset Filter
                    </button>
                    <div id="loadingIndicator" class="hidden ml-3 text-sm text-gray-600">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Memuat data...
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Hasil Pencarian -->
        <div class="mb-4 text-sm text-gray-600">
            <p>Menampilkan <?php echo count($result); ?> log aktivitas <?php echo $level_filter == 'penting' ? 'penting' : ($level_filter == 'normal' ? 'normal' : ''); ?> 
            <?php echo !empty($search) ? "dengan kata kunci \"" . htmlspecialchars($search) . "\"" : ""; ?></p>
        </div>
        
        <!-- Tabel Log Aktivitas -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <?php if ($has_level_column): ?>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                        <?php endif; ?>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktivitas</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($result) > 0): ?>
                        <?php foreach ($result as $row): ?>
                            <tr class="hover:bg-gray-50 <?php echo isset($row['level']) && $row['level'] == 'penting' ? 'bg-yellow-50' : ''; ?>">
                                <td class="px-4 py-3 whitespace-nowrap"><?php echo $row['id']; ?></td>
                                <td class="px-4 py-3 whitespace-nowrap"><?php echo date('d/m/Y H:i:s', strtotime($row['timestamp'])); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap"><?php echo $row['nama'] . ' (' . $row['username'] . ')'; ?></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                          <?php echo $row['role'] == 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo $row['role']; ?>
                                    </span>
                                </td>
                                <?php if ($has_level_column): ?>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                          <?php echo isset($row['level']) && $row['level'] == 'penting' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo isset($row['level']) ? $row['level'] : 'normal'; ?>
                                    </span>
                                </td>
                                <?php endif; ?>
                                <td class="px-4 py-3 <?php echo isset($row['level']) && $row['level'] == 'penting' ? 'font-semibold' : ''; ?>">
                                    <?php 
                                    // Cek pola transaksi dan ubah menjadi link
                                    $aktivitas = $row['aktivitas'];
                                    
                                    // Pola 1: "Membuat transaksi baru #123"
                                    if (preg_match('/Membuat transaksi baru #(\d+)/', $aktivitas, $matches)) {
                                        $transaksi_id = $matches[1];
                                        $aktivitas = preg_replace(
                                            '/(Membuat transaksi baru #)(\d+)/', 
                                            '$1<a href="../transaksi/detail.php?id=$2" class="text-blue-600 hover:text-blue-800 hover:underline">$2</a>',
                                            $aktivitas
                                        );
                                    } 
                                    // Pola 2: "transaksi #123" (tanpa kata "baru")
                                    elseif (preg_match('/transaksi #(\d+)/i', $aktivitas, $matches)) {
                                        $transaksi_id = $matches[1];
                                        $aktivitas = preg_replace(
                                            '/(transaksi #)(\d+)/i', 
                                            '$1<a href="../transaksi/detail.php?id=$2" class="text-blue-600 hover:text-blue-800 hover:underline">$2</a>',
                                            $aktivitas
                                        );
                                    }
                                    
                                    echo $aktivitas;
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $has_level_column ? '6' : '5'; ?>" class="px-4 py-4 text-center text-gray-500">Tidak ada data log aktivitas</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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

<!-- Template untuk cetak yang tersembunyi sampai mode print -->
<div id="print-template" style="display: none;">
    <!-- Template struk kasir -->
    <div class="print-template-struk">
        <div class="struk-header">
            <h1 class="struk-title">LOG AKTIVITAS SISTEM</h1>
            <div class="print-info">Toko Sederhana</div>
            <div class="print-info">Periode: <?php echo date('d/m/Y', strtotime($tgl_mulai)); ?> - <?php echo date('d/m/Y', strtotime($tgl_selesai)); ?></div>
            <div class="print-info">Dicetak: <?php echo date('d/m/Y H:i:s'); ?></div>
            <div class="print-info">User: <?php echo $_SESSION['nama']; ?></div>
            <?php if (!empty($level_filter)): ?>
            <div class="print-info">Level: <?php echo strtoupper($level_filter); ?></div>
            <?php endif; ?>
            <?php if (!empty($search)): ?>
            <div class="print-info">Pencarian: <?php echo htmlspecialchars($search); ?></div>
            <?php endif; ?>
        </div>
        
        <table class="print-table">
            <thead>
                <tr>
                    <td style="width: 30%"><b>WAKTU</b></td>
                    <td style="width: 25%"><b>USER</b></td>
                    <td style="width: 45%"><b>AKTIVITAS</b></td>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (count($result) > 0): 
                    foreach ($result as $row): 
                ?>
                    <tr class="item-row">
                        <td><?php echo date('d/m H:i', strtotime($row['timestamp'])); ?></td>
                        <td><?php echo $row['username']; ?></td>
                        <td><?php echo $row['aktivitas']; ?></td>
                    </tr>
                <?php 
                    endforeach; 
                else: 
                ?>
                    <tr>
                        <td colspan="3" style="text-align:center">Tidak ada data log</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="struk-footer">
            <div class="print-info">Total: <?php echo count($result); ?> log aktivitas</div>
            <div class="print-info">==============================</div>
            <div class="print-info">Log aktivitas adalah keterangan tentang aktivitas</div>
            <div class="print-info">penting yang dilakukan user pada sistem.</div>
            <div class="print-info">==============================</div>
            <div class="print-info">Terima Kasih</div>
        </div>
    </div>
    
    <!-- Template laporan penuh -->
    <div class="print-template-full">
        <div class="report-header">
            <h1 class="report-title">LAPORAN LOG AKTIVITAS SISTEM</h1>
            <table style="width:100%">
                <tr>
                    <td style="width:50%">
                        <div class="report-info"><strong>Toko Sederhana</strong></div>
                        <div class="report-info">Jl. Contoh No. 123, Kota</div>
                        <div class="report-info">Telp: (021) 1234567</div>
                    </td>
                    <td style="width:50%; text-align:right">
                        <div class="report-info"><strong>Laporan Log Aktivitas</strong></div>
                        <div class="report-info">Periode: <?php echo date('d/m/Y', strtotime($tgl_mulai)); ?> s/d <?php echo date('d/m/Y', strtotime($tgl_selesai)); ?></div>
                        <div class="report-info">Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></div>
                    </td>
                </tr>
            </table>
            
            <div style="margin-top:10px; margin-bottom:10px; border-top:2px solid #000; border-bottom:1px solid #000; padding:3px 0;">
                <table style="width:100%">
                    <tr>
                        <td style="width:50%">
                            <?php if (!empty($level_filter)): ?>
                            <div class="report-info">Level: <strong><?php echo strtoupper($level_filter); ?></strong></div>
                            <?php else: ?>
                            <div class="report-info">Level: <strong>Semua</strong></div>
                            <?php endif; ?>
                        </td>
                        <td style="width:50%; text-align:right">
                            <?php if (!empty($search)): ?>
                            <div class="report-info">Filter: <strong><?php echo htmlspecialchars($search); ?></strong></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width:5%">No</th>
                    <th style="width:15%">Tanggal & Jam</th>
                    <th style="width:20%">Pengguna</th>
                    <th style="width:10%">Role</th>
                    <?php if ($has_level_column): ?>
                    <th style="width:10%">Level</th>
                    <?php endif; ?>
                    <th style="width:40%">Aktivitas</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                if (count($result) > 0): 
                    foreach ($result as $row): 
                ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo date('d/m/Y H:i:s', strtotime($row['timestamp'])); ?></td>
                        <td><?php echo $row['nama'] . ' (' . $row['username'] . ')'; ?></td>
                        <td><?php echo $row['role']; ?></td>
                        <?php if ($has_level_column): ?>
                        <td><?php echo isset($row['level']) ? $row['level'] : 'normal'; ?></td>
                        <?php endif; ?>
                        <td><?php echo $row['aktivitas']; ?></td>
                    </tr>
                <?php 
                    endforeach; 
                else: 
                ?>
                    <tr>
                        <td colspan="<?php echo $has_level_column ? '6' : '5'; ?>" style="text-align:center">Tidak ada data log aktivitas</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="report-footer">
            <p>Laporan ini dibuat secara otomatis oleh sistem. Jumlah total log aktivitas: <?php echo count($result); ?></p>
        </div>
        
        <div class="report-signature">
            <div class="report-signature-box">
                <div>Disetujui oleh:</div>
                <div class="report-signature-line"></div>
                <div>Manager</div>
            </div>
            <div class="report-signature-box">
                <div>Dibuat oleh:</div>
                <div class="report-signature-line"></div>
                <div><?php echo $_SESSION['nama']; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Optimasi Log -->
<div id="optimasiModal" class="fixed inset-0 z-50 overflow-auto bg-black bg-opacity-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white w-full max-w-md mx-auto rounded-lg shadow-xl transform transition-all">
            <div class="bg-warning text-white px-4 py-3 flex justify-between items-center rounded-t-lg">
                <h3 class="text-lg font-semibold"><i class="fas fa-cogs mr-2"></i>Optimasi Log Aktivitas</h3>
                <button id="closeOptimasiModal" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="optimasiContent" class="p-6 max-h-[70vh] overflow-y-auto">
                <div class="text-center mb-6">
                    <i class="fas fa-spinner fa-spin text-warning text-4xl mb-3"></i>
                    <p>Silakan tunggu, sistem sedang mengoptimasi data log...</p>
                </div>
            </div>
            <div class="bg-gray-100 px-4 py-3 rounded-b-lg flex justify-end">
                <button id="closeOptimasiBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 hidden">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Script untuk menangani filter dan pencarian real-time
document.addEventListener('DOMContentLoaded', function() {
    // Semua elemen input filter
    const filterInputs = document.querySelectorAll('.filter-input');
    const filterForm = document.getElementById('filterForm');
    const resetButton = document.getElementById('resetFilter');
    const loadingIndicator = document.getElementById('loadingIndicator');
    let timer = null;
    
    // Fungsi untuk memuat data dengan AJAX
    function loadData() {
        const formData = new FormData(filterForm);
        const queryParams = new URLSearchParams(formData).toString();
        
        // Tampilkan indikator loading
        loadingIndicator.classList.remove('hidden');
        
        // Simpan filter di URL untuk bookmark/refresh
        history.replaceState(null, '', '?' + queryParams);
        
        // Lakukan request AJAX
        fetch('get_log_data.php?' + queryParams)
            .then(response => response.text())
            .then(html => {
                // Update tabel dengan hasil
                document.querySelector('.overflow-x-auto').innerHTML = html;
                loadingIndicator.classList.add('hidden');
            })
            .catch(error => {
                console.error('Error:', error);
                loadingIndicator.classList.add('hidden');
            });
    }
    
    // Event listener untuk setiap elemen input
    filterInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Clear timer yang sudah ada
            if (timer) {
                clearTimeout(timer);
            }
            
            // Set timer untuk delay sebelum memuat data (menghindari terlalu banyak request)
            timer = setTimeout(loadData, 500);
        });
        
        // Untuk dropdown yang berubah nilainya
        if (input.tagName === 'SELECT') {
            input.addEventListener('change', function() {
                if (timer) {
                    clearTimeout(timer);
                }
                timer = setTimeout(loadData, 300);
            });
        }
    });
    
    // Event listener untuk reset filter
    resetButton.addEventListener('click', function() {
        // Reset form
        filterForm.reset();
        
        // Set tanggal default
        document.getElementById('tgl_mulai').value = '<?php echo date('Y-m-d', strtotime('-7 days')); ?>';
        document.getElementById('tgl_selesai').value = '<?php echo date('Y-m-d'); ?>';
        
        // Muat data dengan filter yang di-reset
        loadData();
    });
});

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

// Fungsi untuk menangani Modal Optimasi Log
const optimasiModal = document.getElementById('optimasiModal');
const closeOptimasiModal = document.getElementById('closeOptimasiModal');
const closeOptimasiBtn = document.getElementById('closeOptimasiBtn');
const optimasiContent = document.getElementById('optimasiContent');
const btnOptimasi = document.getElementById('btnOptimasi');

// Tombol buka modal
btnOptimasi.addEventListener('click', function() {
    // Tampilkan modal dengan animasi fade in
    optimasiModal.classList.remove('hidden');
    optimasiModal.style.opacity = '0';
    optimasiModal.style.transition = 'opacity 0.3s ease';
    
    setTimeout(() => {
        optimasiModal.style.opacity = '1';
    }, 10);
    
    document.body.classList.add('overflow-hidden');
    
    // Lakukan request Ajax untuk optimasi
    fetch('optimasi_log_ajax.php')
        .then(response => response.text())
        .then(html => {
            // Update konten modal dengan hasil
            optimasiContent.innerHTML = html;
            // Tampilkan tombol tutup
            closeOptimasiBtn.classList.remove('hidden');
        })
        .catch(error => {
            optimasiContent.innerHTML = `
                <div class="bg-red-100 text-red-700 p-4 rounded-md">
                    <p class="font-semibold">Terjadi kesalahan!</p>
                    <p>Tidak dapat mengoptimasi log aktivitas. Silakan coba lagi nanti.</p>
                    <p class="text-xs mt-2">Error: ${error.message}</p>
                </div>
            `;
            closeOptimasiBtn.classList.remove('hidden');
        });
});

// Fungsi untuk menutup modal dengan animasi
function closeModalWithAnimation() {
    optimasiModal.style.opacity = '0';
    
    setTimeout(() => {
        optimasiModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }, 300); // Sesuaikan dengan durasi transisi
}

// Tombol tutup modal
closeOptimasiModal.addEventListener('click', closeModalWithAnimation);
closeOptimasiBtn.addEventListener('click', closeModalWithAnimation);

// Tutup modal saat klik di luar modal (opsional)
optimasiModal.addEventListener('click', function(e) {
    if (e.target === optimasiModal) {
        closeModalWithAnimation();
    }
});

// Script untuk menangani pencetakan
function printLog(format = 'struk') {
    // Tampilkan template cetak saat mode print
    document.getElementById('print-template').style.display = 'block';
    
    // Set mode cetak
    if (format === 'struk') {
        document.body.classList.add('struk-mode');
        document.body.classList.remove('full-mode');
    } else {
        document.body.classList.add('full-mode');
        document.body.classList.remove('struk-mode');
    }
    
    // Set opsi cetak
    window.print();
    
    // Sembunyikan kembali template setelah print dialog ditutup
    setTimeout(function() {
        document.getElementById('print-template').style.display = 'none';
        document.body.classList.remove('struk-mode', 'full-mode');
    }, 1000);
    
    return false;
}
</script>

<?php
// Include footer
include __DIR__ . '/../../footer.php';
?> 