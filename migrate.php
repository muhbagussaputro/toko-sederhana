<?php
// Set zona waktu default ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Header
echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrasi Toko Sederhana</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1, h2 {
            color: #2563eb;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .success {
            color: green;
            padding: 10px;
            margin: 10px 0;
            background-color: #d4edda;
            border-radius: 5px;
        }
        .error {
            color: red;
            padding: 10px;
            margin: 10px 0;
            background-color: #f8d7da;
            border-radius: 5px;
        }
        .warning {
            color: #856404;
            padding: 10px;
            margin: 10px 0;
            background-color: #fff3cd;
            border-radius: 5px;
        }
        .info {
            color: #0c5460;
            padding: 10px;
            margin: 10px 0;
            background-color: #d1ecf1;
            border-radius: 5px;
        }
        button, a.button {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px 10px 0;
        }
        button:hover, a.button:hover {
            background-color: #1d4ed8;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .action-buttons button, .action-buttons a.button {
            flex: 1;
            min-width: 150px;
            text-align: center;
        }
        pre {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .step {
            margin-bottom: 15px;
            padding: 15px;
            border-left: 3px solid #2563eb;
            background-color: #f8fafc;
        }
        .step h3 {
            margin-top: 0;
            color: #2563eb;
        }
        .step-number {
            display: inline-block;
            width: 25px;
            height: 25px;
            line-height: 25px;
            text-align: center;
            background-color: #2563eb;
            color: white;
            border-radius: 50%;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-sync-alt"></i> Migrasi Toko Sederhana</h1>
        <p>Tool ini akan membantu Anda memindahkan proyek Toko Sederhana ke laptop lain dengan mudah.</p>
    </div>';

// Informasi koneksi database
$host = 'localhost';
$username = 'root';
$password = ''; // Password default Laragon biasanya kosong
$database = 'db_toko';

// Direktori backup
$backup_dir = 'db_backup';
$backup_file = $backup_dir . '/db_toko_full_backup.sql';

// Mode dan aksi
$mode = isset($_GET['mode']) ? $_GET['mode'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Fungsi untuk memeriksa keberadaan tool sistem
function checkSystemTool($command) {
    $output = [];
    $return_var = -1;
    
    exec("$command 2>&1", $output, $return_var);
    return $return_var === 0;
}

// Proses Backup Database
if ($mode === 'backup' && $action === 'run') {
    try {
        // Buat direktori backup jika belum ada
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // Gunakan timestamp untuk nama file
        $timestamp = date('Y-m-d_H-i-s');
        $backup_file = $backup_dir . "/db_toko_backup_{$timestamp}.sql";
        
        // Koneksi ke database
        $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Coba menggunakan mysqldump jika tersedia
        $mysqldump_available = checkSystemTool("mysqldump --version");
        
        if ($mysqldump_available) {
            // Gunakan mysqldump command
            $command = "mysqldump -h {$host} -u {$username} --password='{$password}' {$database} > {$backup_file} 2>&1";
            $output = [];
            $return_var = 0;
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                echo '<div class="container success">
                    <p><i class="fas fa-check-circle"></i> Database berhasil dibackup menggunakan mysqldump!</p>
                    <p>File backup: <code>' . $backup_file . '</code></p>
                </div>';
            } else {
                throw new Exception("Mysqldump error: " . implode("\n", $output));
            }
        } else {
            echo '<div class="container warning">
                <p><i class="fas fa-exclamation-triangle"></i> Mysqldump tidak tersedia, menggunakan metode PHP untuk backup...</p>
            </div>';
            
            // Backup menggunakan PHP
            // Dapatkan daftar tabel
            $tables = [];
            $stmt = $pdo->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            // Mulai generate SQL backup
            $sql = "-- Database Backup for {$database}\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Host: {$host}\n";
            $sql .= "-- Database: {$database}\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            // Backup setiap tabel
            foreach ($tables as $table) {
                // Dapatkan create table statement
                $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $create_table = $row['Create Table'];
                
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $create_table . ";\n\n";
                
                // Dapatkan data
                $stmt = $pdo->query("SELECT * FROM `{$table}`");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($rows) > 0) {
                    $sql .= "INSERT INTO `{$table}` VALUES\n";
                    $values = [];
                    
                    foreach ($rows as $row) {
                        $rowValues = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $rowValues[] = 'NULL';
                            } else {
                                $rowValues[] = $pdo->quote($value);
                            }
                        }
                        $values[] = '(' . implode(', ', $rowValues) . ')';
                    }
                    
                    $sql .= implode(",\n", $values) . ";\n\n";
                }
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Simpan ke file
            if (file_put_contents($backup_file, $sql)) {
                echo '<div class="container success">
                    <p><i class="fas fa-check-circle"></i> Database berhasil dibackup menggunakan PHP!</p>
                    <p>File backup: <code>' . $backup_file . '</code></p>
                </div>';
            } else {
                throw new Exception("Gagal menyimpan file backup");
            }
        }
        
        // Tampilkan tombol download
        echo '<div class="container">
            <div class="action-buttons">
                <a href="' . $backup_file . '" download class="button"><i class="fas fa-download"></i> Download File Backup</a>
                <a href="migrate.php" class="button"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
        </div>';
        
    } catch (Exception $e) {
        echo '<div class="container error">
            <p><i class="fas fa-times-circle"></i> Error saat backup database: ' . $e->getMessage() . '</p>
            <a href="migrate.php" class="button"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>';
    }
}
// Proses Restore Database
if ($mode === 'restore' && $action === 'run' && isset($_FILES['backup_file'])) {
    try {
        // Periksa file upload
        if ($_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error pada upload file: " . $_FILES['backup_file']['error']);
        }
        
        // Validasi file
        $file_tmp = $_FILES['backup_file']['tmp_name'];
        $file_name = $_FILES['backup_file']['name'];
        $file_size = $_FILES['backup_file']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Cek ekstensi file
        if ($file_ext !== 'sql') {
            throw new Exception("Hanya file SQL yang diperbolehkan!");
        }
        
        // Cek ukuran file (max 10MB)
        if ($file_size > 10 * 1024 * 1024) {
            throw new Exception("Ukuran file terlalu besar (maksimal 10MB)");
        }
        
        // Baca konten file SQL
        $sql_content = file_get_contents($file_tmp);
        
        // Validasi konten file
        if (strpos($sql_content, 'db_toko') === false && strpos($sql_content, 'Database Backup') === false) {
            throw new Exception("File SQL tidak valid atau bukan backup dari aplikasi Toko Sederhana");
        }
        
        // Koneksi ke server database tanpa memilih database
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo '<div class="container info">
            <p><i class="fas fa-spinner fa-spin"></i> Memulai proses restore database...</p>
        </div>';
        
        // Buat database jika belum ada
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$database`");
        
        // Backup database lama sebelum restore
        $timestamp = date('Y-m-d_H-i-s');
        $pre_restore_backup = $backup_dir . "/pre_restore_backup_{$timestamp}.sql";
        
        try {
            // Eksekusi backup darurat sebelum restore
            $mysqldump_available = checkSystemTool("mysqldump --version");
            
            if ($mysqldump_available) {
                $command = "mysqldump -h {$host} -u {$username} --password='{$password}' {$database} > {$pre_restore_backup} 2>&1";
                exec($command);
                echo '<div class="container info">
                    <p><i class="fas fa-check-circle"></i> Backup darurat sebelum restore dibuat: ' . basename($pre_restore_backup) . '</p>
                </div>';
            }
        } catch (Exception $e) {
            // Lanjutkan proses meskipun backup darurat gagal
            echo '<div class="container warning">
                <p><i class="fas fa-exclamation-triangle"></i> Gagal membuat backup darurat sebelum restore.</p>
            </div>';
        }
        
        // Mulai transaksi database
        $pdo->beginTransaction();
        
        try {
            // Disable foreign key checks
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            
            // Hapus tabel yang ada
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
            }
            
            // Eksekusi SQL restore
            // Pecah SQL menjadi beberapa statement
            $sql_statements = explode(';', $sql_content);
            
            foreach ($sql_statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
            // Enable foreign key checks
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            
            // Commit transaksi
            $pdo->commit();
            
            echo '<div class="container success">
                <p><i class="fas fa-check-circle"></i> Database berhasil di-restore!</p>
                <p>Database <code>' . $database . '</code> telah diperbarui dengan data dari file backup.</p>
                <p><a href="index.php" class="button"><i class="fas fa-home"></i> Kembali ke Halaman Utama</a></p>
            </div>';
            
        } catch (Exception $e) {
            // Rollback jika terjadi error
            $pdo->rollBack();
            throw new Exception("Error saat restore database: " . $e->getMessage());
        }
        
    } catch (Exception $e) {
        echo '<div class="container error">
            <p><i class="fas fa-exclamation-circle"></i> ' . $e->getMessage() . '</p>
            <p><a href="migrate.php" class="button"><i class="fas fa-arrow-left"></i> Kembali</a></p>
        </div>';
    }
}
// Tampilkan halaman utama
else {
    // Dapatkan daftar file backup yang tersedia
    $backup_files = [];
    if (file_exists($backup_dir)) {
        $files = scandir($backup_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $backup_files[] = [
                    'name' => $file,
                    'path' => $backup_dir . '/' . $file,
                    'size' => filesize($backup_dir . '/' . $file),
                    'date' => date('Y-m-d H:i:s', filemtime($backup_dir . '/' . $file))
                ];
            }
        }
    }
    
    // Cek status database
    $db_exists = false;
    $db_tables_count = 0;
    $db_size = 0;
    
    try {
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'");
        $db_exists = $stmt->fetchColumn() !== false;
        
        if ($db_exists) {
            // Dapatkan jumlah tabel
            $pdo->exec("USE `$database`");
            $stmt = $pdo->query("SHOW TABLES");
            $db_tables_count = $stmt->rowCount();
            
            // Dapatkan ukuran database (perkiraan)
            $stmt = $pdo->query("SELECT 
                SUM(data_length + index_length) AS size 
                FROM information_schema.TABLES 
                WHERE table_schema = '$database'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $db_size = $row['size'];
        }
    } catch (PDOException $e) {
        // Gagal koneksi atau error lainnya
    }
    
    // Tampilkan panduan migrasi
    echo '<div class="container">
        <h2><i class="fas fa-info-circle"></i> Panduan Migrasi ke Laptop Lain</h2>
        
        <div class="step">
            <h3><span class="step-number">1</span> Backup Database</h3>
            <p>Buat backup database yang akan dipindahkan ke laptop baru.</p>
            <a href="migrate.php?mode=backup&action=run" class="button"><i class="fas fa-database"></i> Backup Database Sekarang</a>
        </div>
        
        <div class="step">
            <h3><span class="step-number">2</span> Copy Project</h3>
            <p>Copy seluruh folder project ini ke laptop baru. Pastikan semua file termasuk file backup database terbawa.</p>
            <p>Lokasi project saat ini: <code>' . dirname(__FILE__) . '</code></p>
        </div>
        
        <div class="step">
            <h3><span class="step-number">3</span> Install Laragon</h3>
            <p>Install Laragon di laptop baru jika belum ada.</p>
            <p>Download Laragon: <a href="https://laragon.org/download/" target="_blank">https://laragon.org/download/</a></p>
        </div>
        
        <div class="step">
            <h3><span class="step-number">4</span> Pindahkan Project</h3>
            <p>Pindahkan folder project ke direktori <code>C:\laragon\www\</code> di laptop baru.</p>
        </div>
        
        <div class="step">
            <h3><span class="step-number">5</span> Restore Database</h3>
            <p>Buka Laragon, nyalakan MySQL dan Apache, lalu akses <code>http://localhost/toko-sederhana/migrate.php</code></p>
            <p>Upload file backup database dan restore.</p>
            <form action="migrate.php?mode=restore" method="post" enctype="multipart/form-data">
                <input type="file" name="backup_file" accept=".sql" required>
                <button type="submit"><i class="fas fa-upload"></i> Upload & Restore Database</button>
            </form>
        </div>
        
        <div class="step">
            <h3><span class="step-number">6</span> Gunakan Aplikasi</h3>
            <p>Setelah restore selesai, akses aplikasi di <code>http://localhost/toko-sederhana/</code></p>
            <a href="index.php" class="button"><i class="fas fa-home"></i> Buka Aplikasi</a>
        </div>
    </div>';
    
    // Tampilkan informasi status
    echo '<div class="container">
        <h2><i class="fas fa-server"></i> Status Sistem</h2>
        
        <table style="width:100%; border-collapse: collapse; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; width: 40%;"><strong>Database Status:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    ' . ($db_exists ? '<span style="color:green"><i class="fas fa-check-circle"></i> Database ada</span>' : '<span style="color:red"><i class="fas fa-times-circle"></i> Database belum ada</span>') . '
                </td>
            </tr>
            ' . ($db_exists ? '
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Jumlah Tabel:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . $db_tables_count . ' tabel</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Ukuran Database:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . round($db_size / 1024 / 1024, 2) . ' MB</td>
            </tr>' : '') . '
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>File Backup Tersedia:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . count($backup_files) . ' file</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>MySQL Command:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    ' . (checkSystemTool("mysql --version") ? '<span style="color:green"><i class="fas fa-check-circle"></i> Tersedia</span>' : '<span style="color:orange"><i class="fas fa-exclamation-triangle"></i> Tidak tersedia (akan menggunakan PHP)</span>') . '
                </td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>MySQLDump Command:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    ' . (checkSystemTool("mysqldump --version") ? '<span style="color:green"><i class="fas fa-check-circle"></i> Tersedia</span>' : '<span style="color:orange"><i class="fas fa-exclamation-triangle"></i> Tidak tersedia (akan menggunakan PHP)</span>') . '
                </td>
            </tr>
        </table>
    </div>';
    
    // Tampilkan daftar backup yang tersedia
    if (count($backup_files) > 0) {
        echo '<div class="container">
            <h2><i class="fas fa-archive"></i> File Backup Tersedia</h2>
            
            <table style="width:100%; border-collapse: collapse; margin: 15px 0;">
                <tr style="background-color: #f2f2f2;">
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Nama File</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Ukuran</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Tanggal</th>
                    <th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Aksi</th>
                </tr>';
        
        foreach ($backup_files as $file) {
            echo '<tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($file['name']) . '</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . round($file['size'] / 1024, 2) . ' KB</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">' . $file['date'] . '</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">
                    <a href="' . $file['path'] . '" download style="color: #2563eb; margin-right: 10px;"><i class="fas fa-download"></i> Download</a>
                </td>
            </tr>';
        }
        
        echo '</table>
        </div>';
    }
}

// Footer
echo '
    <div style="text-align: center; margin-top: 30px; color: #666;">
        <p>Toko Sederhana &copy; ' . date('Y') . ' - Tool Migrasi Database</p>
    </div>
</body>
</html>';
?> 