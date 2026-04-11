<?php

namespace App\Livewire\Settings;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\SystemDocument;
use App\Models\User;
use App\Services\SelcomCheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class SystemHealthDashboard extends Component
{
    use WithFileUploads;

    // ── KPI counts ──────────────────────────────────────────────────
    public int $totalActiveUsers = 0;

    public int $totalActiveLoans = 0;

    public int $totalCustomers = 0;

    public int $overdueLoans = 0;

    // ── Database ─────────────────────────────────────────────────────
    public string $dbSize = 'N/A';

    public string $dbDriver = 'N/A';

    public string $dbName = 'N/A';

    public bool $dbConnected = false;

    // ── Server ───────────────────────────────────────────────────────
    public string $serverUptime = 'N/A';

    public string $diskFree = 'N/A';

    public string $diskTotal = 'N/A';

    public int $diskUsedPct = 0;

    // ── Redis ────────────────────────────────────────────────────────
    public string $redisStatus = 'Unknown';

    public bool $redisConnected = false;

    // ── Queue ────────────────────────────────────────────────────────
    public string $queueDriver = 'N/A';

    public int $pendingJobs = 0;

    public int $failedJobs = 0;

    // ── Cache ────────────────────────────────────────────────────────
    public string $cacheDriver = 'N/A';

    // ── Mail ─────────────────────────────────────────────────────────
    public string $mailDriver = 'N/A';

    // ── Beem SMS ─────────────────────────────────────────────────────
    public string $beemStatus = 'Not Configured';

    public bool $beemConfigured = false;

    // ── Selcom Checkout ─────────────────────────────────────────────
    public string $selcomStatus = 'Not Configured';

    public bool $selcomConfigured = false;

    public string $selcomVendor = 'N/A';

    // ── Customer agreement upload ─────────────────────────────────
    /** @var TemporaryUploadedFile|null */
    public $customerAgreementUpload = null;

    // ── App info ─────────────────────────────────────────────────────
    public string $phpVersion = 'N/A';

    public string $laravelVersion = 'N/A';

    public string $appEnv = 'N/A';

    public bool $appDebug = false;

    /** @var array<string, bool> */
    public array $phpExtensions = [];

    public string $lastRefreshed = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccess('settings.view'), 403);
        $this->loadHealthData();
    }

    public function loadHealthData(): void
    {
        $this->lastRefreshed = now()->format('d M Y, H:i:s');

        // ── App info ───────────────────────────────────────────────
        $this->phpVersion = PHP_VERSION;
        $this->laravelVersion = app()->version();
        $this->appEnv = config('app.env', 'unknown');
        $this->appDebug = (bool) config('app.debug');

        $this->phpExtensions = [
            'pdo' => extension_loaded('pdo'),
            'pgsql' => extension_loaded('pgsql') || extension_loaded('pdo_pgsql'),
            'mbstring' => extension_loaded('mbstring'),
            'bcmath' => extension_loaded('bcmath'),
            'curl' => extension_loaded('curl'),
            'gd' => extension_loaded('gd'),
            'zip' => extension_loaded('zip'),
            'redis' => extension_loaded('redis'),
            'openssl' => extension_loaded('openssl'),
            'tokenizer' => extension_loaded('tokenizer'),
        ];

        // ── Database ───────────────────────────────────────────────
        $this->dbDriver = config('database.default', 'unknown');
        $this->dbName = config("database.connections.{$this->dbDriver}.database", 'N/A');

        try {
            DB::connection()->getPdo();
            $this->dbConnected = true;

            if ($this->dbDriver === 'pgsql') {
                $result = DB::select("SELECT pg_size_pretty(pg_database_size('{$this->dbName}')) as size");
                $this->dbSize = $result[0]->size ?? 'N/A';
            } elseif ($this->dbDriver === 'mysql') {
                $result = DB::select('SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.TABLES WHERE table_schema = ?', [$this->dbName]);
                $this->dbSize = ($result[0]->size ?? 0).' MB';
            } else {
                $this->dbSize = 'SQLite';
            }
        } catch (\Throwable) {
            $this->dbConnected = false;
            $this->dbSize = 'Unreachable';
        }

        // ── Server uptime ──────────────────────────────────────────
        try {
            if (PHP_OS_FAMILY === 'Linux' && file_exists('/proc/uptime')) {
                $secs = (int) explode(' ', file_get_contents('/proc/uptime'))[0];
                $this->serverUptime = intdiv($secs, 86400).'d '.intdiv($secs % 86400, 3600).'h '.intdiv($secs % 3600, 60).'m';
            } else {
                $this->serverUptime = 'macOS / non-Linux';
            }
        } catch (\Throwable) {
            $this->serverUptime = 'Unavailable';
        }

        // ── Disk space ────────────────────────────────────────────
        try {
            $path = base_path();
            $free = disk_free_space($path);
            $total = disk_total_space($path);
            $used = $total - $free;
            $this->diskFree = $this->formatBytes((int) $free);
            $this->diskTotal = $this->formatBytes((int) $total);
            $this->diskUsedPct = $total > 0 ? (int) round(($used / $total) * 100) : 0;
        } catch (\Throwable) {
            $this->diskFree = 'N/A';
            $this->diskTotal = 'N/A';
            $this->diskUsedPct = 0;
        }

        // ── Redis ─────────────────────────────────────────────────
        try {
            Redis::ping('ping');
            $this->redisConnected = true;
            $host = config('database.redis.default.host');
            $port = config('database.redis.default.port');
            $this->redisStatus = "Connected ({$host}:{$port})";
        } catch (\Throwable) {
            $this->redisConnected = false;
            $this->redisStatus = 'Not Available';
        }

        // ── Queue & jobs ──────────────────────────────────────────
        $this->queueDriver = config('queue.default', 'sync');

        try {
            $this->pendingJobs = DB::table('jobs')->count();
        } catch (\Throwable) {
            $this->pendingJobs = 0;
        }

        try {
            $this->failedJobs = DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            $this->failedJobs = 0;
        }

        // ── Cache & Mail ──────────────────────────────────────────
        $this->cacheDriver = config('cache.default', 'file');
        $this->mailDriver = config('mail.default', 'log');

        // ── Beem SMS ──────────────────────────────────────────────
        $apiKey = config('services.beem.api_key') ?? env('BEEM_API_KEY');
        $secretKey = config('services.beem.secret_key') ?? env('BEEM_SECRET_KEY');

        $this->beemConfigured = (bool) ($apiKey && $secretKey);
        $this->beemStatus = $this->beemConfigured ? 'Credentials Configured' : 'API Keys Not Set';

        $selcomConfig = app(SelcomCheckoutService::class)->configurationSummary();
        $this->selcomConfigured = $selcomConfig['configured'];
        $this->selcomStatus = $this->selcomConfigured ? 'Checkout credentials configured' : 'Vendor, API key, or secret missing';
        $this->selcomVendor = $selcomConfig['vendor'] ?: 'N/A';

        // ── Business counts ───────────────────────────────────────
        try {
            $this->totalActiveUsers = User::where('is_active', true)->count();
            $this->totalActiveLoans = Loan::where('status', 'active')->count();
            $this->totalCustomers = Customer::count();
            $this->overdueLoans = Loan::whereIn('status', ['overdue', 'defaulted'])->count();
        } catch (\Throwable) {
            // non-critical
        }
    }

    public function refresh(): void
    {
        $this->loadHealthData();
        $this->dispatch('toast', message: 'Health data refreshed.', type: 'success');
    }

    public function uploadCustomerAgreement(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'owner']), 403);

        $this->validate([
            'customerAgreementUpload' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        /** @var TemporaryUploadedFile $file */
        $file = $this->customerAgreementUpload;
        $storedPath = $file->store('agreements', 'public');

        SystemDocument::query()
            ->where('key', 'kyc_customer_agreement')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        SystemDocument::create([
            'key' => 'kyc_customer_agreement',
            'title' => 'KYC Customer Agreement',
            'disk' => 'public',
            'path' => $storedPath,
            'mime_type' => $file->getMimeType() ?: 'application/pdf',
            'is_active' => true,
            'uploaded_by' => auth()->id(),
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ],
        ]);

        $this->reset('customerAgreementUpload');
        $this->dispatch('toast', message: 'Customer agreement PDF uploaded successfully.', type: 'success');
    }

    public function getActiveAgreementDocumentProperty(): ?SystemDocument
    {
        return SystemDocument::query()
            ->with('uploadedBy')
            ->where('key', 'kyc_customer_agreement')
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1_073_741_824) {
            return round($bytes / 1_073_741_824, 1).' GB';
        }

        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 1).' MB';
        }

        return round($bytes / 1024, 1).' KB';
    }

    public function render()
    {
        $activeAgreementDocument = $this->activeAgreementDocument;

        return view('livewire.settings.system-health-dashboard', compact('activeAgreementDocument'))
            ->layout('layouts.app', ['title' => 'System Health']);
    }
}
