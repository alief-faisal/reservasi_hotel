<?php
// Paksa server kirim header no-cache — halaman selalu fresh, tidak pernah disimpan browser
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

/* search bar/pencarian */
$keyword = isset($_GET['cari']) ? $koneksi->real_escape_string($_GET['cari']) : '';

/* Ambil Semua Data Beserta Info Diskon Per Tipe Kamar */
$query = "SELECT h.*,
            MAX(CASE WHEN k.tipe_kamar = 'Standard' THEN k.harga_per_malam END) AS harga_standard,
            MAX(CASE WHEN k.tipe_kamar = 'Standard' THEN k.diskon_persen END) AS diskon_standard,
            MAX(CASE WHEN k.tipe_kamar = 'Deluxe'   THEN k.harga_per_malam END) AS harga_deluxe,
            MAX(CASE WHEN k.tipe_kamar = 'Deluxe'   THEN k.diskon_persen END) AS diskon_deluxe,
            MIN(k.harga_per_malam) AS harga_per_malam,
            MAX(k.diskon_persen)   AS diskon_persen
          FROM hotel h
          LEFT JOIN kamar k ON h.id_hotel = k.id_hotel";
if ($keyword !== '') {
    $query .= " WHERE h.nama_hotel LIKE '%$keyword%' OR h.lokasi LIKE '%$keyword%'";
}
$query .= " GROUP BY h.id_hotel ORDER BY h.lokasi ASC, RAND()";
$hasil_raw = $koneksi->query($query);

$hotel_biasa  = [];
$hotel_diskon = [];

if ($hasil_raw && $hasil_raw->num_rows > 0) {
    while ($row = $hasil_raw->fetch_assoc()) {
        $diskon = intval($row['diskon_persen'] ?? 0);
        if ($diskon >= 50 && $keyword === '') {
            $hotel_diskon[] = $row;
        } else {
            $hotel_biasa[] = $row;
        }
    }
}

$jumlah_hotel_biasa  = count($hotel_biasa);
$jumlah_hotel_diskon = count($hotel_diskon);

/* logika hotel dengan pesanan terbanyak hari ini */
$query_favorit = "SELECT h.id_hotel, COUNT(DISTINCT b.id_pemesanan) as jumlah_pesanan
                  FROM hotel h
                  LEFT JOIN kamar k ON h.id_hotel = k.id_hotel
                  LEFT JOIN pemesanan b ON k.id_kamar = b.id_kamar
                  WHERE DATE(b.dibuat_pada) = CURDATE()
                  GROUP BY h.id_hotel
                  ORDER BY jumlah_pesanan DESC
                  LIMIT 1";
$hasil_favorit    = $koneksi->query($query_favorit);
$hotel_favorit_id = null;
if ($hasil_favorit && $hasil_favorit->num_rows > 0) {
    $favorit_row      = $hasil_favorit->fetch_assoc();
    $hotel_favorit_id = $favorit_row['id_hotel'];
}

/* logika button love hilang ketika login role admin */
$loved_ids = [];
if (isset($_SESSION['id_pengguna']) && ($_SESSION['peran'] ?? '') !== 'admin') {
    $id_sesi = intval($_SESSION['id_pengguna']);
    $q_love  = $koneksi->query("SELECT id_hotel FROM wishlist WHERE id_pengguna = $id_sesi");
    if ($q_love) {
        while ($r = $q_love->fetch_assoc()) {
            $loved_ids[] = intval($r['id_hotel']);
        }
    }
}

function getBadgeDiskon(array $row): array|false {
    $ds = intval($row['diskon_standard'] ?? 0);
    $dd = intval($row['diskon_deluxe']   ?? 0);

    if ($ds > 0 && $dd > 0) {
        return ['label' => "Diskon Deluxe -{$dd}%", 'class' => 'badge-diskon-deluxe'];
    } elseif ($dd > 0) {
        return ['label' => "Diskon Deluxe -{$dd}%", 'class' => 'badge-diskon-deluxe'];
    } elseif ($ds > 0) {
        return ['label' => "Diskon Standard -{$ds}%", 'class' => 'badge-diskon-standard'];
    }
    return false;
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
    <!-- Paksa browser selalu reload, tidak boleh cache halaman ini -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <?php include 'komponen/style.php'; ?>
    <link rel="stylesheet" href="/reservasi_hotel/css/love.css">
</head>

<body>

    <?php include 'komponen/navigasi.php'; ?>
    <?php include 'komponen/banner_slider.php'; ?>

    <main class="container">
        <h2 class="section-title">
            <?= $keyword !== '' ? "Hasil Pencarian untuk: '" . $keyword . "'" : "Temukan Hotel dan dapatkan penawaran terbaik!" ?>
        </h2>

        <section class="flex-hotel" style="display: block !important;">

            <!-- ============================================================
                 SKELETON CONTAINER
                 Terdiri dari 3 bagian sesuai struktur halaman asli:
                   1. Grid card biasa (4 kolom)
                   2. Section diskon biru (slider)
                   3. Card horizontal/list (baris bawah)
                 Disembunyikan via JS setelah konten asli siap.
                 ============================================================ -->
            <div id="skeleton-container" style="width:100% !important; display:block !important;">

                <!-- ── 1. SKELETON GRID CARD BIASA (4 kolom) ── -->
                <div class="grid-4-kolom">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="card-hotel" style="box-shadow:none; overflow:hidden;">

                        <!-- Gambar -->
                        <div class="sk-card-img shimmer"></div>

                        <!-- Badge diskon strip menempel di bawah gambar -->
                        <div class="sk-badge-strip shimmer"></div>

                        <div class="sk-card-body">
                            <!-- Judul hotel -->
                            <div class="sk-title shimmer"></div>
                            <!-- Bintang rating -->
                            <div class="sk-stars shimmer"></div>
                            <!-- Lokasi -->
                            <div class="sk-location shimmer"></div>
                            <!-- Harga coret -->
                            <div class="sk-price-old shimmer"></div>
                            <!-- Harga final -->
                            <div class="sk-price shimmer"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- ── 2. SKELETON SECTION DISKON (latar biru) ── -->
                <div class="container-diskon-tengah">
                    <section class="section-diskon-besar">

                        <!-- Judul section -->
                        <div class="shimmer-dark"
                            style="height:24px; width:220px; margin-bottom:30px; border-radius:4px;"></div>

                        <!-- 4 card diskon dalam flex row -->
                        <div style="display:flex; gap:20px; overflow:hidden;">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="card-hotel" style="
                                flex: 0 0 calc((100% - 60px) / 4);
                                min-width: 0;
                                box-shadow: none;
                                overflow: hidden;
                                background: rgba(255,255,255,0.08);
                                border: 1px solid rgba(255,255,255,0.15);
                            ">
                                <!-- Gambar -->
                                <div class="sk-card-img shimmer-dark"></div>
                                <!-- Badge diskon strip -->
                                <div class="sk-badge-strip shimmer-dark"></div>

                                <div class="sk-card-body">
                                    <div class="sk-title    shimmer-dark"></div>
                                    <div class="sk-stars    shimmer-dark"></div>
                                    <div class="sk-location shimmer-dark"></div>
                                    <!-- Card diskon selalu punya harga coret -->
                                    <div class="sk-price-old shimmer-dark"></div>
                                    <div class="sk-price     shimmer-dark"></div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>

                    </section>
                </div>

                <!-- ── 3. SKELETON CARD LIST HORIZONTAL (baris bawah) ── -->

                <!-- Label lokasi skeleton -->
                <div class="shimmer" style="height:22px; width:260px; margin-bottom:16px; border-radius:4px;"></div>

                <div style="display:flex; flex-direction:column; gap:20px; margin-bottom:30px;">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="sk-list-card">

                        <!-- Kiri: gambar -->
                        <div class="sk-list-img shimmer"></div>

                        <!-- Tengah: nama, bintang, lokasi -->
                        <div class="sk-list-center">
                            <div class="sk-list-title    shimmer"></div>
                            <div class="sk-list-stars    shimmer"></div>
                            <div class="sk-list-location shimmer"></div>
                        </div>

                        <!-- Kanan: harga Standard & Deluxe -->
                        <div class="sk-list-right">
                            <!-- Standard -->
                            <div class="sk-list-room-label shimmer"></div>
                            <div class="sk-list-price-old  shimmer"></div>
                            <div class="sk-list-price      shimmer"></div>

                            <div class="sk-list-divider"></div>

                            <!-- Deluxe -->
                            <div class="sk-list-room-label shimmer"></div>
                            <div class="sk-list-price-old  shimmer"></div>
                            <div class="sk-list-price      shimmer"></div>
                        </div>

                    </div>
                    <?php endfor; ?>
                </div>

            </div>
            <!-- ── END SKELETON CONTAINER ── -->


            <!-- ============================================================
                 KONTEN ASLI
                 ============================================================ -->
            <div id="actual-content" style="width:100% !important; display:block !important;">
                <?php if ($jumlah_hotel_biasa > 0 || $jumlah_hotel_diskon > 0): ?>

                <!-- ── BARIS 1: GRID CARD BIASA (maks 4) ── -->
                <div class="grid-4-kolom">
                    <?php
                    $counter = 0;
                    for ($i = 0; $i < $jumlah_hotel_biasa; $i++):
                        $row      = $hotel_biasa[$i];
                        $counter++;
                        $id_hotel = $row['id_hotel'];
                        $badge    = getBadgeDiskon($row);
                    ?>
                    <a href="/reservasi_hotel/layanan_pemesanan/pesan.php?id_hotel=<?= $row['id_hotel']; ?>"
                        class="card-link">
                        <article class="card-hotel">
                            <?php
                            $nama_foto = $row['foto'];
                            $path_foto = (empty($nama_foto) || $nama_foto == 'default.jpg' || !file_exists("assets/" . $nama_foto))
                                ? "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=400&q=80"
                                : "/reservasi_hotel/assets/" . $nama_foto;
                            ?>

                            <?php if ($hotel_favorit_id == $row['id_hotel']): ?>
                            <div class="favorite-badge">Favorit</div>
                            <?php endif; ?>

                            <?php include 'komponen/btn_love.php'; ?>

                            <div class="img-wrapper">
                                <img src="<?= $path_foto; ?>" alt="" class="card-img">
                                <?php if ($badge): ?>
                                <span class="badge-diskon-strip <?= $badge['class']; ?>">
                                    🏷️ <?= $badge['label']; ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <h3 class="card-title"><?= htmlspecialchars($row['nama_hotel']); ?></h3>
                                <?php $rating = intval($row['rating'] ?? 0); ?>
                                <?php if ($rating > 0): ?>
                                <div class="card-rating">
                                    <div class="rating-stars">
                                        <?php for ($j = 1; $j <= $rating; $j++): ?>
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
                                        $diskon         = intval($row['diskon_persen'] ?? 0);
                                        $harga_final    = $diskon > 0 ? $harga_original * (100 - $diskon) / 100 : $harga_original;
                                        ?>
                                        <?php if ($diskon > 0): ?>
                                        <div class="price-row">
                                            <span class="price-original"
                                                style="position:relative; display:inline-block; color:#9ca3af;">IDR
                                                <?= number_format($harga_original, 0, ',', '.'); ?><span
                                                    style="position:absolute; left:0; right:0; top:50%; height:2px; background:#ef4444; transform:rotate(-3deg);"></span></span>
                                            <span class="discount-badge"
                                                style="color:#991b1b; background:#fee2e2;">-<?= $diskon; ?>%</span>
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
                    <?php
                        if ($counter == 4) break;
                    endfor;
                    ?>
                </div>

                <!-- ── BARIS 2: SECTION DISKON (slider biru) ── -->
                <?php if ($jumlah_hotel_diskon > 0): ?>
                <div class="container-diskon-tengah">
                    <section class="section-diskon-besar">
                        <h3 class="section-title-diskon">Diskon nginep lebih dari 50%!</h3>

                        <div class="slider-diskon-wrapper">
                            <button class="chevron-btn chevron-left" aria-label="Slide Left">
                                <svg viewBox="0 0 24 24">
                                    <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" />
                                </svg>
                            </button>
                            <button class="chevron-btn chevron-right" aria-label="Slide Right">
                                <svg viewBox="0 0 24 24">
                                    <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z" />
                                </svg>
                            </button>

                            <div class="grid-diskon-slider">
                                <?php foreach ($hotel_diskon as $row_diskon):
                                    $id_hotel     = $row_diskon['id_hotel'];
                                    $badge_diskon = getBadgeDiskon($row_diskon);
                                ?>
                                <a href="/reservasi_hotel/layanan_pemesanan/pesan.php?id_hotel=<?= $row_diskon['id_hotel']; ?>"
                                    class="card-link">
                                    <article class="card-hotel">
                                        <?php
                                        $nama_foto = $row_diskon['foto'];
                                        $path_foto = (empty($nama_foto) || $nama_foto == 'default.jpg' || !file_exists("assets/" . $nama_foto))
                                            ? "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=400&q=80"
                                            : "/reservasi_hotel/assets/" . $nama_foto;
                                        ?>
                                        <?php if ($hotel_favorit_id == $row_diskon['id_hotel']): ?>
                                        <div class="favorite-badge">Terlaris</div>
                                        <?php endif; ?>

                                        <?php include 'komponen/btn_love.php'; ?>

                                        <div class="img-wrapper">
                                            <img src="<?= $path_foto; ?>" alt="" class="card-img">
                                            <?php if ($badge_diskon): ?>
                                            <span class="badge-diskon-strip <?= $badge_diskon['class']; ?>">
                                                🏷️ <?= $badge_diskon['label']; ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="card-body">
                                            <h3 class="card-title"><?= htmlspecialchars($row_diskon['nama_hotel']); ?>
                                            </h3>
                                            <?php $rating_diskon = intval($row_diskon['rating'] ?? 0); ?>
                                            <?php if ($rating_diskon > 0): ?>
                                            <div class="card-rating">
                                                <div class="rating-stars">
                                                    <?php for ($j = 1; $j <= $rating_diskon; $j++): ?>
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
                                                    $diskon         = intval($row_diskon['diskon_persen'] ?? 0);
                                                    $harga_final    = $diskon > 0 ? $harga_original * (100 - $diskon) / 100 : $harga_original;
                                                    ?>
                                                    <div class="price-row">
                                                        <span class="price-original"
                                                            style="position:relative; display:inline-block; color:#9ca3af;">IDR
                                                            <?= number_format($harga_original, 0, ',', '.'); ?><span
                                                                style="position:absolute; left:0; right:0; top:50%; height:2px; background:#ef4444; transform:rotate(-3deg);"></span></span>
                                                        <span class="discount-badge"
                                                            style="color:#991b1b; background:#fee2e2;">-<?= $diskon; ?>%</span>
                                                    </div>
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
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                </div>
                <?php endif; ?>

                <!-- ── BARIS 3: CARD LIST HORIZONTAL (per lokasi) ── -->
                <?php
                if ($jumlah_hotel_biasa > 4):
                    $current_location = "";
                    echo '<div class="list-container-vertical">';

                    for ($i = 4; $i < $jumlah_hotel_biasa; $i++):
                        $row          = $hotel_biasa[$i];
                        $id_hotel     = $row['id_hotel'];
                        $badge        = getBadgeDiskon($row);
                        $lokasi_hotel = htmlspecialchars($row['lokasi']);

                        if ($lokasi_hotel !== $current_location) {
                            $current_location = $lokasi_hotel;
                            echo '<h3 class="sub-section-location-title">Hotel Pilihan di ' . $current_location . '</h3>';
                        }

                        $nama_foto = $row['foto'];
                        $path_foto = (empty($nama_foto) || $nama_foto == 'default.jpg' || !file_exists("assets/" . $nama_foto))
                            ? "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=400&q=80"
                            : "/reservasi_hotel/assets/" . $nama_foto;

                        $rating = intval($row['rating'] ?? 0);

                        /* Harga Standard */
                        $h_standard     = floatval($row['harga_standard'] ?? 0);
                        $d_standard     = intval($row['diskon_standard']  ?? 0);
                        $final_standard = $d_standard > 0 ? $h_standard * (100 - $d_standard) / 100 : $h_standard;

                        /* Harga Deluxe */
                        $h_deluxe       = floatval($row['harga_deluxe']  ?? 0);
                        $d_deluxe       = intval($row['diskon_deluxe']   ?? 0);
                        $final_deluxe   = $d_deluxe > 0 ? $h_deluxe * (100 - $d_deluxe) / 100 : $h_deluxe;
                ?>
                <a href="/reservasi_hotel/layanan_pemesanan/pesan.php?id_hotel=<?= $row['id_hotel']; ?>"
                    class="card-hotel-list">

                    <!-- KIRI: GAMBAR -->
                    <div class="list-img-section">
                        <?php if ($hotel_favorit_id == $row['id_hotel']): ?>
                        <div class="favorite-badge" style="position:absolute; top:10px; left:10px; z-index:3;">Favorit
                        </div>
                        <?php endif; ?>

                        <?php include 'komponen/btn_love.php'; ?>
                        <img src="<?= $path_foto; ?>" alt="" class="list-img">

                        <?php if ($badge): ?>
                        <span class="badge-diskon-strip <?= $badge['class']; ?>">
                            🏷️ <?= $badge['label']; ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- TENGAH: DETAIL INFO -->
                    <div class="list-center-section">
                        <h3 class="list-hotel-title"><?= htmlspecialchars($row['nama_hotel']); ?></h3>

                        <?php if ($rating > 0): ?>
                        <div class="list-stars">
                            <?php for ($j = 1; $j <= $rating; $j++): ?>
                            <svg class="star-icon" viewBox="0 0 24 24">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>

                        <div class="list-location"><?= $lokasi_hotel; ?></div>
                    </div>

                    <!-- KANAN: HARGA STANDARD & DELUXE -->
                    <div class="list-right-section">
                        <!-- Kamar Standard -->
                        <div class="room-price-row">
                            <div class="room-header-top">
                                <span class="room-type-title title-standard">Kamar Standard</span>
                                <?php if ($h_standard > 0 && $d_standard > 0): ?>
                                <span class="discount-badge">-<?= $d_standard; ?>%</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($h_standard > 0): ?>
                            <?php if ($d_standard > 0): ?>
                            <div class="price-original-wrapper">
                                <span class="price-original">IDR <?= number_format($h_standard, 0, ',', '.'); ?></span>
                            </div>
                            <?php endif; ?>
                            <h4 class="list-final-price">IDR <?= number_format($final_standard, 0, ',', '.'); ?><span
                                    class="list-price-suffix"> /malam</span></h4>
                            <?php else: ?>
                            <span class="list-price-suffix" style="font-style:italic;">Tidak tersedia</span>
                            <?php endif; ?>
                        </div>

                        <div class="divider-line"></div>

                        <!-- Kamar Deluxe -->
                        <div class="room-price-row">
                            <div class="room-header-top">
                                <span class="room-type-title title-deluxe">Kamar Deluxe</span>
                                <?php if ($h_deluxe > 0 && $d_deluxe > 0): ?>
                                <span class="discount-badge">-<?= $d_deluxe; ?>%</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($h_deluxe > 0): ?>
                            <?php if ($d_deluxe > 0): ?>
                            <div class="price-original-wrapper">
                                <span class="price-original">IDR <?= number_format($h_deluxe, 0, ',', '.'); ?></span>
                            </div>
                            <?php endif; ?>
                            <h4 class="list-final-price">IDR <?= number_format($final_deluxe, 0, ',', '.'); ?><span
                                    class="list-price-suffix"> /malam</span></h4>
                            <?php else: ?>
                            <span class="list-price-suffix" style="font-style:italic;">Tidak tersedia</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php
                    endfor;
                    echo '</div>';
                endif;
                ?>

                <?php else: ?>
                <div class="empty-state">Tidak ditemukan Hotel yang cocok dengan kata kunci tersebut.</div>
                <?php endif; ?>
            </div>
            <!-- ── END KONTEN ASLI ── -->

        </section>
    </main>

    <?php include 'komponen/modal_login.php'; ?>
    <?php include 'komponen/script.php'; ?>

    <!-- SCRIPT: Sembunyikan skeleton, tampilkan konten asli setelah halaman siap -->
    <script>
    // Paksa reload saat halaman muncul dari cache back/forward browser (bfcache)
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) window.location.reload();
    });

    document.addEventListener("DOMContentLoaded", function() {
        const skeleton = document.getElementById('skeleton-container');
        const content = document.getElementById('actual-content');

        // ── ATUR DURASI SKELETON DI SINI ──────────────────────────────
        const MINIMUM_DELAY = 1500; // ms — skeleton tampil minimal 1.5 detik
        const FALLBACK_MAX = 5000; // ms — paksa tampil konten setelah 5 detik
        // ──────────────────────────────────────────────────────────────

        const startTime = Date.now();

        // Fungsi tampilkan konten & sembunyikan skeleton
        // Selalu tunggu sisa minimum delay dulu sebelum tampil
        function showContent() {
            const elapsed = Date.now() - startTime;
            const remaining = Math.max(0, MINIMUM_DELAY - elapsed);

            setTimeout(function() {
                skeleton.style.opacity = '0';
                skeleton.style.transition = 'opacity 0.3s ease';
                setTimeout(function() {
                    skeleton.style.display = 'none';
                    content.style.opacity = '1';
                    content.style.pointerEvents = 'auto';
                }, 300); // tunggu fade-out skeleton selesai
            }, remaining);
        }

        // Cek apakah semua gambar di konten asli sudah selesai dimuat
        const images = content.querySelectorAll('img');
        if (images.length === 0) {
            showContent();
            return;
        }

        let loaded = 0;

        function onImageDone() {
            loaded++;
            if (loaded >= images.length) showContent();
        }

        images.forEach(img => {
            if (img.complete) {
                onImageDone();
            } else {
                img.addEventListener('load', onImageDone);
                img.addEventListener('error', onImageDone); // tetap lanjut walau gambar gagal
            }
        });

        // Fallback: paksa tampil setelah FALLBACK_MAX walau gambar belum selesai
        setTimeout(showContent, FALLBACK_MAX);
    });
    </script>

    <!-- SCRIPT SLIDER DISKON (DRAG + CHEVRON) -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const wrappers = document.querySelectorAll('.slider-diskon-wrapper');

        wrappers.forEach(wrapper => {
            const slider = wrapper.querySelector('.grid-diskon-slider');
            const btnLeft = wrapper.querySelector('.chevron-left');
            const btnRight = wrapper.querySelector('.chevron-right');

            let isDown = false,
                startX, scrollLeft, isDragging = false;

            /* Tampilkan/sembunyikan chevron sesuai posisi scroll */
            function updateChevronVisibility() {
                if (window.innerWidth > 768) {
                    if (slider.scrollWidth <= slider.clientWidth) {
                        btnLeft.style.display = 'none';
                        btnRight.style.display = 'none';
                        return;
                    }
                    btnLeft.style.display = slider.scrollLeft > 5 ? 'flex' : 'none';
                    btnRight.style.display = (slider.scrollWidth - slider.clientWidth - slider
                        .scrollLeft) > 5 ? 'flex' : 'none';
                } else {
                    btnLeft.style.display = 'none';
                    btnRight.style.display = 'none';
                }
            }

            updateChevronVisibility();
            window.addEventListener('resize', updateChevronVisibility);
            slider.addEventListener('scroll', updateChevronVisibility);

            /* Klik chevron: geser satu lebar kartu */
            if (btnLeft && btnRight) {
                const getScrollAmount = () => {
                    const firstCard = slider.querySelector('.card-link');
                    return firstCard ? firstCard.clientWidth + 20 : 300;
                };
                btnLeft.addEventListener('click', () => slider.scrollBy({
                    left: -getScrollAmount(),
                    behavior: 'smooth'
                }));
                btnRight.addEventListener('click', () => slider.scrollBy({
                    left: getScrollAmount(),
                    behavior: 'smooth'
                }));
            }

            /* Drag mouse */
            wrapper.addEventListener('mousedown', (e) => {
                if (e.target.closest('.chevron-btn')) return;
                isDown = true;
                isDragging = false;
                wrapper.style.cursor = 'grabbing';
                startX = e.pageX - slider.offsetLeft;
                scrollLeft = slider.scrollLeft;
            });
            wrapper.addEventListener('mouseleave', () => {
                isDown = false;
                wrapper.style.cursor = 'grab';
            });
            wrapper.addEventListener('mouseup', () => {
                isDown = false;
                wrapper.style.cursor = 'grab';
            });
            wrapper.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                isDragging = true;
                e.preventDefault();
                slider.scrollLeft = scrollLeft - (e.pageX - slider.offsetLeft - startX) * 1.5;
            });

            /* Cegah klik link saat drag */
            wrapper.querySelectorAll('.card-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    if (isDragging) e.preventDefault();
                });
            });
        });
    });
    </script>

    <!-- SCRIPT LOVE / WISHLIST -->
    <script>
    const userLoggedIn = <?= isset($_SESSION['id_pengguna']) ? 'true' : 'false'; ?>;

    function toggleLove(event, btn) {
        event.preventDefault();
        event.stopPropagation();
        if (!userLoggedIn) {
            window.location.href = '/reservasi_hotel/layanan_autentikasi/masuk.php';
            return;
        }
        const idHotel = btn.getAttribute('data-hotel-id');
        btn.disabled = true;
        fetch('/reservasi_hotel/layanan_wishlist/toggle_love.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id_hotel=' + idHotel
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (data.loved) {
                        btn.classList.add('loved');
                        btn.setAttribute('data-loved', '1');
                        btn.classList.remove('love-animate');
                        void btn.offsetWidth;
                        btn.classList.add('love-animate');
                    } else {
                        btn.classList.remove('loved', 'love-animate');
                        btn.setAttribute('data-loved', '0');
                    }
                    updateNavLoveBadge(data.total);
                }
            })
            .catch(err => console.error('Toggle love error:', err))
            .finally(() => {
                btn.disabled = false;
            });
    }

    function updateNavLoveBadge(total) {
        const badge = document.querySelector('.nav-love-badge');
        if (!badge) return;
        badge.setAttribute('data-count', total);
        if (total > 0) {
            badge.textContent = total;
            badge.style.display = 'flex';
        } else {
            badge.textContent = '';
            badge.style.display = 'none';
        }
    }
    </script>

</body>

</html>