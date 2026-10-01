# Seeder Data Sekolah — SMK Negeri 1 Subang

**Branch:** `feature/backend-seeder` (based on `feature/school-profile-schema`)
**Penulis:** Alifth (Backend & Database Engineer)
**Tanggal:** 20 September 2026

## Ringkasan

Mengisi database dengan data sekolah, program keahlian, alumni,
sarana-prasarana, ekstrakurikuler, karya inovasi, dan statistik
SPMB/tracking alumni SMK Negeri 1 Subang — plus placeholder untuk
konten yang belum tersedia secara resmi (berita, PPDB, FAQ).

> Skema tabel yang dipakai seeder ini dibuat di branch
> `feature/school-profile-schema` — lihat
> `docs/backend/school-profile-schema.md` untuk detail migration &
> model.

## Seeder

Dijalankan lewat `DatabaseSeeder.php`, urutan: `SchoolPublicDataSeeder`
lalu `DummySchoolDataSeeder`.

### `SchoolPublicDataSeeder.php` — data ASLI

Sumber: situs publik smkn1subang.sch.id dan dokumen profil resmi
sekolah (PDF "Profil SMKN 1 Subang 2026" + dokumen ringkasan jurusan),
diinventarisasi 19-20 September 2026.

- **1 School** — profil lengkap termasuk misi 6 poin.
- **10 Major** — seluruh program keahlian dengan nama resmi terbaru,
  lengkap mata pelajaran, jalur karier, dan mitra industri.
- **12 Alumni** — testimoni asli dari dokumen profil resmi. Label
  jurusan lama pada dokumen sumber dipetakan ke nama program saat ini
  (mis. TSM -> Teknik Otomotif, Grafika -> Desain Komunikasi Visual)
  sesuai keterangan nomenklatur di dokumen sumber sendiri.
- **14 Facility** — sarana dan prasarana sekolah.
- **31 Extracurricular** — daftar ekstrakurikuler.
- **3 Innovation** — karya inovasi ber-HAKI (Motocimic, Nesasserator,
  Siborin), masing-masing terkait ke jurusan pembuatnya.
- **40 AdmissionStat** — jumlah pendaftar SPMB per jurusan, 2022-2025.
- **6 AlumniTrackingStat** — persentase status alumni (bekerja/
  wirausaha/kuliah/lainnya) per tahun kelulusan, 2020-2025.

**Data yang sengaja TIDAK diisi** (bukan lupa — sumber publik belum
menyediakan versi resmi/terverifikasi):
- Jalur karier untuk Desain Komunikasi Visual
- `student_count` dan `classroom_count` pada tabel `schools`

**Larangan penting (dari dokumen inventaris):** data pribadi calon
siswa (nama, NISN, asal sekolah) pada halaman SPMB situs sekolah
**tidak boleh** dimasukkan ke database, seed, search, atau konteks
NESAI dalam bentuk apa pun.

### `DummySchoolDataSeeder.php` — masih PLACEHOLDER

- **3 News**, **1 Ppdb**, **4 Faq** — konten dummy, ditandai `(dummy)`
  pada teksnya. Sekolah belum menyediakan konten berita/PPDB aktif/FAQ
  yang siap diimpor apa adanya. Ganti/hapus seeder ini begitu konten
  resmi tersedia.

## Belum diintegrasikan sama sekali

- Struktur organisasi / daftar staf per program keahlian — dokumen
  sumber punya datanya, tapi belum ada keputusan skema tabelnya

## Perlu koordinasi tim

File `config/jurusan.php` (dibuat AI Engineer) berisi data jurusan
draft yang terpisah dari tabel `majors` ini — berpotensi jadi 2
sumber data yang beda untuk hal yang sama. Perlu didiskusikan supaya
tools NESAI (`GetJurusanInfoTool`, `RecommendJurusanTool`) pindah
baca dari tabel `majors`, bukan config statis.

## Cara menjalankan ulang

```bash
php artisan migrate:fresh --seed
```

## Endpoint yang bisa dites

```
GET /api/v1/school
GET /api/v1/majors
GET /api/v1/majors/{slug}
GET /api/v1/alumni
GET /api/v1/faqs
```

5 tabel baru (`facilities`, `extracurriculars`, `innovations`,
`admission_stats`, `alumni_tracking_stats`) datanya sudah ada di
database tapi **belum punya endpoint API** — menyusul di branch
terpisah.
