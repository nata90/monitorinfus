# Product Requirements Document (PRD)

## Aplikasi Penghitung Tetesan Infus

**Versi:** 1.0  
**Status:** Draft  
**Platform:** Web  
**Teknologi:** Yii2 (PHP) dan MySQL  

## 1. Ringkasan Produk

Aplikasi Penghitung Tetesan Infus adalah aplikasi web untuk membantu petugas memantau perkiraan waktu habis cairan infus pada setiap bed pasien. Halaman utama menampilkan daftar bed aktif beserta countdown timer dalam satuan menit.

Waktu habis dihitung menggunakan:

```text
Waktu habis (menit) = Faktor tetes (tetes/mL) × Volume (mL) ÷ TPM (tetes/menit)
```

Contoh:

```text
Faktor tetes = 20 tetes/mL
Volume       = 500 mL
TPM          = 20 tetes/menit
Waktu habis  = 20 × 500 ÷ 20 = 500 menit
```

> Aplikasi ini adalah alat bantu pemantauan dan tidak menggantikan observasi langsung, penilaian klinis, atau alat medis terkalibrasi.

## 2. Latar Belakang

Petugas perlu mengetahui infus mana yang akan segera habis agar pemeriksaan atau penggantian cairan dapat diprioritaskan. Perhitungan manual dan pencatatan terpisah berisiko menyebabkan keterlambatan pemantauan. Aplikasi ini menyatukan data bed, parameter infus, dan sisa waktu dalam satu tampilan.

## 3. Tujuan

- Menghitung estimasi durasi infus secara otomatis.
- Menampilkan sisa waktu infus per bed secara real-time.
- Membantu petugas memprioritaskan bed yang perlu segera diperiksa.
- Menyimpan data sesi infus secara terstruktur di MySQL.
- Menyediakan antarmuka sederhana yang dapat digunakan melalui desktop, tablet, atau ponsel.

## 4. Pengguna

### 4.1 Petugas

Pengguna utama yang dapat:

- melihat daftar bed dan status infus;
- menambahkan sesi infus;
- mengubah parameter atau status infus;
- menghentikan atau menyelesaikan sesi infus;
- memulai ulang perhitungan setelah penggantian cairan.

### 4.2 Administrator

Memiliki seluruh akses petugas serta dapat:

- mengelola data bed;
- mengelola akun pengguna;
- melihat riwayat perubahan dan sesi infus.

## 5. Ruang Lingkup MVP

### 5.1 Autentikasi

- Login menggunakan username dan password.
- Hak akses minimal: `admin` dan `petugas`.
- Logout.
- Password disimpan sebagai hash menggunakan fasilitas keamanan Yii2.

### 5.2 Master Ruangan

Aplikasi digunakan di beberapa ruangan (bangsal/unit) sekaligus, sehingga ruangan menjadi entitas master tersendiri.

- Tambah, lihat, ubah, dan nonaktifkan ruangan (hanya `admin`; `petugas` hanya melihat daftar).
- Setiap ruangan memiliki kode unik (singkat, huruf besar/angka) dan nama unik.
- Ruangan tidak dapat dinonaktifkan selama masih memiliki sesi infus aktif.
- Ruangan nonaktif tidak muncul pada pemilih ruangan di dashboard dan tidak dapat dipilih untuk bed baru; bed yang sudah terikat tetap tersimpan.

### 5.3 Pengelolaan Bed

- Tambah, lihat, ubah, dan nonaktifkan bed.
- Setiap bed memiliki kode unik dan **wajib melekat pada satu ruangan** (`room_id`).
- Daftar bed dapat difilter per ruangan dan dicari berdasarkan kode bed atau nama ruangan.
- Satu bed hanya boleh memiliki satu sesi infus aktif.

### 5.4 Pencatatan Sesi Infus

Petugas memilih bed (dikelompokkan per ruangan; bila dashboard sedang difilter ke satu ruangan, hanya bed ruangan itu yang ditawarkan) lalu memasukkan:

- faktor tetes dalam `tetes/mL`;
- volume cairan dalam `mL`;
- TPM (tetes per menit);
- waktu mulai, dengan nilai awal waktu saat ini;
- catatan opsional.

Sistem menghitung:

- total durasi dalam menit;
- perkiraan waktu habis;
- sisa waktu berdasarkan waktu server.

### 5.5 Dashboard Monitoring

Dashboard memiliki **filter ruangan** (pemilih "Semua ruangan" atau satu ruangan aktif):

- filter dibawa pada URL (`?room=<id>`) sehingga dapat di-bookmark dan dipasang permanen pada layar monitor sebuah ruangan;
- pilihan terakhir diingat per perangkat (localStorage); membuka dashboard tanpa parameter akan kembali ke ruangan terakhir yang dipilih;
- saat difilter, hanya sesi infus dan bed kosong milik ruangan tersebut yang ditampilkan, dan **alarm/banner hanya dibunyikan untuk bed di ruangan itu**;
- endpoint sinkronisasi JSON menerima parameter `room` yang sama.

Dashboard menampilkan seluruh bed aktif (sesuai filter ruangan) dengan informasi:

- kode atau nama bed;
- ruangan;
- faktor tetes;
- volume awal;
- TPM;
- waktu mulai;
- perkiraan waktu habis;
- countdown sisa waktu dalam menit;
- status infus.

Urutan default adalah sisa waktu paling sedikit.

Status tampilan:

- **Aman:** sisa waktu lebih dari 30 menit;
- **Segera habis:** sisa waktu 1–30 menit;
- **Habis:** sisa waktu 0 menit atau melewati perkiraan waktu habis;
- **Dihentikan:** sesi dihentikan manual;
- **Selesai:** sesi ditandai selesai oleh petugas.

Ambang 30 menit harus disimpan sebagai konfigurasi agar dapat diubah tanpa mengubah kode.

### 5.6 Pembaruan Countdown

- Countdown diperbarui di browser minimal setiap 60 detik.
- Nilai countdown dihitung dari `estimated_end_at` dan waktu server, bukan dengan mengurangi angka lokal secara permanen.
- Halaman melakukan sinkronisasi ulang dengan server secara berkala agar perbedaan waktu perangkat tidak menyebabkan hasil keliru.
- Countdown tidak boleh menampilkan angka negatif; setelah habis, tampilkan `0 menit` dan status `Habis`.

### 5.7 Alarm Dua Menit

- Sistem memicu alarm visual dan suara ketika sisa waktu infus mencapai 2 menit.
- Alarm hanya berlaku untuk sesi dengan status `active`.
- **Alarm mengikuti filter ruangan:** layar yang difilter ke satu ruangan hanya membunyikan alarm dan menampilkan banner untuk bed di ruangan tersebut, sehingga alarm ruangan lain tidak mengganggu. Tampilan "Semua ruangan" membunyikan alarm untuk seluruh ruangan.
- Alarm suara berbunyi terus-menerus (pola nada diulang setiap 3 detik) sampai petugas menekan tombol `Akui Alarm`, atau sampai sesi ditandai selesai/dihentikan.
- Suara alarm memakai berkas audio `web/static/infus-habis.mp3` (di-self-host, diputar lewat Web Audio API agar tunduk pada tombol `Aktifkan Suara`); berkas dapat diganti tanpa mengubah kode selama nama dan lokasinya sama, dan harus cukup keras untuk terdengar di ruang perawatan.
- Alarm visual menampilkan bed terkait secara jelas dan tidak bergantung pada warna saja.
- Pengakuan alarm hanya menghentikan suara untuk sesi tersebut; status kritis tetap terlihat sampai sesi selesai atau dihentikan.
- Jika dashboard dimuat ulang sebelum alarm diakui, alarm aktif kembali selama sisa waktu masih 2 menit atau kurang.
- Dashboard menyediakan tombol `Aktifkan Suara` setelah login karena browser dapat memblokir audio yang belum dipicu oleh interaksi pengguna.
- Jika suara diblokir atau perangkat dibisukan, alarm visual tetap harus ditampilkan.
- Ambang alarm disimpan sebagai konfigurasi dengan nilai awal 2 menit.

### 5.8 Riwayat

- Menampilkan riwayat sesi infus per bed.
- Menyimpan siapa yang membuat dan terakhir mengubah data.
- Riwayat dapat difilter berdasarkan bed, status, dan rentang tanggal.

## 6. Aturan Perhitungan

### 6.1 Input

- `drop_factor`: bilangan positif, satuan tetes/mL.
- `volume_ml`: bilangan positif, satuan mL.
- `tpm`: bilangan positif, satuan tetes/menit.

### 6.2 Formula

```text
duration_minutes = (drop_factor × volume_ml) ÷ tpm
estimated_end_at = started_at + duration_minutes
remaining_minutes = ceil((estimated_end_at - server_current_time) ÷ 60 detik)
```

### 6.3 Pembulatan

- Durasi disimpan dengan presisi desimal.
- Perkiraan waktu habis dihitung hingga detik.
- Countdown yang ditampilkan dibulatkan ke atas ke menit terdekat agar `1–59` detik masih tampil sebagai `1 menit`.

### 6.4 Validasi

- Semua nilai perhitungan wajib lebih besar dari nol.
- TPM tidak boleh nol untuk mencegah pembagian dengan nol.
- Waktu mulai tidak boleh kosong.
- Sistem menolak angka yang melebihi batas tipe data database.
- Nilai desimal diperbolehkan untuk mengakomodasi input klinis yang valid.

## 7. Alur Pengguna Utama

### 7.1 Memulai Sesi Infus

1. Petugas login.
2. Petugas membuka dashboard.
3. Petugas memilih bed yang tidak memiliki sesi aktif.
4. Petugas memasukkan faktor tetes, volume, TPM, dan waktu mulai.
5. Sistem memvalidasi input dan menampilkan pratinjau durasi serta waktu habis.
6. Petugas menyimpan data.
7. Bed muncul pada dashboard dengan countdown aktif.

### 7.2 Mengganti Cairan Infus

1. Petugas membuka detail bed.
2. Petugas menandai sesi sebelumnya sebagai `Selesai`.
3. Petugas membuat sesi baru dengan parameter infus baru.
4. Countdown dimulai berdasarkan sesi baru.

### 7.3 Mengoreksi Data

1. Petugas membuka sesi aktif.
2. Petugas mengubah parameter yang keliru.
3. Sistem menghitung ulang durasi dan waktu habis.
4. Sistem mencatat pengguna serta waktu perubahan.

## 8. Kebutuhan Antarmuka

### 8.0 Prinsip Desain dan Template

Antarmuka menggunakan template modern, simple, dan elegan dengan prinsip berikut:

- **Flat dan bersih:** tanpa gradien, bayangan berat, atau ornamen dekoratif. Pemisahan elemen menggunakan jarak (whitespace) dan garis tipis.
- **Palet warna:** tiga warna utama ditambah warna netral dan warna status semantik. Warna status hanya dipakai untuk badge/indikator status dan alarm, tidak untuk elemen lain.

  | Peran | Nama | Hex | Penggunaan |
  |---|---|---|---|
  | Primer | Teal | `#0F766E` | Navigasi atas, tombol aksi utama, tautan, state aktif |
  | Sekunder | Slate | `#334155` | Teks utama, judul, ikon netral, border tebal |
  | Aksen | Amber | `#F59E0B` | Sorotan penting, tombol `Aktifkan Suara`, penanda interaktif |
  | Netral | Abu terang | `#F8FAFC` latar, `#E2E8F0` border | Latar halaman dan pemisah |
  | Status Aman | Hijau | `#16A34A` | Badge `Aman` |
  | Status Segera habis | Amber | `#F59E0B` | Badge `Segera habis` |
  | Status Habis / Alarm | Merah | `#DC2626` | Badge `Habis`, kartu alarm 2 menit |

  Pada mode gelap, latar menggunakan `#0F172A`, permukaan kartu `#1E293B`, dan teks `#E2E8F0`; warna primer, sekunder, aksen, dan status dipertahankan dengan penyesuaian kecerahan secukupnya agar kontras tetap memenuhi WCAG AA.

- **Tipografi:** font **Poppins** (Google Fonts) untuk seluruh antarmuka. Bobot yang digunakan: 400 untuk teks, 500 untuk label, 600 untuk judul dan angka countdown. Angka countdown menggunakan ukuran besar dan `font-variant-numeric: tabular-nums` agar digit tidak bergeser saat berubah. Font di-self-host melalui asset bundle agar tetap berfungsi pada jaringan internal tanpa akses internet.
- **Ikon:** **Material Design Icons (MDI)** dipakai konsisten di seluruh aplikasi, misalnya `mdi-bed`, `mdi-iv-bag`, `mdi-timer-outline`, `mdi-bell-ring`, `mdi-volume-high`, `mdi-check-circle`, `mdi-stop-circle`. Tidak menggunakan emoji atau set ikon lain.
- **Hierarki jelas:** sisa waktu adalah informasi paling menonjol pada setiap kartu bed; kode bed dan ruangan menjadi informasi sekunder; parameter infus (faktor tetes, volume, TPM) ditampilkan kecil sebagai detail.
- **Tata letak:** dashboard berbentuk grid kartu responsif (1 kolom di ponsel, 2–3 kolom di tablet, 4+ kolom di desktop) dengan opsi tampilan tabel padat. Navigasi atas minimalis berisi nama aplikasi, pencarian, filter status, tombol `Aktifkan Suara`, dan menu pengguna.
- **Komponen konsisten:** tombol, input, badge status, dan modal memakai satu set gaya yang sama di seluruh aplikasi. Radius sudut kecil-menengah (6–10 px), border tipis, dan state hover/focus yang jelas.
- **Mode gelap:** didukung mengikuti preferensi sistem agar nyaman digunakan di ruang perawatan pada malam hari.
- **Gerak minimal:** animasi hanya untuk transisi status dan penanda alarm (misalnya denyut halus pada kartu kritis); menghormati preferensi `prefers-reduced-motion`.

Implementasi teknis yang disarankan: Tailwind CSS (via Yii2 asset bundle) atau Bootstrap 5 dengan tema kustom yang menghilangkan gaya default yang berat. Template admin pihak ketiga yang penuh widget dihindari agar tampilan tetap fokus.

### 8.1 Dashboard

- Responsif untuk desktop, tablet, dan ponsel.
- Bed yang segera habis dan sudah habis harus mudah dibedakan secara visual.
- Informasi utama tetap terbaca tanpa membuka detail.
- Tersedia pemilih ruangan di header halaman ("Semua ruangan" atau satu ruangan aktif); nama ruangan terpilih ditampilkan di judul.
- Tersedia pencarian berdasarkan kode bed atau ruangan.
- Tersedia filter status.
- Tersedia tombol untuk menambah sesi infus; bila ruangan sedang difilter, form hanya menawarkan bed ruangan tersebut.

### 8.2 Form Sesi Infus

- Form ditampilkan dalam modal atau panel samping (drawer) agar petugas tidak kehilangan konteks dashboard.
- Label input wajib menampilkan satuan.
- Validasi ditampilkan di dekat field terkait.
- Pratinjau waktu habis muncul sebelum penyimpanan.
- Konfirmasi diperlukan saat menghentikan atau menyelesaikan sesi.

### 8.3 Aksesibilitas

- Status tidak dibedakan hanya berdasarkan warna; sertakan teks atau ikon.
- Kontras teks memenuhi standar WCAG AA sejauh memungkinkan.
- Seluruh form dapat digunakan dengan keyboard.

## 9. Model Data

### 9.1 `user`

- `id`: primary key
- `username`: varchar, unik
- `password_hash`: varchar
- `role`: enum/string (`admin`, `petugas`)
- `status`: boolean/smallint
- `created_at`: datetime
- `updated_at`: datetime

### 9.2 `room`

- `id`: primary key
- `code`: varchar(16), unik, huruf besar/angka/strip
- `name`: varchar(128), unik
- `description`: text, nullable
- `is_active`: boolean
- `created_at`: datetime
- `updated_at`: datetime

### 9.3 `bed`

- `id`: primary key
- `code`: varchar, unik
- `room_id`: foreign key ke `room` (RESTRICT; ruangan tidak dapat dihapus selama masih memiliki bed)
- `description`: text, nullable
- `is_active`: boolean
- `created_at`: datetime
- `updated_at`: datetime

### 9.4 `infusion_session`

- `id`: primary key
- `bed_id`: foreign key ke `bed`
- `drop_factor`: decimal
- `volume_ml`: decimal
- `tpm`: decimal
- `duration_minutes`: decimal
- `started_at`: datetime
- `estimated_end_at`: datetime
- `status`: enum/string (`active`, `completed`, `stopped`)
- `notes`: text, nullable
- `created_by`: foreign key ke `user`
- `updated_by`: foreign key ke `user`
- `created_at`: datetime
- `updated_at`: datetime
- `completed_at`: datetime, nullable

### 9.5 `app_setting`

- `id`: primary key
- `setting_key`: varchar, unik
- `setting_value`: varchar/text
- `updated_at`: datetime

Konfigurasi awal:

```text
infusion_warning_minutes = 30
infusion_alarm_minutes = 2
```

## 10. Arsitektur Teknis

### 10.1 Backend

- Yii2 dengan pola MVC.
- Yii2 ActiveRecord untuk akses data.
- Yii2 Migration untuk membuat dan memperbarui skema database.
- Service khusus, misalnya `InfusionCalculator`, untuk perhitungan durasi dan waktu habis.
- Validasi dilakukan di server meskipun validasi sisi klien tersedia.
- Zona waktu aplikasi ditetapkan secara eksplisit dalam konfigurasi.

### 10.2 Frontend

- View Yii2 dengan HTML, CSS, dan JavaScript.
- Styling menggunakan Tailwind CSS atau Bootstrap 5 bertema kustom sesuai prinsip desain pada bagian 8.0, dikelola melalui Yii2 AssetBundle.
- Font Poppins dan Material Design Icons (`@mdi/font`) di-self-host dan dimuat melalui Yii2 AssetBundle, bukan dari CDN, agar berfungsi di jaringan internal.
- Warna primer, sekunder, aksen, netral, dan status didefinisikan sebagai CSS custom properties (misalnya `--color-primary`, `--color-secondary`, `--color-accent`) atau token tema Tailwind/Bootstrap agar konsisten dan mudah diubah.
- Pembaruan countdown dilakukan di sisi klien.
- Data dashboard disinkronkan melalui endpoint JSON/AJAX tanpa harus memuat ulang seluruh halaman.

### 10.3 Database

- Menggunakan container MySQL yang sudah tersedia.
- Aplikasi tidak membuat container MySQL baru.
- Konfigurasi koneksi dibaca dari environment variable:

```text
DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASSWORD
```

- `DB_HOST` harus menggunakan nama service/container pada jaringan Docker jika aplikasi berjalan di container, bukan `localhost`.
- Charset database menggunakan `utf8mb4`.
- Detail nama container, jaringan, database, dan kredensial mengikuti lingkungan yang sudah tersedia.

### 10.4 Endpoint Internal yang Disarankan

- `GET /dashboard` — halaman daftar bed dan countdown.
- `GET /api/infusions/active` — data sesi aktif dan waktu server.
- `POST /infusion/create` — membuat sesi infus.
- `PUT/PATCH /infusion/{id}` — memperbarui sesi.
- `POST /infusion/{id}/complete` — menyelesaikan sesi.
- `POST /infusion/{id}/stop` — menghentikan sesi.
- `GET /bed/{id}/history` — riwayat sesi per bed.

## 11. Persyaratan Nonfungsional

### 11.1 Kinerja

- Dashboard dengan hingga 100 bed aktif dimuat maksimal 3 detik pada jaringan lokal normal.
- Pembaruan countdown di browser tidak memuat ulang seluruh halaman.
- Query sesi aktif memiliki indeks pada `status`, `bed_id`, dan `estimated_end_at`.

### 11.2 Keamanan

- Seluruh halaman operasional memerlukan autentikasi.
- Otorisasi berdasarkan peran diterapkan di controller.
- Perlindungan CSRF Yii2 tetap aktif.
- Input divalidasi dan output di-escape untuk mencegah injection dan XSS.
- Kredensial database tidak disimpan di repository.
- Aktivitas perubahan sesi mencatat pengguna dan waktu.

### 11.3 Keandalan

- Perhitungan utama dilakukan di server.
- Refresh browser tidak mengatur ulang countdown.
- Data sesi tetap tersedia setelah aplikasi atau container direstart.
- Operasi pembuatan dan penutupan sesi menggunakan transaksi database jika melibatkan lebih dari satu perubahan.

### 11.4 Kompatibilitas

- Mendukung dua versi terbaru Chrome, Edge, Firefox, dan Safari.
- PHP dan MySQL mengikuti versi yang kompatibel dengan edisi Yii2 yang dipilih.

## 12. Kriteria Penerimaan MVP

1. Pengguna yang valid dapat login dan membuka dashboard.
2. Admin dapat membuat dan menonaktifkan ruangan serta bed; setiap bed wajib melekat pada satu ruangan.
3. Petugas dapat membuat sesi infus menggunakan faktor tetes, volume, dan TPM.
4. Sistem menghitung durasi dengan formula `faktor tetes × volume ÷ TPM`.
5. Sistem menolak faktor tetes, volume, atau TPM yang kosong, nol, atau negatif.
6. Dashboard menampilkan seluruh sesi aktif dan countdown dalam menit.
7. Countdown tetap benar setelah halaman dimuat ulang.
8. Sesi dengan sisa waktu 30 menit atau kurang ditandai `Segera habis`.
9. Sesi yang melewati waktu habis menampilkan `0 menit` dan status tampilan `Habis`.
10. Petugas dapat menandai sesi sebagai selesai atau dihentikan.
11. Satu bed tidak dapat memiliki lebih dari satu sesi aktif.
12. Riwayat sesi dapat dilihat per bed.
13. Aplikasi terhubung ke container MySQL yang sudah ada melalui environment variable.
14. Seluruh migration dapat dijalankan pada database kosong tanpa error.
15. Saat sisa waktu sesi aktif mencapai 2 menit, dashboard menampilkan alarm visual dan membunyikan alarm.
16. Alarm suara berbunyi terus-menerus (diulang setiap 3 detik) sampai diakui atau sesi selesai/dihentikan.
17. Mengakui alarm menghentikan suara tanpa menghilangkan penanda visual sesi yang kritis.
18. Alarm visual tetap berfungsi ketika browser memblokir audio atau perangkat dibisukan.
19. Dashboard dapat difilter per ruangan melalui pemilih ruangan dan parameter URL `room`; filter diingat per perangkat.
20. Saat difilter ke satu ruangan, hanya sesi ruangan itu yang ditampilkan dan alarm hanya berbunyi untuk bed di ruangan tersebut.
21. Ruangan yang masih memiliki sesi infus aktif tidak dapat dinonaktifkan.

## 13. Di Luar Ruang Lingkup MVP

- Integrasi langsung dengan infusion pump atau sensor IoT.
- Penentuan dosis obat atau rekomendasi terapi.
- Rekam medis lengkap pasien.
- Notifikasi WhatsApp, SMS, atau push notification.
- Aplikasi mobile native.
- Integrasi dengan SIMRS.
- Analitik penggunaan cairan tingkat lanjut.

## 14. Risiko dan Mitigasi

- **Input tidak akurat:** tampilkan satuan dengan jelas, lakukan validasi, dan sediakan pratinjau hasil.
- **Perbedaan jam perangkat:** gunakan waktu server sebagai sumber kebenaran.
- **Countdown dianggap sebagai nilai klinis pasti:** tampilkan disclaimer dan status sebagai estimasi.
- **Dua petugas mengaktifkan sesi pada bed yang sama:** gunakan validasi database/transaksi untuk menjamin hanya satu sesi aktif.
- **Koneksi database terputus:** tampilkan pesan kegagalan yang jelas dan jangan menampilkan data lama sebagai data terbaru tanpa indikator.
- **Aplikasi atau browser tidak aktif:** hitung ulang dari `estimated_end_at` saat aplikasi kembali aktif.
- **Audio diblokir browser atau perangkat dibisukan:** minta pengguna mengaktifkan suara setelah login dan selalu sediakan alarm visual.
- **Kelelahan alarm:** bunyikan alarm hanya untuk sesi aktif yang memasuki ambang 2 menit dan sediakan tombol pengakuan per sesi.

## 15. Metrik Keberhasilan

- Seluruh bed dengan infus aktif dapat dipantau dari satu halaman.
- Tidak ada sesi ganda aktif pada bed yang sama.
- Hasil perhitungan sistem sesuai formula untuk seluruh kasus uji.
- Waktu yang dibutuhkan petugas untuk mencatat sesi baru tidak lebih dari satu menit dalam pengujian penggunaan.

## 16. Rencana Pengembangan

### Fase 1 — Fondasi

- Inisialisasi proyek Yii2.
- Konfigurasi koneksi ke container MySQL yang tersedia.
- Migration dan model data.
- Autentikasi dan otorisasi.

### Fase 2 — Fungsi Inti

- CRUD bed.
- Pembuatan dan perubahan sesi infus.
- Service perhitungan.
- Dashboard dan countdown.

### Fase 3 — Penyelesaian MVP

- Riwayat dan filter.
- Audit pengguna.
- Pengujian unit, integrasi, dan antarmuka.
- Dokumentasi instalasi dan operasional.

## 17. Asumsi dan Hal yang Perlu Dikonfirmasi

- Aplikasi digunakan pada jaringan internal fasilitas kesehatan.
- Detail koneksi container MySQL akan diberikan melalui environment variable.
- Identitas pasien tidak disimpan pada MVP; bed menjadi identitas utama tampilan.
- Ambang peringatan awal adalah 30 menit.
- Ambang alarm kritis awal adalah 2 menit.
- Zona waktu operasional akan ditentukan saat implementasi.
- Formula yang digunakan mengikuti kebutuhan produk yang diberikan dan harus dikonfirmasi oleh pemilik proses klinis sebelum digunakan secara operasional.

