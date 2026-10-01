<?php

namespace App\Services\Nesai;

use App\Ai\Tools\GetJurusanInfoTool;
use App\Ai\Tools\GetPpdbInfoTool;
use App\Ai\Tools\GetSchoolInfoTool;
use Laravel\Ai\Tools\Request;

/**
 * [CORE-LOGIC: DATABASE-FAST-PATH]
 * Menjawab pertanyaan umum yang bersifat FAKTUAL-DETERMINISTIK langsung dari database
 * (via tool yang sudah ada) TANPA memanggil Gemini. Tujuan utama: saat stress test online,
 * mayoritas request tidak menyentuh provider AI sama sekali sehingga kuota aman dan
 * latency tetap rendah walau Gemini mati/kuota habis.
 *
 * PENTING (UX): keluaran tool adalah teks yang ditujukan untuk LLM (memuat JSON mentah
 * beserta instruksi "panggil tool ini lagi..."). Fast-path WAJIB mem-parse JSON tersebut
 * lalu MEMFORMAT ulang menjadi jawaban bahasa manusia yang ramah dibaca — BUKAN menampilkan
 * array/JSON mentah ke pengguna.
 *
 * Batasan (agar kualitas jawaban tidak turun): HANYA intent yang jawabannya berupa data
 * faktual (PPDB, profil/kontak/alamat, fasilitas, daftar jurusan) yang di-fast-path.
 * Intent yang butuh komposisi bahasa natural (rekomendasi jurusan, navigasi) DIBIARKAN
 * lewat jalur AI. Bila tidak yakin, kembalikan null agar caller memakai jalur AI.
 *
 * @phpstan-type FastPathResult array{answer: string, intent: string, sources: array<string>, actions: array<array{type: string, path: string, title: string}>, mode: string}
 */
class FastPathService
{
    /**
     * @var list<string>
     */
    private const SCHOOL_FACT_KEYWORDS = [
        'alamat', 'lokasi', 'peta', 'maps', 'dimana', 'di mana',
        'kontak', 'telepon', 'telpon', 'nomor', 'email', 'website', 'situs',
        'kepala sekolah', 'kepsek', 'principal',
        'npsn', 'akreditasi', 'tahun berdiri', 'sejarah', 'berdiri',
        'jumlah siswa', 'jumlah murid', 'berapa siswa', 'jumlah guru', 'jumlah staf',
        'visi', 'misi', 'profil sekolah', 'profil smkn',
        'fasilitas', 'sarana', 'prasarana', 'lab', 'laboratorium', 'perpustakaan', 'bengkel',
        'ekskul', 'ekstrakurikuler',
        'program unggulan', 'beraksi',
    ];

    /**
     * @var list<string>
     */
    private const MAJOR_LIST_KEYWORDS = [
        'daftar jurusan', 'list jurusan', 'apa saja jurusan', 'jurusan apa saja',
        'kompetensi keahlian', 'jurusan yang ada', 'jurusan tersedia',
        'ada jurusan apa', 'jurusan apa',
    ];

    public function __construct(
        private IntentService $intent,
    ) {}

    /**
     * @return FastPathResult|null
     */
    public function respond(string $message): ?array
    {
        $normalized = mb_strtolower(trim($message));

        if ($this->matches($normalized, ['ppdb', 'pendaftaran', 'daftar ulang', 'spmb', 'syarat masuk', 'jalur seleksi', 'zonasi', 'biaya pendaftaran', 'cara mendaftar', 'alur pendaftaran'])) {
            return $this->respondPpdb($message);
        }

        if ($this->matches($normalized, self::MAJOR_LIST_KEYWORDS)) {
            return $this->respondMajorList();
        }

        if ($this->matches($normalized, self::SCHOOL_FACT_KEYWORDS)) {
            return $this->respondSchoolFact($message, $normalized);
        }

        return null;
    }

    /**
     * @return FastPathResult
     */
    private function respondPpdb(string $message): array
    {
        $data = $this->decodeToolJson((string) (new GetPpdbInfoTool)->handle(new Request([])));

        $lines = [];
        $tahun = $data['tahun_ajaran'] ?? null;
        $gelombang = $data['gelombang'] ?? null;
        $status = $data['status'] ?? null;

        $lines[] = 'Berikut informasi **PPDB SMKN 1 Subang**'.($tahun ? " Tahun Ajaran {$tahun}" : '').':';
        if ($gelombang || $status) {
            $lines[] = '**Status:** '.trim(implode(' — ', array_filter([$gelombang, $status]))).'.';
        }

        $section = $this->detectPpdbSection($message);

        $sections = [
            'jadwal' => fn () => $this->formatJadwal($data['jadwal'] ?? []),
            'syarat' => fn () => $this->bulletListFromStrings('**Syarat Umum:**', $data['syarat_umum'] ?? $data['persyaratan'] ?? []),
            'berkas' => fn () => $this->bulletListFromStrings('**Berkas yang Diperlukan:**', $data['berkas'] ?? []),
            'jalur' => fn () => $this->formatJalur($data['jalur_seleksi'] ?? []),
            'alur' => fn () => $this->bulletListFromStrings('**Alur Pendaftaran:**', $data['alur_pendaftaran'] ?? []),
            'biaya' => fn () => $this->formatBiaya($data['biaya'] ?? []),
            'kontak' => fn () => $this->formatKontakPanitia($data['kontak_panitia'] ?? []),
        ];

        // Bila pengguna menyebut bagian spesifik, tampilkan itu; jika tidak, tampilkan ringkasan inti.
        if ($section !== null && isset($sections[$section])) {
            $lines[] = $sections[$section]();
        } else {
            $lines[] = $this->formatJadwal($data['jadwal'] ?? []);
            $lines[] = $this->bulletListFromStrings('**Syarat Umum:**', $data['syarat_umum'] ?? $data['persyaratan'] ?? []);
        }

        $portal = $data['portal_ppdb'] ?? $data['portal_ppdb_jabar'] ?? null;
        if ($portal) {
            $lines[] = "**Portal pendaftaran:** {$portal}";
        }

        return $this->result(
            implode("\n\n", array_filter($lines)),
            'ppdb_information',
            'Informasi PPDB Terkini (Database SMKN 1 Subang)',
            [['type' => 'navigate', 'path' => '/ppdb', 'title' => 'Halaman PPDB SMKN 1 Subang']],
        );
    }

    /**
     * @return FastPathResult
     */
    private function respondMajorList(): array
    {
        $tool = new GetJurusanInfoTool();
        $summary = $this->decodeToolJson((string) $tool->handle(new Request([])));

        // Ambil detail lengkap tiap jurusan dari tool yang sama (di-cache, jadi murah),
        // agar jawaban memuat deskripsi + prospek karir (bukan hanya nama & slug).
        $details = [];
        foreach ($summary as $item) {
            $nama = $item['nama'] ?? null;
            if (! is_string($nama) || $nama === '') {
                continue;
            }

            $found = $this->decodeToolJson((string) $tool->handle(new Request(['nama' => $nama])));
            foreach ($found as $entry) {
                if (($entry['nama'] ?? null) === $nama) {
                    $details[] = $entry;
                    break;
                }
            }
        }

        if (empty($details)) {
            $details = $summary;
        }

        $lines = ['Berikut **10 kompetensi keahlian** yang tersedia di SMKN 1 Subang:'];

        foreach ($details as $index => $major) {
            $nama = $major['nama'] ?? 'Jurusan';
            $lines[] = "\n**".($index + 1).". {$nama}**";

            $deskripsi = trim((string) ($major['deskripsi'] ?? ''));
            if ($deskripsi !== '') {
                $lines[] = $deskripsi;
            }

            $karir = $major['prospek_karir'] ?? [];
            if (is_array($karir) && ! empty($karir)) {
                $lines[] = '- *Prospek karir:* '.implode(', ', array_slice($karir, 0, 6)).'.';
            }

            $mapel = $major['mata_pelajaran_utama'] ?? [];
            if (is_array($mapel) && ! empty($mapel)) {
                $lines[] = '- *Mata pelajaran utama:* '.implode(', ', array_slice($mapel, 0, 6)).'.';
            }
        }

        $lines[] = 'Mau saya jelaskan salah satu jurusan lebih detail, atau bantu memilih jurusan sesuai minatmu? 😊';

        return $this->result(
            implode("\n", $lines),
            'school_information',
            'Basis Data Kompetensi Keahlian (Database SMKN 1 Subang)',
            [['type' => 'navigate', 'path' => '/jurusan', 'title' => 'Daftar Jurusan SMKN 1 Subang']],
        );
    }

    /**
     * @return FastPathResult
     */
    private function respondSchoolFact(string $message, string $normalized): array
    {
        $section = $this->guessSchoolSection($normalized);
        $data = $this->decodeToolJson(
            (string) (new GetSchoolInfoTool)->handle(new Request($section === null ? [] : ['section' => $section]))
        );

        // Bila meminta ringkasan (tanpa section), data berisi banyak field — format terprioritas.
        if ($section === null) {
            return $this->result(
                $this->formatSchoolSummary($data),
                'school_information',
                'Basis Data Profil Resmi (Database SMKN 1 Subang)',
                [['type' => 'navigate', 'path' => '/profil', 'title' => 'Lihat Profil SMKN 1 Subang']],
            );
        }

        return $this->result(
            $this->formatSchoolSection($section, $data),
            'school_information',
            'Basis Data Profil Resmi (Database SMKN 1 Subang)',
            [['type' => 'navigate', 'path' => '/profil', 'title' => 'Lihat Profil SMKN 1 Subang']],
        );
    }

    // =========================================================================
    // FORMATTER
    // =========================================================================

    /**
     * @param  array<int, array<string, mixed>>  $jadwal
     */
    private function formatJadwal(array $jadwal): string
    {
        if (empty($jadwal)) {
            return '';
        }

        $lines = ['**Jadwal Pendaftaran:**'];
        foreach ($jadwal as $tahap) {
            $nama = $tahap['tahap'] ?? '-';
            $mulai = $tahap['tanggal_mulai'] ?? '';
            $selesai = $tahap['tanggal_selesai'] ?? '';
            $tanggal = $mulai !== '' && $selesai !== '' && $mulai !== $selesai
                ? "{$mulai} – {$selesai}"
                : ($mulai ?: $selesai);
            $lines[] = '- '.$nama.($tanggal !== '' ? ": {$tanggal}" : '').'.';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $jalur
     */
    private function formatJalur(array $jalur): string
    {
        if (empty($jalur)) {
            return '';
        }

        $lines = ['**Jalur Seleksi:**'];
        foreach ($jalur as $item) {
            $nama = $item['nama'] ?? '-';
            $deskripsi = $item['deskripsi'] ?? '';
            $kuota = $item['kuota_persen'] ?? null;
            $suffix = $kuota !== null ? " ({$kuota}%)" : '';
            $lines[] = '- **'.$nama.'**'.$suffix.($deskripsi !== '' ? ': '.$deskripsi : '');
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $biaya
     */
    private function formatBiaya(array $biaya): string
    {
        if (empty($biaya)) {
            return '';
        }

        $lines = ['**Biaya:**'];
        foreach ($biaya as $key => $value) {
            if (is_string($value) && $value !== '') {
                $lines[] = '- '.ucfirst(str_replace('_', ' ', (string) $key)).': '.$value;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $kontak
     */
    private function formatKontakPanitia(array $kontak): string
    {
        $parts = [];
        foreach ($kontak as $key => $value) {
            if (is_string($value) && $value !== '') {
                $parts[] = ucfirst(str_replace('_', ' ', (string) $key)).': '.$value;
            }
        }

        return empty($parts) ? '' : '**Kontak Panitia PPDB:** '.implode(' | ', $parts);
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function bulletListFromStrings(string $heading, array $items): string
    {
        $strings = array_values(array_filter($items, fn ($item) => is_string($item) && $item !== ''));
        if (empty($strings)) {
            return '';
        }

        return $heading."\n".implode("\n", array_map(fn ($item) => '- '.$item, $strings));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatSchoolSection(string $section, array $data): string
    {
        $body = $data[$section] ?? $data;

        $text = match ($section) {
            'kontak' => $this->humanizeAssoc(
                'Berikut **kontak resmi SMKN 1 Subang**:',
                array_merge(
                    is_array($body['kontak'] ?? null) ? $body['kontak'] : (is_array($body) ? $this->onlyStringValues($body) : []),
                    is_array($body['media_sosial'] ?? null) ? ['Media sosial' => $this->inlineValues($body['media_sosial'])] : [],
                ),
            ),
            'alamat' => $this->formatAlamat($body, $data),
            'kepala_sekolah' => $this->formatKepalaSekolah($body),
            'visi_misi' => $this->formatVisiMisi($body, $data),
            'fasilitas' => $this->formatFasilitas($body, $data),
            'ekskul' => $this->bulletListFromStrings('**Ekstrakurikuler di SMKN 1 Subang:**', $this->toList($body)),
            'siswa', 'guru', 'statistik' => $this->formatStatistik($body, $data),
            'program_unggulan' => $this->bulletListFromStrings('**Program Unggulan SMKN 1 Subang:**', $this->toList($body)),
            'sejarah' => is_string($body) ? "**Sejarah SMKN 1 Subang:**\n\n{$body}" : $this->humanizeAssoc('**Sejarah SMKN 1 Subang:**', $this->onlyStringValues($body)),
            default => $this->humanizeAssoc('Berikut informasinya:', $this->onlyStringValues(is_array($body) ? $body : ['Informasi' => $body])),
        };

        return trim($text) !== '' ? $text : $this->formatSchoolSummary($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatSchoolSummary(array $data): string
    {
        $lines = ['Berikut **ringkasan profil SMKN 1 Subang**:'];

        $nama = $data['nama_resmi'] ?? $data['nama_pendek'] ?? null;
        if ($nama) {
            $lines[] = "\n**Nama sekolah:** {$nama}";
        }

        $npsn = $data['npsn'] ?? null;
        if ($npsn) {
            $lines[] = '**NPSN:** '.$npsn;
        }

        $akreditasi = $data['akreditasi'] ?? null;
        if ($akreditasi) {
            $lines[] = '**Akreditasi:** '.$akreditasi;
        }

        $tahun = $data['tahun_berdiri'] ?? null;
        if ($tahun) {
            $lines[] = '**Tahun berdiri:** '.$tahun;
        }

        $kepsek = $data['kepala_sekolah'] ?? null;
        if (is_string($kepsek) && $kepsek !== '') {
            $lines[] = '**Kepala sekolah:** '.$kepsek;
        } elseif (is_array($kepsek) && isset($kepsek['nama'])) {
            $lines[] = '**Kepala sekolah:** '.$kepsek['nama'];
        }

        $alamat = $data['alamat'] ?? null;
        if (is_string($alamat) && $alamat !== '') {
            $lines[] = '**Alamat:** '.$alamat;
        } elseif (is_array($alamat) && isset($alamat['lengkap'])) {
            $lines[] = '**Alamat:** '.$alamat['lengkap'];
        }

        $kontak = $data['kontak'] ?? null;
        if (is_array($kontak)) {
            $lines[] = '**Kontak:** '.$this->inlineValues($kontak);
        }

        $siswa = $data['jumlah_siswa'] ?? null;
        if ($siswa) {
            $lines[] = '**Jumlah siswa:** '.$siswa;
        }

        $guru = $data['jumlah_guru_staf'] ?? null;
        if ($guru) {
            $lines[] = '**Jumlah guru/staf:** '.$guru;
        }

        $program = $data['program_unggulan'] ?? null;
        if (is_string($program) && $program !== '') {
            $lines[] = '**Program unggulan:** '.$program;
        }

        $lines[] = "\nIngin tahu lebih detail tentang fasilitas, jurusan, visi-misi, atau kontak? Tinggal tanya saja. 😊";

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatAlamat(mixed $body, array $data): string
    {
        $alamat = is_array($body) ? $body : [];

        if (isset($alamat['lengkap'])) {
            $text = $alamat['lengkap'];
        } elseif (isset($alamat['jalan'])) {
            $text = trim(implode(', ', array_filter([
                $alamat['jalan'] ?? null,
                $alamat['kelurahan'] ?? null,
                $alamat['kecamatan'] ?? null,
                $alamat['kabupaten'] ?? null,
                $alamat['provinsi'] ?? null,
            ])));
        } else {
            $text = is_string($body) ? $body : '';
        }

        $lines = ['**Alamat SMKN 1 Subang:**'];
        $lines[] = $text !== '' ? $text : 'Informasi alamat belum tersedia.';

        $koordinat = $data['koordinat'] ?? null;
        if (is_array($koordinat) && isset($koordinat['google_maps_url'])) {
            $lines[] = '**Peta (Google Maps):** '.$koordinat['google_maps_url'];
        }

        return implode("\n", $lines);
    }

    private function formatKepalaSekolah(mixed $body): string
    {
        if (is_array($body)) {
            $nama = $body['nama'] ?? '';
            $sambutan = $body['sambutan'] ?? '';
            $text = $nama !== '' ? "Kepala sekolah SMKN 1 Subang saat ini adalah **{$nama}**." : '';
            if ($sambutan !== '') {
                $text .= "\n\n\"{$sambutan}\"";
            }

            return $text !== '' ? $text : 'Informasi kepala sekolah belum tersedia.';
        }

        return is_string($body) && $body !== ''
            ? "Kepala sekolah SMKN 1 Subang saat ini adalah **{$body}**."
            : 'Informasi kepala sekolah belum tersedia.';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatVisiMisi(mixed $body, array $data): string
    {
        $visi = null;
        $misi = [];

        if (is_array($body) && isset($body['visi'])) {
            $visi = $body['visi'];
            $misi = $body['misi'] ?? [];
        } else {
            $visi = $data['visi'] ?? null;
            $misi = $data['misi'] ?? [];
        }

        $lines = [];
        if (is_string($visi) && $visi !== '') {
            $lines[] = "**Visi:**\n{$visi}";
        }
        if (is_array($misi) && ! empty($misi)) {
            $lines[] = "**Misi:**\n".implode("\n", array_map(
                fn ($m) => '- '.preg_replace('/^\s*\d+[\.\)]\s*/', '', $m),
                array_filter($misi, 'is_string'),
            ));
        }

        return implode("\n\n", $lines) ?: 'Informasi visi & misi belum tersedia.';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatFasilitas(mixed $body, array $data): string
    {
        $facilities = [];
        if (is_array($body)) {
            $facilities = $body['ringkasan_fasilitas'] ?? $body;
        }

        $names = $this->collectNames($facilities);

        $count = $data['jumlah_fasilitas'] ?? null;
        $header = '**Fasilitas SMKN 1 Subang**'.($count ? " (total {$count} unit)" : '').':';

        if (empty($names)) {
            return $header.' informasi rincian belum tersedia.';
        }

        return $header."\n".implode("\n", array_map(fn ($n) => '- '.$n, array_slice($names, 0, 15)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatStatistik(mixed $body, array $data): string
    {
        $source = is_array($body) ? $body : $data;

        $map = [
            'jumlah_siswa' => 'Jumlah siswa',
            'jumlah_guru_staf' => 'Jumlah guru & staf',
            'jumlah_ruang_kelas' => 'Jumlah ruang kelas',
        ];

        $lines = ['**Statistik SMKN 1 Subang:**'];
        foreach ($map as $key => $label) {
            if (! empty($source[$key])) {
                $lines[] = "- {$label}: {$source[$key]}";
            }
        }

        return count($lines) > 1 ? implode("\n", $lines) : 'Informasi statistik belum tersedia.';
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function detectPpdbSection(string $message): ?string
    {
        $normalized = mb_strtolower($message);

        $map = [
            'jadwal' => ['jadwal', 'kapan', 'tanggal', 'waktu', 'gelombang'],
            'syarat' => ['syarat', 'persyaratan'],
            'berkas' => ['berkas', 'dokumen', 'dokumen apa'],
            'jalur' => ['jalur', 'zonasi', 'prestasi', 'afirmasi', 'seleksi'],
            'alur' => ['alur', 'prosedur', 'cara daftar', 'cara mendaftar', 'tahapan', 'langkah'],
            'biaya' => ['biaya', 'gratis', 'bayar'],
            'kontak' => ['kontak', 'telepon', 'whatsapp', 'email', 'panitia'],
        ];

        foreach ($map as $section => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return $section;
                }
            }
        }

        return null;
    }

    private function guessSchoolSection(string $normalized): ?string
    {
        $map = [
            'kontak' => ['kontak', 'telepon', 'telpon', 'nomor', 'email', 'website', 'situs'],
            'alamat' => ['alamat', 'lokasi', 'peta', 'maps', 'dimana', 'di mana'],
            'kepala_sekolah' => ['kepala sekolah', 'kepsek', 'principal'],
            'sejarah' => ['sejarah', 'tahun berdiri', 'berdiri'],
            'visi_misi' => ['visi', 'misi'],
            'fasilitas' => ['fasilitas', 'sarana', 'prasarana', 'lab', 'laboratorium', 'perpustakaan', 'bengkel'],
            'ekskul' => ['ekskul', 'ekstrakurikuler'],
            'siswa' => ['jumlah siswa', 'jumlah murid', 'berapa siswa'],
            'guru' => ['jumlah guru', 'jumlah staf'],
            'program_unggulan' => ['program unggulan', 'beraksi'],
        ];

        foreach ($map as $section => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return $section;
                }
            }
        }

        return null;
    }

    /**
     * Mengubah string output tool (header + JSON + trailing teks) menjadi array PHP.
     * Output tool sering menambahkan instruksi setelah blok JSON, sehingga kita HARUS
     * mengambil hanya substring JSON yang seimbang (balanced), bukan sisanya sampai akhir.
     *
     * @return array<int|string, mixed>
     */
    private function decodeToolJson(string $raw): array
    {
        $start = null;
        $opener = null;
        $length = strlen($raw);

        for ($i = 0; $i < $length; $i++) {
            if ($raw[$i] === '[' || $raw[$i] === '{') {
                $start = $i;
                $opener = $raw[$i];
                break;
            }
        }

        if ($start === null) {
            return [];
        }

        $closer = $opener === '[' ? ']' : '}';
        $depth = 0;
        $inString = false;
        $escaped = false;
        $end = null;

        for ($i = $start; $i < $length; $i++) {
            $char = $raw[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;
            } elseif ($char === $opener) {
                $depth++;
            } elseif ($char === $closer) {
                $depth--;
                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }

        if ($end === null) {
            return [];
        }

        $decoded = json_decode(substr($raw, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $assoc
     */
    private function humanizeAssoc(string $heading, array $assoc): string
    {
        $assoc = array_filter($assoc, fn ($v) => is_string($v) && $v !== '' || is_numeric($v));
        if (empty($assoc)) {
            return '';
        }

        $lines = [$heading];
        foreach ($assoc as $key => $value) {
            $label = ucfirst(str_replace('_', ' ', (string) $key));
            $lines[] = "- {$label}: {$value}";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $assoc
     */
    private function inlineValues(array $assoc): string
    {
        $parts = [];
        foreach ($assoc as $key => $value) {
            if (is_string($value) && $value !== '') {
                $parts[] = ucfirst(str_replace('_', ' ', (string) $key)).': '.$value;
            }
        }

        return implode(' | ', $parts);
    }

    /**
     * @param  array<string, mixed>  $assoc
     * @return array<string, string>
     */
    private function onlyStringValues(array $assoc): array
    {
        $out = [];
        foreach ($assoc as $key => $value) {
            if (is_string($value) && $value !== '') {
                $out[(string) $key] = $value;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function toList(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $out[] = $item;
            } elseif (is_array($item)) {
                $name = $item['nama'] ?? $item['name'] ?? null;
                if (is_string($name) && $name !== '') {
                    $out[] = $name;
                }
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function collectNames(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $out = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $name = $item['nama'] ?? $item['name'] ?? null;
                if (is_string($name) && $name !== '') {
                    $out[] = $name;
                }
            } elseif (is_string($item) && $item !== '') {
                $out[] = $item;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  list<string>  $keywords
     */
    private function matches(string $normalized, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string>  $sources
     * @param  array<array{type: string, path: string, title: string}>  $actions
     * @return FastPathResult
     */
    private function result(string $answer, string $intent, string $source, array $actions): array
    {
        return [
            'answer' => $answer,
            'intent' => $intent,
            'sources' => [$source],
            'actions' => $actions,
            'mode' => 'ai-agent',
        ];
    }
}
