<div class="card border-0 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>📋 Daftar User</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2"><?= $totalUsers ?> user</span>
            <?php if ($totalPages > 1): ?><span class="badge bg-primary px-3 py-2">Halaman <?= $page ?>/<?= $totalPages ?></span><?php endif; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th class="ps-3 ps-md-4">User</th><th>Username</th><th class="text-center">Role</th><th class="text-center">Status</th><th>Dibuat</th><th class="text-center pe-3 pe-md-4">Aksi</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr class="<?= !$u['is_active'] ? 'table-secondary' : '' ?>">
                            <td class="ps-3 ps-md-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:40px;height:40px;border-radius:50%;background:<?= $u['role']==='admin'?'#667eea':'#10b981' ?>;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;"><?= strtoupper(substr($u['full_name'],0,1)) ?></div>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($u['full_name']) ?></div>
                                        <?php if ($u['id']==$_SESSION['user_id']): ?><small class="text-primary">(Anda)</small><?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="font-monospace small"><?= htmlspecialchars($u['username']) ?></td>
                            <td class="text-center"><span class="badge <?= $u['role']==='admin'?'bg-primary':'bg-success' ?>"><?= strtoupper($u['role']) ?></span></td>
                            <td class="text-center"><?= $u['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' ?></td>
                            <td><small class="text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></small></td>
                            <td class="text-center pe-3 pe-md-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="users.php?edit=<?= $u['id'] ?>&page=<?= $page ?>" class="btn btn-outline-primary px-2 px-md-3" title="Edit">✏️</a>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-outline-danger px-2 px-md-3" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name']) ?>')" title="Hapus">🗑️</button>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary px-2 px-md-3" disabled title="Tidak bisa hapus diri sendiri">🗑️</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted"><div style="font-size:3rem;">👥</div><h6 class="fw-bold mt-3">Belum ada user</h6></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination-container border-top">
                <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?><?= $editUser?'&edit='.$editUser['id']:'' ?>" class="page-btn"><i class="bi bi-chevron-left"></i></a><?php else: ?><span class="page-btn disabled"><i class="bi bi-chevron-left"></i></span><?php endif; ?>
                <?php
                $sp = max(1,$page-2); $ep = min($totalPages,$page+2);
                if ($sp > 1) echo '<a href="?page=1" class="page-btn">1</a>';
                if ($sp > 2) echo '<span class="page-info">...</span>';
                for ($i=$sp; $i<=$ep; $i++): ?>
                    <a href="?page=<?= $i ?><?= $editUser?'&edit='.$editUser['id']:'' ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
                <?php endfor;
                if ($ep < $totalPages-1) echo '<span class="page-info">...</span>';
                if ($ep < $totalPages) echo '<a href="?page='.$totalPages.'" class="page-btn">'.$totalPages.'</a>';
                ?>
                <?php if ($page < $totalPages): ?><a href="?page=<?= $page+1 ?><?= $editUser?'&edit='.$editUser['id']:'' ?>" class="page-btn"><i class="bi bi-chevron-right"></i></a><?php else: ?><span class="page-btn disabled"><i class="bi bi-chevron-right"></i></span><?php endif; ?>
                <div class="page-info w-100 text-center mt-2">Menampilkan <?= (($page-1)*$perPage)+1 ?> - <?= min($page*$perPage,$totalUsers) ?> dari <?= $totalUsers ?> user</div>
            </div>
        <?php endif; ?>
    </div>
</div>