# 🏪 Mini PoS - Sistem Point of Sale Modern

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Laragon](https://img.shields.io/badge/Laragon-Compatible-009688)](https://laragon.org)

Sistem Point of Sale (PoS) modern berbasis web dengan fitur lengkap untuk bisnis F&B, retail, dan UMKM. Dibangun menggunakan **PHP Native + Bootstrap 5 + MySQL** dengan arsitektur yang scalable dan mudah dikembangkan.

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
- ✅ Dropdown profile modern dengan animasi smooth
- ✅ Halaman Edit Profil (nama, username, password)
- ✅ Ganti password dengan verifikasi password lama
- ✅ Reset password (default/custom) via modal
- ✅ Quick reset ke password default
- ✅ Generate password acak 12 karakter
- ✅ Copy password ke clipboard
- ✅ Aktifkan/nonaktifkan akun
- ✅ Pagination daftar user (10 per halaman)
- ✅ Badge role berwarna (Admin ungu, Kasir hijau)
- ✅ Online indicator dengan animasi pulse

### 🛒 Modul Kasir
- ✅ Pencarian produk real-time dengan shortcut **Ctrl+K**
- ✅ Keranjang belanja interaktif dengan kontrol quantity (+/-)
- ✅ Perhitungan pajak otomatis dari pengaturan toko
- ✅ Tampilan subtotal + pajak + total di keranjang
- ✅ Multi-metode pembayaran (Tunai, QRIS, Transfer, E-Wallet)
- ✅ Cetak struk thermal (58mm/80mm) otomatis
- ✅ Notifikasi suara & toast untuk pesanan baru
- ✅ Auto-load cart dari pesanan kiosk
- ✅ Panel pesanan kiosk aktif dengan status real-time
- ✅ Auto-polling pesanan kiosk baru setiap 5 detik

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
- ✅ User info mini dengan avatar & dropdown
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
- ✅ Pagination log (10 per halaman, terbaru ke terlama)
- ✅ Navigasi pagination lengkap (First/Prev/Next/Last)
- ✅ Info counter "Menampilkan X-Y dari Z log"

---

## 🎨 UI/UX Features

- ✅ **Modern Sidebar** dengan avatar gradient & dropdown
- ✅ **Responsive Design** untuk desktop, tablet, dan mobile
- ✅ **Dark Theme Kitchen** untuk layar dapur
- ✅ **Smooth Animations** pada dropdown, toast, dan transisi
- ✅ **Color-Coded Badges** untuk status, role, dan tipe
- ✅ **Custom Confirmation Modals** pengganti alert browser
- ✅ **Toast Notifications** dengan auto-dismiss
- ✅ **Sound Notifications** via Web Audio API
- ✅ **Keyboard Shortcuts** (Ctrl+K search, ESC close)
- ✅ **Print-Optimized** struk thermal

---

## 🚀 Teknologi

| Teknologi | Versi | Keterangan |
|-----------|-------|-----------|
| PHP | 8.0+ | Backend dengan PDO |
| MySQL | 5.7+ | Database |
| Bootstrap | 5.3 | UI Framework |
| Chart.js | 4.4 | Visualisasi data |
| Bootstrap Icons | 1.11 | Icon library |
| Inter Font | - | Typography |
| Laragon | - | Local development |

---

## 📂 Struktur Folder
mini-pos/
├── config/
│ ├── database.php # Koneksi DB + helpers + auto-fix
│ ├── database.example.php # Template konfigurasi
│ └── auth.php # Authentication helpers
├── assets/
│ └── style.css # Custom CSS (sidebar, dropdown, profile)
├── uploads/ # Logo & file upload
│ └── .gitkeep
├── docs/ # Dokumentasi & screenshot
│ └── menulogin.png
├── index.php # Halaman kasir
├── login.php # Halaman login
├── logout.php # Proses logout + logging
├── dashboard.php # Dashboard admin
├── products.php # Manajemen produk & inventory
├── kitchen.php # Kitchen display system
├── users.php # Manajemen user + log aktivitas
├── history.php # Pusat riwayat (transaksi/stok/log)
├── settings.php # Pengaturan toko
├── profile.php # Edit profil & ganti password
├── receipt.php # Cetak struk
├── process_user.php # Handler CRUD user & password
├── process_product.php # Handler CRUD produk & stok
├── process_sale.php # Handler transaksi & pajak
├── process_settings.php # Handler pengaturan toko
├── process_profile.php # Handler edit profil
├── process_order_status.php # Handler update status pesanan
├── setup.sql # Database schema + seed data
├── .gitignore # Git ignore rules
├── README.md # Dokumentasi project
└── LICENSE # MIT License

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
cd mini-pos