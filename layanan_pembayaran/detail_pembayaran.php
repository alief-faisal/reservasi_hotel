<?php
session_start();
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

if (!isset($_SESSION['id_pengguna'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='../layanan_autentikasi/masuk.php';</script>";
    exit();
}

$id_pembayaran = $_GET['id_pembayaran'] ?? 0;
$id_pengguna = $_SESSION['id_pengguna'];

// Ambil detail pembayaran yang masih pending
$query = "SELECT p.*, b.id_pemesanan, b.total_bayar, b.tanggal_checkin, b.tanggal_checkout,
                 k.nama_kamar, k.tipe_kamar, h.nama_hotel, h.lokasi, u.email
          FROM pembayaran p
          JOIN pemesanan b ON p.id_pemesanan = b.id_pemesanan
          JOIN kamar k ON b.id_kamar = k.id_kamar
          JOIN hotel h ON k.id_hotel = h.id_hotel
          JOIN pengguna u ON b.id_pengguna = u.id_pengguna
          WHERE p.id_pembayaran = ? AND p.status_pembayaran = 'pending' AND b.id_pengguna = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("ii", $id_pembayaran, $id_pengguna);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo "<script>alert('Data pembayaran tidak ditemukan atau sudah dibayar'); window.location='riwayat_pembayaran.php';</script>";
    exit();
}

// Hitung sisa waktu pembayaran (24 jam dari pemesanan)
$tanggal_pemesanan = strtotime($data['tanggal_pemesanan']);
$waktu_kadaluarsa = $tanggal_pemesanan + (24 * 60 * 60);
$waktu_sekarang = time();
$sisa_waktu = $waktu_kadaluarsa - $waktu_sekarang;

if ($sisa_waktu <= 0) {
    // Pembayaran expired
    $koneksi->query("UPDATE pembayaran SET status_pembayaran = 'batal' WHERE id_pembayaran = $id_pembayaran");
    echo "<script>alert('Waktu pembayaran telah expired'); window.location='riwayat_pembayaran.php';</script>";
    exit();
}

$jam_sisa = floor($sisa_waktu / 3600);
$menit_sisa = floor(($sisa_waktu % 3600) / 60);

$malam = (strtotime($data['tanggal_checkout']) - strtotime($data['tanggal_checkin'])) / 86400;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pembayaran - GrandStay</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        max-width: 900px;
        margin: 0 auto;
    }

    .alert {
        background: #fef5e7;
        border: 2px solid #f8d7a1;
        color: #856404;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: start;
        gap: 15px;
    }

    .alert-icon {
        font-size: 1.5rem;
    }

    .alert-content {
        flex: 1;
    }

    .alert-title {
        font-weight: 700;
        margin-bottom: 5px;
    }

    .alert-text {
        font-size: 0.95rem;
    }

    .timer {
        font-weight: 700;
        color: #d63031;
        background: rgba(208, 48, 49, 0.1);
        padding: 2px 8px;
        border-radius: 4px;
    }

    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin-bottom: 20px;
    }

    .card-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
    }

    .info-item {
        display: flex;
        flex-direction: column;
    }

    .info-label {
        font-size: 0.85rem;
        color: #718096;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 5px;
    }

    .info-value {
        font-size: 1rem;
        color: #2d3748;
        font-weight: 600;
    }

    .room-details {
        background: #f8fafc;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #667eea;
    }

    .price-breakdown {
        background: #f0f4ff;
        padding: 20px;
        border-radius: 8px;
    }

    .price-row {
        display: flex;
        justify-content: space-between;
        margin: 10px 0;
        font-size: 0.95rem;
    }

    .price-row.total {
        border-top: 2px solid rgba(102, 126, 234, 0.3);
        padding-top: 10px;
        font-size: 1.2rem;
        font-weight: 700;
        color: #667eea;
    }

    .button-group {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .btn {
        flex: 1;
        padding: 14px 20px;
        border: none;
        border-radius: 8px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
        display: inline-block;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
        background: #e2e8f0;
        color: #2d3748;
    }

    .btn-secondary:hover {
        background: #cbd5e1;
    }

    .instruction-box {
        background: #e6fffa;
        border-left: 4px solid #38b6a1;
        padding: 15px;
        border-radius: 6px;
        margin: 20px 0;
    }

    .instruction-title {
        color: #38b6a1;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .instruction-text {
        color: #1f6e63;
        font-size: 0.9rem;
        line-height: 1.5;
    }
    </style>
</head>

<body>
    <?php include_once '../komponen/navigasi.php'; ?>

    <div class="container">
        <div class="alert">
            <div class="alert-icon">⏳</div>
            <div class="alert-content">
                <div class="alert-title">Pembayaran Menunggu Konfirmasi</div>
                <div class="alert-text">
                    Silakan selesaikan pembayaran sebelum <span
                        class="timer"><?= $jam_sisa; ?>:<?= str_pad($menit_sisa, 2, '0', STR_PAD_LEFT); ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">📋 Detail Pemesanan</h2>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Nomor Invoice</span>
                    <span class="info-value">#<?= str_pad($data['id_pembayaran'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Pemesanan</span>
                    <span class="info-value"><?= date('d M Y H:i', strtotime($data['tanggal_pemesanan'])); ?></span>
                </div>
            </div>

            <div class="room-details">
                <div style="font-size: 1rem; font-weight: 700; color: #2d3748; margin-bottom: 8px;">
                    <?= htmlspecialchars($data['nama_kamar']); ?> (<?= htmlspecialchars($data['tipe_kamar']); ?>)
                </div>
                <div style="color: #718096; font-size: 0.9rem; margin-bottom: 8px;">
                    <?= htmlspecialchars($data['nama_hotel']); ?> - <?= htmlspecialchars($data['lokasi']); ?>
                </div>
                <div style="color: #718096; font-size: 0.9rem;">
                    📅 <?= date('d M Y', strtotime($data['tanggal_checkin'])); ?> →
                    <?= date('d M Y', strtotime($data['tanggal_checkout'])); ?> (<?= intval($malam); ?> malam)
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">💰 Ringkasan Pembayaran</h2>

            <div class="price-breakdown">
                <div class="price-row">
                    <span>Biaya Kamar (<?= intval($malam); ?> malam)</span>
                    <span>Rp <?= number_format($data['total_bayar'], 0, ',', '.'); ?></span>
                </div>
                <div class="price-row">
                    <span>Pajak & Biaya Layanan</span>
                    <span>Gratis</span>
                </div>
                <div class="price-row">
                    <span>Diskon (jika ada)</span>
                    <span>Rp 0</span>
                </div>
                <div class="price-row total">
                    <span>Total yang Harus Dibayar</span>
                    <span>Rp <?= number_format($data['total_bayar'], 0, ',', '.'); ?></span>
                </div>
            </div>

            <div class="instruction-box">
                <div class="instruction-title">ℹ️ Informasi Penting</div>
                <div class="instruction-text">
                    ✓ Pembayaran harus diselesaikan dalam 24 jam<br>
                    ✓ Konfirmasi pembayaran otomatis (segera sampai dalam beberapa menit)<br>
                    ✓ Anda akan menerima notifikasi via email di <?= htmlspecialchars($data['email']); ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="card-title">🛠️ Aksi</h2>

            <div class="button-group">
                <a href="bayar.php?id_pembayaran=<?= $id_pembayaran; ?>" class="btn btn-primary">
                    💳 Lanjutkan ke Pembayaran
                </a>
                <a href="riwayat_pembayaran.php" class="btn btn-secondary">
                    ← Kembali ke Riwayat
                </a>
            </div>
        </div>
    </div>

    <script>
    // Update countdown timer setiap detik
    const endTime = <?= $waktu_kadaluarsa * 1000; ?>;

    function updateTimer() {
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance <= 0) {
            window.location.href = 'riwayat_pembayaran.php';
            return;
        }

        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        const timerElements = document.querySelectorAll('.timer');
        timerElements.forEach(el => {
            el.textContent = hours + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2,
                '0');
        });
    }

    updateTimer();
    setInterval(updateTimer, 1000);
    </script>
</body>

</html>