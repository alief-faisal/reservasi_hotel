<?php
session_start();
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

$id_hotel = isset($_GET['id_hotel']) ? intval($_GET['id_hotel']) : 0;

/* Ambil data hotel */
$query_hotel = "SELECT * FROM hotel WHERE id_hotel = ?";
$stmt_h = $koneksi->prepare($query_hotel);
$stmt_h->bind_param("i", $id_hotel);
$stmt_h->execute();
$data_hotel = $stmt_h->get_result()->fetch_assoc();

if (!$data_hotel) {
    echo "<script>alert('Data Hotel tidak ditemukan.'); window.location='../index.php';</script>";
    exit();
}

/* logika stok kamar tersedia*/
$query_kamar = "SELECT * FROM kamar WHERE id_hotel = ? AND stok_kamar > 0";
$stmt_k = $koneksi->prepare($query_kamar);
$stmt_k->bind_param("i", $id_hotel);
$stmt_k->execute();
$hasil_kamar = $stmt_k->get_result();

if (isset($_POST['proses_pesan'])) {
    if (!isset($_SESSION['id_pengguna'])) {
        echo "<script>alert('Silakan masuk ke akun Anda terlebih dahulu untuk memproses booking.'); window.location='../layanan_autentikasi/masuk.php';</script>";
        exit();
    }

    /* Validasi pemesanan kamar : Blokir jika akun yang login memiliki status admin */
    if (isset($_SESSION['peran']) && $_SESSION['peran'] === 'admin') {
        echo "<script>alert('Akun Admin tidak diperbolehkan melakukan pemesanan kamar.'); window.location='../index.php';</script>";
        exit();
    }

    $id_kamar_pilihan = intval($_POST['id_kamar']);

    /* Validasi kamar pilihan */
    $query_cek = "SELECT harga_per_malam, diskon_persen FROM kamar WHERE id_kamar = ? AND id_hotel = ? AND stok_kamar > 0";
    $stmt_cek = $koneksi->prepare($query_cek);
    $stmt_cek->bind_param("ii", $id_kamar_pilihan, $id_hotel);
    $stmt_cek->execute();
    $data_kamar_pilihan = $stmt_cek->get_result()->fetch_assoc();

    if (!$data_kamar_pilihan) {
        echo "<script>alert('Mohon maaf, tipe kamar ini baru saja penuh.'); window.location='../index.php';</script>";
        exit();
    }

    $id_pengguna = $_SESSION['id_pengguna'];
    $harga_asli = $data_kamar_pilihan['harga_per_malam'];
    $diskon = intval($data_kamar_pilihan['diskon_persen'] ?? 0);
    $total_bayar = $diskon > 0 ? $harga_asli * (100 - $diskon) / 100 : $harga_asli;

    /* Buat data transaksi pemesanan */
    $stmt_pesan = $koneksi->prepare("INSERT INTO pemesanan (id_pengguna, id_kamar, tanggal_checkin, tanggal_checkout, total_bayar) VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), ?)");
    $stmt_pesan->bind_param("iid", $id_pengguna, $id_kamar_pilihan, $total_bayar);
    $stmt_pesan->execute();
    $id_pemesanan_baru = $stmt_pesan->insert_id;

    /* Buat status pembayaran tagihan */
    $stmt_bayar = $koneksi->prepare("INSERT INTO pembayaran (id_pemesanan, metode_pembayaran, status_pembayaran) VALUES (?, 'E-Wallet QRIS', 'belum_lunas');");
    $stmt_bayar->bind_param("i", $id_pemesanan_baru);
    $stmt_bayar->execute();
    $id_pembayaran_baru = $stmt_bayar->insert_id;

    echo "<script>alert('Booking dibuat. Mengalihkan ke transaksi...'); window.location='../layanan_pembayaran/bayar.php?id_pembayaran=$id_pembayaran_baru';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Hotel - <?= htmlspecialchars($data_hotel['nama_hotel']); ?></title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Open Sans', sans-serif;
    }

    body {
        background-color: #f8fafc;
        color: #1e293b;
    }

    /* kontainer */
    .detail-container {
        max-width: 1050px;
        margin: 50px auto;
        background: white;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        display: flex;
        align-items: stretch;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border-radius: 12px;
    }

    /* Gambar card */
    .detail-img {
        width: 500px;
        height: auto;
        min-height: 100%;
        object-fit: cover;
        background: #cbd5e1;
        flex-shrink: 0;
    }

    /* pading kanan */
    .detail-content {
        flex: 1;
        padding: 35px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .location-tag {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        margin-bottom: 12px;
    }

    .icon-location {
        width: 15px;
        height: 15px;
        fill: #64748b;
    }

    .hotel-name {
        font-size: 1.8rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 14px;
        line-height: 1.3;
    }

    .hotel-desc {
        font-size: 0.95rem;
        color: #475569;
        line-height: 1.6;
        margin-bottom: 25px;
    }

    .booking-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 18px;
        border-radius: 8px;
        margin-bottom: 16px;
    }

    .form-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .form-select {
        width: 100%;
        padding: 12px;
        font-size: 0.9rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        outline: none;
        background-color: #ffffff;
        color: #0f172a;
        font-weight: 600;
        transition: border-color 0.2s;
        cursor: pointer;
    }

    .form-select:focus {
        border-color: #dc2626;
    }

    .btn-booking {
        display: block;
        width: 100%;
        text-align: center;
        background: #dc2626;
        color: white;
        padding: 14px;
        border: none;
        font-weight: 600;
        font-size: 0.95rem;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.15s, transform 0.1s;
    }

    .btn-booking:hover {
        background: #b91c1c;
    }

    .btn-booking:active {
        transform: scale(0.99);
    }

    .room-sold-out {
        text-align: center;
        color: #dc2626;
        background: #fff5f5;
        border: 1px solid #fed7d7;
        padding: 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
    }

    /* Section Rekomendasi */
    .rekomendasi-section {
        max-width: 1100px;
        margin: 50px auto;
        padding: 0 20px;
    }

    .rekomendasi-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 40px;
        color: #0f172a;
        letter-spacing: -0.5px;
    }

    .rekomendasi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        width: 100%;
    }

    .card-link {
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
    }

    .hotel-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }

    .hotel-card:hover .hotel-card-img {
        transform: scale(1.05);
    }

    .img-wrapper {
        width: 100%;
        height: 180px;
        overflow: hidden;
        background-color: #f1f5f9;
        flex-shrink: 0;
    }

    .hotel-card-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 2s ease-in-out;
    }

    .card-body {
        padding: 20px;
        min-height: 220px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .card-meta {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.7rem;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 8px;
        flex-shrink: 0;
    }

    .icon-location {
        width: 14px;
        height: 14px;
        fill: #64748b;
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 10px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        min-height: 3.0rem;
    }

    .card-text {
        color: #475569;
        font-size: 0.88rem;
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 24px;
    }

    .price-wrapper {
        margin-top: auto;
        display: flex;
        flex-direction: column;
        padding-top: 8px;
    }

    .price-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .price-amount {
        font-size: 1.1rem;
        font-weight: 700;
        color: #dc2626;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .price-row {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .price-suffix {
        font-size: 0.8rem;
        font-weight: 400;
        color: #7f1d1d;
        margin-left: 2px;
    }

    .price-original {
        font-size: 0.8rem;
        color: #9ca3af;
        text-decoration: line-through;
        text-decoration-color: #ef4444;
        text-decoration-thickness: 2px;
        font-style: italic;
        font-weight: 500;
        display: block;
    }

    .discount-badge {
        display: inline-block;
        background: #fee2e2;
        color: #991b1b;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 5px;
        border-radius: 3px;
    }

    @media (max-width: 1200px) {
        .rekomendasi-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .rekomendasi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 480px) {
        .rekomendasi-grid {
            grid-template-columns: 1fr;
        }
    }

    /* desktop & mobile */
    @media (max-width: 950px) {
        .detail-container {
            flex-direction: column;
            margin: 20px;
            max-width: 100%;
        }

        .detail-img {
            width: 100%;
            height: 350px;
            min-height: auto;
        }

        .detail-content {
            padding: 24px;
        }
    }
    </style>
</head>

<body>

    <?php include '../komponen/navigasi.php'; ?>

    <main class="detail-container">
        <?php 
            $nama_foto = $data_hotel['foto'];
            if(empty($nama_foto) || $nama_foto == 'default.jpg' || !file_exists("../assets/" . $nama_foto)) {
                $path_foto = "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=500&h=700&q=80";
            } else {
                $path_foto = "/reservasi_hotel/assets/" . $nama_foto;
            }
        ?>
        <img src="<?= $path_foto; ?>" alt="" class="detail-img">

        <section class="detail-content">
            <div>
                <div class="location-tag">
                    <svg class="icon-location" viewBox="0 0 24 24">
                        <path
                            d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                    </svg>
                    <span><?= htmlspecialchars($data_hotel['lokasi']); ?></span>
                </div>

                <h1 class="hotel-name"><?= htmlspecialchars($data_hotel['nama_hotel']); ?></h1>
                <p class="hotel-desc"><?= htmlspecialchars($data_hotel['deskripsi']); ?></p>
            </div>

            <?php if (!isset($_SESSION['peran']) || $_SESSION['peran'] !== 'admin'): ?>
            <form action="" method="POST">
                <?php if ($hasil_kamar->num_rows > 0): ?>
                <div class="booking-box">
                    <label class="form-label">Pilih Tipe Kamar & Tarif</label>
                    <select name="id_kamar" class="form-select" required>
                        <?php while($kamar = $hasil_kamar->fetch_assoc()): 
                            $harga_kamar = $kamar['harga_per_malam'];
                            $diskon_kamar = intval($kamar['diskon_persen'] ?? 0);
                            $harga_akhir = $diskon_kamar > 0 ? $harga_kamar * (100 - $diskon_kamar) / 100 : $harga_kamar;
                        ?>
                        <option value="<?= $kamar['id_kamar']; ?>">
                            <?= htmlspecialchars($kamar['nama_kamar']); ?>
                            (<?= htmlspecialchars($kamar['tipe_kamar']); ?>)
                            <?php if ($diskon_kamar > 0): ?>
                            — ~~Rp <?= number_format($harga_kamar, 0, ',', '.'); ?>~~ Rp
                            <?= number_format($harga_akhir, 0, ',', '.'); ?> (-<?= $diskon_kamar; ?>%) / malam
                            <?php else: ?>
                            — Rp <?= number_format($harga_kamar, 0, ',', '.'); ?> / malam
                            <?php endif; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" name="proses_pesan" class="btn-booking">Pesan Hotel</button>
                <?php else: ?>
                <div class="room-sold-out">Hotel Penuh (Seluruh Tipe Kamar Habis)</div>
                <?php endif; ?>
            </form>
            <?php endif; ?>
        </section>
    </main>

    <!-- Section Rekomendasi Hotel Lain di Wilayah Sama -->
    <section class="rekomendasi-section">
        <h2 class="rekomendasi-title">Hotel Lain di <?= htmlspecialchars($data_hotel['lokasi']); ?></h2>

        <div class="rekomendasi-grid">
            <?php
            // Ambil hotel lain di wilayah yang sama (max 8)
            $query_rekomendasi = "SELECT h.id_hotel, h.nama_hotel, h.lokasi, h.deskripsi, h.foto, k.harga_per_malam, k.diskon_persen FROM hotel h LEFT JOIN kamar k ON h.id_hotel = k.id_hotel WHERE h.lokasi = ? AND h.id_hotel != ? GROUP BY h.id_hotel LIMIT 8";
            $stmt_rekomendasi = $koneksi->prepare($query_rekomendasi);
            $stmt_rekomendasi->bind_param("si", $data_hotel['lokasi'], $id_hotel);
            $stmt_rekomendasi->execute();
            $hasil_rekomendasi = $stmt_rekomendasi->get_result();

            if ($hasil_rekomendasi->num_rows > 0) {
                while($hotel_rekomendasi = $hasil_rekomendasi->fetch_assoc()) {
                    $nama_foto_rekomendasi = $hotel_rekomendasi['foto'];
                    if(empty($nama_foto_rekomendasi) || $nama_foto_rekomendasi == 'default.jpg' || !file_exists("../assets/" . $nama_foto_rekomendasi)) {
                        $path_foto_rekomendasi = "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=400&h=300&q=80";
                    } else {
                        $path_foto_rekomendasi = "/reservasi_hotel/assets/" . $nama_foto_rekomendasi;
                    }

                    $harga_original = $hotel_rekomendasi['harga_per_malam'];
                    $diskon = intval($hotel_rekomendasi['diskon_persen'] ?? 0);
                    $harga_final = $diskon > 0 ? $harga_original * (100 - $diskon) / 100 : $harga_original;
            ?>
            <a href="pesan.php?id_hotel=<?= $hotel_rekomendasi['id_hotel']; ?>" class="card-link">
                <article class="hotel-card">
                    <div class="img-wrapper">
                        <img src="<?= $path_foto_rekomendasi; ?>"
                            alt="<?= htmlspecialchars($hotel_rekomendasi['nama_hotel']); ?>" class="hotel-card-img">
                    </div>
                    <div class="card-body">
                        <h3 class="card-title"><?= htmlspecialchars($hotel_rekomendasi['nama_hotel']); ?></h3>
                        <div class="card-meta">
                            <span><?= htmlspecialchars($hotel_rekomendasi['lokasi']); ?></span>
                        </div>
                        <p class="card-text"><?= htmlspecialchars($hotel_rekomendasi['deskripsi']); ?></p>
                        <div class="price-wrapper">
                            <span class="price-amount">
                                <?php if ($diskon > 0): ?>
                                <div class="price-row">
                                    <span class="price-original">IDR
                                        <?= number_format($harga_original, 0, ',', '.'); ?></span>
                                    <span class="discount-badge">-<?= $diskon; ?>%</span>
                                </div>
                                <?php endif; ?>
                                <div class="price-row">
                                    <span>IDR <?= $harga_final ? number_format($harga_final, 0, ',', '.') : '-'; ?><span
                                            class="price-suffix">/malam</span></span>
                                </div>
                            </span>
                        </div>
                    </div>
                </article>
            </a>
            <?php
                }
            } else {
                echo '<p style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 40px;">Tidak ada hotel lain di wilayah ini</p>';
            }
            ?>
        </div>
    </section>

</body>

</html>