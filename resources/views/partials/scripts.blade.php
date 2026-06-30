<script>
    window.__activeAccountId = null;
    window.__activeChatId = null;
    window.__activeMessages = [];
    window.__oldestMessageId = null;
    window.__latestMessageId = null;
    window.__hasMoreMessages = false;
    window.__isLoadingOlderMessages = false;
    let activeAccountIdForChat = null;
    window.__quotedMessageId = null;
    window.__readMessageIds = new Set();

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

    function goBack(panel) {
        if (panel === 'accounts') {
            document.querySelector('.panel-chats').classList.remove('slide-in');
            document.querySelector('.panel-accounts').classList.remove('slide-out');
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
                        <div class="d-flex justify-content-between align-items-center gap-1">
                            <span class="fw-semibold text-truncate small d-block flex-grow-1" style="max-width: 140px;">${acc.name}</span>
                            <span class="badge ${acc.status === 'active' ? 'bg-success' : 'bg-danger'} rounded-circle p-1 flex-shrink-0" style="width:7px; height:7px;"></span>
                        </div>
                        <div class="text-muted text-truncate d-block" style="font-size:0.75rem; max-width: 190px;">Session: ${acc.waha_session_id}</div>
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
                    document.getElementById('chats-list').innerHTML = `
                        <div class="text-center p-5 text-muted"><p class="small">Session deleted.</p></div>`;
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
                    document.getElementById('messages-inner-container').innerHTML = `
                        <div class="my-5 text-center text-muted">
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
                // Remove message from local array representation and render
                window.__activeMessages = window.__activeMessages.filter(m => m.id !== id);

                // Recalculate IDs
                if (window.__activeMessages.length > 0) {
                    window.__latestMessageId = window.__activeMessages[window.__activeMessages.length - 1].id;
                    window.__oldestMessageId = window.__activeMessages[0].id;
                } else {
                    window.__latestMessageId = null;
                    window.__oldestMessageId = null;
                }

                renderMessagesList();
                loadChats(window.__activeAccountId);
            }
        } catch (err) {
            alert('Message deletion failed');
        }
    }

    // ─── CHATS & MESSAGES ───
    function selectAccount(accountId, el) {
        if (window.__activeAccountId == accountId) {
            // Deselect active account
            window.__activeAccountId = null;
            window.__activeChatId = null;
            window.__activeMessages = [];
            window.__latestMessageId = null;
            window.__oldestMessageId = null;
            window.__hasMoreMessages = false;

            document.querySelectorAll('.panel-accounts .list-item').forEach(i => i.classList.remove('active'));

            document.getElementById('chats-list').innerHTML = `
                <div class="text-center p-5 text-muted">
                    <i class="bi bi-phone fs-2 opacity-50 mb-2 d-block"></i>
                    <p class="small">Choose an account / session from the left to view chats.</p>
                </div>`;
            document.getElementById('chat-count').textContent = '0';

            document.getElementById('conversation-header').setAttribute('style', 'display: none !important');
            document.getElementById('reply-box').style.display = 'none';
            document.getElementById('messages-inner-container').innerHTML = `
                <div class="my-5 text-center text-muted">
                    <i class="bi bi-send-fill fs-2 opacity-25 mb-3 d-block"></i>
                    <h5>No Active Chat</h5>
                    <p class="small">Select a conversation to reply or view chat log.</p>
                </div>`;
            cancelQuoteReply();
            return;
        }

        cancelQuoteReply();

        window.__activeAccountId = accountId;
        window.__activeChatId = null;
        window.__activeMessages = [];
        window.__latestMessageId = null;
        window.__oldestMessageId = null;
        window.__hasMoreMessages = false;

        document.querySelectorAll('.panel-accounts .list-item').forEach(i => i.classList.remove('active'));
        el?.classList.add('active');

        // Lakukan slide transition baik di desktop maupun mobile
        document.querySelector('.panel-accounts').classList.add('slide-out');
        document.querySelector('.panel-chats').classList.add('slide-in');

        document.getElementById('conversation-header').setAttribute('style', 'display: none !important');
        document.getElementById('reply-box').style.display = 'none';
        document.getElementById('messages-inner-container').innerHTML = `
            <div class="my-5 text-center text-muted">
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

            container.innerHTML = chats.map(chat => {
                // If it is the currently active chat, automatically mark its last message as read
                if (window.__activeChatId == chat.id && chat.last_message_id) {
                    window.__readMessageIds.add(chat.last_message_id);
                }

                // Determine if chat is unread
                const isUnread = chat.last_message_type === 'in' &&
                    window.__activeChatId != chat.id &&
                    !window.__readMessageIds.has(chat.last_message_id);

                return `
                    <div class="list-item ${window.__activeChatId == chat.id ? 'active' : ''} ${isUnread ? 'unread-chat' : ''}" 
                        data-chat-id="${chat.chat_id}" 
                        onclick="if (${chat.last_message_id}) { window.__readMessageIds.add(${chat.last_message_id}); } selectChat(${chat.id}, this)">
                        <div class="avatar-circle" style="background: ${getAvatarColor(chat.user_name)}; width:38px; height:38px; font-size:0.95rem;">
                            ${getInitials(chat.user_name)}
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between align-items-center gap-1">
                                <span class="chat-name fw-semibold text-truncate small d-block flex-grow-1" style="max-width: 155px;">${chat.user_name}</span>
                                <span class="chat-time text-muted flex-shrink-0" style="font-size:0.7rem;">${formatRelative(chat.updated_at)}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-1">
                                <div class="chat-message text-muted text-truncate d-block" style="font-size:0.75rem; max-width: 200px;">${chat.last_message || '[No message text]'}</div>
                                ${isUnread ? `<span class="unread-dot bg-success rounded-circle flex-shrink-0" style="width: 8px; height: 8px; margin-left: auto;"></span>` : ''}
                            </div>
                        </div>
                        <div class="item-actions ms-2">
                            <button type="button" class="btn btn-outline-danger border-0 p-1 btn-sm" title="Delete Chat" onclick="event.stopPropagation(); deleteChat(${chat.id})">
                                <i class="bi bi-trash" style="font-size: 0.8rem;"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

        } catch (err) {
            console.error(err);
        }
    }

    function selectChat(chatId, el) {
        cancelQuoteReply();
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

        const container = document.getElementById('messages-inner-container');
        container.innerHTML = `
            <div class="my-5 text-center text-muted">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 small">Loading messages...</div>
            </div>`;

        try {
            const res = await fetch(`/api/chats/${chatId}/messages`);
            const data = await res.json();
            const {
                chat,
                messages,
                has_more,
                oldest_id
            } = data;

            // Update Header
            document.getElementById('conv-avatar').textContent = getInitials(chat.user_name);
            document.getElementById('conv-avatar').style.backgroundColor = getAvatarColor(chat.user_name);
            document.getElementById('conv-name').textContent = chat.user_name;
            document.getElementById('conv-meta').textContent = chat.chat_id;

            // Open reply box
            document.getElementById('reply-box').style.display = 'block';

            window.__activeMessages = messages || [];
            window.__hasMoreMessages = has_more || false;
            window.__oldestMessageId = oldest_id || null;

            if (window.__activeMessages.length > 0) {
                window.__latestMessageId = window.__activeMessages[window.__activeMessages.length - 1].id;
            } else {
                window.__latestMessageId = null;
            }

            renderMessagesList();

            // Scroll to the newest message (bottom)
            const chatScrollContainer = document.getElementById('messages-container');
            setTimeout(() => {
                chatScrollContainer.scrollTop = chatScrollContainer.scrollHeight;
            }, 50);

        } catch (err) {
            container.innerHTML = `
                <div class="my-5 text-danger text-center">
                    <i class="bi bi-exclamation-triangle fs-3"></i>
                    <p class="small mt-2">Fetch Error: ${err.message}</p>
                </div>`;
        }
    }

    // Scroll listener for Lazy Loading (Infinite Scroll Up)
    const chatScrollContainer = document.getElementById('messages-container');
    chatScrollContainer.addEventListener('scroll', () => {
        if (chatScrollContainer.scrollTop <= 5 && window.__hasMoreMessages && !window
            .__isLoadingOlderMessages && window.__activeChatId) {
            loadOlderMessages(window.__activeChatId);
        }
    });

    async function loadOlderMessages(chatId) {
        if (!chatId || window.__isLoadingOlderMessages) return;

        window.__isLoadingOlderMessages = true;
        const indicator = document.getElementById('load-older-indicator');
        indicator.style.display = 'block';

        const originalScrollHeight = chatScrollContainer.scrollHeight;

        try {
            const res = await fetch(`/api/chats/${chatId}/messages?before_id=${window.__oldestMessageId}`);
            const data = await res.json();
            const {
                messages,
                has_more,
                oldest_id
            } = data;

            if (messages && messages.length > 0) {
                // Prepend new messages to activeMessages
                window.__activeMessages = [...messages, ...window.__activeMessages];
                window.__hasMoreMessages = has_more;
                window.__oldestMessageId = oldest_id;

                renderMessagesList();

                // Keep scroll position relative to original offset
                setTimeout(() => {
                    chatScrollContainer.scrollTop = chatScrollContainer.scrollHeight - originalScrollHeight;
                }, 10);
            } else {
                window.__hasMoreMessages = false;
            }
        } catch (error) {
            console.error('Failed to load older messages', error);
        } finally {
            indicator.style.display = 'none';
            window.__isLoadingOlderMessages = false;
        }
    }

    function renderMessagesList() {
        const container = document.getElementById('messages-inner-container');
        if (window.__activeMessages.length === 0) {
            container.innerHTML = `
                <div class="my-5 text-center text-muted">
                    <i class="bi bi-chat-heart fs-3 opacity-25"></i>
                    <p class="small mt-2">Chat log empty.</p>
                </div>`;
            return;
        }

        let html = '';
        let lastDateGroup = null;

        window.__activeMessages.forEach(msg => {
            const dateGroup = new Date(msg.created_at).toDateString();
            if (dateGroup !== lastDateGroup) {
                html += `<span class="date-badge">${formatDateHeader(msg.created_at)}</span>`;
                lastDateGroup = dateGroup;
            }

            const isOut = msg.type === 'out';

            // For group chats: show sender name on incoming messages
            let senderLabel = '';
            const convMeta = document.getElementById('conv-meta')?.textContent || '';
            const isGroup = convMeta.includes('@g.us');
            if (isGroup && !isOut) {
                let raw = msg.raw_data;
                if (typeof raw === 'string') {
                    try {
                        raw = JSON.parse(raw);
                    } catch (e) {
                        raw = null;
                    }
                }
                const senderName = raw?.payload?._data?.pushName || raw?.payload?._data?.notifyName ||
                    'Unknown';
                const senderColor = getAvatarColor(senderName);
                senderLabel =
                    `<div class="fw-bold mb-1" style="font-size: 0.72rem; color: ${senderColor};">${escapeHtml(senderName)}</div>`;
            }

            html += `
                <div class="d-flex align-items-center gap-2 ${isOut ? 'justify-content-end' : 'justify-content-start'} msg-row">
                    ${isOut ? `
                        <div class="msg-actions opacity-0">
                            <button type="button" class="btn btn-link text-secondary p-0 border-0" title="Reply Message" onclick="initiateQuoteReply(${msg.id})">
                                <i class="bi bi-reply-fill" style="font-size:0.85rem;"></i>
                            </button>
                            <button type="button" class="btn btn-link text-danger p-0 border-0" title="Delete Message" onclick="deleteMessage(${msg.id})">
                                <i class="bi bi-trash" style="font-size:0.75rem;"></i>
                            </button>
                        </div>
                    ` : ''}
                    <div class="msg-bubble ${isOut ? 'msg-outgoing' : 'msg-incoming'}">
                        ${senderLabel}
                        ${renderMessageBody(msg)}
                        <div class="msg-time">${formatHour(msg.created_at)}</div>
                    </div>
                    ${!isOut ? `
                        <div class="msg-actions opacity-0">
                            <button type="button" class="btn btn-link text-secondary p-0 border-0" title="Reply Message" onclick="initiateQuoteReply(${msg.id})">
                                <i class="bi bi-reply-fill" style="font-size:0.85rem;"></i>
                            </button>
                            <button type="button" class="btn btn-link text-danger p-0 border-0" title="Delete Message" onclick="deleteMessage(${msg.id})">
                                <i class="bi bi-trash" style="font-size:0.75rem;"></i>
                            </button>
                        </div>
                    ` : ''}
                </div>`;
        });

        container.innerHTML = html;

        // Initialize Plyr for video elements if Plyr is loaded
        if (typeof Plyr !== 'undefined') {
            Plyr.setup(container.querySelectorAll('video'), {
                controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'fullscreen']
            });
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

        const payload = {
            text: text
        };
        if (window.__quotedMessageId) {
            payload.reply_to = window.__quotedMessageId;
        }

        try {
            const res = await fetch(`/api/chats/${window.__activeChatId}/send`, {
                method: 'POST',
                headers: headers(),
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (data.status === 'success') {
                input.value = '';
                cancelQuoteReply();
                // Wait for webhook or single fetch if instant representation is needed.
                // We will let the poller capture it, or retrieve immediately to show fast:
                pollNewMessages();
                loadChats(window.__activeAccountId);
            } else {
                alert('Sending error: ' + (data.message || 'API failed'));
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

        let quotedTextHtml = '';
        if (raw) {
            const payload = raw.payload || raw;
            const quotedMsg = payload.replyTo || payload.quotedMsg || (payload._data && (payload._data.replyTo ||
                payload._data.quotedMsg)) || raw.replyTo || raw.quotedMsg || (raw._data && (raw._data.replyTo || raw
                ._data.quotedMsg)) || null;
            if (quotedMsg) {
                const quotedText = quotedMsg.body || '[Media/File]';

                // Determine sender name elegantly
                let quotedSender = quotedMsg.pushName || quotedMsg.notifyName;
                if (!quotedSender) {
                    if (quotedMsg.participant) {
                        const cleanPart = quotedMsg.participant.replace(/@.*$/, '');
                        const currContactMeta = document.getElementById('conv-meta')?.textContent || '';
                        const cleanCurr = currContactMeta.replace(/@.*$/, '');

                        if (cleanPart === cleanCurr) {
                            quotedSender = document.getElementById('conv-name')?.textContent || 'Contact';
                        } else {
                            quotedSender = 'Me';
                        }
                    } else {
                        quotedSender = (quotedMsg.id && typeof quotedMsg.id === 'object' && quotedMsg.id.fromMe) ?
                            'Me' : 'Contact';
                    }
                }

                const isOut = msg.type === 'out';

                if (isOut) {
                    // Outgoing message (blue bubble): use white accents
                    quotedTextHtml = `
                        <div class="quoted-msg-bubble mb-2 py-1 px-2 rounded border-start border-3 text-white" style="background: rgba(255, 255, 255, 0.12); border-color: rgba(255, 255, 255, 0.8) !important; font-size:0.75rem; opacity: 0.95; max-width: 100%;">
                            <div class="fw-bold small mb-0" style="color: rgba(255, 255, 255, 0.95);">${escapeHtml(quotedSender)}</div>
                            <div class="text-truncate small" style="color: rgba(255, 255, 255, 0.75); font-size: 0.72rem;">${escapeHtml(quotedText)}</div>
                        </div>
                    `;
                } else {
                    // Incoming message (gray/black bubble): use blue/mute theme accents
                    quotedTextHtml = `
                        <div class="quoted-msg-bubble mb-2 py-1 px-2 rounded border-start border-primary border-3 bg-secondary-subtle" style="font-size:0.75rem; opacity: 0.95; max-width: 100%;">
                            <div class="fw-bold text-primary small mb-0">${escapeHtml(quotedSender)}</div>
                            <div class="text-truncate text-secondary" style="font-size: 0.72rem;">${escapeHtml(quotedText)}</div>
                        </div>
                    `;
                }
            }
        }

        const getProxyUrl = (url) => {
            if (!url) return '';
            return `/api/media?account_id=${window.__activeAccountId}&url=${encodeURIComponent(url)}`;
        };

        let bodyContent = `<div>${escapeHtml(bodyText)}</div>`;

        if (raw && raw.payload) {
            const payload = raw.payload;
            let type = payload.type || (payload._data && payload._data.type) || null;
            if (payload.media) {
                const mime = payload.media.mimetype || '';
                const url = payload.media.url || '';
                if (mime.startsWith('video/') || url.toLowerCase().includes('.mp4')) {
                    type = 'video';
                } else if (mime.startsWith('image/')) {
                    type = 'image';
                }
            }
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
                    bodyContent = `
                        <div class="mb-2 text-center" style="max-width: 280px;">
                            <img src="${mediaUrl}" class="img-fluid rounded border border-light-subtle shadow-sm my-1 mb-2" style="max-height: 200px; cursor: pointer; object-fit: cover;" onclick="window.openMediaPreview('${mediaUrl}', 'image')" alt="Image">
                            <div class="text-start px-1">
                                <a href="${mediaUrl}" download="image_${Date.now()}.jpg" class="btn btn-outline-secondary btn-sm py-0 px-2 fw-semibold" style="font-size: 0.72rem; text-decoration: none;">
                                    <i class="bi bi-download me-1"></i> Unduh Gambar
                                </a>
                            </div>
                        </div>
                        ${displayCaption ? `<div class="mt-1">${escapeHtml(displayCaption)}</div>` : ''}
                    `;
                }
            }

            // Handle Video media URL
            if (type === 'video') {
                const mediaUrl = payload.media ? getProxyUrl(payload.media.url) : null;
                const caption = payload.caption || payload.body || '';
                const displayCaption = (caption && !caption.startsWith('/9j/') && caption.length < 500) ? caption : '';
                if (mediaUrl) {
                    bodyContent = `
                        <div class="mb-2 text-center" style="max-width: 280px; width: 280px;">
                            <video controls class="img-fluid rounded border border-light-subtle shadow-sm my-1 w-100 mb-2" style="max-height: 220px; object-fit: cover;">
                                <source src="${mediaUrl}" type="${payload.media.mimetype || 'video/mp4'}">
                                Browser Anda tidak mendukung pemutar video.
                            </video>
                            <div class="text-start px-1 d-flex gap-2">
                                <button type="button" onclick="window.openMediaPreview('${mediaUrl}', 'video', '${payload.media.mimetype || 'video/mp4'}')" class="btn btn-outline-secondary btn-sm py-0 px-2 fw-semibold" style="font-size: 0.72rem;">
                                    <i class="bi bi-arrows-angle-expand me-1"></i> Perbesar
                                </button>
                                <a href="${mediaUrl}" download="video_${Date.now()}.mp4" class="btn btn-outline-secondary btn-sm py-0 px-2 fw-semibold" style="font-size: 0.72rem; text-decoration: none;">
                                    <i class="bi bi-download me-1"></i> Unduh Video
                                </a>
                            </div>
                        </div>
                        ${displayCaption ? `<div class="mt-1">${escapeHtml(displayCaption)}</div>` : ''}
                    `;
                }
            }

            // Handle Sticker
            if (type === 'sticker') {
                const mediaUrl = payload.media ? getProxyUrl(payload.media.url) : null;
                if (mediaUrl) {
                    bodyContent = `
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
                    bodyContent = `
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
                    bodyContent = `
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
                const lat = parseFloat(payload.lat || (payload.location && payload.location.latitude));
                const lng = parseFloat(payload.lng || (payload.location && payload.location.longitude));
                if (!isNaN(lat) && !isNaN(lng)) {
                    bodyContent = `
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

        return quotedTextHtml + bodyContent;
    }

    function initiateQuoteReply(msgId) {
        const msg = window.__activeMessages.find(m => m.id === msgId);
        if (!msg) return;

        window.__quotedMessageId = msg.message_id;

        let senderName = 'Contact';
        if (msg.type === 'out') {
            senderName = 'Me';
        } else {
            const chatHeaderName = document.getElementById('conv-name').textContent;
            senderName = chatHeaderName || 'Contact';
        }

        const previewText = msg.body || '[Media/Location]';
        document.getElementById('quote-preview-sender').textContent = `Balas ke: ${senderName}`;
        document.getElementById('quote-preview-text').textContent = previewText;

        const container = document.getElementById('quote-preview-container');
        container.style.setProperty('display', 'flex', 'important');

        document.getElementById('reply-text').focus();
    }

    function cancelQuoteReply() {
        window.__quotedMessageId = null;
        const container = document.getElementById('quote-preview-container');
        container.setAttribute('style', 'display: none !important;');
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Smart polling for new messages - only retrieves message rows created after window.__latestMessageId
    async function pollNewMessages() {
        if (!window.__activeChatId) return;

        try {
            let url = `/api/chats/${window.__activeChatId}/messages`;
            if (window.__latestMessageId) {
                url += `?after_id=${window.__latestMessageId}`;
            }

            const res = await fetch(url);
            const data = await res.json();
            const {
                messages
            } = data;

            if (messages && messages.length > 0) {
                // Determine scrolling context to handle auto-scrolling
                const isAtBottom = chatScrollContainer.scrollHeight - chatScrollContainer.clientHeight <=
                    chatScrollContainer.scrollTop + 60;

                // Append newly fetched messages
                window.__activeMessages = [...window.__activeMessages, ...messages];
                window.__latestMessageId = window.__activeMessages[window.__activeMessages.length - 1].id;

                renderMessagesList();

                if (isAtBottom) {
                    chatScrollContainer.scrollTop = chatScrollContainer.scrollHeight;
                }
            }
        } catch (error) {
            console.error('Polling error', error);
        }
    }

    // Live auto-polling every 6 sec (fast and efficient now due to delta load)
    setInterval(() => {
        loadAccounts();
        if (window.__activeAccountId) loadChats(window.__activeAccountId);
        if (window.__activeChatId) {
            pollNewMessages();
        }
    }, 6000);

    // ─── CREATE NEW CHAT MODAL ACTIONS ───
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
                // Refresh chats list and select the new chat immediately without waiting
                await loadChats(activeAccountIdForChat);
                if (data.chat && data.chat.id) {
                    selectChat(data.chat.id, null);
                }
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

    let previewModalInstance = null;
    let previewPlyrInstance = null;

    window.openMediaPreview = function(url, type, mimeType) {
        if (!previewModalInstance) {
            previewModalInstance = new bootstrap.Modal(document.getElementById('mediaPreviewModal'));

            // Cleanup resources when modal is closed
            document.getElementById('mediaPreviewModal').addEventListener('hidden.bs.modal', function() {
                const vid = document.getElementById('preview-video');
                vid.pause();
                vid.src = "";
                vid.load();
                if (previewPlyrInstance) {
                    previewPlyrInstance.destroy();
                    previewPlyrInstance = null;
                }
            });
        }

        const imgEl = document.getElementById('preview-image');
        const videoContainer = document.getElementById('preview-video-container');
        const videoEl = document.getElementById('preview-video');

        imgEl.classList.add('d-none');
        videoContainer.classList.add('d-none');

        const downloadBtn = document.getElementById('preview-download-btn');
        if (downloadBtn) {
            downloadBtn.href = url;
            const ext = type === 'video' ? 'mp4' : 'jpg';
            downloadBtn.download = `media_${Date.now()}.${ext}`;
        }

        if (type === 'image') {
            imgEl.src = url;
            imgEl.classList.remove('d-none');
        } else if (type === 'video') {
            videoEl.src = url;
            if (mimeType) {
                videoEl.querySelectorAll('source')[0].type = mimeType;
            }
            videoContainer.classList.remove('d-none');
            videoEl.load();

            if (typeof Plyr !== 'undefined') {
                previewPlyrInstance = new Plyr(videoEl, {
                    controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume',
                        'fullscreen'
                    ]
                });
            }
        }

        previewModalInstance.show();
    };

    // Core Init
    loadAccounts();
</script>
