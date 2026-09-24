<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MajorController extends Controller
{
    use ApiResponse;

    protected array $aliasMap = [
        'pplg' => 'pengembangan-perangkat-lunak-dan-gim',
        'rpl' => 'pengembangan-perangkat-lunak-dan-gim',
        'tkj' => 'teknik-jaringan-komputer-dan-telekomunikasi',
        'tjkt' => 'teknik-jaringan-komputer-dan-telekomunikasi',
        'dkv' => 'desain-komunikasi-visual',
        'akl' => 'akuntansi-dan-keuangan-lembaga',
        'ak' => 'akuntansi-dan-keuangan-lembaga',
        'mplb' => 'manajemen-perkantoran-dan-layanan-bisnis',
        'otkp' => 'manajemen-perkantoran-dan-layanan-bisnis',
        'ap' => 'manajemen-perkantoran-dan-layanan-bisnis',
        'bdp' => 'pemasaran',
        'pm' => 'pemasaran',
        'to' => 'teknik-otomotif',
        'tbsm' => 'teknik-otomotif',
        'tkr' => 'teknik-otomotif',
        'tm' => 'teknik-mesin',
        'tp' => 'teknik-mesin',
        'tb' => 'kuliner',
        'tlog' => 'teknik-logistik',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = Major::query()->with(['subjects', 'careers']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $majors = $query->get();

        return $this->successResponse($majors, 'Daftar jurusan berhasil diambil.');
    }

    public function show(string $slug): JsonResponse
    {
        $lowerSlug = strtolower(trim($slug));
        $targetSlug = $this->aliasMap[$lowerSlug] ?? $lowerSlug;

        $major = Major::with(['subjects', 'careers', 'innovations', 'admissionStats', 'alumni'])
            ->where('slug', $targetSlug)
            ->first();

        if (!$major) {
            // Fallback cari berdasarkan pencocokan substring nama atau slug
            $major = Major::with(['subjects', 'careers', 'innovations', 'admissionStats', 'alumni'])
                ->where('slug', 'like', "%{$lowerSlug}%")
                ->first();
        }

        if (!$major) {
            return $this->errorResponse('Jurusan tidak ditemukan.', 404);
        }

        return $this->successResponse($major, 'Detail jurusan berhasil diambil.');
    }
}