<div class="card border-0 shadow-sm mt-3">
    <div class="card-header">⚡ Aksi Cepat</div>
    <div class="card-body">
        <button class="btn btn-warning w-100 mb-2 py-2 fw-semibold" onclick="openChangePassword(<?= $editUser['id'] ?>, '<?= htmlspecialchars($editUser['username']) ?>')">🔑 Ganti Password</button>
        <?php if ($editUser['id'] != $_SESSION['user_id']): ?>
            <button class="btn btn-outline-info w-100 py-2 fw-semibold" onclick="toggleStatus(<?= $editUser['id'] ?>, <?= $editUser['is_active'] ? 0 : 1 ?>)">
                <?= $editUser['is_active'] ? '🔒 Nonaktifkan User' : '🔓 Aktifkan User' ?>
            </button>
        <?php else: ?>
            <div class="alert alert-info border-0 small mb-0 mt-2">ℹ️ Anda sedang mengedit akun sendiri.</div>
        <?php endif; ?>
    </div>
</div>