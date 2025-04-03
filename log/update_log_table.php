<?php
// Include koneksi database
require_once '../db.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

try {
    // Cek apakah kolom level sudah ada dalam tabel log_aktivitas
    $check_column = "SHOW COLUMNS FROM log_aktivitas LIKE 'level'";
    $column_result = mysqli_query($conn, $check_column);
    
    if (mysqli_num_rows($column_result) == 0) {
        // Kolom belum ada, tambahkan kolom level
        $add_column = "ALTER TABLE log_aktivitas 
                       ADD COLUMN level ENUM('normal', 'penting') DEFAULT 'normal'";
        
        if (mysqli_query($conn, $add_column)) {
            echo "<p>Kolom level berhasil ditambahkan ke tabel log_aktivitas.</p>";
            
            // Update log yang sudah ada dengan nilai level berdasarkan isi aktivitas
            $update_important = "UPDATE log_aktivitas SET level = 'penting' 
                               WHERE aktivitas LIKE '%hapus%' 
                               OR aktivitas LIKE '%edit%' 
                               OR aktivitas LIKE '%tambah%' 
                               OR aktivitas LIKE '%baru%' 
                               OR aktivitas LIKE '%transaksi%' 
                               OR aktivitas LIKE '%login%' 
                               OR aktivitas LIKE '%logout%'";
            
            if (mysqli_query($conn, $update_important)) {
                echo "<p>Log aktivitas penting berhasil diupdate.</p>";
            } else {
                echo "<p>Error saat mengupdate log aktivitas: " . mysqli_error($conn) . "</p>";
            }
        } else {
            echo "<p>Error saat menambahkan kolom level: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>Kolom level sudah ada dalam tabel log_aktivitas.</p>";
    }
    
    // Hapus log yang tidak penting dan lebih dari 30 hari
    $delete_old = "DELETE FROM log_aktivitas 
                  WHERE level = 'normal' 
                  AND timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    if (mysqli_query($conn, $delete_old)) {
        $affected_rows = mysqli_affected_rows($conn);
        echo "<p>$affected_rows log aktivitas normal yang berusia lebih dari 30 hari telah dihapus.</p>";
    } else {
        echo "<p>Error saat membersihkan log lama: " . mysqli_error($conn) . "</p>";
    }
    
    echo "<p><a href='log.php' class='bg-primary text-white px-4 py-2 rounded-md mt-4 inline-block'>Kembali ke Log Aktivitas</a></p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?> 