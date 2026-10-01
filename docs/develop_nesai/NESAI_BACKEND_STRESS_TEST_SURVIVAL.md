# 🛡️ NESAI Backend — Survival Architecture untuk Stress Test Online (Tahap Penyisihan)

> **Konteks lomba:** Juri melakukan **stress test ONLINE pada tahap penyisihan** (belum grand final offline).
> Sifat uji: **campuran** (kemungkinan bot load-test cepat + pengunjung nyata simultan), dari jaringan luar.
> **Kondisi backend saat ini:** request **SINKRON** (user menunggu Gemini hingga selesai) +
> **Gemini Flash FREE tier**. Ini kombinasi paling rentan.
> **Scope:** HANYA endpoint chatbot NESAI (`POST /api/v1/nesai/chat` + alias `POST /api/chat`).
>
> Dokumen ini untuk sesi **BACKEND (Laravel)**. Pasangan dokumen FE:
> `docs/progress/NESAI_CHATBOT_ROOTCAUSE_FIX.md` (masalah `history` di payload).

---

## 1. Mengapa stress test online berbeda & berbahaya untuk setup ini

Pada tahap penyisihan, yang dinilai **bukan keindahan jawaban**, melainkan:
**uptime, latency, tidak crash, tidak dead-lock, tidak 5xx**.

### Failure mode nyata dengan "sync + Gemini free tier":

| # | Skenario | Akibat | Terlihat oleh juri |
| :-- | :-- | :-- | :-- |
| 1 | Kuota Gemini free habis (RPM/RPD) | Semua request lanjut coba ke Gemini, error → fallback | Jawaban selalu *"layanan AI sedang dalam penyesuaian"* |
| 2 | Gemini lambat (30s) × banyak request | Worker PHP-FPM/thread jenuh, request menumpuk | **502/504, server seperti DOWN** (fatal) |
| 3 | Rate limit per-IP (10/menit) | Load test dari 1-2 IP → mayoritas **429** | Juri catat "sering gagal / ditolak" |
| 4 | Duplikat "in-flight" | Dua request identik → yang kedua 429 | Bug terlihat saat klik ganda |
| 5 | Reconnect/timeout FE (45s) | Request nyangkut lama | Uji terasa "hang" |

> **Kesimpulan:** Prioritas utama bukan "hemat kuota" saja, tapi **"chatbot harus tetap hidup & cepat menjawab meski Gemini mati"**.

---

## 2. Target Arsitektur (prinsip)

```
                        ┌─────────────────────────────┐
   request chat  ─────▶ │  1. CACHE (hash pertanyaan)  │── hit ─▶ balas cepat (<50ms)
                        └───────────────┬─────────────┘
                                        │ miss
                        ┌───────────────▼─────────────┐
                        │  2. FAST-PATH DB (intent     │── dikenali ─▶ jawab dari DB,
                        │     umum: jurusan/ppdb/...)  │              TANPA Gemini
                        └───────────────┬─────────────┘
                                        │ tidak dikenali
                        ┌───────────────▼─────────────┐
                        │  3. CIRCUIT BREAKER          │── OPEN ─▶ langsung fallback DB,
                        │     (state ke Gemini)        │           JANGAN panggil Gemini
                        └───────────────┬─────────────┘
                                        │ CLOSED
                        ┌───────────────▼─────────────┐
                        │  4. AI (timeout ketat        │── sukses ─▶ simpan ke cache
                        │     + concurrency cap)       │
                        └─────────────────────────────┘
```

**Aturan emas:**
1. **Gemini adalah jalur TERAKHIR, bukan jalur wajib.**
2. **Semua respons = HTTP 200** (pakai `mode` untuk bedakan), bukan 5xx.
3. **Tidak ada request yang menunggu Gemini tanpa batas** (timeout ketat).
4. **Jumlah request serentak ke Gemini dibatasi** (concurrency cap).
5. **Rate limiter tidak boleh mendominasi** respons saat load test.

---

## 3. Perubahan Backend yang Dibutuhkan (prioritas)

### 🔴 P0 — WAJIB (tanpa ini, stres test berisiko fatal)

#### 3.1 Fast-path DB untuk intent umum
Pertanyaan yang paling sering dites (jurusan, PPDB, kontak/alamat, fasilitas, berita, profil)
→ **jawab dari database/config tanpa memanggil Gemini**.

- Deteksi intent dengan **keyword matching** (regex/strpos), bukan AI.
  - Contoh: kata kunci `jurusan|kompetensi|keahlian|pplg|tkj|mesin|otomotif|akuntansi` → intent `jurusan`.
  - `ppdb|spmb|pendaftaran|daftar|syarat` → intent `ppdb`.
  - `kontak|alamat|telepon|email|lokasi|peta` → intent `kontak`.
  - `fasilitas|lab|bengkel|sarana|perpustakaan` → intent `fasilitas`.
- Bila intent dikenal → ambil jawaban dari DB/config, isi `sources` & `actions` default,
  `mode: "ai-agent"` (atau mode baru `"database"`), **tanpa** sentuh Gemini.
- Hasil: 60-90% request uji tidak menyentuh AI sama sekali → kuota aman, latency <100ms.

#### 3.2 Circuit breaker ke Gemini
- Simpel tapi efektif: gunakan **Laravel Cache** untuk menyimpan status circuit.
- Aturan:
  - Setiap kali Gemini gagal (error/timeout/quota `429` dari Google) → tambah counter gagal.
  - Bila gagal berturut ≥ N (mis. **5**) dalam jendela waktu (mis. 60s) → **OPEN** selama T (mis. **60s**).
  - Saat **OPEN**: SKIP Gemini sepenuhnya → langsung balas fallback DB (`mode: "fallback-error"`) dalam <50ms.
  - Setelah T lewat → **half-open**: izinkan 1 request uji coba. Sukses → **CLOSED**; gagal → OPEN lagi.
- Efek: begitu kuota Gemini habis, **tidak ada lagi request yang menunggu Gemini** → server tidak jenuh.

#### 3.3 Timeout ketat ke provider
- Turunkan `CHAT_PROVIDER_TIMEOUT` dari 30s → **8-15s**.
- Alasan: pada load test, 30s per request membuat koneksi menumpuk.
  8-15s cukup untuk Gemini Flash menjawab normal; kalau lebih = lebih baik fallback cepat.

#### 3.4 Concurrency cap (semaphore ke Gemini)
- Batasi jumlah request **serentak** ke Gemini (mis. maks **3-5** bersamaan) pakai
  Cache lock (`Cache::lock()`) atau atomic counter.
- Bila penuh → langsung fast-path/fallback (jangan antre panjang, jangan menunggu).
- Efek: melindungi VPS & kuota dari lonjakan serentak.

### 🟠 P1 — SANGAT DISARANKAN

#### 3.5 Cache respons AI
- Key: `hash(normalized_question)` (+ opsional sesi bila jawaban kontekstual).
- TTL: **24 jam**. Simpan seluruh `data` (answer, intent, sources, actions, mode).
- Efek: pertanyaan berulang → 0 panggilan Gemini.

#### 3.6 Sesuaikan rate limiter
- **Naikkan** `CHAT_RATE_LIMIT_PER_IP` (mis. 10 → **60/menit**) — load test biasanya dari sedikit IP.
- **Naikkan atau nonaktifkan** `CHAT_RATE_LIMIT_GLOBAL` saat lomba (hindari penolakan massal).
- Pertimbangkan **tidak** menghitung fast-path (DB) ke limiter AI, atau limiter terpisah.
- Pastikan header `Retry-After` selalu ada pada 429 (sudah ada di FE handling).

#### 3.7 Nonaktifkan/lemahkan dedup "in-flight" yang agresif saat lomba
- Dedup berbasis **sesi** aman. Dedup **global** (lintas user) berbahaya untuk load test
  (dua juri kirim pertanyaan sama → yang kedua 429).
- Pastikan dedup hanya berlaku **per-sesi**, dalam jendela pendek (mis. 5s).

### 🟢 P2 — OPSIONAL (bila sempat)

- Batasi `history`/`context` yang dikirim ke Gemini (mis. hanya 2 turn terakhir) → hemat token input.
- Logging ringan: jangan tulis log verbose per request (I/O bisa jadi bottleneck saat beban tinggi).
- Model mapping: Gemini Flash untuk default; sediakan opsi fallback ke model lain bila ada.

---

## 4. Environment / Konfigurasi (checklist `config/chat.php` & `.env`)

| Variabel | Nilai saat lomba | Alasan |
| :-- | :-- | :-- |
| `CHAT_PROVIDER_TIMEOUT` | `10` (dari 30) | Cegah penumpukan koneksi |
| `CHAT_RATE_LIMIT_PER_IP` | `60` | Load test dari sedikit IP |
| `CHAT_RATE_LIMIT_GLOBAL` | `0`/tinggi (atau disabled) | Hindari 429 massal |
| Rate limit `/search`, `/recommendations` | naikkan bila ikut diuji | Hindari penolakan |
| `CHAT_CIRCUIT_FAIL_THRESHOLD` | `5` | Ambang buka circuit |
| `CHAT_CIRCUIT_OPEN_SECONDS` | `60` | Durasi skip Gemini |
| `CHAT_MAX_CONCURRENCY` | `3` s.d. `5` | Batas serentak ke Gemini |
| `CHAT_CACHE_TTL` | `86400` (24 jam) | Cache respons |
| `CHAT_TRUSTED_ORIGINS` | origin FE prod (koma) | Fail-closed; salah = **403 semua** |
| `FRONTEND_URL` | origin FE prod | CORS |
| `GEMINI_API_KEY` | **tier berbayar bila bisa** | Free tier sangat cepat habis saat stress test |
| `QUEUE_CONNECTION` | (bila pindah async) `database`/`redis` | Lihat §5 |

> ⚠️ Setelah mengubah `.env`, jalankan `php artisan config:cache` **SETELAH** nilai benar.

---

## 5. Soal "sinkron vs queue" (penting)

Setup sekarang **sinkron**. Idealnya chat AI dipindah ke **queue + polling/websocket**,
tapi itu perubahan besar & berisiko **mendekati hari H**. Rekomendasi:

- **Untuk penyisihan:** tetap sinkron **TAPI** dengan P0 lengkap
  (fast-path DB + circuit breaker + timeout ketat + concurrency cap).
  Ini sudah cukup membuat server **tetap hidup & responsif** meski Gemini mati.
- **Jika ada waktu lebih:** pertimbangkan queue hanya untuk jalur AI (fallback ke DB bila antrean penuh).
- **Jangan** lakukan refactor besar tanpa waktu uji. Risiko > manfaat.

---

## 6. Skenario Uji Mandiri (lakukan SEBELUM submit penyisihan)

Lakukan pada staging/dengan API key **terpisah** (agar kuota produksi tidak habis):

1. **Uji normal:** 1 pesan → jawab dari AI/DB, < 15s.
2. **Simulasi kuota habis:** set API key invalid / blokir keluar ke Google → pastikan:
   - respons tetap **200** dengan `mode: "fallback-error"`, **< 1s**, server tidak crash.
   - circuit breaker terbuka setelah 5 gagal.
3. **Load test ringan:** 50-100 request chat bersamaan (mis. `autocannon`/`k6`/`hey`)
   - Pastikan tidak ada 5xx, latency terkendali, CPU/RAM VPS wajar.
   - Cek mayoritas request terlayani (bukan 429).
4. **Duplikat:** kirim 2 request identik dari **satu sesi** cepat → maks 1 diproses AI.
5. **Rate limit:** pastikan 429 punya `Retry-After` & pesan ramah.
6. **403 origin:** uji dengan origin salah → pesan konfigurasi (bukan crash).
7. **Pantau VPS:** `htop`, `php-fpm` status, koneksi aktif — pastikan tidak saturasi.

---

## 7. Definition of Done (Backend)

- [ ] Intent umum (jurusan/PPDB/kontak/fasilitas/berita/profil) dijawab **tanpa Gemini**.
- [ ] Circuit breaker aktif: Gemini mati → respons < 1s, `200 + fallback-error`.
- [ ] `CHAT_PROVIDER_TIMEOUT` ≤ 15s.
- [ ] Concurrency ke Gemini dibatasi.
- [ ] Cache respons AI aktif (TTL 24 jam).
- [ ] `CHAT_RATE_LIMIT_PER_IP` dinaikkan; global dilonggarkan/nonaktif.
- [ ] Dedup hanya per-sesi (tidak lintas user).
- [ ] Semua error = HTTP 200 dengan `mode` yang sesuai (tidak ada 5xx ke user).
- [ ] Load test mandiri lulus: 0 crash, 0 dead-lock, mayoritas request terlayani.
- [ ] FE mengirim `history` dengan benar (lihat `NESAI_CHATBOT_ROOTCAUSE_FIX.md`).

---

## 8. Ringkasan Eksekutif (untuk dibaca cepat di sesi baru)

> **Masalah:** Backend sinkron + Gemini free tier → saat stress test online, kuota habis &
> request menunggu Gemini → server jenuh, terlihat down, jawaban fallback semua.
>
> **Solusi inti:** Jadikan **DB sebagai jawaban utama** untuk intent umum, **Gemini hanya
> jalur terakhir**, lindungi dengan **circuit breaker + timeout ketat + concurrency cap +
> cache**, dan **longgarkan rate limit** agar load test tidak ditolak.
>
> **Hasil:** Skenario terburuk (Gemini mati) → server tetap **200 OK, cepat, tidak crash**.
> Itulah yang membuat skor stress test penyisihan bagus.

---

## 9. Referensi file

- `routes/api.php` — route chat + throttle.
- `app/Providers/AppServiceProvider.php` — `configureRateLimiting()`.
- `app/Http/Middleware/EnsureTrustedChatOrigin.php` — origin guard.
- `config/chat.php` — rate limit, timeout, idempotency, trusted origins.
- `app/Services/NesaiService.php` / `SchoolAssistantAgent` — orkestrasi AI & tools.
- FE pasangan: `docs/progress/NESAI_CHATBOT_ROOTCAUSE_FIX.md`.
- FE API client: `src/lib/api/nesai.ts` (timeout 45s, 429/403 handling).
