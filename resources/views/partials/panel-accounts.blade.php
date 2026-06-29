<!-- ─── PANEL 1: SESSIONS / ACCOUNTS ─── -->
<div class="panel panel-accounts">
    <div class="panel-header py-3 px-4">
        <div class="d-flex align-items-center gap-2">
            <h5 class="m-0 fw-bold">Accounts</h5>
            <button class="btn btn-outline-secondary border-0 btn-sm rounded-circle p-1" onclick="loadAccounts()"
                title="Refresh Accounts">
                <i class="bi bi-arrow-clockwise" style="font-size: 0.9rem;"></i>
            </button>
        </div>
        <button class="btn btn-primary btn-sm rounded-pill d-flex align-items-center gap-1"
            onclick="openAddAccountModal()">
            <i class="bi bi-plus-circle"></i> Add
        </button>
    </div>
    <div class="panel-body" id="accounts-list">
        <!-- Loaded dynamically -->
        <div class="text-center p-5 text-muted">
            <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
            <div>Loading accounts...</div>
        </div>
    </div>
</div>
