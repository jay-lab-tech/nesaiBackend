# Task: Implementasi GetJurusanInfoTool

**Tanggal:** 2026-09-19  
**Branch:** `feature/nesai-api`

## Task
Implementasi `GetJurusanInfoTool` untuk membaca dan menyaring data kompetensi keahlian/jurusan SMKN 1 Subang secara statis dari `config/jurusan.php`.

## File dibuat/diubah
- `app/Ai/Tools/GetJurusanInfoTool.php` — **diubah** (diimplementasikan lengkap dari stub)

## Keputusan yang diambil

### 1. Penamaan tool eksplisit via `name()`
Menambahkan method `name(): string` yang mengembalikan `'get_jurusan_info'` agar konsisten dengan referensi tool di dalam system prompt `SchoolAssistantAgent` serta sesuai dengan standar identifier Gemini (`^[a-zA-Z_][a-zA-Z0-9_]*$`).

### 2. Schema fleksibel (parameter `nama` opsional)
Parameter `nama` dikonfigurasi sebagai string opsional/nullable di method `schema()`.
- Jika argumen `nama` tidak diisi atau kosong: mengembalikan daftar seluruh jurusan beserta kuota dan deskripsinya.
- Jika diisi: menyaring jurusan berdasarkan kata kunci.

### 3. Pemetaan alias dan singkatan umum SMK
Siswa atau orang tua sering bertanya menggunakan singkatan (misal: "TKJ", "PPLG", "RPL", "DKV", "AKL", "Tata Boga"). Ditambahkan kamus `$aliases` untuk memetakan singkatan populer ke nama resmi di konfigurasi agar pencarian tetap akurat.

### 4. Fallback jika pencarian tidak cocok
Jika kata kunci yang dicari tidak ditemukan (misal: "Kedokteran"), tool tidak hanya merespons "tidak ada", melainkan memberikan pemberitahuan sekaligus menyertakan seluruh daftar jurusan yang tersedia di SMKN 1 Subang. Hal ini memudahkan AI mengarahkan penanya ke pilihan yang sebenarnya ada.

### 5. Format respons data JSON terstruktur
Respons tool menggabungkan pengantar teks dan string JSON (`JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE`) agar model AI dapat membaca atribut `nama`, `deskripsi`, dan `kuota` secara tepat tanpa terpotong atau terdistorsi.

## Verifikasi
- ✅ PHP syntax check: `php -l` — no errors.
- ✅ Unit testing script:
  - Request kosong berhasil mengembalikan seluruh 10 jurusan.
  - Pencarian "TKJ" berhasil mengenali alias dan mengembalikan "Teknik Komputer Jaringan".
  - Pencarian "PPLG" berhasil mengenali alias dan mengembalikan "Pengembangan Perangkat Lunak dan Gim".
  - Pencarian kata kunci asing berhasil memicu fallback daftar seluruh jurusan.
  - Skema JSON terbaca valid dengan kunci `nama`.

## Belum selesai / blocker
- `NavigateToPageTool.php` masih berupa stub — belum diimplementasikan.
- `NesaiController` / `NesaiService` belum di-rewire untuk menggunakan `SchoolAssistantAgent`.
