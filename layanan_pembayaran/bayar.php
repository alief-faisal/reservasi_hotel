<?php
// Sistem pembayaran realistis dengan gateway payment
session_start();
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

if (!isset($_SESSION['id_pengguna'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='../layanan_autentikasi/masuk.php';</script>";
    exit();
}

$id_pembayaran = $_GET['id_pembayaran'] ?? 0;

// Ambil data pembayaran dengan detail lengkap
$query = "SELECT p.*, b.total_bayar, b.id_kamar, b.id_pemesanan, b.tanggal_checkin, b.tanggal_checkout, 
                 k.nama_kamar, k.tipe_kamar, h.nama_hotel, h.lokasi, u.nama, u.email
          FROM pembayaran p 
          JOIN pemesanan b ON p.id_pemesanan = b.id_pemesanan 
          JOIN kamar k ON b.id_kamar = k.id_kamar
          JOIN hotel h ON k.id_hotel = h.id_hotel
          JOIN pengguna u ON b.id_pengguna = u.id_pengguna
          WHERE p.id_pembayaran = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_pembayaran);
$stmt->execute();
$data_tagihan = $stmt->get_result()->fetch_assoc();

if (!$data_tagihan) {
    echo "<script>alert('Data pembayaran tidak ditemukan'); window.location='../index.php';</script>";
    exit();
}

// Jika sudah lunas, redirect ke halaman sukses
if ($data_tagihan['status_pembayaran'] === 'lunas') {
    echo "<script>alert('Pembayaran sudah dikonfirmasi. Selamat menikmati liburan Anda!'); window.location='../index.php';</script>";
    exit();
}

$payment_methods = [
    'gopay' => ['nama' => 'GoPay', 'ikon' => '../assets/icons/gopay_logo.png', 'biaya' => 0, 'is_image' => true],
    'ovo' => ['nama' => 'OVO', 'ikon' => '../assets/icons/ovo_logo.png', 'biaya' => 0, 'is_image' => true],
    'dana' => ['nama' => 'DANA', 'ikon' => '../assets/icons/dana_logo.png', 'biaya' => 0, 'is_image' => true],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gateway Pembayaran - GrandStay</title>

    <!-- Panggil File CSS Secara Terpisah agar tidak merusak Navigasi -->
    <link rel="stylesheet" href="/reservasi_hotel/css/style_navigasi.css">
    <link rel="stylesheet" href="/reservasi_hotel/css/style_pembayaran.css">
</head>

<body>
    <?php include_once '../komponen/navigasi.php'; ?>

    <div class="payment-container">
        <!-- Bagian Ringkasan / Invoice -->
        <div class="invoice-section">
            <div class="invoice-header">
                <h2 class="invoice-title">Invoice Pembayaran</h2>
                <span class="invoice-id">#<?= str_pad($data_tagihan['id_pembayaran'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>

            <div class="hotel-details">
                <div class="hotel-name"><?= htmlspecialchars($data_tagihan['nama_hotel']); ?></div>
                <div class="hotel-meta">📍 <?= htmlspecialchars($data_tagihan['lokasi']); ?></div>
            </div>

            <div class="invoice-info">
                <div class="info-row">
                    <span class="info-label">Tipe Kamar</span>
                    <span class="info-value"><?= htmlspecialchars($data_tagihan['nama_kamar']); ?>
                        (<?= htmlspecialchars($data_tagihan['tipe_kamar']); ?>)</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Check-in</span>
                    <span class="info-value"><?= date('d M Y', strtotime($data_tagihan['tanggal_checkin'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Check-out</span>
                    <span class="info-value"><?= date('d M Y', strtotime($data_tagihan['tanggal_checkout'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Jumlah Malam</span>
                    <span
                        class="info-value"><?= (strtotime($data_tagihan['tanggal_checkout']) - strtotime($data_tagihan['tanggal_checkin'])) / 86400; ?>
                        Malam</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nama Pemesan</span>
                    <span class="info-value"><?= htmlspecialchars($data_tagihan['nama']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?= htmlspecialchars($data_tagihan['email']); ?></span>
                </div>
            </div>

            <div class="price-summary">
                <div class="price-row">
                    <span>Subtotal</span>
                    <span>Rp <?= number_format($data_tagihan['total_bayar'], 0, ',', '.'); ?></span>
                </div>
                <div class="price-row">
                    <span>Pajak & Biaya Layanan</span>
                    <span>Gratis</span>
                </div>
                <div class="price-row total">
                    <span>Total Bayar</span>
                    <span>Rp <?= number_format($data_tagihan['total_bayar'], 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <!-- Bagian Metode Pembayaran -->
        <div class="payment-section">
            <h3 class="payment-title">Pilih Metode Pembayaran</h3>

            <div id="error-alert" class="error-alert" style="display: none;"></div>

            <form id="paymentForm">
                <div class="payment-methods">
                    <?php foreach ($payment_methods as $key => $method): ?>
                    <label class="payment-method">
                        <input type="radio" name="metode_bayar" value="<?= $key; ?>" required>
                        <div class="method-content">
                            <div class="method-info">
                                <div class="method-icon">
                                    <?php if ($method['is_image']): ?>
                                    <img src="<?= $method['ikon']; ?>" alt="<?= $method['nama']; ?>">
                                    <?php else: ?>
                                    <?= $method['ikon']; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="method-details">
                                    <h4><?= $method['nama']; ?></h4>
                                </div>
                            </div>
                            <div class="method-time">✓ Tersedia</div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn-pay" id="payBtn">Lanjutkan Pembayaran</button>

                <div class="security-badge">
                    🔒 Transaksi Anda Aman & Terenkripsi
                </div>
            </form>

            <div class="payment-footer">
                <p>Pembayaran diproses oleh partner kami yang terpercaya</p>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('paymentForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const selected = document.querySelector('input[name="metode_bayar"]:checked');
        const errorAlert = document.getElementById('error-alert');
        const payBtn = document.getElementById('payBtn');

        if (!selected) {
            errorAlert.style.display = 'block';
            errorAlert.textContent = 'Silakan pilih metode pembayaran';
            return;
        }

        payBtn.disabled = true;
        const originalText = payBtn.textContent;
        payBtn.innerHTML = '⏳ Memproses pembayaran...';
        errorAlert.style.display = 'none';

        try {
            const formData = new FormData();
            formData.append('id_pembayaran', <?= $id_pembayaran; ?>);
            formData.append('metode_bayar', selected.value);

            const response = await fetch('proses_pembayaran.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.status === 'success') {
                payBtn.innerHTML = '✓ Pembayaran Berhasil!';
                payBtn.style.background = '#10b981';

                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 1500);
            } else {
                errorAlert.style.display = 'block';
                errorAlert.textContent = data.message || 'Terjadi kesalahan, silakan coba lagi';

                payBtn.disabled = false;
                payBtn.textContent = originalText;
                payBtn.style.background = '';
            }
        } catch (error) {
            errorAlert.style.display = 'block';
            errorAlert.textContent = 'Kesalahan jaringan: ' + error.message;
            payBtn.disabled = false;
            payBtn.textContent = originalText;
        }
    });

    document.querySelectorAll('input[name="metode_bayar"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-method').forEach(method => {
                method.style.borderColor = '#e2e8f0';
                method.style.background = 'transparent';
            });

            this.parentElement.style.borderColor = '#667eea';
            this.parentElement.style.background = '#f8fafc';
        });
    });

    // Memperbaiki logo navigasi secara dinamis jika diperlukan
    document.addEventListener('DOMContentLoaded', () => {
        const logoImg = document.querySelector('.brand-logo img');
        if (logoImg) {
            logoImg.src = '/reservasi_hotel/assets/logo/logo.png';
        }
    });
    </script>
</body>

</html>