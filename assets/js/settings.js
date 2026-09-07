/* ============================================
   SETTINGS PAGE - ALL JAVASCRIPT
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

function previewLogo(input) {
  const preview = document.getElementById("logoPreview");
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function removeLogo() {
  if (confirm("Yakin ingin menghapus logo toko?")) {
    document.getElementById("removeLogoInput").value = "1";
    document.getElementById("logoPreview").innerHTML =
      '<div class="placeholder"><i class="bi bi-image"></i>Belum ada logo</div>';
  }
}

function toggleTaxFields() {
  const enabled = document.getElementById("taxEnabled").checked;
  const fields = document.getElementById("taxFields");
  if (enabled) {
    fields.style.opacity = "1";
    fields.style.pointerEvents = "auto";
  } else {
    fields.style.opacity = "0.5";
    fields.style.pointerEvents = "none";
  }
}
