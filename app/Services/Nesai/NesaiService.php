<?php

namespace App\Services\Nesai;

use App\Ai\Agents\SchoolAssistantAgent;
use Illuminate\Support\Facades\Log;
use Throwable;

class NesaiService
{
    public function __construct(
        private IntentService $intent,
        private RetrievalService $retrieval,
        private LlmService $llm,
    ) {}

    /**
     * Handle incoming user message and generate a structured response.
     *
     * @param  string  $message
     * @param  array  $context
     * @return array{
     *     answer: string,
     *     intent: string,
     *     sources: array<string>,
     *     actions: array<array{type: string, path: string, title: string}>,
     *     mode: string
     * }
     */
    public function respond(string $message, array $context = []): array
    {
        $intent = $this->intent->detect($message);
        $sources = $this->retrieval->retrieve($message);
        $actions = [];

        try {
            $response = SchoolAssistantAgent::make()->prompt($message);
            $answer = $response->text;

            // Ekstraksi actions & sources dari toolResults
            if (isset($response->toolResults) && $response->toolResults->isNotEmpty()) {
                foreach ($response->toolResults as $toolResult) {
                    if ($toolResult->name === 'navigate_to_page' && is_string($toolResult->result)) {
                        $navData = json_decode($toolResult->result, true);
                        if (is_array($navData) && isset($navData['path'])) {
                            $actions[] = [
                                'type' => 'navigate',
                                'path' => $navData['path'],
                                'title' => $navData['title'] ?? 'Navigasi Halaman',
                            ];
                        }
                    }

                    if ($toolResult->name === 'get_jurusan_info') {
                        $sources[] = 'Informasi Jurusan SMKN 1 Subang (config/jurusan.php)';
                    }
                }
            }

            // Fallback ekstraksi dari toolCalls jika toolResults belum terisi
            if (empty($actions) && isset($response->toolCalls) && $response->toolCalls->isNotEmpty()) {
                foreach ($response->toolCalls as $toolCall) {
                    if ($toolCall->name === 'navigate_to_page') {
                        $args = $toolCall->arguments;
                        $page = $args['page'] ?? '/';
                        $path = str_starts_with($page, '/') ? $page : '/' . ltrim($page, '/');
                        if (! empty($args['slug'])) {
                            $path = rtrim($path, '/') . '/' . ltrim($args['slug'], '/');
                        }
                        $actions[] = [
                            'type' => 'navigate',
                            'path' => $path,
                            'title' => $args['label'] ?? ('Halaman ' . ucfirst(trim($path, '/'))),
                        ];
                    }

                    if ($toolCall->name === 'get_jurusan_info') {
                        $sources[] = 'Informasi Jurusan SMKN 1 Subang (config/jurusan.php)';
                    }
                }
            }

            // Jika agent memanggil aksi navigasi, sesuaikan intent ke school_navigation
            if (! empty($actions) && $intent === 'school_information') {
                $intent = 'school_navigation';
            }

            $sources = array_values(array_unique($sources));
            $actions = array_values(array_unique($actions, SORT_REGULAR));

            return [
                'answer' => $answer,
                'intent' => $intent,
                'sources' => $sources,
                'actions' => $actions,
                'mode' => 'ai-agent',
            ];
        } catch (Throwable $e) {
            Log::error('NESAI Agent error: ' . $e->getMessage(), [
                'exception' => $e,
                'message' => $message,
            ]);

            return [
                'answer' => 'Halo! Saya NESAI, asisten virtual SMKN 1 Subang. Saat ini layanan AI sedang dalam penyesuaian. Anda dapat menanyakan seputar jurusan dan PPDB, atau langsung mengunjungi halaman yang tersedia di bawah ini.',
                'intent' => $intent,
                'sources' => $sources,
                'actions' => [
                    [
                        'type' => 'navigate',
                        'path' => '/jurusan',
                        'title' => 'Daftar Jurusan SMKN 1 Subang',
                    ],
                    [
                        'type' => 'navigate',
                        'path' => '/ppdb',
                        'title' => 'Informasi PPDB',
                    ],
                ],
                'mode' => 'fallback-error',
            ];
        }
    }
}
