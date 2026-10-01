# 🤖 NESAI Chatbot — Frontend Implementation & Integration Guide

Dokumen ini adalah panduan spesifikasi teknis dan implementasi antarmuka untuk **NESAI (Asisten Virtual SMKN 1 Subang)** di repository frontend (Next.js / React).

> **Untuk AI Coding Agent di Sesi Frontend:**  
> Harap ikuti spesifikasi endpoint, struktur data, tipe TypeScript, dan panduan komponen di bawah ini untuk mengimplementasikan fitur chatbot NESAI secara komprehensif, responsif, dan interaktif.

---

## 1. Ikhtisar & Arsitektur Fitur

NESAI adalah chatbot cerdas asisten resmi SMKN 1 Subang yang ditenagai oleh backend Laravel (`laravel/ai` + Google Gemini). Backend bertindak sebagai orkestrator yang tidak hanya menghasilkan teks jawaban, namun juga mendeteksi *intent*, mengekstrak referensi sumber informasi (*sources*), dan menyediakan aksi navigasi interaktif (*actions*) untuk mengarahkan pengunjung website langsung ke halaman yang relevan (misal: halaman jurusan, PPDB, profil sekolah).

```
┌────────────────────────────────────────────────────────┐
│               Frontend (Next.js / React)               │
│  - Floating Chat Widget / Modal Popup                  │
│  - Markdown Renderer (teks jawaban)                    │
│  - Interactive Action Chips (router.push ke halaman)   │
│  - Source Badges & Quick Suggestions                   │
└───────────────────────────┬────────────────────────────┘
                            │ HTTP POST (JSON)
                            ▼
┌────────────────────────────────────────────────────────┐
│               Backend Laravel (Port 8000)              │
│  - POST /api/v1/nesai/chat                             │
│  - Controller -> NesaiService -> SchoolAssistantAgent  │
│  - Tools: get_jurusan_info & navigate_to_page          │
└────────────────────────────────────────────────────────┘
```

---

## 2. Spesifikasi API Backend

### Endpoint
- **URL:** `{API_BASE_URL}/api/v1/nesai/chat`  
  *(Contoh default lokal: `http://localhost:8000/api/v1/nesai/chat`)*
- **Method:** `POST`
- **Headers:**
  ```http
  Content-Type: application/json
  Accept: application/json
  ```

---

### Request Payload

```json
{
  "message": "Halo, apa saja jurusan yang ada di SMKN 1 Subang?",
  "context": []
}
```

| Field | Tipe | Wajib? | Deskripsi |
| :--- | :--- | :--- | :--- |
| `message` | `string` | **Ya** | Teks pertanyaan dari pengguna (maks. 2000 karakter). |
| `context` | `array` / `object` | Opsional | Konteks tambahan atau riwayat pesan jika dibutuhkan (default: `[]`). |

---

### Response Payload (HTTP 200 OK)

Format respons sukses dari backend mengikuti struktur resource standar Laravel:

```json
{
  "data": {
    "answer": "Halo! Di SMKN 1 Subang terdapat beberapa kompetensi keahlian unggulan, antara lain:\n\n1. **PPLG** (Pengembangan Perangkat Lunak dan Gim)\n2. **TKJ** (Teknik Komputer Jaringan)\n3. **Teknik Mesin**\n4. **Teknik Otomotif**\n5. **Akuntansi Keuangan Lembaga**\n6. **Manajemen Perkantoran dan Layanan Bisnis**\n7. **Pemasaran**\n8. **Desain Komunikasi Visual (DKV)**\n9. **Teknik Logistik**\n10. **Kuliner**\n\nUntuk melihat rincian setiap jurusan, silakan kunjungi halaman jurusan di bawah ini.",
    "intent": "school_navigation",
    "sources": [
      "Informasi Jurusan SMKN 1 Subang (config/jurusan.php)"
    ],
    "actions": [
      {
        "type": "navigate",
        "path": "/jurusan",
        "title": "Daftar Jurusan / Kompetensi Keahlian"
      }
    ],
    "mode": "ai-agent"
  },
  "meta": {},
  "message": null
}
```

#### Penjelasan Properti `data`:
1. **`answer`** (`string`): Teks respon utama dari AI dalam format **Markdown** (mendukung bold, bullet points, angka, dll).
2. **`intent`** (`string`): Kategori intent yang terdeteksi, contoh:
   - `school_navigation` (pertanyaan yang memicu aksi menuju halaman tertentu)
   - `school_information` (pertanyaan informasi seputar sekolah)
   - `general` (sapaan / percakapan umum)
3. **`sources`** (`string[]`): Daftar referensi data yang digunakan (misal data konfigurasi jurusan). Ditampilkan sebagai *badge/sumber referensi* di bawah pesan bot.
4. **`actions`** (`Array<{ type: string, path: string, title: string }>`): Aksi interaktif yang disarankan. Saat ini tipe yang didukung adalah `'navigate'`:
   - `type`: `'navigate'`
   - `path`: URL path internal di frontend (contoh: `/jurusan`, `/ppdb`, `/profil#fasilitas`, `/berita`)
   - `title`: Label ramah tombol navigasi
5. **`mode`** (`string`):
   - `'ai-agent'`: Dihasilkan sukses oleh Gemini Agent.
   - `'fallback-error'`: Respon cadangan ramah pengguna jika terjadi gangguan koneksi/kuota AI backend (tetap mengembalikan tombol navigasi default).

---

### Response Error

#### Validasi Gagal (HTTP 422 Unprocessable Content)
```json
{
  "message": "The message field is required.",
  "errors": {
    "message": [
      "The message field is required."
    ]
  }
}
```

#### Server / Network Error (HTTP 500 atau Fetch Failed)
Frontend harus menangani error secara elegan dengan menampilkan pesan kesalahan inline dan opsi tombol "Coba Lagi".

---

## 3. Definisi Tipe Data TypeScript

Buat file baru di frontend, misalnya `types/nesai.ts`:

```typescript
export interface NesaiAction {
  type: 'navigate' | string;
  path: string;
  title: string;
}

export interface NesaiChatData {
  answer: string;
  intent: string;
  sources: string[];
  actions: NesaiAction[];
  mode: 'ai-agent' | 'fallback-error' | string;
}

export interface NesaiChatResponse {
  data: NesaiChatData;
  meta: Record<string, unknown>;
  message: string | null;
}

export interface ChatMessage {
  id: string;
  sender: 'user' | 'nesai';
  text: string;
  createdAt: Date | string;
  intent?: string;
  sources?: string[];
  actions?: NesaiAction[];
  isError?: boolean;
}
```

---

## 4. Rute Halaman Frontend Terkait (Target Aksi Navigasi)

Backend telah memetakan rute internal website SMKN 1 Subang yang mungkin dikembalikan dalam `actions[].path`. Pastikan frontend mendukung atau mengarahkan ke rute-rute ini:

| Path | Keterangan |
| :--- | :--- |
| `/` | Beranda Sekolah |
| `/jurusan` | Daftar Jurusan / Program Keahlian |
| `/jurusan/[slug]` | Detail jurusan tertentu (misal: `/jurusan/pplg`, `/jurusan/tkj`) |
| `/ppdb` | Informasi dan alur PPDB |
| `/profil` | Profil sekolah & fasilitas (`/profil#fasilitas`, `/profil#visi-misi`) |
| `/berita` | Berita & agenda sekolah |
| `/alumni` | Jejak alumni |
| `/kontak` | Kontak dan peta lokasi sekolah |

---

## 5. Rancangan Komponen UI / UX

Frontend disarankan mengimplementasikan chatbot sebagai **Floating Action Widget** di pojok kanan bawah:

### Struktur Hierarki Komponen
```
components/nesai/
├── NesaiChatWidget.tsx      # Komponen utama: floating button + container modal
├── NesaiHeader.tsx          # Bar judul, status bot, tombol minimize/close, tombol clear
├── NesaiMessageList.tsx      # Container pesan yang auto-scroll ke bawah
├── NesaiMessageItem.tsx      # Bubble pesan (User vs NESAI) + Render Markdown
├── NesaiActionChips.tsx     # Tombol navigasi rute (dari respons `actions`)
├── NesaiSourceBadges.tsx    # Informasi sumber referensi (`sources`)
├── NesaiQuickReplies.tsx    # Tombol pertanyaan cepat saat awal buka
├── NesaiTypingIndicator.tsx # Indikator animasi 3 titik saat menunggu balasan
└── NesaiChatInput.tsx       # Form input teks, tombol kirim, validasi panjang teks
```

### Rekomendasi Fitur UI
1. **Floating Trigger Button:**
   - Ikon robot / chat dengan efek badge notifikasi di pojok kanan bawah (`fixed bottom-6 right-6 z-50`).
   - Animasi buka/tutup yang halus (*smooth transition*).
2. **Pesan Selamat Datang & Quick Prompts:**
   - Ketika chat pertama kali dibuka, berikan sapaan selamat datang dari NESAI.
   - Sediakan tombol quick suggestions, contoh:
     - *"Apa saja jurusan di SMKN 1 Subang?"*
     - *"Kapan PPDB dibuka & apa persyaratannya?"*
     - *"Di mana alamat dan kontak SMKN 1 Subang?"*
3. **Format Jawaban:**
   - Render menggunakan library Markdown (seperti `react-markdown`) agar list, paragraf, link, dan cetak tebal terlihat rapi.
4. **Tombol Aksi Navigasi:**
   - Jika pesan NESAI menyertakan `actions`, render sebagai tombol/chip (misal: *"🔗 Buka Halaman PPDB"* atau *"👉 Lihat Daftar Jurusan"*).
   - Ketika diklik, gunakan Next.js Router (`router.push(action.path)`) atau komponen `<Link href={action.path}>`.
5. **Indikator Loading:**
   - Tampilkan animasi *"NESAI sedang mengetik..."* saat request API sedang berlangsung.
   - Nonaktifkan input dan tombol kirim selama proses fetch.
6. **Penyimpanan Riwayat Sesi (Opsional tapi disarankan):**
   - Simpan riwayat chat sementara di `sessionStorage` agar percakapan tidak hilang saat user berpindah halaman melalui tombol navigasi chatbot.

---

## 6. Contoh Implementasi Service / Hook di Frontend

### File: `lib/api/nesai.ts`
```typescript
import { NesaiChatResponse } from '@/types/nesai';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

export async function sendNesaiMessage(message: string): Promise<NesaiChatResponse> {
  const response = await fetch(`${API_BASE_URL}/api/v1/nesai/chat`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      message: message.trim(),
      context: [],
    }),
  });

  if (!response.ok) {
    const errorData = await response.json().catch(() => null);
    throw new Error(errorData?.message || `Gagal menghubungi NESAI (${response.status})`);
  }

  return response.json();
}
```

### File: `hooks/useNesaiChat.ts`
```typescript
import { useState, useCallback } from 'react';
import { ChatMessage } from '@/types/nesai';
import { sendNesaiMessage } from '@/lib/api/nesai';

export function useNesaiChat() {
  const [messages, setMessages] = useState<ChatMessage[]>([
    {
      id: 'welcome',
      sender: 'nesai',
      text: 'Halo! Saya **NESAI**, asisten virtual SMKN 1 Subang. Ada yang bisa saya bantu terkait jurusan, PPDB, atau info sekolah lainnya?',
      createdAt: new Date(),
    },
  ]);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  const sendMessage = useCallback(async (text: string) => {
    if (!text.trim() || isLoading) return;

    const userMessage: ChatMessage = {
      id: `user-${Date.now()}`,
      sender: 'user',
      text: text.trim(),
      createdAt: new Date(),
    };

    setMessages((prev) => [...prev, userMessage]);
    setIsLoading(true);
    setError(null);

    try {
      const response = await sendNesaiMessage(text);
      const data = response.data;

      const botMessage: ChatMessage = {
        id: `nesai-${Date.now()}`,
        sender: 'nesai',
        text: data.answer,
        createdAt: new Date(),
        intent: data.intent,
        sources: data.sources,
        actions: data.actions,
      };

      setMessages((prev) => [...prev, botMessage]);
    } catch (err: any) {
      const fallbackErrorMessage: ChatMessage = {
        id: `error-${Date.now()}`,
        sender: 'nesai',
        text: 'Maaf, terjadi gangguan saat menghubungi server. Silakan coba lagi atau cek koneksi internet Anda.',
        createdAt: new Date(),
        isError: true,
      };
      setMessages((prev) => [...prev, fallbackErrorMessage]);
      setError(err?.message || 'Terjadi kesalahan jaringan.');
    } finally {
      setIsLoading(false);
    }
  }, [isLoading]);

  const clearChat = useCallback(() => {
    setMessages([
      {
        id: 'welcome',
        sender: 'nesai',
        text: 'Percakapan telah direset. Ada yang bisa NESAI bantu lagi?',
        createdAt: new Date(),
      },
    ]);
  }, []);

  return {
    messages,
    isLoading,
    error,
    sendMessage,
    clearChat,
  };
}
```

---

## 7. Konfigurasi Environment & CORS

### Environment Variable di Frontend (`.env.local`)
Tambahkan variabel base URL backend ke frontend:
```env
NEXT_PUBLIC_API_URL=http://localhost:8000
```
*(Sesuaikan port jika backend dijalankan di URL berbeda, misal `http://127.0.0.1:8000`)*

### Catatan CORS Backend
Backend Laravel telah dikonfigurasi di `config/cors.php`:
- Path: `api/*`
- Allowed Origins: `env('FRONTEND_URL', 'http://localhost:3000')`
- Jika frontend berjalan di port lain (misal Vite di `5173` atau Next.js di `3001`), pastikan variabel `FRONTEND_URL` di file `.env` backend disesuaikan.

---

## 8. Checklist Pengujian Frontend

- [ ] Widget chatbot dapat dibuka dan ditutup dengan lancar melalui floating button.
- [ ] Pesan teks yang diketik pengguna terkirim dan muncul di bubble kanan.
- [ ] Indikator animasi loading/typing muncul selama backend memproses respons Gemini.
- [ ] Format markdown pada respons bot (bold, list angka, bullet list) ter-render rapi.
- [ ] Tombol aksi (`actions`) muncul bila tersedia, dan saat diklik mengarahkan rute halaman Next.js dengan benar.
- [ ] Sumber referensi (`sources`) ditampilkan dalam badge atau catatan kaki pesan.
- [ ] Menangani state error bila backend mati atau tidak terjangkau tanpa membuat aplikasi crash.
- [ ] Layout responsif pada layar smartphone / mobile (lebar penuh atau modal nyaman).

---

## 9. Prompt Contoh untuk AI Coding Agent di Sesi Frontend

Anda dapat langsung menyalin prompt berikut saat memulai sesi di repository frontend:

```text
Halo! Tolong implementasikan fitur chatbot virtual assistant "NESAI" untuk website SMKN 1 Subang.
Spesifikasi lengkap kontrak API, tipe data TypeScript, format respons (termasuk tindakan navigasi actions dan sumber data sources), serta rancangan komponen UI telah dijelaskan di file CHATBOT_FRONTEND_GUIDE.md.

Tolong buatkan:
1. Tipe data TypeScript untuk pesan dan response NESAI.
2. API client / service untuk request ke POST /api/v1/nesai/chat.
3. State management / custom hook useNesaiChat.
4. Komponen UI Floating Chatbot Widget (tombol trigger di pojok kanan bawah, modal chat, rendering markdown, action chips navigasi, quick suggestions, dan typing indicator).
5. Pastikan terintegrasi dengan Next.js Router untuk tombol navigasi ke halaman terkait.
```
