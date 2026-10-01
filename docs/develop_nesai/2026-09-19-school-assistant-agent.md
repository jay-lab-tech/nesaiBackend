# Task: Implementasi SchoolAssistantAgent

**Tanggal:** 2026-09-19  
**Branch:** `feature/nesai-api`

## Task
Implementasi `SchoolAssistantAgent` — menulis system instructions yang spesifik untuk SMKN 1 Subang, wiring tools (`GetJurusanInfoTool`, `NavigateToPageTool`), dan setting provider ke Gemini via `#[Provider]` attribute.

## File dibuat/diubah
- `app/Ai/Agents/SchoolAssistantAgent.php` — **diubah** (overwrite dari skeleton stub)

## Keputusan yang diambil

### 1. Provider via `#[Provider]` attribute, bukan method `provider()`
Package `laravel/ai` v0.11.2 mendukung dua cara set provider: method `provider()` atau PHP attribute `#[Provider(Lab::Gemini)]`. Dipilih attribute karena:
- Lebih deklaratif dan terlihat langsung di class declaration
- Sesuai pattern yang digunakan di contoh bawaan package (`#[UseCheapestModel]` di `SummarizeAgent`)
- Tidak perlu override method

### 2. Drop interface `Conversational`, tetap `Agent` + `HasTools`
Skeleton sebelumnya implements `Conversational` dengan `messages()` yang return `[]`. Karena keputusan user adalah stateless dulu (no conversation memory), interface `Conversational` di-drop agar tidak menyesatkan — nanti ditambahkan kembali saat conversation memory diimplementasi.

### 3. System instructions bilingual
Instruksi ditulis dalam bahasa Indonesia, dengan aturan: "Jika pengguna bertanya dalam bahasa Inggris, jawab dalam bahasa Inggris." Sesuai keputusan user.

### 4. Tool naming convention
Dalam system instructions, tools direferensikan sebagai `get_jurusan_info` dan `navigate_to_page` (snake_case). Ini mengikuti konvensi `laravel/ai` yang menggunakan `ToolNameResolver` untuk men-derive nama tool dari class name.

## Verifikasi
- ✅ PHP syntax check: `php -l` — no errors
- ✅ Laravel bootstrap test: class instantiable, implements `Agent` dan `HasTools`, tools count = 2, provider attribute = `gemini`

## Belum selesai / blocker
- `GetJurusanInfoTool` dan `NavigateToPageTool` masih stub — belum ada logic. Ini task berikutnya.
- `NesaiController` / `NesaiService` belum di-rewire untuk pakai `SchoolAssistantAgent` (masih pakai stub service layer lama)