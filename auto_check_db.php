<?php
/**
 * File untuk pengecekan otomatis database
 * Include file ini di halaman-halaman penting untuk memastikan database selalu tersedia
 */

// Set zona waktu default ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Aktifkan output buffer untuk mencegah masalah header
ob_start();

// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Periksa apakah file ini dipanggil langsung atau diinclude
$is_direct_access = (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__));
$is_called_from_login = (basename($_SERVER['SCRIPT_NAME']) === 'login.php');

// Informasi koneksi database
$host = 'localhost';
$username = 'root';
$password = ''; // Password default Laragon biasanya kosong
$database = 'db_toko';

// Cek file konfigurasi kustom jika ada
$config_file = dirname(__FILE__) . '/db_config.php';
if (file_exists($config_file)) {
    include($config_file);
}

try {
    // Koneksi ke MySQL tanpa memilih database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    writeLog("Koneksi ke MySQL berhasil");
    
    // Cek apakah database sudah ada
    $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'";
    $db_exists = $pdo->query($query)->fetchColumn();
    
    if (!$db_exists) {
        // Buat database baru
        writeLog("Database '$database' tidak ditemukan, membuat database baru...");
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        writeLog("Database '$database' berhasil dibuat");
        
        // Pilih database yang baru dibuat
        $pdo->exec("USE `$database`");
        
        // Buat tabel users
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
        writeLog("Tabel users berhasil dibuat");
        
        // Buat tabel barang
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
        writeLog("Tabel barang berhasil dibuat");
        
        // Tambahkan data barang contoh
        $pdo->exec("INSERT INTO barang (kode, nama, stok, harga) VALUES 
            ('BRG001', 'Buku Tulis', 100, 5000.00),
            ('BRG002', 'Pensil', 200, 2000.00),
            ('BRG003', 'Penghapus', 150, 1500.00),
            ('BRG004', 'Rautan', 100, 3000.00),
            ('BRG005', 'Penggaris', 50, 4000.00)");
        writeLog("Data contoh barang berhasil ditambahkan");
        
        // Buat tabel transaksi
        $sql_create_transaksi = "CREATE TABLE IF NOT EXISTS transaksi (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tanggal DATETIME NOT NULL,
            user_id INT NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql_create_transaksi);
        writeLog("Tabel transaksi berhasil dibuat");
        
        // Buat tabel transaksi_detail
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
        writeLog("Tabel transaksi_detail berhasil dibuat");
        
        // Buat tabel log_aktivitas
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
        writeLog("Tabel log_aktivitas berhasil dibuat");
        
        // Buat user admin dan kasir default
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama, role, email) VALUES (?, ?, ?, 'admin', ?)");
        $stmt->execute(['admin', $admin_password, 'Administrator', 'admin@example.com']);
        writeLog("User admin default berhasil dibuat");
        
        $kasir_password = password_hash('kasir123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama, role, email) VALUES (?, ?, ?, 'kasir', ?)");
        $stmt->execute(['kasir', $kasir_password, 'Kasir Toko', 'kasir@example.com']);
        writeLog("User kasir default berhasil dibuat");
        
        // Buat file db_config.php untuk menyimpan konfigurasi
        $config_file = __DIR__ . '/db_config.php';
        $config_content = "<?php\n";
        $config_content .= "// File konfigurasi database otomatis dibuat oleh sistem\n";
        $config_content .= "// Tanggal pembuatan: " . date('Y-m-d H:i:s') . "\n\n";
        $config_content .= "\$host = '$host';\n";
        $config_content .= "\$username = '$username';\n";
        $config_content .= "\$password = '$password';\n";
        $config_content .= "\$database = '$database';\n";
        $config_content .= "?>";
        
        file_put_contents($config_file, $config_content);
        writeLog("File konfigurasi database berhasil dibuat");
        
        // Set session untuk menunjukkan database baru dibuat
        session_start();
        $_SESSION['db_just_created'] = true;
    } else {
        // Database sudah ada, periksa apakah tabel users ada
        $pdo->exec("USE `$database`");
        
        // Cek tabel users
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() == 0) {
            // Tabel users belum ada, redirect ke setup
            writeLog("Database ada tetapi tabel users tidak ditemukan, perlu setup");
            if (!$is_called_from_login && $is_direct_access) {
                header("Location: setup.php");
                exit;
            }
        }
        
        writeLog("Database dan tabel sudah ada");
    }
    
    // Redirect ke login.php hanya jika file ini diakses langsung (bukan include)
    // dan tidak dipanggil dari login.php
    if (!$is_called_from_login && $is_direct_access) {
        header("Location: login.php");
        exit;
    }
    
} catch (PDOException $e) {
    writeLog("Error pada auto_check_db: " . $e->getMessage(), "error");
    
    // Tampilkan error hanya jika file ini diakses langsung
    if ($is_direct_access) {
        // Cek apakah error karena database tidak ditemukan
        if (strpos($e->getMessage(), "Unknown database") !== false) {
            echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 5px;">
                <strong>Error Database:</strong> Database tidak ditemukan. Sistem akan mencoba membuat database otomatis...
                <meta http-equiv="refresh" content="3;url=setup.php">
            </div>';
        } else {
            echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; margin: 15px 0; border-radius: 5px;">
                <strong>Error:</strong> ' . $e->getMessage() . '
                <br>Silakan cek konfigurasi database atau <a href="setup.php">klik di sini</a> untuk setup ulang.
            </div>';
        }
    }
}

// Akhiri output buffer hanya jika file ini diakses langsung
if ($is_direct_access) {
    ob_end_flush();
} else {
    ob_clean(); // Bersihkan buffer tanpa mengirimkan output
}
?>