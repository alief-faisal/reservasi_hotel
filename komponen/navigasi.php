<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/* logika searchbar */
$pencarian = isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '';

// logika deteksi nama file halaman yang sedang diakses saat ini
$halaman_sekarang = basename($_SERVER['SCRIPT_NAME']);

// logika ambil daftar nama hotel untuk animasi placeholder
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");
$queryHotel = "SELECT nama_hotel FROM hotel LIMIT 8";
$hasilHotel = $koneksi->query($queryHotel);
$daftarHotel = [];
while ($row = $hasilHotel->fetch_assoc()) {
    $daftarHotel[] = $row['nama_hotel'];
}
$hotelJSON = json_encode($daftarHotel);
?>

<link rel="stylesheet" type="text/css" href="css/style_navigasi.css">

<header
    style="width: 100%; background-color: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 16px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);">
    <div
        style="max-width: 1100px; margin: 0 auto; padding: 0 20px; display: flex; align-items: center; justify-content: space-between;">

        <div class="nav-wrapper" style="display: flex; align-items: center; gap: 32px; flex-grow: 1;">
            <a href="/reservasi_hotel/index.php" class="brand-logo" style="display: flex; align-items: center;">
                <img src="assets/logo/logo.png" alt="Logo Kelompok 1"
                    style="height: 40px; width: auto; object-fit: contain;">
            </a>

            <?php if ($halaman_sekarang !== 'kelola_hotel.php'): ?>
            <form id="searchForm" action="/reservasi_hotel/index.php" method="GET" class="search-form-responsive"
                style="position: relative; width: 520px; margin-left: 16px;">
                <div class="search-input-wrapper">
                    <svg class="search-icon" viewBox="0 0 24 24">
                        <path
                            d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                    </svg>
                    <input type="text" name="cari" id="searchInput" value="<?= $pencarian; ?>"
                        oninput="if(this.value === '') document.getElementById('searchForm').submit();"
                        data-hotel-list='<?= $hotelJSON; ?>'>
                </div>
            </form>
            <?php endif; ?>
        </div>

        <div class="user-menu-container">

            <!-- Hamburger menu -->
            <button type="button" class="hamburger-btn" id="hamburgerToggle" aria-label="Menu">
                <span></span>
                <span></span>
            </button>

            <div class="menu-links-wrapper" id="menuLinks">
                <?php if (isset($_SESSION['peran'])): ?>
                <?php if ($_SESSION['peran'] !== 'admin'): ?>
                <span style="color: #334155; font-weight: 200;"><?= htmlspecialchars($_SESSION['nama']); ?></span>
                <span class="divider" style="color: #cbd5e1;">|</span>
                <a href="/reservasi_hotel/layanan_pembayaran/riwayat_pembayaran.php"
                    style="color: #0f172a; text-decoration: none; font-weight: 600;">Riwayat Pembayaran</a>
                <?php endif; ?>

                <?php if ($_SESSION['peran'] === 'admin'): ?>
                <a href="/reservasi_hotel/layanan_hotel/kelola_hotel.php"
                    style="color: #0f172a; text-decoration: none; font-weight: 600;">Admin Panel</a>
                <?php endif; ?>

                <span class="divider" style="color: #cbd5e1;">|</span>
                <a href="/reservasi_hotel/layanan_autentikasi/keluar.php" class="btn-keluar"
                    style="background: #E60000; border: none; color: #ffffff; text-decoration: none; font-weight: 600; cursor: pointer; font-size: 0.9rem; padding: 6px 16px; border-radius: 6px;">Keluar</a>
                <?php else: ?>
                <button type="button" class="btn-masuk" onclick="openLoginModal('login')"
                    style="background: none; border: none; color: #2563eb; text-decoration: none; font-weight: 600; cursor: pointer; font-size: 0.9rem;">
                    Masuk
                </button>
                <button type="button" class="btn-daftar" onclick="openLoginModal('daftar')"
                    style="background: #2563eb; border: none; color: #ffffff; text-decoration: none; font-weight: 600; cursor: pointer; font-size: 0.9rem; padding: 6px 16px; border-radius: 6px;">
                    Daftar
                </button>
                <?php endif; ?>
            </div>

        </div>

    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const hamburgerToggle = document.getElementById('hamburgerToggle');
    const menuLinks = document.getElementById('menuLinks');

    if (hamburgerToggle && menuLinks) {
        hamburgerToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            menuLinks.classList.toggle('show');
            hamburgerToggle.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!menuLinks.contains(e.target) && !hamburgerToggle.contains(e.target)) {
                menuLinks.classList.remove('show');
                hamburgerToggle.classList.remove('active');
            }
        });
    }

    /* logika animasi placeholder */
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    const hotelListJSON = searchInput.getAttribute('data-hotel-list');
    const hotelList = JSON.parse(hotelListJSON || '[]');

    if (hotelList.length === 0) return;

    let currentIndex = 0;
    let isTyping = false;
    let placeholderInterval;

    const updatePlaceholder = () => {
        if (!isTyping && searchInput.value === '') {
            const hotel = hotelList[currentIndex % hotelList.length];

            searchInput.classList.remove('animate-placeholder');
            void searchInput.offsetWidth;
            searchInput.classList.add('animate-placeholder');

            setTimeout(() => {
                searchInput.placeholder = `${hotel}...`;
            }, 300);

            currentIndex++;
        }
    };

    updatePlaceholder();
    placeholderInterval = setInterval(updatePlaceholder, 3000);

    searchInput.addEventListener('input', () => {
        isTyping = searchInput.value !== '';
        if (isTyping) {
            searchInput.placeholder = '';
            searchInput.classList.remove('animate-placeholder');
        } else {
            searchInput.placeholder = 'Cari Hotel atau Lokasi ...';
        }
    });

    searchInput.addEventListener('focus', () => {
        if (searchInput.value === '') {
            searchInput.placeholder = 'Cari Hotel atau Lokasi ...';
            searchInput.classList.remove('animate-placeholder');
        }
    });

    searchInput.addEventListener('blur', () => {
        if (searchInput.value === '') {
            clearInterval(placeholderInterval);
            updatePlaceholder();
            placeholderInterval = setInterval(updatePlaceholder, 3000);
        }
    });
});
</script>