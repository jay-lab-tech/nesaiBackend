# Endpoint Profil Sekolah Tambahan

**Branch:** `feature/school-profile-endpoints`
**Penulis:** Alifth (Backend & Database Engineer)
**Tanggal:** 20 September 2026

## Ringkasan

Membuka akses API untuk 5 tabel yang datanya sudah ter-seed di
`feature/backend-seeder` tapi belum bisa diakses dari luar: sarana,
ekstrakurikuler, karya inovasi, dan statistik SPMB/tracking alumni.

## File baru

5 controller terpisah, masing-masing 1 resource, mengikuti pola
`__invoke()` yang sama seperti `HealthController` dan `SchoolController`:

- `app/Http/Controllers/Api/FacilityController.php`
- `app/Http/Controllers/Api/ExtracurricularController.php`
- `app/Http/Controllers/Api/InnovationController.php`
- `app/Http/Controllers/Api/AdmissionStatController.php`
- `app/Http/Controllers/Api/AlumniTrackingStatController.php`

## Endpoint baru

| Method | Endpoint | Controller | Urutan |
|---|---|---|---|
| GET | `/api/v1/facilities` | `FacilityController` | nama (A-Z) |
| GET | `/api/v1/extracurriculars` | `ExtracurricularController` | nama (A-Z) |
| GET | `/api/v1/innovations` | `InnovationController` (+relasi `major`) | default |
| GET | `/api/v1/stats/admissions` | `AdmissionStatController` (+relasi `major`) | major, lalu tahun |
| GET | `/api/v1/stats/alumni-tracking` | `AlumniTrackingStatController` | tahun |

Semua pakai `->get()` (bukan `->paginate()`) karena jumlah barisnya
kecil (maksimal 40 baris untuk `admission_stats`) — tidak perlu
paginasi.

## Contoh response — `/api/v1/stats/admissions`

```json
{
  "data": [
    {
      "id": 1,
      "major_id": 1,
      "year": 2022,
      "applicant_count": 256,
      "major": { "id": 1, "name": "Akuntansi dan Keuangan Lembaga", "slug": "..." }
    }
  ],
  "meta": {},
  "message": null
}
```

## Cara tes

```bash
php artisan route:list --path=facilities
php artisan route:list --path=stats
curl http://127.0.0.1:8000/api/v1/facilities
curl http://127.0.0.1:8000/api/v1/stats/admissions
curl http://127.0.0.1:8000/api/v1/stats/alumni-tracking
```

## Belum ada

CRUD (`POST`/`PUT`/`DELETE`) untuk 5 tabel ini — semua endpoint di
atas masih read-only, sesuai kebutuhan saat ini (data diisi lewat
seeder, bukan lewat admin panel).
