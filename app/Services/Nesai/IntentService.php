<?php

namespace App\Services\Nesai;

/**
 * [CORE-LOGIC: LIGHTWEIGHT-INTENT-CLASSIFIER]
 * Klasifikasi intent berbasis kata kunci (tanpa panggilan LLM tambahan / tanpa latensi).
 * Intent final tetap dapat di-override oleh hasil tool call di NesaiService
 * (mis. recommend_jurusan → 'major_recommendation', get_ppdb_info → 'ppdb_information').
 *
 * Tujuan: memberikan intent awal yang masuk akal sebagai fallback bila agent
 * tidak memanggil tool apapun, sekaligus menghindari panggilan kosong ke RetrievalService.
 */
class IntentService
{
    /** @var array<string, list<string>> */
    private const PATTERNS = [
        'ppdb_information' => ['ppdb', 'pendaftaran', 'daftar', 'syarat', 'berkas', 'spmb', 'jalur', 'zonasi', 'biaya'],
        'major_recommendation' => ['rekomendasi', 'bingung pilih', 'cocok', 'minat', 'hobi', 'bakat', 'jurusan apa', 'pilih jurusan'],
        'school_navigation' => ['link', 'tautan', 'halaman', 'buka', 'arahkan', 'navigasi', 'di mana halaman'],
    ];

    public function detect(string $message): string
    {
        $normalized = strtolower($message);

        foreach (self::PATTERNS as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return $intent;
                }
            }
        }

        return 'school_information';
    }
}
