<!-- ─── HEADER NAVBAR ─── -->
<header class="main-header bg-body-tertiary">
    <div class="d-flex align-items-center gap-2">
        <span class="fs-4 text-primary"><i class="bi bi-chat-dots-fill"></i></span>
        <span class="h5 mb-0 fw-bold">WAHA SaaS</span>
        <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill ms-2">v1.1</span>
    </div>
    <div class="d-flex align-items-center gap-3">
        <!-- Theme Toggler -->
        <button class="btn btn-outline-secondary border-0 btn-sm rounded-circle px-2 py-1" id="theme-toggle"
            onclick="toggleTheme()" title="Change Theme">
            <i class="bi bi-sun-fill" id="theme-icon"></i>
        </button>
        <div class="dropdown">
            <a href="#" class="d-block text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=80&q=80"
                    alt="mdo" width="32" height="32" class="rounded-circle">
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text text-muted small">Logged in as Admin</span></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-sliders me-2"></i>Settings</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-box-arrow-right me-2"></i>Sign
                        out</a></li>
            </ul>
        </div>
    </div>
</header>
