# Monitor Infus — Aplikasi Penghitung Tetesan Infus

Aplikasi web (Yii2 + MySQL) untuk memantau perkiraan waktu habis cairan infus per bed pasien, dengan countdown dalam menit dan alarm saat sisa waktu mencapai 2 menit.

```
Durasi (menit) = Faktor tetes (tetes/mL) × Volume (mL) ÷ TPM (tetes/menit)
```

Dokumen kebutuhan produk: [PRD.md](PRD.md).

## Kebutuhan

- PHP ≥ 7.4 dengan ekstensi `pdo_mysql`, `mbstring`
- Composer
- Container MySQL yang sudah ada (aplikasi **tidak** membuat container baru)

## Instalasi

1. Pasang dependensi

   ```bash
   composer install
   ```

2. Siapkan konfigurasi lingkungan

   ```bash
   cp .env.example .env
   ```

   Sesuaikan nilai di `.env`:

   | Variabel | Keterangan |
   |---|---|
   | `DB_HOST`, `DB_PORT` | Alamat MySQL saat aplikasi/CLI berjalan di mesin host (mis. `127.0.0.1:3309` bila container mem-publish port 3309) |
   | `DB_HOST_DOCKER`, `DB_PORT_DOCKER` | Dipakai otomatis saat aplikasi berjalan **di dalam** container (terdeteksi `/.dockerenv`), mis. `host.docker.internal:3309` atau IP container mysql `:3306` |
   | `DB_NAME`, `DB_USER`, `DB_PASSWORD` | Kredensial database |
   | `APP_TIMEZONE` | Zona waktu operasional, default `Asia/Jakarta` |
   | `COOKIE_VALIDATION_KEY` | String acak untuk validasi cookie |
   | `URL_SHOW_SCRIPT_NAME` | `true` bila server tidak mengizinkan `.htaccess`/mod_rewrite (URL memakai `index.php/...`) |
   | `YII_DEBUG`, `YII_ENV` | Set `false` / `prod` untuk produksi |

3. Buat database (jika belum ada) di container MySQL

   ```bash
   docker exec mysql mysql -uroot -p -e "CREATE DATABASE infus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

4. Jalankan migration (membuat tabel + data awal)

   ```bash
   php yii migrate
   ```

## Menjalankan

**Via web server yang sudah ada** (mis. container `webapp` yang me-mount folder `html/`): buka

```
http://localhost/infus/web/index.php
```

**Via PHP built-in server** (pengembangan):

```bash
php yii serve --docroot=web
# lalu buka http://localhost:8080
```

## Akun awal

| Username | Password | Peran |
|---|---|---|
| `admin` | `admin123` | Administrator (kelola ruangan, bed & pengguna) |
| `petugas` | `petugas123` | Petugas (monitoring & sesi infus) |

Ganti password setelah login pertama melalui menu **Pengguna**.

## Fitur

- Dashboard kartu/tabel dengan countdown per bed, diurutkan dari sisa waktu paling sedikit
- **Multi-ruangan:** master ruangan, setiap bed melekat pada satu ruangan; dashboard punya pemilih ruangan (`?room=<id>`, diingat per perangkat) dan alarm hanya berbunyi untuk bed di ruangan yang dipilih — cocok untuk satu layar monitor per ruangan
- Status: Aman (> 30 mnt), Segera habis (≤ 30 mnt), Alarm (≤ 2 mnt), Habis (0 mnt)
- Alarm visual + suara (`web/static/infus-habis.mp3`, berbunyi terus-menerus tiap 3 detik) dengan tombol **Akui Alarm**; suara perlu diaktifkan sekali lewat tombol **Aktifkan Suara**. Ganti berkas mp3 tersebut untuk mengubah suara alarm
- Countdown dihitung dari waktu server (sinkron tiap 30 detik) sehingga tahan terhadap refresh dan selisih jam perangkat
- Form sesi infus dalam modal dengan pratinjau durasi & perkiraan habis
- Satu bed hanya boleh memiliki satu sesi aktif (validasi server)
- Riwayat sesi per bed dengan filter status & rentang tanggal; mencatat petugas pembuat/pengubah
- Manajemen ruangan, bed, dan pengguna (admin); ruangan dengan sesi aktif tidak dapat dinonaktifkan
- Tema flat, Poppins + Material Design Icons (self-hosted), mode gelap mengikuti sistem

## Konfigurasi ambang

Tersimpan di tabel `app_setting` dan dapat diubah tanpa mengubah kode:

| Kunci | Default | Keterangan |
|---|---|---|
| `infusion_warning_minutes` | `30` | Ambang status "Segera habis" |
| `infusion_alarm_minutes` | `2` | Ambang alarm |

## Struktur

```
config/        konfigurasi (env.php, db.php, web.php, console.php, params.php)
controllers/   Site, Dashboard, Infusion, Room, Bed, User
models/        User, Room, Bed, InfusionSession, AppSetting, LoginForm
services/      InfusionCalculator (perhitungan durasi/sisa waktu)
migrations/    skema database + data awal
views/         layout & halaman
web/css/       app.css (token warna, komponen)
web/js/        app.js (modal, notifikasi, suara), dashboard.js (countdown, alarm)
web/static/    Poppins & MDI (self-hosted)
```

## Catatan

Aplikasi ini adalah alat bantu pemantauan dan tidak menggantikan observasi langsung, penilaian klinis, atau alat medis terkalibrasi.
