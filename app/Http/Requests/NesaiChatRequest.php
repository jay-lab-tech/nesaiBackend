<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NesaiChatRequest extends FormRequest
{
    /**
     * Endpoint chat bersifat publik (tanpa login), namun tetap terikat ke sesi web.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
            'context' => ['nullable', 'array', 'max:5'],
            'context.*' => ['string', 'max:500'],
        ];
    }

    /**
     * Menormalkan context agar selalu berupa daftar string terbatas sebelum dikonsumsi service.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if (is_array($validated) && isset($validated['context']) && is_array($validated['context'])) {
            $validated['context'] = array_slice(array_values(array_filter(
                $validated['context'],
                fn ($item) => is_string($item) && $item !== '',
            )), 0, 5);
        }

        return $validated;
    }
}
