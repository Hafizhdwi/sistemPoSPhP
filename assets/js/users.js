/* ============================================
   USERS PAGE - ALL JAVASCRIPT
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

function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon = btn.querySelector("i");
  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("bi-eye");
    icon.classList.add("bi-eye-slash");
  } else {
    input.type = "password";
    icon.classList.remove("bi-eye-slash");
    icon.classList.add("bi-eye");
  }
}

function checkPasswordStrength(password, barId, hintId) {
  const bar = document.getElementById(barId);
  const hint = document.getElementById(hintId);
  if (!bar) return;
  bar.className = "password-strength-bar";
  if (password.length === 0) {
    if (hint) {
      hint.textContent = "Gunakan kombinasi huruf & angka";
      hint.className = "text-muted small";
    }
    return;
  }
  let s = 0;
  if (password.length >= 6) s++;
  if (password.length >= 8) s++;
  if (/[A-Z]/.test(password)) s++;
  if (/[0-9]/.test(password)) s++;
  if (/[^A-Za-z0-9]/.test(password)) s++;
  if (s <= 2) {
    bar.classList.add("strength-weak");
    if (hint) {
      hint.textContent = "🔴 Lemah";
      hint.className = "text-danger small";
    }
  } else if (s <= 3) {
    bar.classList.add("strength-medium");
    if (hint) {
      hint.textContent = "🟡 Sedang";
      hint.className = "text-warning small";
    }
  } else {
    bar.classList.add("strength-strong");
    if (hint) {
      hint.textContent = "🟢 Kuat";
      hint.className = "text-success small";
    }
  }
}

document.getElementById("editPassword")?.addEventListener("input", function () {
  checkPasswordStrength(this.value, "editPasswordStrength", "editPasswordHint");
});
document.getElementById("newPassword")?.addEventListener("input", function () {
  checkPasswordStrength(this.value, "newPasswordStrength", "newPasswordHint");
  checkPasswordMatch();
});
document
  .getElementById("confirmPassword")
  ?.addEventListener("input", checkPasswordMatch);

function checkPasswordMatch() {
  const n = document.getElementById("newPassword").value;
  const c = document.getElementById("confirmPassword").value;
  const e = document.getElementById("passwordMatchError");
  if (c.length === 0) {
    e.style.display = "none";
    return;
  }
  e.style.display = n !== c ? "block" : "none";
  updateChangeButtonState();
}

let selectedPasswordOption = "default";
const currentAdminId = window.USERS_CONFIG?.adminId || 0;

function openChangePassword(userId, username) {
  document.getElementById("changePasswordUserId").value = userId;
  document.getElementById("changePasswordUsername").textContent = username;
  document.getElementById("confirmChange").checked = false;
  document.getElementById("btnChangePassword").disabled = true;
  document.getElementById("selfEditBadge").style.display =
    userId == currentAdminId ? "inline-block" : "none";
  selectPasswordOption("default", document.querySelector(".password-option"));
  document.getElementById("newPassword").value = "";
  document.getElementById("confirmPassword").value = "";
  document.getElementById("passwordMatchError").style.display = "none";
  document.getElementById("newPasswordStrength").className =
    "password-strength-bar";
  new bootstrap.Modal(document.getElementById("changePasswordModal")).show();
}

function selectPasswordOption(option, clickedElement) {
  selectedPasswordOption = option;
  const radio = document.querySelector(
    `input[name="password_option"][value="${option}"]`,
  );
  if (radio) radio.checked = true;
  document
    .querySelectorAll(".password-option")
    .forEach((el) => el.classList.remove("selected"));
  if (clickedElement) clickedElement.classList.add("selected");
  else if (radio) radio.closest(".password-option").classList.add("selected");
  const cf = document.getElementById("customPasswordFields");
  cf.style.display = option === "custom" ? "block" : "none";
  const np = document.getElementById("newPassword"),
    cp = document.getElementById("confirmPassword");
  if (option === "custom") {
    np.required = true;
    cp.required = true;
    setTimeout(() => np.focus(), 100);
  } else {
    np.required = false;
    cp.required = false;
    np.value = "";
    cp.value = "";
    document.getElementById("passwordMatchError").style.display = "none";
  }
  updateChangeButtonState();
}

document.querySelectorAll('input[name="password_option"]').forEach((r) =>
  r.addEventListener("change", function () {
    selectPasswordOption(this.value, this.closest(".password-option"));
  }),
);
document
  .getElementById("confirmChange")
  .addEventListener("change", updateChangeButtonState);

function updateChangeButtonState() {
  const ok = document.getElementById("confirmChange").checked;
  const isC = selectedPasswordOption === "custom";
  const n = document.getElementById("newPassword").value,
    c = document.getElementById("confirmPassword").value;
  let v = ok;
  if (isC) v = v && n.length >= 6 && n === c;
  document.getElementById("btnChangePassword").disabled = !v;
}

document
  .getElementById("changePasswordForm")
  .addEventListener("submit", function (e) {
    const opt = document.querySelector(
      'input[name="password_option"]:checked',
    ).value;
    const n = document.getElementById("newPassword").value,
      c = document.getElementById("confirmPassword").value;
    if (opt === "custom") {
      if (n.length < 6) {
        e.preventDefault();
        alert("❌ Password minimal 6 karakter!");
        return false;
      }
      if (n !== c) {
        e.preventDefault();
        alert("❌ Password tidak sama!");
        return false;
      }
    }
  });

function deleteUser(userId, userName) {
  document.getElementById("deleteUserId").value = userId;
  document.getElementById("deleteUserName").textContent = userName;
  document.getElementById("confirmDelete").checked = false;
  document.getElementById("btnDelete").disabled = true;
  new bootstrap.Modal(document.getElementById("deleteUserModal")).show();
}

document
  .getElementById("confirmDelete")
  .addEventListener("change", function () {
    document.getElementById("btnDelete").disabled = !this.checked;
  });

function toggleStatus(userId, newStatus) {
  if (
    !confirm(
      `Yakin ingin ${newStatus ? "mengaktifkan" : "menonaktifkan"} user ini?`,
    )
  )
    return;
  const fd = new FormData();
  fd.append("action", "toggle_status");
  fd.append("id", userId);
  fd.append("is_active", newStatus);
  fetch("process_user.php", { method: "POST", body: fd })
    .then((r) => r.json())
    .then((d) => {
      if (d.success) location.reload();
      else alert("❌ " + d.error);
    })
    .catch(() => alert("Terjadi kesalahan!"));
}

function showConfirm({ icon, title, message, detail, yesText, yesAction }) {
  document.getElementById("confirmIcon").textContent = icon || "⚠️";
  document.getElementById("confirmTitle").textContent = title || "Konfirmasi";
  document.getElementById("confirmMessage").textContent =
    message || "Apakah Anda yakin?";
  const de = document.getElementById("confirmDetail");
  if (detail) {
    de.innerHTML = detail;
    de.style.display = "block";
  } else {
    de.style.display = "none";
  }
  const yb = document.getElementById("confirmYesBtn");
  yb.textContent = yesText || "Ya";
  yb.onclick = function () {
    hideConfirm();
    if (yesAction) yesAction();
  };
  const ov = document.getElementById("confirmOverlay");
  ov.style.display = "flex";
  requestAnimationFrame(() => ov.classList.add("show"));
}

function hideConfirm() {
  const ov = document.getElementById("confirmOverlay");
  ov.classList.remove("show");
  setTimeout(() => (ov.style.display = "none"), 250);
}
document
  .getElementById("confirmOverlay")
  ?.addEventListener("click", function (e) {
    if (e.target === this) hideConfirm();
  });

// Auto-check password status
(function () {
  const statusEl = document.getElementById("passwordStatus");
  if (!statusEl) return;
  const uid = window.USERS_CONFIG?.editUserId;
  if (!uid) return;
  fetch("process_user.php", {
    method: "POST",
    headers: { "X-Requested-With": "XMLHttpRequest" },
    body: new URLSearchParams({ action: "check_password_status", id: uid }),
  })
    .then((r) => r.json())
    .then((d) => {
      statusEl.innerHTML = d.is_default
        ? '<span class="text-warning">⚠️ Masih menggunakan password default</span>'
        : '<span class="text-success">✅ Password sudah diubah dari default</span>';
    })
    .catch(() => {
      statusEl.innerHTML =
        '<span class="text-muted">ℹ️ Status tidak tersedia</span>';
    });
})();

function copyDefaultPassword() {
  const i = document.getElementById("defaultPasswordInput");
  navigator.clipboard
    .writeText(i.value)
    .then(() => showToastMessage("✅ Password default berhasil dicopy!"))
    .catch(() => {
      i.select();
      document.execCommand("copy");
      showToastMessage("✅ Berhasil dicopy!");
    });
}

function generateRandomPassword() {
  const ch = "abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%";
  let p = "";
  for (let i = 0; i < 12; i++)
    p += ch.charAt(Math.floor(Math.random() * ch.length));
  document.getElementById("generatedPassword").value = p;
}

function copyGeneratedPassword() {
  const i = document.getElementById("generatedPassword");
  if (!i.value) {
    showToastMessage("⚠️ Generate dulu!");
    return;
  }
  navigator.clipboard
    .writeText(i.value)
    .then(() => showToastMessage("✅ Berhasil dicopy!"))
    .catch(() => {
      i.select();
      document.execCommand("copy");
      showToastMessage("✅ Berhasil dicopy!");
    });
}

function quickResetPassword(userId, username) {
  showConfirm({
    icon: "⚡",
    title: "Quick Reset Password?",
    message: `Password "${username}" akan direset ke default.`,
    detail:
      '<div style="text-align:center;"><div style="font-size:0.8rem;color:#6b7280;margin-bottom:6px;">Password akan menjadi:</div><div style="font-size:1.3rem;font-weight:900;font-family:monospace;background:#f3f4f6;padding:10px;border-radius:8px;">password123</div></div>',
    yesText: "⚡ Ya, Reset",
    yesAction: function () {
      const fd = new FormData();
      fd.append("action", "change_password");
      fd.append("id", userId);
      fd.append("password_option", "default");
      fetch("process_user.php", {
        method: "POST",
        body: fd,
        headers: { "X-Requested-With": "XMLHttpRequest" },
      })
        .then((r) => r.json())
        .then((d) => {
          if (d.success) {
            showToastMessage("✅ Berhasil direset!");
            setTimeout(() => location.reload(), 1500);
          } else alert("❌ " + d.error);
        })
        .catch(() => alert("Error!"));
    },
  });
}

function showToastMessage(msg) {
  let t = document.getElementById("globalToast");
  if (!t) {
    t = document.createElement("div");
    t.id = "globalToast";
    t.style.cssText =
      "position:fixed;top:20px;right:20px;background:#1e1b4b;color:white;padding:14px 24px;border-radius:12px;font-weight:600;z-index:9999;box-shadow:0 8px 30px rgba(0,0,0,0.2);transition:all 0.3s ease;font-size:0.9rem;";
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.opacity = "1";
  t.style.transform = "translateY(0)";
  setTimeout(() => {
    t.style.opacity = "0";
    t.style.transform = "translateY(-20px)";
  }, 2500);
}
