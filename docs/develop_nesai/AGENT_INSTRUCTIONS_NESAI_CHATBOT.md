# 🤖 AGENT INSTRUCTIONS — NESAI Chatbot Fix & Survival (READ FIRST)

> **File ini adalah instruksi operasional untuk AI coding agent.**
> Baca seluruh file SEBELUM mengubah kode. Patuhi semua RULE di bawah tanpa pengecualian.

---

## 0. ATURAN MUTLAK (NON-NEGOTIABLE RULES)

### 🚫 R1 — DILARANG menyentuh Git (commit/push/amend/branch)
- **JANGAN** jalankan: `git commit`, `git push`, `git amend`, `git rebase`, `git merge`,
  `git reset --hard`, `git checkout -b`, `git stash`, atau operasi yang mengubah history.
- **BOLEH** (read-only): `git status`, `git diff`, `git log`, `git show`, `git grep`.
- Tujuan: perubahan ditinjau manusia dulu. Serahkan keputusan commit/push ke pengguna.

### 🚫 R2 — DILARANG berhalusinasi / mengarang
- **JANGAN** mengarang nama file, nama class, nama method, nama env, endpoint, atau
  signature fungsi. **Verifikasi dengan membaca file** sebelum menulis/mengubah kode.
- Jika sebuah file/fungsi TIDAK ditemukan saat dibaca → **laporkan "tidak ditemukan"**,
  JANGAN berasumsi isinya.
- Jika ragu antara 2 kemungkinan → **sebutkan asumsinya secara eksplisit** dan minta konfirmasi.
- **JANGAN** mengklaim "sudah diperbaiki"/"sudah diuji" tanpa benar-benar menjalankan
  atau membaca hasilnya. Nyatakan status sebenarnya (mis. "belum diverifikasi").
- **JANGAN** menambah dependency/library baru tanpa meminta izin.
- Kutip lokasi (path + nomor baris) saat mereferensikan kode.

### 🚫 R3 — SCOPE HANYA CHATBOT NESAI
- Boleh disentuh HANYA file yang terkait langsung dengan fitur chatbot NESAI (lihat §3).
- **DILARANG** mengubah: CMS/admin (`src/app/(admin)`, `src/app/*/admin`), berita, jurusan,
  fasilitas, search, auth, layout global, styling unrelated, `package.json` deps, dsb.
- Jika perbaikan chatbot tampak butuh mengubah file di luar scope → **STOP dan tanyakan**
  ke pengguna terlebih dahulu. Jangan diam-diam melebar.

### 🚫 R4 — DILARANG mengubah konfigurasi produksi/publikasi
- Jangan deploy, jangan ubah DNS, jangan ubah konfigurasi server/VPS.
- Cukup buat perubahan kode/env yang diminta, lalu laporkan.

### ✅ R5 — Workflow wajib
1. Baca dulu, baru edit.
2. Perubahan minimal (smallest change yang menyelesaikan masalah).
3. Setelah edit, sebutkan: file yang diubah, ringkasan perubahan, cara verifikasi.
4. Jika perlu build/test, jalankan HANYA perintah aman (`npx tsc --noEmit`, `npm run build`)
   dan laporkan hasil apa adanya.

---

## 1. KONTEKS MASALAH (RINGKAS)

**Produk:** NESAI — chatbot virtual assistant SMKN 1 Subang.
**Stack:** FE Next.js (`D:\NEXTJS\nesaiFrontend`), BE Laravel (repo terpisah, boleh diubah oleh pengguna).
**Kondisi:** backend chat **SINKRON** + **Gemini Flash FREE tier**.
**Peristiwa:** akan diuji **stress test ONLINE pada tahap penyisihan lomba** (sifat: campuran
— bot load-test cepat + pengunjung nyata simultan).

**Dua masalah yang harus diselesaikan (terpisah, jangan dicampur):**

### MASALAH A (Frontend) — "hanya bisa 1 pesan, lalu fallback"
- Gejala: turn 1 jawab, turn 2 langsung `mode: "fallback-error"` (*"layanan AI sedang dalam penyesuaian"*).
- PENYEBAB (terverifikasi): field `history` **DIHAPUS** dari payload di `src/lib/api/nesai.ts`.
  Versi lama mengirim `{ message, history, context }`; versi sekarang hanya mengirim
  `{ message, context: string[], session_id, conversation_id }`.
- Detail lengkap: `docs/progress/NESAI_CHATBOT_ROOTCAUSE_FIX.md`.

### MASALAH B (Backend) — rentan mati saat stress test online
- PENYEBAB: request sinkron menunggu Gemini sampai selesai; kuota free habis / latency tinggi
  saat load test → worker jenuh → server terlihat DOWN.
- Detail lengkap: `docs/progress/NESAI_BACKEND_STRESS_TEST_SURVIVAL.md`.

---

## 2. TUGAS (URUTKAN, JANGAN LOMPAT)

### TUGAS A — FIX FRONTEND (kerjakan lebih dulu)
**Target:** multi-turn berfungsi lagi; payload mengirim `history` kembali.

**Langkah:**
1. Baca `src/lib/api/nesai.ts`, `src/hooks/useNesaiChat.ts`, `src/types/nesai.ts`.
2. Di `src/lib/api/nesai.ts`, pada blok `const payload = {...}`:
   - **Tambahkan kembali** field `history` (parameter `history: ChatHistoryItem[]`).
   - **JANGAN** isi `context` dengan array string balasan bot. Set `context: []`
     (atau biarkan sesuai kontrak yang dikonfirmasi backend).
   - Pertahankan: `credentials: 'include'`, `Idempotency-Key`, handling 429/403, timeout.
3. Di `src/hooks/useNesaiChat.ts`: pastikan `history` (array `{role, content}`) benar
   dibangun dari `messages` lalu **diteruskan** ke `sendNesaiMessage(...)`.
4. Verifikasi: `npx tsc --noEmit` (laporkan hasil).

**Kriteria sukses A:**
- Payload request memuat `history` berisi objek `{role, content}`.
- Turn 2 menjawab lanjutan, BUKAN fallback.
- Fitur sync tidak rusak: 429 cooldown, 403 origin, idempotency, `credentials`.

### TUGAS B — REKOMENDASI/PATCH BACKEND (sinkron dengan tim backend)
> Hanya kerjakan bila pengguna meminta bagian backend. Ikuti `NESAI_BACKEND_STRESS_TEST_SURVIVAL.md`.

**Urutan prioritas (P0 → P2):**
- P0.1: Fast-path DB untuk intent umum (jurusan/ppdb/kontak/fasilitas/berita/profil) → jawab TANPA Gemini.
- P0.2: Circuit breaker ke Gemini (fail≥5 → OPEN 60s → skip Gemini, balas fallback cepat).
- P0.3: Timeout provider diturunkan (30s → 10s).
- P0.4: Concurrency cap ke Gemini (maks 3-5 serentak).
- P1.5: Cache respons AI (TTL 24 jam).
- P1.6: Naikkan `CHAT_RATE_LIMIT_PER_IP` (10 → 60), longgarkan/nonaktif `CHAT_RATE_LIMIT_GLOBAL`.
- P1.7: Dedup hanya per-sesi, jangan lintas user.
- P2: batasi `history` ke Gemini; logging ringan.

**Kriteria sukses B:**
- Saat Gemini mati: respons tetap HTTP **200** + `mode:"fallback-error"`, **< 1s**, tanpa 5xx.
- Load test: 0 crash, 0 dead-lock, mayoritas request terlayani.
- Tidak ada request menunggu Gemini tanpa batas.

---

## 3. SCOPE FILE (boleh diubah vs DILARANG)

### ✅ BOLEH diubah (chatbot FE)
- `src/lib/api/nesai.ts`
- `src/hooks/useNesaiChat.ts`
- `src/types/nesai.ts`
- `src/components/nesai/*` (hanya bila perlu, mis. teks/badge fallback)
- File `.md` dokumentasi di `docs/progress/` (bila diminta)

### ✅ BOLEH diubah (chatbot BE — hanya bila diminta & repo backend tersedia)
- `routes/api.php` (bagian route chat saja)
- `app/Providers/AppServiceProvider.php` (bagian `configureRateLimiting` chat)
- `app/Http/Middleware/EnsureTrustedChatOrigin.php`
- `app/Services/NesaiService.php` / agent chat terkait
- `config/chat.php`, `.env` (bagian chat)

### 🚫 DILARANG diubah
- `src/app/(admin)/**` (CMS/admin)
- Fitur lain: berita, jurusan, fasilitas, search, ppdb, profil, kontak, prestasi, pkl
- `src/components/home/**`, `src/components/site/**`, `src/components/admin/**`
- `layout.tsx` global (kecuali benar-benar wajib & disetujui pengguna)
- `package.json` / `package-lock.json` (jangan tambah/hapus dependency tanpa izin)
- Konfigurasi VPS/server/DNS/deploy

> Jika menyentuh file di luar daftar BOLEH → **BERHENTI dan tanyakan** pengguna.

---

## 4. FORMAT LAPORAN (WAJIB setelah selesai)

Laporkan ringkas dengan struktur:
1. **File yang dibaca** (untuk verifikasi, bukan asumsi).
2. **File yang diubah** + ringkasan tiap perubahan (dengan path).
3. **Bukti/verifikasi**: perintah yang dijalankan + hasil (atau "belum diverifikasi").
4. **Asumsi** yang diambil (jika ada) — agar bisa dikoreksi.
5. **Yang TIDAK dikerjakan** (mis. backend, karena out of scope).
6. **Langkah berikutnya** untuk manusia.

---

## 5. CATATAN PENTING (jangan dilanggar)

- **JANGAN commit/push.** (R1)
- **JANGAN berasumsi.** Baca file dulu. (R2)
- **JANGAN melebar dari chatbot.** (R3)
- Perubahan sync FE (429/403/idempotency/credentials/timeout) **sudah benar** — jangan dihapus.
  Cukup **kembalikan `history`** dan perbaiki `context`.
- Utamakan **perubahan minimal** dan **tidak merusak** yang sudah jalan.
- Bila instruksi pengguna bertentangan dengan dokumen ini → **tanyakan**, jangan bertindak sepihak.
