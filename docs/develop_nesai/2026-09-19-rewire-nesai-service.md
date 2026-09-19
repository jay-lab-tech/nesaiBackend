# Task: Rewire NesaiService ke SchoolAssistantAgent

**Tanggal:** 2026-09-19  
**Branch:** `feature/nesai-api`

## Task
Menghubungkan `NesaiService` dan `NesaiController` ke `SchoolAssistantAgent` (`laravel/ai`), mengekstrak pemanggilan tool `navigate_to_page` dan `get_jurusan_info` menjadi struktur data `actions` dan `sources`, serta mengembalikan format JSON standar yang diharapkan oleh frontend Next.js sesuai dokumen arsitektur teknis.

## File dibuat/diubah
- `app/Services/Nesai/NesaiService.php` — **diubah** (mengganti stub lama dengan integrasi `SchoolAssistantAgent`, ekstraksi `actions`, `sources`, dan error fallback)
- `app/Http/Controllers/Api/NesaiController.php` — **diubah** (meneruskan parameter `message` dan `context` ke `NesaiService`)
- `app/Services/Nesai/LlmService.php` — **diubah** (memeriksa ketersediaan kunci API pada `ai.providers.gemini.key` dan `services.llm.key`)
- `config/ai.php` — **diubah** (menambahkan fallback pembacaan kunci Gemini dari `LLM_API_KEY` selain `GEMINI_API_KEY`)

## Keputusan yang diambil

### 1. NesaiService sebagai Adapter / Orchestrator
Sesuai dokumen arsitektur teknis dari lead tim (`docs/arsitektur_teknis/NESAI_Arsitektur_Teknis_FINAL_Laravel.docx`), `NesaiService` dipertahankan sebagai orkestrator bisnis AI. `SchoolAssistantAgent` dipanggil dari dalam `NesaiService` sehingga controller tetap ramping dan kontrak respons ke frontend Next.js tetap terjaga.

### 2. Ekstraksi Otomatis Actions Navigasi
Ketika agent mengeksekusi tool `navigate_to_page`, hasilnya diekstrak dari `$response->toolResults` atau `$response->toolCalls` dan dimasukkan ke dalam array `actions`:
```json
{
  "type": "navigate",
  "path": "/ppdb",
  "title": "Pendaftaran Peserta Didik Baru (PPDB)"
}
```
Jika ada aksi navigasi yang dipicu, nilai `intent` secara otomatis disesuaikan menjadi `'school_navigation'`.

### 3. Ekstraksi Sources dari Tool Jurusan
Jika agent memanggil `get_jurusan_info`, array `sources` otomatis mencatat sumber referensi `'Informasi Jurusan SMKN 1 Subang (config/jurusan.php)'`.

### 4. Resilient Fallback Error Handling
Blok `try-catch` disiapkan untuk menangkap `Throwable` jika terjadi gangguan jaringan, ketiadaan API key di lingkungan lokal, atau rate limit pada Gemini. Alih-alih mengembalikan HTTP 500, sistem memberikan respons teks ramah pengguna disertai tombol aksi navigasi default (`/jurusan` dan `/ppdb`) dengan mode `'fallback-error'`.

## Verifikasi
- ✅ PHP syntax check: `php -l` seluruh file yang diubah bebas error.
- ✅ Unit testing `NesaiService`: resolusi container via Laravel IoC sukses, pemanggilan fake agent menghasilkan structured data yang valid.
- ✅ Unit testing Error Handling: simulasi exception berhasil ditangkap dan mengembalikan structured fallback tanpa 500 error.
- ✅ HTTP Integration Test: simulasi `POST /api/v1/nesai/chat` mengembalikan HTTP 200 dengan struktur `data: { answer, intent, sources, actions, mode }`.

## Belum selesai / blocker
- Menyiapkan nilai riil `GEMINI_API_KEY` di file `.env` untuk pengujian koneksi live ke Google Gemini.
