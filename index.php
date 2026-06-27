<?php
session_start();
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

/* search bar/pencarian */
$keyword = isset($_GET['cari']) ? $koneksi->real_escape_string($_GET['cari']) : '';

/* Ambil Semua Data Berdasarkan Keyword */
$query = "SELECT h.*, k.harga_per_malam, k.diskon_persen FROM hotel h LEFT JOIN kamar k ON h.id_hotel = k.id_hotel";
if ($keyword !== '') {
    $query .= " WHERE h.nama_hotel LIKE '%$keyword%' OR h.lokasi LIKE '%$keyword%'";
}
$query .= " GROUP BY h.id_hotel ORDER BY RAND()";
$hasil_raw = $koneksi->query($query);

$hotel_biasa = [];
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
$hasil_favorit   = $koneksi->query($query_favorit);
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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kel1 | reservasi_hotel</title>
    <link rel="icon" type="image/png" href="assets/logo/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap"
        rel="stylesheet">
    <?php include 'komponen/style.php'; ?>

    <!-- CSS Love (Wishlist) -->
    <link rel="stylesheet" href="/reservasi_hotel/css/love.css">

    <!-- CSS FIX: Desktop 4 Kolom Sejajar, Mobile Simetris & Lancar Di-drag Mouse -->
    <style>
    /* ================= KONDISI DESKTOP ================= */
    .grid-diskon-slider {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        width: 100%;
    }

    /* ================= KONDISI MOBILE (DIBAWAH 768px) ================= */
    @media (max-width: 768px) {
        .slider-diskon-wrapper {
            overflow: hidden;
            width: 100%;
            padding: 5px 0;
        }

        .grid-diskon-slider {
            display: flex !important;
            gap: 15px;
            overflow-x: auto;
            user-select: none;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding-bottom: 10px;
        }

        .grid-diskon-slider::-webkit-scrollbar {
            display: none;
        }

        .grid-diskon-slider .card-link {
            flex: 0 0 65% !important;
            min-width: 65% !important;
            box-sizing: border-box;
        }

        @media (pointer: coarse) {
            .grid-diskon-slider {
                scroll-snap-type: x mandatory;
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
            }

            .grid-diskon-slider .card-link {
                scroll-snap-align: start;
            }
        }
    }
    </style>
</head>

<body>

    <?php include 'komponen/navigasi.php'; ?>
    <?php include 'komponen/banner_slider.php'; ?>

    <main class="container">
        <h2 class="section-title">
            <?= $keyword !== '' ? "Hasil Pencarian untuk: '" . $keyword . "'" : "Temukan Hotel dan dapatkan penawaran terbaik!" ?>
        </h2>

        <section class="flex-hotel" style="display: block !important;">

            <!-- ==================================================================
                 SKELETON CONTAINER
                 ================================================================== -->
            <div id="skeleton-container" style="width: 100% !important; display: block !important;">
                <div class="grid-4-kolom">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="card-hotel" style="box-shadow: none;">
                        <div class="img-wrapper shimmer"></div>
                        <div class="card-body">
                            <div class="shimmer skeleton-text" style="width: 70%; height: 20px; margin-bottom: 15px;">
                            </div>
                            <div class="shimmer skeleton-text" style="width: 40%; height: 14px; margin-bottom: 12px;">
                            </div>
                            <div class="shimmer skeleton-text" style="width: 50%; height: 14px; margin-bottom: auto;">
                            </div>
                            <div class="shimmer skeleton-text" style="width: 100%; height: 35px; border-radius: 4px;">
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>

                <?php if ($keyword === ''): ?>
                <div class="container-diskon-tengah">
                    <div class="section-diskon-besar">
                        <h3 class="section-title-diskon">Diskon nginep s.d. 50%</h3>
                        <div class="slider-diskon-wrapper">
                            <div class="grid-diskon-slider">
                                <?php for ($i = 0; $i < 4; $i++): ?>
                                <div class="card-hotel" style="box-shadow: none;">
                                    <div class="img-wrapper shimmer"></div>
                                    <div class="card-body">
                                        <div class="shimmer skeleton-text"
                                            style="width: 70%; height: 20px; margin-bottom: 15px;"></div>
                                        <div class="shimmer skeleton-text"
                                            style="width: 40%; height: 14px; margin-bottom: 12px;"></div>
                                        <div class="shimmer skeleton-text"
                                            style="width: 50%; height: 14px; margin-bottom: auto;"></div>
                                        <div class="shimmer skeleton-text"
                                            style="width: 100%; height: 35px; border-radius: 4px;"></div>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- ==================================================================
                 KONTEN ASLI
                 ================================================================== -->
            <div id="actual-content" style="width: 100% !important; display: block !important;">
                <?php if ($jumlah_hotel_biasa > 0 || $jumlah_hotel_diskon > 0): ?>

                <!-- baris pertama 4 card -->
                <div class="grid-4-kolom">
                    <?php
                    $counter = 0;
                    for ($i = 0; $i < $jumlah_hotel_biasa; $i++):
                        $row = $hotel_biasa[$i];
                        $counter++;
                        $id_hotel = $row['id_hotel']; // dipakai oleh btn_love.php
                    ?>
                    <a href="/reservasi_hotel/layanan_pemesanan/pesan.php?id_hotel=<?= $row['id_hotel']; ?>"
                        class="card-link">
                        <article class="card-hotel">

                            <?php
                            $nama_foto = $row['foto'];
                            if (empty($nama_foto) || $nama_foto == 'default.jpg' || !file_exists("assets/" . $nama_foto)) {
                                $path_foto = "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=400&q=80";
                            } else {
                                $path_foto = "/reservasi_hotel/assets/" . $nama_foto;
                            }
                            ?>

                            <?php if ($hotel_favorit_id == $row['id_hotel']): ?>
                            <div class="favorite-badge">Favorit</div>
                            <?php endif; ?>

                            <!-- TOMBOL LOVE -->
                            <?php include 'komponen/btn_love.php'; ?>

                            <div class="img-wrapper">
                                <img src="<?= $path_foto; ?>" alt="" class="card-img">
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
                    <?php
                        if ($counter == 4) break;
                    endfor;
                    ?>
                </div>

                <!-- SECTION DISKON -->
                <?php if ($jumlah_hotel_diskon > 0): ?>
                <div class="container-diskon-tengah">
                    <section class="section-diskon-besar">
                        <h3 class="section-title-diskon">Diskon nginep s.d. 50%</h3>
                        <div class="slider-diskon-wrapper">
                            <div class="grid-diskon-slider">
                                <?php foreach ($hotel_diskon as $row_diskon):
                                    $id_hotel = $row_diskon['id_hotel']; // dipakai btn_love.php
                                ?>
                                <a href="/reservasi_hotel/layanan_pemesanan/pesan.php?id_hotel=<?= $row_diskon['id_hotel']; ?>"
                                    class="card-link">
                                    <article class="card-hotel">
                                        <?php
                                        $nama_foto = $row_diskon['foto'];
                                        if (empty($nama_foto) || $nama_foto == 'default.jpg' || !file_exists("assets/" . $nama_foto)) {
                                            $path_foto = "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=400&q=80";
                                        } else {
                                            $path_foto = "/reservasi_hotel/assets/" . $nama_foto;
                                        }
                                        ?>
                                        <?php if ($hotel_favorit_id == $row_diskon['id_hotel']): ?>
                                        <div class="favorite-badge">Favorit</div>
                                        <?php endif; ?>

                                        <!-- TOMBOL LOVE -->
                                        <?php include 'komponen/btn_love.php'; ?>

                                        <div class="img-wrapper">
                                            <img src="<?= $path_foto; ?>" alt="" class="card-img">
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
                                                        <span class="price-original">IDR
                                                            <?= number_format($harga_original, 0, ',', '.'); ?></span>
                                                        <span class="discount-badge">-<?= $diskon; ?>%</span>
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

                <!-- baris ketiga card lanjutan -->
                <?php if ($jumlah_hotel_biasa > 4): ?>
                <div class="grid-4-kolom">
                    <?php for ($i = 4; $i < $jumlah_hotel_biasa; $i++):
                        $row      = $hotel_biasa[$i];
                        $id_hotel = $row['id_hotel']; // dipakai btn_love.php
                    ?>
                    <a href="/reservasi_hotel/layanan_pemesanan/pesan.php?id_hotel=<?= $row['id_hotel']; ?>"
                        class="card-link">
                        <article class="card-hotel">
                            <?php
                            $nama_foto = $row['foto'];
                            if (empty($nama_foto) || $nama_foto == 'default.jpg' || !file_exists("assets/" . $nama_foto)) {
                                $path_foto = "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=400&q=80";
                            } else {
                                $path_foto = "/reservasi_hotel/assets/" . $nama_foto;
                            }
                            ?>
                            <?php if ($hotel_favorit_id == $row['id_hotel']): ?>
                            <div class="favorite-badge">Favorit</div>
                            <?php endif; ?>

                            <!-- TOMBOL LOVE -->
                            <?php include 'komponen/btn_love.php'; ?>

                            <div class="img-wrapper">
                                <img src="<?= $path_foto; ?>" alt="" class="card-img">
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
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="empty-state">Tidak ditemukan Hotel yang cocok dengan kata kunci tersebut.</div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include 'komponen/modal_login.php'; ?>
    <?php include 'komponen/script.php'; ?>

    <!-- JAVASCRIPT DRAG MOBILE -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const wrappers = document.querySelectorAll('.slider-diskon-wrapper');

        wrappers.forEach(wrapper => {
            const slider = wrapper.querySelector('.grid-diskon-slider');
            let isDown = false;
            let startX;
            let scrollLeft;
            let isDragging = false;

            wrapper.addEventListener('mousedown', (e) => {
                if (window.innerWidth > 768) return;
                isDown = true;
                wrapper.style.cursor = 'grabbing';
                startX = e.pageX - slider.offsetLeft;
                scrollLeft = slider.scrollLeft;
                isDragging = false;
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
                const x = e.pageX - slider.offsetLeft;
                const walk = (x - startX) * 1.5;
                slider.scrollLeft = scrollLeft - walk;
            });

            const links = wrapper.querySelectorAll('.card-link');
            links.forEach(link => {
                link.addEventListener('click', (e) => {
                    if (isDragging) e.preventDefault();
                });
            });
        });

        if (window.innerWidth <= 768) {
            document.querySelectorAll('.slider-diskon-wrapper').forEach(w => w.style.cursor = 'grab');
        }
    });
    </script>

    <!-- ============================================================
         JAVASCRIPT LOVE / WISHLIST
         ============================================================ -->
    <script>
    // Status login dari PHP (aman karena tidak menyimpan data sensitif)
    const userLoggedIn = <?= isset($_SESSION['id_pengguna']) ? 'true' : 'false'; ?>;

    /**
     * Toggle love: klik tombol hati di card hotel
     */
    function toggleLove(event, btn) {
        event.preventDefault(); // Jangan ikut buka link card
        event.stopPropagation(); // Jangan bubble ke <a> parent

        // Belum login → arahkan ke halaman masuk
        if (!userLoggedIn) {
            window.location.href = '/reservasi_hotel/layanan_autentikasi/masuk.php';
            return;
        }

        const idHotel = btn.getAttribute('data-hotel-id');
        const sudahLove = btn.getAttribute('data-loved') === '1';

        // Disable sementara agar tidak double klik
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
                        // Baru di-love
                        btn.classList.add('loved');
                        btn.setAttribute('data-loved', '1');
                        btn.setAttribute('title', 'Hapus dari wishlist');
                        btn.setAttribute('aria-label', 'Hapus dari wishlist');

                        // Animasi pop
                        btn.classList.remove('love-animate');
                        void btn.offsetWidth; // reflow
                        btn.classList.add('love-animate');
                    } else {
                        // Di-unlove
                        btn.classList.remove('loved', 'love-animate');
                        btn.setAttribute('data-loved', '0');
                        btn.setAttribute('title', 'Simpan ke wishlist');
                        btn.setAttribute('aria-label', 'Simpan ke wishlist');
                    }

                    // Update badge angka di navbar
                    updateNavLoveBadge(data.total);
                }
            })
            .catch(err => {
                console.error('Toggle love error:', err);
            })
            .finally(() => {
                btn.disabled = false;
            });
    }

    /**
     * Update badge angka wishlist di navbar
     */
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