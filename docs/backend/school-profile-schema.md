# Skema Database — Profil Sekolah

**Branch:** `feature/school-profile-schema`
**Penulis:** Alifth (Backend & Database Engineer)
**Tanggal:** 20 September 2026

## Ringkasan

Menyiapkan skema database untuk menampung data profil lengkap
SMK Negeri 1 Subang: detail sekolah, sarana-prasarana, ekstrakurikuler,
karya inovasi, dan statistik SPMB/tracking alumni. Branch ini murni
perubahan struktur tabel (migration + model) — belum ada data.

## Perubahan migration

**File:** `2026_09_18_000100_create_nesas_content_tables.php` (diperbarui)

### Tabel `schools` — ditambah 12 kolom detail

Sebelumnya cuma: `id`, `name`, `description`, `vision`, `mission`,
`social_links`, timestamps.

Ditambah: `npsn`, `address`, `phone`, `email`, `accreditation`,
`founded_year`, `area_size`, `principal_name`, `staff_count`,
`student_count`, `classroom_count`, `stats_updated_at`.

> `student_count` dan `classroom_count` sengaja nullable — sumber
> publik memberi angka yang saling kontradiktif, perlu konfirmasi
> resmi ke sekolah sebelum diisi.

### 5 tabel baru

| Tabel | Kolom utama | Keterangan |
|---|---|---|
| `facilities` | `name`, `category` | Sarana dan prasarana sekolah |
| `extracurriculars` | `name`, `category` | Daftar ekstrakurikuler |
| `innovations` | `major_id` (FK, nullable), `name`, `description`, `has_haki` | Karya inovasi siswa ber-HAKI |
| `admission_stats` | `major_id` (FK), `year`, `applicant_count` | Jumlah pendaftar SPMB per jurusan per tahun (unique per major+year) |
| `alumni_tracking_stats` | `year` (unique), `employed_percent`, `entrepreneur_percent`, `college_percent`, `other_percent` | Persentase status alumni per tahun kelulusan |

## Bug fix

**File:** `app/Models/Alumni.php`

Model `Alumni` tidak punya `$table` eksplisit, sehingga Eloquent
menebak nama tabel jadi `alumnis` (auto-pluralize salah untuk kata
yang sudah berbentuk jamak), padahal migration membuat tabel `alumni`.
Fix: tambah `protected $table = 'alumni';` — pola yang sama seperti
yang sudah dipakai di model `Ppdb`.

## Model baru

`app/Models/Facility.php`, `Extracurricular.php`, `Innovation.php`,
`AdmissionStat.php`, `AlumniTrackingStat.php` — semua pakai
`$guarded = []`. `Innovation` dan `AdmissionStat` punya relasi
`belongsTo(Major::class)`.

## Belum termasuk di branch ini

- Data (seeder) — lihat `docs/backend/seeder-data-sekolah.md`
- Controller/route API untuk 5 tabel baru — belum dibuat, akan
  menyusul di branch terpisah setelah ada kebutuhan dari
  frontend/NESAI

## Cara tes

```bash
php artisan migrate:fresh
php artisan tinker
>>> Schema::hasColumn('schools', 'npsn')
>>> Schema::hasTable('facilities')
```
