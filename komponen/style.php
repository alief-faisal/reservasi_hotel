<style>
/* =============================================
   RESET & BASE
   ============================================= */
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

/* =============================================
   CONTAINER & LAYOUT UTAMA
   ============================================= */
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

/* Wrapper kartu hotel (skeleton & actual content) */
.flex-hotel {
    position: relative;
    width: 100%;
}

/* Grid 4 kolom untuk skeleton & konten asli */
#skeleton-container,
#actual-content {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    width: 100%;
    align-items: stretch;
}

/* Konten asli disembunyikan dulu, muncul setelah data loaded */
#actual-content {
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease-in-out;
}

/* =============================================
   GRID 4 KOLOM (DIPAKAI DI BERBAGAI SECTION)
   ============================================= */
.grid-4-kolom {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 20px !important;
    width: 100% !important;
    margin-bottom: 30px;
}

.container-diskon-tengah {
    width: 100% !important;
    margin-top: 20px;
    margin-bottom: 40px;
}

/* =============================================
   BANNER SLIDER
   ============================================= */
.banner-section {
    max-width: 1100px;
    margin: 30px auto 55px;
    padding: 0 20px;
}

.banner-slider-wrapper {
    position: relative;
    width: 100%;
    overflow: visible;
    background: transparent !important;
    box-shadow: none !important;
    border-radius: 0;
    user-select: none;
    -webkit-user-select: none;
}

.banner-track-container {
    overflow: hidden;
    width: 100%;
    cursor: grab;
    background: transparent !important;
}

.banner-track-container:active {
    cursor: grabbing;
}

.banner-track {
    display: flex;
    margin: 0 -8px;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    will-change: transform;
    background: transparent !important;
}

/* Setiap slide menempati 50% lebar (2 banner terlihat sekaligus) */
.banner-slide {
    position: relative;
    flex-shrink: 0;
    padding: 0 8px;
    box-sizing: border-box;
    width: 50%;
    background: transparent !important;
}

.banner-slide img {
    width: 100%;
    aspect-ratio: 16 / 6;
    height: auto;
    object-fit: cover;
    display: block;
    border-radius: 12px;
    pointer-events: none;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

/* Indikator dots di bawah banner */
.banner-dots {
    position: absolute;
    bottom: -28px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 8px;
    z-index: 10;
}

.banner-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #cbd5e1;
    border: none;
    cursor: pointer;
    padding: 0;
    transition: background 0.3s ease, width 0.3s ease, border-radius 0.3s ease;
}

/* Dot aktif berbentuk pil */
.banner-dot.active {
    width: 22px;
    border-radius: 4px;
    background: #475569;
}

/* Tampilan kosong jika tidak ada banner */
.banner-empty {
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
}

/* =============================================
   MODAL LOGIN / REGISTER
   ============================================= */
.modal-overlay {
    position: fixed;
    inset: 0;
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

/* Tab Login / Register */
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

/* =============================================
   FORM GROUP (DIPAKAI DI MODAL & ADMIN PANEL)
   ============================================= */
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

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 9px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.9rem;
    background: white;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
    font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group input::placeholder {
    color: #cbd5e1;
}

/* Wrapper input password + tombol show/hide */
.password-wrapper {
    position: relative;
    width: 100%;
}

.password-wrapper input {
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

/* =============================================
   TOMBOL MODAL
   ============================================= */

/* Tombol submit utama (biru) */
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

/* Tombol "Nanti saja" (transparan) */
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

/* =============================================
   ALERT / NOTIFIKASI
   ============================================= */

/* Alert error (merah) */
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

/* Alert sukses (hijau) */
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
    font-weight: 600;
    cursor: pointer;
    font-size: 0.85rem;
}

.toggle-text button:hover {
    text-decoration: underline;
}

/* =============================================
   CARD HOTEL (GRID)
   ============================================= */
.card-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
}

.card-hotel {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
}

/* Wrapper gambar kartu */
.img-wrapper {
    position: relative;
    width: 100%;
    height: 180px;
    overflow: hidden;
    background-color: #f1f5f9;
    flex-shrink: 0;
}

.card-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-body {
    padding: 20px;
    min-height: 220px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Meta info (lokasi, kategori) */
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

/* Rating bintang */
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

/* Bagian harga di bawah card */
.price-wrapper {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    padding-top: 8px;
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
    font-size: 0.7rem;
    font-weight: 400;
    color: #686868;
    margin-left: 2px;
}

/* Harga asli dengan garis coret merah */
.price-original {
    font-size: 0.8rem;
    color: #9ca3af;
    font-style: italic;
    font-weight: 500;
    position: relative;
    display: inline-block;
}

.price-original::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    height: 2px;
    background-color: #ef4444;
    transform: rotate(-3deg);
    transform-origin: center;
}

/* Badge diskon (pill kecil merah muda) */
.discount-badge {
    display: inline-block;
    background: #fee2e2;
    color: #991b1b;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 5px;
    border-radius: 3px;
}

/* Badge "Favorit" di sudut kiri atas gambar */
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

/* =============================================
   SKELETON LOADING (SHIMMER)
   ============================================= */
@keyframes efekShimmer {
    0% {
        background-position: -200% 0;
    }

    100% {
        background-position: 200% 0;
    }
}

/* Kelas shimmer universal — tempel ke elemen apapun */
.shimmer {
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%;
    animation: efekShimmer 1.5s infinite linear;
    border-radius: 4px;
}

/* Skeleton teks generik */
.skeleton-text {
    height: 14px;
    border-radius: 4px;
    margin-bottom: 12px;
}

/* -----------------------------------------------
   SKELETON CARD GRID (baris 1 & 2 — 4 kolom)
   ----------------------------------------------- */

/* Gambar skeleton card grid */
.sk-card-img {
    width: 100%;
    height: 180px;
    border-radius: 0;
    /* ikuti card */
    flex-shrink: 0;
}

/* Badge diskon skeleton (strip bawah gambar) */
.sk-badge-strip {
    height: 24px;
    width: 100%;
    border-radius: 0;
    margin-top: -24px;
    /* tumpuk di atas gambar seperti badge asli */
    position: relative;
    z-index: 2;
}

/* Body skeleton card grid */
.sk-card-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    flex: 1;
    gap: 0;
}

/* Judul hotel */
.sk-title {
    height: 20px;
    width: 80%;
    margin-bottom: 10px;
}

/* Bintang rating */
.sk-stars {
    height: 14px;
    width: 55%;
    margin-bottom: 10px;
}

/* Lokasi */
.sk-location {
    height: 12px;
    width: 45%;
    margin-bottom: 16px;
}

/* Harga coret (opsional, muncul di card diskon) */
.sk-price-old {
    height: 12px;
    width: 50%;
    margin-bottom: 6px;
}

/* Harga final */
.sk-price {
    height: 22px;
    width: 70%;
    margin-top: auto;
}

/* -----------------------------------------------
   SKELETON SECTION DISKON (latar biru)
   Shimmer lebih terang agar kontras di atas biru
   ----------------------------------------------- */
.shimmer-dark {
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.08) 25%, rgba(255, 255, 255, 0.18) 50%, rgba(255, 255, 255, 0.08) 75%);
    background-size: 200% 100%;
    animation: efekShimmer 1.5s infinite linear;
    border-radius: 4px;
}

/* -----------------------------------------------
   SKELETON CARD LIST HORIZONTAL (baris 3)
   ----------------------------------------------- */
.sk-list-card {
    display: flex;
    flex-direction: row;
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    height: 160px;
}

/* Bagian kiri gambar */
.sk-list-img {
    width: 30%;
    min-width: 240px;
    height: 100%;
    flex-shrink: 0;
    border-radius: 0;
}

/* Bagian tengah teks */
.sk-list-center {
    flex: 1;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    justify-content: center;
}

.sk-list-title {
    height: 22px;
    width: 65%;
}

.sk-list-stars {
    height: 14px;
    width: 40%;
}

.sk-list-location {
    height: 12px;
    width: 50%;
}

/* Bagian kanan harga */
.sk-list-right {
    width: 35%;
    padding: 20px;
    border-left: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    justify-content: space-around;
    gap: 6px;
    background: #fcfcfc;
}

.sk-list-room-label {
    height: 12px;
    width: 60%;
    margin-left: auto;
}

.sk-list-price-old {
    height: 12px;
    width: 50%;
    margin-left: auto;
}

.sk-list-price {
    height: 20px;
    width: 70%;
    margin-left: auto;
}

.sk-list-divider {
    height: 1px;
    width: 100%;
    background: #e2e8f0;
    margin: 4px 0;
}

/* Tampilan kosong jika tidak ada data */
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

/* =============================================
   SECTION DISKON (BACKGROUND BIRU GELAP)
   ============================================= */
.section-diskon-besar {
    margin: 40px 0;
    background: linear-gradient(135deg, #002A61 0%, #152BF5 100%);
    padding: 40px;
    border-radius: 12px;
}

.section-title-diskon {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 30px;
    color: #FFFFFF;
    letter-spacing: -0.5px;
}

/* Grid diskon statis (tanpa slider) */
.grid-diskon {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    width: 100%;
}

/* =============================================
   SLIDER DISKON (HORIZONTAL SCROLL + CHEVRON)
   ============================================= */
.slider-diskon-wrapper {
    position: relative;
    width: 100%;
    overflow: visible;
    padding: 5px 0;
    cursor: grab;
}

/* Scrollable flex row, scrollbar disembunyikan */
.grid-diskon-slider {
    display: flex !important;
    gap: 20px;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding-bottom: 10px;
    width: 100%;
}

.grid-diskon-slider::-webkit-scrollbar {
    display: none;
}

/* Setiap kartu di slider tepat 1/4 lebar (4 kartu terlihat) */
.grid-diskon-slider .card-link {
    flex: 0 0 calc((100% - (3 * 20px)) / 4) !important;
    min-width: calc((100% - (3 * 20px)) / 4) !important;
    box-sizing: border-box;
}

/* Tombol chevron kiri/kanan untuk navigasi slider desktop */
.chevron-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    background-color: #ffffff;
    border: 1px solid #dadce0;
    border-radius: 50%;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
    display: none;
    /* tampil via JS */
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s ease;
}

.chevron-btn:hover {
    background-color: #f8fafc;
    transform: translateY(-50%) scale(1.05);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.16);
}

.chevron-btn svg {
    width: 24px;
    height: 24px;
    fill: #1e293b;
}

/* Chevron melayang sedikit ke luar batas kontainer */
.chevron-left {
    left: -22px;
}

.chevron-right {
    right: -22px;
}

/* =============================================
   BADGE DISKON DI DALAM GAMBAR KARTU
   ============================================= */

/* Strip memanjang di bagian bawah gambar */
.badge-diskon-strip {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    text-align: center;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    padding: 6px 10px;
    z-index: 2;
    box-sizing: border-box;
}

/* Varian Standard: merah */
.badge-diskon-standard {
    background: linear-gradient(90deg, #FF3030, #6D0000);
    color: #fff;
}

/* Varian Deluxe: biru */
.badge-diskon-deluxe {
    background: linear-gradient(90deg, #2723FF, #00014B);
    color: #fff;
}

/* =============================================
   CARD HOTEL LIST (TAMPILAN BARIS / LIST VIEW)
   ============================================= */
.sub-section-location-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: #0f172a;
    letter-spacing: -0.5px;
}

.list-container-vertical {
    display: flex;
    flex-direction: column;
    gap: 20px;
    width: 100%;
    margin: 10px 0 30px;
}

/* Card berbentuk baris horizontal */
.card-hotel-list {
    display: flex;
    flex-direction: row;
    background: #fff;
    border-radius: 8px;
    border: 1px solid #dadce0;
    overflow: hidden;
    text-decoration: none;
    color: #333;
    position: relative;
}

/* Bagian kiri: gambar */
.card-hotel-list .list-img-section {
    width: 30%;
    position: relative;
    min-width: 240px;
}

.card-hotel-list .list-img-section .list-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Bagian tengah: nama hotel, bintang, lokasi */
.card-hotel-list .list-center-section {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
    justify-content: center;
}

.card-hotel-list .list-hotel-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0;
}

.card-hotel-list .list-stars {
    display: flex;
    gap: 2px;
}

.card-hotel-list .list-stars .star-icon {
    width: 16px;
    height: 16px;
    fill: #ffb400;
}

.card-hotel-list .list-location {
    font-size: 0.9rem;
    color: #555;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Bagian kanan: harga rata kanan */
.card-hotel-list .list-right-section {
    width: 40%;
    padding: 15px 20px;
    border-left: 1px solid #eee;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: flex-end;
    text-align: right;
    background-color: #fcfcfc;
}

.card-hotel-list .room-price-row {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    width: 100%;
}

/* Header tipe kamar + badge diskon (kanan atas) */
.card-hotel-list .room-header-top {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
    width: 100%;
    margin-bottom: 3px;
}

.card-hotel-list .room-type-title {
    font-size: 0.85rem;
    font-weight: 700;
}

.card-hotel-list .title-standard {
    color: #333;
}

.card-hotel-list .title-deluxe {
    color: #333;
}

/* Badge diskon khusus list view (merah muda) */
.card-hotel-list .discount-badge {
    display: inline-block;
    background: #fee2e2;
    color: #6D0000;
    font-size: 0.68rem;
    font-weight: 800;
    padding: 2px 6px;
}

.card-hotel-list .price-original-wrapper {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    margin-bottom: 1px;
}

/* Harga asli dengan garis coret */
.card-hotel-list .price-original {
    position: relative;
    color: #999;
    font-size: 0.8rem;
    display: inline-block;
}

.card-hotel-list .price-original::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    height: 2px;
    background-color: #ef4444;
    transform: rotate(-3deg);
    transform-origin: center;
}

/* Harga final (merah) */
.card-hotel-list .list-final-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #ef4444;
    margin: 1px 0;
}

.card-hotel-list .list-price-suffix {
    font-size: 0.75rem;
    color: #777;
    font-weight: 400;
}

/* Garis pembatas di antara elemen harga */
.card-hotel-list .divider-line {
    width: 100%;
    border-top: 1px dashed #e0e0e0;
    margin: 10px 0;
}

/* =============================================
   ADMIN PANEL
   ============================================= */
.panel-container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 20px;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    gap: 30px;
}

.panel-title {
    font-size: 1.5rem;
    font-weight: 600;
    flex: 1;
}

/* Search bar di header admin */
.header-search {
    width: 320px;
    position: relative;
}

.header-search input {
    width: 100%;
    padding: 10px 16px 10px 38px;
    font-size: 0.88rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    outline: none;
    background-color: #ffffff;
}

.header-search input:focus {
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
}

.header-search svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 14px;
    height: 14px;
    fill: #94a3b8;
    pointer-events: none;
}

/* Layout 2 kolom: form kiri, tabel kanan */
.grid-layout {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 30px;
    align-items: start;
}

/* Kartu / kotak putih generik */
.box-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 24px;
}

.box-title {
    font-size: 1.05rem;
    font-weight: 600;
    margin-bottom: 20px;
    color: #334155;
}

/* Judul sub-section di form admin */
.sub-section-title {
    font-size: 0.8rem;
    font-weight: 700;
    color: #1e293b;
    text-transform: uppercase;
    margin: 20px 0 10px;
    padding-top: 10px;
    border-top: 1px dashed #e2e8f0;
}

/* Dua input sejajar (misal: harga & diskon) */
.flex-inputs {
    display: flex;
    gap: 10px;
}

/* Tombol submit admin (merah) */
.btn-submit {
    background: #dc2626;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 6px;
    width: 100%;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.9rem;
    margin-top: 15px;
}

.btn-submit:hover {
    background: #b91c1c;
}

.right-content-wrapper {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* =============================================
   TABEL DATA HOTEL (ADMIN)
   ============================================= */
.table-responsive {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 0.88rem;
}

th {
    background: #f1f5f9;
    padding: 12px 16px;
    color: #475569;
    font-weight: 600;
}

td {
    padding: 14px 16px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

/* Thumbnail gambar hotel di tabel */
.img-thumb {
    width: 50px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    background: #e2e8f0;
}

/* Link aksi edit/hapus di tabel */
.action-links a {
    text-decoration: none;
    font-weight: 500;
    margin-right: 12px;
    font-size: 0.85rem;
}

.link-edit {
    color: #0284c7;
}

.link-del {
    color: #ef4444;
}

/* =============================================
   STATISTIK (CHART)
   ============================================= */
.stats-section {
    grid-column: 1 / -1;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 24px;
    margin-top: 20px;
}

.stats-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 20px;
    color: #0f172a;
}

.chart-container {
    position: relative;
    height: 350px;
    margin-bottom: 30px;
}

.no-data-msg {
    text-align: center;
    color: #64748b;
    padding: 40px;
    font-size: 0.95rem;
}

/* =============================================
   ADMIN - KELOLA BANNER
   ============================================= */
.banner-admin-section {
    grid-column: 1 / -1;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 24px;
    margin-top: 20px;
}

.banner-admin-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 20px;
    color: #0f172a;
}

/* Grid preview banner yang sudah diupload */
.banner-admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.banner-admin-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    background: #f8fafc;
}

.banner-admin-card img {
    width: 100%;
    height: 130px;
    object-fit: cover;
    display: block;
}

.banner-admin-card-body {
    padding: 10px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.banner-admin-card-body span {
    font-size: 0.8rem;
    color: #475569;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 130px;
}

/* Tombol hapus banner (merah muda) */
.banner-del-btn {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    border-radius: 4px;
    padding: 4px 10px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.banner-del-btn:hover {
    background: #fee2e2;
}

/* Area upload banner (drag & drop look) */
.banner-upload-box {
    border: 2px dashed #cbd5e1;
    border-radius: 8px;
    padding: 28px;
    text-align: center;
    color: #64748b;
    font-size: 0.88rem;
}

.banner-upload-box input[type="file"] {
    display: block;
    margin: 12px auto 0;
    font-size: 0.85rem;
}

/* Tombol upload (biru) */
.btn-upload-banner {
    background: #0284c7;
    color: white;
    padding: 9px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.88rem;
    margin-top: 12px;
}

.btn-upload-banner:hover {
    background: #0369a1;
}

.banner-empty-admin {
    color: #94a3b8;
    font-size: 0.88rem;
    padding: 20px 0;
}

/* =============================================
   RESPONSIVE - TABLET (≤992px)
   ============================================= */
@media (max-width: 992px) {

    .grid-4-kolom,
    .grid-diskon {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

/* =============================================
   RESPONSIVE - MOBILE (≤768px)
   ============================================= */
@media (max-width: 768px) {

    /* Grid kartu: 2 kolom */
    #skeleton-container,
    #actual-content,
    .grid-4-kolom,
    .grid-diskon {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .container {
        margin: 30px auto;
    }

    .section-title,
    .section-title-diskon {
        font-size: 1.1rem;
    }

    .card-body {
        padding: 14px;
    }

    .card-title {
        min-height: 2.1rem;
    }

    .empty-state {
        grid-column: span 2;
    }

    /* Banner: 1 slide penuh per layar */
    .banner-slide {
        width: 100%;
        min-width: 100%;
    }

    .banner-slide img {
        height: 180px;
        aspect-ratio: 16 / 8;
    }

    /* Slider diskon: kartu 65% lebar layar (swipe) */
    .grid-diskon-slider {
        gap: 15px;
    }

    .grid-diskon-slider .card-link {
        flex: 0 0 65% !important;
        min-width: 65% !important;
    }

    /* Sembunyikan chevron di mobile */
    .chevron-btn {
        display: none !important;
    }

    /* Card list: stack vertikal di mobile */
    .card-hotel-list {
        flex-direction: column !important;
    }

    .card-hotel-list .list-img-section {
        width: 100% !important;
        height: 200px;
    }

    .card-hotel-list .list-right-section {
        width: 100% !important;
        border-left: none !important;
        border-top: 1px solid #eee;
        text-align: right !important;
        align-items: flex-end !important;
    }

    /* Admin panel: 1 kolom */
    .grid-layout {
        grid-template-columns: 1fr;
    }

    .stats-section,
    .banner-admin-section {
        grid-column: 1;
    }

    .header-search {
        width: 100%;
    }

    .panel-header {
        flex-direction: column;
        align-items: flex-start;
    }
}

/* =============================================
   RESPONSIVE - HP KECIL (≤576px)
   ============================================= */
@media (max-width: 576px) {

    .grid-4-kolom,
    .grid-diskon {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

/* =============================================
   RESPONSIVE - HP SANGAT KECIL (≤480px)
   ============================================= */
@media (max-width: 480px) {
    .modal-content {
        width: 95%;
        padding: 32px 24px;
    }

    .modal-header h2 {
        font-size: 1.25rem;
    }
}
</style>