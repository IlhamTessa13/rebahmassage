# Rebah Massage – Booking System

## Deskripsi Proyek

**Rebah Massage** adalah sistem booking untuk layanan **massage dan reflexology** yang kelompok 2 RPL 3SD1 kembangkan untuk memudahkan pelanggan dalam melakukan pemesanan layanan secara online tanpa sistem pembayaran di dalam aplikasi. Seluruh proses pembayaran dilakukan secara **offline** di lokasi.

Sistem ini memiliki dua sisi utama, yaitu **User (Customer)** dan **Admin**, dengan fitur yang terpisah sesuai peran masing-masing.

---

## Fitur Sistem

### Fitur User (Customer)

- **Landing Page**  
  Halaman utama yang menampilkan informasi umum mengenai Rebah Massage, layanan, dan konten promosi.

- **Login & Register**  
  Sistem autentikasi pengguna untuk mengakses fitur booking dan riwayat pemesanan.

- **Booking**  
  Pengguna dapat melakukan pemesanan layanan massage atau reflexology berdasarkan jadwal dan layanan yang tersedia.  
  ⚠️ _Tidak terdapat fitur pembayaran online pada sistem ini._

- **History Booking**  
  Menampilkan riwayat pemesanan yang pernah dilakukan oleh pengguna.

---

### Fitur Admin

- **Dashboard**  
  Ringkasan data sistem seperti jumlah booking, customer, therapist, dan layanan.

- **Service Category**  
  Manajemen kategori layanan massage dan reflexology.

- **Schedule Customer**  
  Pengelolaan jadwal booking dari sisi pelanggan.

- **Schedule Therapist**  
  Pengaturan dan monitoring jadwal therapist.

- **Therapist Data**  
  Manajemen data therapist.

- **Customer Data**  
  Manajemen data pelanggan yang terdaftar di sistem.

- **Room Booking**  
  Pengaturan penggunaan ruangan untuk layanan massage.

- **Manajemen Voucher**

  - Generate voucher
  - Klaim voucher di sistem  
    ⚠️ _Voucher dijual secara offline, bukan melalui sistem._

- **Blog Management**  
  Pengelolaan artikel dan konten blog.

- **Content Management**  
  Pengelolaan konten pada website.

---

## Teknologi

- **Bahasa Pemrograman**: PHP (Native)
- **Frontend**: HTML, CSS, JavaScript
- **Database**: MySQL
- **Autentikasi**: Session-based Authentication
- **Integrasi**:
  - Google Login (Google Config)
  - Email Service (Email Config)

---

## Struktur Folder

```
root/
│── index.php
│── login.php
│── booking.php
│── history.php
│── dashboard.php
│── ... (file .php lain untuk setiap halaman)
│
├── css/
│   └── *.css
│
├── js/
│   └── *.js
│
├── api/
│   └── *.php        # File proses backend / pengambilan data
│
├── includes/
│   ├── db.php       # Konfigurasi database
│   ├── google-config.php
│   ├── email-config.php
│   ├── auth.php    # Autentikasi & session
│   └── ...
│
├── public/
│   └── images/       # Asset gambar
│
├── vendor/            # Dependency (Composer / library lain)
│
└── README.md
```

---

## Catatan Penting

- Sistem **tidak menyediakan pembayaran online**.
- Booking bersifat reservasi jadwal.
- Voucher hanya **digenerate dan diklaim** di sistem, sementara penjualan dilakukan secara **offline**.

---

## Penutup

Proyek **Rebah Massage** dikembangkan sebagai solusi sistem booking yang sederhana, terstruktur, dan mudah dikembangkan kembali sesuai kebutuhan bisnis layanan massage dan reflexology.

---

**Dibuat oleh:**  
TIM 2 RPL 3SD1 Politeknik Statistika STIS Angkatan 65
@2025
