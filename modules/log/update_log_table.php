<?php
// Include koneksi database
require_once __DIR__ . '/../../db.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // Cek apakah kolom level sudah ada dalam tabel log_aktivitas
    $check_column = "SHOW COLUMNS FROM log_aktivitas LIKE 'level'";
    $stmt = $pdo->query($check_column);
    
    if ($stmt->rowCount() == 0) {
        // Kolom belum ada, tambahkan kolom level
        $add_column = "ALTER TABLE log_aktivitas 
                       ADD COLUMN level ENUM('normal', 'penting') DEFAULT 'normal'";
        
        $pdo->exec($add_column);
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
        
        $pdo->exec($update_important);
        echo "<p>Log aktivitas penting berhasil diupdate.</p>";
    } else {
        echo "<p>Kolom level sudah ada dalam tabel log_aktivitas.</p>";
    }
    
    // Hapus log yang tidak penting dan lebih dari 30 hari
    $delete_old = "DELETE FROM log_aktivitas 
                  WHERE level = 'normal' 
                  AND timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $stmt = $pdo->query($delete_old);
    $affected_rows = $stmt->rowCount();
    echo "<p>$affected_rows log aktivitas normal yang berusia lebih dari 30 hari telah dihapus.</p>";
    
    // Optimasi tambahan: Indeks untuk kolom yang sering digunakan dalam query
    try {
        // Cek apakah indeks pada timestamp sudah ada
        $check_index = "SHOW INDEX FROM log_aktivitas WHERE Key_name = 'idx_timestamp'";
        $stmt_idx = $pdo->query($check_index);
        
        if ($stmt_idx->rowCount() == 0) {
            // Tambahkan indeks pada kolom timestamp
            $add_index = "CREATE INDEX idx_timestamp ON log_aktivitas (timestamp)";
            $pdo->exec($add_index);
            echo "<p>Indeks pada kolom timestamp berhasil ditambahkan.</p>";
        }
        
        // Cek apakah indeks pada level sudah ada
        $check_index = "SHOW INDEX FROM log_aktivitas WHERE Key_name = 'idx_level'";
        $stmt_idx = $pdo->query($check_index);
        
        if ($stmt_idx->rowCount() == 0) {
            // Tambahkan indeks pada kolom level
            $add_index = "CREATE INDEX idx_level ON log_aktivitas (level)";
            $pdo->exec($add_index);
            echo "<p>Indeks pada kolom level berhasil ditambahkan.</p>";
        }
        
        // Cek apakah indeks pada user_id sudah ada
        $check_index = "SHOW INDEX FROM log_aktivitas WHERE Key_name = 'idx_user_id'";
        $stmt_idx = $pdo->query($check_index);
        
        if ($stmt_idx->rowCount() == 0) {
            // Tambahkan indeks pada kolom user_id
            $add_index = "CREATE INDEX idx_user_id ON log_aktivitas (user_id)";
            $pdo->exec($add_index);
            echo "<p>Indeks pada kolom user_id berhasil ditambahkan.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Warning: Tidak dapat membuat indeks: " . $e->getMessage() . "</p>";
    }
    
    echo "<p><a href='log.php' class='bg-primary text-white px-4 py-2 rounded-md mt-4 inline-block'>Kembali ke Log Aktivitas</a></p>";
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?> 