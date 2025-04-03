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

// Informasi koneksi database
$host = 'localhost';
$username = 'root';
$password = ''; // Password default Laragon biasanya kosong
$database = 'db_toko';

// Mulai session untuk manajemen login sebelum koneksi database
if (session_status() == PHP_SESSION_NONE) {
    // Parameter session yang lebih aman
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// Fungsi untuk menangani error dan mencatat ke log
function handleError($message, $sql = "", $error_code = 0) {
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
    
    // Tampilkan error user-friendly
    if (isset($GLOBALS['is_development']) && $GLOBALS['is_development']) {
        die("Error Database: $message");
    } else {
        die("Terjadi kesalahan sistem. Silahkan hubungi administrator.");
    }
}

try {
    // Coba koneksi ke MySQL tanpa database dulu
    $conn_check = mysqli_connect($host, $username, $password);
    
    // Cek koneksi
    if (!$conn_check) {
        throw new Exception("Koneksi MySQL gagal: " . mysqli_connect_error(), mysqli_connect_errno());
    }
    
    // Cek apakah database sudah ada
    $db_exists = mysqli_select_db($conn_check, $database);
    
    if (!$db_exists) {
        // Tutup koneksi sebelum redirect
        mysqli_close($conn_check);
        
        // Database belum ada, redirect ke setup.php
        header("Location: setup.php");
        exit();
    }
    
    mysqli_close($conn_check);
    
    // Buat koneksi ke database yang sudah ada dengan karakter set UTF-8
    $conn = mysqli_connect($host, $username, $password, $database);
    
    // Cek koneksi
    if (!$conn) {
        throw new Exception("Koneksi database gagal: " . mysqli_connect_error(), mysqli_connect_errno());
    }
    
    // Set karakter set ke UTF-8
    mysqli_set_charset($conn, 'utf8mb4');
    
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
        $column_result = mysqli_query($conn, $check_column);
        
        if (mysqli_num_rows($column_result) == 0) {
            $add_column = "ALTER TABLE log_aktivitas ADD COLUMN level ENUM('normal', 'penting') DEFAULT 'normal'";
            mysqli_query($conn, $add_column);
        }
        
        $query = "INSERT INTO log_aktivitas (user_id, aktivitas, timestamp, level) 
                  VALUES (?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception("Prepare statement gagal: " . mysqli_error($conn), mysqli_errno($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $activity, $timestamp, $activity_level);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Execute statement gagal: " . mysqli_stmt_error($stmt), mysqli_stmt_errno($stmt));
        }
        
        mysqli_stmt_close($stmt);
        return true;
    } catch (Exception $e) {
        // Hanya log error, tidak menghentikan eksekusi
        error_log("Error log activity: " . $e->getMessage());
        return false;
    }
}

// Fungsi untuk mengamankan input dengan filter sanitasi yang lebih baik
function cleanInput($data, $type = 'string') {
    global $conn;
    
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
            $data = mysqli_real_escape_string($conn, $data);
            return $data;
    }
}

// Cek cookie untuk fitur "Ingat Saya" dengan token yang lebih aman
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    // Cari user dengan token yang sesuai
    $token_query = "SELECT id, username, nama, role FROM users WHERE remember_token = ? AND remember_token_expires > NOW()";
    $token_stmt = mysqli_prepare($conn, $token_query);
    
    if ($token_stmt) {
        mysqli_stmt_bind_param($token_stmt, "s", $token);
        mysqli_stmt_execute($token_stmt);
        $token_result = mysqli_stmt_get_result($token_stmt);
        
        if (mysqli_num_rows($token_result) == 1) {
            $user = mysqli_fetch_assoc($token_result);
            
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
            $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            $update_token = "UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_token);
            
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, "ssi", $new_token, $expires, $user['id']);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                // Perbarui cookie
                setcookie('remember_token', $new_token, time() + (86400 * 7), '/', '', isset($_SERVER['HTTPS']), true);
            }
        } else {
            // Token tidak valid, hapus cookie
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        mysqli_stmt_close($token_stmt);
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
        }
        
        // Eksekusi query
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Execute statement gagal: " . mysqli_stmt_error($stmt), mysqli_stmt_errno($stmt));
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
?> 