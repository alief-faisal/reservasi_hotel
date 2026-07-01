<?php
// Paksa server kirim header no-cache — halaman selalu fresh, tidak pernah disimpan browser
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

/* search bar/pencarian */
$keyword = isset($_GET['cari']) ? $koneksi->real_escape_string($_GET['cari']) : '';

/* filter lokasi dari dropdown navigasi */
$lokasi_filter = isset($_GET['lokasi']) ? $koneksi->real_escape_string($_GET['lokasi']) : '';

/* koordinat GPS untuk sorting terdekat */
$user_lat = isset($_GET['latitude']) ? floatval($_GET['latitude']) : null;
$user_lng = isset($_GET['longitude']) ? floatval($_GET['longitude']) : null;
$mode_terdekat = ($user_lat !== null && $user_lng !== null);

/* Kalau ada filter aktif (pencarian / lokasi / mode terdekat),
   tampilan cukup pakai card list horizontal saja (baris 3),
   tanpa grid 4 kolom & tanpa section diskon biru di atasnya.
   Parameter ?lokasi= yang sengaja dikirim kosong (misalnya user klik
   "Semua Lokasi" di dropdown) tetap dihitung sebagai filter aktif,
   bukan disamakan dengan kondisi halaman awal tanpa filter sama sekali. */
$lokasi_param_dikirim = isset($_GET['lokasi']);
$ada_filter_aktif = ($keyword !== '' || $lokasi_filter !== '' || $mode_terdekat || $lokasi_param_dikirim);

/* ============================================================
   Ambil Semua Data Beserta Info Diskon Per Tipe Kamar
   Pastikan kolom lat, lng ikut diambil dari tabel hotel
   ============================================================ */
$query = "SELECT h.*,
            MAX(CASE WHEN k.tipe_kamar = 'Standard' THEN k.harga_per_malam END) AS harga_standard,
            MAX(CASE WHEN k.tipe_kamar = 'Standard' THEN k.diskon_persen END) AS diskon_standard,
            MAX(CASE WHEN k.tipe_kamar = 'Deluxe'   THEN k.harga_per_malam END) AS harga_deluxe,
            MAX(CASE WHEN k.tipe_kamar = 'Deluxe'   THEN k.diskon_persen END) AS diskon_deluxe,
            MIN(k.harga_per_malam) AS harga_per_malam,
            MAX(k.diskon_persen)   AS diskon_persen
          FROM hotel h
          LEFT JOIN kamar k ON h.id_hotel = k.id_hotel";

$where_clauses = [];
if ($keyword !== '') {
    $where_clauses[] = "(h.nama_hotel LIKE '%$keyword%' OR h.lokasi LIKE '%$keyword%')";
}
if ($lokasi_filter !== '') {
    $where_clauses[] = "h.lokasi = '$lokasi_filter'";
}
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
}
$query .= " GROUP BY h.id_hotel ORDER BY h.lokasi ASC, RAND()";
$hasil_raw = $koneksi->query($query);

$hotel_biasa  = [];
$hotel_diskon = [];

if ($hasil_raw && $hasil_raw->num_rows > 0) {
    while ($row = $hasil_raw->fetch_assoc()) {
        /* Hitung jarak jika mode GPS aktif */
        if ($mode_terdekat
    && isset($row['latitude']) && isset($row['longitude'])
    && $row['latitude'] !== null && $row['longitude'] !== null
    && $row['latitude'] !== '' && $row['longitude'] !== '') {
            $row['_jarak_km'] = hitungJarak($user_lat, $user_lng, floatval($row['latitude']), floatval($row['longitude']));
        } else {
            $row['_jarak_km'] = null;
        }

        $diskon = intval($row['diskon_persen'] ?? 0);
        /*
         * Hotel diskon >= 50% masuk ke seksi biru (baris kedua) HANYA saat
         * tidak ada filter aktif (halaman awal), supaya tidak nyasar ke
         * baris pertama / baris ketiga.
         *
         * Tapi saat sedang search/filter/lokasi terdekat, hotel diskon >=50%
         * tetap harus muncul di hasil pencarian — hanya saja tampilannya
         * disamakan dengan card list horizontal biasa (baris ketiga),
         * bukan pakai layout slider biru khusus. Makanya di sini mereka
         * dimasukkan ke $hotel_biasa supaya ikut render di baris ketiga.
         */
        if ($diskon >= 50 && !$ada_filter_aktif) {
            $hotel_diskon[] = $row;
        } else {
            $hotel_biasa[] = $row;
        }
    }
}

/* Jika mode terdekat, urutkan hotel_biasa berdasarkan jarak */
if ($mode_terdekat) {
    usort($hotel_biasa, function($a, $b) {
        $ja = $a['_jarak_km'] ?? PHP_FLOAT_MAX;
        $jb = $b['_jarak_km'] ?? PHP_FLOAT_MAX;
        return $ja <=> $jb;
    });
}

$jumlah_hotel_biasa  = count($hotel_biasa);
$jumlah_hotel_diskon = count($hotel_diskon);

/* ============================================================
   QUERY TERPISAH KHUSUS CARD BARIS PERTAMA (grid 4 kolom)
   ----------------------------------------------------------
   Kenapa perlu query terpisah?
   Query utama di atas pakai "ORDER BY h.lokasi ASC, RAND()".
   Itu artinya data diurutkan dulu berdasarkan lokasi (A-Z),
   baru di-random DI DALAM tiap grup lokasi yang sama.
   Akibatnya, hotel yang nongol duluan (index 0-3) selalu
   dari satu wilayah yang sama (misal yang paling awal abjad),
   bukan acak dari berbagai daerah.

   Supaya baris pertama benar-benar acak lintas wilayah dan
   berubah setiap refresh, kita ambil 4 hotel dengan query
   ORDER BY RAND() murni (tanpa ikut urutan lokasi).

   Hotel dengan diskon >= 50% dikecualikan lewat HAVING supaya
   tidak pernah muncul di baris pertama ini (hanya boleh tampil
   di baris kedua / seksi diskon biru).
   ============================================================ */
$hotel_grid_atas = [];
if (!$ada_filter_aktif) {
    $query_random4 = "SELECT h.*,
            MAX(CASE WHEN k.tipe_kamar = 'Standard' THEN k.harga_per_malam END) AS harga_standard,
            MAX(CASE WHEN k.tipe_kamar = 'Standard' THEN k.diskon_persen END) AS diskon_standard,
            MAX(CASE WHEN k.tipe_kamar = 'Deluxe'   THEN k.harga_per_malam END) AS harga_deluxe,
            MAX(CASE WHEN k.tipe_kamar = 'Deluxe'   THEN k.diskon_persen END) AS diskon_deluxe,
            MIN(k.harga_per_malam) AS harga_per_malam,
            MAX(k.diskon_persen)   AS diskon_persen
          FROM hotel h
          LEFT JOIN kamar k ON h.id_hotel = k.id_hotel
          GROUP BY h.id_hotel
          HAVING diskon_persen IS NULL OR diskon_persen < 50
          ORDER BY RAND()
          LIMIT 4";
    $hasil_random4 = $koneksi->query($query_random4);
    if ($hasil_random4 && $hasil_random4->num_rows > 0) {
        while ($row = $hasil_random4->fetch_assoc()) {
            if ($mode_terdekat
                && isset($row['latitude']) && isset($row['longitude'])
                && $row['latitude'] !== null && $row['longitude'] !== null
                && $row['latitude'] !== '' && $row['longitude'] !== '') {
                $row['_jarak_km'] = hitungJarak($user_lat, $user_lng, floatval($row['latitude']), floatval($row['longitude']));
            } else {
                $row['_jarak_km'] = null;
            }
            $hotel_grid_atas[] = $row;
        }
    }
}
$jumlah_grid_atas = count($hotel_grid_atas);

/* ============================================================
   Logika 3 hotel dengan pesanan terbanyak hari ini
   ============================================================ */
$query_favorit = "SELECT h.id_hotel, COUNT(DISTINCT b.id_pemesanan) as jumlah_pesanan
                  FROM hotel h
                  LEFT JOIN kamar k ON h.id_hotel = k.id_hotel
                  LEFT JOIN pemesanan b ON k.id_kamar = b.id_kamar
                  WHERE DATE(b.dibuat_pada) = CURDATE()
                  GROUP BY h.id_hotel
                  HAVING jumlah_pesanan > 0
                  ORDER BY jumlah_pesanan DESC
                  LIMIT 3";
$hasil_favorit    = $koneksi->query($query_favorit);
$hotel_terlaris_ids = [];
if ($hasil_favorit && $hasil_favorit->num_rows > 0) {
    while ($fav_row = $hasil_favorit->fetch_assoc()) {
        $hotel_terlaris_ids[] = intval($fav_row['id_hotel']);
    }
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

function hitungJarak(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $R = 6371; // radius bumi km
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2)
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
       * sin($dLng/2) * sin($dLng/2);
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function formatJarak(float $km): string {
    if ($km < 1) {
        return round($km * 1000) . ' m';
    }
    return number_format($km, 1) . ' km';
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
    <!-- paksa browser selalu reload -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="stylesheet" href="/reservasi_hotel/css/style_index.css">
    <link rel="stylesheet" href="/reservasi_hotel/css/love.css">
</head>

<body>

    <?php include 'komponen/navigasi.php'; ?>
    <?php include 'komponen/banner_slider.php'; ?>

    <main class="container">
        <h2 class="section-title">
            <?php
            if ($keyword !== '') {
                echo "Hasil Pencarian untuk: '" . $keyword . "'";
            } elseif ($lokasi_filter !== '') {
            } elseif ($mode_terdekat) {
                echo "Hotel Terdekat dari Lokasi Anda";
            } else {
                echo "Temukan Hotel dan dapatkan penawaran terbaik!";
            }
            ?>
        </h2>

        <section class="flex-hotel" style="display: block !important;">

            <!-- skeleton container -->
            <div id="skeleton-container" style="width:100% !important; display:block !important;">

                <?php if (!$ada_filter_aktif): ?>
                <!-- skeleton card baris pertama -->
                <div class="grid-4-kolom">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="card-hotel" style="box-shadow:none; overflow:hidden;">
                        <div class="sk-card-img shimmer"></div>
                        <div class="sk-badge-strip shimmer"></div>
                        <div class="sk-card-body">
                            <div class="sk-title shimmer"></div>
                            <div class="sk-stars shimmer"></div>
                            <div class="sk-location shimmer"></div>
                            <div class="sk-price-old shimmer"></div>
                            <div class="sk-price shimmer"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <!-- skeleton card baris kedua -->
                <div class="container-diskon-tengah">
                    <section class="section-diskon-besar">
                        <div class="shimmer-dark"
                            style="height:24px; width:220px; margin-bottom:30px; border-radius:4px;"></div>
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
                                <div class="sk-card-img shimmer-dark"></div>
                                <div class="sk-badge-strip shimmer-dark"></div>
                                <div class="sk-card-body">
                                    <div class="sk-title    shimmer-dark"></div>
                                    <div class="sk-stars    shimmer-dark"></div>
                                    <div class="sk-location shimmer-dark"></div>
                                    <div class="sk-price-old shimmer-dark"></div>
                                    <div class="sk-price     shimmer-dark"></div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </section>
                </div>
                <?php endif; ?>

                <!-- skeleton card baris ketiga -->
                <?php if ($keyword !== ''): ?>
                <div class="shimmer" style="height:22px; width:200px; margin-bottom:16px; border-radius:4px;"></div>
                <?php elseif ($lokasi_filter !== ''): ?>
                <div class="shimmer" style="height:22px; width:240px; margin-bottom:16px; border-radius:4px;"></div>
                <?php elseif ($mode_terdekat): ?>
                <div class="shimmer" style="height:22px; width:280px; margin-bottom:16px; border-radius:4px;"></div>
                <?php else: ?>
                <div class="shimmer" style="height:22px; width:260px; margin-bottom:16px; border-radius:4px;"></div>
                <?php endif; ?>

                <div style="display:flex; flex-direction:column; gap:20px; margin-bottom:30px;">
                    <?php
        // Jumlah skeleton card list 
        $sk_count = $ada_filter_aktif ? 5 : 3;
        for ($i = 0; $i < $sk_count; $i++):
        ?>
                    <div class="sk-list-card">
                        <div class="sk-list-img shimmer"></div>
                        <div class="sk-list-center">
                            <div class="sk-list-title    shimmer"></div>
                            <div class="sk-list-stars    shimmer"></div>
                            <div class="sk-list-location shimmer"></div>
                        </div>
                        <div class="sk-list-right">
                            <div class="sk-list-room-label shimmer"></div>
                            <div class="sk-list-price-old  shimmer"></div>
                            <div class="sk-list-price      shimmer"></div>
                            <div class="sk-list-divider"></div>
                            <div class="sk-list-room-label shimmer"></div>
                            <div class="sk-list-price-old  shimmer"></div>
                            <div class="sk-list-price      shimmer"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>


            <!-- konten homepage -->
            <div id="actual-content" style="width:100% !important; display:block !important;">
                <?php if ($jumlah_hotel_biasa > 0 || $jumlah_hotel_diskon > 0): ?>

                <?php if (!$ada_filter_aktif): ?>
                <!-- card baris pertama (RANDOM lintas wilayah, sumber: $hotel_grid_atas) -->
                <div class="grid-4-kolom">
                    <?php
                    for ($i = 0; $i < $jumlah_grid_atas; $i++):
                        $row      = $hotel_grid_atas[$i];
                        $id_hotel = $row['id_hotel'];
                        $badge    = getBadgeDiskon($row);
                        $is_terlaris = in_array(intval($id_hotel), $hotel_terlaris_ids);
                        $jarak_km = $row['_jarak_km'];
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

                            <?php if ($is_terlaris): ?>
                            <div class="favorite-badge">Terlaris</div>
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
                                                    style="position:absolute; left:0; right:0; top:50%; height:2px; background:#ef4444; transform:rotate(-4deg);"></span></span>
                                            <span class="discount-badge"
                                                style="color:#da0000;; background:#fee2e2;">-<?= $diskon; ?>%</span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="price-row">
                                            <span>IDR
                                                <?= $harga_final ? number_format($harga_final, 0, ',', '.') : '-'; ?><span
                                                    class="price-suffix">/Malam</span></span>
                                        </div>
                                    </span>
                                    <!-- Badge jarak muncul di bawah harga, di dalam price-wrapper -->
                                    <?php if ($jarak_km !== null): ?>
                                    <span class="badge-jarak">
                                        <svg viewBox="0 0 24 24">
                                            <path
                                                d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                                        </svg>
                                        <?= formatJarak($jarak_km); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    </a>
                    <?php
                    endfor;
                    ?>
                </div>
                <?php endif; // tutup baris pertama (!$ada_filter_aktif) ?>

                <!-- card baris kedua: tetap tampil walau ada filter aktif (search/lokasi/terdekat),
                     supaya hotel diskon >=50% yang cocok dengan pencarian tetap kelihatan -->
                <?php if ($jumlah_hotel_diskon > 0): ?>
                <div class="container-diskon-tengah">
                    <section class="section-diskon-besar">
                        <h3 class="section-title-diskon">Hotel dengan diskon 50% ke atas!</h3>

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
                                    $is_terlaris_diskon = in_array(intval($id_hotel), $hotel_terlaris_ids);
                                    $jarak_diskon = $row_diskon['_jarak_km'];
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
                                        <?php if ($is_terlaris_diskon): ?>
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
                                            <h3 class="card-title">
                                                <?= htmlspecialchars($row_diskon['nama_hotel']); ?></h3>

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
                                                            style="color:#da0000;; background:#fee2e2;">-<?= $diskon; ?>%</span>
                                                    </div>
                                                    <div class="price-row">
                                                        <span>IDR
                                                            <?= $harga_final ? number_format($harga_final, 0, ',', '.') : '-'; ?><span
                                                                class="price-suffix">/Malam</span></span>
                                                    </div>
                                                </span>
                                                <!-- Badge jarak di bawah harga pada card diskon -->
                                                <?php if ($jarak_diskon !== null): ?>
                                                <span class="badge-jarak">
                                                    <svg viewBox="0 0 24 24">
                                                        <path
                                                            d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                                                    </svg>
                                                    <?= formatJarak($jarak_diskon); ?>
                                                </span>
                                                <?php endif; ?>
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

                <!-- card baris ketiga -->
                <?php
                $index_mulai = $ada_filter_aktif ? 0 : 0;
                if ($jumlah_hotel_biasa > $index_mulai):
                    $current_location = "";
                    echo '<div class="list-container-vertical">';

                    for ($i = $index_mulai; $i < $jumlah_hotel_biasa; $i++):
                        $row          = $hotel_biasa[$i];
                        $id_hotel     = $row['id_hotel'];
                        $badge        = getBadgeDiskon($row);
                        $lokasi_hotel = htmlspecialchars($row['lokasi']);
                        $is_terlaris  = in_array(intval($id_hotel), $hotel_terlaris_ids);
                        $jarak_km     = $row['_jarak_km'];
                        /* logika judul grup lokasi: tetap tampil walau lagi search keyword,
                           supaya hasil pencarian "serang" misalnya tetap dikelompokkan jadi
                           "Hotel Pilihan di Kota Serang" dan "Hotel Pilihan di Kabupaten Serang".
                           Hanya disembunyikan saat mode lokasi terdekat (GPS), karena urutannya
                           berdasarkan jarak, bukan per-lokasi. */
                        if (!$mode_terdekat && $lokasi_hotel !== $current_location) {
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

                    <!-- image card -->
                    <div class="list-img-section">
                        <?php if ($is_terlaris): ?>
                        <div class="favorite-badge">Terlaris</div>
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

                        <!-- Lokasi + badge jarak berdampingan -->
                        <div class="list-location-row">
                            <span class="list-location"><?= $lokasi_hotel; ?></span>
                            <?php if ($jarak_km !== null): ?>
                            <span class="badge-jarak">
                                <svg viewBox="0 0 24 24">
                                    <path
                                        d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                                </svg>
                                <?= formatJarak($jarak_km); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- KANAN: HARGA STANDARD & DELUXE -->
                    <div class="list-right-section">
                        <!-- Kamar Standard -->
                        <div class="room-price-row">
                            <div class="room-header-top">
                                <span class="room-type-title title-standard">Kamar Standard</span>
                                <?php if ($h_standard > 0 && $d_standard > 0): ?>
                                <span class="discount-badge"><?= $d_standard; ?>%</span>
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
                <div class="empty-state">Hotel tidak ditemukan, silahkan ubah lokasi atau kata kunci.</div>
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

        const MINIMUM_DELAY = 1500;
        const FALLBACK_MAX = 5000;

        const startTime = Date.now();

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
                }, 300);
            }, remaining);
        }

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
                img.addEventListener('error', onImageDone);
            }
        });

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
    </script>

</body>

</html>