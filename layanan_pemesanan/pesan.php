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

/* Ambil data kamar yang tersedia */
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

    if (isset($_SESSION['peran']) && $_SESSION['peran'] === 'admin') {
        echo "<script>alert('Akun Admin tidak diperbolehkan melakukan pemesanan kamar.'); window.location='../index.php';</script>";
        exit();
    }

    $id_kamar_pilihan = intval($_POST['id_kamar']);

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
    $harga_asli  = $data_kamar_pilihan['harga_per_malam'];
    $diskon      = intval($data_kamar_pilihan['diskon_persen'] ?? 0);
    $total_bayar = $diskon > 0 ? $harga_asli * (100 - $diskon) / 100 : $harga_asli;

    $stmt_pesan = $koneksi->prepare("INSERT INTO pemesanan (id_pengguna, id_kamar, tanggal_checkin, tanggal_checkout, total_bayar) VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), ?)");
    $stmt_pesan->bind_param("iid", $id_pengguna, $id_kamar_pilihan, $total_bayar);
    $stmt_pesan->execute();
    $id_pemesanan_baru = $stmt_pesan->insert_id;

    $stmt_bayar = $koneksi->prepare("INSERT INTO pembayaran (id_pemesanan, metode_pembayaran, status_pembayaran) VALUES (?, 'E-Wallet QRIS', 'belum_lunas');");
    $stmt_bayar->bind_param("i", $id_pemesanan_baru);
    $stmt_bayar->execute();
    $id_pembayaran_baru = $stmt_bayar->insert_id;

    echo "<script>alert('Booking dibuat. Mengalihkan ke transaksi...'); window.location='../layanan_pembayaran/bayar.php?id_pembayaran=$id_pembayaran_baru';</script>";
    exit();
}

/* Koordinat hotel (bisa null jika belum diisi admin) */
$hotel_lat = !empty($data_hotel['latitude'])  ? floatval($data_hotel['latitude'])  : null;
$hotel_lng = !empty($data_hotel['longitude']) ? floatval($data_hotel['longitude']) : null;
$punya_koordinat = ($hotel_lat !== null && $hotel_lng !== null);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Hotel - <?= htmlspecialchars($data_hotel['nama_hotel']); ?></title>

    <link rel="stylesheet" href="../css/style_navigasi.css">
    <link rel="stylesheet" href="../css/style_pesan.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>

<body>

    <?php include '../komponen/navigasi.php'; ?>

    <main class="main-layout">
        <div class="left-column">
            <!-- PENGUBAHAN: Menambahkan class khusus hotel-detail-card -->
            <div class="block-card hotel-detail-card">

                <!-- PENGUBAHAN: Gambar dibungkus container agar bisa diatur mepet kanan-kiri blok -->
                <div class="detail-img-container">
                    <?php
                        $nama_foto = $data_hotel['foto'];
                        $path_foto = (empty($nama_foto) || $nama_foto == 'default.jpg' || !file_exists("../assets/" . $nama_foto))
                            ? "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&h=450&q=80"
                            : "/reservasi_hotel/assets/" . $nama_foto;
                    ?>
                    <img src="<?= $path_foto; ?>" alt="" class="detail-img">

                    <!-- PENGUBAHAN: Tombol kembali khusus mobile di dalam gambar pojok kiri atas -->
                    <a href="javascript:history.back()" class="mobile-back-btn" aria-label="Kembali">
                        <svg viewBox="0 0 24 24" width="24" height="24">
                            <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" fill="#ffffff" />
                        </svg>
                    </a>
                </div>

                <!-- Pembungkus konten teks agar tetap memiliki padding yang rapi -->
                <div class="hotel-info-content">
                    <div class="location-tag">
                        <svg class="icon-location" viewBox="0 0 24 24" width="16" height="16">
                            <path
                                d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"
                                fill="#64748b" />
                        </svg>
                        <span><?= htmlspecialchars($data_hotel['lokasi']); ?></span>
                    </div>
                    <h1 class="hotel-name"><?= htmlspecialchars($data_hotel['nama_hotel']); ?></h1>
                    <p class="hotel-desc"><?= htmlspecialchars($data_hotel['deskripsi']); ?></p>

                    <!-- ===== PETA LOKASI HOTEL ===== -->
                    <div class="hotel-map-section">
                        <div class="hotel-map-title">
                            Lokasi di Peta
                        </div>

                        <?php if ($punya_koordinat): ?>
                        <div id="hotel-leaflet-map"></div>

                        <!-- Tombol Google Maps -->
                        <?php
                        $gmaps_url = "https://www.google.com/maps/dir/?api=1&destination={$hotel_lat},{$hotel_lng}&travelmode=driving";
                        ?>
                        <a href="<?= $gmaps_url; ?>" target="_blank" rel="noopener noreferrer" class="btn-gmaps">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z" />
                            </svg>
                            Lihat Rute
                        </a>

                        <?php else: ?>
                        <div class="no-koordinat-notice">
                            *Peta belum tersedia untuk hotel ini. Admin belum menambahkan koordinat lokasi.
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- ===== END PETA ===== -->
                </div>
            </div>
        </div>

        <div class="right-column">
            <div class="block-card">
                <h2 class="block-title"
                    style="font-size: 1.2rem; font-weight: 700; margin-bottom: 20px; color: #0f172a; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0;">
                    Pemesanan Kamar</h2>

                <div class="realtime-price-box"
                    style="margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span
                        style="font-size: 0.8rem; color: #64748b; font-weight: 600; display: block; margin-bottom: 4px;">Tarif
                        Kamar</span>
                    <div id="displayHarga" style="font-size: 1.6rem; font-weight: 700; color: #dc2626;">
                        Rp - <span style="font-size: 0.85rem; color: #64748b; font-weight: 400;">/malam</span>
                    </div>
                </div>

                <?php if (!isset($_SESSION['peran']) || $_SESSION['peran'] !== 'admin'): ?>
                <form action="" method="POST">
                    <?php if ($hasil_kamar->num_rows > 0): ?>
                    <div class="booking-box" style="margin-bottom: 20px;">
                        <label class="form-label"
                            style="font-weight: 600; color: #475569; font-size: 0.85rem; margin-bottom: 8px; display: block;">Pilih
                            Tipe Kamar</label>
                        <select name="id_kamar" class="form-select" id="pilihKamar" required
                            style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600; background: #fff;">
                            <option value="" data-harga="0" data-diskon="0">Pilih Tipe Kamar</option>
                            <?php
                            $hasil_kamar->data_seek(0);
                            while($kamar = $hasil_kamar->fetch_assoc()):
                                $harga_kamar  = $kamar['harga_per_malam'];
                                $diskon_kamar = intval($kamar['diskon_persen'] ?? 0);
                            ?>
                            <option value="<?= $kamar['id_kamar']; ?>" data-harga="<?= $harga_kamar; ?>"
                                data-diskon="<?= $diskon_kamar; ?>">
                                <?= htmlspecialchars($kamar['nama_kamar']); ?>
                                (<?= htmlspecialchars($kamar['tipe_kamar']); ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="fasilitas-section" id="fasilitasContainer" style="display: none; margin-bottom: 25px;">
                        <div class="fasilitas-title">Fasilitas Kamar Ini:</div>
                        <div class="fasilitas-list" id="daftarFasilitas"></div>
                    </div>

                    <button type="submit" name="proses_pesan" class="btn-booking">Pesan Kamar</button>
                    <?php else: ?>
                    <div class="room-sold-out">Mohon maaf, semua kamar di hotel ini sudah penuh.</div>
                    <?php endif; ?>
                </form>
                <?php else: ?>
                <div
                    style="text-align: center; color: #475569; padding: 15px; background: #f1f5f9; border-radius: 6px; font-weight: 500; font-size: 0.9rem;">
                    Akun Admin
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <section class="rekomendasi-section">
        <h2 class="rekomendasi-title">Hotel Lain di <?= htmlspecialchars($data_hotel['lokasi']); ?></h2>

        <div class="rekomendasi-grid">
            <?php
            $query_rekomendasi = "SELECT h.id_hotel, h.nama_hotel, h.lokasi, h.deskripsi, h.foto, k.harga_per_malam, k.diskon_persen FROM hotel h LEFT JOIN kamar k ON h.id_hotel = k.id_hotel WHERE h.lokasi = ? AND h.id_hotel != ? GROUP BY h.id_hotel LIMIT 8";
            $stmt_rekomendasi = $koneksi->prepare($query_rekomendasi);
            $stmt_rekomendasi->bind_param("si", $data_hotel['lokasi'], $id_hotel);
            $stmt_rekomendasi->execute();
            $hasil_rekomendasi = $stmt_rekomendasi->get_result();

            if ($hasil_rekomendasi->num_rows > 0) {
                while($hotel_rekomendasi = $hasil_rekomendasi->fetch_assoc()) {
                    $nama_foto_rekomendasi = $hotel_rekomendasi['foto'];
                    $path_foto_rekomendasi = (empty($nama_foto_rekomendasi) || $nama_foto_rekomendasi == 'default.jpg' || !file_exists("../assets/" . $nama_foto_rekomendasi))
                        ? "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=400&h=300&q=80"
                        : "/reservasi_hotel/assets/" . $nama_foto_rekomendasi;

                    $harga_original = $hotel_rekomendasi['harga_per_malam'];
                    $diskon         = intval($hotel_rekomendasi['diskon_persen'] ?? 0);
                    $harga_final    = $diskon > 0 ? $harga_original * (100 - $diskon) / 100 : $harga_original;
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
                                            class="price-suffix"> /malam</span></span>
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

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const logoImg = document.querySelector('.brand-logo img');
        if (logoImg) logoImg.src = '/reservasi_hotel/assets/logo/logo.png';
    });

    function openLoginModal(type) {
        if (type === 'login') {
            window.location.href = '../layanan_autentikasi/masuk.php';
        } else if (type === 'daftar') {
            window.location.href = '../layanan_autentikasi/masuk.php?mode=daftar';
        }
    }

    /* ===== LEAFLET MAP INISIALISASI ===== */
    <?php if ($punya_koordinat): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const lat = <?= $hotel_lat; ?>;
        const lng = <?= $hotel_lng; ?>;
        const namaHotel = <?= json_encode(htmlspecialchars($data_hotel['nama_hotel'])); ?>;
        const lokasiHotel = <?= json_encode(htmlspecialchars($data_hotel['lokasi'])); ?>;

        const map = L.map('hotel-leaflet-map').setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        const marker = L.marker([lat, lng]).addTo(map);
        marker.bindPopup(
            `<div style="font-family:sans-serif; min-width:140px;">
                <strong style="font-size:0.9rem;">${namaHotel}</strong><br>
                <span style="font-size:0.8rem; color:#64748b;"> ${lokasiHotel}</span>
            </div>`
        ).openPopup();

        setTimeout(() => map.invalidateSize(), 300);
    });
    <?php endif; ?>

    /* ===== LOGIKA PEMESANAN KAMAR ===== */
    const pilihKamarSelect = document.getElementById('pilihKamar');
    const fasilitasContainer = document.getElementById('fasilitasContainer');
    const daftarFasilitas = document.getElementById('daftarFasilitas');
    const displayHarga = document.getElementById('displayHarga');

    function formatRupiah(angka) {
        return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    if (pilihKamarSelect) {
        pilihKamarSelect.addEventListener('change', async function() {
            const selectedOption = this.options[this.selectedIndex];
            const idKamar = this.value;
            const hargaAsli = parseFloat(selectedOption.getAttribute('data-harga')) || 0;
            const diskonPersen = parseInt(selectedOption.getAttribute('data-diskon')) || 0;

            if (idKamar === '' || hargaAsli === 0) {
                displayHarga.innerHTML =
                    `Rp - <span style="font-size: 0.85rem; color: #64748b; font-weight: 400;">/ malam</span>`;
                fasilitasContainer.style.display = 'none';
                daftarFasilitas.innerHTML = '';
                return;
            }

            let hargaFinal = hargaAsli;
            if (diskonPersen > 0) {
                hargaFinal = hargaAsli * (100 - diskonPersen) / 100;
                displayHarga.innerHTML = `
                    <div style="font-size: 0.85rem; color: #9ca3af; text-decoration: line-through; font-weight: 500;">${formatRupiah(hargaAsli)}</div>
                    <div>${formatRupiah(hargaFinal)} <span class="discount-badge" style="background:#fee2e2; color:#991b1b; font-size:0.7rem; padding:2px 6px; border-radius:4px; margin-left:5px;">-${diskonPersen}%</span> <span style="font-size: 0.85rem; color: #64748b; font-weight: 400;">/ malam</span></div>
                `;
            } else {
                displayHarga.innerHTML =
                    `${formatRupiah(hargaFinal)} <span style="font-size: 0.85rem; color: #64748b; font-weight: 400;">/ malam</span>`;
            }

            try {
                const response = await fetch(`get_fasilitas.php?id_kamar=${idKamar}`);
                const fasilitas = await response.json();

                if (fasilitas.length > 0) {
                    daftarFasilitas.innerHTML = fasilitas.map(item =>
                        `<div class="fasilitas-item">
                            <span style="color: #dc2626; font-weight: bold;">✓</span>
                            <span>${item.nama_fasilitas}</span>
                        </div>`
                    ).join('');
                    fasilitasContainer.style.display = 'block';
                } else {
                    fasilitasContainer.style.display = 'none';
                    daftarFasilitas.innerHTML = '';
                }
            } catch (error) {
                console.error('Error loading fasilitas:', error);
                fasilitasContainer.style.display = 'none';
            }
        });
    }
    </script>
</body>

</html>