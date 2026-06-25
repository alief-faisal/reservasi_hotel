<?php if (!isset($_SESSION['peran'])): ?>
<div id="loginModal" class="modal-overlay" style="display: flex;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Masuk</h2>
        </div>

        <div id="alertContainer"></div>

        <!-- logika login -->
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
                        <button type="button" class="toggle-password" onclick="toggleModalPassword('password-login')">
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