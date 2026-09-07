/* ============================================
   KIOSK PAGE - SELF ORDER SYSTEM
   Config di-inject via window.KIOSK_CONFIG
   ============================================ */

let cart = {};
let selectedPaymentMethod = "kasir";

// ==================== CONFIRM MODAL ====================
function showConfirm({ icon, title, message, detail, yesText, yesAction }) {
  document.getElementById("confirmIcon").textContent = icon || "⚠️";
  document.getElementById("confirmTitle").textContent = title || "Konfirmasi";
  document.getElementById("confirmMessage").textContent =
    message || "Apakah Anda yakin?";

  const detailEl = document.getElementById("confirmDetail");
  if (detail) {
    detailEl.innerHTML = detail;
    detailEl.style.display = "block";
  } else {
    detailEl.style.display = "none";
  }

  const yesBtn = document.getElementById("confirmYesBtn");
  yesBtn.textContent = yesText || "Ya";
  yesBtn.onclick = function () {
    hideConfirm();
    if (yesAction) yesAction();
  };

  const overlay = document.getElementById("confirmOverlay");
  overlay.style.display = "flex";
  requestAnimationFrame(() => {
    overlay.classList.add("show");
  });
}

function hideConfirm() {
  const overlay = document.getElementById("confirmOverlay");
  overlay.classList.remove("show");
  setTimeout(() => {
    overlay.style.display = "none";
  }, 250);
}

document
  .getElementById("confirmOverlay")
  ?.addEventListener("click", function (e) {
    if (e.target === this) hideConfirm();
  });

// ==================== HELPERS ====================
function formatRupiah(amount) {
  return amount.toLocaleString("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  });
}

// ==================== PRODUCT & CART ====================
function toggleItem(el) {
  const id = el.dataset.id;
  if (cart[id]) cart[id].qty++;
  else
    cart[id] = {
      name: el.dataset.name,
      price: parseFloat(el.dataset.price),
      qty: 1,
    };
  updateUI();
}

function updateUI() {
  let total = 0,
    totalQty = 0;
  document.querySelectorAll(".kiosk-card").forEach((card) => {
    const id = card.dataset.id;
    const badge = document.getElementById("badge-" + id);
    if (cart[id] && cart[id].qty > 0) {
      card.classList.add("selected");
      badge.textContent = cart[id].qty;
      total += cart[id].price * cart[id].qty;
      totalQty += cart[id].qty;
    } else {
      card.classList.remove("selected");
      badge.textContent = "0";
    }
  });
  document.getElementById("cartTotal").textContent = formatRupiah(total);
  const fc = document.getElementById("floatingCart");
  totalQty > 0 ? fc.classList.add("show") : fc.classList.remove("show");
}

function updateItemQty(id, delta) {
  if (!cart[id]) return;
  cart[id].qty += delta;
  if (cart[id].qty <= 0) delete cart[id];
  updateUI();
  refreshOrderModal();
}

function refreshOrderModal() {
  const container = document.getElementById("orderItems");
  const entries = Object.entries(cart).filter(([id, item]) => item.qty > 0);

  if (entries.length === 0) {
    container.innerHTML = `
            <div class="empty-cart-modal">
                <div class="icon">🛒</div>
                <h6 class="fw-bold">Keranjang kosong</h6>
                <p class="small mb-0">Pilih produk untuk memulai pesanan</p>
            </div>`;
    document.getElementById("modalTotal").textContent = "Rp 0";
    document.getElementById("btnOrder").disabled = true;
    return;
  }

  let total = 0;
  container.innerHTML = "";

  entries.forEach(([id, item]) => {
    const sub = item.price * item.qty;
    total += sub;
    container.innerHTML += `
            <div class="order-item">
                <div class="oi-info">
                    <div class="oi-name">${item.name}</div>
                    <div class="oi-price-unit">${formatRupiah(item.price)} / item</div>
                </div>
                <div class="oi-qty-control">
                    <button class="oi-qty-btn ${item.qty <= 1 ? "delete" : ""}"
                            onclick="updateItemQty('${id}', -1)"
                            title="${item.qty <= 1 ? "Hapus item" : "Kurangi"}">
                        ${item.qty <= 1 ? "🗑" : "−"}
                    </button>
                    <span class="oi-qty">${item.qty}</span>
                    <button class="oi-qty-btn" onclick="updateItemQty('${id}', 1)" title="Tambah">+</button>
                </div>
                <div class="oi-subtotal">${formatRupiah(sub)}</div>
            </div>`;
  });

  document.getElementById("modalTotal").textContent = formatRupiah(total);
  checkOrderBtn();
}

function filterCategory(cat, btn) {
  document
    .querySelectorAll(".cat-btn")
    .forEach((b) => b.classList.remove("active"));
  btn.classList.add("active");
  document.querySelectorAll(".kiosk-card").forEach((card) => {
    card.style.display =
      cat === "all" || card.dataset.category === cat ? "" : "none";
  });
}

function selectPayment(method, el) {
  selectedPaymentMethod = method;
  document.getElementById("selectedPayment").value = method;
  document
    .querySelectorAll(".pay-option")
    .forEach((o) => o.classList.remove("selected"));
  el.classList.add("selected");
  document
    .getElementById("qrisInfo")
    .classList.toggle("show", method === "qris");
  document
    .getElementById("transferInfo")
    .classList.toggle("show", method === "transfer");
}

function openOrderModal() {
  refreshOrderModal();
  document.getElementById("orderModal").classList.add("show");
  checkOrderBtn();
  setTimeout(() => {
    document.getElementById("customerName").focus();
  }, 300);
}

function closeOrderModal(e) {
  if (e.target === document.getElementById("orderModal"))
    document.getElementById("orderModal").classList.remove("show");
}

function cancelOrder() {
  const entries = Object.entries(cart).filter(([id, item]) => item.qty > 0);

  if (entries.length === 0) {
    document.getElementById("orderModal").classList.remove("show");
    return;
  }

  let totalQty = 0,
    totalPrice = 0;
  entries.forEach(([id, item]) => {
    totalQty += item.qty;
    totalPrice += item.price * item.qty;
  });

  const itemList = entries
    .map(([id, item]) => `${item.qty}x ${item.name}`)
    .join("<br>");

  showConfirm({
    icon: "🗑️",
    title: "Batalkan Pesanan?",
    message:
      "Semua item yang sudah Anda pilih akan dihapus dan tidak bisa dikembalikan.",
    detail: `
            <div style="text-align:left; margin-bottom:8px;">${itemList}</div>
            <div style="border-top:1px dashed #fecaca; padding-top:8px; text-align:right;">
                <strong>${totalQty} item</strong> — ${formatRupiah(totalPrice)}
            </div>
        `,
    yesText: "🗑️ Ya, Hapus Semua",
    yesAction: function () {
      resetCart();
      document.getElementById("orderModal").classList.remove("show");
    },
  });
}

function resetCart() {
  cart = {};
  updateUI();
  document.getElementById("customerName").value = "";
  document.getElementById("tableNumber").value = "";
  document.getElementById("customerName").classList.remove("is-invalid");
  document.getElementById("tableNumber").classList.remove("is-invalid");
  selectedPaymentMethod = "kasir";
  document.getElementById("selectedPayment").value = "kasir";
  document
    .querySelectorAll(".pay-option")
    .forEach((o, i) => o.classList.toggle("selected", i === 0));
  document.getElementById("qrisInfo").classList.remove("show");
  document.getElementById("transferInfo").classList.remove("show");
  document.getElementById("btnOrder").disabled = true;
  document.getElementById("btnOrder").textContent = "🚀 KIRIM PESANAN";
}

// ==================== INPUT HANDLING ====================
const customerNameInput = document.getElementById("customerName");
const tableNumberInput = document.getElementById("tableNumber");

if (customerNameInput && tableNumberInput) {
  customerNameInput.addEventListener("input", function () {
    this.classList.remove("is-invalid");
    checkOrderBtn();
  });
  tableNumberInput.addEventListener("input", function () {
    this.classList.remove("is-invalid");
    checkOrderBtn();
  });

  [customerNameInput, tableNumberInput].forEach((input) => {
    input.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        if (this.id === "customerName") tableNumberInput.focus();
        else if (!document.getElementById("btnOrder").disabled) submitOrder();
      }
    });
  });
}

function checkOrderBtn() {
  const name = customerNameInput?.value.trim() || "";
  const table = tableNumberInput?.value.trim() || "";
  const hasItems = Object.keys(cart).some((id) => cart[id].qty > 0);
  document.getElementById("btnOrder").disabled = !(name && table && hasItems);
}

// ==================== SUBMIT ORDER ====================
function submitOrder() {
  const name = customerNameInput.value.trim();
  const table = tableNumberInput.value.trim();

  let hasError = false;
  if (!name) {
    customerNameInput.classList.add("is-invalid");
    customerNameInput.focus();
    hasError = true;
  }
  if (!table) {
    tableNumberInput.classList.add("is-invalid");
    if (!hasError) tableNumberInput.focus();
    hasError = true;
  }
  if (hasError) {
    if (!name) shakeElement(customerNameInput);
    if (!table) shakeElement(tableNumberInput);
    return;
  }

  const cartItems = Object.entries(cart).filter(([id, item]) => item.qty > 0);
  if (cartItems.length === 0) {
    alert("Keranjang kosong!");
    return;
  }

  const btn = document.getElementById("btnOrder");
  btn.disabled = true;
  btn.textContent = "⏳ Mengirim...";

  const formData = new FormData();
  formData.append(
    "cart_data",
    JSON.stringify(
      cartItems.map(([id, item]) => ({
        id: parseInt(id),
        name: item.name,
        price: item.price,
        qty: item.qty,
      })),
    ),
  );
  formData.append("customer_name", name);
  formData.append("table_number", table);
  formData.append("payment_method", selectedPaymentMethod);

  fetch("kiosk_process.php", { method: "POST", body: formData })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        document.getElementById("orderModal").classList.remove("show");
        document.getElementById("queueNumber").textContent = "#" + data.queue;
        const msgs = {
          kasir: "💰 Silakan bayar di kasir",
          qris: "📱 Tunjukkan bukti scan ke kasir",
          transfer: "🏦 Konfirmasi transfer ke kasir",
          ewallet: "💜 Tunjukkan bukti bayar ke kasir",
        };
        document.getElementById("successMessage").textContent =
          msgs[selectedPaymentMethod] || "Silakan bayar di kasir";
        document.getElementById("successScreen").classList.add("show");
        setTimeout(() => {
          resetCart();
          document.getElementById("successScreen").classList.remove("show");
        }, 5000);
      } else {
        alert("❌ " + data.error);
        btn.disabled = false;
        btn.textContent = "🚀 KIRIM PESANAN";
      }
    })
    .catch(() => {
      alert("Terjadi kesalahan!");
      btn.disabled = false;
      btn.textContent = "🚀 KIRIM PESANAN";
    });
}

function shakeElement(el) {
  el.style.animation = "none";
  setTimeout(() => {
    el.style.animation = "shake 0.4s ease";
  }, 10);
}
