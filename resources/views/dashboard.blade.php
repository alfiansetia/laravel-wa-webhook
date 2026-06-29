<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>WAHA SaaS — Admin Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .main-header {
            height: 60px;
            border-bottom: 1px solid var(--bs-border-color);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            justify-content: space-between;
            z-index: 10;
        }

        .app-layout {
            display: flex;
            flex: 1;
            height: calc(100vh - 60px);
            overflow: hidden;
            position: relative;
        }

        .panel {
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            border-right: 1px solid var(--bs-border-color);
            background-color: var(--bs-body-bg);
            transition: transform 0.3s ease, width 0.3s ease;
        }

        /* Desktop widths */
        .panel-accounts {
            width: 320px;
            min-width: 320px;
        }

        .panel-chats {
            width: 340px;
            min-width: 340px;
        }

        .panel-conversation {
            flex: 1;
            min-width: 0;
            border-right: none;
        }

        .panel-header {
            padding: 1rem;
            border-bottom: 1px solid var(--bs-border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: var(--bs-tertiary-bg);
        }

        .panel-body {
            flex: 1;
            overflow-y: auto;
        }

        /* List elements styling */
        .list-item {
            padding: 0.9rem 1.2rem;
            border-bottom: 1px solid var(--bs-border-color-translucent);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            position: relative;
        }

        .list-item:hover {
            background-color: var(--bs-secondary-bg);
        }

        .list-item.active {
            background-color: var(--bs-tertiary-bg);
        }

        .list-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: var(--bs-primary);
        }

        .avatar-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        /* Message Bubbles layout */
        .chat-area {
            background-color: var(--bs-secondary-bg);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            overflow-y: auto;
            flex: 1;
        }

        .msg-bubble {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            line-height: 1.4;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            word-wrap: break-word;
        }

        [data-bs-theme="dark"] .msg-incoming {
            background-color: #212529;
            color: #f8f9fa;
            align-self: flex-start;
            border-bottom-left-radius: 2px;
        }

        [data-bs-theme="dark"] .msg-outgoing {
            background-color: #0d6efd;
            color: #ffffff;
            align-self: flex-end;
            border-bottom-right-radius: 2px;
        }

        [data-bs-theme="light"] .msg-incoming {
            background-color: #e9ecef;
            color: #212529;
            align-self: flex-start;
            border-bottom-left-radius: 2px;
        }

        [data-bs-theme="light"] .msg-outgoing {
            background-color: #0d6efd;
            color: #ffffff;
            align-self: flex-end;
            border-bottom-right-radius: 2px;
        }

        .msg-time {
            font-size: 0.75rem;
            opacity: 0.7;
            text-align: right;
            margin-top: 4px;
        }

        .date-badge {
            background-color: var(--bs-tertiary-bg);
            color: var(--bs-secondary-color);
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            margin: 1rem auto;
            align-self: center;
        }

        .msg-row:hover .msg-delete-btn {
            opacity: 1 !important;
        }

        .msg-delete-btn {
            transition: opacity 0.15s ease-in-out;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-input-area {
            padding: 1rem;
            border-top: 1px solid var(--bs-border-color);
            background-color: var(--bs-tertiary-bg);
        }

        /* Responsive UI using Bootstrap grid breakpoints */
        @media (max-width: 992px) {
            .panel-accounts {
                width: 80px;
                min-width: 80px;
            }

            .panel-accounts .panel-header h5,
            .panel-accounts .panel-header button,
            .panel-accounts .list-item .item-details,
            .panel-accounts .list-item .item-badge,
            .panel-accounts .list-item .item-actions {
                display: none !important;
            }

            .panel-accounts .list-item {
                justify-content: center;
                padding: 0.8rem;
            }

            .panel-chats {
                width: 280px;
                min-width: 280px;
            }
        }

        @media (max-width: 768px) {
            .app-layout {
                position: relative;
            }

            .panel {
                position: absolute;
                top: 0;
                left: 0;
                width: 100% !important;
                min-width: 100% !important;
                height: 100%;
                z-index: 1;
                transform: translateX(100%);
            }

            .panel-accounts {
                transform: translateX(0);
                z-index: 2;
            }

            .panel-accounts .panel-header h5,
            .panel-accounts .panel-header button,
            .panel-accounts .list-item .item-details,
            .panel-accounts .list-item .item-badge,
            .panel-accounts .list-item .item-actions {
                display: flex !important;
            }

            .panel-accounts .list-item {
                justify-content: flex-start;
                padding: 0.9rem 1.2rem;
            }

            .panel-chats {
                z-index: 3;
            }

            .panel-conversation {
                z-index: 4;
            }

            .panel.slide-active {
                transform: translateX(0);
            }
        }
    </style>
</head>

<body>

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
                    <li><a class="dropdown-item text-danger" href="#"><i
                                class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- ─── LAYOUT MAIN ─── -->
    <div class="app-layout">

        <!-- ─── PANEL 1: SESSIONS / ACCOUNTS ─── -->
        <div class="panel panel-accounts">
            <div class="panel-header py-3 px-4">
                <h5 class="m-0 fw-bold">Accounts</h5>
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

        <!-- ─── PANEL 2: CHATS ─── -->
        <div class="panel panel-chats">
            <div class="panel-header py-3 px-3">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm border-0 d-md-none"
                        onclick="mobileBack('accounts')">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <h5 class="m-0 fw-bold">Chats</h5>
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

        <!-- ─── PANEL 3: CONVERSATION ─── -->
        <div class="panel panel-conversation">
            <div class="panel-header py-2 px-3 border-bottom d-flex align-items-center justify-content-between"
                id="conversation-header" style="display:none !important;">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-outline-secondary btn-sm border-0 d-md-none" onclick="mobileBack('chats')">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div class="avatar-circle" id="conv-avatar" style="background:#0d6efd;">—</div>
                    <div>
                        <h6 class="mb-0 fw-bold" id="conv-name">—</h6>
                        <span class="text-muted small" id="conv-meta">—</span>
                    </div>
                </div>
                <button class="btn btn-outline-secondary border-0 btn-sm rounded-circle"
                    onclick="loadMessages(window.__activeChatId)">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>

            <!-- Messages area -->
            <div class="chat-area" id="messages-container">
                <div class="m-auto text-center text-muted">
                    <i class="bi bi-send-fill fs-2 opacity-25 mb-3 d-block"></i>
                    <h5>No Active Chat</h5>
                    <p class="small">Select a conversation to reply or view chat log.</p>
                </div>
            </div>

            <!-- Send Message Input Area -->
            <div class="chat-input-area" id="reply-box" style="display:none;">
                <form id="send-message-form" onsubmit="handleSend(event)" class="d-flex gap-2">
                    <input type="text" class="form-control rounded-pill border-0" id="reply-text"
                        placeholder="Type a message..." autocomplete="off">
                    <button type="submit" class="btn btn-primary rounded-circle" id="btn-send-reply"
                        style="width:40px; height:40px; padding:0;">
                        <i class="bi bi-send"></i>
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- ─── MODAL: ADD / EDIT ACCOUNT ─── -->
    <div class="modal fade" id="accountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="accountForm" onsubmit="saveAccount(event)">
                <input type="hidden" id="acc-id">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add WhatsApp Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small">Account / Session Name</label>
                            <input type="text" class="form-control" id="acc-name" required
                                placeholder="e.g. Sales CS 1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">WAHA Session ID</label>
                            <input type="text" class="form-control" id="acc-session" required
                                placeholder="e.g. sales-session">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Phone Number (optional)</label>
                            <input type="text" class="form-control" id="acc-phone" placeholder="e.g. 62838xxx">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">WAHA Base URL</label>
                            <input type="url" class="form-control" id="acc-url"
                                placeholder="e.g. http://localhost:3000">
                            <div class="form-text small opacity-50">Local or remote endpoint of your WhatsApp HTTP API.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">API Key (optional)</label>
                            <input type="text" class="form-control" id="acc-key"
                                placeholder="Enter API auth secret token">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Status</label>
                            <select class="form-select" id="acc-status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveAccount">Save Account</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        window.__activeAccountId = null;
        window.__activeChatId = null;

        // ─── THEME CONTROLLER ───
        function getSavedTheme() {
            return localStorage.getItem('saas-theme') || 'dark';
        }

        function applyTheme(theme) {
            document.documentElement.setAttribute('data-bs-theme', theme);
            const icon = document.getElementById('theme-icon');
            if (theme === 'dark') {
                icon.className = 'bi bi-sun-fill';
            } else {
                icon.className = 'bi bi-moon-stars-fill';
            }
            localStorage.setItem('saas-theme', theme);
        }

        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-bs-theme');
            const target = current === 'dark' ? 'light' : 'dark';
            applyTheme(target);
        }

        // Apply theme immediately
        applyTheme(getSavedTheme());

        // ─── API & AJAX UTILITIES ───
        function headers() {
            return {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            };
        }

        // Helper time formatting
        function formatRelative(dateStr) {
            if (!dateStr) return '';
            const now = new Date();
            const d = new Date(dateStr);
            const diff = Math.floor((now - d) / 1000);
            if (diff < 60) return 'now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
            return d.toLocaleDateString();
        }

        function formatHour(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
        }

        const colors = ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545', '#fd7e14', '#198754', '#20c997'];

        function getAvatarColor(str) {
            let hash = 0;
            for (let i = 0; i < (str || '').length; i++) {
                hash = str.charCodeAt(i) + ((hash << 5) - hash);
            }
            return colors[Math.abs(hash) % colors.length];
        }

        function isMobile() {
            return window.innerWidth <= 768;
        }

        function mobileBack(panel) {
            if (panel === 'accounts') {
                document.querySelector('.panel-chats').classList.remove('slide-active');
            } else if (panel === 'chats') {
                document.querySelector('.panel-conversation').classList.remove('slide-active');
            }
        }

        // ─── LOAD ACCOUNTS ───
        async function loadAccounts() {
            try {
                const res = await fetch('/api/accounts');
                const accounts = await res.json();

                const container = document.getElementById('accounts-list');
                if (accounts.length === 0) {
                    container.innerHTML = `
                        <div class="text-center p-5 text-muted">
                            <i class="bi bi-cloud-slash fs-2 mb-2 d-block"></i>
                            <p class="small">No active accounts/sessions. Click 'Add' to config.</p>
                        </div>`;
                    return;
                }

                container.innerHTML = accounts.map(acc => `
                    <div class="list-item ${window.__activeAccountId == acc.id ? 'active' : ''}" onclick="selectAccount(${acc.id}, this)">
                        <div class="avatar-circle bg-secondary-subtle text-secondary-emphasis" style="width:36px; height:36px; font-size: 0.9rem;">
                            ${getInitials(acc.name)}
                        </div>
                        <div class="item-details flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold text-truncate small">${acc.name}</span>
                                <span class="badge ${acc.status === 'active' ? 'bg-success' : 'bg-danger'} rounded-circle p-1" style="width:7px; height:7px;"></span>
                            </div>
                            <div class="text-muted text-truncate" style="font-size:0.75rem;">Session: ${acc.waha_session_id}</div>
                        </div>
                        <div class="item-actions d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary border-0 p-1 btn-sm" onclick="event.stopPropagation(); editAccount(${JSON.stringify(acc).replace(/"/g, '&quot;')})">
                                <i class="bi bi-pencil-square" style="font-size: 0.8rem;"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger border-0 p-1 btn-sm" onclick="event.stopPropagation(); deleteAccount(${acc.id})">
                                <i class="bi bi-trash" style="font-size: 0.8rem;"></i>
                            </button>
                        </div>
                    </div>
                `).join('');

            } catch (err) {
                console.error(err);
            }
        }

        // ─── ADD / EDIT ACCOUNT MODAL ACTIONS ───
        const accModal = new bootstrap.Modal(document.getElementById('accountModal'));

        function openAddAccountModal() {
            document.getElementById('accountForm').reset();
            document.getElementById('acc-id').value = '';
            document.getElementById('modalTitle').textContent = 'Add WhatsApp Account';
            accModal.show();
        }

        function editAccount(acc) {
            document.getElementById('acc-id').value = acc.id;
            document.getElementById('acc-name').value = acc.name;
            document.getElementById('acc-session').value = acc.waha_session_id;
            document.getElementById('acc-phone').value = acc.phone_number || '';
            document.getElementById('acc-url').value = acc.base_url || '';
            document.getElementById('acc-key').value = acc.api_key || '';
            document.getElementById('acc-status').value = acc.status;
            document.getElementById('modalTitle').textContent = 'Modify Account';
            accModal.show();
        }

        async function saveAccount(e) {
            e.preventDefault();
            const id = document.getElementById('acc-id').value;
            const payload = {
                name: document.getElementById('acc-name').value,
                waha_session_id: document.getElementById('acc-session').value,
                phone_number: document.getElementById('acc-phone').value,
                base_url: document.getElementById('acc-url').value,
                api_key: document.getElementById('acc-key').value,
                status: document.getElementById('acc-status').value
            };

            const url = id ? `/api/accounts/${id}` : '/api/accounts';
            const method = id ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: headers(),
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.status === 'success') {
                    accModal.hide();
                    loadAccounts();
                } else {
                    alert('Error: ' + JSON.stringify(data));
                }
            } catch (err) {
                alert('Save failed: ' + err.message);
            }
        }

        async function deleteAccount(id) {
            if (!confirm(
                    'Are you sure you want to delete this session config? This matches no messages but deletes its reference from dashboard.'
                )) return;
            try {
                const res = await fetch(`/api/accounts/${id}`, {
                    method: 'DELETE',
                    headers: headers()
                });
                const data = await res.json();
                if (data.status === 'success') {
                    if (window.__activeAccountId == id) {
                        window.__activeAccountId = null;
                        document.getElementById('chats-list').innerHTML =
                            `<div class="text-center p-5 text-muted"><p class="small">Session deleted.</p></div>`;
                    }
                    loadAccounts();
                }
            } catch (err) {
                alert('Deletion failed');
            }
        }

        async function deleteChat(id) {
            if (!confirm('Are you sure you want to permanently delete this chat history? This cannot be undone.'))
                return;
            try {
                const res = await fetch(`/api/chats/${id}`, {
                    method: 'DELETE',
                    headers: headers()
                });
                const data = await res.json();
                if (data.status === 'success') {
                    if (window.__activeChatId == id) {
                        window.__activeChatId = null;
                        document.getElementById('conversation-header').setAttribute('style',
                            'display: none !important');
                        document.getElementById('reply-box').style.display = 'none';
                        document.getElementById('messages-container').innerHTML = `
                            <div class="m-auto text-center text-muted">
                                <i class="bi bi-chat-left-dots fs-2 opacity-25 mb-3 d-block"></i>
                                <h5>No Chat Selected</h5>
                                <p class="small">Choose a contact to open conversation.</p>
                            </div>`;
                    }
                    loadChats(window.__activeAccountId);
                }
            } catch (err) {
                alert('Chat deletion failed');
            }
        }

        async function deleteMessage(id) {
            if (!confirm('Are you sure you want to delete this message?')) return;
            try {
                const res = await fetch(`/api/messages/${id}`, {
                    method: 'DELETE',
                    headers: headers()
                });
                const data = await res.json();
                if (data.status === 'success') {
                    // Silence reload to keep focus
                    fetch(`/api/chats/${window.__activeChatId}/messages`)
                        .then(r => r.json())
                        .then(messagesData => {
                            const container = document.getElementById('messages-container');
                            const {
                                messages
                            } = messagesData;

                            if (messages.length === 0) {
                                container.innerHTML = `
                                    <div class="m-auto text-center text-muted">
                                        <i class="bi bi-chat-heart fs-3 opacity-25"></i>
                                        <p class="small mt-2">Chat log empty.</p>
                                    </div>`;
                                return;
                            }

                            let html = '';
                            let lastDateGroup = null;
                            messages.forEach(msg => {
                                const dateGroup = new Date(msg.created_at).toDateString();
                                if (dateGroup !== lastDateGroup) {
                                    html +=
                                        `<span class="date-badge">${formatDateHeader(msg.created_at)}</span>`;
                                    lastDateGroup = dateGroup;
                                }

                                const isOut = msg.type === 'out';
                                html += `
                                    <div class="d-flex align-items-center gap-2 ${isOut ? 'justify-content-end' : 'justify-content-start'} msg-row">
                                        ${isOut ? `
                                                                                <button type="button" class="btn btn-link text-danger p-0 border-0 msg-delete-btn opacity-0" title="Delete Message" onclick="deleteMessage(${msg.id})">
                                                                                    <i class="bi bi-trash" style="font-size:0.75rem;"></i>
                                                                                </button>
                                                                            ` : ''}
                                        <div class="msg-bubble ${isOut ? 'msg-outgoing' : 'msg-incoming'}">
                                            ${renderMessageBody(msg)}
                                            <div class="msg-time">${formatHour(msg.created_at)}</div>
                                        </div>
                                        ${!isOut ? `
                                                                                <button type="button" class="btn btn-link text-danger p-0 border-0 msg-delete-btn opacity-0" title="Delete Message" onclick="deleteMessage(${msg.id})">
                                                                                    <i class="bi bi-trash" style="font-size:0.75rem;"></i>
                                                                                </button>
                                                                            ` : ''}
                                    </div>`;
                            });
                            container.innerHTML = html;
                        });
                    loadChats(window.__activeAccountId);
                }
            } catch (err) {
                alert('Message deletion failed');
            }
        }

        // ─── CHATS & MESSAGES ───
        function selectAccount(accountId, el) {
            window.__activeAccountId = accountId;
            window.__activeChatId = null;

            document.querySelectorAll('.panel-accounts .list-item').forEach(i => i.classList.remove('active'));
            el?.classList.add('active');

            if (isMobile()) {
                document.querySelector('.panel-chats').classList.add('slide-active');
            }

            document.getElementById('conversation-header').setAttribute('style', 'display: none !important');
            document.getElementById('reply-box').style.display = 'none';
            document.getElementById('messages-container').innerHTML = `
                <div class="m-auto text-center text-muted">
                    <i class="bi bi-chat-left-dots fs-2 opacity-25 mb-3 d-block"></i>
                    <h5>No Chat Selected</h5>
                    <p class="small">Choose a contact to open conversation.</p>
                </div>`;

            loadChats(accountId);
        }

        async function loadChats(accountId) {
            if (!accountId) return;
            try {
                const res = await fetch(`/api/accounts/${accountId}/chats`);
                const chats = await res.json();

                document.getElementById('chat-count').textContent = chats.length;
                const container = document.getElementById('chats-list');

                if (chats.length === 0) {
                    container.innerHTML = `
                        <div class="text-center p-5 text-muted">
                            <i class="bi bi-chat-quote fs-2 opacity-50 mb-2 d-block"></i>
                            <p class="small">No conversations recorded for this session yet.</p>
                        </div>`;
                    return;
                }

                container.innerHTML = chats.map(chat => `
                    <div class="list-item ${window.__activeChatId == chat.id ? 'active' : ''}" data-chat-id="${chat.chat_id}" onclick="selectChat(${chat.id}, this)">
                        <div class="avatar-circle" style="background: ${getAvatarColor(chat.user_name)}; width:38px; height:38px; font-size:0.95rem;">
                            ${getInitials(chat.user_name)}
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold text-truncate small">${chat.user_name}</span>
                                <span class="text-muted" style="font-size:0.7rem;">${formatRelative(chat.updated_at)}</span>
                            </div>
                            <div class="text-muted text-truncate" style="font-size:0.75rem;">${chat.last_message || '[No message text]'}</div>
                        </div>
                        <div class="item-actions ms-2">
                            <button type="button" class="btn btn-outline-danger border-0 p-1 btn-sm" title="Delete Chat" onclick="event.stopPropagation(); deleteChat(${chat.id})">
                                <i class="bi bi-trash" style="font-size: 0.8rem;"></i>
                            </button>
                        </div>
                    </div>
                `).join('');

            } catch (err) {
                console.error(err);
            }
        }

        function selectChat(chatId, el) {
            window.__activeChatId = chatId;
            document.querySelectorAll('.panel-chats .list-item').forEach(i => i.classList.remove('active'));
            el?.classList.add('active');

            if (isMobile()) {
                document.querySelector('.panel-conversation').classList.add('slide-active');
            }

            loadMessages(chatId);
        }

        async function loadMessages(chatId) {
            if (!chatId) return;

            const header = document.getElementById('conversation-header');
            header.style.display = 'flex';
            header.removeAttribute('style'); // force showing it

            const container = document.getElementById('messages-container');
            container.innerHTML = `
                <div class="m-auto text-center text-muted">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>`;

            try {
                const res = await fetch(`/api/chats/${chatId}/messages`);
                const data = await res.json();
                const {
                    chat,
                    messages
                } = data;

                // Update Header
                document.getElementById('conv-avatar').textContent = getInitials(chat.user_name);
                document.getElementById('conv-avatar').style.backgroundColor = getAvatarColor(chat.user_name);
                document.getElementById('conv-name').textContent = chat.user_name;
                document.getElementById('conv-meta').textContent = chat.chat_id;

                // Open reply box
                document.getElementById('reply-box').style.display = 'block';

                if (messages.length === 0) {
                    container.innerHTML = `
                        <div class="m-auto text-center text-muted">
                            <i class="bi bi-chat-heart fs-3 opacity-25"></i>
                            <p class="small mt-2">Chat log empty.</p>
                        </div>`;
                    return;
                }

                let html = '';
                let lastDateGroup = null;

                messages.forEach(msg => {
                    const dateGroup = new Date(msg.created_at).toDateString();
                    if (dateGroup !== lastDateGroup) {
                        html += `<span class="date-badge">${formatDateHeader(msg.created_at)}</span>`;
                        lastDateGroup = dateGroup;
                    }

                    const isOut = msg.type === 'out';
                    html += `
                        <div class="d-flex align-items-center gap-2 ${isOut ? 'justify-content-end' : 'justify-content-start'} msg-row">
                            ${isOut ? `
                                                                    <button type="button" class="btn btn-link text-danger p-0 border-0 msg-delete-btn opacity-0" title="Delete Message" onclick="deleteMessage(${msg.id})">
                                                                        <i class="bi bi-trash" style="font-size:0.75rem;"></i>
                                                                    </button>
                                                                ` : ''}
                            <div class="msg-bubble ${isOut ? 'msg-outgoing' : 'msg-incoming'}">
                                ${renderMessageBody(msg)}
                                <div class="msg-time">${formatHour(msg.created_at)}</div>
                            </div>
                            ${!isOut ? `
                                                                    <button type="button" class="btn btn-link text-danger p-0 border-0 msg-delete-btn opacity-0" title="Delete Message" onclick="deleteMessage(${msg.id})">
                                                                        <i class="bi bi-trash" style="font-size:0.75rem;"></i>
                                                                    </button>
                                                                ` : ''}
                        </div>`;
                });

                container.innerHTML = html;

                // Scroll to the newest message (bottom)
                setTimeout(() => {
                    container.scrollTop = container.scrollHeight;
                }, 50);

            } catch (err) {
                container.innerHTML = `
                    <div class="m-auto text-danger text-center">
                        <i class="bi bi-exclamation-triangle fs-3"></i>
                        <p class="small mt-2">Fetch Error: ${err.message}</p>
                    </div>`;
            }
        }

        // Send Text message via SaaS route endpoint
        async function handleSend(e) {
            e.preventDefault();
            const input = document.getElementById('reply-text');
            const text = input.value.trim();
            if (!text || !window.__activeChatId) return;

            const btn = document.getElementById('btn-send-reply');
            const originalIcon = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            btn.disabled = true;

            try {
                const res = await fetch(`/api/chats/${window.__activeChatId}/send`, {
                    method: 'POST',
                    headers: headers(),
                    body: JSON.stringify({
                        text: text
                    })
                });

                const data = await res.json();
                if (data.status === 'success') {
                    input.value = '';
                    loadMessages(window.__activeChatId);
                    // Reload chat list previews
                    loadChats(window.__activeAccountId);
                } else {
                    alert('Sending error (check console or configuration of base URL): ' + (data.message ||
                        'API failed'));
                }
            } catch (err) {
                alert('Send Request Fail: ' + err.message);
            } finally {
                btn.innerHTML = originalIcon;
                btn.disabled = false;
            }
        }

        function formatDateHeader(dateStr) {
            const d = new Date(dateStr);
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(today.getDate() - 1);

            if (d.toDateString() === today.toDateString()) return 'Today';
            if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
            return d.toLocaleDateString([], {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        }

        function renderMessageBody(msg) {
            let bodyText = msg.body || '';
            let raw = msg.raw_data;
            if (typeof raw === 'string') {
                try {
                    raw = JSON.parse(raw);
                } catch (e) {
                    raw = null;
                }
            }

            const getProxyUrl = (url) => {
                if (!url) return '';
                return `/api/media?account_id=${window.__activeAccountId}&url=${encodeURIComponent(url)}`;
            };

            if (raw && raw.payload) {
                const payload = raw.payload;
                let type = payload.type || (payload._data && payload._data.type) || null;
                if (!type) {
                    if (payload.location) {
                        type = 'location';
                    } else if (payload.hasMedia) {
                        type = 'image';
                    } else {
                        type = 'chat';
                    }
                }

                // Handle Image media URL
                if (type === 'image') {
                    const mediaUrl = payload.media ? getProxyUrl(payload.media.url) : null;
                    const caption = payload.caption || payload.body || '';
                    const displayCaption = (caption && !caption.startsWith('/9j/') && caption.length < 500) ? caption : '';
                    if (mediaUrl) {
                        return `
                            <div class="mb-2 text-center" style="max-width: 280px;">
                                <img src="${mediaUrl}" class="img-fluid rounded border border-light-subtle shadow-sm my-1" style="max-height: 200px; cursor: pointer; object-fit: cover;" onclick="window.open('${mediaUrl}', '_blank')" alt="Image">
                            </div>
                            ${displayCaption ? `<div class="mt-1">${escapeHtml(displayCaption)}</div>` : ''}
                        `;
                    }
                }

                // Handle Sticker
                if (type === 'sticker') {
                    const mediaUrl = payload.media ? getProxyUrl(payload.media.url) : null;
                    if (mediaUrl) {
                        return `
                            <div class="text-center" style="max-width: 130px; background: transparent;">
                                <img src="${mediaUrl}" class="img-fluid rounded my-1" style="max-height: 130px; object-fit: contain;" alt="Sticker">
                            </div>
                        `;
                    }
                }

                // Handle Document / Files
                if (type === 'document') {
                    const mediaUrl = payload.media ? getProxyUrl(payload.media.url) : null;
                    const fileName = payload.filename || (payload._data && payload._data.filename) || 'Received File';
                    if (mediaUrl) {
                        return `
                            <div class="d-flex align-items-center gap-2 p-2 bg-body-tertiary rounded border border-secondary-subtle" style="max-width: 280px;">
                                <div class="fs-2 text-secondary"><i class="bi bi-file-earmark-arrow-down-fill"></i></div>
                                <div class="min-width-0 flex-grow-1">
                                    <div class="fw-bold small text-body text-truncate">${escapeHtml(fileName)}</div>
                                    <a href="${mediaUrl}" target="_blank" class="btn btn-primary d-inline-block py-0 px-2 btn-sm text-white mt-1" style="font-size: 0.72rem; text-decoration: none;">
                                        Download File
                                    </a>
                                </div>
                            </div>
                        `;
                    }
                }

                // Handle Audio / Voice Memo
                if (type === 'audio' || type === 'voice' || type === 'ptt') {
                    const mediaUrl = payload.media ? getProxyUrl(payload.media.url) : null;
                    if (mediaUrl) {
                        return `
                            <div class="my-1 d-block" style="width: 260px; max-width: 100%;">
                                <audio controls class="w-100" style="height: 36px;">
                                    <source src="${mediaUrl}" type="${payload.media.mimetype || 'audio/ogg'}">
                                    Audio player not supported.
                                </audio>
                            </div>
                        `;
                    }
                }

                // Handle Location map
                if (type === 'location') {
                    const lat = payload.lat || (payload.location && payload.location.latitude);
                    const lng = payload.lng || (payload.location && payload.location.longitude);
                    if (lat && lng) {
                        return `
                            <div class="d-flex align-items-center gap-2 p-2 bg-body-tertiary rounded border border-secondary-subtle" style="max-width: 260px;">
                                <div class="fs-3 text-danger"><i class="bi bi-geo-alt-fill"></i></div>
                                <div>
                                    <div class="fw-bold small text-body">Shared Location</div>
                                    <div class="text-secondary" style="font-size: 0.7rem; margin-bottom: 4px;">Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)}</div>
                                    <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" class="btn btn-primary d-inline-block py-0 px-2 btn-sm text-white" style="font-size: 0.72rem; text-decoration: none;">
                                        Open Map
                                    </a>
                                </div>
                            </div>
                        `;
                    }
                }
            }

            return `<div>${escapeHtml(bodyText)}</div>`;
        }

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Live auto-polling every 12 sec
        setInterval(() => {
            loadAccounts();
            if (window.__activeAccountId) loadChats(window.__activeAccountId);
            if (window.__activeChatId) {
                // To prevent jumping scroll focus if user is looking up,
                // we can reload silently only updating content if scrolled to bottom.
                const container = document.getElementById('messages-container');
                const isAtBottom = container.scrollHeight - container.clientHeight <= container.scrollTop + 30;

                // Fetch and update
                fetch(`/api/chats/${window.__activeChatId}/messages`)
                    .then(res => res.json())
                    .then(data => {
                        const {
                            messages
                        } = data;
                        if (!messages || messages.length === 0) return;

                        let html = '';
                        let lastDateGroup = null;

                        messages.forEach(msg => {
                            const dateGroup = new Date(msg.created_at).toDateString();
                            if (dateGroup !== lastDateGroup) {
                                html +=
                                    `<span class="date-badge">${formatDateHeader(msg.created_at)}</span>`;
                                lastDateGroup = dateGroup;
                            }

                            const isOut = msg.type === 'out';
                            html += `
                                <div class="d-flex align-items-center gap-2 ${isOut ? 'justify-content-end' : 'justify-content-start'} msg-row">
                                    ${isOut ? `
                                                                            <button type="button" class="btn btn-link text-danger p-0 border-0 msg-delete-btn opacity-0" title="Delete Message" onclick="deleteMessage(${msg.id})">
                                                                                <i class="bi bi-trash" style="font-size:0.75rem;"></i>
                                                                            </button>
                                                                        ` : ''}
                                    <div class="msg-bubble ${isOut ? 'msg-outgoing' : 'msg-incoming'}">
                                        ${renderMessageBody(msg)}
                                        <div class="msg-time">${formatHour(msg.created_at)}</div>
                                    </div>
                                    ${!isOut ? `
                                                                            <button type="button" class="btn btn-link text-danger p-0 border-0 msg-delete-btn opacity-0" title="Delete Message" onclick="deleteMessage(${msg.id})">
                                                                                <i class="bi bi-trash" style="font-size:0.75rem;"></i>
                                                                            </button>
                                                                        ` : ''}
                                </div>`;
                        });

                        container.innerHTML = html;
                        if (isAtBottom) {
                            container.scrollTop = container.scrollHeight;
                        }
                    }).catch(err => {});
            }
        }, 12000);

        // Core Init
        loadAccounts();
    </script>
</body>

</html>

<!-- ─── MODAL: CREATE NEW CHAT ─── -->
<div class="modal fade" id="createChatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small">WhatsApp Number / Chat ID</label>
                    <input type="text" class="form-control" id="chat-id" required
                        placeholder="e.g. 6281234567890 or 6281234567890@c.us">
                </div>
                <div class="mb-3">
                    <label class="form-label small">Pesan / Initial Message</label>
                    <textarea class="form-control" id="chat-message" rows="3" required
                        placeholder="Tulis pesan yang ingin dikirim..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveNewChat()">Kirim & Buat Chat</button>
            </div>
        </div>
    </div>
</div>

<script>
    // ─── CREATE NEW CHAT MODAL ACTIONS ───
    let activeAccountIdForChat = null;
    const createChatModal = new bootstrap.Modal(document.getElementById('createChatModal'));

    function openCreateChatModal() {
        if (!window.__activeAccountId) {
            alert('Please select an account first from the left panel.');
            return;
        }
        activeAccountIdForChat = window.__activeAccountId;
        document.getElementById('chat-id').value = '';
        document.getElementById('chat-message').value = '';
        createChatModal.show();
    }

    async function saveNewChat() {
        const chatId = document.getElementById('chat-id').value.trim();
        const message = document.getElementById('chat-message').value.trim();

        if (!chatId || !message) {
            alert('Silakan isi Nomor WhatsApp dan Pesan.');
            return;
        }

        if (!activeAccountIdForChat) {
            alert('No account selected.');
            return;
        }

        const btn = document.querySelector('#createChatModal .btn-primary');
        const originalText = btn.textContent;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Mengirim...';
        btn.disabled = true;

        try {
            const res = await fetch(`/api/accounts/${activeAccountIdForChat}/chats`, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify({
                    chat_id: chatId,
                    message: message
                })
            });

            const data = await res.json();
            if (data.status === 'success') {
                createChatModal.hide();
                // Wait 1.2s for Webhook to catch & insert chat row in DB
                setTimeout(async () => {
                    await loadChats(activeAccountIdForChat);
                    // Select-click the newly sent chat
                    const chatItem = document.querySelector(
                        `#chats-list [data-chat-id="${data.chat_id}"]`);
                    if (chatItem) {
                        chatItem.click();
                    }
                }, 1200);
            } else {
                alert('Error: ' + (data.message || 'Gagal membuat chat'));
            }
        } catch (err) {
            alert('Create chat failed: ' + err.message);
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    }
</script>
