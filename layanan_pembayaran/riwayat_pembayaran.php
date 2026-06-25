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
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background-color: #f8fafc;
        color: #1e293b;
    }

    .container {
        max-width: 1000px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .header {
        margin-bottom: 40px;
    }

    .header h1 {
        font-size: 2rem;
        margin-bottom: 10px;
        color: #0f172a;
    }

    .header p {
        color: #64748b;
        font-size: 1rem;
    }

    .payment-history {
        display: grid;
        gap: 20px;
    }

    .payment-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        transition: all 0.3s ease;
    }

    .payment-card:hover {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border-color: #cbd5e1;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f1f5f9;
    }

    .hotel-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 5px;
    }

    .hotel-location {
        font-size: 0.9rem;
        color: #64748b;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .status-lunas {
        background: #c6f6d5;
        color: #22543d;
    }

    .status-belum {
        background: #fed7d7;
        color: #742a2a;
    }

    .card-details {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 15px;
    }

    @media (max-width: 600px) {
        .card-details {
            grid-template-columns: 1fr;
        }
    }

    .detail-item {
        display: flex;
        flex-direction: column;
    }

    .detail-label {
        font-size: 0.8rem;
        color: #64748b;
        text-transform: uppercase;
        margin-bottom: 4px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .detail-value {
        font-size: 0.95rem;
        color: #0f172a;
        font-weight: 500;
    }

    .room-info {
        background: #f8fafc;
        padding: 12px;
        border-radius: 6px;
        margin: 15px 0;
    }

    .room-type {
        font-size: 0.95rem;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 5px;
    }

    .room-dates {
        font-size: 0.85rem;
        color: #64748b;
        display: flex;
        gap: 15px;
    }

    .price-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-top: 1px solid #f1f5f9;
        margin-top: 12px;
    }

    .price-amount {
        font-size: 1.3rem;
        font-weight: 700;
        color: #667eea;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .btn {
        flex: 1;
        padding: 10px 16px;
        border: none;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
        display: inline-block;
    }

    .btn-receipt {
        background: #667eea;
        color: white;
    }

    .btn-receipt:hover {
        background: #5568d3;
    }

    .btn-pay {
        background: #f0f4ff;
        color: #667eea;
        border: 2px solid #667eea;
    }

    .btn-pay:hover {
        background: #e7ecff;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 8px;
    }

    .empty-icon {
        font-size: 3rem;
        margin-bottom: 20px;
    }

    .empty-text {
        font-size: 1.2rem;
        color: #0f172a;
        margin-bottom: 10px;
    }

    .empty-subtext {
        color: #64748b;
        margin-bottom: 25px;
    }

    .btn-home {
        display: inline-block;
        background: #667eea;
        color: white;
        padding: 10px 24px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: background 0.3s ease;
    }

    .btn-home:hover {
        background: #5568d3;
    }

    .payment-ref {
        font-size: 0.8rem;
        color: #64748b;
        font-family: 'Courier New', monospace;
        background: #f1f5f9;
        padding: 4px 8px;
        border-radius: 4px;
    }
    </style>
</head>

<body>
    <?php include_once '../komponen/navigasi.php'; ?>

    <div class="container">
        <div class="header">
            <h1>Riwayat Pembayaran </h1>
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
                        <div class="hotel-location"> <?= htmlspecialchars($row['lokasi']); ?></div>
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

</html>