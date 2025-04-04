<?php
// Mulai sesi untuk pengecekan
session_start();

// Set zona waktu default ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Tambahkan header untuk mencegah caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Memulai output buffering untuk mencegah masalah header
ob_start();

// Cek apakah database baru saja dibuat dari auto_check_db.php
if (isset($_SESSION['db_just_created']) && $_SESSION['db_just_created'] === true) {
    // Hapus flag
    unset($_SESSION['db_just_created']);
    
    // Redirect ke login
    header("Location: login.php?setup=success");
    exit();
}

// Tentukan variabel konfigurasi di awal
$host = 'localhost';
$username = 'root';
$password = ''; // Password default Laragon biasanya kosong
$database = 'db_toko';
$backup_dir = 'db_backup';
$backup_file = $backup_dir . '/db_toko_backup.sql';

// Fungsi untuk mencatat log
function writeLog($message, $type = 'info') {
    $log_file = __DIR__ . '/log/setup_log.txt';
    $log_dir = dirname($log_file);
    
    // Buat direktori log jika belum ada
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp][$type] $message" . PHP_EOL, FILE_APPEND);
}

// Fungsi untuk memeriksa keberadaan tool sistem
function checkSystemTool($command) {
    $output = [];
    $return_var = -1;
    
    exec("$command 2>&1", $output, $return_var);
    return $return_var === 0;
}

// Dapatkan daftar file backup yang tersedia
$backup_files = [];
if (file_exists($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backup_files[] = $file;
        }
    }
}

// Tampilkan HTML awal
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Toko Sederhana</title>
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
        .step {
            margin-bottom: 10px;
            padding: 10px;
            border-left: 3px solid #2563eb;
            background-color: #f0f7ff;
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
            margin-top: 10px;
        }
        button:hover, a.button:hover {
            background-color: #1d4ed8;
        }
        pre {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .progress-container {
            width: 100%;
            background-color: #ddd;
            border-radius: 5px;
            margin: 20px 0;
        }
        .progress-bar {
            height: 20px;
            border-radius: 5px;
            background-color: #2563eb;
            width: 0%;
            transition: width 0.5s;
            text-align: center;
            color: white;
            line-height: 20px;
        }
        .checkmark {
            color: green;
            margin-right: 5px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .action-buttons button, .action-buttons a.button {
            flex: 1;
            min-width: 150px;
            text-align: center;
        }
        #setupLog {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #f9f9f9;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-store"></i> Setup Toko Sederhana</h1>
        <div class="progress-container">
            <div id="progressBar" class="progress-bar" style="width: 0%;">0%</div>
        </div>
        <div id="setupLog"></div>
    </div>

<script>
function updateProgress(percent, message) {
    let progressBar = document.getElementById("progressBar");
    if (progressBar) {
        progressBar.style.width = percent + "%";
        progressBar.innerHTML = percent + "%";
    }
    
    if (message) {
        let log = document.getElementById("setupLog");
        if (log) {
            log.innerHTML += "<div class=\"step\"><span class=\"checkmark\">✓</span> " + message + "</div>";
            log.scrollTop = log.scrollHeight;
        }
    }
}

function createFromScratch() {
    document.getElementById("setupOptions").style.display = "block";
    document.getElementById("backupOptions").style.display = "none";
}

function useBackup() {
    document.getElementById("setupOptions").style.display = "none";
    document.getElementById("backupOptions").style.display = "block";
}
</script>

<?php
// Cek jika ada request untuk ekspor database
if (isset($_GET['export_db'])) {
    try {
        writeLog("Memulai proses backup database");
        
        // Koneksi ke database
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            writeLog("Koneksi ke MySQL berhasil");
        } catch (PDOException $e) {
            die("<div class='error'><strong>Gagal koneksi ke MySQL:</strong> " . $e->getMessage() . "</div>");
        }
        
        
        // Buat direktori backup jika belum ada
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
            writeLog("Membuat direktori backup: $backup_dir");
        }
        
        // Eksekusi mysqldump melalui shell command
        $date = date('Y-m-d_H-i-s');
        $backup_file = $backup_dir . "/db_toko_backup_{$date}.sql";
        $mysql_dump_success = false;
        
        // Coba menggunakan mysqldump command jika tersedia
        if (checkSystemTool("mysqldump --version")) {
            writeLog("Mencoba backup dengan mysqldump");
            $command = "mysqldump -h {$host} -u {$username} --password='{$password}' {$database} > {$backup_file} 2>&1";
            $output = [];
            $return_var = 0;
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                echo '<div class="success"><i class="fas fa-check-circle"></i> Database berhasil dibackup ke: ' . $backup_file . '</div>';
                $mysql_dump_success = true;
                writeLog("Backup dengan mysqldump berhasil: $backup_file");
            } else {
                writeLog("Mysqldump gagal dengan kode: $return_var, output: " . implode("\n", $output), "error");
            }
        }
        
        if (!$mysql_dump_success) {
            echo '<div class="warning"><i class="fas fa-exclamation-triangle"></i> Mysqldump tidak tersedia atau gagal, menggunakan metode PHP untuk backup...</div>';
            writeLog("Menggunakan metode PHP untuk backup");
            
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
                writeLog("Memproses tabel: $table");
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
                echo '<div class="success"><i class="fas fa-check-circle"></i> Database berhasil dibackup menggunakan PHP!</div>';
                writeLog("Backup dengan PHP berhasil: $backup_file");
            } else {
                throw new Exception("Gagal menyimpan file backup");
            }
        }
        
        // Tampilkan tombol download dan kembali
        echo '<div class="container">';
        echo '<div class="success">Backup database berhasil! Anda dapat memindahkan file ini ke laptop lain.</div>';
        echo '<div class="action-buttons">';
        echo '<a href="' . $backup_file . '" download class="button"><i class="fas fa-download"></i> Download Backup File</a>';
        echo '<a href="setup.php" class="button"><i class="fas fa-arrow-left"></i> Kembali</a>';
        echo '</div></div>';
        
    } catch (Exception $e) {
        writeLog("Error saat backup database: " . $e->getMessage(), "error");
        echo '<div class="error"><i class="fas fa-times-circle"></i> Error saat backup database: ' . $e->getMessage() . '</div>';
        echo '<a href="setup.php" class="button"><i class="fas fa-arrow-left"></i> Kembali</a>';
    }
    
    // Akhiri output buffer dan keluar
    ob_end_flush();
    exit();
}

// Cek jika ada request untuk import database
if (isset($_GET['import_db']) && isset($_FILES['sql_file'])) {
    try {
        writeLog("Memulai proses import database");
        // Buat direktori backup jika belum ada
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
            writeLog("Membuat direktori backup: $backup_dir");
        }
        
        // Validasi file yang diupload
        if ($_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error upload file: " . $_FILES['sql_file']['error']);
        }
        
        $file_tmp = $_FILES['sql_file']['tmp_name'];
        $file_name = basename($_FILES['sql_file']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if ($file_ext !== 'sql') {
            throw new Exception("Hanya file SQL yang diperbolehkan");
        }
        
        $target_file = $backup_dir . '/' . $file_name;
        
        // Upload file
        if (move_uploaded_file($file_tmp, $target_file)) {
            writeLog("File SQL berhasil diupload: $target_file");
            // Koneksi ke MySQL tanpa database
            $pdo = new PDO("mysql:host=$host", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Buat database jika belum ada
            try {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                echo '<div class="success">Database berhasil dibuat.</div>';
                writeLog("Database $database berhasil dibuat");
            } catch (PDOException $e) {
                echo '<div class="error">Gagal membuat database: ' . $e->getMessage() . '</div>';
                writeLog("Gagal membuat database: " . $e->getMessage(), "error");
                exit();
            }
            
            // $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Pilih database
            $pdo->exec("USE `$database`");
            
            // Backup database sebelum import jika sudah ada
            try {
                $stmt = $pdo->query("SHOW TABLES");
                if ($stmt->rowCount() > 0) {
                    $backup_before_import = $backup_dir . "/pre_import_backup_" . date('Y-m-d_H-i-s') . ".sql";
                    if (checkSystemTool("mysqldump --version")) {
                        $command = "mysqldump -h {$host} -u {$username} --password='{$password}' {$database} > {$backup_before_import} 2>&1";
                        exec($command);
                        writeLog("Backup database sebelum import: $backup_before_import");
                    }
                }
            } catch (Exception $e) {
                writeLog("Warning: Tidak dapat backup database sebelum import: " . $e->getMessage(), "warning");
            }
            
            // Disable foreign key checks dan mulai transaksi
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
            $pdo->beginTransaction();
            
            try {
                // Baca file SQL
                $sql = file_get_contents($target_file);
                
                // Pisahkan statements
                $statements = explode(';', $sql);
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }
                
                // Commit transaksi
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
                $pdo->commit();
                
                echo '<div class="success"><i class="fas fa-check-circle"></i> Database berhasil diimport!</div>';
                echo '<a href="index.php" class="button">Mulai Menggunakan Aplikasi</a>';
                writeLog("Import database berhasil");
            } catch (Exception $e) {
                // Rollback jika ada error
                $pdo->rollBack();
                throw new Exception("Error saat mengeksekusi SQL: " . $e->getMessage());
            }
        } else {
            throw new Exception("Gagal mengupload file SQL");
        }
        
        // Akhiri output buffer dan keluar
        ob_end_flush();
        exit();
    } catch (Exception $e) {
        writeLog("Error saat import database: " . $e->getMessage(), "error");
        echo '<div class="error"><i class="fas fa-times-circle"></i> Error saat import database: ' . $e->getMessage() . '</div>';
        echo '<a href="setup.php" class="button"><i class="fas fa-arrow-left"></i> Kembali</a>';
        
        // Akhiri output buffer dan keluar
        ob_end_flush();
        exit();
    }
}

// Cek apakah database sudah ada dan berfungsi
$db_exists = false;
$db_ready = false;
try {
    writeLog("Memeriksa status database");
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Cek database
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'");
    $db_exists = $stmt->fetchColumn() !== false;
    
    if ($db_exists) {
        writeLog("Database $database sudah ada");
        // Cek apakah tabel users ada dan memiliki data
        $pdo->exec("USE `$database`");
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        $table_exists = $stmt->rowCount() > 0;
        
        if ($table_exists) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $has_users = $stmt->fetchColumn() > 0;
            
            if ($has_users) {
                $db_ready = true;
                writeLog("Database sudah siap dengan user yang terdaftar");
            }
        }
    }
} catch (PDOException $e) {
    writeLog("Error saat memeriksa database: " . $e->getMessage(), "error");
}

// Jika database sudah siap, tampilkan menu utama
if ($db_ready) {
    echo '<div class="container">';
    echo '<div class="success"><i class="fas fa-check-circle"></i> Database sudah siap digunakan!</div>';
    echo '<div class="action-buttons">';
    echo '<a href="index.php" class="button"><i class="fas fa-home"></i> Mulai Menggunakan Aplikasi</a>';
    echo '<a href="?export_db" class="button"><i class="fas fa-download"></i> Backup Database</a>';
    echo '</div>';
    
    // Form import database
    echo '<div style="margin-top: 20px;">';
    echo '<h2><i class="fas fa-upload"></i> Import Database dari Backup</h2>';
    echo '<form action="?import_db" method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="sql_file" accept=".sql" required>';
    echo '<button type="submit"><i class="fas fa-upload"></i> Import Database</button>';
    echo '</form>';
    echo '</div>';
    
    // Tampilkan daftar backup yang tersedia
    if (count($backup_files) > 0) {
        echo '<div style="margin-top: 20px;">';
        echo '<h2><i class="fas fa-archive"></i> File Backup Tersedia</h2>';
        echo '<ul>';
        foreach ($backup_files as $file) {
            echo '<li>' . $file . ' <a href="' . $backup_dir . '/' . $file . '" download><i class="fas fa-download"></i> Download</a></li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // Tambahkan footer dan akhiri halaman
    echo '</body></html>';
    
    // Akhiri output buffer dan keluar
    ob_end_flush();
    exit();
}

// Jika database belum siap, cek apakah ada backup yang bisa digunakan
if (count($backup_files) > 0) {
    echo '<div class="container">';
    echo '<div class="warning"><i class="fas fa-exclamation-triangle"></i> Database belum ada atau belum lengkap, tetapi file backup tersedia.</div>';
    echo '<div class="action-buttons">';
    echo '<button onclick="createFromScratch()"><i class="fas fa-plus-circle"></i> Buat Database Baru</button>';
    echo '<button onclick="useBackup()"><i class="fas fa-upload"></i> Gunakan Backup</button>';
    echo '</div>';
    
    echo '<div id="backupOptions" style="display:none; margin-top: 20px;">';
    echo '<h2><i class="fas fa-archive"></i> Pilih File Backup</h2>';
    echo '<form action="?import_db" method="post" enctype="multipart/form-data">';
    echo '<select name="backup_file" style="width: 100%; margin-bottom: 10px; padding: 8px;">';
    foreach ($backup_files as $file) {
        echo '<option value="' . $backup_dir . '/' . $file . '">' . $file . '</option>';
    }
    echo '</select>';
    echo '<button type="submit"><i class="fas fa-upload"></i> Import File Backup</button>';
    echo '</form>';
    echo '<p>Atau upload file backup baru:</p>';
    echo '<form action="?import_db" method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="sql_file" accept=".sql" required>';
    echo '<button type="submit"><i class="fas fa-upload"></i> Upload & Import</button>';
    echo '</form>';
    echo '</div>';
    
    echo '<div id="setupOptions" style="display:none;">';
} else {
    echo '<div class="container">';
    echo '<div id="setupOptions">';
}

// Proses setup database baru
try {
    // Update progress di client melalui JavaScript
    echo '<script>updateProgress(10, "Memeriksa koneksi ke MySQL server...");</script>';
    // Flush output buffer untuk mengirim JavaScript ke browser
    flush();
    
    // Koneksi ke MySQL tanpa database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    writeLog("Koneksi ke MySQL berhasil");
    
    // Buat database jika belum ada
    echo '<script>updateProgress(20, "Memeriksa keberadaan database...");</script>';
    flush();
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    writeLog("Database $database dibuat atau sudah ada");
    
    // Pilih database
    echo '<script>updateProgress(30, "Memilih database...");</script>';
    flush();
    $pdo->exec("USE `$database`");
    
    // Buat tabel users dengan kolom tambahan untuk keamanan
    echo '<script>updateProgress(40, "Membuat struktur tabel users...");</script>';
    flush();
    $sql_create_users = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        nama VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE,
        role ENUM('admin', 'kasir') NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        remember_token VARCHAR(64) NULL,
        token_expires DATETIME NULL,
        last_login DATETIME NULL,
        failed_login_attempts INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_create_users);
    writeLog("Tabel users dibuat");
    
    // Buat tabel barang
    echo '<script>updateProgress(50, "Membuat struktur tabel barang...");</script>';
    flush();
    $sql_create_barang = "CREATE TABLE IF NOT EXISTS barang (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kode VARCHAR(20) NOT NULL UNIQUE,
        nama VARCHAR(100) NOT NULL,
        stok INT NOT NULL DEFAULT 0,
        harga DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_create_barang);
    writeLog("Tabel barang dibuat");
    
    // Tambahkan beberapa barang contoh dengan kode dimulai dari BRG001
    echo '<script>updateProgress(55, "Menambahkan data barang contoh...");</script>';
    flush();
    
    // Cek apakah tabel barang sudah memiliki data
    $stmt = $pdo->query("SELECT COUNT(*) FROM barang");
    $has_barang = $stmt->fetchColumn() > 0;
    
    if (!$has_barang) {
        $pdo->exec("INSERT INTO barang (kode, nama, stok, harga) VALUES 
            ('BRG001', 'Buku Tulis', 100, 5000.00),
            ('BRG002', 'Pensil', 200, 2000.00),
            ('BRG003', 'Penghapus', 150, 1500.00),
            ('BRG004', 'Rautan', 100, 3000.00),
            ('BRG005', 'Penggaris', 50, 4000.00)");
        writeLog("Data contoh barang ditambahkan");
    } else {
        writeLog("Data barang sudah ada, lewati penambahan data contoh");
    }
    
    echo '<script>updateProgress(57, "Tabel barang berhasil dibuat!");</script>';
    flush();
    
    // Buat tabel transaksi
    echo '<script>updateProgress(60, "Membuat struktur tabel transaksi...");</script>';
    flush();
    $sql_create_transaksi = "CREATE TABLE IF NOT EXISTS transaksi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tanggal DATETIME NOT NULL,
        user_id INT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_create_transaksi);
    writeLog("Tabel transaksi dibuat");
    
    // Buat tabel transaksi_detail
    echo '<script>updateProgress(70, "Membuat struktur tabel transaksi_detail...");</script>';
    flush();
    $sql_create_transaksi_detail = "CREATE TABLE IF NOT EXISTS transaksi_detail (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaksi_id INT NOT NULL,
        barang_id INT NOT NULL,
        jumlah INT NOT NULL,
        harga DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (transaksi_id) REFERENCES transaksi(id),
        FOREIGN KEY (barang_id) REFERENCES barang(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_create_transaksi_detail);
    writeLog("Tabel transaksi_detail dibuat");
    
    // Buat tabel log_aktivitas
    echo '<script>updateProgress(80, "Membuat struktur tabel log_aktivitas...");</script>';
    flush();
    $sql_create_log = "CREATE TABLE IF NOT EXISTS log_aktivitas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        aktivitas TEXT NOT NULL,
        level ENUM('normal', 'penting', 'warning', 'info') DEFAULT 'normal',
        ip_address VARCHAR(45),
        user_agent TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_create_log);
    writeLog("Tabel log_aktivitas dibuat");
    
    // Buat user admin default jika belum ada
    echo '<script>updateProgress(90, "Memeriksa dan membuat user admin default...");</script>';
    flush();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama, role, email) VALUES (?, ?, ?, 'admin', ?)");
        $stmt->execute(['admin', $admin_password, 'Administrator', 'admin@example.com']);
        writeLog("User admin default berhasil dibuat");
        echo '<script>updateProgress(95, "User admin default berhasil dibuat...");</script>';
        flush();
    }
    
    // Buat user kasir default jika belum ada
    echo '<script>updateProgress(97, "Memeriksa dan membuat user kasir default...");</script>';
    flush();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'kasir'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $kasir_password = password_hash('kasir123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama, role, email) VALUES (?, ?, ?, 'kasir', ?)");
        $stmt->execute(['kasir', $kasir_password, 'Kasir Toko', 'kasir@example.com']);
        writeLog("User kasir default berhasil dibuat");
        echo '<script>updateProgress(99, "User kasir default berhasil dibuat...");</script>';
        flush();
    }
    
    echo '<script>updateProgress(100, "Setup database selesai!");</script>';
    flush();
    
    echo '<div class="success" style="margin-top: 20px;">
        <h2><i class="fas fa-check-circle"></i> Setup Database Berhasil!</h2>
        <p>Semua tabel telah dibuat dan siap digunakan.</p>
        <p><strong>User Admin:</strong><br>
        Username: admin<br>
        Password: admin123</p>
        <p><strong>User Kasir:</strong><br>
        Username: kasir<br>
        Password: kasir123</p>
        <p><strong class="warning">PENTING: Segera ganti password admin dan kasir setelah login pertama kali!</strong></p>
        <div class="action-buttons">
            <a href="login.php" class="button"><i class="fas fa-sign-in-alt"></i> Login Sekarang</a>
            <a href="?export_db" class="button"><i class="fas fa-download"></i> Backup Database</a>
        </div>
    </div>';
    writeLog("Setup database selesai dengan sukses");
    
} catch(PDOException $e) {
    writeLog("Error setup database: " . $e->getMessage(), "error");
    echo '<div class="error">
        <h2><i class="fas fa-exclamation-triangle"></i> Error!</h2>
        <p>' . $e->getMessage() . '</p>
        <p>Silakan periksa pengaturan koneksi database atau hubungi administrator.</p>
    </div>';
}

echo '</div></div>';
?>
</body>
</html>
<?php
// Akhiri output buffer
ob_end_flush();
?> 