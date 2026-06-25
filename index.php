<?php
session_start();
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

/* search bar/pencarian */
$keyword = isset($_GET['cari']) ? $koneksi->real_escape_string($_GET['cari']) : '';

/* ambil data hotel */
$query = "SELECT h.*, k.harga_per_malam, k.diskon_persen FROM hotel h LEFT JOIN kamar k ON h.id_hotel = k.id_hotel";
if ($keyword !== '') {
    $query .= " WHERE h.nama_hotel LIKE '%$keyword%' OR h.lokasi LIKE '%$keyword%'";
}
$query .= " GROUP BY h.id_hotel ORDER BY RAND()";
$hasil = $koneksi->query($query);
$jumlah_hotel = $hasil->num_rows;

/* ambil data hotel dengan diskon 50% ke atas */
$query_diskon = "SELECT h.*, k.harga_per_malam, k.diskon_persen FROM hotel h LEFT JOIN kamar k ON h.id_hotel = k.id_hotel WHERE k.diskon_persen >= 50 GROUP BY h.id_hotel ORDER BY k.diskon_persen DESC LIMIT 4";
$hasil_diskon = $koneksi->query($query_diskon);
$jumlah_diskon = $hasil_diskon ? $hasil_diskon->num_rows : 0;

/* ambil hotel dengan pesanan terbanyak hari ini */
$query_favorit = "SELECT h.id_hotel, COUNT(DISTINCT b.id_pemesanan) as jumlah_pesanan
                  FROM hotel h
                  LEFT JOIN kamar k ON h.id_hotel = k.id_hotel
                  LEFT JOIN pemesanan b ON k.id_kamar = b.id_kamar
                  WHERE DATE(b.dibuat_pada) = CURDATE()
                  GROUP BY h.id_hotel
                  ORDER BY jumlah_pesanan DESC
                  LIMIT 1";
$hasil_favorit = $koneksi->query($query_favorit);
$hotel_favorit_id = null;
if ($hasil_favorit && $hasil_favorit->num_rows > 0) {
    $favorit_row = $hasil_favorit->fetch_assoc();
    $hotel_favorit_id = $favorit_row['id_hotel'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kel1 | reservasi_hotel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap"
        rel="stylesheet">
    <?php include 'komponen/style.php'; ?>
</head>

<body>

    <?php include 'komponen/navigasi.php'; ?>

    <!-- Banner Slider -->
    <?php include 'komponen/banner_slider.php'; ?>

    <main class="container">
        <h2 class="section-title">
            <?= $keyword !== '' ? "Hasil Pencarian untuk: '" . $keyword . "'" : "Temukan Hotel dan dapatkan penawaran terbaik!" ?>
        </h2>

        <section class="flex-hotel">
            <div id="skeleton-container">
                <?php for($i = 0; $i < $jumlah_hotel; $i++): ?>
                <div class="card-hotel" style="box-shadow: none;">
                    <div class="shimmer" style="width: 100%; height: 180px;"></div>
                    <div class="card-body">
                        <div class="shimmer skeleton-text" style="width: 40%;"></div>
                        <div class="shimmer skeleton-text" style="width: 80%; height: 20px; margin-bottom: 16px;"></div>
                        <div class="shimmer skeleton-text" style="width: 100%;"></div>
                        <div class="shimmer skeleton-text" style="width: 90%;"></div>
                        <div class="shimmer skeleton-text" style="width: 60%; margin-bottom: auto;"></div>
                        <div class="shimmer skeleton-text"
                            style="width: 50%; height: 35px; margin-bottom: 0; border-radius: 6px;"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <div id="actual-content">
                <?php if ($hasil->num_rows > 0): ?>
                <?php while($row = $hasil->fetch_assoc()): ?>
                <a href="/reservasi_hotel/layanan_pemesanan/pesan.php?id_hotel=<?= $row['id_hotel']; ?>"
                    class="card-link">
                    <article class="card-hotel">
                        <?php
                            $nama_foto = $row['foto'];
                            if(empty($nama_foto) || $nama_foto == 'default.jpg' || !file_exists("assets/" . $nama_foto)) {
                                $path_foto = "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=400&q=80";
                            } else {
                                $path_foto = "/reservasi_hotel/assets/" . $nama_foto;
                            }
                        ?>
                        <?php if ($hotel_favorit_id == $row['id_hotel']): ?>
                        <div class="favorite-badge">Favorit</div>
                        <?php endif; ?>
                        <div class="img-wrapper">
                            <img src="<?= $path_foto; ?>" alt="" class="card-img">
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($row['nama_hotel']); ?></h3>
                            <?php $rating = intval($row['rating'] ?? 0); ?>
                            <?php if ($rating > 0): ?>
                            <div class="card-rating">
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= $rating; $i++): ?>
                                    <svg class="star-icon" viewBox="0 0 24 24">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="card-meta">
                                <span><?= htmlspecialchars($row['lokasi']); ?></span>
                            </div>
                            <div class="price-wrapper">
                                <span class="price-amount">
                                    <?php
                                        $harga_original = $row['harga_per_malam'];
                                        $diskon = intval($row['diskon_persen'] ?? 0);
                                        $harga_final = $diskon > 0 ? $harga_original * (100 - $diskon) / 100 : $harga_original;
                                    ?>
                                    <?php if ($diskon > 0): ?>
                                    <div class="price-row">
                                        <span class="price-original">IDR
                                            <?= number_format($harga_original, 0, ',', '.'); ?></span>
                                        <span class="discount-badge">-<?= $diskon; ?>%</span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="price-row">
                                        <span>IDR
                                            <?= $harga_final ? number_format($harga_final, 0, ',', '.') : '-'; ?><span
                                                class="price-suffix">/Malam</span></span>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </article>
                </a>
                <?php endwhile; ?>
                <?php else: ?>
                <div class="empty-state">Tidak ditemukan Hotel yang cocok dengan kata kunci tersebut.</div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Section Diskon Besar 50% ke atas -->
        <?php if ($jumlah_diskon > 0 && $keyword === ''): ?>
        <section class="section-diskon-besar">
            <h3 class="section-title-diskon">Diskon nginep s.d. 50%</h3>
            <div class="grid-diskon">
                <?php
                    $hasil_diskon->data_seek(0);
                    while($row_diskon = $hasil_diskon->fetch_assoc()):
                ?>
                <a href="/reservasi_hotel/layanan_pemesanan/pesan.php?id_hotel=<?= $row_diskon['id_hotel']; ?>"
                    class="card-link">
                    <article class="card-hotel">
                        <?php
                            $nama_foto = $row_diskon['foto'];
                            if(empty($nama_foto) || $nama_foto == 'default.jpg' || !file_exists("assets/" . $nama_foto)) {
                                $path_foto = "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=400&q=80";
                            } else {
                                $path_foto = "/reservasi_hotel/assets/" . $nama_foto;
                            }
                        ?>
                        <?php if ($hotel_favorit_id == $row_diskon['id_hotel']): ?>
                        <div class="favorite-badge">Favorit</div>
                        <?php endif; ?>
                        <div class="img-wrapper">
                            <img src="<?= $path_foto; ?>" alt="" class="card-img">
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($row_diskon['nama_hotel']); ?></h3>
                            <?php $rating_diskon = intval($row_diskon['rating'] ?? 0); ?>
                            <?php if ($rating_diskon > 0): ?>
                            <div class="card-rating">
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= $rating_diskon; $i++): ?>
                                    <svg class="star-icon" viewBox="0 0 24 24">
                                        <path
                                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="card-meta">
                                <span><?= htmlspecialchars($row_diskon['lokasi']); ?></span>
                            </div>
                            <div class="price-wrapper">
                                <span class="price-amount">
                                    <?php
                                        $harga_original = $row_diskon['harga_per_malam'];
                                        $diskon = intval($row_diskon['diskon_persen'] ?? 0);
                                        $harga_final = $diskon > 0 ? $harga_original * (100 - $diskon) / 100 : $harga_original;
                                    ?>
                                    <?php if ($diskon > 0): ?>
                                    <div class="price-row">
                                        <span class="price-original">IDR
                                            <?= number_format($harga_original, 0, ',', '.'); ?></span>
                                        <span class="discount-badge">-<?= $diskon; ?>%</span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="price-row">
                                        <span>IDR
                                            <?= $harga_final ? number_format($harga_final, 0, ',', '.') : '-'; ?><span
                                                class="price-suffix">/Malam</span></span>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </article>
                </a>
                <?php endwhile; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- Pop-up Login & Daftar -->
    <?php include 'komponen/modal_login.php'; ?>
    <?php include 'komponen/script.php'; ?>

</body>

</html>