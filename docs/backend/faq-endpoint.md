# Endpoint FAQ

**Branch:** `feature/faq-endpoint`
**Penulis:** Alifth (Backend & Database Engineer)
**Tanggal:** 20 September 2026

## Ringkasan

Menambahkan endpoint publik untuk mengambil data FAQ (pertanyaan yang
sering diajukan). Model `Faq` sudah ada sejak awal, tapi belum punya
controller/route sehingga datanya belum bisa diakses dari luar.

## Perubahan

**File:** `app/Http/Controllers/Api/ContentController.php`

Ditambah method `faqs()`, mengikuti pola yang sudah ada di controller
ini untuk `news()`, `ppdb()`, dan `alumni()` — bukan bikin controller
baru, karena FAQ termasuk kategori "konten" yang sama.

```php
public function faqs(): JsonResponse
{
    return response()->json([
        'data' => Faq::query()->orderBy('sort_order')->get(),
        'meta' => (object) [],
        'message' => null,
    ]);
}
```

**File:** `routes/api.php`

Ditambah 1 baris route:
```php
Route::get('faqs', [ContentController::class, 'faqs']);
```

## Endpoint baru

```
GET /api/v1/faqs
```

Response:
```json
{
  "data": [
    {
      "id": 1,
      "question": "Bagaimana cara mendaftar PPDB?",
      "answer": "Pendaftaran dilakukan secara online melalui website sekolah pada periode yang ditentukan. (dummy)",
      "sort_order": 0,
      "created_at": "...",
      "updated_at": "..."
    }
  ],
  "meta": {},
  "message": null
}
```

Data diurutkan berdasarkan kolom `sort_order` (ascending).

Catatan: isi FAQ saat ini masih data placeholder (lihat
`docs/backend/seeder-data-sekolah.md`), belum konten resmi dari
sekolah.

## Cara tes

```bash
php artisan route:list --path=faqs
curl http://127.0.0.1:8000/api/v1/faqs
```

## Siapa yang pakai

- **Frontend** — untuk membangun halaman/section "Pertanyaan Umum"
- **NESAI (AI service)** — sebagai salah satu sumber jawaban untuk
  pertanyaan calon siswa yang sifatnya umum
