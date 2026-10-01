# 🔎 Root Cause: NESAI Chatbot "Hanya Bisa 1 Pesan" Setelah Sync Backend

> **Scope:** HANYA chatbot NESAI (`src/lib/api/nesai.ts`, `src/hooks/useNesaiChat.ts`,
> `src/types/nesai.ts`). Jangan melebar ke CMS/admin/search.
> **Branch yang bermasalah:** `sync/ai` (perubahan masih di working tree, belum di-commit).
> **Dokumen ini untuk dibaca di sesi baru agar perbaikan fokus & konsisten.**

---

## 1. Gejala (Gejala yang dilaporkan)

1. Turn pertama (mis. *"jurusan apa yang paling cocok?"*) → NESAI menjawab normal. ✅
2. Turn kedua (mis. *"saya ingin menjadi programmer hebat"*) → NESAI **langsung fallback**
   dengan teks: *"Halo! Saya NESAI, asisten virtual SMKN 1 Subang. Saat ini layanan AI
   sedang dalam penyesuaian. Anda dapat menanyakan seputar jurusan dan PPDB..."* ❌
3. Sebelum perubahan sync → multi-turn **berfungsi normal**.

---

## 2. Root Cause (Akar Masalah)

### 🔴 Penyebab Utama: Field `history` DIHAPUS dari request payload

Perubahan `sync/ai` mengganti cara frontend mengirim konteks multi-turn ke backend.

**Versi yang MASIH BERFUNGSI** (branch `feature/nesai-chat-ui` / commit `fc9097c`):

```ts
// src/lib/api/nesai.ts
body: JSON.stringify({
  message: message.trim(),
  history,                 // ← array objek {role, content} — memory multi-turn
  context: context || [],  // ← objek NesaiContext (info halaman)
})
```

**Versi BERMASALAH** (working tree `sync/ai`):

```ts
// src/lib/api/nesai.ts (baris ~87-103)
const payload = {
  message: message.trim(),
  // history DIHAPUS TOTAL
  context: history               // ← array STRING (bukan objek)
    .slice(-5)
    .map((item) => item.content.slice(0, 500))
    .filter((content) => content.trim() !== ''),
};
if (sessionId) {
  payload.session_id = sessionId;        // ← asumsi backend melacak riwayat via session
  payload.conversation_id = sessionId;
}
```

**Akar masalahnya:** perubahan ini bekerja di bawah **asumsi** (lihat komentar kode baris
88-93 `src/lib/api/nesai.ts`) bahwa:

> *"Backend mengelola riwayat multi-turn sendiri via `session_id` (ChatSession/ChatMessage),
> sehingga `context` cukup berisi potongan teks terakhir sebagai petunjuk."*

Asumsi tersebut **TIDAK terverifikasi**. Buktinya:
- Backend yang berjalan tetap bergantung pada field `history` (array `{role, content}`)
  untuk membangun percakapan multi-turn.
- Ketika `history` tidak dikirim, backend **kehilangan konteks dialog** → gagal menyusun
  jail/percakapan → memicu **fallback** (`mode: "fallback-error"`) dengan pesan
  *"layanan AI sedang dalam penyesuaian"*.
- Turn pertama tetap sukses karena backend masih bisa menjawab tanpa konteks sebelumnya;
  turn kedua butuh konteks → gagal.

### 🟠 Faktor Kontributor (memperparah / masking)

1. **`context` diubah tipe**: dari `object` (NesaiContext) → `array of string`
   (isi teks balasan sebelumnya termasuk markdown panjang yang di-`slice(0,500)`).
   Ini menggandakan teks jawaban bot sebagai "konteks" → bisa memicu HTTP 422 validasi
   di backend (bila skema `context` backend masih mengharapkan objek), lalu jatuh ke fallback.

2. **`session_id` / `conversation_id` dikirim dari client** (`nesai_sess_<timestamp>_<rand>`).
   Bila backend membuat sesi sendiri (returned session id) dan **tidak** mengenali id buatan
   client, maka `session_id` tidak ada gunanya — memperkuat hilangnya konteks.

3. **`context` lama (NesaiContext) hilang** — frontend kehilangan mekanisme "active context"
   halaman (topic/major/path) yang sebelumnya dikirim `setActiveContext`. Ini menurunkan
   kualitas jawaban kontekstual (mis. saat user berada di `/jurusan/pplg`).

4. **`types/nesai.ts`**: `NesaiContext` **dihapus**, `ChatMessage.context` dihapus →
   jalur context halaman hilang dari tipe.

---

## 3. Bukti (Evidence Ringkas)

| Artefak | Isi |
| :-- | :-- |
| `git show fc9097c:src/lib/api/nesai.ts` | Mengirim `{ message, history, context }` |
| Working tree `src/lib/api/nesai.ts` | Mengirim `{ message, context: string[], session_id, conversation_id }`; `history` hilang |
| `git diff` `sync/ai` `nesai.ts` | Baris `- history,` dan `- context: history,` → diganti `context: <string[]>` |
| `git diff` `sync/ai` `types/nesai.ts` | `NesaiContext` + `ChatMessage.context` dihapus |
| Waktu kemunculan bug | Tepat setelah commit sync (`branch sync/ai`) |

---

## 4. Arah Perbaikan (REKOMENDASI)

### ✅ Opsi A (PALING AMAN — disarankan): Kembalikan field `history`

Pertahankan seluruh perbaikan sync (429/403/credentials/idempotency/timeout) **TANPA**
menghapus `history`. Yaitu, kirim KEDUANYA: `history` (untuk multi-turn) dan `context`
(opsional).

**`src/lib/api/nesai.ts`** — ubah blok payload menjadi:

```ts
const payload: Record<string, unknown> = {
  message: message.trim(),
  // WAJIB: pertahankan history {role, content} untuk memory multi-turn.
  history,                       // array ChatHistoryItem[]
  // context (opsional) — JANGAN kirim array string balasan bot.
  // Kirim hanya jika backend mendukungnya, atau kosongkan.
  context: [],
};

if (sessionId) {
  payload.session_id = sessionId;
  payload.conversation_id = sessionId;
}
```

> Catatan: JANGAN isi `context` dengan `history.map(...)`. Bila backend ingin `context`
> sebagai array string petunjuk singkat, kirim HANYA pesan user terakhir, bukan balasan bot.

**`src/hooks/useNesaiChat.ts`** — pastikan `history` diteruskan sesuai versi lama:

```ts
const history: ChatHistoryItem[] = messages
  .filter((m) => m.id !== 'welcome' && !m.isError && m.text.trim())
  .slice(-10)
  .map((m) => ({
    role: m.sender === 'user' ? 'user' : 'assistant',
    content: m.text,
  }));

const response = await sendNesaiMessage(text, history, sessionId, { idempotencyKey });
```

> Pastikan `sendNesaiMessage` benar-benar memakai parameter `history` (parameter ini masih
> ada di signature tetapi **diabaikan** pada versi bermasalah).

### ✅ Opsi B: Verifikasi dulu kontrak backend

Sebelum "menebak", lakukan 3 hal:
1. **Cek kode backend** (`NesaiChatController` / `NesaiService` / `SchoolAssistantAgent`):
   - Apakah request rule memvalidasi `history`? `context`? `session_id`?
   - Apakah ada model `ChatSession`/`ChatMessage` yang benar-benar dipakai?
2. **Cek Network tab** di browser:
   - Request payload turn 2 → apakah `history` kosong/tidak ada?
   - Response turn 2 → `data.mode` = `"fallback-error"`? atau HTTP 422/500?
3. **Cek log backend** saat turn 2.

Jika backend **memang** sudah mendukung `session_id` penuh → berarti bug bukan di sini,
lanjut ke investigator backend. Tetapi gejala "berfungsi sebelum sync, rusak setelah sync"
saat `history` dihapus = arah kuat ke Opsi A.

### 🔧 Perbaikan Pendukung

- Pulihkan `NesaiContext` di `src/types/nesai.ts` bila context halaman masih diinginkan.
- `context` request **jangan** diisi array string balasan bot; bila backend hanya menerima
  string[], kirim minimal (mis. 1 pesan user terakhir) — bukan 5 balasan panjang.

---

## 5. Definition of Done (Verifikasi Sesi Baru)

- [ ] Turn 1: tanya jurusan → jawaban normal (`mode: ai-agent`).
- [ ] Turn 2: *"saya ingin menjadi programmer hebat"* → **NESAI menjawab lanjutan**,
      BUKAN fallback "layanan AI sedang dalam penyesuaian".
- [ ] Multi-turn 3-4 pesan berurutan tetap kontekstual.
- [ ] Payload request memuat `history` (array `{role, content}`).
- [ ] Tidak ada regresi pada fitur sync: 429 cooldown, 403 origin, `credentials: 'include'`,
      `Idempotency-Key`, badge `fallback-error`.
- [ ] `npx tsc --noEmit` bersih.

---

## 6. Referensi File (scope chatbot)

- `src/lib/api/nesai.ts` — payload & endpoint (PUSAT masalah).
- `src/hooks/useNesaiChat.ts` — pembangun `history` & pemanggilan API.
- `src/types/nesai.ts` — tipe `ChatHistoryItem`, `NesaiContext`, `ChatMessage`.
- `nesai-frontend-backend-sync.md` — dokumen sync backend (asumsi `session_id` ada di sini).
- Komit pembanding yang masih berfungsi: `fc9097c` (*"perbaikan ui nesai chat"*).
