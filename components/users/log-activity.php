<div class="card border-0 shadow-sm mt-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
        <div>
            <span class="fw-bold" style="font-size:0.9rem;">📋 Log Aktivitas</span>
            <small class="text-muted ms-2" style="font-size:0.7rem;">Terbaru → Terlama · <?= $logPerPage ?> per halaman</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border" style="font-size:0.7rem;"><?= $totalLogs ?> total</span>
            <?php if ($logTotalPages > 1): ?><span class="badge bg-primary" style="font-size:0.65rem;">Hal <?= $logPage ?>/<?= $logTotalPages ?></span><?php endif; ?>
            <a href="history.php?tab=users" class="btn btn-sm btn-outline-primary px-2 py-1" style="font-size:0.7rem;">Semua →</a>
        </div>
    </div>

    <?php if ($logTotalPages > 1 && !empty($activityLogs)): ?>
        <?php
        $buildLogUrl = function($p) use ($editUser, $page) {
            $params = ['log_page' => $p];
            if ($page > 1) $params['page'] = $page;
            if ($editUser) $params['edit'] = $editUser['id'];
            return '?' . http_build_query($params);
        };
        ?>
        <div class="d-flex justify-content-center align-items-center gap-1 py-2 px-3 border-bottom" style="background:#f9fafb;">
            <?php if ($logPage > 1): ?>
                <a href="<?= $buildLogUrl(1) ?>" class="log-page-btn"><i class="bi bi-chevron-bar-left"></i></a>
                <a href="<?= $buildLogUrl($logPage-1) ?>" class="log-page-btn"><i class="bi bi-chevron-left"></i></a>
            <?php else: ?>
                <span class="log-page-btn disabled"><i class="bi bi-chevron-bar-left"></i></span>
                <span class="log-page-btn disabled"><i class="bi bi-chevron-left"></i></span>
            <?php endif; ?>
            <?php
            $ls = max(1,$logPage-2); $le = min($logTotalPages,$logPage+2);
            if ($ls > 1) { echo '<a href="'.$buildLogUrl(1).'" class="log-page-btn">1</a>'; if ($ls > 2) echo '<span class="log-page-btn disabled" style="border:none;">…</span>'; }
            for ($i=$ls; $i<=$le; $i++): ?>
                <a href="<?= $buildLogUrl($i) ?>" class="log-page-btn <?= $i===$logPage?'active':'' ?>"><?= $i ?></a>
            <?php endfor;
            if ($le < $logTotalPages-1) echo '<span class="log-page-btn disabled" style="border:none;">…</span>';
            if ($le < $logTotalPages) echo '<a href="'.$buildLogUrl($logTotalPages).'" class="log-page-btn">'.$logTotalPages.'</a>';
            ?>
            <?php if ($logPage < $logTotalPages): ?>
                <a href="<?= $buildLogUrl($logPage+1) ?>" class="log-page-btn"><i class="bi bi-chevron-right"></i></a>
                <a href="<?= $buildLogUrl($logTotalPages) ?>" class="log-page-btn"><i class="bi bi-chevron-bar-right"></i></a>
            <?php else: ?>
                <span class="log-page-btn disabled"><i class="bi bi-chevron-right"></i></span>
                <span class="log-page-btn disabled"><i class="bi bi-chevron-bar-right"></i></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card-body p-0 log-scroll-container" style="max-height: 420px; overflow-y: auto;">
        <?php if (empty($activityLogs)): ?>
            <div class="text-center py-4 text-muted"><div style="font-size:2rem;">📭</div><small class="d-block mt-1" style="font-size:0.75rem;">Belum ada aktivitas</small></div>
        <?php else: ?>
            <?php foreach ($activityLogs as $index => $log): ?>
                <?php
                $action = $log['action']; $bc = 'bg-secondary'; $ic = 'info'; $icon = 'ℹ️'; $label = ucwords(str_replace('_',' ',$action));
                if (str_contains($action,'login_success')||str_contains($action,'created')) { $bc='bg-success'; $ic='success'; $icon='✅'; }
                elseif (str_contains($action,'failed')||str_contains($action,'deleted')) { $bc='bg-danger'; $ic='failed'; $icon='❌'; }
                elseif (str_contains($action,'password')) { $bc='bg-warning text-dark'; $ic='warning'; $icon='🔑'; }
                elseif (str_contains($action,'updated')||str_contains($action,'toggled')) { $bc='bg-info text-dark'; $ic='info'; $icon='✏️'; }
                elseif (str_contains($action,'logout')) { $bc='bg-dark'; $ic='dark'; $icon='🚪'; }
                elseif (str_contains($action,'system')) { $bc='bg-primary'; $ic='primary'; $icon='⚙️'; }
                $bg = ($index%2===0) ? '' : 'style="background:#f9fafb;"';
                ?>
                <div class="activity-item-compact" <?= $bg ?>>
                    <div class="activity-icon-compact <?= $ic ?>"><?= $icon ?></div>
                    <div class="flex-grow-1" style="min-width:0;">
                        <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                            <span class="badge <?= $bc ?>" style="font-size:0.6rem;padding:2px 8px;"><?= htmlspecialchars($label) ?></span>
                            <small class="text-muted" style="font-size:0.65rem;white-space:nowrap;"><?= date('d/m H:i', strtotime($log['created_at'])) ?></small>
                        </div>
                        <div class="fw-semibold text-truncate" style="font-size:0.78rem;color:#374151;"><?= htmlspecialchars($log['description']?:$label) ?></div>
                        <div class="d-flex gap-1 mt-1 flex-wrap">
                            <?php if (!empty($log['full_name'])): ?><span class="badge bg-light text-dark border" style="font-size:0.6rem;padding:1px 6px;">👤 <?= htmlspecialchars($log['full_name']) ?></span><?php endif; ?>
                            <?php if (!empty($log['ip_address'])): ?><span class="badge bg-light text-dark border" style="font-size:0.6rem;padding:1px 6px;">🌐 <?= htmlspecialchars($log['ip_address']) ?></span><?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($activityLogs) && $logTotalPages > 1): ?>
        <div class="card-footer py-2 px-3 text-center border-top" style="background:#f9fafb;">
            <small class="text-muted" style="font-size:0.7rem;">Menampilkan <?= (($logPage-1)*$logPerPage)+1 ?>-<?= min($logPage*$logPerPage,$totalLogs) ?> dari <?= $totalLogs ?> log · Scroll ↓</small>
        </div>
    <?php endif; ?>
</div>