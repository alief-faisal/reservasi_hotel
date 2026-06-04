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
    'transfer_bank' => ['nama' => 'Transfer Bank', 'ikon' => '🏦', 'biaya' => 0, 'is_image' => false],
    'kartu_kredit' => ['nama' => 'Kartu Kredit/Debit', 'ikon' => '💳', 'biaya' => 0, 'is_image' => false],
];

// No POST processing here, semua dihandle via AJAX
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gateway Pembayaran - GrandStay</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
        min-height: 100vh;
        padding: 20px;
        display: flex;
        flex-direction: column;
    }

    .payment-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        max-width: 1000px;
        width: 100%;
        margin: 40px auto 0;
        flex: 1;
    }

    @media (max-width: 768px) {
        .payment-container {
            grid-template-columns: 1fr;
        }
    }

    /* Bagian Ringkasan Invoice */
    .invoice-section {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    .invoice-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
    }

    .invoice-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2d3748;
    }

    .invoice-id {
        background: #f0f4ff;
        color: #667eea;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .invoice-info {
        margin-bottom: 25px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.95rem;
    }

    .info-label {
        color: #718096;
        font-weight: 500;
    }

    .info-value {
        color: #2d3748;
        font-weight: 600;
        text-align: right;
        max-width: 50%;
        word-wrap: break-word;
    }

    .hotel-details {
        background: #f8fafc;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #667eea;
    }

    .hotel-name {
        font-size: 1rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 5px;
    }

    .hotel-meta {
        font-size: 0.85rem;
        color: #718096;
        margin: 3px 0;
    }

    /* Ringkasan Harga */
    .price-summary {
        background: #f0f4ff;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
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

    /* Bagian Metode Pembayaran */
    .payment-section {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    .payment-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .payment-methods {
        display: grid;
        gap: 12px;
        margin-bottom: 25px;
    }

    .payment-method {
        display: flex;
        align-items: center;
        padding: 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .payment-method:hover {
        border-color: #667eea;
        background: #f8fafc;
    }

    .payment-method input[type="radio"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        margin-right: 15px;
        accent-color: #667eea;
    }

    .payment-method input[type="radio"]:checked {
        accent-color: #667eea;
    }

    .method-content {
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .method-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .method-icon {
        font-size: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 45px;
        min-height: 45px;
    }

    .method-icon img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        width: 45px;
        height: 45px;
    }

    .method-details h4 {
        font-size: 1rem;
        color: #2d3748;
        margin-bottom: 0;
    }

    .method-time {
        font-size: 0.85rem;
        background: #f0f4ff;
        color: #667eea;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        white-space: nowrap;
    }

    /* Error Message */
    .error-alert {
        background: #fed7d7;
        color: #c53030;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #c53030;
    }

    /* Button */
    .btn-pay {
        width: 100%;
        padding: 16px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1.05rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-pay:hover {
        background: #2563eb;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
    }

    .btn-pay:active {
        transform: translateY(0);
    }

    .btn-pay:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .payment-footer {
        text-align: center;
        margin-top: 20px;
        font-size: 0.85rem;
        color: #718096;
    }

    .security-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 0.85rem;
        color: #38a169;
        margin-top: 15px;
    }
    </style>
</head>

<body>
    <?php include_once '../komponen/navigasi.php'; ?>

    <div class="payment-container">
        <!-- Logic Invoice/Ringkasan -->
        <div class="invoice-section">
            <div class="invoice-header">
                <h2 class="invoice-title">Invoice Pembayaran</h2>
                <span class="invoice-id">#<?= str_pad($data_tagihan['id_pembayaran'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>

            <div class="hotel-details">
                <div class="hotel-name"><?= htmlspecialchars($data_tagihan['nama_hotel']); ?></div>
                <div class="hotel-meta">� <?= htmlspecialchars($data_tagihan['lokasi']); ?></div>
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

        <!-- Logici Metode Pembayaran -->
        <div class="payment-section">
            <h3 class="payment-title">
                Pilih Metode Pembayaran
            </h3>

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

        // Disable button 
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
                // Sukses 
                payBtn.innerHTML = '✓ Pembayaran Berhasil!';
                payBtn.style.background = '#10b981';

                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 1500);
            } else {
                // Error
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

    // Real-time update metode pembayaran yang dipilih
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
    </script>
</body>

</html>