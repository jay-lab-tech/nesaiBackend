# Dokumentasi Fitur Rekomendasi Jurusan Berbasis Minat (NESAI AI & Find Your Path)

**Tanggal:** 2026-09-19  
**Branch:** `feature/nesai-api`  
**Kebutuhan:** Lomba Web Development JHIC 2.0  
**Target:** AI dapat merekomendasikan jurusan lewat chat percakapan dan form seleksi minat ("Find Your Path")

---

## 1. Latar Belakang & Filosofi Desain

Calon siswa SMK sering bingung memilih jurusan yang tepat. Lead proyek meminta agar NESAI AI dapat merekomendasikan jurusan berdasarkan minat pengguna dan data resmi sekolah.

Sesuai catatan di `docs/data/data.md`:
> *"Bagian ini tidak boleh dibuat berdasarkan asumsi developer. Harus disetujui guru atau pihak sekolah. (tapi sementara buat demo kita cari dari internet saja dulu)"*

Kami menerapkan **Arsitektur Hybrid**:
1. **Engine Scoring Deterministik (`JurusanScorer`)**: Kalkulasi kecocokan minat tidak diserahkan ke halusinasi LLM, melainkan dihitung oleh rule-based matcher di PHP menggunakan data di `config/jurusan.php`.
2. **Penyajian Cerdas via AI (`SchoolAssistantAgent` + `RecommendJurusanTool`)**: LLM mengekstrak minat dari obrolan santai siswa (misal: *"aku suka ngoprek hp dan main game"*), memanggil tool rekomendasi, lalu merangkai jawaban ramah dan persuasif disertai saran link navigasi.
3. **Akses Langsung Form Interaktif (`POST /api/v1/recommendations/majors`)**: Frontend Next.js (halaman Find Your Path) dapat langsung mengirim array minat siswa tanpa harus melalui LLM, menghasilkan respon instan (<5ms) dan 100% konsisten.

---

## 2. Struktur File & Komponen Baru

```
app/
├── Ai/
│   ├── Agents/
│   │   └── SchoolAssistantAgent.php    # Diperbarui: Prompt panduan rekomendasi + binding RecommendJurusanTool
│   ├── Support/
│   │   └── JurusanScorer.php           # [BARU] Shared engine kalkulasi bobot & pencocokan minat siswa
│   └── Tools/
│       ├── GetJurusanInfoTool.php      # Pencarian info jurusan & kurikulum
│       ├── NavigateToPageTool.php      # Navigasi halaman frontend
│       └── RecommendJurusanTool.php    # [BARU] Tool function calling Gemini untuk rekomendasi jurusan
├── Http/
│   └── Controllers/
│       └── Api/
│           └── RecommendationController.php # Diperbarui: Controller Find Your Path terhubung ke JurusanScorer
├── Services/
│   ├── Nesai/
│   │   └── NesaiService.php            # Diperbarui: Ekstraksi intent 'major_recommendation' & quick actions
│   └── RecommendationService.php       # Diperbarui: Service form rekomendasi menggunakan JurusanScorer
config/
└── jurusan.php                         # Diperkaya: slug, kata_kunci_minat, prospek_karir, mata_pelajaran_utama
```

---

## 3. Tag Core Logic & Penjelasannya

Untuk memudahkan tim memahami arsitektur kode, berikut tag komentar core logic yang disematkan:

### `[CORE-LOGIC: JURUSAN-SCORING-ALGORITHM]`
- **Lokasi:** `app/Ai/Support/JurusanScorer.php`
- **Fungsi:** Menghitung total skor kecocokan minat siswa terhadap 10 jurusan resmi SMKN 1 Subang secara deterministik dan transparan.
- **Aturan Pembobotan (`[CORE-LOGIC: KEYWORD-MATCHING-RULE]`):**
  - **+3 poin**: Kecocokan pada `kata_kunci_minat` jurusan (identik atau substring).
  - **+2 poin**: Kecocokan pada profesi di `prospek_karir` (cita-cita siswa).
  - **+2 poin**: Kecocokan langsung pada `nama` jurusan.
  - **+1 poin**: Kecocokan pada `mata_pelajaran_utama`.

### `[CORE-LOGIC: RECOMMEND-JURUSAN-TOOL]` & `[CORE-LOGIC: FLEXIBLE-INTEREST-PARSER]`
- **Lokasi:** `app/Ai/Tools/RecommendJurusanTool.php`
- **Fungsi:** Tool AI yang menerima input minat dari Gemini. Menggunakan parser fleksibel yang menerima parameter minat baik berupa JSON array `["coding", "game"]` maupun string dipisah koma `"coding, game"`.

### `[CORE-LOGIC: RECOMMENDATION-SERVICE]` & `[CORE-LOGIC: RECOMMENDATION-CONTROLLER]`
- **Lokasi:** `app/Services/RecommendationService.php` dan `app/Http/Controllers/Api/RecommendationController.php`
- **Fungsi:** Endpoint API standar untuk halaman interactive quiz / form "Find Your Path" di frontend Next.js.

### `[CORE-LOGIC: TOOL-RESULT-EXTRACTION]` & `[CORE-LOGIC: INTENT-NORMALIZATION]`
- **Lokasi:** `app/Services/Nesai/NesaiService.php`
- **Fungsi:** Mendeteksi eksekusi `recommend_jurusan`, menormalisasi intent ke `major_recommendation`, mencatat sumber data, dan secara otomatis menyusun tombol quick-action menuju `/jurusan/{slug}` untuk 2 jurusan teratas.

---

## 4. Spesifikasi Endpoint API

### A. Chatbot AI (`POST /api/chat`)

Menerima pesan natural language dari pengguna.

**Request:**
```json
{
  "message": "Saya suka bermain game dan belajar coding, jurusan apa yang pas buat saya?"
}
```

**Response (AI Agent Active):**
```json
{
  "data": {
    "answer": "Berdasarkan minat kamu di bidang game dan coding, rekomendasi jurusan yang paling cocok di SMKN 1 Subang adalah:\n\n1. **Pengembangan Perangkat Lunak dan Gim (PPLG)**\n- Alasan: Sangat sesuai dengan minat coding dan game. Prospek karirnya meliputi Software Developer dan Game Developer.\n- Mata pelajaran yang dipelajari: Pemrograman Dasar, Pemrograman Berorientasi Objek, Basis Data.\n\nKamu juga bisa melihat Teknik Komputer Jaringan (TKJ) jika tertarik dengan infrastruktur komputernya.",
    "intent": "major_recommendation",
    "sources": [
      "Rekomendasi Jurusan SMKN 1 Subang (config/jurusan.php)"
    ],
    "actions": [
      {
        "type": "navigate",
        "path": "/jurusan/pplg",
        "title": "Lihat Pengembangan Perangkat Lunak dan Gim"
      },
      {
        "type": "navigate",
        "path": "/jurusan/tkj",
        "title": "Lihat Teknik Komputer Jaringan"
      }
    ],
    "mode": "ai-agent"
  }
}
```

---

### B. Form Rekomendasi "Find Your Path" (`POST /api/v1/recommendations/majors`)

Endpoint langsung untuk komponen frontend kuis/seleksi minat tanpa overhead AI.

**Request:**
```json
{
  "interests": ["game", "coding", "komputer"]
}
```

**Response (HTTP 200 OK):**
```json
{
  "data": [
    {
      "nama": "Pengembangan Perangkat Lunak dan Gim",
      "slug": "pplg",
      "skor_kecocokan": 11,
      "alasan": "Cocok dengan minat: game, coding, komputer; Sesuai prospek karir: Game Developer",
      "kata_kunci_cocok": [
        "game",
        "coding",
        "komputer"
      ],
      "prospek_karir": [
        "Software Developer",
        "Web Developer",
        "Game Developer",
        "Mobile App Developer",
        "Full Stack Developer",
        "Database Administrator"
      ],
      "mata_pelajaran_utama": [
        "Pemrograman Dasar",
        "Pemrograman Berorientasi Objek",
        "Pemrograman Web dan Perangkat Bergerak",
        "Basis Data",
        "Produk Kreatif dan Kewirausahaan"
      ]
    },
    {
      "nama": "Teknik Komputer Jaringan",
      "slug": "tkj",
      "skor_kecocokan": 7,
      "alasan": "Cocok dengan minat: komputer, Teknik Komputer Jaringan; Sesuai prospek karir: Teknisi Komputer & Jaringan",
      "kata_kunci_cocok": [
        "komputer",
        "Teknik Komputer Jaringan"
      ],
      "prospek_karir": [
        "Network Administrator",
        "IT Support Specialist",
        "System Administrator",
        "Cyber Security Analyst",
        "Network Engineer",
        "Teknisi Komputer & Jaringan"
      ],
      "mata_pelajaran_utama": [
        "Dasar-Dasar Teknik Jaringan",
        "Administrasi Infrastruktur Jaringan",
        "Administrasi Sistem Jaringan",
        "Keamanan Jaringan",
        "Teknologi Layanan Jaringan"
      ]
    }
  ],
  "meta": {
    "total": 2,
    "status": "scored",
    "source": "config/jurusan.php"
  },
  "message": "Rekomendasi jurusan berhasil dikalkulasi berdasarkan minat yang dipilih."
}
```

---

## 5. Sinkronisasi dengan Tim Konten & Frontend Next.js

1. **Review Data oleh Sekolah/Tim Konten**:
   Seluruh kata kunci minat, prospek karir, dan mata pelajaran di `config/jurusan.php` saat ini menggunakan template representatif SMK. Tim konten dapat langsung mengedit file `config/jurusan.php` untuk menyesuaikan dengan silabus riil SMKN 1 Subang.
2. **Deep-linking Frontend**:
   Slug yang dihasilkan (`pplg`, `tkj`, `dkv`, `teknik-mesin`, `teknik-otomotif`, `akl`, `mplb`, `pemasaran`, `teknik-logistik`, `kuliner`) sudah disinkronkan untuk rute halaman Next.js: `/jurusan/[slug]`.
