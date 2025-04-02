<?php
// Set zona waktu default ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Informasi koneksi database
$host = 'localhost';
$username = 'root';
$password = ''; // Password default Laragon biasanya kosong
$database = 'db_toko';

// Coba koneksi ke MySQL tanpa database dulu
$conn_check = mysqli_connect($host, $username, $password);

// Cek koneksi
if (!$conn_check) {
    die("Koneksi MySQL gagal: " . mysqli_connect_error());
}

// Cek apakah database sudah ada
$db_exists = mysqli_select_db($conn_check, $database);

if (!$db_exists) {
    // Database belum ada, redirect ke setup.php
    header("Location: setup.php");
    exit();
}

mysqli_close($conn_check);

// Buat koneksi ke database yang sudah ada
$conn = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Fungsi untuk mencatat aktivitas user ke dalam log
function logActivity($user_id, $activity) {
    global $conn;
    $timestamp = date('Y-m-d H:i:s');
    
    $query = "INSERT INTO log_aktivitas (user_id, aktivitas, timestamp) 
              VALUES (?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $activity, $timestamp);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Fungsi untuk mengamankan input
function cleanInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Memulai sesi untuk manajemen login jika belum ada sesi aktif
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek cookie untuk fitur "Ingat Saya"
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    // Cari user dengan token yang sesuai
    $token_query = "SELECT id, username, nama, role FROM users WHERE remember_token = ?";
    $token_stmt = mysqli_prepare($conn, $token_query);
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
    }
}

// Jika user adalah penjaga dan ini adalah halaman utama, redirect ke transaksi baru
if (isset($_SESSION['role']) && $_SESSION['role'] == 'penjaga') {
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    
    // Redirect hanya jika di halaman login (bukan di index, jadi penjaga bisa mengakses dashboard)
    if ($current_script == 'login.php' && isset($_SESSION['last_page'])) {
        header("Location: " . $_SESSION['last_page']);
        exit();
    }
}
?> 