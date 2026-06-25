<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

body {
    background-color: #f8fafc;
}

.wrapper {
    max-width: 360px;
    margin: 100px auto;
    padding: 32px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

.form-title {
    font-size: 1.25rem;
    font-weight: 600;
    text-align: center;
    margin-bottom: 24px;
    color: #0f172a;
}

.alert {
    color: #e53e3e;
    font-size: 0.85rem;
    margin-bottom: 16px;
    text-align: center;
    font-weight: 500;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 6px;
    color: #475569;
}

.form-group input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: border-color 0.15s;
}

.form-group input:focus {
    outline: none;
    border-color: #0f172a;
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
    color: #0f172a;
}

.toggle-password svg {
    width: 18px;
    height: 18px;
}

.btn-submit {
    width: 100%;
    padding: 10px;
    background: #0E56FF;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-submit:hover {
    background: #0d4ed5;
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
    font-weight: 500;
    color: #94a3b8;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
    margin-bottom: -2px;
}

.tab-button.active {
    color: #0E56FF;
    border-bottom-color: #0E56FF;
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

.toggle-text a {
    color: #0E56FF;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
}

.toggle-text a:hover {
    text-decoration: underline;
}
</style>