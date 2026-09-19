<?php

namespace App\Ai\Support;

/**
 * [CORE-LOGIC: JURUSAN-SCORING-ALGORITHM]
 * Engine pencocokan dan pembobotan minat siswa terhadap data jurusan SMKN 1 Subang.
 * Bersifat deterministik dan transparan, digunakan bersama oleh:
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
        $jurusanList = config('jurusan', []);

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
