<?php
session_start();

// Include koneksi database
require_once __DIR__ . '/../../db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

// Periksa apakah ada parameter id
if (isset($_GET['id'])) {
    try {
        // Ambil id dari parameter URL
        $id = cleanInput($_GET['id']);
        
        // Cek apakah barang ada
        $query = "SELECT kode, nama FROM barang WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $barang = mysqli_fetch_assoc($result);
        
        if ($barang) {
            // Cek apakah barang terkait dengan transaksi
            $check_query = "SELECT COUNT(*) as count FROM transaksi_detail WHERE barang_id = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "i", $id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $transaksi_count = mysqli_fetch_assoc($check_result)['count'];
            
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
                    $activity = "Menghapus barang: " . htmlspecialchars($barang['kode']) . " - " . htmlspecialchars($barang['nama']);
                    logActivity($_SESSION['user_id'], $activity);
                    
                    $_SESSION['success'] = "Barang berhasil dihapus!";
                } else {
                    throw new Exception("Gagal menghapus data barang: " . mysqli_error($conn));
                }
            }
        } else {
            $_SESSION['error'] = "Barang tidak ditemukan!";
        }
    } catch (Exception $e) {
        error_log("Error hapus barang: " . $e->getMessage());
        $_SESSION['error'] = "Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.";
    }
} else {
    $_SESSION['error'] = "ID barang tidak valid!";
}

// Redirect kembali ke halaman list barang
header("Location: list.php");
exit();
?> 