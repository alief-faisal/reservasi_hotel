<?php
session_start();
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

/* search bar/pencrarian */
$keyword = isset($_GET['cari']) ? $koneksi->real_escape_string($_GET['cari']) : '';

    /* ambil data hotel */
    $query = "SELECT h.*, k.harga_per_malam, k.diskon_persen FROM hotel h LEFT JOIN kamar k ON h.id_hotel = k.id_hotel";
    if ($keyword !== '') {
        $query .= " WHERE h.nama_hotel LIKE '%$keyword%' OR h.lokasi LIKE '%$keyword%'";
    }
    $query .= " GROUP BY h.id_hotel ORDER BY RAND()";$hasil = $koneksi->query($query);
$jumlah_hotel = $hasil->num_rows; // Simpan jumlah hotel sebelum di-loop

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

    .container {
        max-width: 1100px;
        margin: 60px auto;
        padding: 0 20px;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 40px;
        color: #0f172a;
        letter-spacing: -0.5px;
    }

    /* logika grid utama */
    .flex-hotel {
        position: relative;
        width: 100%;
    }

    /* logika Struktur Grid Desktop (4 Card) */
    #skeleton-container,
    #actual-content {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        width: 100%;
        align-items: stretch;
    }

    /* logika hide konten saat loading */
    #actual-content {
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease-in-out;
    }

    /* Styling Modal Pop-up Login */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        max-width: 380px;
        width: 90%;
        padding: 40px 32px;
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        margin-bottom: 28px;
        text-align: center;
    }

    .modal-header h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.5px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 6px;
        color: #475569;
    }

    .form-group input {
        width: 100%;
        padding: 11px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.9rem;
        transition: border-color 0.15s, box-shadow 0.15s;
        font-family: inherit;
    }

    .form-group input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .form-group input::placeholder {
        color: #cbd5e1;
    }

    .password-wrapper {
        position: relative;
        width: 100%;
    }

    .password-wrapper input {
        width: 100%;
        padding-right: 40px;
    }

    .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        transition: color 0.2s;
    }

    .toggle-password:hover {
        color: #1e293b;
    }

    .toggle-password svg {
        width: 18px;
        height: 18px;
    }

    .btn-login-submit {
        width: 100%;
        padding: 11px 16px;
        background-color: #2563eb;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s ease;
        margin-bottom: 12px;
    }

    .btn-login-submit:hover {
        background-color: #1d4ed8;
    }

    .btn-login-submit:active {
        background-color: #1e40af;
    }

    .btn-nanti-link {
        width: 100%;
        padding: 11px 16px;
        background-color: transparent;
        color: #64748b;
        border: none;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: block;
        text-align: center;
    }

    .btn-nanti-link:hover {
        color: #475569;
        text-decoration: underline;
    }

    .tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 24px;
        border-bottom: 2px solid #e2e8f0;
    }

    .tab-button {
        padding: 12px 16px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 0.95rem;
        font-weight: 600;
        color: #94a3b8;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
        margin-bottom: -2px;
    }

    .tab-button.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
    }

    .tab-button:hover {
        color: #0f172a;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .alert {
        color: #e53e3e;
        background: #fef2f2;
        border: 1px solid #fecaca;
        padding: 12px;
        border-radius: 6px;
        font-size: 0.85rem;
        margin-bottom: 16px;
        text-align: center;
        font-weight: 500;
    }

    .alert-success {
        color: #22863a;
        background: #f0f5e9;
        border: 1px solid #d4e9cf;
        padding: 12px;
        border-radius: 6px;
        font-size: 0.85rem;
        margin-bottom: 16px;
        text-align: center;
        font-weight: 500;
    }

    .toggle-text {
        text-align: center;
        margin-top: 12px;
        font-size: 0.85rem;
        color: #64748b;
    }

    .toggle-text button {
        background: none;
        border: none;
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.85rem;
    }

    .toggle-text button:hover {
        text-decoration: underline;
    }

    @media (max-width: 480px) {
        .modal-content {
            width: 95%;
            padding: 32px 24px;
        }

        .modal-header h2 {
            font-size: 1.25rem;
        }
    }

    .card-link {
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
    }

    /* logika card hotel */
    .card-hotel {
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

    /* logika wrapper gambar */
    .img-wrapper {
        width: 100%;
        height: 180px;
        overflow: hidden;
        background-color: #f1f5f9;
        flex-shrink: 0;
    }

    /* logika gambar card */
    .card-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 1s ease-in-out;
    }

    /* logika body card */
    .card-body {
        padding: 20px;
        min-height: 220px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    /* logika ikon lokasi */
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

    .card-rating {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-bottom: 12px;
        margin-top: -4px;
    }

    .rating-stars {
        display: flex;
        gap: 2px;
        align-items: center;
    }

    .star-icon {
        width: 16px;
        height: 16px;
        fill: #f97316;
        filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.05));
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

    /* logika box harga */
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

    /* Badge Favorit */
    .favorite-badge {
        position: absolute;
        top: 0;
        left: 0;
        background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
        color: white;
        padding: 4px 10px;
        font-size: 0.9rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        border-radius: 0 0 8px 0;
        z-index: 10;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        white-space: nowrap;
    }

    /* logika wrapper card dengan positioning */
    .card-hotel {
        position: relative;
    }

    /* logika hover gambar */
    .card-hotel:hover .card-img {
        transform: scale(1.05);
    }

    /* logika skeleton shimmer loading */
    @keyframes efekShimmer {
        0% {
            background-position: -200% 0;
        }

        100% {
            background-position: 200% 0;
        }
    }

    .shimmer {
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: efekShimmer 1.5s infinite linear;
    }

    .skeleton-text {
        height: 14px;
        border-radius: 4px;
        margin-bottom: 12px;
    }

    .empty-state {
        text-align: center;
        padding: 60px;
        background: white;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        color: #64748b;
        font-size: 0.95rem;
        grid-column: span 4;
    }

    /* Section Diskon 50% ke atas */
    .section-diskon-besar {
        margin-bottom: 60px;
        margin-top: 80px;
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        padding: 40px;
        border-radius: 12px;
    }

    .section-title-diskon {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 30px;
        color: #0f172a;
        letter-spacing: -0.5px;
    }

    .grid-diskon {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        width: 100%;
    }

    /* logika Breakpoint grid mobile (2 Card) */
    @media (max-width: 768px) {

        #skeleton-container,
        #actual-content {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .grid-diskon {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .container {
            margin: 30px auto;
        }

        .section-title {
            font-size: 1.1rem;
        }

        .section-title-diskon {
            font-size: 1.1rem;
        }

        .card-body {
            padding: 14px;
        }

        .empty-state {
            grid-column: span 2;
        }

        .card-title {
            min-height: 2.1rem;
        }
    }
    </style>
</head>

<body>

    <?php include 'komponen/navigasi.php'; ?>

    <main class="container">
        <h2 class="section-title">
            <?= $keyword !== '' ? "Hasil Pencarian untuk: '" . $keyword . "'" : "Temukan Hotel dan dapatkan penawaran terbaik!" ?>
        </h2>

        <section class="flex-hotel">

            <div id="skeleton-container">
                <?php for($i=0; $i<$jumlah_hotel; $i++): ?>
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

                            <?php 
                                $rating = intval($row['rating'] ?? 0);
                            ?>
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
                                                class="price-suffix">/malam</span></span>
                                    </div>
                                </span>
                            </div>
                        </div>
                    </article>
                </a>
                <?php endwhile; ?>
                <?php else: ?>
                <div class="empty-state">
                    Tidak ditemukan Hotel yang cocok dengan kata kunci tersebut.
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Section Diskon Besar 50% ke atas -->
        <?php if ($jumlah_diskon > 0 && $keyword === ''): ?>
        <section class="section-diskon-besar">
            <h3 class="section-title-diskon">Diskon nginep s.d. 50%</h3>

            <div class="grid-diskon">
                <?php 
                    $hasil_diskon->data_seek(0); // Reset pointer ke awal
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

                            <?php 
                                $rating_diskon = intval($row_diskon['rating'] ?? 0);
                            ?>
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
                                                class="price-suffix">/malam</span></span>
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
    <?php if (!isset($_SESSION['peran'])): ?>
    <div id="loginModal" class="modal-overlay" style="display: flex;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Masuk</h2>
            </div>

            <div id="alertContainer"></div>

            <!-- TAB LOGIN -->
            <div class="tab-content active" id="tab-login">
                <form id="loginForm" method="POST">
                    <div class="form-group">
                        <label for="email-login">Email</label>
                        <input type="email" id="email-login" name="email" required placeholder="Masukkan email Anda">
                    </div>
                    <div class="form-group">
                        <label for="password-login">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password-login" name="password" required
                                placeholder="Masukkan password Anda">
                            <button type="button" class="toggle-password"
                                onclick="toggleModalPassword('password-login')">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-login-submit">Masuk</button>
                </form>
                <div class="toggle-text">
                    Belum punya akun? <button type="button" onclick="switchModalTab('daftar')">Daftar di sini</button>
                </div>
            </div>

            <!-- TAB DAFTAR -->
            <div class="tab-content" id="tab-daftar">
                <form id="registerForm" method="POST">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" required placeholder="Masukkan nama lengkap">
                    </div>
                    <div class="form-group">
                        <label for="email-register">Email</label>
                        <input type="email" id="email-register" name="email" required placeholder="Masukkan email">
                    </div>
                    <div class="form-group">
                        <label for="password-register">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password-register" name="password" required
                                placeholder="Minimal 6 karakter">
                            <button type="button" class="toggle-password"
                                onclick="toggleModalPassword('password-register')">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="konfirmasi-password">Konfirmasi Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="konfirmasi-password" name="konfirmasi_password" required
                                placeholder="Ulangi password">
                            <button type="button" class="toggle-password"
                                onclick="toggleModalPassword('konfirmasi-password')">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-login-submit">Daftar</button>
                </form>
                <div class="toggle-text">
                    Sudah punya akun? <button type="button" onclick="switchModalTab('login')">Login di sini</button>
                </div>
            </div>

            <button id="btnNanti" class="btn-nanti-link">Nanti</button>
        </div>
    </div>
    <?php endif; ?>

    <script>
    // Fungsi untuk membuka modal dan switch ke tab tertentu
    function openLoginModal(tab = 'login') {
        const loginModal = document.getElementById('loginModal');
        if (loginModal) {
            loginModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            switchModalTab(tab);
        }
    }

    // Fungsi untuk toggle password di modal
    function toggleModalPassword(inputId) {
        const input = document.getElementById(inputId);
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
    }

    // Fungsi untuk berpindah antar tab di modal
    function switchModalTab(tab) {
        // Sembunyikan semua tab
        document.getElementById('tab-login').classList.remove('active');
        document.getElementById('tab-daftar').classList.remove('active');

        // Sembunyikan alert
        document.getElementById('alertContainer').innerHTML = '';

        // Tampilkan tab yang dipilih
        if (tab === 'login') {
            document.getElementById('tab-login').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Masuk Akun';
        } else if (tab === 'daftar') {
            document.getElementById('tab-daftar').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Daftar Akun';
        }
    }

    window.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            // logika skeleton dihapus setelah konten ditampilkan
            const skeleton = document.getElementById('skeleton-container');
            if (skeleton) skeleton.remove();

            //  logika transisi konten tampil
            const content = document.getElementById('actual-content');
            if (content) {
                content.style.opacity = '1';
                content.style.pointerEvents = 'auto';
            }
        }, 600); /* skeleton 0.6  */

        // Logika modal login pop-up
        const loginModal = document.getElementById('loginModal');
        const btnNanti = document.getElementById('btnNanti');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        // Hitung jumlah refresh di halaman utama
        let refreshCount = parseInt(sessionStorage.getItem('refreshCount')) || 0;
        refreshCount++;
        sessionStorage.setItem('refreshCount', refreshCount);

        // Tampilkan popup saat refresh  2x
        if (refreshCount % 2 === 0 && loginModal) {
            // Disable scroll saat popup muncul
            document.body.style.overflow = 'hidden';
        } else if (loginModal) {
            // Sembunyikan popup saat refresh ganjil
            loginModal.style.display = 'none';
        }

        if (loginModal && btnNanti) {
            btnNanti.addEventListener('click', (e) => {
                e.preventDefault();
                loginModal.style.display = 'none';
                // Enable scroll kembali
                document.body.style.overflow = 'auto';
                // Reset counter saat modal ditutup
                sessionStorage.setItem('refreshCount', 0);
            });
        }

        // Handle LOGIN form submission di modal
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const email = document.getElementById('email-login').value;
                const password = document.getElementById('password-login').value;
                const alertContainer = document.getElementById('alertContainer');

                try {
                    const response = await fetch('/reservasi_hotel/layanan_autentikasi/masuk.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            email: email,
                            password: password,
                            mode: 'login'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Login berhasil, redirect
                        window.location.href = '/reservasi_hotel/index.php';
                    } else {
                        alertContainer.innerHTML =
                            '<div class="alert">' + data.message + '</div>';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alertContainer.innerHTML =
                        '<div class="alert">Terjadi kesalahan. Coba lagi.</div>';
                }
            });
        }

        // Handle REGISTER form submission di modal
        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const nama = document.getElementById('nama').value;
                const email = document.getElementById('email-register').value;
                const password = document.getElementById('password-register').value;
                const konfirmasi_password = document.getElementById('konfirmasi-password').value;
                const alertContainer = document.getElementById('alertContainer');

                // Validasi client-side
                if (!nama || !email || !password || !konfirmasi_password) {
                    alertContainer.innerHTML = '<div class="alert">Semua field harus diisi.</div>';
                    return;
                }

                if (password.length < 6) {
                    alertContainer.innerHTML =
                        '<div class="alert">Password minimal 6 karakter.</div>';
                    return;
                }

                if (password !== konfirmasi_password) {
                    alertContainer.innerHTML = '<div class="alert">Password tidak cocok.</div>';
                    return;
                }

                try {
                    const response = await fetch('/reservasi_hotel/layanan_autentikasi/masuk.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            nama: nama,
                            email: email,
                            password: password,
                            konfirmasi_password: konfirmasi_password,
                            mode: 'daftar'
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Daftar berhasil
                        alertContainer.innerHTML =
                            '<div class="alert-success">Akun berhasil dibuat! Silakan login dengan akun Anda.</div>';

                        // Reset form
                        registerForm.reset();

                        // Switch ke tab login setelah 1.5 detik
                        setTimeout(() => {
                            switchModalTab('login');
                            // Clear form login juga
                            document.getElementById('loginForm').reset();
                        }, 1500);
                    } else {
                        alertContainer.innerHTML =
                            '<div class="alert">' + data.message + '</div>';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alertContainer.innerHTML =
                        '<div class="alert">Terjadi kesalahan. Coba lagi.</div>';
                }
            });
        }
    });
    </script>
</body>

</html>