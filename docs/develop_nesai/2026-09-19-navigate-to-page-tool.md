# Task: Implementasi NavigateToPageTool

**Tanggal:** 2026-09-19  
**Branch:** `feature/nesai-api`

## Task
Implementasi `NavigateToPageTool` untuk memungkinkan AI menyarankan navigasi ke halaman atau rute tertentu di website SMKN 1 Subang (seperti PPDB, jurusan, berita, profil sekolah, fasilitas, visi-misi, alumni, dan kontak).

## File dibuat/diubah
- `app/Ai/Tools/NavigateToPageTool.php` — **diubah** (diimplementasikan lengkap dari stub)

## Keputusan yang diambil

### 1. Penamaan tool eksplisit via `name()`
Menambahkan method `name(): string` yang mengembalikan `'navigate_to_page'` agar selaras dengan system instructions di `SchoolAssistantAgent` serta sesuai dengan spesifikasi penamaan fungsi Gemini.

### 2. Kamus pemetaan rute standar website sekolah
Menyediakan mapping `$routes` untuk berbagai variasi kata kunci dan alias halaman:
- Beranda: `/` (`home`, `beranda`)
- Profil & Fasilitas: `/profil`, `/profil#fasilitas`, `/profil#visi-misi` (`profil`, `tentang`, `about`, `fasilitas`, `visi-misi`)
- Jurusan: `/jurusan` (`jurusan`, `majors`)
- PPDB: `/ppdb` (`ppdb`, `daftar`, `pendaftaran`)
- Berita & Pengumuman: `/berita` (`berita`, `news`, `artikel`)
- Alumni: `/alumni` (`alumni`, `karir`)
- Kontak: `/kontak` (`kontak`, `contact`)
- Pencarian: `/search` (`search`, `pencarian`)

### 3. Dukungan detail slug dan label kustom
- Parameter `slug` (opsional): Memungkinkan navigasi langsung ke halaman detail jurusan atau artikel berita tertentu (misal: `page = 'jurusan'`, `slug = 'pplg'` menghasilkan path `/jurusan/pplg`).
- Parameter `label` (opsional): Memungkinkan AI memberikan teks judul/label tombol yang kontekstual bagi pengguna (misal: "Info Kelulusan 2026").
- Dukungan path langsung: Jika model memberikan path dengan awalan `/` (misal `/ppdb` atau `/profil#fasilitas`), tool dapat langsung memprosesnya.

### 4. Format payload respons JSON terstruktur
Respons tool mengembalikan JSON yang memuat `status`, `action: navigate`, `path`, `title`, dan `message`. Format ini memudahkan:
1. LLM merangkai jawaban teks alami dengan tautan yang tepat ke halaman yang dituju.
2. Controller backend untuk mengekstrak aksi navigasi dari `toolCalls` ke dalam properti `actions` API response bagi frontend Next.js.

## Verifikasi
- ✅ PHP syntax check: `php -l` — no errors.
- ✅ Unit testing:
  - Navigasi rute PPDB menghasilkan `/ppdb`.
  - Navigasi jurusan dengan slug `pplg` menghasilkan `/jurusan/pplg`.
  - Navigasi dengan label kustom berhasil memodifikasi title.
  - Navigasi path langsung seperti `/profil#visi-misi` tertangani dengan benar.
  - Integrasi agent: `SchoolAssistantAgent` memuat 2 tools (`get_jurusan_info` dan `navigate_to_page`) dengan resolusi nama yang valid.

## Belum selesai / blocker
- `NesaiController` / `NesaiService` belum di-rewire untuk menggunakan `SchoolAssistantAgent` dan mengekstrak `actions` dari hasil tool calls.
