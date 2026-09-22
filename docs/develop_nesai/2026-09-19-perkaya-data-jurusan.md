# Task: Perkaya Data config/jurusan.php untuk Rekomendasi Jurusan

**Tanggal:** 2026-09-19  
**Branch:** `feature/nesai-api`  
**Bagian dari:** Sub-task 1 — Fitur Rekomendasi Jurusan Berbasis Minat

## Task
Memperkaya data statis `config/jurusan.php` dengan field-field baru yang dibutuhkan untuk scoring rekomendasi jurusan berdasarkan minat pengguna, sekaligus memperbaiki deskripsi setiap jurusan dari generik menjadi informatif.

## File dibuat/diubah
- `config/jurusan.php` — **diubah** (overwrite: menambahkan field `slug`, `kata_kunci_minat`, `prospek_karir`, `mata_pelajaran_utama` ke seluruh 10 jurusan; memperbaiki `deskripsi`)

## Struktur Data Baru Per Jurusan

| Field | Tipe | Fungsi |
|---|---|---|
| `nama` | `string` | *(sudah ada)* Nama resmi kompetensi keahlian |
| `slug` | `string` | **BARU** — identifier URL-safe untuk deep-link frontend (`/jurusan/{slug}`) |
| `deskripsi` | `string` | *(diperkaya)* Deskripsi informatif menggantikan "Jurusan X" generik |
| `kuota` | `int` | *(sudah ada)* Kuota penerimaan |
| `kata_kunci_minat` | `array<string>` | **BARU** — 14-15 kata kunci per jurusan untuk scoring rekomendasi |
| `prospek_karir` | `array<string>` | **BARU** — 6 profesi lulusan per jurusan |
| `mata_pelajaran_utama` | `array<string>` | **BARU** — 5 mapel inti per jurusan |

## Keputusan yang diambil

### 1. Pengelompokan per Rumpun
Jurusan dikelompokkan menggunakan komentar separator per rumpun keahlian (TI, Mesin & Otomotif, Bisnis & Manajemen, Logistik & Kuliner) agar mudah di-navigate oleh developer.

### 2. Slug Convention
- Jurusan dengan singkatan resmi populer → pakai singkatan: `tkj`, `pplg`, `dkv`, `akl`, `mplb`
- Jurusan tanpa singkatan standar → pakai kebab-case: `teknik-mesin`, `teknik-otomotif`, `teknik-logistik`, `pemasaran`, `kuliner`
- Semua slug ditandai komentar `/* DRAFT — sesuaikan dengan slug frontend Next.js */` agar tim frontend bisa menyinkronkan.

### 3. Kata Kunci Minat Bilingual
Kata kunci minat dicampur bahasa Indonesia dan Inggris (misal: `'coding'`, `'pemrograman'`, `'komputer'`). Ini karena calon siswa SMK bisa mengetik minat mereka di chat dalam dua bahasa.

### 4. Backward Compatibility
Field lama (`nama`, `deskripsi`, `kuota`) **tidak diubah posisi/tipe**-nya. `GetJurusanInfoTool` yang sudah ada tetap kompatibel tanpa perlu modifikasi — tool tsb hanya membaca `nama` dan `deskripsi` untuk pencarian.

### 5. Semua Data Ditandai DRAFT
Komentar header file secara eksplisit menyatakan bahwa data ini adalah **template representatif** dan tim konten wajib review sebelum demo juri JHIC 2.0.

## Verifikasi
- ✅ PHP syntax check (`php -l config/jurusan.php`) — no errors
- ✅ Data integrity test:
  - Total jurusan: 10 ✓
  - Semua entry memiliki field `slug`: YES ✓
  - Semua entry memiliki field `kata_kunci_minat`: YES ✓
  - Semua entry memiliki field `prospek_karir`: YES ✓
  - Semua entry memiliki field `mata_pelajaran_utama`: YES ✓
  - Backward compatible (`nama` + `deskripsi` + `kuota`): YES ✓

## Belum selesai / blocker
- **Sub-task 2**: Buat `RecommendJurusanTool` + `JurusanScorer` + update `SchoolAssistantAgent` dan `NesaiService` — task berikutnya.
- **Sub-task 3**: Rewire endpoint `POST /api/v1/recommendations/majors`.
- **Slug sinkronisasi**: Slug yang digunakan perlu dikonfirmasi oleh tim frontend agar konsisten dengan routing Next.js.
