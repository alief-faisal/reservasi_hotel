<?php
/* logika ambil data banner dari database */
$query_banner = "SELECT * FROM banner ORDER BY urutan ASC, id_banner ASC";
$hasil_banner = $koneksi->query($query_banner);
$banner_list = [];
if ($hasil_banner) {
    while ($b = $hasil_banner->fetch_assoc()) {
        $banner_list[] = $b;
    }
}
$jumlah_banner = count($banner_list);
?>

<div class="banner-section">
    <?php if ($jumlah_banner > 0): ?>
    <div class="banner-slider-wrapper" id="bannerWrapper">

        <div class="banner-track-container" id="trackContainer">
            <div class="banner-track" id="bannerTrack">
                <?php foreach ($banner_list as $b):
                    $path_b = "/reservasi_hotel/assets/banner/" . htmlspecialchars($b['nama_file']);
                ?>
                <div class="banner-slide">
                    <img src="<?= $path_b ?>" alt="<?= htmlspecialchars($b['judul'] ?? 'Banner') ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="banner-dots" id="bannerDots"></div>

    </div>
    <?php else: ?>
    <div class="banner-empty">Belum ada banner yang ditambahkan.</div>
    <?php endif; ?>
</div>

<script>
(function() {
    const container = document.getElementById('trackContainer');
    const track = document.getElementById('bannerTrack');
    const dotsEl = document.getElementById('bannerDots');
    const wrapper = document.getElementById('bannerWrapper');

    if (!track || !container) return;

    const slides = track.querySelectorAll('.banner-slide');
    const total = slides.length;
    if (total === 0) return;

    let current = 0;
    let autoplay;

    function perView() {
        return window.innerWidth <= 650 ? 1 : 2;
    }

    function maxIndex() {
        return Math.max(0, total - perView());
    }

    function updateDots() {
        if (!dotsEl) return;
        const dots = dotsEl.querySelectorAll('.banner-dot');
        if (dots.length === 0) return;

        let activeDotIndex = Math.round(current / perView());
        if (activeDotIndex >= dots.length) activeDotIndex = dots.length - 1;

        dots.forEach((dot, idx) => {
            if (idx === activeDotIndex) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }

    function goTo(idx) {
        current = Math.min(Math.max(idx, 0), maxIndex());
        const slideWidthPercent = 100 / perView();
        track.style.transform = `translateX(-${current * slideWidthPercent}%)`;

        updateDots();
    }

    function rebuildDots() {
        if (!dotsEl) return;
        dotsEl.innerHTML = '';

        const pv = perView();
        if (total <= pv) {
            dotsEl.style.display = 'none';
            return;
        }

        dotsEl.style.display = 'flex';
        const totalDots = Math.ceil(total / pv);

        for (let i = 0; i < totalDots; i++) {
            const d = document.createElement('button');
            d.className = 'banner-dot';
            d.addEventListener('click', () => {
                goTo(i * pv);
            });
            dotsEl.appendChild(d);
        }
        updateDots();
    }


    // logika drag mouse
    let isDragging = false;
    let startX = 0;

    function getX(e) {
        return e.type.includes('mouse') ? e.pageX : e.touches[0].clientX;
    }

    function dragStart(e) {
        isDragging = true;
        startX = getX(e);
        clearInterval(autoplay);
        track.style.transition = 'none';
    }

    function dragMove(e) {
        if (!isDragging) return;
        const currentX = getX(e);
        const diffX = currentX - startX;

        const pv = perView();
        const baseTranslate = -current * (100 / pv);
        const movePercent = (diffX / container.offsetWidth) * 100;

        track.style.transform = `translateX(${baseTranslate + movePercent}%)`;
    }

    function dragEnd(e) {
        if (!isDragging) return;
        isDragging = false;
        track.style.transition = 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)';

        const endX = e.type.includes('mouse') ? e.pageX : e.changedTouches[0].clientX;
        const diffX = startX - endX;

        if (Math.abs(diffX) > 50) {
            if (diffX > 0) {
                goTo(current + perView());
            } else {
                goTo(current - perView());
            }
        } else {
            goTo(current);
        }

        startAutoplay();
    }

    container.addEventListener('mousedown', dragStart);
    container.addEventListener('mousemove', dragMove);
    container.addEventListener('mouseup', dragEnd);
    container.addEventListener('mouseleave', dragEnd);

    container.addEventListener('touchstart', dragStart, {
        passive: true
    });
    container.addEventListener('touchmove', dragMove, {
        passive: true
    });
    container.addEventListener('touchend', dragEnd);

    // logika autoplay banner
    function startAutoplay() {
        clearInterval(autoplay);
        autoplay = setInterval(() => {
            if (current >= maxIndex()) goTo(0);
            else goTo(current + perView());
        }, 4000);
    }

    function init() {
        rebuildDots();
        goTo(0);
        startAutoplay();
    }

    init();

    wrapper.addEventListener('mouseenter', () => clearInterval(autoplay));
    wrapper.addEventListener('mouseleave', startAutoplay);

    window.addEventListener('resize', () => {
        rebuildDots();
        goTo(0);
    });
})();
</script>