<style>
/* Global Admin Layout */
html {
    scroll-behavior: smooth;
}

.dashboard-wrapper {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 24px;
    align-items: start;
    margin-top: 20px;
}

/* Sticky Sidebar Menu */
.admin-sidebar {
    position: sticky;
    top: 90px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    padding: 20px;
}

.sidebar-title {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #000000;
    margin-bottom: 15px;
    font-weight: 700;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #475569;
    text-decoration: none;
    font-weight: 500;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
    background: #0f172a;
    color: #FFFFFF;
}

.admin-main-content {
    display: flex;
    flex-direction: column;
    gap: 32px;
}

/* Responsive untuk Tablet / Mobile */
@media (max-width: 992px) {
    .dashboard-wrapper {
        grid-template-columns: 1fr;
    }

    .admin-sidebar {
        position: relative;
        top: 0;
    }

    .sidebar-menu {
        flex-direction: row;
        flex-wrap: wrap;
    }

    .sidebar-menu a {
        flex: 1 1 150px;
        justify-content: center;
    }
}
</style>