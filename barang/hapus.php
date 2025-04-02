<?php
// Include koneksi database
require_once '../db.php';

// Periksa apakah ada parameter id
if (isset($_GET['id'])) {
    // Ambil id dari parameter URL
    $id = cleanInput($_GET['id']);
    
    // Cek apakah barang ada
    $check_query = "SELECT kode, nama FROM barang WHERE id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $barang = mysqli_fetch_assoc($result);
        
        // Cek apakah barang terkait dengan transaksi
        $check_transaksi = "SELECT COUNT(*) as count FROM transaksi_detail WHERE barang_id = ?";
        $transaksi_stmt = mysqli_prepare($conn, $check_transaksi);
        mysqli_stmt_bind_param($transaksi_stmt, "i", $id);
        mysqli_stmt_execute($transaksi_stmt);
        $transaksi_result = mysqli_stmt_get_result($transaksi_stmt);
        $transaksi_count = mysqli_fetch_assoc($transaksi_result)['count'];
        
        if ($transaksi_count > 0) {
            // Barang sudah terkait dengan transaksi, tidak bisa dihapus
            $_SESSION['error'] = "Barang tidak dapat dihapus karena sudah terkait dengan transaksi!";
        } else {
            // Hapus barang
            $delete_query = "DELETE FROM barang WHERE id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                // Log aktivitas
                $user_id = $_SESSION['user_id'];
                $activity = "Menghapus barang: {$barang['kode']} - {$barang['nama']}";
                logActivity($user_id, $activity);
                
                $_SESSION['success'] = "Barang berhasil dihapus!";
            } else {
                $_SESSION['error'] = "Error: " . mysqli_error($conn);
            }
        }
    } else {
        $_SESSION['error'] = "Barang tidak ditemukan!";
    }
} else {
    $_SESSION['error'] = "ID barang tidak valid!";
}

// Redirect kembali ke halaman list barang
header("Location: list.php");
exit();
?> 