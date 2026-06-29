<!-- ─── MODAL: ADD / EDIT ACCOUNT ─── -->
<div class="modal fade" id="accountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="accountForm" onsubmit="saveAccount(event)">
            <input type="hidden" id="acc-id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add WhatsApp Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                        <input type="url" class="form-control" id="acc-url" required
                            placeholder="e.g. http://localhost:3000">
                        <div class="form-text small opacity-50">Base URL location of your WAHA session (Required).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">API Key</label>
                        <input type="text" class="form-control" id="acc-key" required
                            placeholder="Enter API Key/Secret token (Required)">
                        <div class="form-text small opacity-50">API authentication key/token for WAHA (Required).</div>
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
