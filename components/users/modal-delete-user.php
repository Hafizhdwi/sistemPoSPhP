<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold">🗑️ Hapus User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="process_user.php" method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteUserId">
                    <div class="text-center mb-3"><div style="font-size:4rem;">⚠️</div></div>
                    <div class="alert alert-danger border-0 text-center"><strong>Hapus user <span id="deleteUserName"></span>?</strong></div>
                    <p class="text-muted small text-center">Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                        <label class="form-check-label small" for="confirmDelete">Saya yakin ingin menghapus user ini</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="deleteForm" class="btn btn-danger px-4 fw-bold" id="btnDelete" disabled>🗑️ Hapus User</button>
            </div>
        </div>
    </div>
</div>