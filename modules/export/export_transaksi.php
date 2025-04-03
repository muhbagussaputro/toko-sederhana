<?php
// Include koneksi database
require_once __DIR__ . '/../../db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

// Validasi input dengan sanitasi
$tipe_laporan = isset($_GET['tipe']) ? filter_var($_GET['tipe'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
$tanggal = isset($_GET['tanggal']) ? filter_var($_GET['tanggal'], FILTER_SANITIZE_SPECIAL_CHARS) : date('Y-m-d');
$bulan = isset($_GET['bulan']) ? filter_var($_GET['bulan'], FILTER_SANITIZE_SPECIAL_CHARS) : date('Y-m');
$tahun = isset($_GET['tahun']) ? filter_var($_GET['tahun'], FILTER_SANITIZE_SPECIAL_CHARS) : date('Y');
$tgl_mulai = isset($_GET['tgl_mulai']) ? filter_var($_GET['tgl_mulai'], FILTER_SANITIZE_SPECIAL_CHARS) : date('Y-m-d', strtotime('-7 days'));
$tgl_selesai = isset($_GET['tgl_selesai']) ? filter_var($_GET['tgl_selesai'], FILTER_SANITIZE_SPECIAL_CHARS) : date('Y-m-d');

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
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_mulai)) {
    $tgl_mulai = date('Y-m-d', strtotime('-7 days'));
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_selesai)) {
    $tgl_selesai = date('Y-m-d');
}

// Set judul dan filter SQL berdasarkan tipe laporan
$judul_laporan = '';
$periode_laporan = '';
$sql_filter = '';
$sql_params = [];

if ($tipe_laporan == 'harian') {
    $judul_laporan = 'Laporan Penjualan Harian: ' . date('d/m/Y', strtotime($tanggal));
    $periode_laporan = 'Tanggal: ' . date('d/m/Y', strtotime($tanggal));
    $sql_filter = "WHERE DATE(t.tanggal) = ?";
    $sql_params = [$tanggal];
    $filename = 'Laporan_Harian_' . date('Y-m-d', strtotime($tanggal));
} elseif ($tipe_laporan == 'bulanan') {
    $bulan_indo = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', 
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', 
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $bulan_tahun = explode('-', $bulan);
    $bulan_nama = isset($bulan_indo[$bulan_tahun[1]]) ? $bulan_indo[$bulan_tahun[1]] : date('F', strtotime($bulan));
    
    $judul_laporan = 'Laporan Penjualan Bulanan: ' . $bulan_nama . ' ' . $bulan_tahun[0];
    $periode_laporan = 'Periode: ' . $bulan_nama . ' ' . $bulan_tahun[0];
    $sql_filter = "WHERE DATE_FORMAT(t.tanggal, '%Y-%m') = ?";
    $sql_params = [$bulan];
    $filename = 'Laporan_Bulanan_' . date('Y-m', strtotime($bulan));
} elseif ($tipe_laporan == 'tahunan') {
    $judul_laporan = 'Laporan Penjualan Tahunan: ' . $tahun;
    $periode_laporan = 'Tahun: ' . $tahun;
    $sql_filter = "WHERE YEAR(t.tanggal) = ?";
    $sql_params = [$tahun];
    $filename = 'Laporan_Tahunan_' . $tahun;
} else {
    // Default ke rentang tanggal jika tipe tidak valid
    $judul_laporan = 'Laporan Data Transaksi';
    $periode_laporan = 'Periode: ' . date('d/m/Y', strtotime($tgl_mulai)) . ' - ' . date('d/m/Y', strtotime($tgl_selesai));
    $sql_filter = "WHERE DATE(t.tanggal) BETWEEN ? AND ?";
    $sql_params = [$tgl_mulai, $tgl_selesai];
    $filename = 'Transaksi_' . date('Y-m-d', strtotime($tgl_mulai)) . '_sd_' . date('Y-m-d', strtotime($tgl_selesai));
}

try {
    // Nama file Excel
    $nama_toko = "Toko Sederhana";
    
    // Query untuk mendapatkan daftar transaksi dengan detail item
    $query = "SELECT t.id, t.tanggal, t.total, u.username as user, u.nama as nama_user,
            COUNT(td.id) as jumlah_item,
            GROUP_CONCAT(CONCAT(b.nama, ' (', td.jumlah, ')') SEPARATOR ', ') as detail_barang
           FROM transaksi t 
           JOIN users u ON t.user_id = u.id 
           LEFT JOIN transaksi_detail td ON td.transaksi_id = t.id
           LEFT JOIN barang b ON td.barang_id = b.id
           $sql_filter
           GROUP BY t.id
           ORDER BY t.tanggal DESC";
    
    $stmt = $pdo->prepare($query);
    for ($i = 0; $i < count($sql_params); $i++) {
        $stmt->bindValue($i+1, $sql_params[$i]);
    }
    $stmt->execute();
    $transactions = $stmt->fetchAll();
    
    // Hitung total pendapatan dalam periode
    $query_total = "SELECT SUM(t.total) as total_pendapatan,
                   COUNT(*) as jumlah_transaksi,
                   AVG(t.total) as rata_transaksi,
                   MIN(t.total) as min_transaksi,
                   MAX(t.total) as max_transaksi
                   FROM transaksi t
                   $sql_filter";
    
    $stmt_total = $pdo->prepare($query_total);
    for ($i = 0; $i < count($sql_params); $i++) {
        $stmt_total->bindValue($i+1, $sql_params[$i]);
    }
    $stmt_total->execute();
    $summary = $stmt_total->fetch();
    
    // Log aktivitas ekspor
    $activity = "Mengekspor data transaksi periode " . date('d/m/Y', strtotime($tgl_mulai)) . " - " . date('d/m/Y', strtotime($tgl_selesai));
    logActivity($_SESSION['user_id'], $activity, 'penting');

    // Set header untuk download file Excel
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    // Mulai output HTML yang diformat untuk Excel
    echo '<!DOCTYPE html>';
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '<meta name="ProgId" content="Excel.Sheet">';
    echo '<meta name="Generator" content="Microsoft Excel 15">';
    echo '<!--[if gte mso 9]>';
    echo '<xml>';
    echo '<x:ExcelWorkbook>';
    echo '<x:ExcelWorksheets>';
    echo '<x:ExcelWorksheet>';
    echo '<x:Name>Transaksi</x:Name>';
    echo '<x:WorksheetOptions>';
    echo '<x:DisplayGridlines/>';
    echo '</x:WorksheetOptions>';
    echo '</x:ExcelWorksheet>';
    echo '</x:ExcelWorksheets>';
    echo '</x:ExcelWorkbook>';
    echo '</xml>';
    echo '<![endif]-->';
    echo '<style>';
    echo '@page { margin: 0.5cm; }';
    echo 'table { border-collapse: collapse; width: 100%; border: 1px solid #000; }';
    echo 'th, td { border: 1px solid #000; padding: 5px; text-align: left; mso-number-format:\@; }';
    echo 'th { background-color: #4472C4; color: white; font-weight: bold; }';
    echo '.header { font-size: 16pt; font-weight: bold; margin-bottom: 10px; }';
    echo '.subheader { font-size: 12pt; font-weight: bold; margin-bottom: 5px; }';
    echo '.periode { font-size: 11pt; margin-bottom: 15px; }';
    echo '.summary-table { margin-bottom: 20px; width: 50%; }';
    echo '.summary-header { background-color: #5B9BD5; color: white; font-weight: bold; }';
    echo '.total-row { background-color: #D9E1F2; font-weight: bold; }';
    echo '.align-right { text-align: right; mso-number-format:"0.00"; }';
    echo '.align-center { text-align: center; }';
    echo '.footer { font-size: 10pt; font-style: italic; margin-top: 10px; }';
    echo '.currency { mso-number-format:"_-* #,##0.00_-;-* #,##0.00_-;_-* \"-\"??_-;_-@_-"; }';
    echo '.number { mso-number-format:"0"; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Header toko dan laporan
    echo '<div class="header">' . htmlspecialchars($nama_toko) . '</div>';
    echo '<div class="subheader">' . htmlspecialchars($judul_laporan) . '</div>';
    echo '<div class="periode">' . htmlspecialchars($periode_laporan) . '</div>';
    echo '<div>Laporan dibuat pada: ' . date('d/m/Y H:i:s') . '</div>';
    echo '<div>Dibuat oleh: ' . htmlspecialchars($_SESSION['nama']) . ' (' . htmlspecialchars($_SESSION['role']) . ')</div>';
    echo '<br>';
    
    // Tabel ringkasan
    echo '<div class="subheader">RINGKASAN TRANSAKSI</div>';
    echo '<table class="summary-table">';
    echo '<tr><td class="summary-header">Total Transaksi</td><td class="number">' . number_format($summary['jumlah_transaksi'], 0, ',', '.') . ' transaksi</td></tr>';
    echo '<tr><td class="summary-header">Total Pendapatan</td><td class="currency">Rp ' . number_format($summary['total_pendapatan'] ?? 0, 0, ',', '.') . '</td></tr>';
    echo '<tr><td class="summary-header">Rata-rata Transaksi</td><td class="currency">Rp ' . number_format($summary['rata_transaksi'] ?? 0, 0, ',', '.') . '</td></tr>';
    echo '<tr><td class="summary-header">Transaksi Terkecil</td><td class="currency">Rp ' . number_format($summary['min_transaksi'] ?? 0, 0, ',', '.') . '</td></tr>';
    echo '<tr><td class="summary-header">Transaksi Terbesar</td><td class="currency">Rp ' . number_format($summary['max_transaksi'] ?? 0, 0, ',', '.') . '</td></tr>';
    echo '</table>';
    echo '<br>';
    
    // Tabel detail transaksi
    echo '<div class="subheader">DETAIL TRANSAKSI</div>';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th class="align-center">No.</th>';
    echo '<th>ID Transaksi</th>';
    echo '<th>Tanggal & Waktu</th>';
    echo '<th>Kasir</th>';
    echo '<th class="align-center">Jumlah Item</th>';
    echo '<th class="align-right">Total</th>';
    echo '<th>Detail Item</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    if (count($transactions) > 0) {
        $no = 1;
        $grand_total = 0;
        $total_items = 0;
        
        foreach ($transactions as $data) {
            $grand_total += $data['total'];
            $total_items += $data['jumlah_item'];
            
            echo '<tr>';
            echo '<td class="align-center">' . $no++ . '</td>';
            echo '<td>TRX-' . str_pad($data['id'], 4, '0', STR_PAD_LEFT) . '</td>';
            echo '<td>' . date('d/m/Y H:i', strtotime($data['tanggal'])) . '</td>';
            echo '<td>' . htmlspecialchars($data['nama_user'] ?? $data['user']) . '</td>';
            echo '<td class="align-center number">' . $data['jumlah_item'] . ' item</td>';
            echo '<td class="align-right currency">Rp ' . number_format($data['total'], 0, ',', '.') . '</td>';
            echo '<td>' . htmlspecialchars($data['detail_barang'] ?? '-') . '</td>';
            echo '</tr>';
        }
        
        // Baris total
        echo '<tr class="total-row">';
        echo '<td colspan="4" class="align-right">TOTAL:</td>';
        echo '<td class="align-center number">' . $total_items . ' item</td>';
        echo '<td class="align-right currency">Rp ' . number_format($grand_total, 0, ',', '.') . '</td>';
        echo '<td></td>';
        echo '</tr>';
    } else {
        echo '<tr><td colspan="7" class="align-center">Tidak ada data transaksi</td></tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // Footer
    echo '<div class="footer">Catatan: Laporan ini bersifat rahasia dan hanya untuk keperluan internal.</div>';
    echo '<div class="footer">Toko Sederhana © ' . date('Y') . '</div>';
    
    echo '</body>';
    echo '</html>';
    
} catch (PDOException $e) {
    // Log error
    handleError("Error pada ekspor data transaksi: " . $e->getMessage());
    
    // Redirect ke halaman sebelumnya dengan pesan error
    $_SESSION['error'] = "Terjadi kesalahan saat mengekspor data. Silakan coba lagi.";
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
} 