<div class="col-12 col-lg-4 col-xl-4">
    <div class="calendar-card">
        <div class="calendar-header">
            <div class="calendar-header-top">
                <button class="calendar-nav-btn" onclick="changeMonth(-1)" title="Bulan Sebelumnya"><i class="bi bi-chevron-left"></i></button>
                <div class="calendar-month-year" id="calendarMonthYear">-</div>
                <button class="calendar-nav-btn" onclick="changeMonth(1)" title="Bulan Berikutnya"><i class="bi bi-chevron-right"></i></button>
            </div>
            <div class="calendar-today-box">
                <div class="today-day" id="todayDay"><?= date('d') ?></div>
                <div class="today-info" id="todayInfo"><?= date('l, F Y') ?></div>
            </div>
        </div>
        <div class="calendar-body">
            <div class="calendar-weekdays">
                <div class="calendar-weekday weekend">Min</div>
                <div class="calendar-weekday">Sen</div>
                <div class="calendar-weekday">Sel</div>
                <div class="calendar-weekday">Rab</div>
                <div class="calendar-weekday">Kam</div>
                <div class="calendar-weekday">Jum</div>
                <div class="calendar-weekday weekend">Sab</div>
            </div>
            <div class="calendar-days" id="calendarDays"></div>
        </div>
        <div class="calendar-stats" id="calendarStats">
            <div class="calendar-stats-header">
                <div class="calendar-stats-title">Statistik</div>
                <div class="calendar-stats-date" id="statsDate">Hari Ini</div>
            </div>
            <div id="statsContent">
                <div class="calendar-stat-row">
                    <div class="calendar-stat-label"><div class="calendar-stat-icon blue">📦</div><span>Total Transaksi</span></div>
                    <div class="calendar-stat-value" id="statTxCount"><?= $todayStats['count'] ?></div>
                </div>
                <div class="calendar-stat-row">
                    <div class="calendar-stat-label"><div class="calendar-stat-icon green">💰</div><span>Pendapatan</span></div>
                    <div class="calendar-stat-value" id="statRevenue"><?= formatRupiah($todayStats['revenue']) ?></div>
                </div>
                <div class="calendar-stat-row">
                    <div class="calendar-stat-label"><div class="calendar-stat-icon orange">✅</div><span>Selesai</span></div>
                    <div class="calendar-stat-value" id="statCompleted"><?= $todayStats['completed'] ?></div>
                </div>
            </div>
        </div>
        <div class="calendar-legend">
            <div class="calendar-legend-item"><div class="calendar-legend-dot today"></div><span>Hari Ini</span></div>
            <div class="calendar-legend-item"><div class="calendar-legend-dot has-tx"></div><span>Ada Transaksi</span></div>
            <div class="calendar-legend-item"><div class="calendar-legend-dot many-tx"></div><span>Ramai (>5)</span></div>
        </div>
    </div>
</div>