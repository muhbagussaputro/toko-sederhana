<?php
// Include koneksi database
require_once __DIR__ . '/../../db.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded-md">
            <p class="font-semibold">Akses Ditolak!</p>
            <p>Anda tidak memiliki hak akses untuk fitur ini.</p>
          </div>';
    exit();
}

// Buffer output untuk mengembalikan HTML yang terformat
ob_start();

try {
    // Tampilkan progress bar
    echo '<div class="relative mb-5">
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div id="progressBar" class="h-full bg-warning rounded-full" style="width: 0%;"></div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>Memulai</span>
                <span id="progressText">0%</span>
                <span>Selesai</span>
            </div>
          </div>';
          
    echo '<div class="space-y-4">';
    
    // Script untuk animasi progres
    echo '<script>
            // Fungsi untuk mengupdate progress bar
            function updateProgress(percent) {
                var progressBar = document.getElementById("progressBar");
                var progressText = document.getElementById("progressText");
                if (progressBar && progressText) {
                    progressBar.style.width = percent + "%";
                    progressText.textContent = percent + "%";
                }
            }
            
            // Update progress awal
            updateProgress(10);
          </script>';
    
    // Cek apakah kolom level sudah ada dalam tabel log_aktivitas
    $check_column = "SHOW COLUMNS FROM log_aktivitas LIKE 'level'";
    $stmt = $pdo->query($check_column);
    
    if ($stmt->rowCount() == 0) {
        // Kolom belum ada, tambahkan kolom level
        $add_column = "ALTER TABLE log_aktivitas 
                       ADD COLUMN level ENUM('normal', 'penting') DEFAULT 'normal'";
        
        $pdo->exec($add_column);
        echo '<div class="bg-green-100 text-green-700 p-3 rounded-md">
                <i class="fas fa-check-circle mr-2"></i>
                Kolom level berhasil ditambahkan ke tabel log_aktivitas.
              </div>';
        
        echo '<script>updateProgress(25);</script>';
        
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
        echo '<div class="bg-green-100 text-green-700 p-3 rounded-md">
                <i class="fas fa-check-circle mr-2"></i>
                Log aktivitas penting berhasil diupdate.
              </div>';
              
        echo '<script>updateProgress(40);</script>';
    } else {
        echo '<div class="bg-blue-100 text-blue-700 p-3 rounded-md">
                <i class="fas fa-info-circle mr-2"></i>
                Kolom level sudah ada dalam tabel log_aktivitas.
              </div>';
              
        echo '<script>updateProgress(40);</script>';
    }
    
    // Hapus log yang tidak penting dan lebih dari 30 hari
    $delete_old = "DELETE FROM log_aktivitas 
                  WHERE level = 'normal' 
                  AND timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $stmt = $pdo->query($delete_old);
    $affected_rows = $stmt->rowCount();
    
    if ($affected_rows > 0) {
        echo '<div class="bg-green-100 text-green-700 p-3 rounded-md flex items-start">
                <i class="fas fa-trash-alt mr-2 mt-1"></i>
                <div>
                    <p class="font-semibold">Pembersihan Log Selesai</p>
                    <p>' . $affected_rows . ' log aktivitas normal yang berusia lebih dari 30 hari telah dihapus.</p>
                </div>
              </div>';
    } else {
        echo '<div class="bg-blue-100 text-blue-700 p-3 rounded-md">
                <i class="fas fa-broom mr-2"></i>
                Tidak ada log normal lama yang perlu dihapus.
              </div>';
    }
    
    echo '<script>updateProgress(60);</script>';
    
    // Optimasi tambahan: Indeks untuk kolom yang sering digunakan dalam query
    try {
        $indices_added = 0;
        
        // Cek apakah indeks pada timestamp sudah ada
        $check_index = "SHOW INDEX FROM log_aktivitas WHERE Key_name = 'idx_timestamp'";
        $stmt_idx = $pdo->query($check_index);
        
        if ($stmt_idx->rowCount() == 0) {
            // Tambahkan indeks pada kolom timestamp
            $add_index = "CREATE INDEX idx_timestamp ON log_aktivitas (timestamp)";
            $pdo->exec($add_index);
            $indices_added++;
        }
        
        // Cek apakah indeks pada level sudah ada
        $check_index = "SHOW INDEX FROM log_aktivitas WHERE Key_name = 'idx_level'";
        $stmt_idx = $pdo->query($check_index);
        
        if ($stmt_idx->rowCount() == 0) {
            // Tambahkan indeks pada kolom level
            $add_index = "CREATE INDEX idx_level ON log_aktivitas (level)";
            $pdo->exec($add_index);
            $indices_added++;
        }
        
        // Cek apakah indeks pada user_id sudah ada
        $check_index = "SHOW INDEX FROM log_aktivitas WHERE Key_name = 'idx_user_id'";
        $stmt_idx = $pdo->query($check_index);
        
        if ($stmt_idx->rowCount() == 0) {
            // Tambahkan indeks pada kolom user_id
            $add_index = "CREATE INDEX idx_user_id ON log_aktivitas (user_id)";
            $pdo->exec($add_index);
            $indices_added++;
        }
        
        if ($indices_added > 0) {
            echo '<div class="bg-green-100 text-green-700 p-3 rounded-md flex items-start">
                    <i class="fas fa-database mr-2 mt-1"></i>
                    <div>
                        <p class="font-semibold">Optimasi Database</p>
                        <p>' . $indices_added . ' indeks database berhasil ditambahkan untuk meningkatkan performa.</p>
                    </div>
                  </div>';
        } else {
            echo '<div class="bg-blue-100 text-blue-700 p-3 rounded-md">
                    <i class="fas fa-check-circle mr-2"></i>
                    Semua indeks database sudah ada dan optimal.
                  </div>';
        }
        
        echo '<script>updateProgress(80);</script>';
    } catch (PDOException $e) {
        echo '<div class="bg-yellow-100 text-yellow-700 p-3 rounded-md">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Peringatan: Tidak dapat membuat indeks: ' . $e->getMessage() . '
              </div>';
    }
    
    // Lakukan optimasi tabel
    try {
        $optimize = "OPTIMIZE TABLE log_aktivitas";
        $pdo->query($optimize);
        
        echo '<div class="bg-green-100 text-green-700 p-3 rounded-md">
                <i class="fas fa-wrench mr-2"></i>
                Tabel log_aktivitas berhasil dioptimalkan.
              </div>';
              
        echo '<script>updateProgress(90);</script>';
    } catch (PDOException $e) {
        echo '<div class="bg-yellow-100 text-yellow-700 p-3 rounded-md">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Peringatan: Tidak dapat mengoptimasi tabel: ' . $e->getMessage() . '
              </div>';
    }
    
    // Ringkasan statistik
    try {
        $stats = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN level = 'penting' THEN 1 ELSE 0 END) as penting,
                SUM(CASE WHEN level = 'normal' THEN 1 ELSE 0 END) as normal,
                MIN(timestamp) as oldest,
                MAX(timestamp) as newest
            FROM log_aktivitas
        ")->fetch();
        
        echo '<div class="bg-gray-100 p-3 rounded-md mt-4">
                <h4 class="font-semibold text-gray-700 mb-2"><i class="fas fa-chart-pie mr-2"></i>Statistik Log Aktivitas</h4>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>Total Log: <span class="font-semibold">' . number_format($stats['total']) . '</span></div>
                    <div>Log Penting: <span class="font-semibold">' . number_format($stats['penting']) . '</span></div>
                    <div>Log Normal: <span class="font-semibold">' . number_format($stats['normal']) . '</span></div>
                    <div>Persentase Penting: <span class="font-semibold">' . 
                        ($stats['total'] > 0 ? round(($stats['penting'] / $stats['total']) * 100, 1) : 0) . '%</span>
                    </div>
                    <div>Log Tertua: <span class="font-semibold">' . 
                        ($stats['oldest'] ? date('d/m/Y', strtotime($stats['oldest'])) : '-') . '</span>
                    </div>
                    <div>Log Terbaru: <span class="font-semibold">' . 
                        ($stats['newest'] ? date('d/m/Y H:i', strtotime($stats['newest'])) : '-') . '</span>
                    </div>
                </div>
              </div>';
    } catch (PDOException $e) {
        // Abaikan error statistik
    }
    
    echo '</div>';
    
    // Tampilkan pesan sukses
    echo '<div class="bg-green-50 border-l-4 border-green-500 p-4 mt-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">
                        Optimasi log aktivitas telah selesai. Data log sudah optimal dan lebih efisien.
                    </p>
                </div>
            </div>
          </div>';
    
    // Catat aktivitas optimasi di log
    logActivity($_SESSION['user_id'], "Melakukan optimasi database log aktivitas", 'penting');
    
    // Update progress bar ke 100%
    echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    updateProgress(100);
                }, 500);
            });
          </script>';
    
} catch (PDOException $e) {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded-md">
            <p class="font-semibold">Error:</p>
            <p>' . $e->getMessage() . '</p>
          </div>';
}

// Mengembalikan output HTML
echo ob_get_clean();
?> 