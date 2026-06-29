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
        <!-- infinite scroll older indicator -->
        <div id="load-older-indicator" class="text-center py-2 text-muted small" style="display:none; width: 100%;">
            <div class="spinner-border spinner-border-sm text-secondary me-1" role="status"></div>
            Memuat pesan lama...
        </div>
        <div id="messages-inner-container" class="d-flex flex-column gap-2">
            <div class="my-5 text-center text-muted">
                <i class="bi bi-send-fill fs-2 opacity-25 mb-3 d-block"></i>
                <h5>No Active Chat</h5>
                <p class="small">Select a conversation to reply or view chat log.</p>
            </div>
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
