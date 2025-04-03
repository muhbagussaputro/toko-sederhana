<?php
// Include koneksi database
require_once '../db.php';

// Cek apakah user adalah admin
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Validasi input - Sanitasi input dari user
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

// Set judul dan filter SQL berdasarkan tipe laporan
$judul_laporan = '';
$sql_filter = '';

// Menggunakan prepared statement untuk mencegah SQL injection
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
    // Default ke harian jika tipe tidak valid
    $tipe_laporan = 'harian';
    $judul_laporan = 'Laporan Penjualan Harian: ' . date('d/m/Y', strtotime($tanggal));
    $periode_laporan = 'Tanggal: ' . date('d/m/Y', strtotime($tanggal));
    $sql_filter = "WHERE DATE(t.tanggal) = ?";
    $sql_params = [$tanggal];
    $filename = 'Laporan_Harian_' . date('Y-m-d', strtotime($tanggal));
}

// Query untuk mendapatkan data transaksi dengan error handling
try {
    // Berikan nama yang lebih deskriptif untuk file Excel
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
    $nama_toko = "Toko Sederhana";
    
    // Optimasi query - menggunakan prepared statement dan mengoptimalkan JOIN
$query = "SELECT t.id, t.tanggal, t.total, u.nama as user,
              COUNT(td.id) as jumlah_item,
              GROUP_CONCAT(CONCAT(b.nama, ' (', td.jumlah, ')') SEPARATOR ', ') as detail_barang
          FROM transaksi t
          JOIN users u ON t.user_id = u.id
              LEFT JOIN transaksi_detail td ON td.transaksi_id = t.id
              LEFT JOIN barang b ON td.barang_id = b.id
          $sql_filter
              GROUP BY t.id
          ORDER BY t.tanggal DESC";
    
    // Prepare dan bind parameters
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($sql_params)), ...$sql_params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        throw new Exception("Error dalam query transaksi: " . mysqli_error($conn));
    }

    // Optimasi query total - juga menggunakan prepared statement
    $query_total = "SELECT COUNT(DISTINCT t.id) as total_transaksi, 
                   SUM(t.total) as total_penjualan,
                   AVG(t.total) as rata_rata_penjualan,
                   MIN(t.total) as min_transaksi,
                   MAX(t.total) as max_transaksi
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

    // Log aktivitas ekspor
    $activity = "Mengekspor $judul_laporan ke Excel";
    logActivity($_SESSION['user_id'], $activity);

    // Set header untuk download file Excel dengan deteksi browser
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    // Mulai output HTML yang diformat untuk Excel dengan dukungan browser yang lebih baik
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
    echo '<x:Name>Laporan</x:Name>';
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
    echo '<tr><td class="summary-header">Total Transaksi</td><td class="number">' . number_format($row_total['total_transaksi'], 0, ',', '.') . ' transaksi</td></tr>';
    echo '<tr><td class="summary-header">Total Penjualan</td><td class="currency">Rp ' . number_format($row_total['total_penjualan'] ?? 0, 0, ',', '.') . '</td></tr>';
    echo '<tr><td class="summary-header">Rata-rata Penjualan</td><td class="currency">Rp ' . number_format($row_total['rata_rata_penjualan'] ?? 0, 0, ',', '.') . '</td></tr>';
    echo '<tr><td class="summary-header">Transaksi Terkecil</td><td class="currency">Rp ' . number_format($row_total['min_transaksi'] ?? 0, 0, ',', '.') . '</td></tr>';
    echo '<tr><td class="summary-header">Transaksi Terbesar</td><td class="currency">Rp ' . number_format($row_total['max_transaksi'] ?? 0, 0, ',', '.') . '</td></tr>';
    echo '</table>';
    echo '<br>';
    
    // Tabel detail transaksi
    echo '<div class="subheader">DETAIL TRANSAKSI</div>';
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th class="align-center">No.</th>';
    echo '<th>ID</th>';
    echo '<th>Tanggal & Waktu</th>';
    echo '<th>Kasir</th>';
    echo '<th class="align-center">Jumlah Item</th>';
    echo '<th class="align-right">Total</th>';
    echo '<th>Detail Item</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    if (mysqli_num_rows($result) > 0) {
        $no = 1;
        $grand_total = 0;
        $total_items = 0;
        
        while ($data = mysqli_fetch_assoc($result)) {
            $grand_total += $data['total'];
            $total_items += $data['jumlah_item'];
            
            echo '<tr>';
            echo '<td class="align-center">' . $no++ . '</td>';
            echo '<td>TRX-' . str_pad($data['id'], 4, '0', STR_PAD_LEFT) . '</td>';
            echo '<td>' . date('d/m/Y H:i', strtotime($data['tanggal'])) . '</td>';
            echo '<td>' . htmlspecialchars($data['user']) . '</td>';
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
    
} catch (Exception $e) {
    // Log error dengan detail lebih lengkap
    $error_message = "Error dalam export Excel: " . $e->getMessage();
    error_log("[" . date('Y-m-d H:i:s') . "] " . $error_message);
    
    // Log ke database
    if (isset($_SESSION['user_id'])) {
        $error_activity = "Error ekspor Excel: " . $error_message;
        logActivity($_SESSION['user_id'], $error_activity);
    }
    
    // Tampilkan pesan error yang user-friendly
    echo "<script>
        alert('Terjadi kesalahan dalam mengekspor data: " . addslashes($e->getMessage()) . "\\nSilakan coba lagi atau hubungi administrator.');
        window.history.back();
    </script>";
}

// Tutup koneksi database
mysqli_close($conn);
exit();
?> 