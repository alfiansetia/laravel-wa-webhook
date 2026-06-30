<!-- ─── PANEL 2: CHATS ─── -->
<div class="panel panel-chats">
    <div class="panel-header py-3 px-3">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary btn-sm border-0" onclick="goBack('accounts')">
                <i class="bi bi-chevron-left"></i>
            </button>
            <h5 class="m-0 fw-bold">Chats</h5>
            <button class="btn btn-outline-secondary border-0 btn-sm rounded-circle p-1"
                onclick="loadChats(window.__activeAccountId)" title="Refresh Chats">
                <i class="bi bi-arrow-clockwise" style="font-size: 0.9rem;"></i>
            </button>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-primary btn-sm rounded-circle" onclick="openCreateChatModal()"
                title="Create New Chat">
                <i class="bi bi-plus-lg"></i>
            </button>
            <span class="badge bg-secondary rounded-pill" id="chat-count">0</span>
        </div>
    </div>
    <div class="panel-body" id="chats-list">
        <div class="text-center p-5 text-muted">
            <i class="bi bi-phone fs-2 opacity-50 mb-2 d-block"></i>
            <p class="small">Choose an account / session from the left to view chats.</p>
        </div>
    </div>
</div>
