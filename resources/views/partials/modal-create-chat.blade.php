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
