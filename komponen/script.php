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

    // Tampilkan popup saat refresh 2x
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

    // logika handle pengiriman formulir login di modal
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
                    // Login berhasil
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

    // logika handle pendaftaran pengiriman formulir di modal
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

                    // logika reset form
                    registerForm.reset();

                    // logika ganti ke tab login setelah 1.5 detik
                    setTimeout(() => {
                        switchModalTab('login');
                        // logika clear form login 
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