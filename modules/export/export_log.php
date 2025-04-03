<?php
// Include koneksi database
require_once __DIR__ . '/../../db.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
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

// Tambahkan filter user jika dipilih
if (!empty($user_filter)) {
    $where_conditions[] = "l.user_id = ?";
    $params[] = $user_filter;
}

try {
    // Periksa apakah kolom level ada
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
    
    // Query untuk mendapatkan daftar log aktivitas
    $query = "SELECT l.id, l.aktivitas, l.timestamp, u.nama, u.username, u.role";
    
    // Tambahkan kolom level jika ada
    if ($has_level_column) {
        $query .= ", l.level";
    }
    
    $query .= " FROM log_aktivitas l
               JOIN users u ON l.user_id = u.id
               $where_clause
               ORDER BY l.timestamp DESC";
    
    // Eksekusi query
    $stmt = $pdo->prepare($query);
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i+1, $params[$i]);
    }
    $stmt->execute();
    $activities = $stmt->fetchAll();
    
    // Nama file Excel dengan periode yang dipilih
    $filename = 'Log_Aktivitas_' . date('Y-m-d', strtotime($tgl_mulai)) . '_sd_' . date('Y-m-d', strtotime($tgl_selesai));
    $nama_toko = "Toko Sederhana";
    
    // Catat aktivitas ekspor di log
    logActivity($_SESSION['user_id'], "Mengekspor log aktivitas periode " . date('d/m/Y', strtotime($tgl_mulai)) . " - " . date('d/m/Y', strtotime($tgl_selesai)), 'penting');
    
    // Set header untuk download file Excel
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename.xls\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // Output konten sebagai HTML yang akan diinterpretasikan sebagai Excel
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Export Log Aktivitas</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
            }
            .header {
                font-size: 16px;
                font-weight: bold;
                text-align: center;
                margin-bottom: 5px;
            }
            .subheader {
                font-size: 14px;
                font-weight: bold;
                text-align: center;
                margin-bottom: 10px;
            }
            .periode {
                text-align: center;
                margin-bottom: 15px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            tr.penting {
                background-color: #fff8e1;
            }
            .summary {
                margin-top: 15px;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <!-- Header toko dan laporan -->
        <div class="header">' . htmlspecialchars($nama_toko) . '</div>
        <div class="subheader">Log Aktivitas Sistem</div>
        <div class="periode">Periode: ' . date('d/m/Y', strtotime($tgl_mulai)) . ' - ' . date('d/m/Y', strtotime($tgl_selesai)) . '</div>
        <div>Laporan dibuat pada: ' . date('d/m/Y H:i:s') . '</div>
        <div>Dibuat oleh: ' . htmlspecialchars($_SESSION['nama']) . ' (' . htmlspecialchars($_SESSION['role']) . ')</div>';
    
    // Tambahkan filter jika ada
    if (!empty($level_filter) || !empty($search) || !empty($user_filter)) {
        echo '<div class="filter-info">Filter:';
        
        if (!empty($level_filter)) {
            echo ' Level: <b>' . htmlspecialchars(ucfirst($level_filter)) . '</b>';
        }
        
        if (!empty($search)) {
            echo ' Pencarian: <b>' . htmlspecialchars($search) . '</b>';
        }
        
        if (!empty($user_filter)) {
            // Dapatkan nama user
            $user_stmt = $pdo->prepare("SELECT nama, username FROM users WHERE id = ?");
            $user_stmt->execute([$user_filter]);
            $user_data = $user_stmt->fetch();
            
            if ($user_data) {
                echo ' Pengguna: <b>' . htmlspecialchars($user_data['nama']) . ' (' . htmlspecialchars($user_data['username']) . ')</b>';
            }
        }
        
        echo '</div>';
    }
    
    // Tabel log aktivitas
    echo '<table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Waktu</th>
                    <th>ID</th>
                    <th>Pengguna</th>
                    <th>Role</th>';
                    
    if ($has_level_column) {
        echo '<th>Level</th>';
    }
    
    echo '      <th>Aktivitas</th>
                </tr>
            </thead>
            <tbody>';
    
    $no = 1;
    foreach ($activities as $activity) {
        // Class untuk row penting
        $row_class = (isset($activity['level']) && $activity['level'] == 'penting') ? ' class="penting"' : '';
        
        echo '<tr' . $row_class . '>
                <td>' . $no++ . '</td>
                <td>' . date('d/m/Y H:i:s', strtotime($activity['timestamp'])) . '</td>
                <td>' . $activity['id'] . '</td>
                <td>' . htmlspecialchars($activity['nama']) . ' (' . htmlspecialchars($activity['username']) . ')</td>
                <td>' . htmlspecialchars($activity['role']) . '</td>';
                
        if ($has_level_column) {
            echo '<td>' . (isset($activity['level']) ? htmlspecialchars($activity['level']) : 'normal') . '</td>';
        }
        
        echo '  <td>' . htmlspecialchars($activity['aktivitas']) . '</td>
              </tr>';
    }
    
    echo '</tbody>
        </table>';
    
    // Statistik ringkasan
    echo '<div class="summary">Jumlah Total Log: ' . count($activities) . '</div>';
    
    // Jika ada kolom level, hitung jumlah untuk masing-masing level
    if ($has_level_column) {
        $normal_count = 0;
        $important_count = 0;
        
        foreach ($activities as $activity) {
            if (isset($activity['level']) && $activity['level'] == 'penting') {
                $important_count++;
            } else {
                $normal_count++;
            }
        }
        
        echo '<div>Log Penting: ' . $important_count . '</div>';
        echo '<div>Log Normal: ' . $normal_count . '</div>';
    }
    
    echo '</body>
    </html>';
    
} catch (PDOException $e) {
    // Log error
    error_log("Error export log: " . $e->getMessage());
    
    // Tampilkan pesan error jika debug aktif
    die("Terjadi kesalahan saat mengekspor data log. Silakan coba lagi atau hubungi administrator.");
}
?> 