/* ============================================
   KITCHEN DISPLAY - ALL JAVASCRIPT
   Config di-inject via window.KITCHEN_CONFIG
   ============================================ */

// ==================== USER DROPDOWN ====================
function toggleKitchenUserDropdown(event) {
  event.stopPropagation();
  const wrapper = document.getElementById("kitchenUserWrapper");
  if (wrapper) wrapper.classList.toggle("open");
}

document.addEventListener("click", function (e) {
  const wrapper = document.getElementById("kitchenUserWrapper");
  if (wrapper && !wrapper.contains(e.target)) wrapper.classList.remove("open");
});

document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    const wrapper = document.getElementById("kitchenUserWrapper");
    if (wrapper) wrapper.classList.remove("open");
  }
});

// ==================== UPDATE STATUS ====================
function updateStatus(orderId, newStatus) {
  const formData = new FormData();
  formData.append("order_id", orderId);
  formData.append("status", newStatus);

  fetch("process_order_status.php", { method: "POST", body: formData })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) location.reload();
      else alert("❌ " + data.error);
    })
    .catch(() => alert("Terjadi kesalahan koneksi!"));
}

// ==================== AUTO-REFRESH POLLING ====================
(function () {
  const config = window.KITCHEN_CONFIG || {};
  let knownOrderIds = config.orderIds || [];

  setInterval(function () {
    fetch("kitchen.php?ajax=1")
      .then((r) => r.text())
      .then((html) => {
        const doc = new DOMParser().parseFromString(html, "text/html");
        const cards = doc.querySelectorAll(".order-card");
        const newIds = Array.from(cards).map((c) =>
          parseInt(c.id.replace("order-", "")),
        );

        if (newIds.some((id) => !knownOrderIds.includes(id))) {
          showToast();
          playNotificationSound();
        }

        const newGrid = doc.getElementById("kitchenGrid");
        if (newGrid) {
          document.getElementById("kitchenGrid").innerHTML = newGrid.innerHTML;
          document.getElementById("orderCount").textContent =
            cards.length + " pesanan aktif";
        }

        knownOrderIds = newIds;
      })
      .catch(() => {});
  }, 5000);
})();

// ==================== NOTIFICATIONS ====================
function showToast() {
  const toast = document.getElementById("toast");
  toast.classList.add("show");
  setTimeout(() => toast.classList.remove("show"), 3000);
}

function playNotificationSound() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.frequency.value = 800;
    osc.type = "sine";
    gain.gain.setValueAtTime(0.3, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.5);
  } catch (e) {}
}
