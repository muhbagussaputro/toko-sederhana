<?php
// Set zona waktu default ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Error reporting - hanya tampilkan error di development
$is_development = true; // Ubah ke false jika di production
if ($is_development) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}

// Cek keberadaan file konfigurasi database lokal
$config_file = dirname(__FILE__) . '/db_config.php';
$is_custom_config = false;

// Jika ada file konfigurasi lokal, gunakan itu
if (file_exists($config_file)) {
    include($config_file);
    $is_custom_config = true;
} else {
    // Informasi koneksi database default untuk Laragon
    $host = 'localhost';
    $username = 'root';
    $password = ''; // Password default Laragon biasanya kosong
    $database = 'db_coba';
}

// Mulai session untuk manajemen login sebelum koneksi database
if (session_status() == PHP_SESSION_NONE) {
    // Parameter session yang lebih aman
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    // Konfigurasi session timeout - 30 menit tidak aktif
    ini_set('session.gc_maxlifetime', 1800); // 30 menit dalam detik
    session_set_cookie_params(1800);
    
    session_start();
    
    // Mekanisme pengecekan timeout session
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // Session telah timeout, hapus session dan redirect ke login
        session_unset();
        session_destroy();
        
        // Jika ini bukan halaman login, redirect ke login
        $current_script = basename($_SERVER['SCRIPT_NAME']);
        if ($current_script != 'login.php') {
            header('Location: ' . (strpos($_SERVER['REQUEST_URI'], 'modules/') ? '../../login.php' : 'login.php') . '?timeout=1');
            exit;
        }
    }
    
    // Update waktu aktivitas terakhir
    $_SESSION['last_activity'] = time();
}

// Periksa apakah file ini dipanggil dari login.php
$is_called_from_login = (basename($_SERVER['SCRIPT_NAME']) === 'login.php');

// Fungsi untuk menangani error dan mencatat ke log
function handleError($message, $sql = "", $error_code = 0) {
    global $is_called_from_login;
    
    // Log error ke file
    $error_log = dirname(__FILE__) . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
    $log_message = "[$timestamp] User: $user_id - $message";
    
    if (!empty($sql)) {
        $log_message .= " | SQL: $sql";
    }
    
    if ($error_code) {
        $log_message .= " | Code: $error_code";
    }
    
    error_log($log_message . PHP_EOL, 3, $error_log);
    
    // Cek apakah ini error database tidak ditemukan
    if (stripos($message, 'Unknown database') !== false && !$is_called_from_login) {
        // Redirect ke auto_check_db.php untuk menangani pembuatan database otomatis
        header("Location: auto_check_db.php");
        exit();
    }
    
    // Tampilkan error user-friendly
    if (isset($GLOBALS['is_development']) && $GLOBALS['is_development']) {
        die("Error Database: $message");
    } else {
        die("Terjadi kesalahan sistem. Silahkan hubungi administrator.");
    }
}

// Fungsi untuk menghasilkan kode barang otomatis
function generateKodeBarang() {
    global $conn;
    
    // Prefix untuk kode barang
    $prefix = "BRG";
    
    try {
        // Ambil semua kode barang untuk menemukan nomor tertinggi
        $query = "SELECT kode FROM barang";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            // Cari nomor tertinggi dari kode yang ada
            $highest_number = 0;
            
            while ($row = mysqli_fetch_assoc($result)) {
                $code = $row['kode'];
                // Pastikan kode memiliki format yang benar (BRGxxx)
                if (preg_match('/^BRG(\d+)$/', $code, $matches)) {
                    $number = intval($matches[1]);
                    if ($number > $highest_number) {
                        $highest_number = $number;
                    }
                }
            }
            
            // Tambahkan 1 ke nomor tertinggi
            $newNumber = $highest_number + 1;
            
            // Format nomor baru dengan leading zeros
            $newCode = $prefix . sprintf('%03d', $newNumber);
        } else {
            // Belum ada barang, mulai dari 001
            $newCode = $prefix . "001";
        }
        
        return $newCode;
        
    } catch (Exception $e) {
        // Jika terjadi error, gunakan format default
        error_log("Error generating kode barang: " . $e->getMessage());
        return $prefix . "001";
    }
}

// Koneksi database dengan PDO dan MySQLi
try {
    // Coba koneksi ke MySQL tanpa database dulu
    $conn_check = mysqli_connect($host, $username, $password);
    
    // Cek koneksi
    if (!$conn_check) {
        throw new Exception("Koneksi MySQL gagal: " . mysqli_connect_error(), mysqli_connect_errno());
    }
    
    // Cek apakah database sudah ada
    $db_exists = mysqli_select_db($conn_check, $database);
    
    if (!$db_exists && !$is_called_from_login) {
        // Tutup koneksi sebelum redirect
        mysqli_close($conn_check);
        
        // Database belum ada, redirect ke setup.php
        // Gunakan path relatif untuk kompatibilitas dengan domain virtual
        $current_path = $_SERVER['PHP_SELF'];
        $path_parts = pathinfo($current_path);
        $setup_path = rtrim(dirname($current_path), '/') . '/setup.php';
        
        // Redirect ke setup.php dengan path relatif
        header("Location: $setup_path");
        exit();
    }
    
    mysqli_close($conn_check);
    
    // Buat koneksi ke database dengan mysqli
    $conn = mysqli_connect($host, $username, $password, $database);
    
    // Cek koneksi
    if (!$conn) {
        throw new Exception("Koneksi database gagal: " . mysqli_connect_error(), mysqli_connect_errno());
    }
    
    // Set karakter set ke UTF-8
    mysqli_set_charset($conn, 'utf8mb4');
    
    // Buat koneksi menggunakan PDO untuk kompatibilitas
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Jika berhasil terhubung dan belum ada file konfigurasi kustom, buat file
    if (!$is_custom_config) {
        $config_content = "<?php\n";
        $config_content .= "// File konfigurasi database otomatis dibuat oleh sistem\n";
        $config_content .= "// Tanggal pembuatan: " . date('Y-m-d H:i:s') . "\n\n";
        $config_content .= "\$host = '$host';\n";
        $config_content .= "\$username = '$username';\n";
        $config_content .= "\$password = '$password';\n";
        $config_content .= "\$database = '$database';\n";
        $config_content .= "?>";
        
        // Simpan konfigurasi ke file untuk memudahkan migrasi
        file_put_contents($config_file, $config_content);
    }
    
} catch (Exception $e) {
    handleError($e->getMessage(), "", $e->getCode());
}

// Fungsi untuk mencatat aktivitas user ke dalam log
function logActivity($user_id, $activity, $level = 'normal') {
    global $conn;
    
    // Daftar aktivitas yang tidak penting dan bisa diabaikan
    $skip_activities = [
        'Mencari barang dengan kata kunci:', // Skip pencarian biasa
        'Login otomatis via cookie', // Skip login otomatis
        'error', // Skip error umum
        'transaksi' // Skip transaksi umum
    ];
    
    // Cek jika aktivitas dimulai dengan salah satu string yang tidak penting
    foreach ($skip_activities as $skip) {
        if (strpos($activity, $skip) === 0 && $level != 'penting') {
            return true; // Skip logging
        }
    }
    
    // Tentukan level prioritas aktivitas
    $activity_level = $level;
    
    // Deteksi otomatis aktivitas penting
    $important_keywords = ['hapus', 'edit', 'tambah', 'baru', 'transaksi', 'login', 'logout', 'error'];
    foreach ($important_keywords as $keyword) {
        if (stripos($activity, $keyword) !== false) {
            $activity_level = 'penting';
            break;
        }
    }
    
    try {
        $timestamp = date('Y-m-d H:i:s');
        
        // Tambahkan kolom level jika belum ada
        $check_column = "SHOW COLUMNS FROM log_aktivitas LIKE 'level'";
        $check_result = mysqli_query($conn, $check_column);
        
        if (mysqli_num_rows($check_result) == 0) {
            $add_column = "ALTER TABLE log_aktivitas ADD COLUMN level ENUM('normal', 'penting') DEFAULT 'normal'";
            mysqli_query($conn, $add_column);
        }
        
        $query = "INSERT INTO log_aktivitas (user_id, aktivitas, timestamp, level) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $activity, $timestamp, $activity_level);
        mysqli_stmt_execute($stmt);
        
        return true;
    } catch (Exception $e) {
        // Hanya log error, tidak menghentikan eksekusi
        error_log("Error log activity: " . $e->getMessage());
        return false;
    }
}

// Fungsi untuk mengamankan input dengan filter sanitasi yang lebih baik
function cleanInput($data, $type = 'string') {
    // Jika null, kembalikan null
    if ($data === null) {
        return null;
    }
    
    // Trim whitespace
    $data = trim($data);
    
    // Sanitasi berdasarkan tipe
    switch ($type) {
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'email':
            return filter_var($data, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($data, FILTER_SANITIZE_URL);
        case 'html':
            // Menggunakan HTMLPurifier lebih disarankan untuk HTML
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        default:
            // String standar
            $data = stripslashes($data);
            return $data;
    }
}

// Fungsi untuk mengeksekusi query PDO dengan lebih mudah
function pdo_query($query, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        handleError($e->getMessage(), $query, $e->getCode());
        return false;
    }
}

// Fungsi untuk mengambil satu baris hasil query PDO
function pdo_fetch($query, $params = []) {
    $stmt = pdo_query($query, $params);
    return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
}

// Fungsi untuk mengambil semua baris hasil query PDO
function pdo_fetch_all($query, $params = []) {
    $stmt = pdo_query($query, $params);
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

// Cek cookie untuk fitur "Ingat Saya" dengan token yang lebih aman
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    try {
        // Cari user dengan token yang sesuai
        $token_query = "SELECT id, username, nama, role FROM users WHERE remember_token = ? AND remember_token_expires > NOW()";
        $token_stmt = mysqli_prepare($conn, $token_query);
        mysqli_stmt_bind_param($token_stmt, "s", $token);
        mysqli_stmt_execute($token_stmt);
        
        $result = mysqli_stmt_get_result($token_stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Set session dengan data user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            
            // Log aktivitas login otomatis
            $activity = "Login otomatis via cookie";
            logActivity($user['id'], $activity);
            
            // Perbarui token untuk rotasi keamanan
            $new_token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $update_token = "UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_token);
            mysqli_stmt_bind_param($update_stmt, "sss", $new_token, $expires, $user['id']);
            mysqli_stmt_execute($update_stmt);
            
            // Set cookie baru
            setcookie('remember_token', $new_token, strtotime('+30 days'), '/', '', true, true);
        }
    } catch (Exception $e) {
        error_log("Error pada remember me: " . $e->getMessage());
    }
}

// Jika user adalah kasir dan ini adalah halaman utama, redirect ke transaksi baru
if (isset($_SESSION['role']) && $_SESSION['role'] == 'kasir') {
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    
    // Redirect hanya jika di halaman login (bukan di index, jadi kasir bisa mengakses dashboard)
    if ($current_script == 'login.php' && isset($_SESSION['last_page'])) {
        header("Location: " . $_SESSION['last_page']);
        exit();
    }
}

// Fungsi untuk menjalankan query secara aman dengan prepared statement
function safeQuery($sql, $params = [], $types = "") {
    global $conn;
    
    try {
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception("Prepare statement gagal: " . mysqli_error($conn), mysqli_errno($conn));
        }
        
        // Bind parameter jika ada
        if (!empty($params)) {
            // Jika tipe parameter tidak dispesifikasikan, buat tipe otomatis
            if (empty($types)) {
                $types = str_repeat("s", count($params));
            }
            
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
        }
        
        // Dapatkan result jika query adalah SELECT
        if (stripos(trim($sql), 'SELECT') === 0) {
            $result = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);
            return $result;
        } else {
            // Untuk query non-SELECT, kembalikan affected rows
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows;
        }
    } catch (Exception $e) {
        handleError($e->getMessage(), $sql, $e->getCode());
        return false;
    }
}

// Fungsi untuk mencatat log aktivitas
function logallActivity($user_id, $aktivitas, $level = 'info') {
    global $conn;
    try {
        $stmt = mysqli_prepare($conn, "INSERT INTO log_aktivitas (user_id, aktivitas, level) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $aktivitas, $level);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?> 