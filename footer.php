        </div>
    </main>
    
    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Toko Sederhana. Hak Cipta Dilindungi.</p>
        </div>
    </footer>

    <!-- Script untuk menu aktif -->
    <script>
        // Menandai menu aktif
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const menuItems = document.querySelectorAll('nav ul li a');
            
            // Cek jika kita berada di halaman utama
            const isIndex = currentPath.endsWith('/index.php') || 
                           currentPath.endsWith('/') || 
                           currentPath === '' ||
                           currentPath.endsWith('toko-sederhana/');
            
            // Cek halaman transaksi dengan lebih spesifik
            const isTransaksiBaru = currentPath.includes('/transaksi/tambah.php');
            const isTransaksiList = currentPath.includes('/transaksi/list.php') || 
                                 currentPath.includes('/transaksi/detail.php');
            
            // Cek jika kita berada di halaman barang
            const isBarang = currentPath.includes('/barang/');
            // Cek jika kita berada di halaman laporan
            const isLaporan = currentPath.includes('/laporan/');
            // Cek jika kita berada di halaman log
            const isLog = currentPath.includes('/log/');
            
            // Reset semua penanda menu
            menuItems.forEach(item => item.classList.remove('bg-primary'));
            
            // Tandai menu yang aktif
            menuItems.forEach(item => {
                const href = item.getAttribute('href');
                const menuText = item.textContent.trim();
                
                if (isIndex && href.includes('index.php')) {
                    item.classList.add('bg-primary');
                } else if (isTransaksiBaru && menuText === 'Transaksi Baru') {
                    item.classList.add('bg-primary');
                } else if (isTransaksiList && menuText === 'Transaksi') {
                    item.classList.add('bg-primary');
                } else if (isBarang && href.includes('barang')) {
                    item.classList.add('bg-primary');
                } else if (isLaporan && href.includes('laporan')) {
                    item.classList.add('bg-primary');
                } else if (isLog && href.includes('log')) {
                    item.classList.add('bg-primary');
                }
            });
            
            // Fungsi konfirmasi hapus
            const deleteLinks = document.querySelectorAll('a[href*="hapus.php"]');
            deleteLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Apakah Anda yakin ingin menghapus item ini?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html> 