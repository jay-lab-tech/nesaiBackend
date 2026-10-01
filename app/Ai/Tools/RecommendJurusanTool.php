<?php

namespace App\Ai\Tools;

use App\Ai\Support\JurusanScorer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RecommendJurusanTool implements Tool
{
    public function __construct(
        protected ?JurusanScorer $scorer = null
    ) {
        $this->scorer = $scorer ?? new JurusanScorer();
    }

    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'recommend_jurusan';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Merekomendasikan jurusan atau kompetensi keahlian SMKN 1 Subang berdasarkan minat, hobi, ketertarikan, skill, atau cita-cita karir pengguna. Tool ini melakukan kalkulasi kecocokan dan mengembalikan daftar jurusan teratas beserta alasan rekomendasinya.';
    }

    /**
     * [CORE-LOGIC: RECOMMEND-JURUSAN-TOOL]
     * Menjalankan scoring rule-based berdasarkan input minat yang diekstrak oleh LLM.
     */
    public function handle(Request $request): Stringable|string
    {
        // [CORE-LOGIC: FLEXIBLE-INTEREST-PARSER]
        // Menangani input minat baik berupa array maupun string koma/spasi dari LLM secara fleksibel dan aman.
        $rawInterests = $request['interests'] ?? [];

        $interests = [];
        if (is_array($rawInterests)) {
            $interests = $rawInterests;
        } elseif (is_string($rawInterests)) {
            $interests = array_map('trim', explode(',', $rawInterests));
        }

        $interests = array_values(array_filter($interests, fn ($item) => ! empty($item)));

        if (empty($interests)) {
            return json_encode([
                'status' => 'empty_input',
                'message' => 'Tidak ada kata kunci minat yang dapat diproses. Minta pengguna menyebutkan minat, hobi, atau mata pelajaran yang disukai.',
                'rekomendasi' => [],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $limit = $request->integer('limit', 3);
        if ($limit <= 0) {
            $limit = 3;
        }

        // [CORE-LOGIC: SCORER-DELEGATION]
        // Mendelegasikan pencocokan ke shared scoring engine
        $rekomendasi = $this->scorer->score($interests, $limit);

        if (empty($rekomendasi)) {
            return json_encode([
                'status' => 'no_match',
                'message' => 'Belum ditemukan jurusan yang secara spesifik cocok dengan minat tersebut. Berikan opsi untuk melihat seluruh daftar jurusan di SMKN 1 Subang.',
                'minat_input' => $interests,
                'rekomendasi' => [],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return json_encode([
            'status' => 'success',
            'minat_input' => $interests,
            'total_rekomendasi' => count($rekomendasi),
            'rekomendasi' => $rekomendasi,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * [CORE-LOGIC: TOOL-JSON-SCHEMA]
     * Menyediakan JSON Schema untuk function calling Gemini.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'interests' => $schema->array()
                ->items($schema->string())
                ->description('Daftar minat, hobi, skill, mata pelajaran favorit, atau cita-cita karir yang disebutkan pengguna (contoh: ["komputer", "coding", "game", "desain grafis"]).')
                ->required(),
            'limit' => $schema->integer()
                ->description('Jumlah rekomendasi jurusan yang ingin diambil (default: 3).')
                ->nullable(),
        ];
    }
}
