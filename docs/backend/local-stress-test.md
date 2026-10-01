# Panduan pemeriksaan beban lokal NESAI

Dokumen ini hanya untuk aplikasi yang berjalan di `127.0.0.1`. Jangan arahkan skenario ke host production.

## Chat dan batas request

- `POST /api/chat` dan `POST /api/v1/nesai/chat` memiliki batas 10 request per menit. Middleware Laravel menghitung pengunjung tanpa autentikasi berdasarkan IP, sehingga beberapa virtual user dari satu mesin berbagi penghitung yang sama.
- Pisahkan HTTP `429` dari kegagalan provider. Pembatasan Laravel mengembalikan `429`; kegagalan Gemini saat ini menghasilkan fallback dengan HTTP `200` dan `data.mode = fallback-error`. Log `nesai.component_failed` membedakan `rate_limited`, `provider_overloaded`, `connection_or_timeout`, `quota_or_credit_exhausted`, dan error provider lain.
- Untuk uji integrasi, gunakan provider fake agar beberapa session dapat diuji tanpa request Gemini. Tes `NesaiLocalBehaviorTest` mencakup tiga session terpisah dan fallback.
- Untuk Gemini nyata, jalankan paling banyak tiga request manual dan berurutan per sesi pengukuran. Tiap panggilan dibatasi 30 detik secara default; satu prompt dibatasi maksimal tiga langkah agent. Tidak ada concurrency Gemini pada runbook ini.

## Endpoint publik

Endpoint daftar menggunakan `per_page=15` secara default dan membatasi nilai maksimum ke 100. Contoh pemeriksaan tunggal:

```powershell
$watch = [Diagnostics.Stopwatch]::StartNew()
$response = Invoke-WebRequest 'http://127.0.0.1:8000/api/v1/majors?per_page=100'
$watch.Stop()
[pscustomobject]@{
    Status = $response.StatusCode
    DurationMs = [math]::Round($watch.Elapsed.TotalMilliseconds, 1)
    ResponseBytes = [Text.Encoding]::UTF8.GetByteCount($response.Content)
}
```

Periksa `meta.per_page`, `meta.total`, jumlah `data`, ukuran body, dan status untuk setiap endpoint. Jangan mengulang request chat dalam loop tanpa mengakumulasikan penghitung rate limit per IP.

## Log dan data chat

- `api.request`: route, method, status HTTP, durasi total, dan ukuran JSON response.
- `nesai.provider`: durasi provider/model dan status berhasil.
- `nesai.component` / `nesai.component_failed`: waktu penyimpanan/history, intent, retrieval, Gemini, atau persistence yang gagal. Prompt pengguna tidak ditulis ke log.
- Cek kandidat retensi dengan `php artisan chat:prune --days=90`. Ini dry-run; data hanya dihapus jika `--apply` ditambahkan.
- History aktif dibatasi 100 pesan per session (maksimal 50 giliran user/asisten); session tanpa pesan baru lebih dari 90 hari menjadi kandidat cleanup manual.
- Tabel `agent_conversations` dan `agent_conversation_messages` saat ini tidak dipakai oleh alur chat NESAI yang diperiksa. Pantau ukurannya terpisah sebelum menghapus atau mengubah tabel tersebut.
