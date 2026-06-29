<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
/* logika searchbar */
$pencarian = isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '';
$lokasi_filter = isset($_GET['lokasi']) ? htmlspecialchars($_GET['lokasi']) : '';

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

// Ambil daftar lokasi unik dari tabel hotel
$queryLokasi = "SELECT DISTINCT lokasi FROM hotel ORDER BY lokasi ASC";
$hasilLokasi = $koneksi->query($queryLokasi);
$daftarLokasi = [];
while ($rowL = $hasilLokasi->fetch_assoc()) {
    $daftarLokasi[] = $rowL['lokasi'];
}

/* ============================================================
   HITUNG JUMLAH WISHLIST USER UNTUK BADGE NAVBAR
   ============================================================ */
$jumlah_wishlist = 0;
if (isset($_SESSION['id_pengguna']) && ($_SESSION['peran'] ?? '') !== 'admin') {
    $id_nav = intval($_SESSION['id_pengguna']);
    $q_badge = $koneksi->query("SELECT COUNT(*) as total FROM wishlist WHERE id_pengguna = $id_nav");
    if ($q_badge) {
        $jumlah_wishlist = intval($q_badge->fetch_assoc()['total']);
    }
}

$is_admin   = isset($_SESSION['peran']) && $_SESSION['peran'] === 'admin';
$is_user    = isset($_SESSION['id_pengguna']) && !$is_admin;
$nama_user  = $is_user ? htmlspecialchars($_SESSION['nama']) : '';
?>

<link rel="stylesheet" type="text/css" href="/reservasi_hotel/css/style_navigasi.css">
<link rel="stylesheet" type="text/css" href="/reservasi_hotel/css/love.css">

<!-- Inject CSS overlay langsung agar pasti berada di bawah search bar tapi di atas logo/menu -->
<style>
#search-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    /* Menggelapkan layar */
    z-index: 10;
    /* Berada di bawah search-lokasi-wrapper */
    display: none;
}

#search-overlay.active {
    display: block;
}

/* Memastikan elemen pendukung overlay memiliki z-index yang tepat */
.brand-logo,
.user-menu-container {
    position: relative;
    z-index: 5;
    /* Di bawah overlay ketika overlay aktif */
}

.search-lokasi-wrapper {
    position: relative;
    z-index: 20 !important;
    /* Wajib di atas overlay agar tetap menyala */
}
</style>

<header
    style="width: 100%; background-color: #1B0091; padding: 16px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);">

    <!-- PINDAH KE SINI: Overlay sekarang berada di dalam header agar satu context stacking dengan logo dan menu -->
    <div id="search-overlay" onclick="tutupSearchOverlay()"></div>

    <div
        style="max-width: 1100px; margin: 0 auto; padding: 0 20px; display: flex; align-items: center; justify-content: space-between; position: relative;">

        <div class="nav-wrapper" style="display: flex; align-items: center; gap: 32px; flex-grow: 1;">
            <a href="/reservasi_hotel/index.php" class="brand-logo" style="display: flex; align-items: center;">
                <img src="/reservasi_hotel/assets/logo/logo.png" alt="Logo Kelompok 1"
                    style="height: 40px; width: auto; object-fit: contain;">
            </a>

            <?php if ($halaman_sekarang !== 'kelola_hotel.php'): ?>
            <!-- ===== SEARCH + DROPDOWN LOKASI ===== -->
            <div class="search-lokasi-wrapper">

                <!-- Ikon search -->
                <svg class="search-icon" viewBox="0 0 24 24">
                    <path
                        d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                </svg>

                <form id="searchForm" action="/reservasi_hotel/index.php" method="GET"
                    style="flex:1; min-width:0; display:flex;">
                    <input type="hidden" name="lokasi" id="hiddenLokasi" value="<?= $lokasi_filter; ?>">
                    <input type="text" name="cari" id="searchInput" value="<?= $pencarian; ?>"
                        oninput="if(this.value === '') document.getElementById('searchForm').submit();"
                        data-hotel-list='<?= $hotelJSON; ?>'
                        style="flex:1; min-width:0; padding:10px 14px 10px 40px; font-size:0.9rem; border:none; outline:none; background:transparent; font-family:inherit;">
                </form>

                <!-- Divider vertikal -->
                <div class="lokasi-divider"></div>

                <!-- Tombol dropdown lokasi -->
                <button type="button" class="btn-lokasi-dropdown" id="btnLokasiDropdown" aria-expanded="false">
                    <svg viewBox="0 0 24 24" class="chevron-lokasi"
                        style="width:14px;height:14px; fill:#475569; flex-shrink:0; margin-right:2px;">
                        <path
                            d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                    </svg>
                    <span id="lokasiLabel"><?= $lokasi_filter !== '' ? $lokasi_filter : 'Lokasi'; ?></span>
                    <svg viewBox="0 0 24 24" class="chevron-lokasi">
                        <path d="M7 10l5 5 5-5z" />
                    </svg>
                </button>

                <!-- Panel dropdown lokasi -->
                <div class="lokasi-dropdown-panel" id="lokasiDropdownPanel">

                    <!-- Opsi deteksi lokasi terdekat (GPS) -->
                    <button type="button" class="lokasi-item lokasi-item-terdekat" id="btnLokasiTerdekat">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.94 3c-.46-4.17-3.77-7.48-7.94-7.94V1h-2v2.06C6.83 3.52 3.52 6.83 3.06 11H1v2h2.06c.46 4.17 3.77 7.48 7.94 7.94V23h2v-2.06c4.17-.46 7.48-3.77 7.94-7.94H23v-2h-2.06zM12 19c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z" />
                        </svg>
                        Gunakan Lokasi Saya
                    </button>

                    <div class="lokasi-divider-item"></div>

                    <!-- Opsi semua lokasi -->
                    <button type="button" class="lokasi-item <?= $lokasi_filter === '' ? 'active' : ''; ?>"
                        data-lokasi="">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                        </svg>
                        Semua Lokasi
                    </button>

                    <!-- Daftar lokasi dari DB -->
                    <?php foreach ($daftarLokasi as $lok): ?>
                    <button type="button" class="lokasi-item <?= ($lokasi_filter === $lok) ? 'active' : ''; ?>"
                        data-lokasi="<?= htmlspecialchars($lok); ?>">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                        </svg>
                        <?= htmlspecialchars($lok); ?>
                    </button>
                    <?php endforeach; ?>

                </div>
            </div>
            <!-- ===================================== -->
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

                <?php if (!$is_admin): ?>
                <!-- ===== DESKTOP: Nama → Dropdown Menu ===== -->
                <div class="user-dropdown-wrapper" id="userDropdownWrapper">
                    <button type="button" class="btn-user-nama" id="btnUserNama">
                        <?= $nama_user; ?>
                        <svg class="chevron-user" viewBox="0 0 24 24">
                            <path d="M7 10l5 5 5-5z" />
                        </svg>
                    </button>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <div class="user-dropdown-header"><?= $nama_user; ?></div>
                        <a href="/reservasi_hotel/layanan_pembayaran/riwayat_pembayaran.php">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z" />
                            </svg>
                            Riwayat Pembayaran
                        </a>
                        <div class="user-dropdown-divider"></div>
                        <a href="/reservasi_hotel/layanan_autentikasi/keluar.php" class="item-keluar">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" />
                            </svg>
                            Keluar
                        </a>
                    </div>
                </div>

                <span class="divider" style="color: #cbd5e1;">|</span>

                <!-- ===== DESKTOP: Wishlist dengan label ===== -->
                <a href="/reservasi_hotel/layanan_wishlist/halaman_love.php" class="nav-love-link" title="Wishlist">
                    <svg class="nav-love-icon <?= $jumlah_wishlist > 0 ? 'filled' : ''; ?>" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                    </svg>
                    <span class="nav-love-label">Wishlist</span>
                    <?php if ($jumlah_wishlist > 0): ?>
                    <span class="nav-love-badge" data-count="<?= $jumlah_wishlist; ?>">
                        <?= $jumlah_wishlist; ?>
                    </span>
                    <?php else: ?>
                    <span class="nav-love-badge" data-count="0" style="display:none;"></span>
                    <?php endif; ?>
                </a>

                <?php else: ?>
                <!-- ===== ADMIN DESKTOP ===== -->
                <a href="/reservasi_hotel/layanan_hotel/kelola_hotel.php"
                    style="color: #FFFFFF; text-decoration: none; font-weight: 600;">Admin Panel</a>
                <span class="divider" style="color: #cbd5e1;">|</span>
                <a href="/reservasi_hotel/layanan_autentikasi/keluar.php" class="btn-keluar"
                    style="background: #E60000; border: none; color: #ffffff; text-decoration: none; font-weight: 600; cursor: pointer; font-size: 0.9rem; padding: 6px 16px; border-radius: 6px;">Keluar</a>
                <?php endif; ?>

                <?php else: ?>
                <!-- ===== BELUM LOGIN DESKTOP ===== -->
                <button type="button" class="btn-masuk" onclick="openLoginModal('login')"
                    style="background: none; border: none; color: #FFFFFF; text-decoration: none; font-weight: 600; cursor: pointer; font-size: 0.9rem;">
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

<!-- BARIS OVERLAY LAMA DI SINI SUDAH DIHAPUS & DIPINDAHKAN KE DALAM HEADER DI ATAS -->

<!-- ============================================================
     MOBILE HAMBURGER MENU PANEL (terpisah, di luar header flow)
     ============================================================ -->
<div id="mobileMenuPanel"
    style="display:none; position:fixed; top:72px; right:16px; background:#0A0036; border:1px solid rgba(255,255,255,0.15); border-radius:12px; padding:12px; min-width:220px; z-index:1050; box-shadow:0 10px 30px rgba(0,0,0,0.25); flex-direction:column; gap:4px;">
    <?php if (isset($_SESSION['peran'])): ?>

    <?php if (!$is_admin): ?>
    <div
        style="color:#94a3b8; font-size:0.78rem; padding:4px 12px 6px; border-bottom:1px solid rgba(255,255,255,0.1); margin-bottom:4px;">
        <?= $nama_user; ?>
    </div>

    <a href="/reservasi_hotel/layanan_wishlist/halaman_love.php" class="mobile-wishlist-row">
        <svg class="mobile-wishlist-icon <?= $jumlah_wishlist > 0 ? 'filled-mobile' : ''; ?>" viewBox="0 0 24 24">
            <path
                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
        </svg>
        <span class="mobile-wishlist-label">Wishlist</span>
        <?php if ($jumlah_wishlist > 0): ?>
        <span class="mobile-wishlist-badge" id="mobileLoveBadge"><?= $jumlah_wishlist; ?></span>
        <?php else: ?>
        <span class="mobile-wishlist-badge" id="mobileLoveBadge" style="display:none;"></span>
        <?php endif; ?>
    </a>

    <a href="/reservasi_hotel/layanan_pembayaran/riwayat_pembayaran.php" class="mobile-riwayat-link">
        <svg style="width:16px;height:16px;fill:#94a3b8;flex-shrink:0;" viewBox="0 0 24 24">
            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z" />
        </svg>
        Riwayat Pembayaran
    </a>

    <div class="mobile-menu-divider"></div>
    <a href="/reservasi_hotel/layanan_autentikasi/keluar.php" class="mobile-keluar-btn">Keluar</a>

    <?php else: ?>
    <a href="/reservasi_hotel/layanan_hotel/kelola_hotel.php" class="mobile-riwayat-link">Admin Panel</a>
    <div class="mobile-menu-divider"></div>
    <a href="/reservasi_hotel/layanan_autentikasi/keluar.php" class="mobile-keluar-btn">Keluar</a>
    <?php endif; ?>

    <?php else: ?>
    <button type="button" onclick="openLoginModal('login'); closeMobileMenu();"
        style="background:none;border:none;color:#fff;font-weight:600;font-size:0.9rem;padding:8px 12px;text-align:left;cursor:pointer;font-family:inherit;border-radius:6px;width:100%;">
        Masuk
    </button>
    <button type="button" onclick="openLoginModal('daftar'); closeMobileMenu();"
        style="background:#2563eb;border:none;color:#fff;font-weight:600;font-size:0.9rem;padding:8px 16px;border-radius:6px;cursor:pointer;font-family:inherit;width:100%;margin-top:4px;">
        Daftar
    </button>
    <?php endif; ?>
</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const hamburgerToggle = document.getElementById('hamburgerToggle');
    const mobileMenuPanel = document.getElementById('mobileMenuPanel');

    function closeMobileMenu() {
        if (mobileMenuPanel) mobileMenuPanel.style.display = 'none';
        if (hamburgerToggle) hamburgerToggle.classList.remove('active');
    }
    window.closeMobileMenu = closeMobileMenu;

    if (hamburgerToggle && mobileMenuPanel) {
        hamburgerToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = mobileMenuPanel.style.display === 'flex';
            mobileMenuPanel.style.display = isOpen ? 'none' : 'flex';
            hamburgerToggle.classList.toggle('active', !isOpen);
        });

        document.addEventListener('click', (e) => {
            if (!mobileMenuPanel.contains(e.target) && !hamburgerToggle.contains(e.target)) {
                closeMobileMenu();
            }
        });
    }

    /* ============================================================
        USER DROPDOWN DESKTOP
       ============================================================ */
    const userDropdownWrapper = document.getElementById('userDropdownWrapper');
    const btnUserNama = document.getElementById('btnUserNama');

    if (userDropdownWrapper && btnUserNama) {
        btnUserNama.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdownWrapper.classList.toggle('open');
        });
        document.addEventListener('click', (e) => {
            if (!userDropdownWrapper.contains(e.target)) {
                userDropdownWrapper.classList.remove('open');
            }
        });
    }

    /* ============================================================
        DROPDOWN LOKASI
       ============================================================ */
    const btnLokasi = document.getElementById('btnLokasiDropdown');
    const panelLokasi = document.getElementById('lokasiDropdownPanel');
    const hiddenLokasi = document.getElementById('hiddenLokasi');
    const lokasiLabel = document.getElementById('lokasiLabel');
    const searchForm = document.getElementById('searchForm');

    if (btnLokasi && panelLokasi) {
        btnLokasi.addEventListener('click', (e) => {
            e.stopPropagation();
            panelLokasi.classList.toggle('open');
            btnLokasi.classList.toggle('open');
            btnLokasi.setAttribute('aria-expanded', panelLokasi.classList.contains('open'));
        });

        document.addEventListener('click', (e) => {
            if (!btnLokasi.contains(e.target) && !panelLokasi.contains(e.target)) {
                panelLokasi.classList.remove('open');
                btnLokasi.classList.remove('open');
            }
        });

        panelLokasi.querySelectorAll('.lokasi-item[data-lokasi]').forEach(btn => {
            btn.addEventListener('click', () => {
                const val = btn.getAttribute('data-lokasi');
                if (hiddenLokasi) hiddenLokasi.value = val;
                if (lokasiLabel) lokasiLabel.textContent = val !== '' ? val : 'Lokasi';

                panelLokasi.querySelectorAll('.lokasi-item').forEach(b => b.classList.remove(
                    'active'));
                btn.classList.add('active');

                panelLokasi.classList.remove('open');
                btnLokasi.classList.remove('open');
                if (searchForm) searchForm.submit();
            });
        });

        const btnTerdekat = document.getElementById('btnLokasiTerdekat');
        if (btnTerdekat) {
            btnTerdekat.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    alert('Browser Anda tidak mendukung geolocation.');
                    return;
                }
                btnTerdekat.textContent = 'Mendeteksi...';
                btnTerdekat.disabled = true;

                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const latitude = pos.coords.latitude;
                        const longitude = pos.coords.longitude;
                        const url = new URL(window.location.href);
                        url.searchParams.set('latitude', latitude.toFixed(6));
                        url.searchParams.set('longitude', longitude.toFixed(6));
                        url.searchParams.delete('lokasi');
                        if (lokasiLabel) lokasiLabel.textContent = 'Terdekat';
                        window.location.href = url.toString();
                    },
                    (err) => {
                        alert('Tidak dapat mengakses lokasi: ' + err.message);
                        btnTerdekat.innerHTML =
                            `<svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:#16a34a;flex-shrink:0;"><path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.94 3c-.46-4.17-3.77-7.48-7.94-7.94V1h-2v2.06C6.83 3.52 3.52 6.83 3.06 11H1v2h2.06c.46 4.17 3.77 7.48 7.94 7.94V23h2v-2.06c4.17-.46 7.48-3.77 7.94-7.94H23v-2h-2.06zM12 19c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/></svg>Gunakan Lokasi Saya`;
                        btnTerdekat.disabled = false;
                    }, {
                        timeout: 8000
                    }
                );
            });
        }
    }

    /* ============================================================
        ANIMASI PLACEHOLDER SEARCHBAR
       ============================================================ */
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

    /* ============================================================
       SEARCH OVERLAY — kunci scroll saat searchbar aktif
       ============================================================ */
    const searchOverlay = document.getElementById('search-overlay');
    const searchLokasi = document.querySelector('.search-lokasi-wrapper');
    const headerEl = document.querySelector('header');

    function bukaSearchOverlay() {
        if (searchOverlay) searchOverlay.classList.add('active');
        if (headerEl) headerEl.classList.add('overlay-active');

        // UBAH BAGIAN INI: Mengunci scroll tanpa menghilangkan scrollbar bawaan browser
        document.body.style.top = `-${window.scrollY}px`;
        document.body.style.position = 'fixed';
        document.body.style.width = '100%';
    }

    function tutupSearchOverlay() {
        if (searchOverlay) searchOverlay.classList.remove('active');
        if (headerEl) headerEl.classList.remove('overlay-active');
        if (panelLokasi) panelLokasi.classList.remove('open');
        if (btnLokasi) btnLokasi.classList.remove('open');
        if (searchInput) searchInput.blur();

        // UBAH BAGIAN INI: Mengembalikan posisi scroll ke semula saat overlay ditutup
        const scrollY = document.body.style.top;
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        window.scrollTo(0, parseInt(scrollY || '0') * -1);
    }

    window.tutupSearchOverlay = tutupSearchOverlay;

    searchInput.addEventListener('focus', bukaSearchOverlay);
    if (btnLokasi) btnLokasi.addEventListener('click', bukaSearchOverlay);

    document.addEventListener('click', (e) => {
        if (
            searchOverlay &&
            searchOverlay.classList.contains('active') &&
            !searchLokasi?.contains(e.target) &&
            !panelLokasi?.contains(e.target)
        ) {
            tutupSearchOverlay();
        }
    });
});

/* ============================================================
    UPDATE BADGE WISHLIST
   ============================================================ */
function updateNavLoveBadge(total) {
    const badge = document.querySelector('.nav-love-badge');
    if (badge) {
        badge.setAttribute('data-count', total);
        if (total > 0) {
            badge.textContent = total;
            badge.style.display = 'inline-flex';
        } else {
            badge.textContent = '';
            badge.style.display = 'none';
        }
    }
    const mobileBadge = document.getElementById('mobileLoveBadge');
    if (mobileBadge) {
        if (total > 0) {
            mobileBadge.textContent = total;
            mobileBadge.style.display = 'inline-flex';
        } else {
            mobileBadge.textContent = '';
            mobileBadge.style.display = 'none';
        }
    }
}
</script>