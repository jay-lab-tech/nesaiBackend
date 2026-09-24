<?php

namespace App\Ai\Support;

use App\Models\Major;
use Illuminate\Support\Facades\Cache;

/**
 * [CORE-LOGIC: JURUSAN-SCORING-ALGORITHM]
 * Engine pencocokan dan pembobotan minat siswa terhadap data jurusan SMKN 1 Subang.
 * Membaca data langsung dari database (model Major) dengan caching.
 * Digunakan bersama oleh:
 *   1. RecommendJurusanTool (Chatbot AI NESAI)
 *   2. RecommendationService (Endpoint form /api/v1/recommendations/majors)
 */
class JurusanScorer
{
    /**
     * Menghitung skor kecocokan seluruh jurusan berdasarkan daftar minat/kata kunci pengguna.
     *
     * @param  array<string>  $interests  Daftar minat/kata kunci dari pengguna
     * @param  int  $limit  Jumlah rekomendasi maksimal yang dikembalikan (default: 3)
     * @return array<array{
     *     nama: string,
     *     slug: string,
     *     skor_kecocokan: int,
     *     alasan: string,
     *     kata_kunci_cocok: array<string>,
     *     prospek_karir: array<string>,
     *     mata_pelajaran_utama: array<string>
     * }>
     */
    public function score(array $interests, int $limit = 3): array
    {
        // [CORE-LOGIC: DATABASE-FIRST-SCORER]
        $jurusanList = Cache::remember('nesai:jurusan_scorer_db_v2', 3600, function () {
            $majors = Major::with(['subjects', 'careers'])->get();

            if ($majors->isEmpty()) {
                return config('jurusan', []);
            }

            $configList = config('jurusan', []);

            return $majors->map(function ($major) use ($configList) {
                $configMatch = collect($configList)->first(function ($c) use ($major) {
                    $slugMatch = isset($c['slug']) && strtolower($c['slug']) === strtolower($major->slug);
                    $nameMatch = isset($c['nama']) && (
                        str_contains(strtolower($major->name), strtolower($c['nama'])) ||
                        str_contains(strtolower($c['nama']), strtolower($major->name))
                    );
                    return $slugMatch || $nameMatch;
                });

                return [
                    'nama' => $major->name,
                    'slug' => $major->slug,
                    'deskripsi' => $major->summary ?: $major->description,
                    'kata_kunci_minat' => $configMatch['kata_kunci_minat'] ?? [],
                    'prospek_karir' => $major->careers->pluck('name')->toArray(),
                    'mata_pelajaran_utama' => $major->subjects->pluck('name')->toArray(),
                ];
            })->toArray();
        });

        if (empty($jurusanList) || empty($interests)) {
            return [];
        }

        // Normalisasi dan sanitasi kata kunci input
        $normalizedInterests = array_values(array_filter(array_map(function ($item) {
            return strtolower(trim((string) $item));
        }, $interests), fn ($item) => $item !== ''));

        if (empty($normalizedInterests)) {
            return [];
        }

        $scoredJurusan = [];

        foreach ($jurusanList as $jurusan) {
            $nama = $jurusan['nama'] ?? '';
            $slug = $jurusan['slug'] ?? '';
            $deskripsi = strtolower($jurusan['deskripsi'] ?? '');
            $kataKunciMinat = array_map('strtolower', $jurusan['kata_kunci_minat'] ?? []);
            $prospekKarir = $jurusan['prospek_karir'] ?? [];
            $mapelUtama = $jurusan['mata_pelajaran_utama'] ?? [];

            $skor = 0;
            $matchedKeywords = [];
            $matchedCareers = [];

            // [CORE-LOGIC: KEYWORD-MATCHING-RULE]
            // Aturan pembobotan:
            // 1. Kecocokan kata kunci minat (+3 poin jika substring / identik)
            // 2. Kecocokan prospek karir cita-cita (+2 poin)
            // 3. Kecocokan nama jurusan langsung (+2 poin)
            // 4. Kecocokan mata pelajaran utama (+1 poin)
            // 5. Kecocokan kata kunci dalam summary deskripsi (+1 poin)
            foreach ($normalizedInterests as $userInterest) {
                // Cek kecocokan di kata kunci minat
                foreach ($kataKunciMinat as $keyword) {
                    if (str_contains($userInterest, $keyword) || str_contains($keyword, $userInterest)) {
                        $skor += 3;
                        $matchedKeywords[] = $keyword;
                    }
                }

                // Cek kecocokan di prospek karir
                foreach ($prospekKarir as $career) {
                    $lowerCareer = strtolower($career);
                    if (str_contains($userInterest, $lowerCareer) || str_contains($lowerCareer, $userInterest)) {
                        $skor += 2;
                        $matchedCareers[] = $career;
                    }
                }

                // Cek nama jurusan
                if (str_contains(strtolower($nama), $userInterest) || str_contains($userInterest, strtolower($nama))) {
                    $skor += 2;
                    $matchedKeywords[] = $nama;
                }

                // Cek mata pelajaran utama
                foreach ($mapelUtama as $mapel) {
                    $lowerMapel = strtolower($mapel);
                    if (str_contains($userInterest, $lowerMapel) || str_contains($lowerMapel, $userInterest)) {
                        $skor += 1;
                    }
                }

                // Cek ringkasan deskripsi
                if (strlen($userInterest) >= 4 && str_contains($deskripsi, $userInterest)) {
                    $skor += 1;
                    $matchedKeywords[] = $userInterest;
                }
            }

            $matchedKeywords = array_values(array_unique($matchedKeywords));
            $matchedCareers = array_values(array_unique($matchedCareers));

            // Hanya sertakan jurusan yang memiliki kecocokan (skor > 0)
            if ($skor > 0) {
                $alasanParts = [];
                if (! empty($matchedKeywords)) {
                    $alasanParts[] = 'Cocok dengan minat: ' . implode(', ', array_slice($matchedKeywords, 0, 4));
                }
                if (! empty($matchedCareers)) {
                    $alasanParts[] = 'Sesuai prospek karir: ' . implode(', ', array_slice($matchedCareers, 0, 3));
                }

                $alasan = ! empty($alasanParts)
                    ? implode('; ', $alasanParts)
                    : "Memiliki relevansi umum dengan minat yang Anda pilih.";

                $scoredJurusan[] = [
                    'nama' => $nama,
                    'slug' => $slug,
                    'skor_kecocokan' => $skor,
                    'alasan' => $alasan,
                    'kata_kunci_cocok' => $matchedKeywords,
                    'prospek_karir' => $prospekKarir,
                    'mata_pelajaran_utama' => $mapelUtama,
                ];
            }
        }

        // Urutkan berdasarkan skor tertinggi (DESC)
        usort($scoredJurusan, function ($a, $b) {
            return $b['skor_kecocokan'] <=> $a['skor_kecocokan'];
        });

        // Ambil top-N sesuai limit
        return array_slice($scoredJurusan, 0, $limit);
    }
}