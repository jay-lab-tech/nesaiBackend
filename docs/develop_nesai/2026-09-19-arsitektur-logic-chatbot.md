# Task: Perancangan Arsitektur Logic Chatbot AI & Tagging Core Logic

**Tanggal:** 2026-09-19  
**Branch:** `feature/nesai-api`  
**Role:** Senior Backend Developer

## Task
Merancang arsitektur logic komprehensif untuk Chatbot AI NESAI (SMKN 1 Subang) untuk kebutuhan lomba web development JHIC 2.0, menyematkan tag komentar arsitektural (`[CORE-LOGIC: ...]`) pada file-file inti logic, dan memastikan ketersediaan endpoint `POST /api/chat`.

## File dibuat/diubah
- `routes/api.php` — **diubah** (menambahkan endpoint langsung `POST /api/chat` dengan tag arsitektur)
- `app/Ai/Agents/SchoolAssistantAgent.php` — **diubah** (menambahkan tag komentar `[CORE-LOGIC: AGENT-PROVIDER-CONFIG]`, `[CORE-LOGIC: AGENT-SYSTEM-PROMPT]`, `[CORE-LOGIC: AGENT-TOOL-BINDING]`)
- `app/Ai/Tools/GetJurusanInfoTool.php` — **diubah** (menambahkan tag komentar `[CORE-LOGIC: ALIAS-NORMALIZATION]`, `[CORE-LOGIC: STATIC-CONFIG-SOURCE]`, `[CORE-LOGIC: FUZZY-SEARCH-FILTER]`, `[CORE-LOGIC: TOOL-JSON-SCHEMA]`)
- `app/Ai/Tools/NavigateToPageTool.php` — **diubah** (menambahkan tag komentar `[CORE-LOGIC: ROUTE-DICTIONARY]`, `[CORE-LOGIC: ROUTE-NORMALIZATION-LOGIC]`, `[CORE-LOGIC: STRUCTURED-ACTION-PAYLOAD]`, `[CORE-LOGIC: TOOL-JSON-SCHEMA]`)
- `app/Services/Nesai/NesaiService.php` — **diubah** (menambahkan tag komentar `[CORE-LOGIC: AI-AGENT-INVOCATION]`, `[CORE-LOGIC: TOOL-RESULT-EXTRACTION]`, `[CORE-LOGIC: INTENT-NORMALIZATION]`, `[CORE-LOGIC: RESILIENT-FALLBACK-GUARD]`)
- `docs/develop_nesai/2026-09-19-arsitektur-logic-chatbot.md` — **dibuat** (dokumentasi arsitektur dan changelog)

## Keputusan yang diambil

### 1. Verifikasi Paket Resmi `laravel/ai`
Paket yang terpasang adalah `laravel/ai v0.11.2` dan memerlukan PHP `>= 8.4.1`. Penggunaan enum `Laravel\Ai\Enums\Lab::Gemini` dan attribute `#[Provider(Lab::Gemini)]` di `SchoolAssistantAgent` sudah sesuai dengan spesifikasi resmi package tanpa hardcoded string.

### 2. Standardisasi Tag Komentar `[CORE-LOGIC: ...]`
Untuk mempermudah pemahaman seluruh anggota tim lomba JHIC 2.0, core logic ditandai dengan label konsisten:
- `[CORE-LOGIC: AGENT-PROVIDER-CONFIG]`: Penegasan konfigurasi provider AI via Enum.
- `[CORE-LOGIC: AGENT-SYSTEM-PROMPT]`: Guardrail LLM agar tidak halusinasi di luar konteks sekolah dan bilingual ID/EN.
- `[CORE-LOGIC: AGENT-TOOL-BINDING]`: Registrasi tool `get_jurusan_info` dan `navigate_to_page`.
- `[CORE-LOGIC: STATIC-CONFIG-SOURCE]`: Pembacaan data jurusan dari `config/jurusan.php` tanpa beban database.
- `[CORE-LOGIC: ALIAS-NORMALIZATION]`: Normalisasi singkatan (TKJ, RPL/PPLG, OTKP/MPLB) agar model akurat mencari jurusan.
- `[CORE-LOGIC: ROUTE-DICTIONARY]`: Kamus rute frontend Next.js untuk auto-navigation.
- `[CORE-LOGIC: AI-AGENT-INVOCATION]`: Eksekusi non-streaming agent.
- `[CORE-LOGIC: TOOL-RESULT-EXTRACTION]`: Pengubahan eksekusi tool menjadi array JSON `actions` & `sources`.
- `[CORE-LOGIC: RESILIENT-FALLBACK-GUARD]`: Fallback cerdas jika API key bermasalah atau rate limit 429 saat demo juri.

### 3. Dukungan Rute `POST /api/chat`
Di samping rute versi sebelumnya (`POST /api/v1/nesai/chat`), ditambahkan rute langsung `POST /api/chat` pada `routes/api.php` agar persis sesuai kontrak frontend lomba.

## Verifikasi
- ✅ Verifikasi versi package: `composer show laravel/ai` -> `v0.11.2`.
- ✅ Verifikasi sintaks PHP 8.4: Seluruh 5 file yang diubah (`SchoolAssistantAgent.php`, `GetJurusanInfoTool.php`, `NavigateToPageTool.php`, `NesaiService.php`, `routes/api.php`) lulus pengecekan sintaks `php -l` tanpa error.

## Belum selesai / blocker (Butuh Keputusan User)
1. **Conversation Memory**: Belum ditentukan apakah menggunakan Laravel Session (ringan, stateful cookie) atau tabel database `agent_conversations` (menggunakan trait `RemembersConversations` bawaan `laravel/ai` yang stateless via conversation_id/token untuk API Next.js).
2. **Kondisi Penggunaan Free Tier Gemini**: Apakah free tier Gemini ini hanya untuk tahap dev lokal atau juga akan digunakan saat live demo/penjurian lomba JHIC 2.0 (menentukan apakah perlu dibuatkan rate-limiting handler, retry backoff, atau response caching sekarang).
