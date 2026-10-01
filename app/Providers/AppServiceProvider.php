<?php

namespace App\Providers;

use App\Ai\Support\NesaiCache;
use App\Models\Career;
use App\Models\Extracurricular;
use App\Models\Facility;
use App\Models\Innovation;
use App\Models\Major;
use App\Models\MajorSubject;
use App\Models\News;
use App\Models\Ppdb;
use App\Models\School;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // [CORE-LOGIC: NESAI-CACHE-INVALIDATION]
        // Setiap perubahan data yang dikonsumsi chatbot (profil sekolah, jurusan,
        // PPDB, fasilitas, ekskul, inovasi, berita) langsung membersihkan cache NESAI
        // agar chatbot menyajikan data terbaru tanpa menunggu TTL habis.
        $chatbotDependencies = [
            School::class,
            Major::class,
            MajorSubject::class,
            Career::class,
            Facility::class,
            Extracurricular::class,
            Innovation::class,
            News::class,
            Ppdb::class,
        ];

        foreach ($chatbotDependencies as $modelClass) {
            $flush = static fn (Model $model) => NesaiCache::flush();

            $modelClass::saved($flush);
            $modelClass::deleted($flush);
        }
    }

    /**
     * [CORE-LOGIC: CHAT-RATE-LIMITING]
     * Rate limit dasar (throttle:10,1) mengikat pada IP + session sehingga attacker
     * dapat mereset bucket dengan memutar laravel_session baru tiap request. Limiter
     * bernama di bawah ini mengikat pada IP saja (chat) untuk menutup celah tersebut,
     * sekaligus menambahkan plafon global konkurensi agar lonjakan trafik tidak
     * menghabiskan kuota provider AI berbayar.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('chat', function (Request $request) {
            $perIp = (int) config('chat.rate_limit_per_ip', 10);

            return [
                Limit::perMinute(max(1, $perIp))->by('chat:ip:'.$request->ip()),
                Limit::perMinute(max(1, (int) config('chat.rate_limit_global', 120)))->by('chat:global'),
            ];
        });

        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(max(1, (int) config('chat.rate_limit_search', 30)))
                ->by('search:ip:'.$request->ip());
        });

        RateLimiter::for('recommendations', function (Request $request) {
            return Limit::perMinute(max(1, (int) config('chat.rate_limit_recommendations', 20)))
                ->by('recommendations:ip:'.$request->ip());
        });
    }
}
