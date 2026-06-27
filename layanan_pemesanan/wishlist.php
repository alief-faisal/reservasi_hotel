<?php
// 1. Pastikan session dimulai paling pertama SEBELUM manipulasi direktori apapun
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Proteksi halaman: Jika belum login, tendang ke halaman masuk
if (!isset($_SESSION['id_user'])) {
    header("Location: /reservasi_hotel/layanan_autentikasi/masuk.php");
    exit;
}

// 3. Koneksi ke Database
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

$id_user = $_SESSION['id_user'];

// 4. Ambil daftar hotel yang di-wishlist oleh user ini
$query = "SELECT h.* FROM wishlist w 
          JOIN hotel h ON w.id_hotel = h.id_hotel 
          WHERE w.id_user = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist Saya - Reservasi Hotel</title>
    <!-- Path CSS ditarik relatif aman dari folder layanan_pemesanan -->
    <link rel="stylesheet" href="../css/style_navigasi.css">
    <link rel="stylesheet" href="../css/love-button.css">
    <style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background-color: #f8fafc;
        margin: 0;
        padding: 0;
    }

    .container {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .page-title {
        color: #0f172a;
        font-size: 1.75rem;
        margin-bottom: 24px;
    }

    .grid-wishlist {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }

    .card-hotel {
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .img-wrapper {
        position: relative;
        width: 100%;
        padding-top: 65%;
    }

    .img-wrapper img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .card-content {
        padding: 16px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .hotel-name {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 8px 0;
    }

    .hotel-meta {
        font-size: 0.9rem;
        color: #64748b;
        margin-bottom: 16px;
    }

    .btn-detail {
        margin-top: auto;
        background: #2563eb;
        color: white;
        text-align: center;
        padding: 10px;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: background 0.2s;
    }

    .btn-detail:hover {
        background: #1d4ed8;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .empty-state p {
        color: #64748b;
        font-size: 1.1rem;
        margin-bottom: 20px;
    }

    .btn-jelajah {
        background: #2563eb;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 600;
    }
    </style>
</head>

<body>

    <!-- 5. Memanggil Navigasi menggunakan Absolute Path berbasis root server agar session tidak pecah -->
    <?php 
    include $_SERVER['DOCUMENT_ROOT'] . '/reservasi_hotel/komponen/navigasi.php'; 
    ?>

    <div class="container">
        <h1 class="page-title">Hotel Favorit Saya</h1>

        <?php if ($result->num_rows > 0): ?>
        <div class="grid-wishlist">
            <?php while($hotel = $result->fetch_assoc()): ?>
            <div class="card-hotel">
                <!-- Tombol Love untuk hapus langsung -->
                <button class="btn-love active" onclick="removeWishlist(event, <?= $hotel['id_hotel']; ?>, this)">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                    </svg>
                </button>

                <div class="img-wrapper">
                    <img src="../assets/gambar/<?= htmlspecialchars($hotel['gambar_hotel'] ?? 'default.jpg'); ?>"
                        alt="<?= htmlspecialchars($hotel['nama_hotel']); ?>">
                </div>

                <div class="card-content">
                    <h3 class="hotel-name"><?= htmlspecialchars($hotel['nama_hotel']); ?></h3>
                    <div class="hotel-meta">
                        📍 <?= htmlspecialchars($hotel['alamat_hotel'] ?? 'Lokasi tidak tertera'); ?>
                    </div>
                    <a href="../detail_hotel.php?id=<?= $hotel['id_hotel']; ?>" class="btn-detail">Lihat Detail
                        Kamar</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <p>Belum ada hotel yang kamu tambahkan ke daftar wishlist.</p>
            <a href="../index.php" class="btn-jelajah">Cari Hotel Sekarang</a>
        </div>
        <?php endif; ?>
    </div>

    <script>
    // Flag status login untuk dibaca oleh fungsi JS di halaman ini jika diperlukan
    const isLoggedIn = <?= isset($_SESSION['id_user']) ? 'true' : 'false'; ?>;

    function removeWishlist(e, idHotel, element) {
        e.stopPropagation();

        if (confirm("Hapus hotel ini dari daftar favorit Anda?")) {
            fetch('../proses_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id_hotel=' + idHotel
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.action === 'removed') {
                        const card = element.closest('.card-hotel');
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8)';
                        card.style.transition = 'all 0.3s ease';
                        setTimeout(() => {
                            card.remove();
                            if (document.querySelectorAll('.card-hotel').length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    }
    </script>
</body>

</html>