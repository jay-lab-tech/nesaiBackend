<?php

namespace Database\Seeders;

use App\Models\AdmissionStat;
use App\Models\Alumni;
use App\Models\AlumniTrackingStat;
use App\Models\Career;
use App\Models\Extracurricular;
use App\Models\Facility;
use App\Models\Innovation;
use App\Models\Major;
use App\Models\MajorSubject;
use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data ASLI (bukan dummy) diambil dari situs & dokumen profil resmi
 * SMK Negeri 1 Subang (smkn1subang.sch.id), per inventaris & dokumen
 * profil PDF yang diunggah 19-20 Sept 2026.
 *
 * Field yang datanya belum tersedia/masih kontradiktif di sumber publik
 * SENGAJA dibiarkan null, bukan ditebak. Lihat catatan di tiap bagian.
 */
class SchoolPublicDataSeeder extends Seeder
{
    public function run(): void
    {
        School::updateOrCreate(['npsn' => '20233680'], [
            'name' => 'SMK Negeri 1 Subang',
            'npsn' => '20233680',
            'address' => 'Jalan Arief Rahman Hakim No. 35, Kelurahan Cigadung, Kecamatan Subang, Kabupaten Subang, Jawa Barat 41213',
            'phone' => '0260-411410',
            'email' => 'info@smkn1subang.sch.id',
            'accreditation' => 'A',
            'founded_year' => 1965,
            'area_size' => '18.882 m2',
            'principal_name' => 'Walyati Retnoningsih, S.Si., M.AP',
            'staff_count' => 159,
            'student_count' => 2589,
            'classroom_count' => null,
            'classroom_count_min' => 50,
            'classroom_count_max' => 52,
            'stats_updated_at' => null,
            'description' => 'SMK Negeri 1 Subang adalah sekolah menengah kejuruan negeri di Kabupaten Subang, Jawa Barat, terakreditasi A, dengan program unggulan BerAKSI (Berkarakter, Adaptif, Kompeten, Sinergis, Inovatif).',
            'vision' => 'Menjadikan Lulusan yang Berkarakter Agamis, Berjiwa Wirausaha, Mampu Beradaptasi dengan Perkembangan Zaman, Kompeten di Bidangnya, Peduli Terhadap Lingkungan dan menerapkan BLUD pada Tahun 2029.',
            'mission' => "1. Menyiapkan lulusan yang berkarakter agamis.\n2. Menyiapkan lulusan yang berjiwa wirausaha.\n3. Menyiapkan lulusan yang mampu beradaptasi dengan perkembangan zaman.\n4. Menyiapkan lulusan yang kompeten di bidangnya.\n5. Menyiapkan lulusan yang peduli terhadap lingkungan.\n6. Menyiapkan lulusan yang kompeten sesuai dengan implementasi BLUD.",
            'social_links' => [
                'facebook' => 'https://facebook.com/officialsmkn1subang',
                'instagram' => 'https://instagram.com/officialsmkn1subang',
                'youtube' => 'https://youtube.com/c/SMKNegeri1SubangOfficial',
            ],
        ]);

        $majors = [
            [
                'name' => 'Akuntansi dan Keuangan Lembaga',
                'summary' => 'Pembukuan dan administrasi keuangan untuk perusahaan dan instansi.',
                'logo' => 'majors/akl.svg',
                'subjects' => ['Etika Profesi', 'Spreadsheet', 'Akuntansi Dasar', 'Perbankan Dasar', 'Praktikum Akuntansi', 'Komputer Akuntansi', 'Administrasi Pajak'],
                'careers' => ['Staf Akuntansi', 'Administrasi Keuangan', 'Staf Bank', 'Staf Pajak', 'Wirausaha'],
                'note' => 'Mitra industri: PT BPR Karya Utama Jabar; KAP Bambang Moedjiono dan Rekan; BRI; BJB; BTN.',
            ],
            [
                'name' => 'Pemasaran',
                'summary' => 'Penjualan, promosi, pelayanan konsumen, bisnis ritel, e-commerce, dan pemasaran digital. Konsentrasi Bisnis Ritel dan Bisnis Digital.',
                'logo' => 'majors/bdp.svg',
                'subjects' => ['Dasar Bisnis Ritel', 'Penanganan Produk', 'Penjualan', 'Persediaan', 'Perencanaan Bisnis', 'Teknologi Ritel', 'Promosi', 'Bisnis Digital'],
                'careers' => ['Pramuniaga', 'Kasir', 'Staf Toko', 'Admin Online Shop', 'Digital Marketer', 'Content Creator', 'Customer Service', 'Wirausaha'],
                'note' => 'Mitra industri: PT Akur Pratama (Yogya Grup); Griya; Yomart; Amanda Mart; Jerbee.',
            ],
            [
                'name' => 'Manajemen Perkantoran dan Layanan Bisnis',
                'summary' => 'Administrasi perkantoran, pelayanan bisnis, dokumen, teknologi perkantoran, dan layanan pelanggan.',
                'logo' => 'majors/mplb.svg',
                'subjects' => ['Proses Bisnis Manajemen', 'Ekonomi Bisnis', 'Dokumen Digital', 'Teknologi Perkantoran', 'Administrasi', 'Kearsipan', 'Kehumasan'],
                'careers' => ['Staf Administrasi', 'Resepsionis', 'Arsiparis', 'Sekretaris', 'Layanan Pelanggan', 'Operator Komputer', 'Wirausaha'],
                'note' => 'Mitra industri: PT Bino Mitra Sejati; Aston Hotel; ASPAPI Jawa Barat; Pegadaian; BJB; PERURI.',
            ],
            [
                'name' => 'Pengembangan Perangkat Lunak dan Gim',
                'summary' => 'Analisis, perancangan, pembuatan, pengujian, dan pemeliharaan perangkat lunak desktop, web, dan mobile.',
                'logo' => 'majors/rpl.svg',
                'subjects' => ['Pemrograman Dasar', 'Jaringan', 'Sistem Komputer', 'Kerja Proyek', 'Database', 'Pemrograman Web dan Perangkat Bergerak', 'Java', 'Android Studio'],
                'careers' => ['Web Developer', 'Software/Mobile Developer', 'Programmer', 'Teknisi IT', 'Pengembang Gim', 'Freelancer', 'Wirausaha'],
                'note' => 'Mitra industri: PT Jerbee Indonesia; PT Surya Tekno Mandiri (ESTIMA); LontarLab; Zimgba; Makerindo; Thawaf; Masagi.',
            ],
            [
                'name' => 'Teknik Jaringan Komputer dan Telekomunikasi',
                'summary' => 'Instalasi, konfigurasi, pemeliharaan, dan perbaikan komputer serta LAN, internet, Wi-Fi, dan fiber optik.',
                'logo' => 'majors/tkj.svg',
                'subjects' => ['Komputer dan Jaringan Dasar', 'Pemrograman', 'Desain Grafis', 'WAN', 'Administrasi Infrastruktur', 'Administrasi Sistem', 'Layanan Jaringan'],
                'careers' => ['Teknisi Komputer/Jaringan', 'Teknisi Internet/Wi-Fi/CCTV', 'IT Support', 'Administrator Jaringan', 'Wirausaha'],
                'note' => 'Mitra industri: PT Jerbee Indonesia; Media Distribusi Prima; LontarLab; Zimgba.',
            ],
            [
                'name' => 'Teknik Otomotif',
                'summary' => 'Perawatan dan perbaikan kendaraan roda dua untuk menjadi mekanik junior atau wirausaha bengkel.',
                'logo' => 'majors/to.svg',
                'subjects' => ['Perawatan Berkala', 'Perbaikan Engine', 'Sasis', 'Kelistrikan', 'Perawatan Bengkel', 'Produk Kreatif'],
                'careers' => ['Mekanik Junior', 'Mekanik Resmi/Umum', 'Service Advisor', 'Teknisi', 'Wirausaha Bengkel'],
                'note' => 'Mitra industri: PT Astra Honda Motor; PT Daya Adicipta Motora; AHASS Golden Motor; Hyundai Safety Riding Center; AutoGas.',
            ],
            [
                'name' => 'Desain Komunikasi Visual',
                'summary' => 'Penerapan keterampilan seni dan komunikasi untuk kebutuhan industri maupun karya seni — meliputi desain tata letak, warna, gambar, ilustrasi, tipografi, videografi, fotografi, edit foto/video, dan animasi.',
                'logo' => 'majors/dkv.svg',
                'subjects' => ['Desain Tata Letak', 'Ilustrasi', 'Tipografi', 'Videografi', 'Fotografi', 'Edit Foto dan Video', 'Animasi'],
                'careers' => [],
                'note' => 'Unit Produksi DKV memproduksi desain banner, stiker, souvenir, ID card, akrilik, name tag, sablon kaos, poster, dan jasa dokumentasi. Mitra: Gulali Books; DEA Grafika; Polycloth; Sintesa; Jonas Photo; Mutfin Graphics.',
            ],
            [
                'name' => 'Teknik Mesin',
                'summary' => 'Produksi komponen mesin dan otomotif menggunakan mesin konvensional maupun CNC.',
                'logo' => 'majors/tm.svg',
                'subjects' => ['Gambar Teknik', 'Pekerjaan Dasar Mesin', 'Bubut', 'Frais', 'Gerinda', 'CNC', 'CADD'],
                'careers' => ['Operator Bubut/Frais/CNC', 'Teknisi Manufaktur', 'Drafter', 'Quality Control', 'Wirausaha'],
                'note' => 'Mitra industri: PT Teknik Jaya Component; PT Pudak Scientific; Pindad.',
            ],
            [
                'name' => 'Kuliner',
                'summary' => 'Pengolahan makanan, penyajian, pelayanan makanan dan minuman, keamanan pangan, dan usaha kuliner.',
                'logo' => 'majors/kuliner.svg',
                'subjects' => ['Keamanan Pangan', 'Bahan Makanan', 'Ilmu Gizi', 'Tata Hidang', 'Pengolahan', 'Cake dan Kue', 'Pastry dan Bakery'],
                'careers' => ['Staf Kitchen', 'Staf Service', 'Cook Helper', 'Pastry', 'Pramusaji', 'Katering', 'Wirausaha'],
                'note' => 'Mitra industri: Hotel Aston Pasteur Bandung; Sari Ater Hotels & Resorts; Grant Hotel Subang.',
            ],
            [
                'name' => 'Teknik Logistik',
                'summary' => 'Perencanaan, pengendalian, penyimpanan, pemindahan, dan distribusi barang.',
                'logo' => 'majors/logistik.svg',
                'subjects' => ['Gambar Teknik', 'Penanganan Material', 'Persediaan', 'Administrasi Gudang', 'Pergudangan', 'Material Handling Equipment', 'Logistik Multimoda'],
                'careers' => ['Procurement', 'Operator MHE', 'Logistic Operator', 'Administrasi', 'Delivery', 'Sorting', 'Staf Gudang'],
                'note' => 'Mitra industri: Politeknik Pos Indonesia; PT Pos Logistik Indonesia; PT Pos Indonesia; Toyota Indonesia; ULBI.',
            ],
        ];

        /** @var array<string, Major> $majorsByName */
        $majorsByName = [];

        foreach ($majors as $data) {
            $description = $data['summary'] . ' ' . $data['note'];

            $major = Major::updateOrCreate(['slug' => Str::slug($data['name'])], [
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'summary' => $data['summary'],
                'description' => trim($description),
                'logo' => $data['logo'] ?? null,
            ]);


            $majorsByName[$data['name']] = $major;

            foreach ($data['subjects'] as $subject) {
                MajorSubject::updateOrCreate(
                    ['major_id' => $major->id, 'name' => $subject],
                    ['description' => null],
                );
            }

            foreach ($data['careers'] as $career) {
                Career::updateOrCreate(
                    ['major_id' => $major->id, 'name' => $career],
                    ['description' => null],
                );
            }
        }

        // Testimoni alumni ASLI dari dokumen profil resmi sekolah.
        // Label jurusan lama dipetakan ke nama program keahlian saat ini
        // sesuai keterangan nomenklatur pada dokumen sumber:
        // TSM -> Teknik Otomotif, Tata Buku -> Akuntansi dan Keuangan Lembaga,
        // Grafika/Persiapan Grafika -> Desain Komunikasi Visual,
        // Teknik Pemesinan -> Teknik Mesin, RPL -> Pengembangan Perangkat Lunak dan Gim.
        $alumniData = [
            ['name' => 'Leman Mulyana, S.E', 'headline' => 'Bank BTN — Akuntansi, lulus 2012', 'major' => 'Akuntansi dan Keuangan Lembaga', 'story' => 'Ilmu yang diperoleh selama belajar di SMKN 1 Subang sangat berguna untuk diaplikasikan di tempat kerja saat ini. Karena selain akademik, SMKN 1 Subang menerapkan etika, agama, dan pengembangan diri.'],
            ['name' => 'Hanum Ayu Lestari', 'headline' => 'BRI Pamanukan — Manajemen Perkantoran, lulus 2009', 'major' => 'Manajemen Perkantoran dan Layanan Bisnis', 'story' => 'Bangga dan bersyukur menjadi alumni SMKN 1 Subang, pendidik yang kompeten, disiplin, tapi tetap bersahabat. Lingkungan belajar yang bersih, asri, dan nyaman, serta fasilitas yang lengkap.'],
            ['name' => 'Aceng Rohmana, S.Kom.', 'headline' => 'BPR-KU Jabar — Pengembangan Perangkat Lunak, lulus 2006', 'major' => 'Pengembangan Perangkat Lunak dan Gim', 'story' => 'Materi yang dipelajari di sekolah lebih mengutamakan praktik sehingga ketika lulus sudah siap untuk bekerja ataupun melanjutkan ke jenjang pendidikan yang lebih tinggi.'],
            ['name' => 'Raehan Herdiansyach', 'headline' => 'Polri — Akuntansi, lulus 2017', 'major' => 'Akuntansi dan Keuangan Lembaga', 'story' => 'Sangat bangga menjadi alumni SMKN 1 Subang, karena di sekolah ini menekankan disiplin dan agama yang menurut saya jadi kunci penting untuk masa depan.'],
            ['name' => 'Legiana R Permana, SDS', 'headline' => 'CEO & Wirausaha — Desain Komunikasi Visual, lulus 2013', 'major' => 'Desain Komunikasi Visual', 'story' => 'Bangga menjadi alumni SMKN 1 Subang, karena pembelajaran di sekolah ini sangat aplikatif dengan kebutuhan dunia usaha & industri yang menjadi modal penting ketika lulus.'],
            ['name' => 'Cecep Cepi Juliana', 'headline' => 'Bank BRI — Akuntansi, lulus 2006', 'major' => 'Akuntansi dan Keuangan Lembaga', 'story' => 'Saya bangga menjadi alumni SMK Negeri 1 Subang. Di sekolah ini saya diajarkan banyak hal yang sangat mendukung dengan pekerjaan dan karir saya saat ini.'],
            ['name' => 'M. Amru Muharram R', 'headline' => 'PT. Telkom — Pengembangan Perangkat Lunak, lulus 2008', 'major' => 'Pengembangan Perangkat Lunak dan Gim', 'story' => 'Menjadi bagian dari SMKN 1 Subang adalah sebuah rasa syukur bagi saya, karena di sekolah kami diajarkan agar siap menjadi manusia yang menerima perubahan di masa depan, dan itu terbukti pada saat ini.'],
            ['name' => 'R. Gantina Rosmala Deswi', 'headline' => 'KPP Pratama — Pemasaran, lulus 2008', 'major' => 'Pemasaran', 'story' => 'Karakter sekolah yang Islamic School Culture menjadikan kami disiplin, jujur, berbudi pekerti luhur, dan berakhlak mulia. Lingkungan sekolah yang luas, bersih, dan asri menambah nyaman saat belajar.'],
            ['name' => 'Jamalludin, S.T', 'headline' => 'Guru SMKN Dawuan — Teknik Otomotif, lulus 2012', 'major' => 'Teknik Otomotif', 'story' => 'Banyak sekali pelajaran dan pengalaman yang saya peroleh selama bersekolah di SMK Negeri 1 Subang. Bukan hanya ilmu akademik, tapi juga tentang mental berjuang yang selalu ditanamkan agar bisa menjadi manusia yang tangguh dan berguna.'],
            ['name' => 'Ipda. Wawan Chaswan, S.A.N', 'headline' => 'Polres Subang — Akuntansi, lulus 1992', 'major' => 'Akuntansi dan Keuangan Lembaga', 'story' => 'Dalam proses belajar, sekolah ini memadukan pengetahuan kognitif, artinya kombinasi antara materi dan praktik yang membuat siswa menjadi lebih luas dalam memahami sebuah materi dengan gambaran nyata dunia kerja.'],
            ['name' => "Risya Helimatussa'diyah", 'headline' => 'Chemi-Con Malaysia Sdn. Bhd — Desain Komunikasi Visual, lulus 2019', 'major' => 'Desain Komunikasi Visual', 'story' => "Sungguh kebanggaan sudah menjadi bagian dari SMKN 1 Subang, banyak ilmu yang bisa kita petik untuk masa depan. Ingat ada kata 'Guru yang hebat akan menghasilkan orang hebat, yaitu kamu salah satunya!' Tetap semangat dalam belajar!"],
            ['name' => 'Brahma Ramdhan', 'headline' => 'TNI — Teknik Mesin, lulus 2019', 'major' => 'Teknik Mesin', 'story' => 'SMK Negeri 1 Subang bukan hanya sekolah, tapi rumah kedua yang membuat saya menemukan banyak hal — lebih dari belajar tapi makna yang didapat sangat besar hingga saya ada di titik sekarang. Salam sukses! Nesas Ceren!'],
        ];

        foreach ($alumniData as $alum) {
            Alumni::updateOrCreate(
                ['name' => $alum['name']],
                [
                    'major_id' => $majorsByName[$alum['major']]->id ?? null,
                    'headline' => $alum['headline'],
                    'story' => $alum['story'],
                ],
            );
        }

        // Sarana dan Prasarana.
        $facilities = [
            'Teaching Factory Mimake Mart', 'Lapang Upacara', 'Masjid', 'Ruang Multimedia',
            'Ruang Kelas', 'Ruang Perpustakaan', 'Lapang Basket dan Futsal', 'Gerbang Utama',
            'Teaching Factory Kuliner dan DKV', 'Ruang Praktik Siswa', 'Bank Mini',
            'Aula Mimake', 'Taman Membaca', 'Laboratorium Komputer',
        ];
        foreach ($facilities as $name) {
            Facility::updateOrCreate(
                ['name' => $name],
                [
                    'category' => 'fasilitas umum',
                    'quantity' => 1,
                    'is_placeholder' => false,
                ],
            );
        }

        // Inventaris lab per jurusan. Detail teknis masih placeholder dan
        // harus diganti setelah sekolah memberikan spesifikasi resmi.
        $laboratories = [
            ['name' => 'Laboratorium RPL', 'major' => 'Pengembangan Perangkat Lunak dan Gim', 'quantity' => 1],
            ['name' => 'Laboratorium TKJ', 'major' => 'Teknik Jaringan Komputer dan Telekomunikasi', 'quantity' => 1],
            ['name' => 'Laboratorium MPLB 1', 'major' => 'Manajemen Perkantoran dan Layanan Bisnis', 'quantity' => 1],
            ['name' => 'Laboratorium MPLB 2', 'major' => 'Manajemen Perkantoran dan Layanan Bisnis', 'quantity' => 1],
            ['name' => 'Laboratorium MPLB 3', 'major' => 'Manajemen Perkantoran dan Layanan Bisnis', 'quantity' => 1],
            ['name' => 'Laboratorium Pemasaran', 'major' => 'Pemasaran', 'quantity' => 1],
            ['name' => 'Laboratorium DKV 1', 'major' => 'Desain Komunikasi Visual', 'quantity' => 1],
            ['name' => 'Laboratorium DKV 2', 'major' => 'Desain Komunikasi Visual', 'quantity' => 1],
            ['name' => 'Laboratorium Teknik Otomotif', 'major' => 'Teknik Otomotif', 'quantity' => 1],
            ['name' => 'Laboratorium AKL', 'major' => 'Akuntansi dan Keuangan Lembaga', 'quantity' => 1],
            ['name' => 'Laboratorium Kuliner', 'major' => 'Kuliner', 'quantity' => 1],
            ['name' => 'Laboratorium Teknik Mesin', 'major' => 'Teknik Mesin', 'quantity' => 1],
            ['name' => 'Laboratorium Teknik Logistik', 'major' => 'Teknik Logistik', 'quantity' => 1],
        ];

        foreach ($laboratories as $laboratory) {
            Facility::updateOrCreate(
                ['name' => $laboratory['name']],
                [
                    'major_id' => $majorsByName[$laboratory['major']]->id ?? null,
                    'category' => 'laboratorium',
                    'quantity' => $laboratory['quantity'],
                    'description' => 'Detail teknis laboratorium masih placeholder/fiktif dan menunggu verifikasi sekolah.',
                    'is_placeholder' => true,
                ],
            );
        }

        // Ekstrakurikuler.
        $extracurriculars = [
            'Basket', 'Voli', 'Futsal', 'Karawitan', 'Seni Tari', 'Marching Band',
            'Paduan Suara', 'Nasyid', 'Pramuka', 'PMR', 'Paskibra', 'BTQ', 'Tahfidz',
            "Qira'at", 'Marawis', 'Muhadatsah', 'Nihongokai', 'English Club',
            'Tata Boga', 'Tata Busana', 'Tata Rias', 'Kirit Club', 'PLH',
            'Bulu Tangkis', 'Karate', 'Pencak Silat', 'Tarung Drajat', 'Jurnalis',
            'PIK-R', 'Kaligrafi', 'Hover',
        ];
        foreach ($extracurriculars as $name) {
            Extracurricular::updateOrCreate(['name' => $name], ['category' => null]);
        }

        // Karya Inovasi ber-HAKI.
        $innovations = [
            ['name' => 'Motocimic', 'major' => 'Teknik Otomotif', 'description' => 'Karya inovasi sepeda listrik, terdaftar HAKI.'],
            ['name' => 'Nesasserator', 'major' => 'Teknik Mesin', 'description' => 'Incinerator (mesin pembakar sampah tanpa asap kotor/polusi), terdaftar HAKI.'],
            ['name' => 'Siborin', 'major' => 'Pengembangan Perangkat Lunak dan Gim', 'description' => 'Standing Information Board — aplikasi papan informasi berdiri, mendapat Hak Kekayaan Intelektual dari Kementerian Hukum dan HAM.'],
        ];
        foreach ($innovations as $data) {
            Innovation::updateOrCreate(
                ['name' => $data['name']],
                [
                    'major_id' => $majorsByName[$data['major']]->id ?? null,
                    'description' => $data['description'],
                    'has_haki' => true,
                ],
            );
        }

        // Data Peminat PPDB (SPMB) per program keahlian, 2022-2025.
        $admission = [
            'Akuntansi dan Keuangan Lembaga' => [2022 => 256, 2023 => 263, 2024 => 180, 2025 => 218],
            'Manajemen Perkantoran dan Layanan Bisnis' => [2022 => 262, 2023 => 278, 2024 => 368, 2025 => 392],
            'Pemasaran' => [2022 => 226, 2023 => 242, 2024 => 256, 2025 => 275],
            'Desain Komunikasi Visual' => [2022 => 92, 2023 => 115, 2024 => 93, 2025 => 127],
            'Pengembangan Perangkat Lunak dan Gim' => [2022 => 174, 2023 => 188, 2024 => 214, 2025 => 235],
            'Teknik Jaringan Komputer dan Telekomunikasi' => [2022 => 147, 2023 => 153, 2024 => 231, 2025 => 242],
            'Teknik Otomotif' => [2022 => 99, 2023 => 117, 2024 => 279, 2025 => 293],
            'Teknik Mesin' => [2022 => 70, 2023 => 128, 2024 => 177, 2025 => 180],
            'Teknik Logistik' => [2022 => 36, 2023 => 54, 2024 => 71, 2025 => 89],
            'Kuliner' => [2022 => 55, 2023 => 67, 2024 => 73, 2025 => 96],
        ];
        foreach ($admission as $majorName => $years) {
            $majorId = $majorsByName[$majorName]->id ?? null;
            if (! $majorId) {
                continue;
            }
            foreach ($years as $year => $count) {
                AdmissionStat::updateOrCreate(
                    ['major_id' => $majorId, 'year' => $year],
                    ['applicant_count' => $count],
                );
            }
        }

        // Data Penelusuran Alumni (persentase status setelah lulus), 2020-2025.
        $alumniTracking = [
            2020 => ['employed' => 62, 'entrepreneur' => 15, 'college' => 13, 'other' => 10],
            2021 => ['employed' => 68, 'entrepreneur' => 18, 'college' => 10, 'other' => 4],
            2022 => ['employed' => 58, 'entrepreneur' => 25, 'college' => 12, 'other' => 5],
            2023 => ['employed' => 56.5, 'entrepreneur' => 25.7, 'college' => 13.8, 'other' => 4],
            2024 => ['employed' => 60.1, 'entrepreneur' => 19.8, 'college' => 11.9, 'other' => 8.3],
            2025 => ['employed' => 64.2, 'entrepreneur' => 20, 'college' => 13.4, 'other' => 2.4],
        ];
        foreach ($alumniTracking as $year => $data) {
            AlumniTrackingStat::updateOrCreate(
                ['year' => $year],
                [
                    'employed_percent' => $data['employed'],
                    'entrepreneur_percent' => $data['entrepreneur'],
                    'college_percent' => $data['college'],
                    'other_percent' => $data['other'],
                ],
            );
        }
    }
}
