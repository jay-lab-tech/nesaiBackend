<?php

namespace App\Services\Nesai;

/**
 * [CORE-LOGIC: RETRIEVAL-HOOK]
 * Titik ekstensi untuk Retrieval-Augmented Generation (RAG).
 *
 * Saat ini belum ada korpus dokumen resmi yang terindeks, sehingga method ini
 * mengembalikan daftar kosong. NesaiService hanya menambahkan sumber apabila
 * hasilnya tidak kosong — jadi mengaktifkan RAG cukup dengan mengisi method ini
 * (mis. embedding + vector search) tanpa mengubah alur pemanggilan.
 */
class RetrievalService
{
    /**
     * @return array<string> Daftar label sumber dokumen yang relevan dengan pesan.
     */
    public function retrieve(string $message): array
    {
        return [];
    }
}
