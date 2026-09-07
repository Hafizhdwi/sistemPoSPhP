# 🏪 Mini PoS - Sistem Point of Sale Modern

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Laragon](https://img.shields.io/badge/Laragon-Compatible-009688)](https://laragon.org)

Sistem Point of Sale (PoS) modern berbasis web dengan fitur lengkap untuk bisnis F&B, retail, dan UMKM. Dibangun menggunakan **PHP Native + Bootstrap 5 + MySQL** dengan arsitektur modular yang scalable, maintainable, dan mudah dikembangkan.

![Mini PoS Banner](docs/menulogin.png)

---

## ✨ Fitur Utama

### 🔐 Autentikasi & Keamanan
- ✅ Multi-user system dengan role-based access (Admin & Kasir)
- ✅ Auto-fix password hash untuk kompatibilitas lintas versi PHP
- ✅ Auto-rehash saat login untuk upgrade keamanan otomatis
- ✅ Password strength indicator (Lemah/Sedang/Kuat)
- ✅ Show/hide password dengan icon mata
- ✅ Riwayat perubahan password per user
- ✅ Proteksi admin terakhir & proteksi diri sendiri
- ✅ Activity logging untuk audit trail lengkap
- ✅ Session management yang aman

### 👤 Manajemen User
- ✅ CRUD user dengan avatar gradient otomatis
- ✅ **User Info Modern** dengan dropdown di sidebar (avatar gradient, role badge, online indicator)
- ✅ Dropdown profile dengan akses cepat: Edit Profil, Pengaturan, Ganti Password, Logout
- ✅ Halaman Edit Profil (nama, username, password)
- ✅ Ganti password dengan verifikasi password lama
- ✅ Reset password (default/custom) via modal
- ✅ Quick reset ke password default dengan confirmation modal
- ✅ Generate password acak 12 karakter
- ✅ Copy password ke clipboard
- ✅ Aktifkan/nonaktifkan akun
- ✅ Pagination daftar user (10 per halaman)
- ✅ Badge role berwarna (Admin ungu 🛡️, Kasir hijau 🛒)
- ✅ **Compact Log Activity** dengan pagination (5 per halaman, scrollable)
- ✅ **Log Pagination di atas** untuk akses mudah tanpa scroll

### 🛒 Modul Kasir (Upgraded!)
- ✅ Pencarian produk real-time dengan shortcut **Ctrl+K**
- ✅ **Floating Cart Button** dengan badge jumlah item (gradient purple)
- ✅ **Offcanvas Cart** slide dari kanan (bukan panel statis)
- ✅ **Calendar Panel** modern menggantikan panel cart (gradient header, stats harian)
- ✅ Perhitungan pajak otomatis dari pengaturan toko
- ✅ Tampilan subtotal + pajak + total di offcanvas
- ✅ Multi-metode pembayaran (Tunai, QRIS, Transfer, E-Wallet)
- ✅ Cetak struk thermal (58mm/80mm) otomatis
- ✅ **Premium Toast Notifikasi** dengan:
  - 🎨 Animasi slide-in dengan bounce effect
  - ✨ Icon check SVG dengan draw animation
  - 🌊 Ripple effect di belakang icon
  - 🎉 Mini confetti (30 partikel warna-warni)
  - 🔊 Sound effect "ka-ching" kasir
  - 📊 Progress bar countdown 5 detik
  - ⏸️ Hover pause (countdown berhenti saat hover)
- ✅ Auto-load cart dari pesanan kiosk
- ✅ Panel pesanan kiosk aktif dengan status real-time
- ✅ **AJAX Polling** pesanan kiosk baru setiap 5 detik
- ✅ Notifikasi suara & toast untuk pesanan baru masuk

### 🖥️ Self-Service Kiosk
- ✅ Halaman pelanggan tanpa login (seperti McDonald's/KFC)
- ✅ UI touch-friendly dengan animasi smooth
- ✅ Filter kategori produk (Makanan/Minuman/Snack)
- ✅ Floating cart bar dengan auto-show
- ✅ Nomor antrian otomatis
- ✅ 4 metode pembayaran terintegrasi
- ✅ Review pesanan dengan kontrol quantity (+/-)
- ✅ Custom confirmation modal untuk batal pesanan
- ✅ Input nama & nomor meja fleksibel (huruf/angka)
- ✅ Success screen dengan nomor antrian

### 🍳 Kitchen Display System (KDS)
- ✅ Layar dapur real-time dengan auto-refresh 5 detik
- ✅ Status pesanan: Pending → Preparing → Ready → Completed
- ✅ Notifikasi suara saat pesanan baru masuk
- ✅ Live indicator dengan animasi pulse
- ✅ Color-coded card untuk status (Kuning/Biru/Hijau)
- ✅ **User Info Mini** di header dengan dropdown
- ✅ Navigation links ke Kasir, Dashboard, Riwayat
- ✅ Responsive untuk tablet & monitor dapur

### 📦 Manajemen Inventory
- ✅ CRUD produk lengkap
- ✅ Restock dengan audit trail
- ✅ Edit stok manual dengan catatan adjustment
- ✅ Preview selisih stok (bertambah/berkurang)
- ✅ Riwayat mutasi stok (Masuk/Keluar/Adjustment)
- ✅ Peringatan stok menipis (< 10 unit)
- ✅ Badge warna stok (merah < 10, hijau >= 10)

### ⚙️ Pengaturan Toko
- ✅ Konfigurasi nama, alamat, telepon, email toko
- ✅ Upload logo toko dengan preview real-time
- ✅ Hapus logo dengan konfirmasi
- ✅ Toggle pajak on/off
- ✅ Tarif pajak custom (%)
- ✅ Label pajak custom (PPN 11%, dll)
- ✅ Custom header & footer struk
- ✅ Toggle elemen struk (logo, alamat)
- ✅ Preview struk live dengan data contoh
- ✅ Semua setting tersimpan di database

### 📊 Dashboard & Analytics
- ✅ Statistik penjualan harian & bulanan
- ✅ Total item terjual hari ini
- ✅ Widget produk stok menipis
- ✅ Chart penjualan 7 hari terakhir (Chart.js Line)
- ✅ Top 5 produk terlaris (Chart.js Bar Horizontal)
- ✅ Analisis jam sibuk / Peak Hours (Chart.js Bar)
- ✅ Tabel transaksi terbaru
- ✅ Gradient stat cards

### 📜 Pusat Riwayat
- ✅ Tab Transaksi dengan filter status & aksi cepat
- ✅ Tab Mutasi Stok dengan audit trail
- ✅ Tab Aktivitas User (admin only)
- ✅ Log aktivitas dengan badge warna & icon
- ✅ Informasi entity, target, IP address
- ✅ Export-ready untuk laporan

### 📋 Log Aktivitas & Audit Trail
- ✅ Pencatatan login berhasil/gagal
- ✅ Pencatatan logout
- ✅ Pencatatan CRUD user
- ✅ Pencatatan perubahan password
- ✅ Pencatatan perubahan profil
- ✅ Pencatatan transaksi
- ✅ Pencatatan perubahan pengaturan toko
- ✅ **Compact Pagination** log (5 per halaman, terbaru ke terlama)
- ✅ **Scrollable container** dengan custom scrollbar
- ✅ Navigasi pagination lengkap (First/Prev/Next/Last)
- ✅ Info counter "Menampilkan X-Y dari Z log"

---

## 🎨 UI/UX Features

- ✅ **Modern Sidebar** dengan avatar gradient & dropdown di semua halaman
- ✅ **Responsive Design** untuk desktop, tablet, dan mobile
- ✅ **Dark Theme Kitchen** untuk layar dapur
- ✅ **Smooth Animations** pada dropdown, toast, dan transisi
- ✅ **Color-Coded Badges** untuk status, role, dan tipe
- ✅ **Custom Confirmation Modals** pengganti alert browser
- ✅ **Premium Toast Notifications** dengan confetti, sound, progress bar
- ✅ **Sound Notifications** via Web Audio API
- ✅ **Keyboard Shortcuts** (Ctrl+K search, ESC close dropdown/modal)
- ✅ **Print-Optimized** struk thermal
- ✅ **Floating Action Button** dengan pulse animation
- ✅ **Offcanvas Slide** untuk keranjang belanja
- ✅ **Calendar Heatmap** dengan indicator transaksi

---

## 🏗️ Arsitektur Modular

Project ini menggunakan arsitektur modular untuk maintainability yang lebih baik:

sistemPoSPhP/
│
├── 📁 config/ # Konfigurasi & helper functions
│ ├── database.php # Koneksi DB + helpers + auto-fix hash
│ ├── database.example.php # Template konfigurasi database
│ └── auth.php # Authentication helpers
│
├── 📁 assets/ # Static assets (CSS, JS, images)
│ ├── style.css # Global CSS (sidebar, dropdown, profile)
│ ├── 📁 css/
│ │ └── kasir.css # 🆕 CSS specific halaman kasir
│ └── 📁 js/
│ └── kasir.js # 🆕 JS specific halaman kasir
│
├── 📁 components/ # 🆕 Komponen HTML reusable
│ ├── sidebar.php # Sidebar + user info dropdown
│ ├── calendar-panel.php # Panel kalender interaktif + statistik
│ ├── offcanvas-cart.php # Offcanvas keranjang + floating button
│ └── toast-success.php # Notifikasi sukses premium (confetti)
│
├── 📁 uploads/ # File upload (logo toko, dll)
│ └── .gitkeep # Placeholder untuk git
│
├── 📁 docs/ # Dokumentasi & screenshot
│ └── menulogin.png # Banner halaman login
│
│ ─── 📄 HALAMAN UTAMA ───────────────────────────────────────────
│
├── index.php # 🛒 Halaman kasir (AJAX handler + main)
├── login.php # 🔐 Halaman login
├── logout.php # 🚪 Proses logout + activity logging
├── dashboard.php # 📊 Dashboard admin + analytics
├── products.php # 📦 Manajemen produk & inventory
├── kitchen.php # 🍳 Kitchen display system (KDS)
├── users.php # 👥 Manajemen user + log aktivitas
├── history.php # 📜 Pusat riwayat (transaksi/stok/log)
├── settings.php # ⚙️ Pengaturan toko (info, pajak, struk)
├── profile.php # 👤 Edit profil & ganti password
├── receipt.php # 🧾 Cetak struk thermal
└── kiosk.php # 🖥️ Self-service kiosk (tanpa login)
│
│ ─── 📄 HANDLER / PROCESSOR ─────────────────────────────────────
│
├── process_user.php # Handler CRUD user & password
├── process_product.php # Handler CRUD produk & stok
├── process_sale.php # Handler transaksi & perhitungan pajak
├── process_settings.php # Handler pengaturan toko
├── process_profile.php # Handler edit profil user
└── process_order_status.php # Handler update status pesanan kiosk
│
│ ─── 📄 FILE PENDUKUNG ──────────────────────────────────────────
│
├── setup.sql # 🗄️ Database schema + seed data
├── .gitignore # 🚫 Git ignore rules
├── README.md # 📖 Dokumentasi project (file ini)
└── LICENSE # ⚖️ MIT License

### 🎯 Keuntungan Arsitektur Modular

| Aspek | Sebelum | Sesudah |
|-------|---------|---------|
| **File Size** | `index.php` ~1200 baris | `index.php` ~300 baris |
| **Maintainability** | Sulit cari code | File terpisah per tanggung jawab |
| **Browser Cache** | Tiap reload download semua | CSS/JS di-cache browser |
| **Reusability** | Copy-paste manual | `include` component sekali |
| **Debugging** | Susah isolate bug | File terpisah, mudah debug |
| **AJAX Support** | Full HTML response | Partial HTML response (hemat bandwidth) |

---

## 🚀 Teknologi

| Teknologi | Versi | Keterangan |
|-----------|-------|-----------|
| **PHP** | 8.0+ | Backend dengan PDO |
| **MySQL** | 5.7+ | Database |
| **Bootstrap** | 5.3 | UI Framework |
| **Chart.js** | 4.4 | Visualisasi data |
| **Bootstrap Icons** | 1.11 | Icon library |
| **Inter Font** | - | Typography |
| **Web Audio API** | Native | Sound notifications |
| **Laragon** | - | Local development |

---

## 📦 Instalasi

### Prasyarat
- [Laragon](https://laragon.org/download/) (recommended) atau XAMPP/WAMP
- PHP 8.0 atau lebih baru
- MySQL 5.7 atau lebih baru
- Web browser modern (Chrome/Firefox/Edge)
- Nama database: `db_mini_pos`

### Langkah-langkah

#### 1. Clone Repository
```bash
git clone https://github.com/Hafizhdwi/sistemPoSPhP.git
cd sistemPoSPhP