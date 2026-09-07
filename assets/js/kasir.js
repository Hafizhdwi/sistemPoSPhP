/* ============================================
   KASIR PAGE - ALL JAVASCRIPT
   Config di-inject via window.KASIR_CONFIG
   ============================================ */

function toggleSidebar() {
  document.getElementById("sidebar").classList.toggle("show");
  document.getElementById("sidebarOverlay").classList.toggle("show");
}

function toggleUserDropdown(event) {
  event.stopPropagation();
  const wrapper = document.getElementById("userWrapper");
  if (wrapper) wrapper.classList.toggle("open");
}

document.addEventListener("click", function (e) {
  const wrapper = document.getElementById("userWrapper");
  if (wrapper && !wrapper.contains(e.target)) wrapper.classList.remove("open");
});

document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    const wrapper = document.getElementById("userWrapper");
    if (wrapper) wrapper.classList.remove("open");
  }
});

const searchInput = document.getElementById("productSearch");
const searchClear = document.getElementById("searchClear");
const productCols = document.querySelectorAll(".product-col");
const noResults = document.getElementById("noResults");
const productCount = document.getElementById("productCount");

if (searchInput) {
  searchInput.addEventListener("input", function () {
    const query = this.value.toLowerCase().trim();
    let visibleCount = 0;
    searchClear.style.display = query.length > 0 ? "block" : "none";
    productCols.forEach((col) => {
      const match = col.getAttribute("data-name").includes(query);
      col.style.display = match ? "" : "none";
      if (match) visibleCount++;
    });
    noResults.classList.toggle("show", visibleCount === 0 && query.length > 0);
    productCount.textContent = visibleCount + " produk";
  });
}

function clearSearch() {
  searchInput.value = "";
  searchInput.dispatchEvent(new Event("input"));
  searchInput.focus();
}

document.addEventListener("keydown", function (e) {
  if ((e.ctrlKey || e.metaKey) && e.key === "k") {
    e.preventDefault();
    searchInput.focus();
    searchInput.select();
  }
  if (e.key === "Escape" && document.activeElement === searchInput) {
    clearSearch();
    searchInput.blur();
  }
});

function formatCurrency(amount) {
  return "Rp " + Math.round(amount).toLocaleString("id-ID");
}

const MONTHS_ID = [
  "Januari",
  "Februari",
  "Maret",
  "April",
  "Mei",
  "Juni",
  "Juli",
  "Agustus",
  "September",
  "Oktober",
  "November",
  "Desember",
];
const DAYS_ID = [
  "Minggu",
  "Senin",
  "Selasa",
  "Rabu",
  "Kamis",
  "Jumat",
  "Sabtu",
];
let currentCalendarDate = new Date();
let selectedDate = new Date();

function renderCalendar() {
  const year = currentCalendarDate.getFullYear();
  const month = currentCalendarDate.getMonth();
  const config = window.KASIR_CONFIG || {};
  const calData = config.calendarData || {};

  document.getElementById("calendarMonthYear").textContent =
    MONTHS_ID[month] + " " + year;

  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const daysInPrevMonth = new Date(year, month, 0).getDate();
  const container = document.getElementById("calendarDays");
  container.innerHTML = "";

  const today = new Date();
  const todayStr =
    today.getFullYear() +
    "-" +
    String(today.getMonth() + 1).padStart(2, "0") +
    "-" +
    String(today.getDate()).padStart(2, "0");

  for (let i = firstDay - 1; i >= 0; i--) {
    const div = document.createElement("div");
    div.className = "calendar-day other-month";
    div.textContent = daysInPrevMonth - i;
    container.appendChild(div);
  }

  for (let day = 1; day <= daysInMonth; day++) {
    const dateStr =
      year +
      "-" +
      String(month + 1).padStart(2, "0") +
      "-" +
      String(day).padStart(2, "0");
    const div = document.createElement("div");
    div.className = "calendar-day";
    div.textContent = day;

    const dayData = calData[dateStr];
    if (dayData && dayData.count > 0) {
      div.classList.add("has-transactions");
      if (dayData.count > 5) div.classList.add("has-many-transactions");
    }
    if (dateStr === todayStr) div.classList.add("today");

    const selStr =
      selectedDate.getFullYear() +
      "-" +
      String(selectedDate.getMonth() + 1).padStart(2, "0") +
      "-" +
      String(selectedDate.getDate()).padStart(2, "0");
    if (dateStr === selStr && dateStr !== todayStr)
      div.classList.add("selected");

    div.onclick = () => selectDate(new Date(year, month, day));
    container.appendChild(div);
  }

  const remaining = (7 - (container.children.length % 7)) % 7;
  for (let i = 1; i <= remaining; i++) {
    const div = document.createElement("div");
    div.className = "calendar-day other-month";
    div.textContent = i;
    container.appendChild(div);
  }
}

function selectDate(date) {
  selectedDate = date;
  renderCalendar();
  const config = window.KASIR_CONFIG || {};
  const calData = config.calendarData || {};
  const dateStr =
    date.getFullYear() +
    "-" +
    String(date.getMonth() + 1).padStart(2, "0") +
    "-" +
    String(date.getDate()).padStart(2, "0");
  const today = new Date();
  const todayStr =
    today.getFullYear() +
    "-" +
    String(today.getMonth() + 1).padStart(2, "0") +
    "-" +
    String(today.getDate()).padStart(2, "0");
  const isToday = dateStr === todayStr;

  document.getElementById("statsDate").textContent = isToday
    ? "Hari Ini"
    : DAYS_ID[date.getDay()] +
      ", " +
      date.getDate() +
      " " +
      MONTHS_ID[date.getMonth()];

  const data = calData[dateStr];
  if (data && data.count > 0) {
    document.getElementById("statsContent").innerHTML = `
            <div class="calendar-stat-row"><div class="calendar-stat-label"><div class="calendar-stat-icon blue">📦</div><span>Total Transaksi</span></div><div class="calendar-stat-value">${data.count}</div></div>
            <div class="calendar-stat-row"><div class="calendar-stat-label"><div class="calendar-stat-icon green">💰</div><span>Pendapatan</span></div><div class="calendar-stat-value">${formatCurrency(data.revenue)}</div></div>
            <div class="calendar-stat-row"><div class="calendar-stat-label"><div class="calendar-stat-icon orange">✅</div><span>Selesai</span></div><div class="calendar-stat-value">${data.completed}</div></div>`;
  } else {
    document.getElementById("statsContent").innerHTML =
      `<div class="calendar-no-data"><div class="icon">📭</div><div>Tidak ada transaksi</div></div>`;
  }
}

function changeMonth(delta) {
  currentCalendarDate.setMonth(currentCalendarDate.getMonth() + delta);
  renderCalendar();
}

if (document.getElementById("calendarDays")) {
  renderCalendar();
  selectDate(new Date());
}

let cart = [];

function addToCart(id, name, price) {
  const existing = cart.find((item) => item.id === id);
  if (existing) existing.qty++;
  else cart.push({ id, name, price, qty: 1 });
  renderCart();
  if (cart.length === 1) {
    const offcanvas = new bootstrap.Offcanvas(
      document.getElementById("cartOffcanvas"),
    );
    offcanvas.show();
  }
}

function updateQty(id, delta) {
  const item = cart.find((i) => i.id === id);
  if (!item) return;
  item.qty += delta;
  if (item.qty <= 0) cart = cart.filter((i) => i.id !== id);
  renderCart();
}

function removeFromCart(id) {
  cart = cart.filter((item) => item.id !== id);
  renderCart();
}

function renderCart() {
  const listContainer = document.getElementById("cart-items-list");
  const summaryContainer = document.getElementById("cart-summary-offcanvas");
  const footer = document.getElementById("cart-footer-offcanvas");
  const floatingBtn = document.getElementById("floatingCartBtn");
  const floatingBadge = document.getElementById("floatingCartBadge");
  const config = window.KASIR_CONFIG || {};

  summaryContainer.innerHTML = "";
  let subtotal = 0;
  const totalItems = cart.reduce((a, b) => a + b.qty, 0);

  if (totalItems > 0) {
    floatingBtn.classList.remove("empty");
    floatingBtn.classList.add("has-items");
    floatingBadge.textContent = totalItems;
    floatingBadge.style.display = "flex";
    setTimeout(() => floatingBtn.classList.remove("has-items"), 500);
  } else {
    floatingBtn.classList.add("empty");
    floatingBtn.classList.remove("has-items");
    floatingBadge.style.display = "none";
  }

  if (cart.length === 0) {
    listContainer.innerHTML = `<div class="cart-empty"><div class="icon">🛒</div><div class="fw-semibold mb-1">Keranjang Kosong</div><div class="small">Klik produk untuk menambahkan</div></div>`;
    document.getElementById("grand-total-offcanvas").innerText = "Rp 0";
    document.getElementById("offcanvas-cart-count").innerText = "0 item";
    document.getElementById("cart-data").value = "[]";
    document.getElementById("btn-checkout").disabled = true;
    footer.style.display = "none";
    return;
  }

  footer.style.display = "block";
  let html = "";
  cart.forEach((item) => {
    const sub = item.price * item.qty;
    subtotal += sub;
    html += `<div class="cart-item">
            <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">${formatCurrency(item.price)} × ${item.qty}</div>
                <div class="cart-item-controls">
                    <div class="cart-qty-btn" onclick="updateQty(${item.id},-1)">−</div>
                    <div class="cart-qty-value">${item.qty}</div>
                    <div class="cart-qty-btn" onclick="updateQty(${item.id},1)">+</div>
                </div>
            </div>
            <div>
                <div class="cart-item-subtotal">${formatCurrency(sub)}</div>
                <button class="cart-item-remove" onclick="removeFromCart(${item.id})"><i class="bi bi-trash"></i></button>
            </div>
        </div>`;
  });
  listContainer.innerHTML = html;

  let taxAmount = 0;
  if (config.taxEnabled && config.taxRate > 0) {
    taxAmount = Math.round((subtotal * config.taxRate) / 100);
    summaryContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-1"><span class="text-muted small">Subtotal</span><span class="small fw-semibold">${formatCurrency(subtotal)}</span></div>
            <div class="d-flex justify-content-between align-items-center mb-3"><span class="text-muted small">${config.taxLabel}</span><span class="small fw-semibold">${formatCurrency(taxAmount)}</span></div>`;
  }

  document.getElementById("grand-total-offcanvas").innerText = formatCurrency(
    subtotal + taxAmount,
  );
  document.getElementById("offcanvas-cart-count").innerText =
    totalItems + " item";
  document.getElementById("cart-data").value = JSON.stringify(cart);
  document.getElementById("btn-checkout").disabled = false;
}

(function () {
  const config = window.KASIR_CONFIG || {};
  if (config.kioskItems && config.kioskItems.length > 0) {
    config.kioskItems.forEach((item) => cart.push(item));
    renderCart();
    setTimeout(() => {
      const offcanvas = new bootstrap.Offcanvas(
        document.getElementById("cartOffcanvas"),
      );
      offcanvas.show();
      setTimeout(
        () => document.querySelector('[name="pay_amount"]')?.focus(),
        400,
      );
    }, 300);
  } else {
    renderCart();
  }
})();

(function () {
  const config = window.KASIR_CONFIG || {};
  let knownPendingIds = config.pendingOrderIds || [];

  setInterval(function () {
    fetch("index.php?ajax_pending=1")
      .then((r) => r.text())
      .then((html) => {
        const doc = new DOMParser().parseFromString(html, "text/html");
        const newPanel = doc.querySelector(".pending-panel");
        const existingPanel = document.querySelector(".pending-panel");
        const newIds = [];
        if (newPanel) {
          newPanel
            .querySelectorAll('a[href*="process_order"]')
            .forEach((link) => {
              newIds.push(parseInt(link.getAttribute("href").split("=")[1]));
            });
        }
        if (newIds.some((id) => !knownPendingIds.includes(id))) {
          playCashierAlert();
          showCashierToast();
        }
        if (newPanel && !existingPanel) {
          const mh = document.querySelector(".mobile-header");
          if (mh) mh.insertAdjacentElement("afterend", newPanel);
        } else if (newPanel && existingPanel) {
          existingPanel.outerHTML = newPanel.outerHTML;
        } else if (!newPanel && existingPanel) {
          existingPanel.remove();
        }
        knownPendingIds = newIds;
      })
      .catch(() => {});
  }, 5000);
})();

function playCashierAlert() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.frequency.value = 600;
    osc.type = "sine";
    gain.gain.setValueAtTime(0.3, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.8);
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.8);
  } catch (e) {}
}

function showCashierToast() {
  let toast = document.getElementById("cashierToast");
  if (!toast) {
    toast = document.createElement("div");
    toast.id = "cashierToast";
    toast.style.cssText =
      "position:fixed;top:20px;right:20px;background:#e63946;color:white;padding:16px 24px;border-radius:12px;font-weight:700;z-index:9999;box-shadow:0 8px 30px rgba(230,57,70,0.4);animation:slideIn 0.3s ease;";
    document.body.appendChild(toast);
  }
  toast.textContent = "🔔 Pesanan kiosk baru masuk!";
  toast.style.display = "block";
  setTimeout(() => {
    toast.style.display = "none";
  }, 4000);
}

(function () {
  const toast = document.getElementById("successToast");
  if (!toast) return;
  const delay = parseInt(toast.dataset.autohide) || 5000;
  let hideTimeout,
    isHidden = false;

  function playSound() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const play = (f, s, d) => {
        const o = ctx.createOscillator(),
          g = ctx.createGain();
        o.connect(g);
        g.connect(ctx.destination);
        o.frequency.value = f;
        o.type = "sine";
        g.gain.setValueAtTime(0.2, ctx.currentTime + s);
        g.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + s + d);
        o.start(ctx.currentTime + s);
        o.stop(ctx.currentTime + s + d);
      };
      play(1200, 0, 0.1);
      play(1600, 0.12, 0.15);
      play(2000, 0.28, 0.2);
    } catch (e) {}
  }

  function confetti() {
    const c = document.createElement("div");
    c.className = "confetti-container";
    document.body.appendChild(c);
    const colors = ["#10b981", "#f59e0b", "#3b82f6", "#ec4899", "#8b5cf6"];
    for (let i = 0; i < 30; i++) {
      const p = document.createElement("div");
      p.className = "confetti";
      const sz = 6 + Math.random() * 6;
      p.style.cssText = `left:${Math.random() * 100}%;width:${sz}px;height:${sz}px;background:${colors[Math.floor(Math.random() * 5)]};border-radius:${Math.random() > 0.5 ? "50%" : "2px"};animation-delay:${Math.random() * 0.5}s;animation-duration:${2 + Math.random() * 1.5}s;`;
      c.appendChild(p);
    }
    setTimeout(() => c.remove(), 4000);
  }

  function hide() {
    if (isHidden) return;
    isHidden = true;
    toast.classList.add("hiding");
    setTimeout(() => toast.remove(), 400);
  }

  window.hideSuccessToast = hide;
  toast.addEventListener("mouseenter", () => clearTimeout(hideTimeout));
  toast.addEventListener("mouseleave", () => {
    hideTimeout = setTimeout(hide, 2000);
  });
  playSound();
  setTimeout(confetti, 300);
  hideTimeout = setTimeout(hide, delay);
})();
