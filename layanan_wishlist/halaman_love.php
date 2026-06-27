<?php
// ============================================================
//  layanan_wishlist/halaman_love.php
//  Halaman daftar hotel yang disimpan (wishlist)
// ============================================================
session_start();
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

// Redirect ke login jika belum masuk
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: /reservasi_hotel/layanan_autentikasi/masuk.php");
    exit;
}

$id_pengguna = intval($_SESSION['id_pengguna']);

// Ambil semua hotel yang ada di wishlist pengguna ini
// beserta data harga & diskon dari tabel kamar
$query = "
    SELECT h.*, k.harga_per_malam, k.diskon_persen, w.id_wishlist
    FROM wishlist w
    JOIN hotel h ON w.id_hotel = h.id_hotel
    LEFT JOIN kamar k ON h.id_hotel = k.id_hotel
    WHERE w.id_pengguna = ?
    GROUP BY h.id_hotel
    ORDER BY w.dibuat_pada DESC
";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_pengguna);
$stmt->execute();
$hasil = $stmt->get_result();

$daftar_love = [];
while ($row = $hasil->fetch_assoc()) {
    $daftar_love[] = $row;
}
$jumlah = count($daftar_love);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist Hotel Saya</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap"
        rel="stylesheet">

    <!-- CSS utama dari project -->
    <?php include '../komponen/style.php'; ?>

    <!-- CSS khusus love -->
    <link rel="stylesheet" href="/reservasi_hotel/css/love.css">
</head>

<body>

    <?php include '../komponen/navigasi.php'; ?>

    <main class="love-page-container">

        <h1 class="love-page-title">
            <!-- Ikon hati merah -->
            Wishlist Kamu, Ayo Pesan sekarang!
        </h1>
        <p class="love-page-subtitle">
            <?= $jumlah > 0
                ? $jumlah . ' hotel yang kamu simpan'
                : 'Belum ada hotel yang kamu simpan' ?>
        </p>

        <div class="love-grid">

            <?php if ($jumlah === 0): ?>
            <!-- EMPTY STATE -->
            <div class="love-empty">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                </svg>
                <h3>Belum Ada Hotel Tersimpan</h3>
                <a href="/reservasi_hotel/index.php">Jelajahi Hotel</a>
            </div>

            <?php else: ?>
            <?php foreach ($daftar_love as $row): ?>

            <?php
                // Logika foto
                $nama_foto = $row['foto'];
                if (empty($nama_foto) || $nama_foto == 'default.jpg' || !file_exists("../assets/" . $nama_foto)) {
                    $path_foto = "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=400&q=80";
                } else {
                    $path_foto = "/reservasi_hotel/assets/" . $nama_foto;
                }

                // Logika harga
                $harga_original = floatval($row['harga_per_malam'] ?? 0);
                $diskon         = intval($row['diskon_persen'] ?? 0);
                $harga_final    = $diskon > 0 ? $harga_original * (100 - $diskon) / 100 : $harga_original;
                $rating         = intval($row['rating'] ?? 0);
            ?>

            <!-- CARD HOTEL WISHLIST -->
            <div class="card-link-wrapper" style="position: relative;">
                <a href="/reservasi_hotel/layanan_pemesanan/pesan.php?id_hotel=<?= $row['id_hotel']; ?>"
                    class="card-link">
                    <article class="card-hotel">
                        <div class="img-wrapper">
                            <img src="<?= $path_foto; ?>" alt="<?= htmlspecialchars($row['nama_hotel']); ?>"
                                class="card-img" loading="lazy">
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($row['nama_hotel']); ?></h3>

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

                            <!-- Tombol hapus dari wishlist -->
                            <button class="btn-unlove" data-hotel-id="<?= $row['id_hotel']; ?>"
                                onclick="hapusDariWishlist(event, this, <?= $row['id_hotel']; ?>)">

                                Hapus dari Wishlist
                            </button>
                        </div>
                    </article>
                </a>
            </div>

            <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </main>

    <?php include '../komponen/modal_login.php'; ?>
    <?php include '../komponen/script.php'; ?>

    <!-- CSS Love -->
    <!-- (sudah diload di <head>) -->

    <script>
    // Hapus hotel dari wishlist langsung dari halaman ini
    function hapusDariWishlist(event, btn, idHotel) {
        event.preventDefault();
        event.stopPropagation();

        btn.disabled = true;
        btn.style.opacity = '0.6';

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
                    // Animasi hilang lalu hapus dari DOM
                    const cardWrapper = btn.closest('.card-link-wrapper');
                    cardWrapper.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    cardWrapper.style.opacity = '0';
                    cardWrapper.style.transform = 'scale(0.93)';
                    setTimeout(() => {
                        cardWrapper.remove();

                        // Update jumlah badge di navbar
                        updateNavBadge(data.total);

                        // Update subtitle
                        const sisa = document.querySelectorAll('.card-link-wrapper').length;
                        const subtitle = document.querySelector('.love-page-subtitle');
                        if (subtitle) {
                            subtitle.textContent = sisa > 0 ?
                                sisa + ' hotel yang kamu simpan' :
                                'Belum ada hotel yang kamu simpan';
                        }

                        // Tampilkan empty state jika sudah kosong
                        if (sisa === 0) {
                            document.querySelector('.love-grid').innerHTML = `
                            <div class="love-empty">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                                <h3>Belum Ada Hotel Tersimpan</h3>
                                <p>Klik ikon ❤ di kartu hotel untuk menyimpannya ke sini.</p>
                                <a href="/reservasi_hotel/index.php">Jelajahi Hotel</a>
                            </div>`;
                        }
                    }, 320);
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
    }

    // Update badge angka di navbar
    function updateNavBadge(total) {
        const badge = document.querySelector('.nav-love-badge');
        if (!badge) return;
        badge.setAttribute('data-count', total);
        badge.textContent = total > 0 ? total : '';
        badge.style.display = total > 0 ? 'flex' : 'none';
    }
    </script>
</body>

</html>