<?php
session_start();
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../layanan_autentikasi/masuk.php");
    exit();
}

$id_pengguna = $_SESSION['id_pengguna'];

// Ambil riwayat pembayaran pengguna
$query = "SELECT p.*, b.tanggal_checkin, b.tanggal_checkout, b.total_bayar,
                 k.nama_kamar, k.tipe_kamar, h.nama_hotel, h.lokasi
          FROM pembayaran p
          JOIN pemesanan b ON p.id_pemesanan = b.id_pemesanan
          JOIN kamar k ON b.id_kamar = k.id_kamar
          JOIN hotel h ON k.id_hotel = h.id_hotel
          WHERE b.id_pengguna = ?
          ORDER BY p.id_pembayaran DESC";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_pengguna);
$stmt->execute();
$hasil = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pembayaran - GrandStay</title>

    <!-- Panggil CSS Navigasi & Halaman Riwayat secara Eksternal -->
    <link rel="stylesheet" href="/reservasi_hotel/css/style_navigasi.css">
    <link rel="stylesheet" href="/reservasi_hotel/css/style_riwayat_pembayaran.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap"
        rel="stylesheet">

</head>

<body>
    <?php include_once '../komponen/navigasi.php'; ?>

    <div class="container">
        <div class="header">
            <h1>Riwayat Pembayaran</h1>
            <p>Kelola dan pantau semua transaksi pemesanan Anda</p>
        </div>

        <?php if ($hasil->num_rows > 0): ?>
        <div class="payment-history">
            <?php while ($row = $hasil->fetch_assoc()):
                    $status_class = $row['status_pembayaran'] === 'lunas' ? 'status-lunas' : 'status-belum';
                    $status_text = $row['status_pembayaran'] === 'lunas' ? '✓ LUNAS' : '⏳ BELUM BAYAR';
                    $malam = (strtotime($row['tanggal_checkout']) - strtotime($row['tanggal_checkin'])) / 86400;
                ?>
            <div class="payment-card">
                <div class="card-header">
                    <div>
                        <div class="hotel-title"><?= htmlspecialchars($row['nama_hotel']); ?></div>
                        <div class="hotel-location"><?= htmlspecialchars($row['lokasi']); ?></div>
                    </div>
                    <span class="status-badge <?= $status_class; ?>"><?= $status_text; ?></span>
                </div>

                <div class="room-info">
                    <div class="room-type"><?= htmlspecialchars($row['nama_kamar']); ?>
                        (<?= htmlspecialchars($row['tipe_kamar']); ?>)</div>
                    <div class="room-dates">
                        <span>📅 <?= date('d M Y', strtotime($row['tanggal_checkin'])); ?></span>
                        <span>→</span>
                        <span><?= date('d M Y', strtotime($row['tanggal_checkout'])); ?></span>
                        <span>(<?= intval($malam); ?> malam)</span>
                    </div>
                </div>

                <div class="card-details">
                    <div class="detail-item">
                        <span class="detail-label">Nomor Referensi</span>
                        <span class="payment-ref">#<?= str_pad($row['id_pembayaran'], 12, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Metode Pembayaran</span>
                        <span class="detail-value"><?= htmlspecialchars($row['metode_pembayaran']); ?></span>
                    </div>
                </div>

                <div class="price-section">
                    <span style="color: #64748b;">Total Pembayaran</span>
                    <span class="price-amount">Rp <?= number_format($row['total_bayar'], 0, ',', '.'); ?></span>
                </div>

                <div class="action-buttons">
                    <?php if ($row['status_pembayaran'] === 'lunas'): ?>
                    <a href="bukti_pembayaran.php?id_pembayaran=<?= $row['id_pembayaran']; ?>" class="btn btn-receipt">
                        📄 Lihat Bukti Pembayaran
                    </a>
                    <?php else: ?>
                    <a href="bayar.php?id_pembayaran=<?= $row['id_pembayaran']; ?>" class="btn btn-pay">
                        💳 Lanjutkan Pembayaran
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <div class="empty-text">Belum Ada Riwayat Pembayaran</div>
            <div class="empty-subtext">Mulai pesan kamar hotel sekarang untuk melacak pembayaran Anda di sini</div>
            <a href="../index.php" class="btn-home">Cari Hotel Sekarang</a>
        </div>
        <?php endif; ?>
    </div>
</body>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Memperbaiki path link gambar logo navigasi yang pecah
    const logoImg = document.querySelector('.brand-logo img');
    if (logoImg) {
        logoImg.src = '/reservasi_hotel/assets/logo/logo.png';
    }
});
</script>

</html>