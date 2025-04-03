<?php
// Include koneksi database
require_once '../db.php';

// Hanya terima permintaan AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    // Jika bukan AJAX, kembalikan 403 Forbidden
    header('HTTP/1.0 403 Forbidden');
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

// Proses pencarian
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$response = ['status' => 'success', 'data' => []];

// Pastikan pencarian memiliki minimal 1 karakter
if (strlen($search) > 0) {
    try {
        // Gunakan prepared statement untuk mencegah SQL injection
        $query = "SELECT id, kode, nama, stok, harga 
                 FROM barang 
                 WHERE kode LIKE ? OR nama LIKE ? 
                 ORDER BY nama ASC 
                 LIMIT 20";
        
        $search_param = "%$search%";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ss', $search_param, $search_param);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result) {
            throw new Exception("Error dalam pencarian: " . mysqli_error($conn));
        }
        
        // Format hasil pencarian
        while ($row = mysqli_fetch_assoc($result)) {
            $row['harga_formatted'] = 'Rp ' . number_format($row['harga'], 0, ',', '.');
            $row['stok_status'] = $row['stok'] <= 5 ? 'rendah' : ($row['stok'] <= 10 ? 'sedang' : 'tinggi');
            $response['data'][] = $row;
        }
        
        // Log aktivitas pencarian
        if (isset($_SESSION['user_id'])) {
            $activity = "Mencari barang dengan kata kunci: $search";
            logActivity($_SESSION['user_id'], $activity);
        }
        
    } catch (Exception $e) {
        $response = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
        
        // Log error
        error_log("Search API Error: " . $e->getMessage());
    }
}

// Kembalikan hasil dalam format JSON
header('Content-Type: application/json');
echo json_encode($response);
exit; 