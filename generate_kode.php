<?php
// Include koneksi database
require_once 'db.php';

// Hanya tampilkan kode, bukan HTML atau output lain
header('Content-Type: text/plain');

// Hasilkan kode barang otomatis
echo generateKodeBarang();
?> 