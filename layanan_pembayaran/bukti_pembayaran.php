<?php
session_start();
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

$id_pembayaran = $_GET['id_pembayaran'] ?? 0;

// Ambil data pembayaran dengan detail lengkap
$query = "SELECT p.*, b.total_bayar, b.id_kamar, b.id_pemesanan, b.tanggal_checkin, b.tanggal_checkout,
                 k.nama_kamar, k.tipe_kamar, h.nama_hotel, h.lokasi, u.nama, u.email
          FROM pembayaran p
          JOIN pemesanan b ON p.id_pemesanan = b.id_pemesanan
          JOIN kamar k ON b.id_kamar = k.id_kamar
          JOIN hotel h ON k.id_hotel = h.id_hotel
          JOIN pengguna u ON b.id_pengguna = u.id_pengguna
          WHERE p.id_pembayaran = ? AND p.status_pembayaran = 'lunas'";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_pembayaran);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo "<script>alert('Data pembayaran tidak ditemukan'); window.location='../index.php';</script>";
    exit();
}

// Nama metode pembayaran
$metode_pembayaran = [
    'gopay' => 'GoPay',
    'ovo' => 'OVO',
    'dana' => 'DANA',
];

$nama_metode = $metode_pembayaran[$data['metode_pembayaran']] ?? $data['metode_pembayaran'];

// Hitung jumlah malam
$malam = (strtotime($data['tanggal_checkout']) - strtotime($data['tanggal_checkin'])) / 86400;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pembayaran - GrandStay</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: #f5f5f5;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .receipt-container {
        background: white;
        max-width: 600px;
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
    }

    .receipt-header {
        background: #ffffff;
        color: #2d3748;
        padding: 40px 30px;
        text-align: center;
        border-bottom: 2px solid #e2e8f0;
    }

    .success-icon {
        margin-bottom: 15px;
        animation: scaleIn 0.6s ease-out;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .success-icon img {
        width: 80px;
        height: 80px;
        object-fit: contain;
    }

    @keyframes scaleIn {
        from {
            transform: scale(0);
        }

        to {
            transform: scale(1);
        }
    }

    .receipt-header h1 {
        font-size: 1.8rem;
        margin-bottom: 5px;
    }

    .receipt-header p {
        font-size: 0.95rem;
        opacity: 0.9;
    }

    .receipt-content {
        padding: 40px 30px;
    }

    .receipt-section {
        margin-bottom: 30px;
    }

    .section-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #2d3748;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }

    .receipt-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .grid-item {
        display: flex;
        flex-direction: column;
    }

    .grid-item label {
        font-size: 0.8rem;
        color: #718096;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 5px;
        letter-spacing: 0.5px;
    }

    .grid-item value {
        font-size: 1rem;
        color: #2d3748;
        font-weight: 600;
    }

    .hotel-info {
        background: #ffffff;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #4a5568;
    }

    .hotel-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 5px;
    }

    .hotel-detail {
        font-size: 0.9rem;
        color: #718096;
        margin: 3px 0;
    }

    .room-info {
        background: #f8fafc;
        padding: 20px;
        border-radius: 8px;
        margin-top: 10px;
    }

    .room-type {
        font-size: 0.95rem;
        color: #2d3748;
        margin-bottom: 8px;
    }

    .room-dates {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
        color: #718096;
    }

    .price-breakdown {
        background: #ffffff;
        padding: 20px;
        border-radius: 8px;
    }

    .price-item {
        display: flex;
        justify-content: space-between;
        margin: 10px 0;
        font-size: 0.95rem;
    }

    .price-item.total {
        border-top: 2px solid #e2e8f0;
        padding-top: 10px;
        font-size: 1.15rem;
        font-weight: 700;
        color: #2d3748;
    }

    .status-badge {
        display: inline-block;
        background: #c6f6d5;
        color: #22543d;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        margin: 10px 0;
    }

    .receipt-footer {
        background: #f8fafc;
        padding: 30px;
        text-align: center;
        border-top: 1px solid #e2e8f0;
    }

    .receipt-footer h3 {
        font-size: 0.95rem;
        color: #2d3748;
        margin-bottom: 15px;
    }

    .button-group {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .btn {
        flex: 1;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-download {
        background: #4a5568;
        color: white;
    }

    .btn-download:active {
        transform: scale(0.95);
    }

    .btn-home {
        background: #e2e8f0;
        color: #2d3748;
    }

    .btn-home:active {
        transform: scale(0.95);
    }

    .reference-code {
        background: white;
        border: 2px dashed #e2e8f0;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        margin: 20px 0;
        font-family: 'Courier New', monospace;
    }

    .reference-code label {
        display: block;
        font-size: 0.8rem;
        color: #718096;
        margin-bottom: 8px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .reference-code code {
        font-size: 1.2rem;
        color: #2d3748;
        font-weight: 700;
        letter-spacing: 2px;
    }

    .qr-placeholder {
        text-align: center;
        padding: 20px;
        background: #f8fafc;
        border-radius: 8px;
        margin: 20px 0;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .qr-placeholder img {
        width: 120px;
        height: 120px;
        margin: 15px 0;
        object-fit: contain;
    }

    .qr-placeholder p {
        font-size: 0.85rem;
        color: #718096;
    }

    @media print {
        body {
            background: white;
        }

        .btn,
        .button-group {
            display: none;
        }

        .receipt-container {
            box-shadow: none;
        }
    }
    </style>
</head>

<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="success-icon">
                <img src="../assets/icons/checklist.svg" alt="Pembayaran Berhasil">
            </div>
            <h1>Pembayaran Berhasil!</h1>
            <p>Terima kasih, enjoy your holiday</p>
        </div>

        <!-- Content -->
        <div class="receipt-content">
            <!-- Status -->
            <div class="receipt-section" style="text-align: center;">
                <span class="status-badge">✓ PEMBAYARAN DIKONFIRMASI</span>
            </div>

            <!-- Nomor Referensi -->
            <div class="receipt-section">
                <div class="reference-code">
                    <label>Nomor Referensi</label>
                    <code><?= str_pad($id_pembayaran, 12, '0', STR_PAD_LEFT); ?></code>
                </div>
            </div>

            <!-- Informasi Tamu -->
            <div class="receipt-section">
                <h3 class="section-title">Informasi Tamu</h3>
                <div class="receipt-grid">
                    <div class="grid-item">
                        <label>Nama</label>
                        <value><?= htmlspecialchars($data['nama']); ?></value>
                    </div>
                    <div class="grid-item">
                        <label>Email</label>
                        <value><?= htmlspecialchars($data['email']); ?></value>
                    </div>
                </div>
            </div>

            <!-- Informasi Hotel & Kamar -->
            <div class="receipt-section">
                <h3 class="section-title">Hotel & Kamar</h3>
                <div class="hotel-info">
                    <div class="hotel-name"><?= htmlspecialchars($data['nama_hotel']); ?></div>
                    <div class="hotel-detail"> <?= htmlspecialchars($data['lokasi']); ?></div>
                </div>
                <div class="room-info">
                    <div class="room-type">
                        <strong><?= htmlspecialchars($data['nama_kamar']); ?></strong>
                        <span
                            style="color: #718096; margin-left: 10px;">(<?= htmlspecialchars($data['tipe_kamar']); ?>)</span>
                    </div>
                    <div class="room-dates">
                        <span>📅 Check-in: <?= date('d M Y', strtotime($data['tanggal_checkin'])); ?></span>
                        <span>📅 Check-out: <?= date('d M Y', strtotime($data['tanggal_checkout'])); ?></span>
                    </div>
                    <div style="color: #718096; margin-top: 8px; font-size: 0.9rem;">⏱️ Durasi: <?= intval($malam); ?>
                        malam</div>
                </div>
            </div>

            <!-- Rincian Pembayaran -->
            <div class="receipt-section">
                <h3 class="section-title"> Rincian Pembayaran</h3>
                <div class="price-breakdown">
                    <div class="price-item">
                        <span>Harga Kamar (<?= intval($malam); ?> malam)</span>
                        <span>Rp <?= number_format($data['total_bayar'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="price-item">
                        <span>Pajak & Biaya Layanan</span>
                        <span>Rp 0</span>
                    </div>
                    <div class="price-item total">
                        <span>Total Dibayar</span>
                        <span>Rp <?= number_format($data['total_bayar'], 0, ',', '.'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Metode Pembayaran -->
            <div class="receipt-section">
                <h3 class="section-title">Metode Pembayaran</h3>
                <div class="receipt-grid">
                    <div class="grid-item">
                        <label>Metode</label>
                        <value><?= $nama_metode; ?></value>
                    </div>
                    <div class="grid-item">
                        <label>Waktu Pembayaran</label>
                        <value><?= date('d M Y, H:i', strtotime($data['waktu_pembayaran'])); ?></value>
                    </div>
                </div>
            </div>

            <!-- QR Code Placeholder -->
            <div class="qr-placeholder">
                <p>🔗 Kode Booking QR</p>
                <img src="../assets/icons/qr.svg" alt="QR Code Booking">
                <p>Tunjukkan kode ini di resepsi hotel</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <h3>Apa Selanjutnya?</h3>
            <p style="color: #718096; font-size: 0.95rem; margin: 10px 0;">
                Kami telah mengirimkan detail lengkap ke email Anda. Tunjukkan bukti pembayaran ini saat check-in.
            </p>

            <div class="button-group">
                <button class="btn btn-download" onclick="window.print()">Cetak</button>
                <button class="btn btn-home" onclick="window.location='../index.php'">Kembali ke Beranda</button>
            </div>
        </div>
    </div>

    <script>
    // Auto-print dialog jika dari payment gateway
    if (sessionStorage.getItem('auto_print')) {
        setTimeout(() => window.print(), 500);
        sessionStorage.removeItem('auto_print');
    }
    </script>
</body>

</html>