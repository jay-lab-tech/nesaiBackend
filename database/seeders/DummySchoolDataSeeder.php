<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\News;
use App\Models\Ppdb;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DUMMY / PLACEHOLDER DATA untuk News, PPDB, dan FAQ — bukan data resmi
 * SMKN 1 Subang. School dan Major sudah diisi data asli lewat
 * SchoolPublicDataSeeder, jadi TIDAK diulang di sini.
 *
 * Alasan bagian ini masih dummy: situs publik sekolah belum menyajikan
 * konten berita/PPDB/FAQ yang siap diimpor apa adanya (lihat catatan di
 * dokumen inventaris — PPDB aktif & FAQ belum terstruktur untuk publik).
 * Ganti/hapus seeder ini begitu konten resmi tersedia.
 */
class DummySchoolDataSeeder extends Seeder
{
    public function run(): void
    {
        $newsData = [
            [
                'title' => 'Pendaftaran PPDB 2027/2028 Segera Dibuka',
                'excerpt' => 'Simak jadwal dan persyaratan pendaftaran siswa baru tahun ajaran 2027/2028. (dummy)',
                'body' => 'Isi lengkap berita mengenai pembukaan PPDB akan diperbarui dengan informasi resmi dari sekolah. (dummy)',
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Siswa Raih Prestasi di Kompetisi Tingkat Provinsi',
                'excerpt' => 'Tim siswa berhasil meraih prestasi dalam kompetisi antar SMK. (dummy)',
                'body' => 'Isi lengkap berita prestasi siswa akan diperbarui dengan informasi resmi dari sekolah. (dummy)',
                'published_at' => now()->subDays(10),
            ],
            [
                'title' => 'Kunjungan Industri ke Mitra Dunia Kerja',
                'excerpt' => 'Siswa kelas 12 melakukan kunjungan industri untuk memperluas wawasan dunia kerja. (dummy)',
                'body' => 'Isi lengkap berita kunjungan industri akan diperbarui dengan informasi resmi dari sekolah. (dummy)',
                'published_at' => now()->subDays(20),
            ],
        ];

        foreach ($newsData as $news) {
            News::create([
                'title' => $news['title'],
                'slug' => Str::slug($news['title']),
                'excerpt' => $news['excerpt'],
                'body' => $news['body'],
                'published_at' => $news['published_at'],
            ]);
        }

        Ppdb::create([
            'title' => 'PPDB Tahun Ajaran 2027/2028 (Dummy)',
            'description' => 'Informasi penerimaan peserta didik baru — data ini masih placeholder, menunggu ketentuan resmi dari sekolah.',
            'requirements' => [
                'Fotokopi ijazah/SKL SMP/MTs',
                'Fotokopi Kartu Keluarga',
                'Pas foto berwarna 3x4',
                'Surat keterangan sehat (dummy)',
            ],
            'schedule' => [
                ['tahap' => 'Pendaftaran Online', 'tanggal' => '1 - 15 Juni 2027 (dummy)'],
                ['tahap' => 'Seleksi Berkas', 'tanggal' => '16 - 20 Juni 2027 (dummy)'],
                ['tahap' => 'Pengumuman', 'tanggal' => '25 Juni 2027 (dummy)'],
                ['tahap' => 'Daftar Ulang', 'tanggal' => '26 - 30 Juni 2027 (dummy)'],
            ],
            'is_active' => true,
        ]);

        $faqsData = [
            ['question' => 'Bagaimana cara mendaftar PPDB?', 'answer' => 'Pendaftaran dilakukan secara online melalui website sekolah pada periode yang ditentukan. (dummy)'],
            ['question' => 'Apa saja program keahlian yang tersedia?', 'answer' => 'Tersedia 10 program keahlian, lihat halaman Program Keahlian untuk daftar lengkapnya.'],
            ['question' => 'Apakah ada biaya pendaftaran?', 'answer' => 'Informasi biaya pendaftaran akan diumumkan resmi oleh pihak sekolah. (dummy)'],
            ['question' => 'Bagaimana cara menghubungi pihak sekolah?', 'answer' => 'Silakan hubungi kontak resmi sekolah yang tertera di halaman utama.'],
        ];

        foreach ($faqsData as $index => $faq) {
            Faq::create([
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'sort_order' => $index,
            ]);
        }
    }
}