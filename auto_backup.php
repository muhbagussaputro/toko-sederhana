<?php
/**
 * Script Backup Database Otomatis
 * 
 * File ini digunakan untuk melakukan backup database secara otomatis
 * Dapat dijalankan melalui cron job: php auto_backup.php
 * Contoh setting cron job untuk backup setiap hari jam 12 malam:
 * 0 0 * * * php /path/to/auto_backup.php
 */

// Set zona waktu default ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk mencatat log
function writeLog($message) {
    $log_file = __DIR__ . '/log/backup_log.txt';
    $log_dir = dirname($log_file);
    
    // Buat direktori log jika belum ada
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    echo "[$timestamp] $message" . PHP_EOL;
}

// Konfigurasi database dari file konfigurasi
if (file_exists(__DIR__ . '/db_config.php')) {
    include(__DIR__ . '/db_config.php');
} else {
    // Informasi koneksi database default
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'db_toko';
}

// Direktori backup
$backup_dir = __DIR__ . '/db_backup';

// Buat direktori backup jika belum ada
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
    writeLog("Direktori backup dibuat: $backup_dir");
}

// Gunakan timestamp untuk nama file
$timestamp = date('Y-m-d_H-i-s');
$backup_file = $backup_dir . "/db_auto_backup_{$timestamp}.sql";

// Batas penyimpanan backup (dalam hari)
$retention_days = 7;

try {
    // Coba menggunakan mysqldump terlebih dahulu
    $mysqldump_available = false;
    $output = [];
    $return_var = -1;
    
    exec("mysqldump --version 2>&1", $output, $return_var);
    $mysqldump_available = ($return_var === 0);
    
    if ($mysqldump_available) {
        // Gunakan mysqldump command
        $command = "mysqldump -h {$host} -u {$username} --password='{$password}' {$database} > {$backup_file} 2>&1";
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            writeLog("Database berhasil dibackup menggunakan mysqldump: $backup_file");
        } else {
            throw new Exception("Mysqldump error: " . implode("\n", $output));
        }
    } else {
        // Backup menggunakan PHP
        writeLog("Mysqldump tidak tersedia, menggunakan metode PHP untuk backup...");
        
        try {
            // Koneksi ke database
            $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Dapatkan daftar tabel
            $tables = [];
            $stmt = $pdo->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            // Mulai generate SQL backup
            $sql = "-- Toko Sederhana - Database Backup Otomatis\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Host: {$host}\n";
            $sql .= "-- Database: {$database}\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            // Backup setiap tabel
            foreach ($tables as $table) {
                writeLog("Memproses tabel: $table");
                
                // Dapatkan create table statement
                $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $row = $stmt->fetch(PDO::FETCH_NUM);
                $sql .= "\n\n" . $row[1] . ";\n\n";
                
                // Dapatkan data
                $stmt = $pdo->query("SELECT * FROM `{$table}`");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($rows) > 0) {
                    // Buat insert statement
                    $columns = array_keys($rows[0]);
                    $sql .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES\n";
                    
                    $values = [];
                    foreach ($rows as $row) {
                        $row_values = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $row_values[] = 'NULL';
                            } else {
                                $row_values[] = "'" . addslashes($value) . "'";
                            }
                        }
                        $values[] = "(" . implode(', ', $row_values) . ")";
                    }
                    $sql .= implode(",\n", $values) . ";\n";
                }
            }
            
            $sql .= "\n\nSET FOREIGN_KEY_CHECKS=1;\n";
            
            // Simpan ke file
            file_put_contents($backup_file, $sql);
            writeLog("Database berhasil dibackup menggunakan PHP: $backup_file");
            
        } catch (PDOException $e) {
            throw new Exception("Error koneksi database: " . $e->getMessage());
        }
    }
    
    // Hapus backup lama (melebihi retention period)
    $files = glob($backup_dir . "/db_auto_backup_*.sql");
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= 60 * 60 * 24 * $retention_days) { // lebih dari N hari
                unlink($file);
                writeLog("Menghapus backup lama: " . basename($file));
            }
        }
    }
    
    writeLog("Proses backup selesai!");
    
} catch (Exception $e) {
    writeLog("ERROR: " . $e->getMessage());
}
?> 