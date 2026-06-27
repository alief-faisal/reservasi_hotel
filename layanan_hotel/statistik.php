<?php
session_start();
if (!isset($_SESSION['peran']) || $_SESSION['peran'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

$query_stats = "SELECT h.id_hotel, h.nama_hotel,
                    COUNT(DISTINCT b.id_pemesanan) as jumlah_pesanan
                FROM hotel h
                LEFT JOIN kamar k ON h.id_hotel = k.id_hotel
                LEFT JOIN pemesanan b ON k.id_kamar = b.id_kamar
                WHERE DATE(b.dibuat_pada) = CURDATE()
                GROUP BY h.id_hotel, h.nama_hotel
                ORDER BY jumlah_pesanan DESC";
$stats_result = $koneksi->query($query_stats);
$stats_data = [];
while($row = $stats_result->fetch_assoc()) {
    $stats_data[] = $row;
}
$hotel_names    = json_encode(array_map(fn($i) => $i['nama_hotel'], $stats_data));
$pesanan_counts = json_encode(array_map(fn($i) => $i['jumlah_pesanan'], $stats_data));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Penjualan - Admin</title>

    <!--  memanggil css global, navigasi, dan modul statistik -->
    <?php include '../komponen/style.php'; ?>
    <link rel="stylesheet" href="/reservasi_hotel/css/style_navigasi.css">
    <link rel="stylesheet" href="/reservasi_hotel/css/style_statistik.css">
</head>

<body>
    <?php include '../komponen/navigasi.php'; ?>

    <main class="panel-container">
        <header class="panel-header">
            <h1 class="panel-title">Analitik Ringkasan Transaksi</h1>
        </header>

        <div class="dashboard-wrapper">
            <!-- sidebar navigasi panel admin -->
            <aside class="admin-sidebar">
                <div class="sidebar-title">Menu Utama</div>
                <ul class="sidebar-menu">
                    <li><a href="kelola_hotel.php">🏨 Kelola Hotel</a></li>
                    <li><a href="banner.php">🖼️ Kelola Banner</a></li>
                    <li><a href="statistik.php" class="active">📊 Statistik Hari Ini</a></li>
                </ul>
            </aside>

            <!-- grafik statistik penjualan -->
            <div class="admin-main-content">
                <section class="stats-section box-card">
                    <h2 class="stats-title">📊 Grafik Reservasi Hari Ini (<?= date('d M Y'); ?>)</h2>

                    <?php if (!empty($stats_data)): ?>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                    <?php else: ?>
                    <div class="stats-empty">Belum ada pesanan masuk hari ini.</div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <!-- pustaka grafik chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // logo path di komponen navigasi
        const logoImg = document.querySelector('.brand-logo img');
        if (logoImg) {
            logoImg.src = '/reservasi_hotel/assets/logo/logo.png';
        }

        // render grafik jika data stats tersedia
        <?php if (!empty($stats_data)): ?>
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= $hotel_names; ?>,
                datasets: [{
                    label: 'Jumlah Tiket/Kamar Dipesan',
                    data: <?= $pesanan_counts; ?>,
                    backgroundColor: '#4f46e5',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    });
    </script>
</body>

</html>