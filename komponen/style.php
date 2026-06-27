<style>
/* utama */
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

/* layout */
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

.flex-hotel {
    position: relative;
    width: 100%;
}

#skeleton-container,
#actual-content {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    width: 100%;
    align-items: stretch;
}

#actual-content {
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease-in-out;
}

/* banner slider */
.banner-section {
    max-width: 1100px;
    margin: 30px auto 0;
    padding: 0 20px;
    margin-bottom: 55px !important;
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

/* Responsive ke HP */
@media (max-width: 650px) {
    .banner-slide {
        width: 100%;
    }

    .banner-slide img {
        aspect-ratio: 16 / 8;
    }
}

/* Indikator Dots */
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

/* Efek Pil Aktif */
.banner-dot.active {
    width: 22px;
    border-radius: 4px;
    background: #475569;
}

.banner-empty {
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
}

/* modal login */
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

/* ==========================================================================
    LAYOUT GRID UTAMA (Kunci 4 Kolom Ke Samping)
    ========================================================================== */
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

/* Responsive Layout */
@media (max-width: 992px) {
    .grid-4-kolom {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 576px) {
    .grid-4-kolom {
        grid-template-columns: 1fr !important;
    }
}

/* ==========================================================================
    CARD HOTEL
    ========================================================================== */
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

.img-wrapper {
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
    transition: transform 1s ease-in-out;
}

.card-hotel:hover .card-img {
    transform: scale(1.05);
}

.card-body {
    padding: 20px;
    min-height: 220px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

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

.price-original {
    font-size: 0.8rem;
    color: #9ca3af;
    font-style: italic;
    font-weight: 500;
    display: block;
    position: relative;
    display: inline-block;
}

/* badge diskon */
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

.discount-badge {
    display: inline-block;
    background: #fee2e2;
    color: #991b1b;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 5px;
    border-radius: 3px;
}

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

/*  SKELETON SHIMMER */
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

/* SECTION DISKON BACKGROUND  */
.section-diskon-besar {
    margin-bottom: 40px;
    margin-top: 40px;
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

.grid-diskon {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    width: 100%;
}

@media (max-width: 992px) {
    .grid-diskon {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .grid-diskon {
        grid-template-columns: 1fr;
    }
}

/* utama */
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

/* layout */
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

.flex-hotel {
    position: relative;
    width: 100%;
}

#skeleton-container,
#actual-content {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    width: 100%;
    align-items: stretch;
}

#actual-content {
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease-in-out;
}

/* banner slider */
.banner-section {
    max-width: 1100px;
    margin: 30px auto 0;
    padding: 0 20px;
    margin-bottom: 55px !important;
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

/* Responsive ke HP */
@media (max-width: 650px) {
    .banner-slide {
        width: 100%;
    }

    .banner-slide img {
        aspect-ratio: 16 / 8;
    }
}

/* Indikator Dots */
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

/* Efek Pil Aktif */
.banner-dot.active {
    width: 22px;
    border-radius: 4px;
    background: #475569;
}

.banner-empty {
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
}

/* modal login */
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

/* ==========================================================================
    LAYOUT GRID UTAMA (Kunci 4 Kolom Ke Samping)
    ========================================================================== */
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

/* Responsive Layout */
@media (max-width: 992px) {
    .grid-4-kolom {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 576px) {
    .grid-4-kolom {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}


/* admin panel */
.panel-container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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

.grid-layout {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 30px;
    align-items: start;
}

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

.sub-section-title {
    font-size: 0.8rem;
    font-weight: 700;
    color: #1e293b;
    text-transform: uppercase;
    margin: 20px 0 10px 0;
    padding-top: 10px;
    border-top: 1px dashed #e2e8f0;
}

.form-group {
    margin-bottom: 14px;
}

.form-group label {
    display: block;
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 5px;
    color: #475569;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 9px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.88rem;
    background: white;
    outline: none;
}

.flex-inputs {
    display: flex;
    gap: 10px;
}

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

.img-thumb {
    width: 50px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    background: #e2e8f0;
}

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

/* Admin - Kelola Banner */
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

/* mobile */
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

    .section-title,
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

    /* Banner mobile: tampilkan 1 per slide */
    .banner-slide {
        min-width: 100%;
    }

    .banner-slide img {
        height: 180px;
    }

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