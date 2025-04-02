<?php
// Set informasi koneksi database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'db_toko';

// Buat koneksi ke database
$conn = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

// Definisikan array barang yang akan ditambahkan
$barang_list = [
    ['KD001', 'Laptop Asus X441', 5, 8500000.00],
    ['KD002', 'Smartphone Samsung A52', 10, 4200000.00],
    ['KD003', 'Mouse Logitech M170', 20, 120000.00],
    ['KD004', 'Keyboard Mechanical Fantech', 15, 350000.00],
    ['KD005', 'Headset Gaming Rexus', 8, 275000.00],
    ['KD006', 'SSD Kingston 240GB', 12, 430000.00],
    ['KD007', 'USB Flash Drive Sandisk 32GB', 25, 85000.00],
    ['KD008', 'Monitor LG 24 inch', 6, 1750000.00],
    ['KD009', 'Printer Epson L3110', 4, 2100000.00],
    ['KD010', 'Scanner Canon LiDE 300', 3, 1450000.00]
];

// Tambahkan barang ke database
$sukses = 0;
foreach ($barang_list as $barang) {
    // Cek apakah barang sudah ada
    $cek_query = "SELECT * FROM barang WHERE kode = '{$barang[0]}'";
    $cek_result = mysqli_query($conn, $cek_query);
    
    if (mysqli_num_rows($cek_result) == 0) {
        // Tambahkan barang jika belum ada
        $query = "INSERT INTO barang (kode, nama, stok, harga) VALUES ('{$barang[0]}', '{$barang[1]}', {$barang[2]}, {$barang[3]})";
        if (mysqli_query($conn, $query)) {
            $sukses++;
            echo "Berhasil menambahkan barang: {$barang[1]}<br>";
        } else {
            echo "Gagal menambahkan barang {$barang[1]}: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "Barang {$barang[1]} sudah ada di database<br>";
    }
}

echo "<br>Total $sukses barang berhasil ditambahkan ke database.<br>";
echo "<a href='index.php'>Kembali ke halaman utama</a>";

// Tutup koneksi
mysqli_close($conn);
?> 