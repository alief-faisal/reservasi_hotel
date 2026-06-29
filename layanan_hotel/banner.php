<?php
session_start();
if (!isset($_SESSION['peran']) || $_SESSION['peran'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

/* hapus banner */
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus_banner' && isset($_GET['id_banner'])) {
    $id_b = intval($_GET['id_banner']);
    $stmt_b = $koneksi->prepare("SELECT nama_file FROM banner WHERE id_banner = ?");
    $stmt_b->bind_param("i", $id_b);
    $stmt_b->execute();
    $row_b = $stmt_b->get_result()->fetch_assoc();
    if ($row_b) {
        @unlink("../assets/banner/" . $row_b['nama_file']);
    }
    
    $del_b = $koneksi->prepare("DELETE FROM banner WHERE id_banner = ?");
    $del_b->bind_param("i", $id_b);
    $del_b->execute();
    echo "<script>alert('Banner berhasil dihapus.'); window.location='banner.php';</script>";
    exit();
}

/* upload banner */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah_banner'])) {
    $judul_b = trim($_POST['judul_banner'] ?? '');
    $urutan_b = intval($_POST['urutan_banner'] ?? 0);

    if (isset($_FILES['foto_banner']) && $_FILES['foto_banner']['error'] === 0) {
        $ext_b = strtolower(pathinfo($_FILES['foto_banner']['name'], PATHINFO_EXTENSION));
        if (in_array($ext_b, ['jpg', 'jpeg', 'png', 'webp'])) {
            $dir_banner = "../assets/banner/";
            if (!is_dir($dir_banner)) mkdir($dir_banner, 0775, true);
            $nama_b = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext_b;
            move_uploaded_file($_FILES['foto_banner']['tmp_name'], $dir_banner . $nama_b);

            $stmt_ins = $koneksi->prepare("INSERT INTO banner (judul, nama_file, urutan) VALUES (?, ?, ?)");
            $stmt_ins->bind_param("ssi", $judul_b, $nama_b, $urutan_b);
            $stmt_ins->execute();
            echo "<script>alert('Banner berhasil ditambahkan.'); window.location='banner.php';</script>";
            exit();
        }
    }
}

$query_banner_list = "SELECT * FROM banner ORDER BY urutan ASC, id_banner ASC";
$hasil_banner_list = $koneksi->query($query_banner_list);
$banner_admin_list = [];
while ($b = $hasil_banner_list->fetch_assoc()) {
    $banner_admin_list[] = $b;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Banner - Admin</title>

    <!-- Memanggil CSS Global, Navigasi, dan Banner Modul -->
    <link rel="stylesheet" href="/reservasi_hotel/css/style_index.css">
    <link rel="stylesheet" href="/reservasi_hotel/css/style_navigasi.css">
    <link rel="stylesheet" href="/reservasi_hotel/css/style_banner.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap"
        rel="stylesheet">
</head>

<body>
    <?php include '../komponen/navigasi.php'; ?>

    <main class="panel-container">
        <header class="panel-header">
            <h1 class="panel-title">Dashboard Manajemen Banner</h1>
        </header>

        <div class="dashboard-wrapper">
            <!-- Sidebar navigasi panel admin -->
            <aside class="admin-sidebar">
                <div class="sidebar-title">Menu Utama</div>
                <ul class="sidebar-menu">
                    <li><a href="kelola_hotel.php">🏨 Kelola Hotel</a></li>
                    <li><a href="banner.php" class="active">🖼️ Kelola Banner</a></li>
                    <li><a href="statistik.php">📊 Statistik Hari Ini</a></li>
                </ul>
            </aside>

            <!-- Area konten daftar banner dan formulir unggah -->
            <div class="admin-main-content">
                <section class="box-card">
                    <h2 class="banner-admin-title">🖼️ Daftar Banner Saat Ini</h2>

                    <?php if (!empty($banner_admin_list)): ?>
                    <div class="banner-admin-grid">
                        <?php foreach ($banner_admin_list as $b): ?>
                        <div class="banner-admin-card">
                            <img src="/reservasi_hotel/assets/banner/<?= htmlspecialchars($b['nama_file']); ?>"
                                alt="Banner Promo">
                            <div class="banner-admin-card-body">
                                <span><?= htmlspecialchars($b['judul'] ?: '-'); ?></span>
                                <a href="banner.php?aksi=hapus_banner&id_banner=<?= $b['id_banner']; ?>"
                                    onclick="return confirm('Hapus banner ini?');" class="banner-del-btn">Hapus</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="banner-empty-admin">Belum ada banner aktif.</p>
                    <?php endif; ?>

                    <!-- Form Unggah Banner Baru -->
                    <form action="" method="POST" enctype="multipart/form-data" class="banner-upload-box">
                        <strong>Upload Banner Baru</strong>
                        <div class="form-group">
                            <label>Judul Banner</label>
                            <input type="text" name="judul_banner" placeholder="Contoh: Promo Spesial Akhir Tahun">
                        </div>
                        <div class="form-group">
                            <label>Urutan Tampil</label>
                            <input type="number" name="urutan_banner" value="<?= count($banner_admin_list) + 1; ?>">
                        </div>
                        <div class="form-group">
                            <label>Pilih Berkas Gambar</label>
                            <input type="file" name="foto_banner" accept="image/*" required>
                        </div>
                        <button type="submit" name="tambah_banner" class="btn-upload-banner">Upload Banner</button>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const logoImg = document.querySelector('.brand-logo img');
        if (logoImg) {
            logoImg.src = '/reservasi_hotel/assets/logo/logo.png';
        }
    });
    </script>
</body>

</html>